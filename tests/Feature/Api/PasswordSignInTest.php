<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordSignInTest extends TestCase
{
    use RefreshDatabase;

    private const OTP_SESSION = ['app', 'set-password'];

    private const PASSWORD_SESSION = ['app'];

    /**
     * A customer as created by WhatsApp sign-up, optionally with a password of their own.
     */
    private function customer(string $phone = '01012345678', ?string $password = null, array $extra = []): User
    {
        $user = User::factory()->create($extra + [
            'phone_number' => $phone,
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make($password ?? 'random-'.uniqid()),
        ]);
        $user->forceFill(['password_set_at' => $password ? now() : null])->save();

        return $user;
    }

    private function actingWithToken(User $user, array $abilities): static
    {
        // Each request must resolve the user from its own token
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('test-'.uniqid(), $abilities)->plainTextToken);
    }

    public function test_methods_reports_a_password_only_when_the_customer_chose_one(): void
    {
        $this->customer('01011111111');
        $this->customer('01022222222', 'secret-pass');

        $this->postJson('/api/v1/auth/methods', ['phone' => '01033333333'])
            ->assertOk()->assertJsonPath('data.has_password', false);
        $this->postJson('/api/v1/auth/methods', ['phone' => '01011111111'])
            ->assertOk()->assertJsonPath('data.has_password', false);
        $this->postJson('/api/v1/auth/methods', ['phone' => '+20 102 222 2222'])
            ->assertOk()->assertJsonPath('data.has_password', true);
    }

    public function test_signs_in_with_phone_and_password(): void
    {
        $this->customer('01012345678', 'secret-pass');

        $token = $this->postJson('/api/v1/auth/login', [
            'phone' => '+20 101 234 5678', 'password' => 'secret-pass', 'device_name' => 'Pixel',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.phone_number', '01012345678')
            ->assertJsonPath('data.user.has_password', true)
            ->assertJsonMissingPath('data.user.password')
            ->json('data.token');

        $this->withToken($token)->getJson('/api/v1/wallet')->assertOk();
    }

    public function test_wrong_password_and_unknown_number_get_the_same_answer(): void
    {
        $this->customer('01012345678', 'secret-pass');

        $wrong = $this->postJson('/api/v1/auth/login', [
            'phone' => '01012345678', 'password' => 'nope-nope', 'device_name' => 'Pixel',
        ])->assertStatus(422)->assertJsonPath('error_code', 'INVALID_CREDENTIALS');

        $unknown = $this->postJson('/api/v1/auth/login', [
            'phone' => '01099999999', 'password' => 'nope-nope', 'device_name' => 'Pixel',
        ])->assertStatus(422)->assertJsonPath('error_code', 'INVALID_CREDENTIALS');

        $this->assertSame($wrong->json('message'), $unknown->json('message'));
    }

    public function test_staff_and_frozen_accounts_cannot_sign_in_with_a_password(): void
    {
        $this->customer('01011111111', 'secret-pass', ['role' => 'admin']);
        $this->customer('01022222222', 'secret-pass', ['is_active' => false]);

        $this->postJson('/api/v1/auth/login', [
            'phone' => '01011111111', 'password' => 'secret-pass', 'device_name' => 'Pixel',
        ])->assertStatus(422)->assertJsonPath('error_code', 'INVALID_CREDENTIALS');

        $this->postJson('/api/v1/auth/login', [
            'phone' => '01022222222', 'password' => 'secret-pass', 'device_name' => 'Pixel',
        ])->assertStatus(403)->assertJsonPath('error_code', 'ACCOUNT_FROZEN_FRAUD');
    }

    public function test_a_website_account_from_before_the_flag_is_marked_on_first_password_sign_in(): void
    {
        $user = $this->customer('01012345678', 'secret-pass');
        $user->forceFill(['password_set_at' => null])->save();

        $this->postJson('/api/v1/auth/login', [
            'phone' => '01012345678', 'password' => 'secret-pass', 'device_name' => 'Pixel',
        ])->assertOk()->assertJsonPath('data.user.has_password', true);

        $this->assertNotNull($user->fresh()->password_set_at);
    }

    public function test_a_new_customer_completes_their_profile_then_signs_in_with_the_password(): void
    {
        $user = $this->customer();

        $this->actingWithToken($user, self::OTP_SESSION)
            ->postJson('/api/v1/user/complete-profile', [
                'full_name' => 'أحمد محمود',
                'password' => 'my-new-pass',
                'password_confirmation' => 'my-new-pass',
            ])
            ->assertOk()
            ->assertJsonPath('data.user.full_name', 'أحمد محمود')
            ->assertJsonPath('data.user.has_password', true);

        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => ''])->postJson('/api/v1/auth/login', [
            'phone' => '01012345678', 'password' => 'my-new-pass', 'device_name' => 'Pixel',
        ])->assertOk();
    }

    public function test_completing_a_profile_validates_the_password(): void
    {
        $user = $this->customer();
        $this->actingWithToken($user, self::OTP_SESSION);

        $this->postJson('/api/v1/user/complete-profile', [
            'full_name' => 'أحمد', 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertStatus(422);

        $this->postJson('/api/v1/user/complete-profile', [
            'full_name' => 'أحمد', 'password' => 'long-enough-1', 'password_confirmation' => 'different-1',
        ])->assertStatus(422);

        $this->assertNull($user->fresh()->password_set_at);
    }

    public function test_only_a_whatsapp_verified_session_may_set_a_password_without_the_old_one(): void
    {
        $user = $this->customer('01012345678', 'old-password');

        // Signed in with the password: the old one is required, here and for completing a profile
        $this->actingWithToken($user, self::PASSWORD_SESSION);
        $this->postJson('/api/v1/user/complete-profile', [
            'full_name' => 'x', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertStatus(403)->assertJsonPath('error_code', 'OTP_REQUIRED');
        $this->putJson('/api/v1/user/password', [
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertStatus(422);
        $this->putJson('/api/v1/user/password', [
            'current_password' => 'wrong-one', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertStatus(422)->assertJsonPath('error_code', 'WRONG_PASSWORD');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));

        // Signed in with a WhatsApp code after forgetting it: no old password needed
        $this->actingWithToken($user, self::OTP_SESSION)
            ->putJson('/api/v1/user/password', [
                'password' => 'new-password', 'password_confirmation' => 'new-password',
            ])->assertOk();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_changing_the_password_signs_out_other_devices(): void
    {
        $user = $this->customer('01012345678', 'old-password');
        $user->createToken('other-phone', self::PASSWORD_SESSION);

        $this->actingWithToken($user, self::PASSWORD_SESSION)
            ->putJson('/api/v1/user/password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertOk();

        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame(0, $user->tokens()->where('name', 'other-phone')->count());
    }
}
