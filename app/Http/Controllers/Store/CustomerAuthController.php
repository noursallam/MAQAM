<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Rank;
use App\Models\User;
use App\Services\Store\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('store.profile');
        }

        return view('store.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        $cleanPhone = preg_replace('/\D/', '', $validated['phone']);
        if (str_starts_with($cleanPhone, '20')) {
            $cleanPhone = '0'.substr($cleanPhone, 2);
        }

        $user = User::where('phone_number', $cleanPhone)
            ->orWhere('email', $validated['phone'])
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => __('store.auth.user_not_found'),
            ]);
        }

        // If password is provided, check it; if omitted and phone matches, allow OTP direct verification
        if (! empty($validated['password'])) {
            if (! Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => __('store.auth.wrong_password'),
                ]);
            }
        }

        Auth::login($user, true);

        // Migrate guest cart
        $this->cartService->transferGuestCartToUser($user->id);

        return redirect()->intended(route('store.profile'))->with('success', __('store.auth.welcome_back', ['name' => $user->full_name]));
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('store.profile');
        }

        return view('store.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $cleanPhone = preg_replace('/\D/', '', $validated['phone']);
        if (str_starts_with($cleanPhone, '20')) {
            $cleanPhone = '0'.substr($cleanPhone, 2);
        }

        if (User::where('phone_number', $cleanPhone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => __('store.auth.phone_taken'),
            ]);
        }

        $user = User::create([
            'full_name' => $validated['full_name'],
            'phone_number' => $cleanPhone,
            'email' => $validated['email'] ?? ($cleanPhone.'@customer.maqam-eg.com'),
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'is_active' => true,
            'preferred_language' => app()->getLocale(),
        ]);

        $silverRank = Rank::where('name_en', 'Silver')->first() ?? Rank::first();
        Customer::create([
            'user_id' => $user->id,
            'rank_id' => $silverRank?->id,
            'points_balance' => 0,
            'total_points_earned' => 0,
            'total_points_spent' => 0,
        ]);

        Auth::login($user, true);

        // Transfer any guest cart
        $this->cartService->transferGuestCartToUser($user->id);

        return redirect()->intended(route('store.profile'))
            ->with('success', __('store.auth.registration_successful'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('store.home')->with('success', __('store.auth.logged_out'));
    }
}
