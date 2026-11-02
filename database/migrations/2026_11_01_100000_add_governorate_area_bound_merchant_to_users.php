<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('governorate', 100)->nullable()->after('preferred_language');
            $table->string('area', 255)->nullable()->after('governorate');
            $table->foreignId('bound_merchant_id')->nullable()->after('area')
                ->constrained('merchants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['bound_merchant_id']);
            $table->dropColumn(['governorate', 'area', 'bound_merchant_id']);
        });
    }
};
