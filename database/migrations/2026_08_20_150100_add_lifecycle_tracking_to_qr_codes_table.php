<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->timestamp('printed_at')->nullable()->after('generated_at');
            $table->timestamp('sold_at')->nullable()->after('printed_at');
            $table->foreignId('sold_order_id')->nullable()->after('sold_at')->constrained('orders')->nullOnDelete();
            $table->index(['status', 'printed_at', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sold_order_id');
            $table->dropColumn(['printed_at', 'sold_at']);
        });
    }
};
