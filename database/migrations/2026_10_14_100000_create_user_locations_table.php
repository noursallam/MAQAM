<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every position a signed-in visitor shared from the browser; place names are filled in after reverse geocoding
        Schema::create('user_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('accuracy_m')->nullable();
            $table->string('source', 20)->default('web');
            $table->char('country_code', 2)->nullable()->index();
            $table->string('country_name', 100)->nullable();
            // ISO 3166-2 code such as EG-C, so the governorate can be shown in either language
            $table->string('region_code', 10)->nullable()->index();
            $table->string('region_name', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->timestamp('geocoded_at')->nullable();
            $table->unsignedTinyInteger('geocode_attempts')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Browser answers: granted, denied, prompt (not decided yet) or unsupported
            $table->string('location_permission', 12)->nullable();
            $table->string('notification_permission', 12)->nullable();
            $table->timestamp('permissions_reported_at')->nullable();
            $table->foreignId('latest_location_id')->nullable()->constrained('user_locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('latest_location_id');
            $table->dropColumn(['location_permission', 'notification_permission', 'permissions_reported_at']);
        });

        Schema::dropIfExists('user_locations');
    }
};
