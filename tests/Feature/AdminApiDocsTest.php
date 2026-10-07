<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class AdminApiDocsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(string $role): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        Admin::create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_docs_and_spec_are_not_public(): void
    {
        $this->get('/admin/api-docs')->assertRedirect('/admin/login');
        $this->get('/admin/api-docs/openapi.yaml')->assertRedirect('/admin/login');

        // A signed-in customer is not an admin
        $this->actingAs(User::factory()->create())->get('/admin/api-docs/openapi.yaml')->assertForbidden();

        // Nothing is served from the public folder
        $this->assertFileDoesNotExist(public_path('openapi.yaml'));
    }

    public function test_only_roles_with_the_docs_permission_can_read_them(): void
    {
        $this->actingAs($this->admin('super_admin'))->get('/admin/api-docs')->assertOk()->assertSee('swagger-ui');
        $this->actingAs($this->admin('support'))->get('/admin/api-docs')->assertForbidden();
        $this->actingAs($this->admin('support'))->get('/admin/api-docs/openapi.yaml')->assertForbidden();
    }

    public function test_developer_account_sees_docs_and_nothing_else(): void
    {
        $developer = $this->admin('developer');

        $this->actingAs($developer)->get('/admin')->assertRedirect(route('admin.api-docs.index'));
        $this->actingAs($developer)->get('/admin/api-docs')->assertOk();
        $this->actingAs($developer)->get('/admin/api-docs/openapi.yaml')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        foreach (['/admin/customers', '/admin/orders', '/admin/settings', '/admin/search', '/admin/whatsapp', '/admin/admins'] as $url) {
            $this->actingAs($developer)->get($url)->assertForbidden();
        }
    }

    public function test_create_developer_command_makes_a_docs_only_login(): void
    {
        $this->artisan('admin:create-developer', ['email' => 'dev@example.com'])->assertExitCode(0);

        $user = User::where('email', 'dev@example.com')->firstOrFail();
        $this->assertSame('developer', $user->admin->role);
        $this->assertTrue($user->isAdmin());
    }

    /**
     * The documentation must describe exactly the endpoints that exist.
     */
    public function test_spec_documents_every_api_route_and_nothing_else(): void
    {
        $spec = Yaml::parseFile(resource_path('api-docs/openapi.yaml'));

        $documented = [];
        foreach ($spec['paths'] as $path => $operations) {
            foreach (array_keys($operations) as $method) {
                $documented[] = strtoupper($method).' '.$path;
            }
        }

        $actual = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $actual[] = $method.' /'.substr($route->uri(), strlen('api/v1/'));
            }
        }

        sort($documented);
        sort($actual);

        $this->assertSame($actual, $documented);
    }

    public function test_spec_references_resolve(): void
    {
        $raw = file_get_contents(resource_path('api-docs/openapi.yaml'));
        $spec = Yaml::parse($raw);

        preg_match_all("~#/components/(\w+)/(\w+)~", $raw, $matches, PREG_SET_ORDER);
        $this->assertNotEmpty($matches);

        foreach ($matches as [$ref, $group, $name]) {
            $this->assertArrayHasKey($name, $spec['components'][$group] ?? [], "Broken reference {$ref}");
        }
    }
}
