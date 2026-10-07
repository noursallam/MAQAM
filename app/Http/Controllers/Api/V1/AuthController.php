<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\DeviceToken;
use App\Models\Rank;
use App\Models\User;
use App\Services\Store\CartService;
use App\Services\WhatsApp\WhatsAppOtpService;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class AuthController extends ApiController
{
    public function __construct(
        protected WhatsAppOtpService $whatsAppOtp
    ) {}

    /**
     * Step 1: get a challenge code the customer must send to our WhatsApp number.
     */
    public function start(Request $request): JsonResponse
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);

        $validated = $request->validate([
            'phone' => ['required', 'regex:/^01[0125]\d{8}$/'],
            'full_name' => ['nullable', 'string', 'max:255'],
        ]);

        $businessNumber = $this->whatsAppOtp->businessNumber();

        if (! $businessNumber) {
            throw new ApiException('WHATSAPP_UNAVAILABLE', __('store.auth.wa_unavailable'), 503);
        }

        $challenge = $this->whatsAppOtp->start($validated['phone'], [
            'purpose' => 'login',
            'full_name' => $validated['full_name'] ?? null,
        ]);

        return $this->ok([
            'challenge_id' => $challenge['id'],
            'code' => $challenge['code'],
            'whatsapp_number' => $businessNumber,
            'whatsapp_url' => 'https://wa.me/'.$businessNumber.'?text='.rawurlencode($challenge['code']),
            'expires_in_seconds' => WhatsAppOtpService::CHALLENGE_MINUTES * 60,
            'poll_interval_seconds' => 4,
        ], __('api.auth.challenge_created'));
    }

    /**
     * Step 2 (poll): has the customer's message arrived? If so the OTP has been sent to them.
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate(['challenge_id' => ['required', 'string', 'max:100']]);

        if (! $this->whatsAppOtp->find($validated['challenge_id'])) {
            throw new ApiException('CHALLENGE_EXPIRED', __('store.auth.wa_expired'), 410);
        }

        try {
            $sent = $this->whatsAppOtp->sendOtpIfChallengeReceived($validated['challenge_id']);
        } catch (Throwable $e) {
            report($e);

            throw new ApiException('WHATSAPP_UNAVAILABLE', __('store.auth.wa_unavailable'), 503);
        }

        return $this->ok([
            'otp_sent' => $sent,
            'otp_expires_in_seconds' => $sent ? WhatsAppOtpService::OTP_MINUTES * 60 : null,
        ]);
    }

    /**
     * Step 3: exchange the OTP for an access token (the account is created on first sign-in).
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'string', 'max:100'],
            'otp' => ['required', 'digits:6'],
            'device_name' => ['required', 'string', 'max:100'],
            'device_token' => ['nullable', 'string', 'max:512'],
            'platform' => ['nullable', Rule::in(['android', 'ios'])],
        ]);

        $pending = $this->whatsAppOtp->find($validated['challenge_id']);

        // A challenge started for something else (e.g. changing a phone number) cannot sign anyone in
        if (! $pending || ($pending['meta']['purpose'] ?? 'login') !== 'login') {
            throw new ApiException('CHALLENGE_EXPIRED', __('store.auth.wa_expired'), 410);
        }

        $challenge = $this->whatsAppOtp->verify($validated['challenge_id'], $validated['otp']);

        if (! $challenge) {
            throw new ApiException('INVALID_OTP', __('store.auth.wa_wrong_otp'), 422);
        }

        $user = DB::transaction(fn () => $this->resolveUser($challenge));

        if ($user->role === 'admin') {
            throw new ApiException('FORBIDDEN', __('api.forbidden'), 403);
        }

        if (! $user->is_active) {
            throw new ApiException('ACCOUNT_FROZEN_FRAUD', __('api.account_frozen'), 403);
        }

        $user->forceFill([
            'phone_verified_at' => $user->phone_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        if (! empty($validated['device_token'])) {
            DeviceToken::register($user, $validated['device_token'], $validated['platform'] ?? null);
        }

        // One live token per device
        $user->tokens()->where('name', $validated['device_name'])->delete();
        $token = $user->createToken($validated['device_name']);

        return $this->ok([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String()
                ?? (config('sanctum.expiration') ? now()->addMinutes(config('sanctum.expiration'))->toIso8601String() : null),
            'is_new_user' => $user->wasRecentlyCreated,
            'user' => ApiPresenter::user($user),
            'customer' => ApiPresenter::wallet($this->customerOf($user)),
        ], __('api.auth.signed_in'));
    }

    public function logout(Request $request): JsonResponse
    {
        $validated = $request->validate(['device_token' => ['nullable', 'string', 'max:512']]);

        // Stop pushing to this device once it is signed out
        if (! empty($validated['device_token'])) {
            DeviceToken::where('user_id', $request->user()->id)->where('token', $validated['device_token'])->delete();
        }

        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, __('api.auth.signed_out'));
    }

    public function logoutAll(Request $request): JsonResponse
    {
        DeviceToken::where('user_id', $request->user()->id)->delete();
        $request->user()->tokens()->delete();

        return $this->ok(null, __('api.auth.signed_out'));
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->ok([
            'user' => ApiPresenter::user($user),
            'customer' => ApiPresenter::wallet($this->customer($request)),
            'merchant' => $user->merchant ? ApiPresenter::merchant($user->merchant) : null,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'preferred_language' => ['sometimes', 'required', Rule::in(['ar', 'en'])],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
        ]);

        // Only these fields are ever writable from the app: never role, phone or balances
        $user->fill(collect($validated)->only(['full_name', 'preferred_language'])->all());
        if (array_key_exists('email', $validated) && $validated['email']) {
            $user->email = $validated['email'];
        }
        $user->save();

        if (array_key_exists('date_of_birth', $validated)) {
            $this->customer($request)->update(['date_of_birth' => $validated['date_of_birth']]);
        }

        return $this->profile($request);
    }

    public function deviceToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', Rule::in(['android', 'ios'])],
        ]);

        DeviceToken::register($request->user(), $validated['device_token'], $validated['platform'] ?? null);

        return $this->ok(null, __('api.saved'));
    }

    private function resolveUser(array $challenge): User
    {
        $user = User::where('phone_number', $challenge['phone'])->lockForUpdate()->first();

        if (! $user) {
            $user = User::create([
                'full_name' => $challenge['meta']['full_name'] ?: __('api.auth.default_name'),
                'phone_number' => $challenge['phone'],
                'email' => $challenge['phone'].'@customer.maqam-eg.com',
                'password' => Hash::make(Str::random(40)),
                'role' => 'customer',
                'is_active' => true,
                'preferred_language' => app()->getLocale(),
            ]);
        }

        $this->customerOf($user);

        return $user;
    }

    private function customerOf(User $user): Customer
    {
        return $user->customer()->firstOrCreate([], [
            'rank_id' => Rank::where('is_active', true)->orderBy('min_points')->value('id'),
            'points_balance' => 0,
            'total_points_earned' => 0,
            'total_points_spent' => 0,
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return str_starts_with($digits, '20') ? '0'.substr($digits, 2) : $digits;
    }
}
