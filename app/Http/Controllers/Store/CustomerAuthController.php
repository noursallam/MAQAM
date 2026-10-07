<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Rank;
use App\Models\User;
use App\Services\Store\CartService;
use App\Services\WhatsApp\WhatsAppOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CustomerAuthController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected WhatsAppOtpService $whatsAppOtp
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

        // No password: the customer proves the number by messaging us on WhatsApp first
        if (empty($validated['password'])) {
            if (! $this->whatsAppOtp->businessNumber()) {
                throw ValidationException::withMessages([
                    'phone' => __('store.auth.wa_unavailable'),
                ]);
            }

            $challenge = $this->whatsAppOtp->start($user->phone_number);
            session(['whatsapp_otp_id' => $challenge['id']]);

            return redirect()->route('store.login.whatsapp');
        }

        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('store.auth.wrong_password'),
            ]);
        }

        return $this->completeLogin($user);
    }

    public function showWhatsApp(): View|RedirectResponse
    {
        $challenge = $this->whatsAppOtp->find(session('whatsapp_otp_id'));
        $businessNumber = $this->whatsAppOtp->businessNumber();

        if (! $challenge || ! $businessNumber) {
            return redirect()->route('store.login')->withErrors(['phone' => __('store.auth.wa_expired')]);
        }

        return view('store.auth.whatsapp', [
            'code' => $challenge['code'],
            'businessNumber' => $businessNumber,
            'otpSent' => $challenge['otp_hash'] !== null,
        ]);
    }

    public function checkWhatsApp(): JsonResponse
    {
        $id = session('whatsapp_otp_id');

        if (! $this->whatsAppOtp->find($id)) {
            return response()->json(['expired' => true]);
        }

        try {
            return response()->json(['sent' => $this->whatsAppOtp->sendOtpIfChallengeReceived($id)]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['sent' => false, 'message' => __('store.auth.wa_unavailable')], 502);
        }
    }

    public function verifyWhatsApp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $id = session('whatsapp_otp_id');

        if (! $this->whatsAppOtp->find($id)) {
            return redirect()->route('store.login')->withErrors(['phone' => __('store.auth.wa_expired')]);
        }

        $challenge = $this->whatsAppOtp->verify($id, $validated['otp']);
        $user = $challenge ? User::where('phone_number', $challenge['phone'])->first() : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'otp' => __('store.auth.wa_wrong_otp'),
            ]);
        }

        $user->forceFill(['phone_verified_at' => $user->phone_verified_at ?? now()])->save();

        return $this->completeLogin($user);
    }

    private function completeLogin(User $user): RedirectResponse
    {
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
