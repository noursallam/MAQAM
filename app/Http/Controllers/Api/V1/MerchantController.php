<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\Merchant;
use App\Services\MediaService;
use App\Support\ApiPresenter;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $merchant = $this->merchant($request);

        $stats = $merchant->stats();
        $stats['last_scan_at'] = $stats['last_scan_at'] ? Carbon::parse($stats['last_scan_at'])->toIso8601String() : null;

        return $this->ok(ApiPresenter::merchant($merchant) + [
            'owner_name' => $merchant->user?->full_name,
            'created_at' => $merchant->created_at?->toIso8601String(),
            'stats' => $stats,
            'recent_scans' => $this->scanQuery($merchant)->take(10)->get()->map(ApiPresenter::merchantScan(...))->values(),
        ]);
    }

    /**
     * Every scan credited to the merchant's code, newest first.
     */
    public function scans(Request $request): JsonResponse
    {
        $page = $this->scanQuery($this->merchant($request))->paginate($this->perPage($request));

        return $this->paginated($page, ApiPresenter::merchantScan(...));
    }

    private function merchant(Request $request): Merchant
    {
        return $request->user()->merchant()->first()
            ?? throw new ApiException('NOT_FOUND', __('api.not_found'), 404);
    }

    private function scanQuery(Merchant $merchant): HasMany
    {
        return $merchant->qrScans()->with(['customer.user', 'qrCode.categoryPrize'])->latest('scanned_at')->latest('id');
    }
}
