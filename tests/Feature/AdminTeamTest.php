<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTeamTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(string $role = 'super_admin'): User
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        Admin::create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_existing_roles_keep_their_access_after_the_move_to_the_database(): void
    {
        $this->assertSame(5, AdminRole::where('is_system', true)->count());

        $this->actingAs($this->admin('support'))->get('/admin/orders')->assertOk();
        $this->actingAs($this->admin('support'))->get('/admin/products')->assertForbidden();
    }

    public function test_a_new_role_opens_only_the_sections_ticked(): void
    {
        $this->actingAs($this->admin())->post('/admin/admin-roles', [
            'name' => 'مسؤول المخزن',
            'modules' => ['dashboard', 'commerce'],
        ])->assertRedirect('/admin/admins');

        $role = AdminRole::where('name', 'مسؤول المخزن')->firstOrFail();
        $this->assertFalse($role->is_system);
        $this->assertSame(['dashboard', 'commerce'], $role->modules);

        $stock = $this->admin($role->key);
        $this->actingAs($stock)->get('/admin/products')->assertOk();
        $this->actingAs($stock)->get('/admin/orders')->assertForbidden();
        $this->actingAs($stock)->get('/admin/admins')->assertForbidden();
    }

    public function test_the_super_admin_role_always_keeps_everything(): void
    {
        $super = AdminRole::where('key', 'super_admin')->firstOrFail();

        $this->actingAs($this->admin())->put("/admin/admin-roles/{$super->id}", [
            'name' => 'المدير',
            'modules' => ['dashboard'],
        ])->assertRedirect();

        $this->assertSame('المدير', $super->fresh()->name);
        $this->actingAs($this->admin())->get('/admin/admins')->assertOk();
    }

    public function test_a_super_admin_adds_a_team_member_with_a_role_who_can_then_sign_in(): void
    {
        $this->actingAs($this->admin())->post('/admin/admins', [
            'full_name' => 'سارة محمد',
            'email' => 'sara@maqam-eg.com',
            'password' => 'a-long-test-pass',
            'role' => 'finance',
        ])->assertRedirect('/admin/admins');

        $sara = User::where('email', 'sara@maqam-eg.com')->firstOrFail();
        $this->assertSame('admin', $sara->role);
        $this->assertSame('finance', $sara->admin->role);
        $this->assertTrue(Hash::check('a-long-test-pass', $sara->password));
        // A made-up number, so this login can never go through WhatsApp sign-in
        $this->assertStringStartsWith('admin-', $sara->phone_number);

        auth()->logout();
        $this->post('/admin/login', ['email' => 'sara@maqam-eg.com', 'password' => 'a-long-test-pass'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($sara);
    }

    public function test_changing_a_members_role_or_disabling_them(): void
    {
        $member = $this->admin('support');

        $this->actingAs($this->admin())->put("/admin/admins/{$member->admin->id}", [
            'full_name' => $member->full_name,
            'email' => $member->email,
            'role' => 'content_manager',
            'is_active' => '0',
        ])->assertRedirect('/admin/admins');

        $member->refresh();
        $this->assertSame('content_manager', $member->admin->role);
        $this->assertFalse((bool) $member->is_active);
    }

    public function test_you_cannot_demote_or_disable_yourself(): void
    {
        $me = $this->admin();
        $this->admin();

        $this->actingAs($me)->put("/admin/admins/{$me->admin->id}", [
            'full_name' => $me->full_name,
            'email' => $me->email,
            'role' => 'support',
            'is_active' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertSame('super_admin', $me->admin->fresh()->role);
    }

    public function test_the_last_active_super_admin_cannot_be_removed(): void
    {
        $other = $this->admin();
        $me = $this->admin('admins_only');
        AdminRole::create(['key' => 'admins_only', 'name' => 'إدارة الفريق', 'modules' => ['dashboard', 'admins']]);

        $this->actingAs($me)->put("/admin/admins/{$other->admin->id}", [
            'full_name' => $other->full_name,
            'email' => $other->email,
            'role' => 'support',
            'is_active' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertSame('super_admin', $other->admin->fresh()->role);
    }

    public function test_a_role_in_use_or_built_in_cannot_be_deleted(): void
    {
        $super = $this->admin();
        $custom = AdminRole::create(['key' => 'stock', 'name' => 'مخزن', 'modules' => ['commerce']]);
        $this->admin('stock');

        $this->actingAs($super)->delete("/admin/admin-roles/{$custom->id}")->assertSessionHas('error');
        $this->actingAs($super)->delete('/admin/admin-roles/'.AdminRole::where('key', 'finance')->value('id'))->assertSessionHas('error');
        $this->assertSame(2, AdminRole::whereIn('key', ['stock', 'finance'])->count());

        $empty = AdminRole::create(['key' => 'empty', 'name' => 'فارغ', 'modules' => ['dashboard']]);
        $this->actingAs($super)->delete("/admin/admin-roles/{$empty->id}")->assertRedirect('/admin/admins');
        $this->assertNull($empty->fresh());
    }

    public function test_the_team_pages_render(): void
    {
        $super = $this->admin();
        $role = AdminRole::where('key', 'support')->first();

        $this->actingAs($super)->get('/admin/admins')->assertOk()->assertSee(__('admin.team.new_role'));
        $this->actingAs($super)->get('/admin/admins/create')->assertOk();
        $this->actingAs($super)->get("/admin/admins/{$super->admin->id}/edit")->assertOk()->assertSee(__('admin.team.not_yourself'));
        $this->actingAs($super)->get('/admin/admin-roles/create')->assertOk();
        $this->actingAs($super)->get("/admin/admin-roles/{$role->id}/edit")->assertOk();
    }
}
