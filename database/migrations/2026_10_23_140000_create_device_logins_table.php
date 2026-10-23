<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lets a phone that is locked with fingerprint, Face ID or the app PIN sign back in
        // without the password. The phone keeps the secret; only its hash is stored here.
        Schema::create('device_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_name', 100);
            $table->char('secret_hash', 64);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('device_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_logins');
    }
};
