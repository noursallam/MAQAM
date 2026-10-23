<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles were a fixed list in code; now the team can add their own. `admins.role` keeps
        // holding the role's key, so existing admins keep exactly the access they had.
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name');
            $table->json('modules');
            // Built-in roles cannot be deleted; the super admin's access cannot be narrowed
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        $builtIn = [
            'super_admin' => ['مدير أعلى', ['dashboard', 'orders', 'merchants', 'customers', 'qr', 'loyalty', 'commerce', 'coupons', 'notifications', 'risk', 'settings', 'admins', 'api_docs']],
            'content_manager' => ['مسؤول محتوى', ['dashboard', 'qr', 'loyalty', 'commerce', 'coupons', 'notifications', 'settings']],
            'support' => ['دعم', ['dashboard', 'orders', 'merchants', 'customers', 'notifications', 'risk']],
            'finance' => ['مالية', ['dashboard', 'orders', 'coupons', 'loyalty', 'settings']],
            'developer' => ['مطوّر التطبيق', ['api_docs']],
        ];

        DB::table('admin_roles')->insert(collect($builtIn)->map(fn ($role, $key) => [
            'key' => $key,
            'name' => $role[0],
            'modules' => json_encode($role[1]),
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
