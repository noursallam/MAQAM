<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class WebChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => 'test-gemini-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'الشحن 35 جنيه.']]]]],
        ])]);
    }

    /**
     * Do what the browser does: fetch a puzzle, burn the CPU time, wait a human moment.
     */
    protected function solvedCounter(?array $challenge = null): int
    {
        $challenge ??= $this->getJson(route('store.chat.challenge'))->assertOk()->json('challenge');

        for ($counter = 0; ; $counter++) {
            $hash = hash('sha256', $challenge['nonce'].':'.$counter, true);
            $bits = '';
            foreach (str_split(substr($hash, 0, 4)) as $byte) {
                $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
            }
            if (strspn($bits, '0') >= $challenge['bits']) {
                break;
            }
        }

        usleep(450_000);

        return $counter;
    }

    protected function assertGeminiNeverCalled(): void
    {
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'generativelanguage'));
    }

    public function test_widget_is_on_the_storefront(): void
    {
        $this->get(route('store.faq'))->assertOk()->assertSee('mqChatPanel', false);
    }

    public function test_a_solved_puzzle_gets_an_answer_and_history_stays_on_the_server(): void
    {
        $first = $this->postJson(route('store.chat.send'), ['message' => 'كم الشحن؟', 'counter' => $this->solvedCounter()])
            ->assertOk()->assertJsonPath('reply', 'الشحن 35 جنيه.');

        // The reply carries the next puzzle, so a conversation needs no extra round trip
        $this->postJson(route('store.chat.send'), ['message' => 'شكرا', 'counter' => $this->solvedCounter($first->json('challenge'))])
            ->assertOk();

        Http::assertSent(fn (Request $r) => count($r['contents']) === 3
            && $r['contents'][0]['parts'][0]['text'] === 'كم الشحن؟'
            && $r['contents'][1]['role'] === 'model');
    }

    public function test_posting_without_solving_the_puzzle_is_refused(): void
    {
        // No puzzle requested at all
        $this->postJson(route('store.chat.send'), ['message' => 'hi', 'counter' => 0])->assertStatus(422);

        // Puzzle requested, wrong answer
        $this->getJson(route('store.chat.challenge'))->assertOk();
        usleep(450_000);
        $this->postJson(route('store.chat.send'), ['message' => 'hi', 'counter' => -1])->assertStatus(422);

        $this->assertGeminiNeverCalled();
    }

    public function test_a_solution_cannot_be_replayed(): void
    {
        $counter = $this->solvedCounter();

        $this->postJson(route('store.chat.send'), ['message' => 'one', 'counter' => $counter])->assertOk();
        $this->postJson(route('store.chat.send'), ['message' => 'two', 'counter' => $counter])->assertStatus(422);

        Http::assertSentCount(1);
    }

    public function test_instant_answers_are_treated_as_a_bot(): void
    {
        $counter = $this->fastCounter(null);

        // The correct solution, but posted the very moment the puzzle was issued
        $pow = session('chat_pow');
        $pow['issued_at'] = microtime(true);
        $this->withSession(['chat_pow' => $pow]);

        $this->postJson(route('store.chat.send'), ['message' => 'hi', 'counter' => $counter])->assertStatus(422);
        $this->assertGeminiNeverCalled();
    }

    public function test_honeypot_swallows_form_filling_bots_without_calling_the_ai(): void
    {
        $this->postJson(route('store.chat.send'), ['message' => 'buy now', 'counter' => $this->solvedCounter(), 'website' => 'http://spam.example'])
            ->assertOk();

        $this->assertGeminiNeverCalled();
    }

    public function test_requests_without_a_csrf_token_or_user_agent_are_refused(): void
    {
        $counter = $this->solvedCounter();

        $this->withHeader('User-Agent', '')->postJson(route('store.chat.send'), ['message' => 'hi', 'counter' => $counter])->assertStatus(422);

        $this->assertGeminiNeverCalled();
        $this->assertContains('web', app('router')->getRoutes()->getByName('store.chat.send')->gatherMiddleware());
    }

    public function test_daily_allowances_stop_a_flood_before_it_reaches_the_ai(): void
    {
        // One address has used its daily allowance
        foreach (range(1, 60) as $i) {
            RateLimiter::hit('web-chat:ip:127.0.0.1', 86400);
        }
        $this->postJson(route('store.chat.send'), ['message' => 'hi', 'counter' => $this->fastCounter(null)])->assertStatus(429);

        // Many addresses together have used the whole site's allowance
        RateLimiter::clear('web-chat:ip:127.0.0.1');
        foreach (range(1, 2000) as $i) {
            RateLimiter::hit('web-chat:site', 86400);
        }
        $this->postJson(route('store.chat.send'), ['message' => 'hi', 'counter' => $this->fastCounter(null)])->assertStatus(429);

        $this->assertGeminiNeverCalled();
    }

    public function test_long_messages_are_rejected(): void
    {
        $this->postJson(route('store.chat.send'), ['message' => str_repeat('x', 501), 'counter' => 1])->assertStatus(422);
        $this->assertGeminiNeverCalled();
    }

    /**
     * Solve like solvedCounter() but skip the real sleep by ageing the puzzle in the session.
     */
    protected function fastCounter(?array $challenge): int
    {
        $challenge ??= $this->getJson(route('store.chat.challenge'))->json('challenge');

        for ($counter = 0; ; $counter++) {
            $hash = hash('sha256', $challenge['nonce'].':'.$counter, true);
            $bits = implode('', array_map(fn ($b) => str_pad(decbin(ord($b)), 8, '0', STR_PAD_LEFT), str_split(substr($hash, 0, 4))));
            if (strspn($bits, '0') >= $challenge['bits']) {
                break;
            }
        }

        $pow = session('chat_pow');
        $pow['issued_at'] -= 2;
        $this->withSession(['chat_pow' => $pow]);

        return $counter;
    }
}
