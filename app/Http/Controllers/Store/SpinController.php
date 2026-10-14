<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\WheelPrize;
use App\Services\WheelSpinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * TEMPORARY / EXPERIMENTAL (تجريبي مؤقت): quick lucky-wheel page for the website.
 * Strings are inline and not translated yet, there are no tests yet.
 * Replace with a proper page (lang keys, tests, design pass) before treating it as final.
 */
class SpinController extends Controller
{
    public function __construct(protected WheelSpinService $wheel) {}

    public function show(Request $request): View
    {
        $customer = $request->user()->customer?->loadMissing('rank');
        $cost = (int) ($customer?->rank?->wheel_cost_points ?? 0);

        return view('store.spin', [
            'slices' => $this->slices(),
            'enabled' => $this->wheel->isEnabled(),
            'hasCustomer' => $customer !== null,
            'cost' => $cost,
            'balance' => (int) ($customer?->points_balance ?? 0),
        ]);
    }

    public function spin(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return response()->json(['message' => __('admin.wheel.no_rank')], 422);
        }

        // Read the slices first so slice_index matches what the page is showing
        $slices = $this->slices();

        try {
            $result = $this->wheel->spin($customer);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $spin = $result['spin'];
        $index = $spin->is_win ? collect($slices)->search(fn ($s) => $s['id'] === $spin->wheel_prize_id) : false;

        return response()->json([
            'is_win' => (bool) $spin->is_win,
            'prize_label' => $spin->is_win ? $result['prize']?->displayLabel() : null,
            'points_won' => (int) $spin->points_won,
            'balance' => (int) $customer->fresh()->points_balance,
            'slice_index' => $index === false ? count($slices) - 1 : $index,
        ]);
    }

    /**
     * Same order as the mobile API: active prizes, then the "no prize" slice last.
     *
     * @return list<array{id: ?int, label: string}>
     */
    private function slices(): array
    {
        $slices = WheelPrize::query()->available()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (WheelPrize $prize) => ['id' => $prize->id, 'label' => $prize->displayLabel()])
            ->all();

        $slices[] = ['id' => null, 'label' => __('api.wheel.no_prize')];

        return $slices;
    }
}
