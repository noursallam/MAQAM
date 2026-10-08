<?php

namespace Tests\Feature;

use App\Services\WhatsApp\WhatsAppOtpService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WhatsApp often files an incoming message under an anonymous "@lid" chat
 * instead of the sender's phone number.
 */
class WhatsAppLidChatTest extends TestCase
{
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
     * @param  array<string, list<array{text: string, at: int, fromMe?: bool}>>  $lidChats  messages by chat id
     */
    protected function fakeSenderBot(array $lidChats): void
    {
        Http::fake(function (Request $request) use ($lidChats) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            if ($path === '/chats/send') {
                return Http::response(['success' => true, 'data' => []]);
            }

            if ($path === '/chats') {
                return Http::response(['success' => true, 'data' => collect($lidChats)->map(fn ($messages, $id) => [
                    'id' => $id,
                    'conversationTimestamp' => max(array_column($messages, 'at')),
                ])->values()->all()]);
            }

            $messages = $lidChats[urldecode(basename($path))] ?? [];

            return Http::response(['success' => true, 'data' => array_map(fn ($m) => [
                'key' => ['fromMe' => $m['fromMe'] ?? false, 'addressingMode' => 'lid'],
                'messageTimestamp' => $m['at'],
                'message' => ['conversation' => $m['text']],
            ], $messages)]);
        });
    }

    protected function assertOtpSentTo(?string $receiver): void
    {
        $sends = Http::recorded(fn (Request $r) => parse_url($r->url(), PHP_URL_PATH) === '/chats/send');

        if ($receiver === null) {
            $this->assertCount(0, $sends);

            return;
        }

        $this->assertCount(1, $sends);
        $this->assertSame($receiver, $sends->first()[0]['receiver']);
    }

    public function test_code_sent_from_an_anonymous_chat_is_found_and_the_otp_goes_to_the_phone(): void
    {
        $otp = app(WhatsAppOtpService::class);
        $challenge = $otp->start('01012345678');
        $this->fakeSenderBot(['190365936152604@lid' => [['text' => $challenge['code'], 'at' => now()->timestamp]]]);

        $this->assertTrue($otp->sendOtpIfChallengeReceived($challenge['id']));

        // Never to the anonymous chat: receiving the OTP is what proves the number
        $this->assertOtpSentTo('201012345678');
    }

    public function test_anonymous_chats_without_the_code_do_not_trigger_an_otp(): void
    {
        $otp = app(WhatsAppOtpService::class);
        $challenge = $otp->start('01012345678');
        $this->fakeSenderBot([
            '111@lid' => [['text' => 'MAQAM-000000', 'at' => now()->timestamp]],
            '222@lid' => [['text' => $challenge['code'], 'at' => now()->timestamp, 'fromMe' => true]],
        ]);

        $this->assertFalse($otp->sendOtpIfChallengeReceived($challenge['id']));
        $this->assertOtpSentTo(null);
    }

    public function test_a_message_older_than_the_challenge_is_ignored(): void
    {
        $otp = app(WhatsAppOtpService::class);
        $challenge = $otp->start('01012345678');
        $this->fakeSenderBot([
            '111@lid' => [['text' => $challenge['code'], 'at' => now()->subHour()->timestamp]],
        ]);

        $this->assertFalse($otp->sendOtpIfChallengeReceived($challenge['id']));
        $this->assertOtpSentTo(null);
    }
}
