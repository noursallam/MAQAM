<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.senderbot.url' => 'http://senderbot.test',
            'services.senderbot.token' => 'test-token',
            'services.senderbot.session' => 'maqam',
        ]);
    }

    /**
     * Fake Senderbot; $incoming is the list of texts the customer has sent us.
     */
    protected function fakeSenderBot(array &$incoming): void
    {
        Http::fake([
            'senderbot.test/sessions/status/*' => Http::response(['success' => true, 'data' => [
                'status' => 'authenticated',
                'valid_session' => true,
                'user_info' => ['id' => '201000000000:5@s.whatsapp.net', 'name' => 'MAQAM'],
            ]]),
            'senderbot.test/chats/send*' => Http::response(['success' => true, 'data' => []]),
            'senderbot.test/chats?*' => Http::response(['success' => true, 'data' => []]),
            'senderbot.test/chats/*' => function () use (&$incoming) {
                return Http::response(['success' => true, 'data' => array_map(fn ($text) => [
                    'key' => ['fromMe' => false],
                    'message' => ['conversation' => $text],
                ], $incoming)]);
            },
        ]);
    }

    protected function sentOtp(): ?string
    {
        $otp = null;
        Http::assertSent(function (Request $request) use (&$otp) {
            if (str_contains($request->url(), '/chats/send')
                && preg_match('/\b(\d{6})\b/', $request['message']['text'], $m)) {
                $otp = $m[1];
            }

            return true;
        });

        return $otp;
    }

    public function test_otp_is_sent_only_after_customer_sends_the_challenge_code(): void
    {
        $incoming = [];
        $this->fakeSenderBot($incoming);
        $user = User::factory()->create(['phone_number' => '01012345678']);

        $this->post(route('store.login.submit'), ['phone' => '01012345678'])
            ->assertRedirect(route('store.login.whatsapp'));
        $this->assertGuest();

        $code = \Illuminate\Support\Facades\Cache::get('wa_otp:'.hash('sha256', session('whatsapp_otp_id')))['code'];
        $this->get(route('store.login.whatsapp'))->assertOk()->assertSee($code);

        // Customer has not messaged yet: nothing is sent
        $this->postJson(route('store.login.whatsapp.check'))->assertJson(['sent' => false]);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/chats/send'));

        // A message with the wrong code does not count either
        $incoming[] = 'MAQAM-000000x';
        $this->postJson(route('store.login.whatsapp.check'))->assertJson(['sent' => false]);

        $incoming[] = $code;
        $this->postJson(route('store.login.whatsapp.check'))->assertJson(['sent' => true]);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/chats/send') && $r['receiver'] === '201012345678');

        $otp = $this->sentOtp();
        $this->assertNotNull($otp);

        $wrong = $otp === '111111' ? '222222' : '111111';
        $this->post(route('store.login.whatsapp.verify'), ['otp' => $wrong])->assertSessionHasErrors('otp');
        $this->assertGuest();

        $this->post(route('store.login.whatsapp.verify'), ['otp' => $otp])->assertRedirect(route('store.profile'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_phone_alone_no_longer_logs_in(): void
    {
        $incoming = [];
        $this->fakeSenderBot($incoming);
        User::factory()->create(['phone_number' => '01012345678']);

        $this->post(route('store.login.submit'), ['phone' => '01012345678']);

        $this->assertGuest();
    }

    public function test_login_falls_back_to_error_when_whatsapp_service_is_down(): void
    {
        Http::fake(['senderbot.test/*' => Http::response(null, 500)]);
        User::factory()->create(['phone_number' => '01012345678']);

        $this->post(route('store.login.submit'), ['phone' => '01012345678'])
            ->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_password_login_still_works(): void
    {
        $user = User::factory()->create(['phone_number' => '01012345678']);

        $this->post(route('store.login.submit'), ['phone' => '01012345678', 'password' => 'password'])
            ->assertRedirect(route('store.profile'));

        $this->assertAuthenticatedAs($user);
    }
}
