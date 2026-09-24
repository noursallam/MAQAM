<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Rank;
use App\Models\User;
use App\Services\Payment\KashierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StorefrontBuyingCycleTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected Rank $rank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rank = Rank::firstOrCreate(
            ['name_en' => 'Silver'],
            [
                'name_ar' => 'فضي',
                'min_points' => 0,
                'max_points' => 999,
                'customer_points_per_scan' => 10,
                'merchant_points_per_scan' => 5,
                'wheel_win_probability' => 0.20,
                'wheel_cost_points' => 50,
                'is_active' => true,
            ]
        );

        $this->category = Category::firstOrCreate(
            ['slug' => 'test-switches'],
            ['name_en' => 'Switches', 'name_ar' => 'مفاتيح', 'is_active' => true]
        );
    }

    protected function makeProduct(string $sku = 'TEST-PRD-01', float $price = 75.00, int $stock = 25): Product
    {
        return Product::firstOrCreate(
            ['sku' => $sku],
            [
                'category_id' => $this->category->id,
                'name_en' => 'Test Product ' . $sku,
                'name_ar' => 'منتج تجريبي ' . $sku,
                'price' => $price,
                'stock_quantity' => $stock,
                'is_active' => true,
            ]
        );
    }

    protected function createCustomerUser(string $phone = '01011112222'): User
    {
        $user = User::create([
            'full_name' => 'عميل تجريبي',
            'phone_number' => $phone,
            'email' => $phone . '@test.com',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_active' => true,
            'preferred_language' => 'ar',
        ]);

        Customer::create([
            'user_id' => $user->id,
            'rank_id' => $this->rank->id,
            'points_balance' => 500,
            'total_points_earned' => 500,
            'total_points_spent' => 0,
        ]);

        return $user;
    }

    public function test_home_page_renders_with_database_content(): void
    {
        $product = $this->makeProduct('TEST-HOME-01', 75.00);

        $response = $this->get(route('store.home'));

        $response->assertStatus(200);
        $response->assertSee($this->category->name_ar);
        $response->assertSee('75.00');
    }

    public function test_shop_page_renders_and_filters_products(): void
    {
        $product = $this->makeProduct('TEST-SHOP-SKT', 55.00);

        $response = $this->get(route('store.shop', ['q' => 'تجريبي']));

        $response->assertStatus(200);
        $response->assertSee('55.00');
    }

    public function test_product_details_page_renders(): void
    {
        $product = $this->makeProduct('TEST-DETAIL-01', 88.00);

        $response = $this->get(route('store.product', $product->id));

        $response->assertStatus(200);
        $response->assertSee($product->name_ar);
        $response->assertSee('88.00');
    }

    public function test_can_add_item_to_cart_and_view_cart(): void
    {
        $user = $this->createCustomerUser('01011113333');
        $product = $this->makeProduct('TEST-CART-01', 45.00);

        $response = $this->actingAs($user)->post(route('store.cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertSessionHas('success');

        $cartResponse = $this->actingAs($user)->get(route('store.cart'));
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee($product->name_ar);
    }

    public function test_can_apply_coupon_code(): void
    {
        $user = $this->createCustomerUser('01011114444');
        $product = $this->makeProduct('TEST-COUPON-01', 100.00);

        Coupon::firstOrCreate(
            ['code' => 'TESTSAVE20'],
            [
                'name' => 'Save 20%',
                'type' => 'percentage',
                'value' => 20,
                'scope' => 'all',
                'assignment' => Coupon::ASSIGNMENT_PUBLIC_CODE,
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addMonth(),
                'is_active' => true,
                'is_public' => true,
            ]
        );

        $this->actingAs($user)->post(route('store.cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('store.cart.coupon.apply'), [
            'code' => 'TESTSAVE20',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_complete_checkout_with_cash_on_delivery(): void
    {
        $user = $this->createCustomerUser('01019876543');
        $product = $this->makeProduct('TEST-COD-01', 60.00, 20);
        $initialStock = $product->stock_quantity;

        // 1. Add product to cart
        $this->actingAs($user)->post(route('store.cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // 2. Submit checkout form with COD
        $response = $this->actingAs($user)->post(route('store.checkout.process'), [
            'full_name' => 'أحمد العميل',
            'phone' => '01019876543',
            'governorate' => 'القاهرة',
            'city' => 'المعادي',
            'address' => 'شارع 9، عمارة 12، شقة 4',
            'payment_method' => 'cod',
            'notes' => 'توصيل في الفترة الصباحية',
        ]);

        // 3. Verify order created in database
        $order = Order::where('payment_method', 'cod')->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('new', $order->status);
        $this->assertEquals('pending', $order->payment_status);

        // 4. Verify redirected to order confirmation
        $response->assertRedirect(route('store.order.confirmation', ['orderNumber' => $order->order_number]));

        // 5. Verify confirmation page loads successfully
        $confirmationResponse = $this->actingAs($user)->get(route('store.order.confirmation', ['orderNumber' => $order->order_number]));
        $confirmationResponse->assertStatus(200);
        $confirmationResponse->assertSee($order->order_number);
        $confirmationResponse->assertSee('أحمد العميل');

        // 6. Verify stock was decremented
        $product->refresh();
        $this->assertEquals($initialStock - 2, $product->stock_quantity);
    }

    public function test_kashier_redirect_signature_verification(): void
    {
        $kashierService = app(KashierService::class);

        $params = [
            'paymentStatus' => 'SUCCESS',
            'cardDataToken' => 'null',
            'maskedCard' => '512345xxxxxx0008',
            'merchantOrderId' => 'MQ-TEST-12345',
            'orderId' => 'KASH-9988',
            'cardBrand' => 'MASTERCARD',
            'orderReference' => 'REF-99',
            'transactionId' => 'TX-100200',
            'amount' => '150.00',
            'currency' => 'EGP',
        ];

        $fields = [
            'paymentStatus', 'cardDataToken', 'maskedCard', 'merchantOrderId', 'orderId',
            'cardBrand', 'orderReference', 'transactionId', 'amount', 'currency',
        ];
        $parts = [];
        foreach ($fields as $field) {
            $parts[] = "{$field}=" . ($params[$field] ?? 'null');
        }
        $payload = implode('&', $parts);
        $apiKey = config('kashier.payment_api_key');
        $validSignature = hash_hmac('sha256', $payload, $apiKey, false);

        $params['signature'] = $validSignature;

        $this->assertTrue($kashierService->validateRedirectSignature($params));

        // Tampered signature must fail
        $params['signature'] = 'tampered_signature_123';
        $this->assertFalse($kashierService->validateRedirectSignature($params));
    }

    public function test_customer_registration_and_profile_access(): void
    {
        $response = $this->post(route('store.register.submit'), [
            'full_name' => 'محمود طارق',
            'phone' => '01298765432',
            'email' => 'mahmoud@test.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('store.profile'));

        $this->assertAuthenticated();

        $profileResponse = $this->get(route('store.profile'));
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee('محمود طارق');
        $profileResponse->assertSee('فضي');
    }

    public function test_kashier_webhook_updates_order_and_payment_status(): void
    {
        $user = $this->createCustomerUser('01019998888');
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'MQ-WEBHOOK-TEST',
            'status' => 'new',
            'subtotal' => 200.00,
            'shipping_cost' => 35.00,
            'total_amount' => 235.00,
            'payment_method' => 'kashier',
            'payment_status' => 'pending',
        ]);

        $kashierService = app(KashierService::class);
        $payloadData = [
            'amount' => 235,
            'channel' => 'e-commerce',
            'currency' => 'EGP',
            'kashierOrderId' => 'KASH-WH-001',
            'merchantOrderId' => $order->order_number,
            'method' => 'card',
            'orderReference' => 'ORD-REF-1',
            'status' => 'SUCCESS',
            'transactionId' => 'TX-WH-123456',
            'transactionResponseCode' => '00',
            'signatureKeys' => [
                'amount', 'channel', 'currency', 'kashierOrderId', 'merchantOrderId',
                'method', 'orderReference', 'status', 'transactionId', 'transactionResponseCode'
            ]
        ];

        // Compute signature per spec
        $keys = $payloadData['signatureKeys'];
        sort($keys);
        $pairs = [];
        foreach ($keys as $k) {
            $pairs[] = $k . '=' . rawurlencode((string) $payloadData[$k]);
        }
        $str = implode('&', $pairs);
        $sig = hash_hmac('sha256', $str, config('kashier.payment_api_key'), false);

        $response = $this->postJson(route('payment.kashier.webhook'), [
            'event' => 'pay',
            'data' => $payloadData,
        ], [
            'x-kashier-signature' => $sig,
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('processing', $order->status);
    }

    public function test_checkout_with_loyalty_wallet_points(): void
    {
        $user = $this->createCustomerUser('01017776666');
        $customer = $user->customer;
        // Give enough points (e.g. 2000 points)
        $customer->update(['points_balance' => 2000]);

        $product = $this->makeProduct('TEST-WALLET-01', 50.00, 10);

        $this->actingAs($user)->post(route('store.cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('store.checkout.process'), [
            'full_name' => 'عميل محفظة',
            'phone' => '01017776666',
            'governorate' => 'القاهرة',
            'city' => 'المعادي',
            'address' => 'شارع النصر',
            'payment_method' => 'wallet',
        ]);

        $order = Order::where('payment_method', 'wallet')->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('processing', $order->status);

        $response->assertRedirect(route('store.order.confirmation', ['orderNumber' => $order->order_number]));
    }
}
