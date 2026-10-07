<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\CustomerReward;
use App\Models\Rank;
use App\Models\WheelPrize;
use App\Services\WheelSpinService;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class LoyaltyController extends ApiController
{
    public function __construct(
        protected WheelSpinService $wheel
    ) {}

    public function wallet(Request $request): JsonResponse
    {
        return $this->ok(ApiPresenter::wallet($this->customer($request)));
    }

    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['earn', 'spend', 'refund', 'expire', 'adjust'])],
        ]);

        $page = $this->customer($request)->pointsTransactions()
            ->when($validated['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated($page, ApiPresenter::transaction(...));
    }

    public function ranks(): JsonResponse
    {
        return $this->ok(Rank::where('is_active', true)->orderBy('min_points')->get()->map(ApiPresenter::rank(...))->values());
    }

    public function rewards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['available', 'used', 'expired', 'revoked'])],
        ]);

        $page = $this->customer($request)->rewards()
            ->with('product')
            ->when(($validated['status'] ?? null) === 'available', fn ($q) => $q->available())
            ->when(in_array($validated['status'] ?? null, ['used', 'revoked'], true), fn ($q) => $q->where('status', $validated['status']))
            ->when(($validated['status'] ?? null) === 'expired', fn ($q) => $q->where(fn ($w) => $w
                ->where('status', CustomerReward::STATUS_EXPIRED)
                ->orWhere(fn ($e) => $e->where('status', CustomerReward::STATUS_AVAILABLE)->where('expires_at', '<=', now()))))
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated($page, ApiPresenter::reward(...));
    }

    /**
     * Wheel slices in display order. The last slice is always the "no prize" slice.
     */
    public function wheelConfig(Request $request): JsonResponse
    {
        $customer = $this->customer($request)->loadMissing('rank');
        $cost = (int) ($customer->rank?->wheel_cost_points ?? 0);
        $enabled = $this->wheel->isEnabled();

        return $this->ok([
            'is_enabled' => $enabled,
            'cost_points' => $cost,
            'customer_balance' => (int) $customer->points_balance,
            'can_spin' => $enabled && $customer->rank !== null && $customer->points_balance >= $cost,
            'slices' => $this->slices(),
        ]);
    }

    public function spin(Request $request): JsonResponse
    {
        $customer = $this->customer($request)->loadMissing('rank');

        if (! $this->wheel->isEnabled()) {
            throw new ApiException('WHEEL_DISABLED', __('admin.wheel.disabled'), 422);
        }

        if ($customer->rank && $customer->points_balance < (int) $customer->rank->wheel_cost_points) {
            throw new ApiException('INSUFFICIENT_POINTS', __('admin.wheel.insufficient_points'), 422);
        }

        // Read the slices first so slice_index matches what the app is showing
        $slices = $this->slices();

        try {
            $result = $this->wheel->spin($customer);
        } catch (RuntimeException $e) {
            throw new ApiException('WHEEL_UNAVAILABLE', $e->getMessage(), 422);
        }

        $spin = $result['spin'];
        $sliceIndex = $spin->is_win
            ? collect($slices)->search(fn ($slice) => $slice['id'] === $spin->wheel_prize_id)
            : false;

        return $this->ok([
            'spin' => [
                'id' => $spin->id,
                'is_win' => (bool) $spin->is_win,
                'prize_type' => $spin->prize_type,
                'prize_label' => $spin->is_win ? $result['prize']?->displayLabel() : null,
                'points_cost' => (int) $spin->points_cost,
                'points_won' => (int) $spin->points_won,
            ],
            'reward' => $spin->reward ? ApiPresenter::reward($spin->reward) : null,
            'new_points_balance' => (int) $customer->fresh()->points_balance,
            // Falls back to the "no prize" slice
            'slice_index' => $sliceIndex === false ? count($slices) - 1 : $sliceIndex,
        ], $spin->is_win ? __('api.wheel.won') : __('api.wheel.lost'));
    }

    /**
     * @return list<array{id: ?int, type: string, label: string, label_ar: string, label_en: string}>
     */
    private function slices(): array
    {
        $slices = WheelPrize::query()->available()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (WheelPrize $prize) => [
                'id' => $prize->id,
                'type' => $prize->type,
                'label' => $prize->displayLabel(),
                'label_ar' => $prize->label_ar,
                'label_en' => $prize->label_en,
            ])->all();

        $slices[] = [
            'id' => null,
            'type' => 'none',
            'label' => __('api.wheel.no_prize'),
            'label_ar' => __('api.wheel.no_prize', [], 'ar'),
            'label_en' => __('api.wheel.no_prize', [], 'en'),
        ];

        return $slices;
    }
}
