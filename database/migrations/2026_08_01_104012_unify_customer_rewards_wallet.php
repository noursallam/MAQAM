<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy bridge: older local DBs created customer_coupons first.
 * Fresh installs already get customer_rewards from the previous migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_coupons')) {
            return;
        }

        if (! Schema::hasTable('customer_rewards')) {
            Schema::create('customer_rewards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('type', 20);
                $table->string('status', 20)->default('available');
                $table->string('source', 20)->default('wheel');
                $table->string('code', 64)->nullable()->unique();
                $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('wheel_spin_id')->nullable()->constrained('wheel_spins')->nullOnDelete();
                $table->string('amount_type', 20)->nullable();
                $table->decimal('amount_value', 10, 2)->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'status']);
                $table->index(['customer_id', 'type', 'status']);
            });
        }

        foreach (DB::table('customer_coupons')->orderBy('id')->get() as $row) {
            DB::table('customer_rewards')->insertOrIgnore([
                'id' => $row->id,
                'customer_id' => $row->customer_id,
                'type' => $row->coupon_id ? 'coupon' : 'discount',
                'status' => $row->status === 'reserved' ? 'available' : $row->status,
                'source' => $row->source ?: 'wheel',
                'code' => $row->code,
                'coupon_id' => $row->coupon_id,
                'product_id' => null,
                'wheel_spin_id' => $row->wheel_spin_id,
                'amount_type' => $row->discount_type,
                'amount_value' => $row->discount_value,
                'expires_at' => $row->expires_at,
                'used_at' => $row->used_at,
                'order_id' => $row->order_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        if (Schema::hasColumn('orders', 'customer_coupon_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('customer_coupon_id');
            });
        }

        if (! Schema::hasColumn('orders', 'customer_reward_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('customer_reward_id')->nullable()->after('coupon_id')->constrained('customer_rewards')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('wheel_spins', 'customer_coupon_id')) {
            Schema::table('wheel_spins', function (Blueprint $table) {
                $table->dropConstrainedForeignId('customer_coupon_id');
            });
        }

        if (! Schema::hasColumn('wheel_spins', 'customer_reward_id')) {
            Schema::table('wheel_spins', function (Blueprint $table) {
                $table->foreignId('customer_reward_id')->nullable()->after('wheel_prize_id')->constrained('customer_rewards')->nullOnDelete();
            });
        }

        foreach (DB::table('customer_rewards')->whereNotNull('wheel_spin_id')->get() as $reward) {
            DB::table('wheel_spins')->where('id', $reward->wheel_spin_id)->update([
                'customer_reward_id' => $reward->id,
            ]);
        }

        foreach (DB::table('customer_rewards')->whereNotNull('order_id')->get() as $reward) {
            DB::table('orders')->where('id', $reward->order_id)->update([
                'customer_reward_id' => $reward->id,
            ]);
        }

        try {
            Schema::table('customer_rewards', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            });
        } catch (\Throwable) {
            // FK may already exist on fresh path
        }

        Schema::dropIfExists('customer_coupons');
    }

    public function down(): void
    {
        // Irreversible legacy bridge — fresh installs never need this.
    }
};
