<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 50)->unique();
            $table->foreignId('category_id')->constrained('categories_prize')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('processed_count')->default(0);
            $table->string('status', 20)->default('queued')->index();
            $table->string('notes', 500)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('zip_ready_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_batches');
    }
};
