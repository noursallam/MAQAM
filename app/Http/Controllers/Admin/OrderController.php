<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Store\OrderNotifier;
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

        if (! $this->canMove($order->status, $data['status'])) {
            return back()->with('error', __('admin.orders.invalid_transition'));
        }

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

        $statusChanged = $order->status !== $data['status'];
        $order->update($updates);

        if ($statusChanged) {
            app(OrderNotifier::class)->statusChanged($order);
        }

        return back()->with('success', __('admin.orders.update_status').': '.__('admin.orders.'.$data['status']));
    }

    /**
     * Orders only move forward through new → processing → shipped → delivered. Cancelling or refunding
     * is allowed from any of those, and a cancelled or refunded order is closed for good.
     */
    public static function canMove(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        if (in_array($from, ['cancelled', 'refunded'], true)) {
            return false;
        }

        if (in_array($to, ['cancelled', 'refunded'], true)) {
            return $to === 'refunded' || $from !== 'delivered';
        }

        $flow = ['new', 'processing', 'shipped', 'delivered'];

        return array_search($to, $flow, true) > array_search($from, $flow, true);
    }
}
