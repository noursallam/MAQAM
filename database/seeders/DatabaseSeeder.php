<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ranks are seeded via migration (2026_11_02_100000_seed_real_ranks).
        // Everything else (products, merchants, settings, wheel prizes, banners)
        // is created from the admin dashboard. No demo data needed.
    }
}
