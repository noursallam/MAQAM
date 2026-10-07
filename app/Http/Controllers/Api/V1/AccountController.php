<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\AppNotification;
use App\Models\Cart;
use App\Models\DeviceToken;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountController extends ApiController
{
    public function __construct(
        protected WhatsAppOtpService $whatsAppOtp
    ) {}

    /**
     * Permanently delete the account (required by the App Store and Google Play).
     *
     * Personal data is erased and the phone number is released. Order and points
     * history stay as anonymous business records.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'confirm' => ['required', 'in:DELETE'],
        ]);

        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            DeviceToken::where('user_id', $user->id)->delete();
            AppNotification::where('user_id', $user->id)->delete();
            Cart::where('user_id', $user->id)->get()->each(function (Cart $cart) {
                $cart->items()->delete();
                $cart->delete();
            });

            // Addresses stay attached to past orders but disappear everywhere else
            ShippingAddress::where('user_id', $user->id)->delete();

            $user->merchant()->update(['is_approved' => false, 'business_name' => 'Deleted merchant', 'business_address' => null, 'logo_url' => null]);
            $user->customer()->update(['date_of_birth' => null]);

            $user->forceFill([
                'full_name' => 'Deleted user',
                'phone_number' => 'deleted-'.$user->id.'-'.Str::lower(Str::random(6)),
                'email' => 'deleted-'.$user->id.'@deleted.maqam-eg.com',
                'password' => Hash::make(Str::random(40)),
                'role' => 'deleted',
                'is_active' => false,
                'device_token' => null,
                'face_id_enabled' => false,
                'face_id_token' => null,
                'otp_code' => null,
                'phone_verified_at' => null,
            ])->save();
        });

        return $this->ok(null, __('api.account.deleted'));
    }

    /**
     * Change phone, step 1: get a code to send from the NEW number on WhatsApp.
     */
    public function startPhoneChange(Request $request): JsonResponse
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);

        $validated = $request->validate([
            'phone' => ['required', 'regex:/^01[0125]\d{8}$/'],
        ]);

        $user = $request->user();

        if ($validated['phone'] === $user->phone_number || User::where('phone_number', $validated['phone'])->exists()) {
            throw new ApiException('PHONE_TAKEN', __('api.account.phone_taken'), 422);
        }

        $businessNumber = $this->whatsAppOtp->businessNumber();

        if (! $businessNumber) {
            throw new ApiException('WHATSAPP_UNAVAILABLE', __('store.auth.wa_unavailable'), 503);
        }

        $challenge = $this->whatsAppOtp->start($validated['phone'], [
            'purpose' => 'change_phone',
            'user_id' => $user->id,
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
     * Change phone, step 3: confirm with the code received on the new number.
     */
    public function verifyPhoneChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'string', 'max:100'],
            'otp' => ['required', 'digits:6'],
        ]);

        $user = $request->user();
        $pending = $this->whatsAppOtp->find($validated['challenge_id']);

        // The challenge must be this user's own phone-change request
        if (! $pending || ($pending['meta']['purpose'] ?? null) !== 'change_phone' || ($pending['meta']['user_id'] ?? null) !== $user->id) {
            throw new ApiException('CHALLENGE_EXPIRED', __('store.auth.wa_expired'), 410);
        }

        $challenge = $this->whatsAppOtp->verify($validated['challenge_id'], $validated['otp']);

        if (! $challenge) {
            throw new ApiException('INVALID_OTP', __('store.auth.wa_wrong_otp'), 422);
        }

        DB::transaction(function () use ($user, $challenge) {
            if (User::where('phone_number', $challenge['phone'])->lockForUpdate()->exists()) {
                throw new ApiException('PHONE_TAKEN', __('api.account.phone_taken'), 422);
            }

            $placeholderEmail = $user->phone_number.'@customer.maqam-eg.com';

            $user->forceFill([
                'phone_number' => $challenge['phone'],
                'phone_verified_at' => now(),
                // Keep the auto-generated email in step with the number
                'email' => $user->email === $placeholderEmail ? $challenge['phone'].'@customer.maqam-eg.com' : $user->email,
            ])->save();
        });

        return $this->ok(['phone_number' => $user->phone_number], __('api.account.phone_changed'));
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return str_starts_with($digits, '20') ? '0'.substr($digits, 2) : $digits;
    }
}
