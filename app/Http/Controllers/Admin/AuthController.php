<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password'], 'role' => 'admin', 'is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid admin credentials.'])->onlyInput('email');
        }

        $user = Auth::user();
        if (! $user->admin) {
            Auth::logout();

            return back()->withErrors(['email' => 'No admin profile linked to this account.']);
        }

        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
