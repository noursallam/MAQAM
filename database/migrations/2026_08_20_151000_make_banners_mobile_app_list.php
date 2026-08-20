<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropUnique(['slot']);
        });

        // Keep existing rows as mobile banners and allow many per platform.
        DB::table('banners')->update(['slot' => 'mobile_app']);
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->unique('slot');
        });
    }
};
