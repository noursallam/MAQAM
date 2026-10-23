<?php

namespace Tests\Feature;

use App\Jobs\SendPushNotification;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\DeviceToken;
use App\Models\Merchant;
use App\Models\Rank;
use App\Models\User;
use App\Services\Push\FcmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\FakesFirebase;
use Tests\TestCase;

class AdminPushNotificationsTest extends TestCase
{
    use FakesFirebase;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Rank::create([
            'name_en' => 'Silver', 'name_ar' => 'فضي', 'min_points' => 0, 'max_points' => null, 'is_active' => true,
            'customer_points_per_scan' => 10, 'merchant_points_per_scan' => 5, 'wheel_win_probability' => 0, 'wheel_cost_points' => 50,
        ]);
        $this->configureFirebase();
    }

    protected function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        Admin::create(['user_id' => $user->id, 'role' => 'super_admin']);

        return $user;
    }

    protected function customer(string $phone, string $language = 'ar'): User
    {
        $user = User::factory()->create(['role' => 'customer', 'phone_number' => $phone, 'preferred_language' => $language]);
        Customer::create(['user_id' => $user->id, 'rank_id' => Rank::first()->id]);
        DeviceToken::register($user, 'token-'.$phone, 'android');

        return $user;
    }

    protected function fakeFcm(array $dead = []): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => fn (Request $r) => match ($dead[$r['message']['token']] ?? null) {
                'unregistered' => Http::response(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404),
                'malformed' => Http::response(['error' => [
                    'status' => 'INVALID_ARGUMENT',
                    'message' => 'The registration token is not a valid FCM registration token',
                    'details' => [['errorCode' => 'INVALID_ARGUMENT']],
                ]], 400),
                'other_project' => Http::response(['error' => ['status' => 'PERMISSION_DENIED', 'details' => [['errorCode' => 'SENDER_ID_MISMATCH']]]], 403),
                'busy' => Http::response(['error' => ['status' => 'UNAVAILABLE']], 503),
                default => Http::response(['name' => 'projects/maqam-test/messages/1']),
            },
        ]);
    }

    public function test_one_push_reaches_every_device_and_forgets_the_dead_ones(): void
    {
        $this->fakeFcm([
            'gone' => 'unregistered',
            'garbage' => 'malformed',
            'foreign' => 'other_project',
            'busy-device' => 'busy',
        ]);
        $user = $this->customer('01011111111');
        foreach (['gone', 'garbage', 'foreign', 'busy-device', 'tablet'] as $token) {
            DeviceToken::register($user, $token, 'android');
        }

        (new SendPushNotification([$user->id], 'عنوان', 'نص', 'offer'))->handle(app(FcmService::class));

        $this->assertEqualsCanonicalizing(['token-01011111111', 'tablet', 'busy-device'], DeviceToken::pluck('token')->all());
        $this->assertSame(
            ['title' => 'عنوان', 'devices' => 6, 'sent' => 2, 'failed' => 1, 'removed' => 3],
            collect(Cache::get(SendPushNotification::LAST_RUN))->except('finished_at')->all(),
        );
        // One access token for the whole broadcast
        $this->assertSame(1, collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'oauth2'))->count());
    }

    public function test_a_large_audience_is_sent_in_parallel_batches(): void
    {
        $this->fakeFcm();
        $user = $this->customer('01011111111');
        foreach (range(1, 60) as $i) {
            DeviceToken::register($user, "device-$i", 'android');
        }

        $results = app(FcmService::class)->sendMany(DeviceToken::pluck('token')->all(), 'عنوان', 'نص');

        $this->assertCount(61, $results);
        $this->assertSame([FcmService::SENT], array_values(array_unique($results)));
    }

    public function test_merchants_include_customers_whose_application_was_approved(): void
    {
        Queue::fake();
        $applied = $this->customer('01022222222');
        Merchant::create(['user_id' => $applied->id, 'business_name' => 'معرض النور', 'merchant_code' => 'M-NOUR', 'is_approved' => true]);
        $pending = $this->customer('01033333333');
        Merchant::create(['user_id' => $pending->id, 'business_name' => 'معرض الأمل', 'merchant_code' => 'M-AMAL', 'is_approved' => false]);
        $this->customer('01044444444');

        $this->actingAs($this->admin())->post('/admin/notifications', [
            'title' => 'للتجار', 'body' => 'رسالة', 'type' => 'offer', 'segment' => 'merchants',
        ])->assertRedirect('/admin/notifications')->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', ['user_id' => $applied->id, 'title' => 'للتجار']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $pending->id]);
        Queue::assertPushedOn('push', SendPushNotification::class, fn ($job) => $job->userIds === [$applied->id]);
    }

    public function test_one_customer_can_be_reached_by_phone_number_in_their_language(): void
    {
        Queue::fake();
        $english = $this->customer('01055555555', 'en');
        $this->customer('01066666666');

        $this->actingAs($this->admin())->post('/admin/notifications', [
            'title' => 'تجربة', 'body' => 'نص', 'title_en' => 'Test', 'body_en' => 'Text',
            'type' => 'offer', 'segment' => 'phone', 'phone' => '+20 10 5555 5555',
        ])->assertSessionHas('success', __('admin.notifications.sent_summary', ['users' => 1, 'devices' => 1]));

        $this->assertDatabaseHas('notifications', ['user_id' => $english->id, 'title' => 'Test']);
        $this->assertSame(1, DB::table('notifications')->count());
        Queue::assertPushed(SendPushNotification::class, fn ($job) => $job->userIds === [$english->id] && $job->title === 'Test');
    }

    public function test_an_unknown_phone_number_is_refused(): void
    {
        Queue::fake();

        $this->actingAs($this->admin())->from('/admin/notifications/create')->post('/admin/notifications', [
            'title' => 'تجربة', 'body' => 'نص', 'type' => 'offer', 'segment' => 'phone', 'phone' => '01099999999',
        ])->assertRedirect('/admin/notifications/create')->assertSessionHasErrors('phone');

        $this->assertSame(0, DB::table('notifications')->count());
        Queue::assertNothingPushed();
    }

    public function test_the_history_page_warns_when_pushes_are_stuck_in_the_queue(): void
    {
        $this->customer('01011111111');
        DB::table('jobs')->insert([
            'queue' => 'push', 'payload' => '{}', 'attempts' => 0,
            'available_at' => now()->subMinutes(10)->timestamp, 'created_at' => now()->subMinutes(10)->timestamp,
        ]);

        $this->actingAs($this->admin())->get('/admin/notifications')
            ->assertOk()
            ->assertSee(__('admin.notifications.configured'))
            ->assertSee('schedule:run');
    }

    public function test_the_history_page_shows_the_last_send(): void
    {
        Cache::forever(SendPushNotification::LAST_RUN, [
            'title' => 'عرض', 'devices' => 12, 'sent' => 11, 'failed' => 0, 'removed' => 1, 'finished_at' => now()->toIso8601String(),
        ]);

        $this->actingAs($this->admin())->get('/admin/notifications')
            ->assertOk()
            ->assertSee(__('admin.notifications.last_run_detail', ['sent' => 11, 'failed' => 0, 'removed' => 1]))
            ->assertDontSee('schedule:run');
    }
}
