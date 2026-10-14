<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\DeviceToken;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisitorLocationTest extends TestCase
{
    use RefreshDatabase;

    // Tahrir Square
    private const CAIRO = ['latitude' => 30.0444, 'longitude' => 31.2357];

    protected function fakeCairoLookup(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([
            'address' => [
                'city' => 'Cairo',
                'state' => 'Cairo Governorate',
                'ISO3166-2-lvl4' => 'EG-C',
                'country' => 'Egypt',
                'country_code' => 'eg',
            ],
        ])]);
    }

    protected function admin(string $role = 'super_admin'): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        Admin::create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    protected function report(User $user, array $body)
    {
        return $this->actingAs($user)->postJson(route('store.permissions.store'), $body + [
            'location_permission' => 'granted',
            'notification_permission' => 'denied',
        ]);
    }

    public function test_guests_cannot_report(): void
    {
        $this->postJson(route('store.permissions.store'), ['location_permission' => 'granted', 'notification_permission' => 'granted'])
            ->assertRedirect('/login');
    }

    public function test_signing_in_asks_for_permissions(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $this->post(route('store.login.submit'), ['phone' => $user->phone_number, 'password' => 'secret123'])
            ->assertSessionHas('ask_permissions', true);

        $this->get(route('store.faq'))->assertOk()->assertSee('mqPerm', false);
    }

    public function test_guests_do_not_get_the_prompt(): void
    {
        $this->get(route('store.faq'))->assertOk()->assertDontSee('mqPerm', false);
    }

    public function test_a_shared_location_is_saved_and_named(): void
    {
        $this->fakeCairoLookup();
        $user = User::factory()->create();

        $this->report($user, self::CAIRO + ['accuracy' => 35])->assertOk()->assertJson(['saved' => true]);

        $user->refresh();
        $this->assertSame('granted', $user->location_permission);
        $this->assertSame('denied', $user->notification_permission);
        $this->assertNotNull($user->permissions_reported_at);

        $location = $user->latestLocation;
        $this->assertSame(35, $location->accuracy_m);
        $this->assertSame('EG', $location->country_code);
        $this->assertSame('EG-C', $location->region_code);
        $this->assertSame('Cairo', $location->region_name);
        $this->assertSame('Cairo', $location->city);
        $this->assertNotNull($location->geocoded_at);

        app()->setLocale('ar');
        $this->assertSame('القاهرة', $location->regionLabel());

        // Identifies the app to the map service as its usage policy requires
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/reverse') && str_starts_with($r->header('User-Agent')[0] ?? '', 'MAQAM/'));
    }

    public function test_the_same_spot_again_is_not_a_new_row(): void
    {
        $this->fakeCairoLookup();
        $user = User::factory()->create();

        $this->report($user, self::CAIRO)->assertOk();
        $this->report($user, ['latitude' => 30.0450, 'longitude' => 31.2360])->assertOk();
        $this->assertSame(1, UserLocation::count());

        // Alexandria is a new place
        $this->report($user, ['latitude' => 31.2001, 'longitude' => 29.9187])->assertOk();
        $this->assertSame(2, UserLocation::count());
        $this->assertSame(UserLocation::latest('id')->first()->id, $user->fresh()->latest_location_id);
    }

    public function test_coordinates_are_ignored_unless_location_was_granted(): void
    {
        $this->fakeCairoLookup();
        $user = User::factory()->create();

        $this->report($user, self::CAIRO + ['location_permission' => 'denied'])->assertOk();

        $this->assertSame(0, UserLocation::count());
        $this->assertSame('denied', $user->fresh()->location_permission);
    }

    public function test_bad_input_is_rejected(): void
    {
        $this->fakeCairoLookup();
        $user = User::factory()->create();

        $this->report($user, ['latitude' => 120, 'longitude' => 31])->assertJsonValidationErrors('latitude');
        $this->report($user, ['latitude' => 30])->assertJsonValidationErrors('longitude');
        $this->report($user, ['location_permission' => 'maybe'])->assertJsonValidationErrors('location_permission');
    }

    public function test_a_granted_browser_gets_its_push_token_registered(): void
    {
        $this->fakeCairoLookup();
        $user = User::factory()->create();

        $this->report($user, ['notification_permission' => 'granted', 'push_token' => 'web-token-1'])->assertOk();

        $this->assertDatabaseHas('device_tokens', ['user_id' => $user->id, 'token' => 'web-token-1', 'platform' => 'web']);

        // A token without permission is not kept
        $this->report($user, ['notification_permission' => 'denied', 'push_token' => 'web-token-2'])->assertOk();
        $this->assertSame(1, DeviceToken::count());
    }

    public function test_failed_lookups_are_retried_by_the_command(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::sequence()
            ->push('busy', 503)
            ->push(['address' => ['country' => 'Egypt', 'country_code' => 'eg', 'state' => 'Giza Governorate', 'ISO3166-2-lvl4' => 'EG-GZ']])]);

        $user = User::factory()->create();
        $this->report($user, ['latitude' => 29.9773, 'longitude' => 31.1325])->assertOk();

        $location = $user->fresh()->latestLocation;
        $this->assertNull($location->geocoded_at);
        $this->assertSame(1, $location->geocode_attempts);

        $this->travel(2)->seconds();
        $this->artisan('locations:geocode')->assertSuccessful();

        $this->assertSame('EG-GZ', $location->fresh()->region_code);
    }

    public function test_service_worker_is_only_served_when_web_push_is_configured(): void
    {
        $this->get('/firebase-messaging-sw.js')->assertNotFound();

        config(['services.firebase.web' => [
            'api_key' => 'key', 'project_id' => 'maqam', 'messaging_sender_id' => '123',
            'app_id' => '1:123:web:abc', 'vapid_key' => 'vapid',
        ]]);

        $this->get('/firebase-messaging-sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->assertSee('"projectId":"maqam"', false)
            ->assertDontSee('vapid', false);
    }

    public function test_admin_sees_people_by_country_and_governorate(): void
    {
        $this->fakeCairoLookup();
        $cairo = User::factory()->create(['full_name' => 'Cairo Customer']);
        Customer::create(['user_id' => $cairo->id]);
        $this->report($cairo, self::CAIRO)->assertOk();

        $silent = User::factory()->create(['full_name' => 'Silent Customer']);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.audience.index'))
            ->assertOk()
            ->assertSee('Cairo Customer')
            ->assertSee('Silent Customer')
            ->assertSee('القاهرة');

        $this->actingAs($admin)->get(route('admin.audience.index', ['region' => 'EG-C']))
            ->assertOk()
            ->assertSee('Cairo Customer')
            ->assertDontSee('Silent Customer');

        $this->actingAs($admin)->get(route('admin.audience.index', ['location' => 'none']))
            ->assertOk()
            ->assertSee('Silent Customer')
            ->assertDontSee('Cairo Customer');

        $this->actingAs($admin)->get(route('admin.customers.show', $cairo->customer))->assertOk()->assertSee('القاهرة');
    }

    public function test_csv_export_follows_the_filters(): void
    {
        $this->fakeCairoLookup();
        $user = User::factory()->create(['full_name' => 'Cairo Customer']);
        $this->report($user, self::CAIRO)->assertOk();
        User::factory()->create(['full_name' => 'Other Customer']);

        $csv = $this->actingAs($this->admin())->get(route('admin.audience.export', ['country' => 'EG']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Cairo Customer', $csv);
        $this->assertStringContainsString('30.0444', $csv);
        $this->assertStringNotContainsString('Other Customer', $csv);
    }

    public function test_audience_page_follows_admin_roles(): void
    {
        $this->actingAs($this->admin('support'))->get(route('admin.audience.index'))->assertOk();
        $this->actingAs($this->admin('finance'))->get(route('admin.audience.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.audience.index'))->assertForbidden();
    }
}
