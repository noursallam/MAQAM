<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// TEMPORARY: smoke test for the experimental website spin page
class SpinPageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_spin_page_renders_and_spins(): void
    {
        $this->get('/spin')->assertRedirect('/login');

        $rank = Rank::create(['name_ar' => 'فضي', 'name_en' => 'Silver', 'min_points' => 0, 'max_points' => 999, 'customer_points_per_scan' => 10, 'merchant_points_per_scan' => 5, 'wheel_cost_points' => 10, 'wheel_win_probability' => 0, 'is_active' => true]);
        $user = User::factory()->create();
        Customer::create(['user_id' => $user->id, 'rank_id' => $rank->id, 'points_balance' => 100]);

        $this->actingAs($user)->get('/spin')->assertOk()->assertSee('mqSpinWheel', false);
        // Win probability 0: always lands on the "no prize" slice and costs the spin price
        $this->actingAs($user)->postJson('/spin')->assertOk()
            ->assertJson(['is_win' => false, 'balance' => 90, 'slice_index' => 0]);
    }
}
