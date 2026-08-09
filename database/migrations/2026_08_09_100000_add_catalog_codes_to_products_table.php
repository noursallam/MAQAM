<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('production_code', 50)->nullable()->unique()->after('sku');
            $table->unsignedInteger('system_code')->nullable()->index()->after('production_code');
            $table->string('catalog_code', 100)->nullable()->after('system_code');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['production_code', 'system_code', 'catalog_code']);
        });
    }
};
