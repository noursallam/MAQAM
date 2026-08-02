<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('name')->nullable()->after('code');
            $table->text('description')->nullable()->after('name');
            // public_code = typed at checkout; personal_grant = only via customer_rewards
            $table->string('assignment')->default('public_code')->after('scope');
            $table->unsignedInteger('usage_limit_per_customer')->nullable()->after('usage_limit');
            $table->decimal('max_discount_amount', 10, 2)->nullable()->after('min_order_amount');
            $table->boolean('is_public')->default(true)->after('is_active');
        });

        Schema::create('customer_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('type', 20); // coupon | discount | product
            $table->string('status', 20)->default('available'); // available | used | expired | revoked
            $table->string('source', 20)->default('wheel'); // wheel | admin | campaign
            $table->string('code', 64)->nullable()->unique();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('wheel_spin_id')->nullable()->constrained('wheel_spins')->nullOnDelete();
            $table->string('amount_type', 20)->nullable(); // percentage | fixed
            $table->decimal('amount_value', 10, 2)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['customer_id', 'type', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('shipping_address_id')->constrained('coupons')->nullOnDelete();
            $table->foreignId('customer_reward_id')->nullable()->after('coupon_id')->constrained('customer_rewards')->nullOnDelete();
            $table->string('coupon_code', 64)->nullable()->after('customer_reward_id');
        });

        Schema::table('customer_rewards', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::table('wheel_spins', function (Blueprint $table) {
            $table->foreignId('customer_reward_id')->nullable()->after('wheel_prize_id')->constrained('customer_rewards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wheel_spins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_reward_id');
        });

        Schema::table('customer_rewards', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_reward_id');
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn('coupon_code');
        });

        Schema::dropIfExists('customer_rewards');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'description',
                'assignment',
                'usage_limit_per_customer',
                'max_discount_amount',
                'is_public',
            ]);
        });
    }
};
