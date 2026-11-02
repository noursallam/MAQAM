<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Services\MediaService;
use App\Services\RankService;
use App\Support\ApiPresenter;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

    /**
     * Technicians bound to this merchant.
     */
    public function technicians(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);

        $technicians = User::where('bound_merchant_id', $merchant->id)
            ->with('customer')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'phone_number' => $u->phone_number,
                'governorate' => $u->governorate,
                'area' => $u->area,
                'points_balance' => (int) ($u->customer?->points_balance ?? 0),
                'total_earned' => (int) ($u->customer?->total_points_earned ?? 0),
                'joined_at' => $u->created_at?->toIso8601String(),
            ]);

        return $this->ok($technicians);
    }

    public function technician(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant($request);
        $user = User::where('id', $id)->where('bound_merchant_id', $merchant->id)->firstOrFail();
        $customer = $user->customer;

        return $this->ok([
            'id' => $user->id,
            'full_name' => $user->full_name,
            'phone_number' => $user->phone_number,
            'governorate' => $user->governorate,
            'area' => $user->area,
            'points_balance' => (int) ($customer?->points_balance ?? 0),
            'total_earned' => (int) ($customer?->total_points_earned ?? 0),
            'total_spent' => (int) ($customer?->total_points_spent ?? 0),
            'scans_count' => $customer ? $customer->qrScans()->where('merchant_id', $merchant->id)->count() : 0,
            'joined_at' => $user->created_at?->toIso8601String(),
        ]);
    }

    public function technicianScans(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant($request);
        $user = User::where('id', $id)->where('bound_merchant_id', $merchant->id)->firstOrFail();
        $customer = $user->customer;

        if (! $customer) {
            return $this->ok([]);
        }

        $page = $customer->qrScans()
            ->where('merchant_id', $merchant->id)
            ->with('qrCode.categoryPrize')
            ->latest('scanned_at')
            ->paginate($this->perPage($request));

        return $this->paginated($page, fn ($scan) => [
            'id' => $scan->id,
            'points' => (int) $scan->points_awarded_customer,
            'merchant_points' => (int) $scan->points_awarded_merchant,
            'scanned_at' => $scan->scanned_at?->toIso8601String(),
        ]);
    }

    /**
     * Merchant deducts points from a bound technician (e.g. for returns).
     */
    public function deductPoints(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant($request);
        $user = User::where('id', $id)->where('bound_merchant_id', $merchant->id)->firstOrFail();
        $customer = $user->customer;

        if (! $customer) {
            throw new ApiException('NOT_FOUND', __('api.not_found'), 404);
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($validated['amount'] > $customer->points_balance) {
            throw new ApiException('INSUFFICIENT_POINTS', __('api.merchant.insufficient_points'), 422);
        }

        DB::transaction(function () use ($customer, $merchant, $validated) {
            $newBalance = $customer->points_balance - $validated['amount'];
            $customer->update([
                'points_balance' => $newBalance,
                'total_points_spent' => $customer->total_points_spent + $validated['amount'],
            ]);
            app(RankService::class)->promoteIfEarned($customer);

            PointsTransaction::create([
                'customer_id' => $customer->id,
                'merchant_id' => $merchant->id,
                'type' => 'spend',
                'amount' => -$validated['amount'],
                'description' => $validated['reason'],
                'balance_after' => $newBalance,
                'transaction_date' => now(),
            ]);
        });

        return $this->ok([
            'new_balance' => (int) $customer->fresh()->points_balance,
        ], __('api.merchant.points_deducted'));
    }

    /**
     * All point transactions related to this merchant's technicians.
     */
    public function transactions(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);

        $technicianCustomerIds = Customer::whereHas('user', fn ($q) => $q->where('bound_merchant_id', $merchant->id))
            ->pluck('id');

        $page = PointsTransaction::whereIn('customer_id', $technicianCustomerIds)
            ->latest('transaction_date')
            ->paginate($this->perPage($request));

        return $this->paginated($page, fn (PointsTransaction $tx) => [
            'id' => $tx->id,
            'type' => $tx->type,
            'amount' => (int) $tx->amount,
            'balance_after' => $tx->balance_after !== null ? (int) $tx->balance_after : null,
            'description' => $tx->description,
            'customer_name' => $tx->customer?->user?->full_name,
            'date' => ($tx->transaction_date ?? $tx->created_at)?->toIso8601String(),
        ]);
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
