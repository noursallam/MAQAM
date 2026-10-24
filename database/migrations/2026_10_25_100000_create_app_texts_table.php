<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Overrides only: the app ships its own wording and uses a row here when one is filled in
        Schema::create('app_texts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value_ar')->nullable();
            $table->text('value_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_texts');
    }
};
