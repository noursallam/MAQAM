<?php

/**
 * Dump demo dashboard activity for the last 7 days.
 * Run: php database/scripts/dump_dashboard_demo.php
 */

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PointsTransaction;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$customers = Customer::with('user')->whereHas('user', fn ($q) => $q->where('is_active', true))->get();
if ($customers->isEmpty()) {
    fwrite(STDERR, "No active customers found.\n");
    exit(1);
}

$merchant = Merchant::first();
$userIds = User::whereIn('role', ['customer', 'merchant'])->pluck('id');
if ($userIds->isEmpty()) {
    $userIds = User::pluck('id');
}

$statuses = ['new', 'processing', 'shipped', 'delivered', 'delivered', 'delivered'];
$ordersCreated = 0;
$scansCreated = 0;
$pointsCreated = 0;

$unusedCodes = QrCode::query()
    ->where('status', 'active')
    ->whereNull('used_at')
    ->orderBy('id')
    ->take(80)
    ->get();

$codeIndex = 0;

for ($dayOffset = 6; $dayOffset >= 0; $dayOffset--) {
    $day = Carbon::today()->subDays($dayOffset)->setTime(10, 0);

    // 2–6 orders per day with varying revenue
    $orderCount = 2 + ($dayOffset % 5);
    for ($i = 0; $i < $orderCount; $i++) {
        $status = $statuses[array_rand($statuses)];
        $subtotal = random_int(180, 2400);
        $shipping = 40;
        $total = $subtotal + $shipping;
        $createdAt = (clone $day)->addHours($i)->addMinutes(random_int(0, 40));

        Order::create([
            'user_id' => $userIds->random(),
            'order_number' => 'MQ-DEMO-'.strtoupper(Str::random(8)),
            'status' => $status,
            'subtotal' => $subtotal,
            'tax' => 0,
            'discount' => 0,
            'shipping_cost' => $shipping,
            'total_amount' => $total,
            'payment_method' => 'cod',
            'payment_status' => in_array($status, ['shipped', 'delivered'], true) ? 'paid' : 'pending',
            'shipped_at' => in_array($status, ['shipped', 'delivered'], true) ? $createdAt->copy()->addDay() : null,
            'delivered_at' => $status === 'delivered' ? $createdAt->copy()->addDays(2) : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        $ordersCreated++;
    }

    // 3–10 scans per day
    $scanCount = 3 + ($dayOffset % 8);
    for ($i = 0; $i < $scanCount; $i++) {
        $customer = $customers->random();
        $scannedAt = (clone $day)->addHours(8 + $i)->addMinutes(random_int(0, 50));
        $points = 10;

        $qr = $unusedCodes->get($codeIndex);
        $codeIndex++;

        if (! $qr) {
            // Fallback: create scan without consuming a unique unused code if pool exhausted
            $qr = QrCode::query()->inRandomOrder()->first();
        }

        if (! $qr) {
            continue;
        }

        QrScan::create([
            'qr_code_id' => $qr->id,
            'customer_id' => $customer->id,
            'merchant_id' => $merchant?->id,
            'points_awarded_customer' => $points,
            'points_awarded_merchant' => $merchant ? 5 : 0,
            'scan_location_lat' => 30.04 + (mt_rand(-80, 80) / 1000),
            'scan_location_lng' => 31.23 + (mt_rand(-80, 80) / 1000),
            'scanned_at' => $scannedAt,
            'is_offline' => false,
            'sync_status' => 'synced',
            'device_id' => 'demo-dump',
        ]);

        if ($qr->used_at === null) {
            $qr->update([
                'status' => 'used',
                'used_at' => $scannedAt,
                'used_by_customer_id' => $customer->id,
            ]);
        }

        PointsTransaction::create([
            'customer_id' => $customer->id,
            'type' => 'earn',
            'amount' => $points,
            'balance_after' => (int) $customer->points_balance + $points,
            'description' => 'Demo dump scan points',
            'transaction_date' => $scannedAt,
        ]);

        $customer->increment('points_balance', $points);
        $customer->increment('total_points_earned', $points);

        $scansCreated++;
        $pointsCreated++;
    }
}

echo json_encode([
    'orders_created' => $ordersCreated,
    'scans_created' => $scansCreated,
    'points_created' => $pointsCreated,
    'orders_last_7d' => Order::where('created_at', '>=', now()->subDays(6)->startOfDay())->count(),
    'scans_last_7d' => QrScan::where('scanned_at', '>=', now()->subDays(6)->startOfDay())->count(),
    'revenue_last_7d' => (float) Order::where('created_at', '>=', now()->subDays(6)->startOfDay())
        ->whereIn('status', ['processing', 'shipped', 'delivered'])
        ->sum('total_amount'),
], JSON_PRETTY_PRINT).PHP_EOL;
