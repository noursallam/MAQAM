<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\CategoryPrize;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\QrCode;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected Rank $rank;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.senderbot.url' => 'http://senderbot.test',
            'services.senderbot.token' => 'test-token',
        ]);

        $this->rank = Rank::create([
            'name_en' => 'Silver', 'name_ar' => 'فضي', 'min_points' => 0, 'max_points' => 999,
            'customer_points_per_scan' => 10, 'merchant_points_per_scan' => 5,
            'wheel_win_probability' => 0, 'wheel_cost_points' => 50, 'is_active' => true,
        ]);
        Rank::create([
            'name_en' => 'Gold', 'name_ar' => 'ذهبي', 'min_points' => 1000, 'max_points' => null,
            'customer_points_per_scan' => 10, 'merchant_points_per_scan' => 8,
            'wheel_win_probability' => 0, 'wheel_cost_points' => 50, 'is_active' => true,
        ]);
    }

    protected function customerUser(int $points = 0, string $phone = '01012345678'): User
    {
        $user = User::factory()->create(['phone_number' => $phone]);
        Customer::create([
            'user_id' => $user->id, 'rank_id' => $this->rank->id, 'points_balance' => $points,
            'total_points_earned' => $points, 'total_points_spent' => 0,
        ]);

        return $user;
    }

    protected function qrCode(string $serial = '1111222233334444', int $points = 25): QrCode
    {
        $category = CategoryPrize::firstOrCreate(['name_en' => 'Prize A'], [
            'name_ar' => 'جائزة أ', 'category_type' => 'gift', 'points_value' => $points,
            'background_color' => '#22C55E', 'is_active' => true,
        ]);

        return QrCode::create([
            'serial_code' => $serial, 'category_id' => $category->id, 'points_awarded' => $points,
            'status' => 'active', 'generated_at' => now(), 'batch_id' => 'TEST',
        ]);
    }

    protected function merchant(?User $owner = null, bool $approved = true): Merchant
    {
        return Merchant::create([
            'user_id' => ($owner ?? User::factory()->create())->id,
            'business_name' => 'معرض الأمل', 'merchant_code' => 'M-TEST'.random_int(100, 999),
            'is_approved' => $approved,
        ]);
    }

    protected function product(float $price = 100, int $stock = 5): Product
    {
        $category = Category::firstOrCreate(['slug' => 'switches'], ['name_en' => 'Switches', 'name_ar' => 'مفاتيح', 'is_active' => true]);

        return Product::create([
            'category_id' => $category->id, 'name_en' => 'Switch', 'name_ar' => 'مفتاح',
            'price' => $price, 'stock_quantity' => $stock, 'sku' => 'SKU-'.random_int(1000, 9999), 'is_active' => true,
        ]);
    }

    // ---------------------------------------------------------------- auth

    public function test_whatsapp_sign_in_creates_account_and_returns_token(): void
    {
        $incoming = [];
        Http::fake([
            'senderbot.test/sessions/status/*' => Http::response(['success' => true, 'data' => [
                'status' => 'authenticated', 'valid_session' => true, 'user_info' => ['id' => '201000000000@s.whatsapp.net'],
            ]]),
            'senderbot.test/chats/send*' => Http::response(['success' => true, 'data' => []]),
            'senderbot.test/chats?*' => Http::response(['success' => true, 'data' => []]),
            'senderbot.test/chats/*' => function () use (&$incoming) {
                return Http::response(['success' => true, 'data' => array_map(
                    fn ($text) => ['key' => ['fromMe' => false], 'message' => ['conversation' => $text]], $incoming
                )]);
            },
        ]);

        $start = $this->postJson('/api/v1/auth/whatsapp/start', ['phone' => '+20 101 234 5678', 'full_name' => 'أحمد'])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.whatsapp_number', '201000000000')
            ->json('data');

        $this->postJson('/api/v1/auth/whatsapp/check', ['challenge_id' => $start['challenge_id']])
            ->assertOk()->assertJsonPath('data.otp_sent', false);

        // OTP cannot be redeemed before the customer has messaged us
        $this->postJson('/api/v1/auth/whatsapp/verify', ['challenge_id' => $start['challenge_id'], 'otp' => '123456', 'device_name' => 'Pixel'])
            ->assertStatus(422)->assertJsonPath('error_code', 'INVALID_OTP');

        $incoming[] = $start['code'];
        $this->postJson('/api/v1/auth/whatsapp/check', ['challenge_id' => $start['challenge_id']])
            ->assertOk()->assertJsonPath('data.otp_sent', true);

        $otp = null;
        Http::assertSent(function (Request $r) use (&$otp) {
            if (str_contains($r->url(), '/chats/send') && preg_match('/\b(\d{6})\b/', $r['message']['text'], $m)) {
                $otp = $m[1];
            }

            return true;
        });

        $login = $this->postJson('/api/v1/auth/whatsapp/verify', ['challenge_id' => $start['challenge_id'], 'otp' => $otp, 'device_name' => 'Pixel'])
            ->assertOk()
            ->assertJsonPath('data.is_new_user', true)
            ->assertJsonPath('data.user.phone_number', '01012345678')
            ->assertJsonPath('data.user.full_name', 'أحمد')
            ->assertJsonPath('data.customer.points_balance', 0)
            ->assertJsonMissingPath('data.user.password')
            ->json('data');

        $this->assertDatabaseHas('customers', ['user_id' => $login['user']['id']]);

        // The OTP is single-use
        $this->postJson('/api/v1/auth/whatsapp/verify', ['challenge_id' => $start['challenge_id'], 'otp' => $otp, 'device_name' => 'Pixel'])
            ->assertStatus(410)->assertJsonPath('error_code', 'CHALLENGE_EXPIRED');

        $this->withToken($login['token'])->getJson('/api/v1/user/profile')
            ->assertOk()->assertJsonPath('data.user.phone_number', '01012345678');
    }

    public function test_protected_routes_reject_guests_and_frozen_accounts(): void
    {
        $this->getJson('/api/v1/wallet')->assertStatus(401)
            ->assertJson(['success' => false, 'error_code' => 'UNAUTHENTICATED']);

        $user = $this->customerUser();
        $user->update(['is_active' => false]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/wallet')->assertStatus(403)->assertJsonPath('error_code', 'ACCOUNT_FROZEN_FRAUD');
    }

    public function test_invalid_phone_is_rejected_with_validation_envelope(): void
    {
        $this->postJson('/api/v1/auth/whatsapp/start', ['phone' => '12345'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['success', 'message', 'errors' => ['phone'], 'error_code']);
    }

    public function test_profile_update_cannot_change_role_or_balance(): void
    {
        $user = $this->customerUser(100);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/user/profile', ['full_name' => 'New Name', 'role' => 'admin', 'points_balance' => 99999, 'is_active' => false])
            ->assertOk()->assertJsonPath('data.user.full_name', 'New Name');

        $this->assertSame('customer', $user->fresh()->role);
        $this->assertSame(100, (int) $user->customer->fresh()->points_balance);
    }

    // ---------------------------------------------------------------- scanning

    public function test_scan_awards_points_once_and_credits_merchant(): void
    {
        $user = $this->customerUser();
        $merchant = $this->merchant();
        $this->qrCode();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/qr/preview', ['serial_code' => '1111222233334444', 'merchant_code' => $merchant->merchant_code])
            ->assertOk()->assertJsonPath('data.points_customer', 25)->assertJsonPath('data.points_merchant', 5);
        $this->assertSame(0, (int) $user->customer->fresh()->points_balance);

        $this->postJson('/api/v1/qr/scan', [
            'serial_code' => '1111222233334444', 'merchant_code' => $merchant->merchant_code,
            'latitude' => 30.0444, 'longitude' => 31.2357, 'device_id' => 'DEV-1',
        ])->assertOk()->assertJsonPath('data.points_awarded', 25)->assertJsonPath('data.new_balance', 25)
            ->assertJsonPath('data.merchant_points', 5);

        $this->assertDatabaseHas('qr_scans', ['merchant_id' => $merchant->id, 'device_id' => 'DEV-1', 'sync_status' => 'synced']);

        $this->postJson('/api/v1/qr/scan', ['serial_code' => '1111222233334444'])
            ->assertStatus(422)->assertJsonPath('error_code', 'QR_ALREADY_USED');
        $this->assertSame(25, (int) $user->customer->fresh()->points_balance);

        $this->getJson('/api/v1/merchants/my-recent')->assertOk()
            ->assertJsonPath('data.0.merchant_code', $merchant->merchant_code);
    }

    public function test_scan_rejects_unknown_unapproved_and_own_merchant_codes(): void
    {
        $user = $this->customerUser();
        $this->qrCode();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/qr/scan', ['serial_code' => '1111222233334444', 'merchant_code' => 'M-NOPE'])
            ->assertStatus(422)->assertJsonPath('error_code', 'MERCHANT_NOT_FOUND');

        $pending = $this->merchant(null, false);
        $this->postJson('/api/v1/qr/scan', ['serial_code' => '1111222233334444', 'merchant_code' => $pending->merchant_code])
            ->assertStatus(422)->assertJsonPath('error_code', 'MERCHANT_NOT_FOUND');

        $own = $this->merchant($user);
        $this->postJson('/api/v1/qr/scan', ['serial_code' => '1111222233334444', 'merchant_code' => $own->merchant_code])
            ->assertStatus(422)->assertJsonPath('error_code', 'MERCHANT_SELF_SCAN');

        $this->assertDatabaseHas('qr_codes', ['serial_code' => '1111222233334444', 'status' => 'active']);
    }

    public function test_guessing_codes_locks_scanning(): void
    {
        Sanctum::actingAs($this->customerUser());

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/qr/scan', ['serial_code' => '99990000'.$i])
                ->assertStatus(404)->assertJsonPath('error_code', 'QR_NOT_FOUND');
        }

        $this->qrCode();
        $this->postJson('/api/v1/qr/scan', ['serial_code' => '1111222233334444'])
            ->assertStatus(429)->assertJsonPath('error_code', 'SCAN_LOCKED');
    }

    public function test_impossible_travel_freezes_account_and_revokes_tokens(): void
    {
        $user = $this->customerUser();
        $this->qrCode('1111222233334444');
        $this->qrCode('5555666677778888');
        $token = $user->createToken('Pixel')->plainTextToken;

        // Cairo, then Aswan seconds later
        $this->withToken($token)->postJson('/api/v1/qr/scan', ['serial_code' => '1111222233334444', 'latitude' => 30.0444, 'longitude' => 31.2357])
            ->assertOk()->assertJsonPath('data.account_frozen', false);
        $this->withToken($token)->postJson('/api/v1/qr/scan', ['serial_code' => '5555666677778888', 'latitude' => 24.0889, 'longitude' => 32.8998])
            ->assertOk()->assertJsonPath('data.account_frozen', true);

        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_offline_batch_sync_reports_each_scan(): void
    {
        $user = $this->customerUser();
        $this->qrCode('1111222233334444');
        $other = $this->customerUser(0, '01112345678');
        $taken = $this->qrCode('5555666677778888');
        $taken->update(['status' => 'used', 'used_by_customer_id' => $other->customer->id]);
        Sanctum::actingAs($user);

        $payload = ['device_id' => 'DEV-1', 'scans' => [
            ['serial_code' => '1111222233334444', 'scanned_at' => now()->subHour()->toDateTimeString()],
            ['serial_code' => '5555666677778888', 'scanned_at' => now()->subHour()->toDateTimeString()],
            ['serial_code' => '0000000000000000', 'scanned_at' => now()->addYear()->toDateTimeString()],
        ]];

        $this->postJson('/api/v1/qr/sync-batch', $payload)->assertOk()
            ->assertJsonPath('data.total_synced', 1)
            ->assertJsonPath('data.total_points_gained', 25)
            ->assertJsonPath('data.current_balance', 25)
            ->assertJsonPath('data.results.0.status', 'success')
            ->assertJsonPath('data.results.1.error_code', 'QR_ALREADY_USED')
            ->assertJsonPath('data.results.2.error_code', 'QR_NOT_FOUND');

        // Retrying the same batch never double-credits
        $this->postJson('/api/v1/qr/sync-batch', $payload)->assertOk()
            ->assertJsonPath('data.total_synced', 0)
            ->assertJsonPath('data.results.0.status', 'already_synced')
            ->assertJsonPath('data.current_balance', 25);
    }

    // ---------------------------------------------------------------- loyalty

    public function test_wallet_shows_progress_and_transactions(): void
    {
        Sanctum::actingAs($this->customerUser(400));

        $this->getJson('/api/v1/wallet')->assertOk()
            ->assertJsonPath('data.points_balance', 400)
            ->assertJsonPath('data.rank.name_en', 'Silver')
            ->assertJsonPath('data.next_rank.name_en', 'Gold')
            ->assertJsonPath('data.points_left_for_next_rank', 600)
            ->assertJsonPath('data.progress_percent', 40);

        $this->getJson('/api/v1/wallet/transactions')->assertOk()->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);
    }

    public function test_wheel_spin_charges_cost_and_refuses_without_points(): void
    {
        $user = $this->customerUser(60);
        Sanctum::actingAs($user);

        $config = $this->getJson('/api/v1/wheel/config')->assertOk()
            ->assertJsonPath('data.can_spin', true)->assertJsonPath('data.cost_points', 50)->json('data');
        $this->assertSame('none', end($config['slices'])['type']);

        // Win probability is 0 in this test, so the spin is a paid loss on the last slice
        $this->postJson('/api/v1/wheel/spin')->assertOk()
            ->assertJsonPath('data.spin.is_win', false)
            ->assertJsonPath('data.new_points_balance', 10)
            ->assertJsonPath('data.slice_index', count($config['slices']) - 1);

        $this->postJson('/api/v1/wheel/spin')->assertStatus(422)->assertJsonPath('error_code', 'INSUFFICIENT_POINTS');
        $this->assertSame(10, (int) $user->customer->fresh()->points_balance);
    }

    // ---------------------------------------------------------------- store

    public function test_catalog_is_public_and_hides_inactive_products(): void
    {
        $visible = $this->product();
        $hidden = $this->product();
        $hidden->update(['is_active' => false]);

        $this->getJson('/api/v1/store/products')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $visible->id);
        $this->getJson('/api/v1/store/products/'.$visible->id)->assertOk()->assertJsonStructure(['data' => ['images', 'colors', 'options']]);
        $this->getJson('/api/v1/store/products/'.$hidden->id)->assertStatus(404)->assertJsonPath('error_code', 'NOT_FOUND');
        $this->getJson('/api/v1/store/products?search='.urlencode('%'))->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_cart_and_cod_checkout_create_order_and_reduce_stock(): void
    {
        $user = $this->customerUser();
        $product = $this->product(100, 5);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()->assertJsonPath('data.items_count', 2)->assertJsonPath('data.subtotal', '200.00');

        $response = $this->postJson('/api/v1/store/checkout', [
            'full_name' => 'أحمد', 'phone' => '01012345678', 'governorate' => 'القاهرة', 'city' => 'مدينة نصر',
            'address' => 'شارع الطيران', 'payment_method' => 'cod',
        ])->assertCreated()->assertJsonPath('data.order.payment_method', 'cod')->assertJsonPath('data.payment_url', null);

        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
        $this->getJson('/api/v1/store/cart')->assertOk()->assertJsonPath('data.items_count', 0);

        $number = $response->json('data.order.order_number');
        $this->getJson('/api/v1/orders/'.$number)->assertOk()->assertJsonPath('data.items.0.quantity', 2);
        $this->getJson('/api/v1/orders')->assertOk()->assertJsonPath('meta.total', 1);

        // Another customer can never read this order
        Sanctum::actingAs($this->customerUser(0, '01112345678'));
        $this->getJson('/api/v1/orders/'.$number)->assertStatus(404);
    }

    public function test_checkout_refuses_more_than_stock_and_empty_cart(): void
    {
        $user = $this->customerUser();
        $product = $this->product(100, 5);
        Sanctum::actingAs($user);

        $shipping = ['full_name' => 'أحمد', 'phone' => '01012345678', 'governorate' => 'القاهرة', 'city' => 'مدينة نصر', 'address' => 'شارع', 'payment_method' => 'cod'];

        $this->postJson('/api/v1/store/checkout', $shipping)->assertStatus(422)->assertJsonPath('error_code', 'CART_EMPTY');

        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $product->update(['stock_quantity' => 1]);

        $this->postJson('/api/v1/store/checkout', $shipping)->assertStatus(422)->assertJsonPath('error_code', 'OUT_OF_STOCK');
        $this->assertSame(0, Order::count());
        $this->assertSame(1, (int) $product->fresh()->stock_quantity);
    }

    public function test_wallet_checkout_needs_enough_points_and_deducts_them(): void
    {
        $user = $this->customerUser(1000);
        $product = $this->product(50, 5);
        Sanctum::actingAs($user);

        $shipping = ['full_name' => 'أحمد', 'phone' => '01012345678', 'governorate' => 'القاهرة', 'city' => 'مدينة نصر', 'address' => 'شارع', 'payment_method' => 'wallet'];
        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        // 100 EGP + shipping needs more than 1000 points
        $this->postJson('/api/v1/store/checkout', $shipping)->assertStatus(422)->assertJsonPath('error_code', 'INSUFFICIENT_POINTS');
        $this->assertSame(0, Order::count());
        $this->assertSame(5, (int) $product->fresh()->stock_quantity);

        $user->customer->update(['points_balance' => 5000]);
        $total = (float) $this->getJson('/api/v1/store/cart')->json('data.total');

        $this->postJson('/api/v1/store/checkout', $shipping)->assertCreated()
            ->assertJsonPath('data.order.payment_status', 'paid')->assertJsonPath('data.order.status', 'processing');
        $this->assertSame(5000 - (int) ceil($total * 10), (int) $user->customer->fresh()->points_balance);
    }

    public function test_cart_items_of_other_users_are_not_reachable(): void
    {
        $owner = $this->customerUser();
        $product = $this->product();
        Sanctum::actingAs($owner);
        $itemId = $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id])->json('data.items.0.id');

        Sanctum::actingAs($this->customerUser(0, '01112345678'));
        $this->postJson('/api/v1/store/cart/update', ['item_id' => $itemId, 'quantity' => 9])->assertStatus(404);
        $this->deleteJson('/api/v1/store/cart/remove/'.$itemId)->assertStatus(404);

        $this->assertDatabaseHas('cart_items', ['id' => $itemId, 'quantity' => 1]);
    }

    public function test_merchant_application_is_pending_until_approved(): void
    {
        Sanctum::actingAs($this->customerUser());

        $this->postJson('/api/v1/merchant/apply', ['business_name' => 'معرض النور'])
            ->assertCreated()->assertJsonPath('data.is_approved', false);
        $this->postJson('/api/v1/merchant/apply', ['business_name' => 'مرة ثانية'])
            ->assertStatus(409)->assertJsonPath('error_code', 'MERCHANT_ALREADY_EXISTS');
        $this->getJson('/api/v1/merchant')->assertOk()->assertJsonPath('data.stats.total_scans', 0);
    }
}
