<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\LinkPageItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_shows_visible_links_and_counts_clicks(): void
    {
        LinkPageItem::query()->update(['is_active' => false]);
        $insta = LinkPageItem::create(['platform' => 'instagram', 'url' => 'https://instagram.com/maqam', 'is_active' => true]);
        LinkPageItem::create(['platform' => 'android', 'url' => 'https://play.google.com/store/apps/details?id=maqam.egypt', 'is_active' => true]);
        LinkPageItem::create(['platform' => 'custom', 'label_ar' => 'الكتالوج', 'url' => 'https://example.com/hidden', 'is_active' => false]);

        $this->get('/links')->assertOk()
            ->assertSee('Google Play')
            ->assertSee(route('store.links.go', $insta))
            ->assertDontSee('الكتالوج');

        $this->get("/links/go/{$insta->id}")->assertRedirect('https://instagram.com/maqam');
        $this->assertSame(1, $insta->fresh()->clicks);
    }

    public function test_the_admin_adds_edits_and_removes_links(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Admin::create(['user_id' => $user->id, 'role' => 'super_admin']);
        $this->actingAs($user);

        $this->get('/admin/links')->assertOk();
        $this->post('/admin/links', ['platform' => 'tiktok', 'url' => 'https://tiktok.com/@maqam'])->assertSessionHas('success');
        $this->post('/admin/links', ['platform' => 'custom', 'url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');

        $tiktok = LinkPageItem::where('platform', 'tiktok')->firstOrFail();
        $this->put("/admin/links/{$tiktok->id}", ['platform' => 'tiktok', 'url' => 'https://tiktok.com/@maqam.eg', 'sort_order' => 9])
            ->assertSessionHas('success');
        $this->assertFalse($tiktok->fresh()->is_active);
        $this->assertSame('https://tiktok.com/@maqam.eg', $tiktok->fresh()->url);

        $this->delete("/admin/links/{$tiktok->id}")->assertSessionHas('success');
        $this->assertNull($tiktok->fresh());
    }
}
