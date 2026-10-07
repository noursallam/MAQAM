<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\Merchant;
use App\Models\QrScan;
use App\Services\MediaService;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantController extends ApiController
{
    /**
     * Ask to become a merchant. The profile stays inactive until an admin approves it.
     */
    public function apply(Request $request, MediaService $media): JsonResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->merchant()->exists()) {
            throw new ApiException('MERCHANT_ALREADY_EXISTS', __('api.merchant.already_applied'), 409);
        }

        do {
            $code = 'M-'.Str::upper(Str::random(8));
        } while (Merchant::where('merchant_code', $code)->exists());

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'business_name' => $validated['business_name'],
            'business_address' => $validated['business_address'] ?? null,
            'merchant_code' => $code,
            'is_approved' => false,
            'logo_url' => $request->hasFile('logo') ? $media->upload($request->file('logo'), 'merchants') : null,
        ]);

        return $this->ok(ApiPresenter::merchant($merchant), __('api.merchant.applied'), 201);
    }

    /**
     * Replace the merchant's logo (multipart upload).
     */
    public function logo(Request $request, MediaService $media): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $merchant = $request->user()->merchant()->first();

        if (! $merchant) {
            throw new ApiException('NOT_FOUND', __('api.not_found'), 404);
        }

        $path = $media->upload($request->file('logo'), 'merchants');

        if (! $path) {
            throw new ApiException('UPLOAD_FAILED', __('api.upload_failed'), 422);
        }

        $media->delete($merchant->logo_url);
        $merchant->update(['logo_url' => $path]);

        return $this->ok(ApiPresenter::merchant($merchant), __('api.saved'));
    }

    /**
     * The signed-in user's merchant profile and what their code has earned.
     */
    public function show(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant()->first();

        if (! $merchant) {
            throw new ApiException('NOT_FOUND', __('api.not_found'), 404);
        }

        $scans = QrScan::where('merchant_id', $merchant->id);

        return $this->ok(ApiPresenter::merchant($merchant) + [
            'stats' => [
                'total_scans' => (clone $scans)->count(),
                'total_points' => (int) (clone $scans)->sum('points_awarded_merchant'),
                'scans_this_month' => (clone $scans)->where('scanned_at', '>=', now()->startOfMonth())->count(),
                'unique_customers' => (clone $scans)->distinct()->count('customer_id'),
            ],
        ]);
    }
}
