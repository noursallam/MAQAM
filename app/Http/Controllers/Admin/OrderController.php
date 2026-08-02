<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $viewMode = $request->get('view', 'kanban');

        if ($viewMode === 'table') {
            $orders = Order::with('user')
                ->when($request->status, fn ($q, $status) => $q->where('status', $status))
                ->when($request->q, fn ($q, $term) => $q->where('order_number', 'like', "%{$term}%"))
                ->latest()
                ->paginate(20)
                ->withQueryString();

            return view('admin.orders.index', [
                'viewMode' => 'table',
                'orders' => $orders,
                'columns' => [],
            ]);
        }

        $statuses = ['new', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        $columns = [];
        foreach ($statuses as $status) {
            $columns[$status] = Order::with('user')
                ->where('status', $status)
                ->latest()
                ->take(30)
                ->get();
        }

        return view('admin.orders.index', [
            'viewMode' => 'kanban',
            'orders' => null,
            'columns' => $columns,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['user.customer', 'items.product', 'shippingAddress', 'payments', 'coupon', 'reward.product']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,processing,shipped,delivered,cancelled,refunded'],
            'cancellation_reason' => ['nullable', 'string'],
        ]);

        $updates = ['status' => $data['status']];

        if ($data['status'] === 'shipped') {
            $updates['shipped_at'] = now();
        }
        if ($data['status'] === 'delivered') {
            $updates['delivered_at'] = now();
        }
        if (in_array($data['status'], ['cancelled', 'refunded'], true)) {
            $updates['cancelled_at'] = now();
            $updates['cancellation_reason'] = $data['cancellation_reason'] ?? null;
        }

        $order->update($updates);

        return back()->with('success', __('admin.orders.update_status').': '.__('admin.orders.'.$data['status']));
    }
}
