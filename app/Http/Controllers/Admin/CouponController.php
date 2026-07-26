<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        return view('admin.coupons.index', [
            'coupons' => Coupon::latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.coupons.form', ['coupon' => new Coupon([
            'type' => 'percentage',
            'scope' => 'all',
            'valid_from' => now(),
            'valid_to' => now()->addMonth(),
            'is_active' => true,
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Coupon::create($this->validated($request));

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.form', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request, $coupon));

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }

    protected function validated(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code,'.($coupon?->id ?? 'NULL')],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'scope' => ['required', 'in:all,category,product,merchant'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['required', 'date', 'after:valid_from'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;

        return $data;
    }
}
