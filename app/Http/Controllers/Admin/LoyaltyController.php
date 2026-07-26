<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\PointsTransaction;
use App\Models\Product;
use App\Models\Rank;
use App\Models\SystemSetting;
use App\Models\WheelPrize;
use App\Models\WheelSpin;
use App\Services\WheelSpinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class LoyaltyController extends Controller
{
    public function transactions(Request $request): View
    {
        $transactions = PointsTransaction::with(['customer.user', 'merchant'])
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->latest('transaction_date')
            ->paginate(25)
            ->withQueryString();

        return view('admin.loyalty.transactions', compact('transactions'));
    }

    public function spins(Request $request): View
    {
        $spins = WheelSpin::with(['customer.user', 'rank', 'prize'])
            ->latest('spun_at')
            ->paginate(25);

        $total = WheelSpin::count();
        $wins = WheelSpin::where('is_win', true)->count();
        $wheelEnabled = (SystemSetting::where('key', 'wheel_enabled')->value('value') ?? '1') === '1';
        $ranks = Rank::where('is_active', true)->orderBy('min_points')->get();

        $prizes = WheelPrize::with(['coupon', 'product'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $activeWeight = (int) $prizes->where('is_active', true)->sum('weight');

        return view('admin.loyalty.spins', [
            'spins' => $spins,
            'winRate' => $total ? round(($wins / $total) * 100, 1) : 0,
            'wheelEnabled' => $wheelEnabled,
            'ranks' => $ranks,
            'total' => $total,
            'wins' => $wins,
            'prizes' => $prizes,
            'activeWeight' => $activeWeight,
            'products' => Product::where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'sku']),
            'coupons' => Coupon::where('is_active', true)->orderBy('code')->get(['id', 'code', 'type', 'value']),
            'customers' => Customer::with(['user', 'rank'])->orderByDesc('points_balance')->limit(100)->get(),
            'editing' => null,
        ]);
    }

    public function simulateSpin(Request $request, WheelSpinService $wheel): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
        ]);

        $customer = Customer::with(['rank', 'user'])->findOrFail($data['customer_id']);

        try {
            $result = $wheel->spin($customer);
            $spin = $result['spin'];
            $prize = $result['prize'];
            $customer = $customer->fresh(['user', 'rank']);

            $payload = [
                'ok' => true,
                'win' => (bool) $spin->is_win,
                'customer' => $customer->user?->full_name,
                'customer_id' => $customer->id,
                'prize_id' => $prize?->id,
                'prize' => $spin->is_win
                    ? ($prize?->label_ar ?? ($spin->prize_type.' '.$spin->prize_value))
                    : null,
                'type' => $spin->prize_type,
                'value' => $spin->prize_value,
                'cost' => $spin->points_cost,
                'points_won' => $spin->points_won,
                'balance' => $customer->points_balance,
                'rank' => $customer->rank?->name_ar,
                'message' => $spin->is_win
                    ? __('admin.wheel.sim_win', [
                        'prize' => $prize?->label_ar ?? ($spin->prize_type.' '.$spin->prize_value),
                    ])
                    : __('admin.wheel.sim_loss'),
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($payload);
            }

            return back()
                ->with('success', $payload['message'])
                ->with('spin_result', $payload);
        } catch (RuntimeException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function toggleWheel(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('enabled');
        SystemSetting::updateOrCreate(
            ['key' => 'wheel_enabled'],
            ['value' => $enabled ? '1' : '0', 'group' => 'wheel', 'description' => 'Enable lucky wheel', 'is_public' => true]
        );

        return back()->with('success', $enabled ? __('admin.wheel.enabled') : __('admin.wheel.disabled'));
    }

    public function storePrize(Request $request): RedirectResponse
    {
        $data = $this->validatePrize($request);
        $data['awarded_count'] = 0;
        $data['sort_order'] = $data['sort_order'] ?? ((int) WheelPrize::max('sort_order') + 1);

        WheelPrize::create($data);

        return back()->with('success', __('admin.wheel.prize_saved'));
    }

    public function updatePrize(Request $request, WheelPrize $prize): RedirectResponse
    {
        $data = $this->validatePrize($request);
        unset($data['awarded_count']);

        $prize->update($data);

        return back()->with('success', __('admin.wheel.prize_saved'));
    }

    public function destroyPrize(WheelPrize $prize): RedirectResponse
    {
        $prize->delete();

        return back()->with('success', __('admin.wheel.prize_deleted'));
    }

    public function togglePrize(WheelPrize $prize): RedirectResponse
    {
        $prize->update(['is_active' => ! $prize->is_active]);

        return back()->with('success', __('admin.wheel.prize_saved'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePrize(Request $request): array
    {
        $type = $request->input('type');

        $rules = [
            'type' => ['required', Rule::in(['points', 'coupon', 'product', 'discount'])],
            'label_ar' => ['required', 'string', 'max:120'],
            'label_en' => ['required', 'string', 'max:120'],
            'weight' => ['required', 'integer', 'min:1', 'max:10000'],
            'stock_limit' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'points_amount' => ['nullable', 'integer', 'min:1'],
            'coupon_id' => ['nullable', 'exists:coupons,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'discount_type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($type === 'points') {
            $rules['points_amount'] = ['required', 'integer', 'min:1'];
        } elseif ($type === 'coupon') {
            $rules['coupon_id'] = ['required', 'exists:coupons,id'];
        } elseif ($type === 'product') {
            $rules['product_id'] = ['required', 'exists:products,id'];
        } elseif ($type === 'discount') {
            $rules['discount_type'] = ['required', Rule::in(['percentage', 'fixed'])];
            $rules['discount_value'] = ['required', 'numeric', 'min:0.01'];
        }

        $data = $request->validate($rules);

        return [
            'type' => $data['type'],
            'label_ar' => $data['label_ar'],
            'label_en' => $data['label_en'],
            'weight' => (int) $data['weight'],
            'points_amount' => $type === 'points' ? (int) $data['points_amount'] : null,
            'coupon_id' => $type === 'coupon' ? (int) $data['coupon_id'] : null,
            'product_id' => $type === 'product' ? (int) $data['product_id'] : null,
            'discount_type' => $type === 'discount' ? $data['discount_type'] : null,
            'discount_value' => $type === 'discount' ? $data['discount_value'] : null,
            'stock_limit' => isset($data['stock_limit']) ? (int) $data['stock_limit'] : null,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
