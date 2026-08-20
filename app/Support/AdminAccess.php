<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\User;

class AdminAccess
{
    /**
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        return [
            'super_admin' => ['dashboard', 'orders', 'merchants', 'customers', 'qr', 'loyalty', 'commerce', 'coupons', 'notifications', 'risk', 'settings', 'admins'],
            'content_manager' => ['dashboard', 'qr', 'loyalty', 'commerce', 'coupons', 'notifications', 'settings'],
            'support' => ['dashboard', 'orders', 'merchants', 'customers', 'notifications', 'risk'],
            'finance' => ['dashboard', 'orders', 'coupons', 'loyalty', 'settings'],
        ];
    }

    public static function modulesFor(?Admin $admin): array
    {
        if (! $admin) {
            return self::matrix()['super_admin'];
        }

        $role = $admin->role ?: 'support';
        $base = self::matrix()[$role] ?? ['dashboard'];

        if (is_array($admin->permissions) && $admin->permissions !== []) {
            return array_values(array_unique(array_merge($base, $admin->permissions)));
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
        if (in_array($routeName, ['admin.locale', 'admin.logout', 'admin.dashboard', 'admin.search'], true)) {
            return 'dashboard';
        }

        $map = [
            'admin.orders.' => 'orders',
            'admin.merchants.' => 'merchants',
            'admin.customers.' => 'customers',
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
            'admin.risk.' => 'risk',
            'admin.settings.' => 'settings',
            'admin.admins.' => 'admins',
        ];

        foreach ($map as $prefix => $module) {
            if (str_starts_with($routeName, $prefix) || $routeName === rtrim($prefix, '.')) {
                return $module;
            }
        }

        return 'dashboard';
    }
}
