<?php

use App\Models\Admin;
use App\Models\Customer;
use App\Models\User;
use App\Models\UserLocation;
use App\Services\Geo\ReverseGeocoder;
use App\Services\RankService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('loyalty:sync-ranks', function () {
    $promoted = 0;

    Customer::query()->chunkById(500, function ($customers) use (&$promoted) {
        foreach ($customers as $customer) {
            $promoted += app(RankService::class)->promoteIfEarned($customer) ? 1 : 0;
        }
    });

    $this->info("Promoted {$promoted} customers.");
})->purpose('Promote customers whose lifetime earned points already qualify for a higher rank');

Artisan::command('admin:create-developer {email} {name=App Developer}', function (string $email, string $name) {
    if (User::where('email', $email)->exists()) {
        $this->error('A user with this email already exists.');

        return 1;
    }

    $password = Str::password(20, symbols: false);

    DB::transaction(function () use ($email, $name, $password) {
        $user = User::create([
            'full_name' => $name,
            'email' => $email,
            // Not a real number, so this account can never be reached through WhatsApp sign-in
            'phone_number' => 'dev-'.Str::lower(Str::random(12)),
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
        ]);

        Admin::create(['user_id' => $user->id, 'role' => 'developer']);
    });

    $this->info('Developer account created. It can open the API docs only.');
    $this->line('Login:    '.url('/admin/login'));
    $this->line('Email:    '.$email);
    $this->line('Password: '.$password.'   (shown once; pass it on securely)');

    return 0;
})->purpose('Create an admin login that can only read the private API documentation');

// Sends queued push notifications on hosts without a permanent queue worker
Schedule::command('queue:work --queue=push --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();

// Names the places of shared locations that could not be looked up right away (map service busy or down)
Artisan::command('locations:geocode {--limit=50}', function (ReverseGeocoder $geocoder) {
    $pending = UserLocation::whereNull('geocoded_at')
        ->where('geocode_attempts', '<', ReverseGeocoder::MAX_ATTEMPTS)
        ->oldest()
        ->limit((int) $this->option('limit'))
        ->get();

    $done = 0;

    foreach ($pending as $location) {
        $attempts = $location->geocode_attempts;
        $ok = $geocoder->geocode($location);

        // Turned away by the one-request-per-second limit rather than failed: wait and go again
        if (! $ok && $location->geocode_attempts === $attempts) {
            usleep(1_100_000);
            $ok = $geocoder->geocode($location);
        }

        $done += $ok ? 1 : 0;
    }

    $this->info("Geocoded {$done} of {$pending->count()} locations.");

    return 0;
})->purpose('Fill in country and governorate for saved visitor locations');

Schedule::command('locations:geocode')->everyTenMinutes()->withoutOverlapping();
