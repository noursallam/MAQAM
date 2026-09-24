<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_options') && ! Schema::hasColumn('product_options', 'price')) {
            Schema::table('product_options', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->nullable()->after('value');
            });
        }

        if (Schema::hasTable('cart_items') && ! Schema::hasColumn('cart_items', 'product_option_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('product_option_id')->nullable()->after('product_id')->constrained('product_options')->nullOnDelete();
                $table->string('option_label')->nullable()->after('product_option_id');
            });
        }

        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'product_option_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('product_option_id')->nullable()->after('product_id')->constrained('product_options')->nullOnDelete();
                $table->string('option_label')->nullable()->after('product_option_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'product_option_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_option_id');
                $table->dropColumn('option_label');
            });
        }

        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'product_option_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_option_id');
                $table->dropColumn('option_label');
            });
        }

        if (Schema::hasTable('product_options') && Schema::hasColumn('product_options', 'price')) {
            Schema::table('product_options', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};
