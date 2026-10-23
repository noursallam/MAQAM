<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    public function create(): View
    {
        return view('admin.admins.role-form', [
            'role' => new AdminRole(['modules' => ['dashboard']]),
            'labels' => AdminAccess::labels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        AdminRole::create([
            'key' => $this->uniqueKey($data['name']),
            'name' => $data['name'],
            'modules' => $data['modules'],
            'is_system' => false,
        ]);

        return redirect()->route('admin.admins.index')->with('success', __('admin.team.role_created'));
    }

    public function edit(AdminRole $adminRole): View
    {
        return view('admin.admins.role-form', [
            'role' => $adminRole,
            'labels' => AdminAccess::labels(),
        ]);
    }

    public function update(Request $request, AdminRole $adminRole): RedirectResponse
    {
        $data = $this->validated($request, $adminRole);

        $adminRole->update([
            'name' => $data['name'],
            // The super admin keeps everything, whatever is ticked
            'modules' => $adminRole->isSuperAdmin() ? AdminAccess::MODULES : $data['modules'],
        ]);

        return redirect()->route('admin.admins.index')->with('success', __('admin.team.role_saved'));
    }

    public function destroy(AdminRole $adminRole): RedirectResponse
    {
        if ($adminRole->is_system) {
            return back()->with('error', __('admin.team.role_system'));
        }

        if ($adminRole->admins()->exists()) {
            return back()->with('error', __('admin.team.role_in_use'));
        }

        $adminRole->delete();

        return redirect()->route('admin.admins.index')->with('success', __('admin.team.role_deleted'));
    }

    /**
     * @return array{name: string, modules: list<string>}
     */
    private function validated(Request $request, ?AdminRole $role = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('admin_roles', 'name')->ignore($role?->id)],
            'modules' => [$role?->isSuperAdmin() ? 'nullable' : 'required', 'array', 'min:1'],
            'modules.*' => [Rule::in(AdminAccess::MODULES)],
        ]);

        $data['modules'] = array_values(array_intersect(AdminAccess::MODULES, $data['modules'] ?? []));

        return $data;
    }

    /**
     * A stable key from the name, e.g. "مدير المخزن" -> "role-3f9a2c".
     */
    private function uniqueKey(string $name): string
    {
        $base = Str::slug($name) ?: 'role';

        do {
            $key = Str::limit($base, 40, '').'-'.Str::lower(Str::random(6));
        } while (AdminRole::where('key', $key)->exists());

        return $key;
    }
}
