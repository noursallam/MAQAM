<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Seminar;
use App\Models\User;
use App\Services\SeminarDrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class SeminarDrawTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        Admin::create(['user_id' => $user->id, 'role' => 'super_admin']);

        return $user;
    }

    protected function seminar(array $prizes = ['تليفزيون', 'شنيور', 'شنيور'], string $when = 'live'): Seminar
    {
        [$start, $end] = match ($when) {
            'upcoming' => [now()->addDay(), now()->addDays(2)],
            'ended' => [now()->subDays(2), now()->subDay()],
            default => [now()->subHour(), now()->addHour()],
        };
        $seminar = Seminar::create(['title' => 'سيمينار القاهرة', 'starts_at' => $start, 'ends_at' => $end]);
        foreach ($prizes as $i => $prize) {
            $seminar->draws()->create(['position' => $i + 1, 'prize' => $prize]);
        }

        return $seminar;
    }

    protected function attendees(Seminar $seminar, int $count): array
    {
        return collect(range(1, $count))->map(function () use ($seminar) {
            $user = User::factory()->create(['role' => 'customer']);
            $seminar->registrations()->create(['user_id' => $user->id]);

            return $user->id;
        })->all();
    }

    public function test_the_admin_creates_a_seminar_with_one_draw_per_prize_line_in_cairo_time(): void
    {
        $this->actingAs($this->admin())->post('/admin/seminars', [
            'title' => 'سيمينار الإسكندرية',
            'location' => 'فندق هيلتون',
            'starts_at' => '2026-11-01T18:00',
            'ends_at' => '2026-11-01T21:00',
            'prizes' => "تليفزيون\n\n  شنيور  \nشنيور\n",
        ])->assertRedirect();

        $seminar = Seminar::firstOrFail();
        $this->assertSame(['تليفزيون', 'شنيور', 'شنيور'], $seminar->draws()->pluck('prize')->all());
        $this->assertSame([1, 2, 3], $seminar->draws()->pluck('position')->all());
        // 18:00 in Cairo (UTC+2 in November) is 16:00 UTC
        $this->assertSame('2026-11-01 16:00:00', $seminar->starts_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_draws_follow_the_prize_order_and_nobody_wins_twice(): void
    {
        $seminar = $this->seminar();
        $people = $this->attendees($seminar, 3);

        $winners = collect(app(SeminarDrawService::class)->drawAll($seminar))
            ->map(fn ($draw) => [$draw->prize, $draw->winner_user_id]);

        $this->assertSame(['تليفزيون', 'شنيور', 'شنيور'], $winners->pluck(0)->all());
        $this->assertEqualsCanonicalizing($people, $winners->pluck(1)->all());
        // Each winner hears about it
        foreach ($people as $id) {
            $this->assertDatabaseHas('notifications', ['user_id' => $id, 'type' => 'seminar']);
        }
    }

    public function test_drawing_stops_when_everyone_has_won(): void
    {
        $seminar = $this->seminar(['أ', 'ب', 'ج']);
        $this->attendees($seminar, 2);

        $this->assertCount(2, app(SeminarDrawService::class)->drawAll($seminar));
        $this->assertSame(1, $seminar->draws()->whereNull('drawn_at')->count());

        $this->expectExceptionMessage(SeminarDrawService::NO_ONE_LEFT);
        app(SeminarDrawService::class)->drawNext($seminar);
    }

    public function test_nothing_is_drawn_before_the_seminar_starts(): void
    {
        $seminar = $this->seminar(when: 'upcoming');
        $this->attendees($seminar, 2);

        $this->actingAs($this->admin())->post("/admin/seminars/{$seminar->id}/draw")
            ->assertSessionHasErrors('draw');
        $this->assertSame(0, $seminar->draws()->whereNotNull('drawn_at')->count());
    }

    public function test_every_attendee_can_win_over_many_draws(): void
    {
        // A crude fairness check: with 3 people and 1 prize, everyone wins sometimes
        $wins = [];
        for ($round = 0; $round < 60; $round++) {
            $seminar = $this->seminar(['جائزة']);
            $people = $this->attendees($seminar, 3);
            $winner = app(SeminarDrawService::class)->drawNext($seminar)->winner_user_id;
            $wins[array_search($winner, $people, true)] = true;
        }

        $this->assertCount(3, $wins);
    }

    public function test_prizes_cannot_change_once_drawing_has_started(): void
    {
        $seminar = $this->seminar();
        $this->attendees($seminar, 1);
        app(SeminarDrawService::class)->drawNext($seminar);

        $this->actingAs($this->admin())->put("/admin/seminars/{$seminar->id}", [
            'title' => 'اسم جديد',
            'starts_at' => now()->subHour()->timezone('Africa/Cairo')->format('Y-m-d\TH:i'),
            'ends_at' => now()->addHour()->timezone('Africa/Cairo')->format('Y-m-d\TH:i'),
            'prizes' => 'جائزة مختلفة',
        ])->assertRedirect();

        $this->assertSame('اسم جديد', $seminar->fresh()->title);
        $this->assertSame(['تليفزيون', 'شنيور', 'شنيور'], $seminar->draws()->pluck('prize')->all());
    }

    public function test_the_app_sees_only_the_live_seminar_and_a_customer_signs_up_once(): void
    {
        $this->seminar(when: 'upcoming');
        $this->seminar(when: 'ended');
        $live = $this->seminar();

        $this->getJson('/api/v1/seminars/current')->assertOk()
            ->assertJsonPath('data.id', $live->id)
            ->assertJsonPath('data.status', 'live')
            ->assertJsonPath('data.is_registered', false)
            ->assertJsonPath('data.draws.0.prize', 'تليفزيون')
            ->assertJsonPath('data.draws.0.is_drawn', false);

        Sanctum::actingAs(User::factory()->create(['role' => 'customer']));
        $this->postJson("/api/v1/seminars/{$live->id}/register")->assertOk()
            ->assertJsonPath('data.is_registered', true)
            ->assertJsonPath('data.registrations_count', 1);
        $this->postJson("/api/v1/seminars/{$live->id}/register")->assertOk()
            ->assertJsonPath('data.registrations_count', 1);
    }

    public function test_no_live_seminar_means_nothing_on_the_home_page(): void
    {
        $this->seminar(when: 'ended');

        $this->getJson('/api/v1/seminars/current')->assertOk()->assertJsonPath('data', null);
    }

    public function test_signing_up_is_refused_outside_the_seminar_time(): void
    {
        $ended = $this->seminar(when: 'ended');
        Sanctum::actingAs(User::factory()->create(['role' => 'customer']));

        $this->postJson("/api/v1/seminars/{$ended->id}/register")->assertStatus(422)
            ->assertJsonPath('error_code', 'SEMINAR_NOT_OPEN');
    }

    public function test_winners_show_by_first_name_and_the_winner_sees_it_is_theirs(): void
    {
        $seminar = $this->seminar(['تليفزيون'], 'ended');
        $winner = User::factory()->create(['role' => 'customer', 'full_name' => 'أحمد محمود', 'phone_number' => '01012345678']);
        $seminar->registrations()->create(['user_id' => $winner->id]);
        $seminar->draws()->first()->update(['winner_user_id' => $winner->id, 'drawn_at' => now()]);

        $this->getJson("/api/v1/seminars/{$seminar->id}")->assertOk()
            ->assertJsonPath('data.status', 'ended')
            ->assertJsonPath('data.draws.0.winner_name', 'أحمد •••678')
            ->assertJsonPath('data.draws.0.is_mine', false);

        Sanctum::actingAs($winner);
        $this->getJson("/api/v1/seminars/{$seminar->id}")->assertJsonPath('data.draws.0.is_mine', true);
    }

    public function test_an_upcoming_seminar_stays_hidden(): void
    {
        $upcoming = $this->seminar(when: 'upcoming');

        $this->getJson("/api/v1/seminars/{$upcoming->id}")->assertNotFound();
    }

    public function test_the_admin_pages_render(): void
    {
        $seminar = $this->seminar();
        $this->attendees($seminar, 2);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/seminars')->assertOk()->assertSee('سيمينار القاهرة');
        $this->actingAs($admin)->get('/admin/seminars/create')->assertOk();
        $this->actingAs($admin)->get("/admin/seminars/{$seminar->id}")->assertOk()->assertSee(__('admin.seminars.draw_next'));
        $this->actingAs($admin)->get("/admin/seminars/{$seminar->id}/edit")->assertOk()->assertSee('شنيور');

        $this->actingAs($admin)->post("/admin/seminars/{$seminar->id}/draw")->assertSessionHas('success');
        $this->assertSame(1, $seminar->draws()->whereNotNull('drawn_at')->count());
    }

    public function test_draw_failures_are_reported_not_thrown(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(SeminarDrawService::NO_ONE_LEFT);

        app(SeminarDrawService::class)->drawNext($this->seminar());
    }
}
