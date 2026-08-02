<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerReward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RewardController extends Controller
{
    public function index(Request $request): View
    {
        $rewards = CustomerReward::query()
            ->with(['customer.user', 'coupon', 'product'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->source, fn ($q, $source) => $q->where('source', $source))
            ->when($request->q, function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', "%{$term}%")
                        ->orWhereHas('customer.user', fn ($u) => $u
                            ->where('full_name', 'like', "%{$term}%")
                            ->orWhere('phone_number', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.rewards.index', [
            'rewards' => $rewards,
            'stats' => [
                'available' => CustomerReward::where('status', CustomerReward::STATUS_AVAILABLE)->count(),
                'used' => CustomerReward::where('status', CustomerReward::STATUS_USED)->count(),
                'total' => CustomerReward::count(),
            ],
        ]);
    }

    public function revoke(CustomerReward $reward): RedirectResponse
    {
        if ($reward->status !== CustomerReward::STATUS_AVAILABLE) {
            return back()->with('error', __('admin.rewards.revoke_only_available'));
        }

        $reward->update([
            'status' => CustomerReward::STATUS_REVOKED,
            'used_at' => null,
        ]);

        return back()->with('success', __('admin.rewards.revoked'));
    }
}
