<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AppText;
use App\Models\CategoryPrize;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MerchantProfileAndAppTextsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        Admin::create(['user_id' => $admin->id, 'role' => 'super_admin']);

        return $admin;
    }

    /**
     * A merchant whose code two customers scanned three times between them.
     */
    private function merchantWithScans(): Merchant
    {
        $owner = User::factory()->create(['full_name' => 'صاحب المحل', 'phone_number' => '01055555555', 'role' => 'customer', 'is_active' => true]);
        $merchant = Merchant::create([
            'user_id' => $owner->id, 'business_name' => 'معرض النور', 'merchant_code' => 'M-NOUR1234',
            'is_approved' => true, 'approved_at' => now(),
        ]);
        $prize = CategoryPrize::create([
            'name_en' => 'Gold pack', 'name_ar' => 'عبوة ذهبية', 'category_type' => 'points',
            'points_value' => 20, 'background_color' => '#D4AF37', 'is_active' => true,
        ]);

        foreach ([['01012345678', 'أحمد', 2], ['01198765432', 'منى', 1]] as [$phone, $name, $times]) {
            $user = User::factory()->create(['full_name' => $name, 'phone_number' => $phone, 'role' => 'customer', 'is_active' => true]);
            $customer = Customer::create(['user_id' => $user->id, 'points_balance' => 0, 'total_points_earned' => 0, 'total_points_spent' => 0]);

            for ($i = 0; $i < $times; $i++) {
                $code = QrCode::create([
                    'serial_code' => 'SN-'.$phone.'-'.$i, 'category_id' => $prize->id, 'points_awarded' => 20,
                    'status' => 'used', 'used_at' => now(), 'used_by_customer_id' => $customer->id,
                ]);
                QrScan::create([
                    'qr_code_id' => $code->id, 'customer_id' => $customer->id, 'merchant_id' => $merchant->id,
                    'points_awarded_customer' => 20, 'points_awarded_merchant' => 5, 'scanned_at' => now()->subMinutes(10 - $i),
                ]);
            }
        }

        return $merchant;
    }

    public function test_a_merchant_sees_who_scanned_with_their_code(): void
    {
        $merchant = $this->merchantWithScans();
        Sanctum::actingAs($merchant->user);

        $this->getJson('/api/v1/merchant')->assertOk()
            ->assertJsonPath('data.owner_name', 'صاحب المحل')
            ->assertJsonPath('data.stats.total_scans', 3)
            ->assertJsonPath('data.stats.total_points', 15)
            ->assertJsonPath('data.stats.points_this_month', 15)
            ->assertJsonPath('data.stats.unique_customers', 2)
            ->assertJsonCount(3, 'data.recent_scans')
            ->assertJsonPath('data.recent_scans.0.prize', 'عبوة ذهبية')
            ->assertJsonPath('data.recent_scans.0.points', 5);

        // The full number stays private
        $phones = collect($this->getJson('/api/v1/merchant/scans?per_page=2')->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(2, 'data')
            ->json('data'))->pluck('customer_phone');
        $this->assertTrue($phones->every(fn ($phone) => str_contains($phone, '****')));
    }

    public function test_someone_who_is_not_a_merchant_has_no_scans_list(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'customer', 'is_active' => true]));

        $this->getJson('/api/v1/merchant/scans')->assertNotFound();
    }

    public function test_the_dashboard_shows_a_merchants_whole_profile(): void
    {
        $merchant = $this->merchantWithScans();

        $this->actingAs($this->admin())->get(route('admin.merchants.show', $merchant))->assertOk()
            ->assertSee('معرض النور')
            ->assertSee('M-NOUR1234')
            ->assertSee('01055555555')
            ->assertSee('أحمد')
            ->assertSee('منى')
            ->assertSee('SN-01012345678-0');
    }

    public function test_app_texts_changed_in_the_dashboard_reach_the_app(): void
    {
        $this->getJson('/api/v1/app/texts')->assertOk()->assertJsonPath('data.texts.ar', []);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.app-texts.index', ['group' => 'merchant']))->assertOk()->assertSee('merchantShare');

        $this->actingAs($admin)->from(route('admin.app-texts.index'))->put(route('admin.app-texts.update'), ['texts' => [
            'accountHelp' => ['ar' => 'اتصل بنا', 'en' => ''],
            'navAccount' => ['ar' => '', 'en' => 'Profile'],
        ]])->assertRedirect()->assertSessionHasNoErrors();

        $this->getJson('/api/v1/app/texts')->assertOk()
            ->assertJsonPath('data.texts.ar.accountHelp', 'اتصل بنا')
            ->assertJsonPath('data.texts.en.navAccount', 'Profile')
            ->assertJsonMissingPath('data.texts.en.accountHelp');

        // Emptying both brings back the app's own wording
        $this->actingAs($admin)->put(route('admin.app-texts.update'), ['texts' => ['accountHelp' => ['ar' => '', 'en' => '']]]);
        $this->assertNull(AppText::where('key', 'accountHelp')->first());
        $this->getJson('/api/v1/app/texts')->assertJsonMissingPath('data.texts.ar.accountHelp');
    }

    public function test_a_text_must_keep_its_placeholders(): void
    {
        $key = collect(AppText::defaults())->search(fn ($text) => ! $text['plural'] && $text['placeholders']);

        $this->actingAs($this->admin())->from(route('admin.app-texts.index'))
            ->put(route('admin.app-texts.update'), ['texts' => [$key => ['ar' => 'نص بدون متغيرات', 'en' => '']]])
            ->assertSessionHasErrors("texts.{$key}.ar");

        $this->assertNull(AppText::where('key', $key)->first());
    }
}
