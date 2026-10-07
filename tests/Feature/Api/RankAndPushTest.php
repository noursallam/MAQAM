<?php

namespace Tests\Feature\Api;

use App\Jobs\SendPushNotification;
use App\Models\CategoryPrize;
use App\Models\Customer;
use App\Models\DeviceToken;
use App\Models\QrCode;
use App\Models\Rank;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Push\FcmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RankAndPushTest extends TestCase
{
    use RefreshDatabase;

    protected Rank $silver;
    protected Rank $gold;

    protected function setUp(): void
    {
        parent::setUp();

        $base = ['customer_points_per_scan' => 10, 'merchant_points_per_scan' => 5, 'wheel_win_probability' => 0, 'wheel_cost_points' => 50, 'is_active' => true];
        $this->silver = Rank::create(['name_en' => 'Silver', 'name_ar' => 'فضي', 'min_points' => 0, 'max_points' => 999] + $base);
        $this->gold = Rank::create(['name_en' => 'Gold', 'name_ar' => 'ذهبي', 'min_points' => 1000, 'max_points' => null] + $base);

        config(['services.firebase.credentials' => '']);
    }

    protected function customerUser(int $balance, int $earned): User
    {
        $user = User::factory()->create();
        DeviceToken::register($user, 'device-token-'.$user->id, 'android');
        Customer::create([
            'user_id' => $user->id, 'rank_id' => $this->silver->id, 'points_balance' => $balance,
            'total_points_earned' => $earned, 'total_points_spent' => $earned - $balance,
        ]);

        return $user;
    }

    protected function qrCode(int $points): QrCode
    {
        $category = CategoryPrize::create([
            'name_en' => 'Prize', 'name_ar' => 'جائزة', 'category_type' => 'gift', 'points_value' => $points,
            'background_color' => '#22C55E', 'is_active' => true,
        ]);

        return QrCode::create([
            'serial_code' => '1111222233334444', 'category_id' => $category->id, 'points_awarded' => $points,
            'status' => 'active', 'generated_at' => now(), 'batch_id' => 'TEST',
        ]);
    }

    /**
     * Point the app at a throwaway service-account key.
     */
    protected function configureFirebase(): void
    {
        // A throwaway key made on the spot, so no private key ever lives in the repository
        $config = storage_path('framework/testing/openssl-'.uniqid().'.cnf');
        @mkdir(dirname($config), 0777, true);
        file_put_contents($config, "[req]
distinguished_name = dn
[dn]
");
        $options = ['config' => $config, 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        openssl_pkey_export(openssl_pkey_new($options), $pem, null, $options);
        @unlink($config);

        $path = storage_path('framework/testing/fcm-'.uniqid().'.json');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, json_encode([
            'project_id' => 'maqam-test', 'client_email' => 'push@maqam-test.iam.gserviceaccount.com', 'private_key' => $pem,
        ]));
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        config(['services.firebase.credentials' => $path]);
        $this->app->forgetInstance(FcmService::class);
    }

    public function test_rank_follows_lifetime_earned_points_not_the_spendable_balance(): void
    {
        // Spent almost everything: balance 50, but 990 earned over time
        $user = $this->customerUser(50, 990);
        $this->qrCode(25);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/qr/scan', ['serial_code' => '1111222233334444'])->assertOk();

        $customer = $user->customer->fresh();
        $this->assertSame(75, (int) $customer->points_balance);
        $this->assertSame($this->gold->id, $customer->rank_id);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'type' => 'rank_upgrade']);

        $this->getJson('/api/v1/wallet')->assertOk()
            ->assertJsonPath('data.rank.name_en', 'Gold')
            ->assertJsonPath('data.next_rank', null)
            ->assertJsonPath('data.progress_percent', 100);
    }

    public function test_spending_points_never_demotes(): void
    {
        $user = $this->customerUser(1200, 1200);
        $user->customer->update(['rank_id' => $this->gold->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/wheel/spin')->assertOk()->assertJsonPath('data.new_points_balance', 1150);

        $this->assertSame($this->gold->id, $user->customer->fresh()->rank_id);
    }

    public function test_wallet_progress_uses_lifetime_points(): void
    {
        Sanctum::actingAs($this->customerUser(100, 400));

        $this->getJson('/api/v1/wallet')->assertOk()
            ->assertJsonPath('data.points_balance', 100)
            ->assertJsonPath('data.points_left_for_next_rank', 600)
            ->assertJsonPath('data.progress_percent', 40);
    }

    public function test_nothing_is_queued_while_firebase_is_not_configured(): void
    {
        Queue::fake();
        $user = $this->customerUser(0, 0);

        app(NotificationService::class)->notify([$user->id], 'عنوان', 'نص', 'offer');

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'type' => 'offer']);
        Queue::assertNothingPushed();
    }

    public function test_notification_is_stored_and_queued_for_push_when_configured(): void
    {
        $this->configureFirebase();
        Queue::fake();
        $user = $this->customerUser(0, 0);

        app(NotificationService::class)->notify([$user->id], 'عنوان', 'نص', 'offer');

        Queue::assertPushedOn('push', SendPushNotification::class, fn ($job) => $job->userIds === [$user->id] && $job->type === 'offer');
    }

    public function test_push_job_sends_through_fcm_and_drops_dead_tokens(): void
    {
        $this->configureFirebase();
        $alive = $this->customerUser(0, 0);
        $gone = User::factory()->create();
        DeviceToken::register($gone, 'dead-token', 'ios');
        // The same person signed in on a second device
        DeviceToken::register($alive, 'tablet-token', 'android');

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => fn (Request $r) => $r['message']['token'] === 'dead-token'
                ? Http::response(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404)
                : Http::response(['name' => 'projects/maqam-test/messages/1']),
        ]);

        (new SendPushNotification([$alive->id, $gone->id], 'عنوان', 'نص', 'order_update', ['order_number' => 'MQ-1']))
            ->handle(app(FcmService::class));

        $sent = collect(Http::recorded())
            ->map(fn ($pair) => $pair[0])
            ->first(fn (Request $r) => str_contains($r->url(), 'projects/maqam-test/messages:send') && $r['message']['token'] === 'device-token-'.$alive->id);

        $this->assertNotNull($sent);
        $this->assertSame(['Bearer ya29.test'], $sent->header('Authorization'));
        $this->assertSame('عنوان', $sent['message']['notification']['title']);
        $this->assertEquals(['order_number' => 'MQ-1', 'type' => 'order_update'], (array) $sent['message']['data']);

        // Every device of the user is reached, and the dead token is forgotten
        $this->assertSame(2, collect(Http::recorded())->filter(fn ($pair) => in_array($pair[0]['message']['token'] ?? null, ['device-token-'.$alive->id, 'tablet-token'], true))->count());
        $this->assertSame(2, $alive->deviceTokens()->count());
        $this->assertSame(0, $gone->deviceTokens()->count());
    }
}
