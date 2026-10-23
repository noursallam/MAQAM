<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A live event with a prize draw among the customers who sign up while it runs
        Schema::create('seminars', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('seminar_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['seminar_id', 'user_id']);
        });

        // One row per draw, in the order the prizes are drawn. The prize is a name the
        // organiser hands over in person, not a record elsewhere in the system.
        Schema::create('seminar_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('prize');
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();

            $table->unique(['seminar_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_draws');
        Schema::dropIfExists('seminar_registrations');
        Schema::dropIfExists('seminars');
    }
};
