<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PointsTransaction;
use App\Models\Rank;
use App\Models\User;
use App\Models\WheelSpin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::with(['user', 'rank'])
            ->when($request->q, function ($q, $term) {
                $q->whereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$term}%")
                    ->orWhere('phone_number', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"));
            })
            ->when($request->frozen === '1', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('is_active', false)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(Customer $customer): View
    {
        $customer->load(['user', 'rank']);
        $ranks = Rank::where('is_active', true)->orderBy('min_points')->get();
        $nextRank = $ranks->first(fn ($r) => $r->min_points > ($customer->points_balance ?? 0));

        $progress = 0;
        if ($customer->rank && $nextRank) {
            $span = max(1, $nextRank->min_points - $customer->rank->min_points);
            $progress = min(100, max(0, (($customer->points_balance - $customer->rank->min_points) / $span) * 100));
        } elseif ($customer->rank && ! $nextRank) {
            $progress = 100;
        }

        return view('admin.customers.show', [
            'customer' => $customer,
            'ranks' => $ranks,
            'nextRank' => $nextRank,
            'progress' => $progress,
            'scans' => $customer->qrScans()->with(['merchant', 'qrCode'])->latest('scanned_at')->take(15)->get(),
            'ledger' => $customer->pointsTransactions()->latest('transaction_date')->take(15)->get(),
            'orders' => Order::where('user_id', $customer->user_id)->latest()->take(10)->get(),
            'spins' => WheelSpin::where('customer_id', $customer->id)->with(['rank', 'reward'])->latest('spun_at')->take(10)->get(),
            'rewards' => $customer->rewards()->with(['coupon', 'product'])->latest()->take(15)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.form', [
            'customer' => new Customer,
            'ranks' => Rank::where('is_active', true)->orderBy('min_points')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users,phone_number'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'rank_id' => ['nullable', 'exists:ranks,id'],
            'points_balance' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isActive = $request->boolean('is_active', true);

        DB::transaction(function () use ($data, $isActive) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'phone_number' => $data['phone_number'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role' => 'customer',
                'is_active' => $isActive,
            ]);

            Customer::create([
                'user_id' => $user->id,
                'rank_id' => $data['rank_id'] ?? Rank::orderBy('min_points')->value('id'),
                'points_balance' => $data['points_balance'] ?? 0,
            ]);
        });

        return redirect()->route('admin.customers.index')->with('success', __('admin.success'));
    }

    public function edit(Customer $customer): View
    {
        $customer->load('user');

        return view('admin.customers.form', [
            'customer' => $customer,
            'ranks' => Rank::where('is_active', true)->orderBy('min_points')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users,phone_number,'.$customer->user_id],
            'email' => ['nullable', 'email', 'unique:users,email,'.$customer->user_id],
            'password' => ['nullable', 'string', 'min:8'],
            'rank_id' => ['nullable', 'exists:ranks,id'],
            'points_balance' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $customer->user->update([
            'full_name' => $data['full_name'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            ...($data['password'] ?? null ? ['password' => $data['password']] : []),
        ]);

        $customer->update([
            'rank_id' => $data['rank_id'] ?? $customer->rank_id,
            'points_balance' => $data['points_balance'] ?? $customer->points_balance,
        ]);

        return redirect()->route('admin.customers.show', $customer)->with('success', __('admin.success'));
    }

    public function adjustPoints(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($customer, $data, $request) {
            $newBalance = max(0, $customer->points_balance + $data['amount']);
            $customer->update([
                'points_balance' => $newBalance,
                'total_points_earned' => $data['amount'] > 0
                    ? $customer->total_points_earned + $data['amount']
                    : $customer->total_points_earned,
                'total_points_spent' => $data['amount'] < 0
                    ? $customer->total_points_spent + abs($data['amount'])
                    : $customer->total_points_spent,
            ]);

            PointsTransaction::create([
                'customer_id' => $customer->id,
                'type' => 'adjust',
                'amount' => $data['amount'],
                'description' => $data['reason'],
                'balance_after' => $newBalance,
                'admin_id' => $request->user()->admin?->id,
                'transaction_date' => now(),
            ]);
        });

        return back()->with('success', __('admin.customers.adjust_points'));
    }

    public function freeze(Customer $customer): RedirectResponse
    {
        $customer->user->update(['is_active' => false]);

        return back()->with('success', __('admin.customers.freeze'));
    }

    public function unfreeze(Customer $customer): RedirectResponse
    {
        $customer->user->update(['is_active' => true]);

        return back()->with('success', __('admin.customers.unfreeze'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->user->delete();

        return redirect()->route('admin.customers.index')->with('success', __('admin.success'));
    }
}
