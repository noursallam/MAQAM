<?php

namespace Tests\Feature\Api;

use App\Models\DeviceLogin;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuickLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Rank::create([
            'name_en' => 'Silver', 'name_ar' => 'فضي', 'min_points' => 0, 'max_points' => null, 'is_active' => true,
            'customer_points_per_scan' => 10, 'merchant_points_per_scan' => 5, 'wheel_win_probability' => 0, 'wheel_cost_points' => 50,
        ]);
    }

    protected function enabled(User $user, string $device = 'android-abc'): string
    {
        Sanctum::actingAs($user);

        return $this->postJson('/api/v1/auth/quick-login/enable', ['device_name' => $device])
            ->assertOk()->json('data.secret');
    }

    public function test_a_phone_signs_back_in_with_its_secret_instead_of_the_password(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'phone_number' => '01012345678']);
        $secret = $this->enabled($user);

        $this->assertSame(64, strlen($secret));
        // Only a hash is kept on the server
        $this->assertNotSame($secret, DeviceLogin::first()->secret_hash);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/quick-login', ['device_name' => 'android-abc', 'secret' => $secret])
            ->assertOk()
            ->assertJsonPath('data.user.phone_number', '01012345678')
            ->assertJsonStructure(['data' => ['token', 'expires_at']]);
        $this->assertNotNull(DeviceLogin::first()->last_used_at);
    }

    public function test_a_wrong_secret_or_another_device_name_is_refused(): void
    {
        $secret = $this->enabled(User::factory()->create(['role' => 'customer']));

        $this->postJson('/api/v1/auth/quick-login', ['device_name' => 'android-abc', 'secret' => str_repeat('x', 64)])
            ->assertStatus(422)->assertJsonPath('error_code', 'QUICK_LOGIN_INVALID');
        $this->postJson('/api/v1/auth/quick-login', ['device_name' => 'android-other', 'secret' => $secret])
            ->assertStatus(422)->assertJsonPath('error_code', 'QUICK_LOGIN_INVALID');
    }

    public function test_a_frozen_account_cannot_sign_back_in(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $secret = $this->enabled($user);
        $user->update(['is_active' => false]);

        $this->postJson('/api/v1/auth/quick-login', ['device_name' => 'android-abc', 'secret' => $secret])
            ->assertStatus(403)->assertJsonPath('error_code', 'ACCOUNT_FROZEN_FRAUD');
    }

    public function test_signing_out_everywhere_or_turning_it_off_ends_quick_sign_in(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->enabled($user, 'android-1');
        $this->enabled($user, 'android-2');

        $this->postJson('/api/v1/auth/quick-login/disable', ['device_name' => 'android-1'])->assertOk();
        $this->assertSame(['android-2'], DeviceLogin::pluck('device_name')->all());

        $this->postJson('/api/v1/auth/logout-all')->assertOk();
        $this->assertSame(0, DeviceLogin::count());
    }

    public function test_enabling_again_replaces_the_old_secret(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $old = $this->enabled($user);
        $new = $this->enabled($user);

        $this->assertSame(1, DeviceLogin::count());
        $this->postJson('/api/v1/auth/quick-login', ['device_name' => 'android-abc', 'secret' => $old])->assertStatus(422);
        $this->postJson('/api/v1/auth/quick-login', ['device_name' => 'android-abc', 'secret' => $new])->assertOk();
    }

    public function test_deleting_the_account_ends_quick_sign_in(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->enabled($user);

        $this->deleteJson('/api/v1/user/account', ['confirm' => 'DELETE'])->assertOk();

        $this->assertSame(0, DeviceLogin::count());
    }
}
