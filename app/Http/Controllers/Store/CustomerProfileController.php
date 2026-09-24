<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PointsTransaction;
use App\Models\Rank;
use App\Models\ShippingAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerProfileController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('store.login')->with('info', __('store.auth.login_required'));
        }

        $customer = $user->customer;
        $rank = $customer?->rank;

        // Calculate progress to next rank
        $nextRank = null;
        $progressPercent = 100;
        $pointsLeft = 0;

        if ($rank) {
            $nextRank = Rank::where('min_points', '>', $rank->min_points)
                ->orderBy('min_points', 'asc')
                ->first();

            if ($nextRank && $nextRank->min_points > 0) {
                $currentPoints = $customer->points_balance;
                $pointsLeft = max(0, $nextRank->min_points - $currentPoints);
                $progressPercent = min(100, max(5, (int) round(($currentPoints / $nextRank->min_points) * 100)));
            }
        }

        // Recent orders
        $orders = Order::with(['items.product', 'shippingAddress', 'payments'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(15)
            ->get();

        // Points transaction logs
        $transactions = $customer ? PointsTransaction::where('customer_id', $customer->id)->latest()->take(10)->get() : collect();

        // Saved shipping addresses
        $addresses = ShippingAddress::where('user_id', $user->id)->latest()->get();

        return view('store.profile', compact(
            'user',
            'customer',
            'rank',
            'nextRank',
            'pointsLeft',
            'progressPercent',
            'orders',
            'transactions',
            'addresses'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('store.login');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'] ?? $user->email,
        ]);

        if (! empty($validated['city']) || ! empty($validated['address'])) {
            ShippingAddress::updateOrCreate(
                ['user_id' => $user->id, 'is_default' => true],
                [
                    'recipient_name' => $validated['full_name'],
                    'phone' => $user->phone_number,
                    'city' => $validated['city'] ?? 'Cairo',
                    'governorate' => $validated['city'] ?? 'Cairo',
                    'address_line1' => $validated['address'] ?? 'Primary Address',
                    'country' => 'Egypt',
                ]
            );
        }

        return back()->with('success', __('store.profile.saved'));
    }
}
