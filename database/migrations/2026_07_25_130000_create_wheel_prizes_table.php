<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wheel_prizes', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // points, coupon, product, discount
            $table->string('label_ar');
            $table->string('label_en');
            $table->unsignedInteger('weight')->default(1);
            $table->unsignedInteger('points_amount')->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('discount_type')->nullable(); // percentage, fixed
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->unsignedInteger('stock_limit')->nullable();
            $table->unsignedInteger('awarded_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('wheel_spins', function (Blueprint $table) {
            $table->foreignId('wheel_prize_id')->nullable()->after('rank_id')->constrained('wheel_prizes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wheel_spins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wheel_prize_id');
        });

        Schema::dropIfExists('wheel_prizes');
    }
};
