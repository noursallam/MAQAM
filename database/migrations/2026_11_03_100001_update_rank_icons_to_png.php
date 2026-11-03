<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ranks')
            ->where('icon_url', 'like', '%.svg')
            ->update(['icon_url' => DB::raw("REPLACE(icon_url, '.svg', '.png')")]);
    }

    public function down(): void
    {
        DB::table('ranks')
            ->where('icon_url', 'like', '%.png')
            ->update(['icon_url' => DB::raw("REPLACE(icon_url, '.png', '.svg')")]);
    }
};
