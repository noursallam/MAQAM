<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AdminAccess
{
    /** Every part of the panel a role can be given, in the order they are listed. */
    public const MODULES = [
        'dashboard', 'orders', 'merchants', 'customers', 'qr', 'loyalty', 'commerce',
        'coupons', 'notifications', 'risk', 'settings', 'admins', 'api_docs',
    ];

    /** The roles the panel started with, used until the roles table exists. */
    private const DEFAULT_ROLES = [
        'super_admin' => self::MODULES,
        'content_manager' => ['dashboard', 'qr', 'loyalty', 'commerce', 'coupons', 'notifications', 'settings'],
        'support' => ['dashboard', 'orders', 'merchants', 'customers', 'notifications', 'risk'],
        'finance' => ['dashboard', 'orders', 'coupons', 'loyalty', 'settings'],
        // App developers: API documentation only, no business data
        'developer' => ['api_docs'],
    ];

    /**
     * Each role's modules, keyed by role. Read once per request.
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        // Kept on the request, not the process: a role saved now must count on the next request
        $attributes = request()->attributes;
        if (! $attributes->has('admin_roles')) {
            $attributes->set('admin_roles', self::loadRoles());
        }

        return $attributes->get('admin_roles');
    }

    /**
     * @return array<string, list<string>>
     */
    private static function loadRoles(): array
    {
        if (! Schema::hasTable('admin_roles')) {
            return self::DEFAULT_ROLES;
        }

        $roles = AdminRole::orderBy('id')->get()->mapWithKeys(fn (AdminRole $role) => [
            // The super admin always reaches everything, so nobody can lock the team out
            $role->key => $role->isSuperAdmin()
                ? self::MODULES
                : array_values(array_intersect(self::MODULES, $role->modules ?? [])),
        ])->all();

        return $roles ?: self::DEFAULT_ROLES;
    }

    /**
     * What each module is called in the panel.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'dashboard' => __('admin.nav.command_center'),
            'orders' => __('admin.nav.orders_pipeline'),
            'merchants' => __('admin.nav.merchants'),
            'customers' => __('admin.nav.customers'),
            'qr' => __('admin.nav.qr_factory'),
            'loyalty' => __('admin.nav.loyalty'),
            'commerce' => __('admin.nav.commerce'),
            'coupons' => __('admin.nav.coupons'),
            'notifications' => __('admin.nav.communications'),
            'risk' => __('admin.nav.risk_desk'),
            'settings' => __('admin.nav.settings'),
            'admins' => __('admin.nav.admins_rbac'),
            'api_docs' => __('admin.nav.api_docs'),
        ];
    }

    public static function modulesFor(?Admin $admin): array
    {
        if (! $admin) {
            return self::MODULES;
        }

        // A role that was removed or never existed opens nothing but the dashboard
        $base = self::matrix()[$admin->role ?: 'support'] ?? ['dashboard'];

        if (is_array($admin->permissions) && $admin->permissions !== []) {
            return array_values(array_intersect(self::MODULES, array_unique(array_merge($base, $admin->permissions))));
        }

        return $base;
    }

    public static function can(?User $user, string $module): bool
    {
        if (! $user || ! $user->isAdmin()) {
            return false;
        }

        return in_array($module, self::modulesFor($user->admin), true);
    }

    public static function moduleForRoute(?string $routeName): ?string
    {
        if (! $routeName || ! str_starts_with($routeName, 'admin.')) {
            return null;
        }

        // Always allowed within admin panel.
        if (in_array($routeName, ['admin.locale', 'admin.logout'], true)) {
            return null;
        }

        if (in_array($routeName, ['admin.dashboard', 'admin.search'], true)) {
            return 'dashboard';
        }

        $map = [
            'admin.orders.' => 'orders',
            'admin.merchants.' => 'merchants',
            'admin.customers.' => 'customers',
            'admin.audience.' => 'customers',
            'admin.qr-codes.' => 'qr',
            'admin.scans.' => 'qr',
            'admin.prize-categories.' => 'qr',
            'admin.ranks.' => 'loyalty',
            'admin.loyalty.' => 'loyalty',
            'admin.rewards.' => 'loyalty',
            'admin.coupons.' => 'coupons',
            'admin.categories.' => 'commerce',
            'admin.products.' => 'commerce',
            'admin.inventory.' => 'commerce',
            'admin.banners.' => 'commerce',
            'admin.notifications.' => 'notifications',
            'admin.seminars.' => 'notifications',
            'admin.risk.' => 'risk',
            'admin.settings.' => 'settings',
            'admin.whatsapp.' => 'settings',
            'admin.admins.' => 'admins',
            'admin.admin-roles.' => 'admins',
            'admin.api-docs.' => 'api_docs',
        ];

        foreach ($map as $prefix => $module) {
            if (str_starts_with($routeName, $prefix) || $routeName === rtrim($prefix, '.')) {
                return $module;
            }
        }

        return 'dashboard';
    }
}
