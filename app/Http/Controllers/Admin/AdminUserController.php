<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.admins.index', [
            'admins' => Admin::with('user')->latest()->get(),
            'roles' => AdminRole::withCount('admins')->orderBy('id')->get(),
            'matrix' => AdminAccess::matrix(),
            'labels' => AdminAccess::labels(),
        ]);
    }

    public function create(): View
    {
        return view('admin.admins.form', [
            'admin' => new Admin(['role' => 'support']),
            'roles' => AdminRole::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(10)],
            'role' => ['required', Rule::exists('admin_roles', 'key')],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                // Not a real number, so this login can never be reached through WhatsApp sign-in
                'phone_number' => 'admin-'.Str::lower(Str::random(12)),
                'password' => Hash::make($data['password']),
                'role' => 'admin',
                'is_active' => true,
            ]);

            Admin::create(['user_id' => $user->id, 'role' => $data['role']]);
        });

        return redirect()->route('admin.admins.index')->with('success', __('admin.team.admin_created'));
    }

    public function edit(Admin $admin): View
    {
        return view('admin.admins.form', [
            'admin' => $admin->load('user'),
            'roles' => AdminRole::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->user_id)],
            'password' => ['nullable', 'string', Password::min(10)],
            'role' => ['required', Rule::exists('admin_roles', 'key')],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $active = $request->boolean('is_active');
        $isSelf = $admin->user_id === $request->user()->id;

        // You cannot take away your own access, nor leave the team without a super admin
        if ($isSelf && (! $active || $data['role'] !== $admin->role)) {
            throw ValidationException::withMessages(['role' => __('admin.team.not_yourself')]);
        }
        if ($admin->role === AdminRole::SUPER_ADMIN && ($data['role'] !== AdminRole::SUPER_ADMIN || ! $active) && $this->lastActiveSuperAdmin($admin)) {
            throw ValidationException::withMessages(['role' => __('admin.team.last_super_admin')]);
        }

        DB::transaction(function () use ($admin, $data, $active) {
            $admin->user->update(array_filter([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'is_active' => $active,
                'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null,
            ], fn ($value) => $value !== null));

            // Extra modules granted one by one would outlive a role change; the role decides now
            $admin->update(['role' => $data['role'], 'permissions' => null]);

            if (! $active) {
                $admin->user->tokens()->delete();
            }
        });

        return redirect()->route('admin.admins.index')->with('success', __('admin.team.admin_saved'));
    }

    private function lastActiveSuperAdmin(Admin $admin): bool
    {
        return ! Admin::where('role', AdminRole::SUPER_ADMIN)
            ->whereKeyNot($admin->id)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->exists();
    }
}
