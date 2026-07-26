<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MerchantController extends Controller
{
    public function index(Request $request): View
    {
        $merchants = Merchant::with(['user', 'approvedBy'])
            ->when($request->status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($request->status === 'approved', fn ($q) => $q->where('is_approved', true))
            ->when($request->q, function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('business_name', 'like', "%{$term}%")
                        ->orWhere('merchant_code', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.merchants.index', compact('merchants'));
    }

    public function inbox(Request $request): View
    {
        $pending = Merchant::with('user')
            ->where('is_approved', false)
            ->latest()
            ->paginate(12, ['*'], 'pending_page');

        $approved = Merchant::with(['user', 'approvedBy'])
            ->where('is_approved', true)
            ->latest('approved_at')
            ->paginate(12, ['*'], 'approved_page');

        $selected = null;
        if ($request->filled('review')) {
            $selected = Merchant::with('user')->find($request->integer('review'));
        } elseif ($pending->count()) {
            $selected = $pending->first();
        }

        return view('admin.merchants.inbox', compact('pending', 'approved', 'selected'));
    }

    public function create(): View
    {
        return view('admin.merchants.form', ['merchant' => new Merchant]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users,phone_number'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'business_name' => ['required', 'string', 'max:255'],
            'business_address' => ['nullable', 'string'],
            'merchant_code' => ['nullable', 'string', 'max:50', 'unique:merchants,merchant_code'],
            'is_approved' => ['nullable', 'boolean'],
        ]);

        $isApproved = $request->boolean('is_approved');

        DB::transaction(function () use ($data, $isApproved, $request) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'phone_number' => $data['phone_number'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role' => 'merchant',
                'is_active' => true,
            ]);

            Merchant::create([
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'business_address' => $data['business_address'] ?? null,
                'merchant_code' => $data['merchant_code'] ?: 'M-'.Str::upper(Str::random(8)),
                'is_approved' => $isApproved,
                'approved_at' => $isApproved ? now() : null,
                'approved_by' => $isApproved ? $request->user()->admin?->id : null,
            ]);
        });

        return redirect()->route('admin.merchants.index')->with('success', __('admin.success'));
    }

    public function edit(Merchant $merchant): View
    {
        $merchant->load('user');

        return view('admin.merchants.form', compact('merchant'));
    }

    public function update(Request $request, Merchant $merchant): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users,phone_number,'.$merchant->user_id],
            'email' => ['nullable', 'email', 'unique:users,email,'.$merchant->user_id],
            'password' => ['nullable', 'string', 'min:8'],
            'business_name' => ['required', 'string', 'max:255'],
            'business_address' => ['nullable', 'string'],
            'merchant_code' => ['required', 'string', 'max:50', 'unique:merchants,merchant_code,'.$merchant->id],
            'is_approved' => ['nullable', 'boolean'],
        ]);

        $isApproved = $request->boolean('is_approved');

        $merchant->user->update([
            'full_name' => $data['full_name'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'] ?? null,
            ...($data['password'] ?? null ? ['password' => $data['password']] : []),
        ]);

        $merchant->update([
            'business_name' => $data['business_name'],
            'business_address' => $data['business_address'] ?? null,
            'merchant_code' => $data['merchant_code'],
            'is_approved' => $isApproved,
            'approved_at' => $isApproved ? ($merchant->approved_at ?? now()) : null,
            'approved_by' => $isApproved ? ($merchant->approved_by ?? $request->user()->admin?->id) : null,
        ]);

        return redirect()->route('admin.merchants.index')->with('success', __('admin.success'));
    }

    public function approve(Merchant $merchant, Request $request): RedirectResponse
    {
        $merchant->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $request->user()->admin?->id,
        ]);

        return back()->with('success', __('admin.merchants.approve').' — '.$merchant->merchant_code);
    }

    public function reject(Merchant $merchant, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $merchant->update([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
            'business_address' => trim(($merchant->business_address ?? '')."\n[REJECTED] ".$data['reason']),
        ]);

        if ($merchant->user) {
            $merchant->user->update(['is_active' => false]);
        }

        return redirect()->route('admin.merchants.inbox')->with('success', __('admin.merchants.reject'));
    }

    public function destroy(Merchant $merchant): RedirectResponse
    {
        $merchant->user->delete();

        return redirect()->route('admin.merchants.index')->with('success', __('admin.success'));
    }
}
