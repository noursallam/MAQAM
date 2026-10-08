<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Rank;
use App\Models\User;
use App\Services\RankService;
use App\Services\Store\OrderNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Every order event reaches the customer in the app and on WhatsApp.
 */
class OrderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Rank $silver;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.senderbot.url' => 'http://senderbot.test',
            'services.senderbot.token' => 'test-token',
            'services.senderbot.session' => 'maqam',
        ]);
        Http::fake(['senderbot.test/*' => Http::response(['success' => true, 'data' => []])]);

        // Run "after the response" work straight away, so its effects can be asserted
        $this->withoutDefer();

        $this->silver = Rank::create([
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

    private function customer(string $language = 'ar', int $points = 0): User
    {
        $user = User::factory()->create(['phone_number' => '01012345678', 'role' => 'customer', 'is_active' => true]);
        $user->forceFill(['preferred_language' => $language])->save();
        Customer::create([
            'user_id' => $user->id, 'rank_id' => $this->silver->id, 'points_balance' => $points,
            'total_points_earned' => $points, 'total_points_spent' => 0,
        ]);

        return $user;
    }

    private function placeOrder(User $user, string $payment = 'cod'): Order
    {
        $category = Category::firstOrCreate(['slug' => 'switches'], ['name_en' => 'Switches', 'name_ar' => 'مفاتيح', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name_en' => 'Switch', 'name_ar' => 'مفتاح',
            'price' => 100, 'stock_quantity' => 5, 'sku' => 'SKU-'.random_int(1000, 9999), 'is_active' => true,
        ]);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/store/cart/add', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $number = $this->postJson('/api/v1/store/checkout', [
            'full_name' => 'أحمد', 'phone' => '01012345678', 'governorate' => 'القاهرة', 'city' => 'مدينة نصر',
            'address' => 'شارع الطيران', 'payment_method' => $payment,
        ])->assertCreated()->json('data.order.order_number');

        return Order::where('order_number', $number)->firstOrFail();
    }

    /**
     * @return list<string> the WhatsApp texts sent so far, oldest first
     */
    private function whatsAppMessages(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), '/chats/send'))
            ->map(fn ($pair) => $pair[0]['message']['text'])
            ->values()->all();
    }

    private function lastNotification(User $user): AppNotification
    {
        return AppNotification::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    public function test_a_cash_order_is_announced_in_the_app_and_on_whatsapp_with_its_details(): void
    {
        $user = $this->customer();
        $order = $this->placeOrder($user);

        $notification = $this->lastNotification($user);
        $this->assertSame('order_update', $notification->type);
        $this->assertStringContainsString($order->order_number, $notification->title);
        $this->assertSame('تم استلام طلبك وسنبدأ تجهيزه.', $notification->body);
        $this->assertSame($order->order_number, $notification->data['order_number']);

        // The app gets the order number with the notification, so tapping it can open the order
        $this->getJson('/api/v1/notifications')->assertOk()
            ->assertJsonPath('data.0.data.order_number', $order->order_number)
            ->assertJsonPath('meta.unread_count', 1);

        $messages = $this->whatsAppMessages();
        $this->assertCount(1, $messages);
        $text = $messages[0];
        $this->assertStringContainsString($order->order_number, $text);
        $this->assertStringContainsString('الحالة الآن: تم الاستلام', $text);
        $this->assertStringContainsString('• مفتاح × 2 — 200.00 ج.م', $text);
        $this->assertStringContainsString('الإجمالي: '.number_format((float) $order->total_amount, 2).' ج.م', $text);
        $this->assertStringContainsString('الدفع: عند الاستلام', $text);
        $this->assertStringContainsString('العنوان: القاهرة، مدينة نصر، شارع الطيران', $text);

        // To the customer's own verified number
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/chats/send') && $r['receiver'] === '201012345678');
    }

    public function test_messages_follow_the_customers_language(): void
    {
        $user = $this->customer('en');
        $this->placeOrder($user);

        $this->assertSame('We got your order and are preparing it.', $this->lastNotification($user)->body);
        $this->assertStringContainsString('Status now: Received', $this->whatsAppMessages()[0]);
        $this->assertStringContainsString('• Switch × 2 — 200.00 EGP', $this->whatsAppMessages()[0]);
    }

    public function test_every_status_change_by_staff_reaches_the_customer(): void
    {
        $user = $this->customer();
        $order = $this->placeOrder($user);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        Admin::create(['user_id' => $admin->id, 'role' => 'super_admin']);

        foreach (['processing' => 'جارٍ التجهيز', 'shipped' => 'تم الشحن', 'delivered' => 'تم التسليم'] as $status => $label) {
            $this->actingAs($admin)
                ->from('/admin/orders')
                ->patch(route('admin.orders.status', $order), ['status' => $status])
                ->assertRedirect();

            $this->assertSame($status, $order->fresh()->status);
            $this->assertSame('order_update', $this->lastNotification($user)->type);
            $this->assertStringContainsString('الحالة الآن: '.$label, last($this->whatsAppMessages()));
        }

        // One when placed, then one per change
        $this->assertSame(4, AppNotification::where('user_id', $user->id)->count());
        $this->assertCount(4, $this->whatsAppMessages());

        // Saving the same status again says nothing new
        $this->actingAs($admin)->from('/admin/orders')
            ->patch(route('admin.orders.status', $order), ['status' => 'delivered']);
        $this->assertSame(4, AppNotification::where('user_id', $user->id)->count());
    }

    public function test_cancelling_an_order_is_confirmed_to_the_customer(): void
    {
        $user = $this->customer();
        $order = $this->placeOrder($user);

        $this->postJson('/api/v1/orders/'.$order->order_number.'/cancel')->assertOk();

        $this->assertSame('تم إلغاء طلبك.', $this->lastNotification($user)->body);
        $this->assertStringContainsString('الحالة الآن: ملغي', last($this->whatsAppMessages()));
    }

    public function test_an_online_order_is_announced_only_once_it_is_paid(): void
    {
        $user = $this->customer();
        $order = Order::create([
            'order_number' => 'MQ-TEST-ONLINE', 'user_id' => $user->id, 'status' => 'new',
            'payment_method' => 'kashier', 'payment_status' => 'pending',
            'subtotal' => 100, 'discount' => 0, 'shipping_cost' => 35, 'total_amount' => 135,
        ]);
        $notifier = app(OrderNotifier::class);

        $this->assertSame(0, AppNotification::where('user_id', $user->id)->count());

        $order->update(['payment_status' => 'paid', 'status' => 'processing']);
        $notifier->paymentConfirmed($order);

        $this->assertSame('تم الدفع بنجاح، وطلبك جارٍ تجهيزه.', $this->lastNotification($user)->body);
        $this->assertStringContainsString('الدفع: بطاقة أو محفظة إلكترونية', $this->whatsAppMessages()[0]);

        $notifier->paymentFailed($order);
        $this->assertSame('لم يكتمل الدفع، ولم يُخصم أي مبلغ.', $this->lastNotification($user)->body);
    }

    public function test_a_whatsapp_outage_never_breaks_the_order(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['senderbot.test/*' => Http::response(['success' => false, 'message' => 'down'], 500)]);

        $user = $this->customer();
        $order = $this->placeOrder($user);

        // The order went through and the in-app notification is still there
        $this->assertSame('new', $order->status);
        $this->assertSame('order_update', $this->lastNotification($user)->type);
    }

    public function test_reaching_a_new_rank_notifies_the_customer(): void
    {
        $user = $this->customer();
        $customer = $user->customer;
        $customer->forceFill(['total_points_earned' => 1200])->save();

        $rank = app(RankService::class)->promoteIfEarned($customer);

        $this->assertSame('Gold', $rank->name_en);
        $notification = $this->lastNotification($user);
        $this->assertSame('rank_upgrade', $notification->type);
        $this->assertStringContainsString('ذهبي', $notification->body);

        // Already there: no second announcement
        $this->assertNull(app(RankService::class)->promoteIfEarned($customer->fresh()));
        $this->assertSame(1, AppNotification::where('user_id', $user->id)->count());
    }
}
