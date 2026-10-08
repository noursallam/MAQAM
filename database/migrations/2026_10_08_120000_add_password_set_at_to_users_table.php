<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Null while the account only has the random password given at WhatsApp sign-up
            $table->timestamp('password_set_at')->nullable()->after('password');
        });

        // Staff, merchants and customers who registered with their own email chose a password.
        // Customers on the generated address may have signed up over WhatsApp; they are asked once.
        DB::table('users')
            ->where(fn ($q) => $q->where('role', '!=', 'customer')
                ->orWhere('email', 'not like', '%@customer.maqam-eg.com'))
            ->update(['password_set_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });
    }
};
