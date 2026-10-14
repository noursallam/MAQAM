<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Rank;
use App\Models\User;
use App\Models\WheelPrize;
use App\Services\RankService;
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
    public function __construct(
        protected WheelSpinService $wheel,
        protected RankService $ranks,
    ) {}

    public function show(Request $request): View
    {
        $customer = $this->customer($request->user());
        $cost = (int) ($customer?->rank?->wheel_cost_points ?? 0);

        return view('store.spin', [
            'slices' => $this->slices(),
            'enabled' => $this->wheel->isEnabled(),
            'hasCustomer' => $customer !== null,
            'isStaff' => $request->user()->role === 'admin',
            // Still false only when no active rank exists at all
            'hasRank' => $customer?->rank !== null,
            'cost' => $cost,
            'balance' => (int) ($customer?->points_balance ?? 0),
        ]);
    }

    public function spin(Request $request): JsonResponse
    {
        $customer = $this->customer($request->user());

        if (! $customer) {
            return response()->json(['message' => __('store.spin.customers_only')], 422);
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
     * The signed-in customer's loyalty profile, created with the starting rank when it's missing
     * (accounts made by the admin or older sign-ups). Staff accounts don't get one.
     */
    private function customer(User $user): ?Customer
    {
        if ($user->role === 'admin') {
            return null;
        }

        $customer = $user->customer()->firstOrCreate([], [
            'rank_id' => Rank::where('is_active', true)->orderBy('min_points')->value('id'),
            'points_balance' => 0,
            'total_points_earned' => 0,
            'total_points_spent' => 0,
        ]);

        if (! $customer->rank_id) {
            // Picks the rank their lifetime points already qualify for
            $this->ranks->promoteIfEarned($customer);
        }

        return $customer->loadMissing('rank');
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
