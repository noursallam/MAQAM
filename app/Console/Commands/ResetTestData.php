<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off clean-up before launch: removes customers, merchants, orders, scans, points and other
 * test activity, and keeps the catalogue and configuration. Dry run unless --execute is given.
 * Delete this file after use.
 */
class ResetTestData extends Command
{
    protected $signature = 'maqam:reset-test-data {--execute : actually delete (default is a dry run that only counts)}';

    protected $description = 'Delete test activity (users, orders, scans, points...) but keep products, QR batches/codes, ranks, prize categories and settings';

    /** Tables emptied completely, children before parents. */
    private const WIPE = [
        'qr_scans', 'points_transactions', 'customer_rewards', 'wheel_spins',
        'payments', 'order_items', 'orders', 'cart_items', 'cart',
        'seminar_registrations', 'seminar_draws',
        'notifications', 'device_tokens', 'device_logins', 'user_locations', 'shipping_addresses',
        'merchant_change_requests', 'merchants', 'customers',
        'personal_access_tokens', 'sessions', 'password_reset_tokens',
        'jobs', 'job_batches', 'failed_jobs', 'insights',
    ];

    /** Never touched. */
    private const KEEP = [
        'products', 'product_images', 'product_colors', 'product_options', 'categories',
        'qr_batches', 'qr_codes', 'ranks', 'categories_prize', 'wheel_prizes',
        'system_settings', 'app_texts', 'banners', 'coupons', 'link_page_items', 'seminars',
        'admins', 'admin_roles',
    ];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        $rows = [];
        foreach (self::WIPE as $table) {
            if (Schema::hasTable($table)) {
                $rows[] = [$table, DB::table($table)->count(), 'delete'];
            }
        }
        $rows[] = ['users (role != admin)', DB::table('users')->where('role', '!=', 'admin')->count(), 'delete'];
        $rows[] = ['qr_codes used', DB::table('qr_codes')->where('status', 'used')->count(), 'reset to active'];
        foreach (self::KEEP as $table) {
            if (Schema::hasTable($table)) {
                $rows[] = [$table, DB::table($table)->count(), 'KEEP'];
            }
        }
        $this->table(['table', 'rows', 'action'], $rows);
        $this->line('Admin users, the WhatsApp/Senderbot session (.env and Senderbot side) and all settings are not touched.');

        if (! $execute) {
            $this->warn('Dry run only. Take a database backup, then run again with --execute.');

            return self::SUCCESS;
        }

        if ($this->ask('This cannot be undone. Type DELETE to continue') !== 'DELETE') {
            $this->error('Cancelled.');

            return self::FAILURE;
        }

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () {
                foreach (self::WIPE as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }
                DB::table('users')->where('role', '!=', 'admin')->delete();

                // Codes used by test scans go back to unused; the codes and batches themselves stay
                $reset = ['status' => 'active', 'used_at' => null, 'used_by_customer_id' => null];
                if (Schema::hasColumn('qr_codes', 'sold_order_id')) {
                    $reset['sold_order_id'] = null;
                }
                DB::table('qr_codes')->where('status', 'used')->update($reset);

                if (Schema::hasColumn('qr_batches', 'claimed_count')) {
                    DB::table('qr_batches')->update(['claimed_count' => 0]);
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->call('cache:clear');
        $this->info('Done.');

        return self::SUCCESS;
    }
}
