<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insights', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type', 64)->default('performance_summary');
            $table->string('locale', 8)->default('ar');
            $table->longText('content');
            $table->string('source', 32)->default('gemini');
            $table->json('metrics_snapshot')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insights');
    }
};
