<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MerchantChangeRequestController extends Controller
{
    public function index(): View
    {
        $requests = MerchantChangeRequest::with(['user', 'currentMerchant', 'requestedMerchant', 'reviewer'])
            ->latest()
            ->paginate(20);

        return view('admin.merchant-change-requests.index', compact('requests'));
    }

    public function approve(MerchantChangeRequest $merchantChangeRequest): RedirectResponse
    {
        if ($merchantChangeRequest->status !== 'pending') {
            return back()->with('error', __('admin.merchant_change.already_reviewed'));
        }

        $merchantChangeRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->user()->admin?->id,
            'reviewed_at' => now(),
        ]);

        $merchantChangeRequest->user->update([
            'bound_merchant_id' => $merchantChangeRequest->requested_merchant_id,
        ]);

        return back()->with('success', __('admin.merchant_change.approved'));
    }

    public function reject(MerchantChangeRequest $merchantChangeRequest): RedirectResponse
    {
        if ($merchantChangeRequest->status !== 'pending') {
            return back()->with('error', __('admin.merchant_change.already_reviewed'));
        }

        $merchantChangeRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->user()->admin?->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', __('admin.merchant_change.rejected'));
    }
}
