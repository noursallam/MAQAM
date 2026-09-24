<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Rank;
use App\Models\User;
use App\Services\Store\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOptionPricingTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Rank::firstOrCreate(
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

        $this->category = Category::create([
            'name_en' => 'Switches & Sockets',
            'name_ar' => 'مفاتيح وبرايز',
            'slug' => 'switches-sockets',
            'is_active' => true,
        ]);
    }

    public function test_product_option_pricing_and_min_price_calculation(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name_en' => 'Modular Switch',
            'name_ar' => 'مفتاح معياري',
            'sku' => 'SW-MOD-01',
            'price' => 50.00,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        $opt1 = $product->options()->create(['name' => 'المقاس', 'value' => '10A', 'price' => 35.00, 'sort_order' => 1]);
        $opt2 = $product->options()->create(['name' => 'المقاس', 'value' => '16A', 'price' => 45.00, 'sort_order' => 2]);
        $opt3 = $product->options()->create(['name' => 'المقاس', 'value' => '24mm', 'price' => 60.00, 'sort_order' => 3]);
        $opt4 = $product->options()->create(['name' => 'المقاس', 'value' => '32mm', 'price' => 75.00, 'sort_order' => 4]);

        $product->load('options');

        $this->assertTrue($product->hasOptionPricing());
        $this->assertTrue($product->isPriceVariable());
        $this->assertEquals(35.00, $product->minPrice());
        $this->assertEquals(75.00, $product->maxPrice());
    }

    public function test_cart_and_checkout_respect_selected_option_pricing(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name_en' => 'Modular Switch',
            'name_ar' => 'مفتاح معياري',
            'sku' => 'SW-MOD-02',
            'price' => 50.00,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        $small = $product->options()->create(['name' => 'المقاس', 'value' => '10A', 'price' => 35.00, 'sort_order' => 1]);
        $large = $product->options()->create(['name' => 'المقاس', 'value' => '32mm', 'price' => 75.00, 'sort_order' => 2]);

        $user = User::create([
            'full_name' => 'خالد محمد',
            'phone_number' => '01011122233',
            'email' => 'khaled@test.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        // Add small option via HTTP endpoint
        $response1 = $this->actingAs($user)->post(route('store.cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
            'option_id' => $small->id,
        ]);
        $response1->assertSessionHas('success');

        // Add large option via HTTP endpoint
        $response2 = $this->actingAs($user)->post(route('store.cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'option_id' => $large->id,
        ]);
        $response2->assertSessionHas('success');

        $cartService = app(CartService::class);
        $cart = $cartService->getCart();
        $this->assertCount(2, $cart->items);

        $cartSmall = $cart->items->firstWhere('product_option_id', $small->id);
        $cartLarge = $cart->items->firstWhere('product_option_id', $large->id);
        $this->assertEquals(35.00, (float) $cartSmall->unit_price);
        $this->assertStringContainsString('10A', $cartSmall->option_label);
        $this->assertEquals(75.00, (float) $cartLarge->unit_price);
        $this->assertStringContainsString('32mm', $cartLarge->option_label);

        // Perform checkout
        $response = $this->actingAs($user)->post(route('store.checkout.process'), [
            'full_name' => 'خالد محمد',
            'phone' => '01011122233',
            'governorate' => 'القاهرة',
            'city' => 'مدينة نصر',
            'address' => 'شارع عباس العقاد',
            'payment_method' => 'cod',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $order = Order::with('items')->latest()->first();
        $this->assertNotNull($order);
        $this->assertCount(2, $order->items);

        $orderItemSmall = $order->items->firstWhere('product_option_id', $small->id);
        $orderItemLarge = $order->items->firstWhere('product_option_id', $large->id);

        $this->assertNotNull($orderItemSmall);
        $this->assertEquals(35.00, (float) $orderItemSmall->unit_price);
        $this->assertEquals(70.00, (float) $orderItemSmall->subtotal);
        $this->assertStringContainsString('10A', $orderItemSmall->option_label);

        $this->assertNotNull($orderItemLarge);
        $this->assertEquals(75.00, (float) $orderItemLarge->unit_price);
        $this->assertEquals(75.00, (float) $orderItemLarge->subtotal);
        $this->assertStringContainsString('32mm', $orderItemLarge->option_label);
    }
}
