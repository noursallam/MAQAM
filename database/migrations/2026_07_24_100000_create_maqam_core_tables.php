<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 50);
            $table->string('name_ar', 50);
            $table->integer('min_points');
            $table->integer('max_points')->nullable();
            $table->integer('customer_points_per_scan');
            $table->integer('merchant_points_per_scan');
            $table->float('wheel_win_probability');
            $table->integer('wheel_cost_points')->default(50);
            $table->string('icon_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('role'); // super_admin, content_manager, support, finance
            $table->json('permissions')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->integer('points_balance')->default(0);
            $table->integer('total_points_earned')->default(0);
            $table->integer('total_points_spent')->default(0);
            $table->date('date_of_birth')->nullable();
            $table->timestamps();
        });

        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->text('business_address')->nullable();
            $table->string('merchant_code', 50)->unique();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 100);
            $table->string('name_ar', 100);
            $table->string('slug', 100)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories_prize', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 100);
            $table->string('name_ar', 100);
            $table->string('category_type')->default('gift'); // standard, gift
            $table->integer('points_value')->default(0);
            $table->string('background_color', 7)->default('#C5A059');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name_en');
            $table->string('name_ar');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->string('sku', 100)->unique()->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('serial_code', 16)->unique();
            $table->foreignId('category_id')->nullable()->constrained('categories_prize')->nullOnDelete();
            $table->integer('points_awarded')->default(0);
            $table->string('status')->default('active'); // active, used, expired
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('batch_id', 50)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained('qr_codes')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->nullOnDelete();
            $table->integer('points_awarded_customer');
            $table->integer('points_awarded_merchant')->default(0);
            $table->string('scan_location_lat', 50)->nullable();
            $table->string('scan_location_lng', 50)->nullable();
            $table->timestamp('scanned_at')->useCurrent();
            $table->boolean('is_offline')->default(false);
            $table->string('sync_status')->default('pending'); // pending, synced, failed
            $table->string('device_id')->nullable();
            $table->timestamps();
        });

        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->nullOnDelete();
            $table->foreignId('qr_scan_id')->nullable()->constrained('qr_scans')->nullOnDelete();
            $table->string('type'); // earn, spend, refund, expire, adjust
            $table->integer('amount');
            $table->text('description')->nullable();
            $table->integer('balance_after')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->timestamp('expires_at');
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('cart')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });

        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city', 100);
            $table->string('governorate', 100);
            $table->string('country', 100)->default('Egypt');
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 20);
            $table->string('recipient_name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 50)->unique();
            $table->string('status')->default('new'); // new, processing, shipped, delivered, cancelled, refunded
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method'); // cod, paymob, wallet
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->foreignId('shipping_address_id')->nullable()->constrained('shipping_addresses')->nullOnDelete();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('transaction_id', 100)->unique();
            $table->string('gateway'); // paymob, cod, wallet
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, success, failed, refunded
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wheel_spins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('rank_id')->constrained('ranks')->cascadeOnDelete();
            $table->integer('points_cost');
            $table->integer('points_won')->default(0);
            $table->string('prize_type'); // points, discount, coupon, none
            $table->string('prize_value')->nullable();
            $table->boolean('is_win')->default(false);
            $table->float('probability_used');
            $table->timestamp('spun_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type'); // percentage, fixed
            $table->decimal('value', 10, 2);
            $table->string('scope'); // all, category, product, merchant
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->dateTime('valid_from');
            $table->dateTime('valid_to');
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->decimal('min_order_amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('type'); // rank_upgrade, offer, reminder, order_update, promotion
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value');
            $table->string('group', 50);
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('wheel_spins');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('shipping_addresses');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('cart');
        Schema::dropIfExists('points_transactions');
        Schema::dropIfExists('qr_scans');
        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories_prize');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('merchants');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('ranks');
    }
};
