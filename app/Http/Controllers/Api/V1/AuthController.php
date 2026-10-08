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
use Illuminate\Validation\Rules\Password;
use Throwable;

class AuthController extends ApiController
{
    /** Every app session. */
    private const ABILITY_APP = 'app';

    /** Sessions opened with a WhatsApp code, which may set a password without knowing the old one. */
    private const ABILITY_SET_PASSWORD = 'set-password';

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

        $this->ensureMaySignIn($user);

        $user->forceFill(['phone_verified_at' => $user->phone_verified_at ?? now()])->save();

        // Having just proved the number, this session may also choose a new password
        return $this->issueSession($user, $validated, [self::ABILITY_APP, self::ABILITY_SET_PASSWORD]);
    }

    /**
     * How a phone number signs in: with its password, or over WhatsApp when it has none
     * (which is also how a new number registers).
     */
    public function methods(Request $request): JsonResponse
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);
        $validated = $request->validate(['phone' => ['required', 'regex:/^01[0125]\d{8}$/']]);

        return $this->ok([
            'has_password' => User::where('phone_number', $validated['phone'])
                ->where('role', '!=', 'admin')
                ->whereNotNull('password_set_at')
                ->exists(),
        ]);
    }

    /**
     * Sign in with phone number and password.
     */
    public function login(Request $request): JsonResponse
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);

        $validated = $request->validate([
            'phone' => ['required', 'regex:/^01[0125]\d{8}$/'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:100'],
            'device_token' => ['nullable', 'string', 'max:512'],
            'platform' => ['nullable', Rule::in(['android', 'ios'])],
        ]);

        $user = User::where('phone_number', $validated['phone'])->first();

        // One answer for "no such account" and "wrong password", so numbers cannot be probed
        if (! $user || $user->role === 'admin' || ! Hash::check($validated['password'], $user->password)) {
            throw new ApiException('INVALID_CREDENTIALS', __('api.auth.invalid_credentials'), 422);
        }

        $this->ensureMaySignIn($user);

        // Accounts registered on the website before this flag existed
        if ($user->password_set_at === null) {
            $user->forceFill(['password_set_at' => now()])->save();
        }
        $this->customerOf($user);

        return $this->issueSession($user, $validated, [self::ABILITY_APP]);
    }

    /**
     * First-time details after a WhatsApp sign-up: a name and a password of the customer's own.
     */
    public function completeProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureMaySetPassword($request);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        DB::transaction(function () use ($request, $user, $validated) {
            $user->full_name = $validated['full_name'];
            if (! empty($validated['email'])) {
                $user->email = $validated['email'];
            }
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'password_set_at' => now(),
            ])->save();

            if (! empty($validated['date_of_birth'])) {
                $this->customer($request)->update(['date_of_birth' => $validated['date_of_birth']]);
            }
        });

        return $this->ok($this->profile($request)->getData(true)['data'], __('api.auth.profile_completed'));
    }

    /**
     * Change the password. A session opened with a WhatsApp code may do so without the old one,
     * which is how a forgotten password is replaced.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $verifiedByOtp = $user->currentAccessToken()->can(self::ABILITY_SET_PASSWORD);

        $validated = $request->validate([
            'current_password' => [$verifiedByOtp ? 'nullable' : 'required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! $verifiedByOtp && ! Hash::check($validated['current_password'], $user->password)) {
            throw new ApiException('WRONG_PASSWORD', __('api.auth.wrong_current_password'), 422);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_set_at' => now(),
        ])->save();

        // Whoever knew the old password is signed out everywhere else
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return $this->ok(null, __('api.auth.password_updated'));
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

    private function ensureMaySignIn(User $user): void
    {
        if ($user->role === 'admin') {
            throw new ApiException('FORBIDDEN', __('api.forbidden'), 403);
        }

        if (! $user->is_active) {
            throw new ApiException('ACCOUNT_FROZEN_FRAUD', __('api.account_frozen'), 403);
        }
    }

    private function ensureMaySetPassword(Request $request): void
    {
        if (! $request->user()->currentAccessToken()->can(self::ABILITY_SET_PASSWORD)) {
            throw new ApiException('OTP_REQUIRED', __('api.auth.verify_to_set_password'), 403);
        }
    }

    /**
     * Record the sign-in and hand the app its access token.
     *
     * @param  array{device_name: string, device_token?: ?string, platform?: ?string}  $device
     * @param  list<string>  $abilities
     */
    private function issueSession(User $user, array $device, array $abilities): JsonResponse
    {
        $user->forceFill(['last_login_at' => now()])->save();

        if (! empty($device['device_token'])) {
            DeviceToken::register($user, $device['device_token'], $device['platform'] ?? null);
        }

        // One live token per device
        $user->tokens()->where('name', $device['device_name'])->delete();
        $token = $user->createToken($device['device_name'], $abilities);

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
