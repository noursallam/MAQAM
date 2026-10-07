<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\DeviceToken;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Rank;
use App\Models\ShippingAddress;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppCompletenessTest extends TestCase
{
    use RefreshDatabase;

    protected Rank $rank;

    protected array $shipping = [
        'full_name' => 'أحمد', 'phone' => '01012345678', 'governorate' => 'القاهرة',
        'city' => 'مدينة نصر', 'address' => 'شارع الطيران',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.senderbot.url' => 'http://senderbot.test',
            'services.senderbot.token' => 'test-token',
            'services.firebase.credentials' => '',
            'kashier.payment_api_key' => 'test-payment-key',
        ]);

        $this->rank = Rank::create([
            'name_en' => 'Silver', 'name_ar' => 'فضي', 'min_points' => 0, 'max_points' => null,
            'customer_points_per_scan' => 10, 'merchant_points_per_scan' => 5,
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

    protected function product(float $price = 100, int $stock = 5): Product
    {
        $category = Category::firstOrCreate(['slug' => 'switches'], ['name_en' => 'Switches', 'name_ar' => 'مفاتيح', 'is_active' => true]);

        return Product::create([
            'category_id' => $category->id, 'name_en' => 'Switch', 'name_ar' => 'مفتاح',
            'price' => $price, 'stock_quantity' => $stock, 'sku' => 'SKU-'.random_int(10000, 99999), 'is_active' => true,
        ]);
    }

    protected function kashierOrder(User $user, float $total = 150): Order
    {
        $order = Order::create([
            'user_id' => $user->id, 'order_number' => 'MQ-TEST-'.random_int(1000, 9999), 'status' => 'new',
            'subtotal' => $total, 'total_amount' => $total, 'payment_method' => 'kashier', 'payment_status' => 'pending',
        ]);
        Payment::create(['order_id' => $order->id, 'transaction_id' => 'sess-'.$order->id, 'gateway' => 'kashier', 'amount' => $total, 'status' => 'pending']);

        return $order;
    }

    protected function signedCallbackQuery(Order $order, string $status = 'SUCCESS', ?string $amount = null): array
    {
        $params = [
            'paymentStatus' => $status, 'merchantOrderId' => $order->order_number, 'orderId' => 'K-1',
            'transactionId' => 'TX-99', 'amount' => $amount ?? (string) $order->total_amount, 'currency' => 'EGP',
        ];
        $fields = ['paymentStatus', 'cardDataToken', 'maskedCard', 'merchantOrderId', 'orderId', 'cardBrand', 'orderReference', 'transactionId', 'amount', 'currency'];
        $payload = implode('&', array_map(fn ($f) => $f.'='.($params[$f] ?? 'null'), $fields));

        return $params + ['signature' => hash_hmac('sha256', $payload, 'test-payment-key')];
    }

    // ------------------------------------------------------------ payment security

    public function test_unsigned_payment_callback_cannot_mark_an_order_paid(): void
    {
        $order = $this->kashierOrder($this->customerUser());

        $this->get(route('payment.kashier.callback', ['merchantOrderId' => $order->order_number, 'paymentStatus' => 'SUCCESS', 'amount' => '150.00']))
            ->assertRedirect(route('store.order.confirmation', ['orderNumber' => $order->order_number]));
        $this->get(route('payment.kashier.callback', ['merchantOrderId' => $order->order_number, 'paymentStatus' => 'SUCCESS', 'signature' => 'forged']));

        $this->assertSame('pending', $order->fresh()->payment_status);

        // Nor can a forged failure sabotage someone else's order
        $this->get(route('payment.kashier.callback', ['merchantOrderId' => $order->order_number, 'paymentStatus' => 'FAILED']));
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_signed_callback_pays_once_and_never_awards_points_twice(): void
    {
        $user = $this->customerUser();
        $order = $this->kashierOrder($user, 150);
        $query = $this->signedCallbackQuery($order);

        $this->get(route('payment.kashier.callback', $query))->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(15, (int) $user->customer->fresh()->points_balance);

        // Reloading the success URL must not pay out again
        $this->get(route('payment.kashier.callback', $query))->assertRedirect();
        $this->assertSame(15, (int) $user->customer->fresh()->points_balance);
    }

    public function test_signed_callback_with_wrong_amount_is_not_accepted(): void
    {
        $order = $this->kashierOrder($this->customerUser(), 150);

        $this->get(route('payment.kashier.callback', $this->signedCallbackQuery($order, 'SUCCESS', '1.00')));

        $this->assertNotSame('paid', $order->fresh()->payment_status);
    }

    public function test_webhook_without_signature_is_rejected(): void
    {
        $order = $this->kashierOrder($this->customerUser());

        $this->postJson(route('payment.kashier.webhook'), ['event' => 'pay', 'data' => [
            'merchantOrderId' => $order->order_number, 'status' => 'SUCCESS', 'amount' => 150,
        ]])->assertStatus(400);

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    // ------------------------------------------------------------ account

    public function test_account_deletion_erases_personal_data_and_frees_the_phone(): void
    {
        $user = $this->customerUser(40);
        $token = $user->createToken('Pixel')->plainTextToken;
        DeviceToken::register($user, 'fcm-1');
        ShippingAddress::create(['user_id' => $user->id, 'address_line1' => 'x', 'city' => 'c', 'governorate' => 'g', 'phone' => '01012345678', 'recipient_name' => 'أحمد']);
        AppNotification::create(['user_id' => $user->id, 'title' => 't', 'body' => 'b', 'type' => 'offer']);
        $order = $this->kashierOrder($user);

        $this->withToken($token)->deleteJson('/api/v1/user/account', ['confirm' => 'nope'])->assertStatus(422);
        $this->withToken($token)->deleteJson('/api/v1/user/account', ['confirm' => 'DELETE'])->assertOk();

        $user->refresh();
        $this->assertSame('Deleted user', $user->full_name);
        $this->assertStringStartsWith('deleted-', $user->phone_number);
        $this->assertFalse($user->is_active);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(0, DeviceToken::count());
        $this->assertSame(0, AppNotification::count());
        $this->assertSame(0, ShippingAddress::count());
        $this->assertFalse(User::where('phone_number', '01012345678')->exists());

        // Business records survive, detached from the person
        $this->assertNotNull($order->fresh());

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/user/profile')->assertStatus(401);
    }

    public function test_phone_change_needs_proof_from_the_new_number(): void
    {
        $incoming = [];
        Http::fake([
            'senderbot.test/sessions/status/*' => Http::response(['success' => true, 'data' => ['status' => 'authenticated', 'valid_session' => true, 'user_info' => ['id' => '201000000000@s.whatsapp.net']]]),
            'senderbot.test/chats/send*' => Http::response(['success' => true, 'data' => []]),
            'senderbot.test/chats/*' => function () use (&$incoming) {
                return Http::response(['success' => true, 'data' => array_map(fn ($t) => ['key' => ['fromMe' => false], 'message' => ['conversation' => $t]], $incoming)]);
            },
        ]);

        $user = $this->customerUser();
        $this->customerUser(0, '01112345678');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/user/phone/start', ['phone' => '01112345678'])->assertStatus(422)->assertJsonPath('error_code', 'PHONE_TAKEN');

        $start = $this->postJson('/api/v1/user/phone/start', ['phone' => '01212345678'])->assertOk()->json('data');

        // A phone-change challenge can never be used to sign in
        $this->postJson('/api/v1/auth/whatsapp/verify', ['challenge_id' => $start['challenge_id'], 'otp' => '123456', 'device_name' => 'x'])
            ->assertStatus(410);

        $incoming[] = $start['code'];
        $this->postJson('/api/v1/auth/whatsapp/check', ['challenge_id' => $start['challenge_id']])->assertJsonPath('data.otp_sent', true);

        $otp = null;
        foreach (Http::recorded() as [$request]) {
            if (str_contains($request->url(), '/chats/send') && preg_match('/\b(\d{6})\b/', $request['message']['text'], $m)) {
                $otp = $m[1];
            }
        }

        $this->postJson('/api/v1/user/phone/verify', ['challenge_id' => $start['challenge_id'], 'otp' => $otp])
            ->assertOk()->assertJsonPath('data.phone_number', '01212345678');
        $this->assertSame('01212345678', $user->fresh()->phone_number);
    }

    // ------------------------------------------------------------ rewards

    public function test_wheel_discount_reward_is_used_once_at_checkout(): void
    {
        $user = $this->customerUser();
        $product = $this->product(200, 5);
        $reward = CustomerReward::create([
            'customer_id' => $user->customer->id, 'type' => 'discount', 'status' => 'available', 'source' => 'wheel',
            'code' => 'WD-TEST0001', 'amount_type' => 'percentage', 'amount_value' => 10, 'expires_at' => now()->addDay(),
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id])->assertOk();
        $this->postJson('/api/v1/store/cart/coupon', ['code' => 'WD-TEST0001'])->assertOk()
            ->assertJsonPath('data.discount', '20.00')->assertJsonPath('data.coupon_code', 'WD-TEST0001');

        $number = $this->postJson('/api/v1/store/checkout', $this->shipping + ['payment_method' => 'cod'])
            ->assertCreated()->assertJsonPath('data.order.discount', '20.00')->json('data.order.order_number');

        $this->assertSame('used', $reward->fresh()->status);

        // Spent rewards cannot be applied again
        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id])->assertOk();
        $this->postJson('/api/v1/store/cart/coupon', ['code' => 'WD-TEST0001'])->assertStatus(422)->assertJsonPath('error_code', 'INVALID_COUPON');

        // Cancelling the order gives the reward back
        $this->postJson("/api/v1/orders/{$number}/cancel")->assertOk();
        $this->assertSame('available', $reward->fresh()->status);
    }

    public function test_reward_code_of_another_customer_is_rejected(): void
    {
        $owner = $this->customerUser(0, '01112345678');
        CustomerReward::create([
            'customer_id' => $owner->customer->id, 'type' => 'discount', 'status' => 'available', 'source' => 'wheel',
            'code' => 'WD-OTHER001', 'amount_type' => 'fixed', 'amount_value' => 50,
        ]);
        $product = $this->product();
        Sanctum::actingAs($this->customerUser());

        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id])->assertOk();
        $this->postJson('/api/v1/store/cart/coupon', ['code' => 'WD-OTHER001'])->assertStatus(422);
    }

    public function test_gift_product_reward_ships_free_with_the_order(): void
    {
        $user = $this->customerUser();
        $product = $this->product(100, 5);
        $gift = $this->product(999, 3);
        CustomerReward::create([
            'customer_id' => $user->customer->id, 'type' => 'product', 'status' => 'available', 'source' => 'wheel',
            'code' => 'GP-TEST0001', 'product_id' => $gift->id,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id])->assertOk();
        $this->postJson('/api/v1/store/cart/coupon', ['code' => 'GP-TEST0001'])->assertOk()->assertJsonPath('data.subtotal', '100.00');

        $order = $this->postJson('/api/v1/store/checkout', $this->shipping + ['payment_method' => 'cod'])->assertCreated()->json('data.order');

        $this->assertCount(2, $order['items']);
        $this->assertSame('100.00', $order['subtotal']);
        $this->assertSame('0.00', collect($order['items'])->firstWhere('product.id', $gift->id)['unit_price']);
        // The gift's stock was reserved when it was won, not at checkout
        $this->assertSame(3, (int) $gift->fresh()->stock_quantity);
    }

    public function test_percentage_coupon_respects_its_maximum_discount(): void
    {
        Coupon::create([
            'code' => 'HALF', 'type' => 'percentage', 'value' => 50, 'scope' => 'all', 'max_discount_amount' => 30,
            'valid_from' => now()->subDay(), 'valid_to' => now()->addDay(), 'is_active' => true,
        ]);
        $product = $this->product(200);
        Sanctum::actingAs($this->customerUser());

        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id])->assertOk();
        $this->postJson('/api/v1/store/cart/coupon', ['code' => 'HALF'])->assertOk()->assertJsonPath('data.discount', '30.00');
    }

    // ------------------------------------------------------------ orders and addresses

    public function test_customer_can_cancel_and_gets_stock_and_points_back(): void
    {
        $user = $this->customerUser(5000);
        $product = $this->product(50, 5);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $order = $this->postJson('/api/v1/store/checkout', $this->shipping + ['payment_method' => 'wallet'])->assertCreated()->json('data.order');

        $this->assertTrue($order['can_cancel']);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
        $this->assertLessThan(5000, (int) $user->customer->fresh()->points_balance);

        $this->postJson("/api/v1/orders/{$order['order_number']}/cancel", ['reason' => 'غيرت رأيي'])->assertOk()
            ->assertJsonPath('data.status', 'cancelled')->assertJsonPath('data.payment_status', 'refunded')->assertJsonPath('data.can_cancel', false);

        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
        $this->assertSame(5000, (int) $user->customer->fresh()->points_balance);
        $this->assertDatabaseHas('points_transactions', ['customer_id' => $user->customer->id, 'type' => 'refund']);

        // Cancelling twice changes nothing
        $this->postJson("/api/v1/orders/{$order['order_number']}/cancel")->assertStatus(422)->assertJsonPath('error_code', 'ORDER_NOT_CANCELLABLE');
        $this->assertSame(5000, (int) $user->customer->fresh()->points_balance);
    }

    public function test_shipped_card_paid_and_foreign_orders_cannot_be_cancelled(): void
    {
        $user = $this->customerUser();
        Sanctum::actingAs($user);

        $shipped = $this->kashierOrder($user);
        $shipped->update(['status' => 'shipped']);
        $this->postJson("/api/v1/orders/{$shipped->order_number}/cancel")->assertStatus(422);

        $paid = $this->kashierOrder($user);
        $paid->update(['payment_status' => 'paid', 'status' => 'processing']);
        $this->postJson("/api/v1/orders/{$paid->order_number}/cancel")->assertStatus(422);

        $foreign = $this->kashierOrder($this->customerUser(0, '01112345678'));
        $this->postJson("/api/v1/orders/{$foreign->order_number}/cancel")->assertStatus(404);
        $this->assertSame('new', $foreign->fresh()->status);
    }

    public function test_address_book_crud_and_checkout_with_a_saved_address(): void
    {
        $user = $this->customerUser();
        $product = $this->product();
        Sanctum::actingAs($user);

        $body = ['recipient_name' => 'أحمد', 'phone' => '01012345678', 'governorate' => 'القاهرة', 'city' => 'مدينة نصر', 'address' => 'شارع 1'];
        $first = $this->postJson('/api/v1/addresses', $body)->assertCreated()->assertJsonPath('data.is_default', true)->json('data.id');
        $second = $this->postJson('/api/v1/addresses', ['address' => 'شارع 2', 'is_default' => true] + $body)->assertCreated()->json('data.id');

        $this->getJson('/api/v1/addresses')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $second);
        $this->putJson("/api/v1/addresses/{$first}", ['city' => 'المعادي'] + $body)->assertOk()->assertJsonPath('data.city', 'المعادي');

        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id])->assertOk();
        $number = $this->postJson('/api/v1/store/checkout', ['address_id' => $first, 'payment_method' => 'cod'])
            ->assertCreated()->assertJsonPath('data.order.shipping_address.city', 'المعادي')->json('data.order.order_number');

        // Deleting the address keeps it on the order it shipped to
        $this->deleteJson("/api/v1/addresses/{$first}")->assertOk();
        $this->getJson('/api/v1/addresses')->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/orders/{$number}")->assertOk()->assertJsonPath('data.shipping_address.city', 'المعادي');

        // Another user's address is invisible and unusable
        Sanctum::actingAs($this->customerUser(0, '01112345678'));
        $this->putJson("/api/v1/addresses/{$second}", $body)->assertStatus(404);
        $this->deleteJson("/api/v1/addresses/{$second}")->assertStatus(404);
        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id])->assertOk();
        $this->postJson('/api/v1/store/checkout', ['address_id' => $second, 'payment_method' => 'cod'])
            ->assertStatus(422)->assertJsonPath('error_code', 'ADDRESS_NOT_FOUND');
    }

    // ------------------------------------------------------------ app, content, chat

    public function test_app_config_reports_forced_and_optional_updates(): void
    {
        SystemSetting::where('key', 'app_min_version_android')->update(['value' => '1.2.0']);
        SystemSetting::where('key', 'app_latest_version_android')->update(['value' => '1.5.0']);
        Cache::flush();

        $this->getJson('/api/v1/app/config?platform=android&version=1.1.9')->assertOk()
            ->assertJsonPath('data.update_required', true)->assertJsonPath('data.update_available', true);
        $this->getJson('/api/v1/app/config?platform=android&version=1.2.0')->assertOk()
            ->assertJsonPath('data.update_required', false)->assertJsonPath('data.update_available', true);
        $this->getJson('/api/v1/app/config?platform=android&version=1.10.0')->assertOk()
            ->assertJsonPath('data.update_required', false)->assertJsonPath('data.update_available', false);
        $this->getJson('/api/v1/app/config?platform=windows&version=1')->assertStatus(422);
    }

    public function test_content_endpoints_are_public_and_localised(): void
    {
        $this->getJson('/api/v1/content/faq')->assertOk()->assertJsonCount(7, 'data')->assertJsonStructure(['data' => [['id', 'question', 'answer']]]);
        $this->withHeader('X-App-Locale', 'en')->getJson('/api/v1/content/faq')->assertJsonPath('data.0.question', 'Are MAQAM products safe for daily use?');

        $this->getJson('/api/v1/content/pages/privacy')->assertOk()->assertJsonPath('data.slug', 'privacy');
        $this->assertStringContainsString('<p>', $this->getJson('/api/v1/content/pages/terms')->json('data.html'));
        $this->getJson('/api/v1/content/pages/secrets')->assertStatus(404);
    }

    public function test_chatbot_answers_through_gemini_without_leaking_the_key_in_the_url(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'الشحن 35 جنيه.']]]]],
        ])]);

        $this->postJson('/api/v1/chat', ['message' => 'hi'])->assertStatus(401);

        Sanctum::actingAs($this->customerUser(120));

        $this->postJson('/api/v1/chat', [
            'message' => 'كم تكلفة الشحن؟',
            'history' => [['role' => 'user', 'text' => 'مرحبا'], ['role' => 'assistant', 'text' => 'أهلاً بك']],
        ])->assertOk()->assertJsonPath('data.reply', 'الشحن 35 جنيه.')->assertJsonPath('data.messages_left_today', 59);

        Http::assertSent(function (Request $r) {
            return ! str_contains($r->url(), 'test-gemini-key')
                && $r->hasHeader('x-goog-api-key', 'test-gemini-key')
                && count($r['contents']) === 3
                && $r['contents'][1]['role'] === 'model'
                && $r['contents'][2]['parts'][0]['text'] === 'كم تكلفة الشحن؟'
                && str_contains($r['systemInstruction']['parts'][0]['text'], '120 spendable points');
        });

        $this->postJson('/api/v1/chat', ['message' => str_repeat('x', 1001)])->assertStatus(422);
    }

    public function test_chatbot_reports_unavailable_when_gemini_fails_or_is_not_configured(): void
    {
        Sanctum::actingAs($this->customerUser());

        config(['services.gemini.key' => '']);
        $this->postJson('/api/v1/chat', ['message' => 'hi'])->assertStatus(503)->assertJsonPath('error_code', 'CHATBOT_UNAVAILABLE');

        config(['services.gemini.key' => 'test-gemini-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'bad']], 500)]);
        $this->postJson('/api/v1/chat', ['message' => 'hi'])->assertStatus(503)->assertJsonPath('error_code', 'CHATBOT_UNAVAILABLE');
    }

    // ------------------------------------------------------------ merchant and notifications

    public function test_merchant_logo_upload_and_decision_notifications(): void
    {
        Storage::fake('public');
        $user = $this->customerUser();
        $user->update(['preferred_language' => 'en']);
        Sanctum::actingAs($user);

        $this->post('/api/v1/merchant/apply', ['business_name' => 'معرض النور', 'logo' => UploadedFile::fake()->image('logo.png')], ['Accept' => 'application/json'])
            ->assertCreated();
        $merchant = Merchant::where('user_id', $user->id)->firstOrFail();
        Storage::disk('public')->assertExists($merchant->logo_url);

        $this->post('/api/v1/merchant/logo', ['logo' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->post('/api/v1/merchant/logo', ['logo' => UploadedFile::fake()->image('new.jpg')], ['Accept' => 'application/json'])
            ->assertOk();
        Storage::disk('public')->assertMissing($merchant->logo_url);

        // Admin rejects: the applicant is told, in their language, and keeps their customer account
        $admin = User::factory()->create(['role' => 'admin']);
        Admin::create(['user_id' => $admin->id, 'role' => 'super_admin']);
        $this->actingAs($admin, 'web')->post(route('admin.merchants.reject', $merchant), ['reason' => 'Missing papers'])->assertRedirect();

        $this->assertTrue($user->fresh()->is_active);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'type' => 'merchant_update', 'title' => 'Merchant application not accepted']);

        $this->actingAs($admin, 'web')->post(route('admin.merchants.approve', $merchant))->assertRedirect();
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'title' => 'You are now an approved merchant']);
    }

    public function test_push_tokens_are_kept_per_device(): void
    {
        $user = $this->customerUser();
        $token = $user->createToken('Pixel')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/notifications/device-token', ['device_token' => 'phone', 'platform' => 'android'])->assertOk();
        $this->withToken($token)->postJson('/api/v1/notifications/device-token', ['device_token' => 'tablet', 'platform' => 'ios'])->assertOk();
        $this->withToken($token)->postJson('/api/v1/notifications/device-token', ['device_token' => 'phone'])->assertOk();
        $this->assertSame(2, $user->deviceTokens()->count());

        // A token moves with the device when someone else signs in on it
        $other = $this->customerUser(0, '01112345678');
        $this->app['auth']->forgetGuards();
        $this->withToken($other->createToken('Pixel')->plainTextToken)->postJson('/api/v1/notifications/device-token', ['device_token' => 'phone'])->assertOk();
        $this->assertSame(1, $user->deviceTokens()->count());

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v1/auth/logout', ['device_token' => 'tablet'])->assertOk();
        $this->assertSame(0, $user->deviceTokens()->count());
    }
}
