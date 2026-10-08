<?php

namespace App\Services\Store;

use App\Exceptions\ApiException;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PointsTransaction;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a user's cart into an order atomically: stock, wallet points, rewards and the
 * order rows either all change together or not at all. Cancelling reverses the same things.
 */
class OrderPlacementService
{
    // 10 loyalty points pay for 1 EGP
    public const POINTS_PER_EGP = 10;

    public function __construct(
        protected CartService $cartService,
        protected OrderNotifier $notifier,
    ) {}

    /**
     * @param  array{address_id?: ?int, full_name?: string, phone?: string, governorate?: string, city?: string, address?: string, notes?: ?string}  $shipping
     */
    public function place(User $user, Cart $cart, array $shipping, string $paymentMethod): Order
    {
        $order = DB::transaction(function () use ($user, $cart, $shipping, $paymentMethod) {
            $cart->load('items');

            if ($cart->items->isEmpty()) {
                throw new ApiException('CART_EMPTY', __('store.cart.empty_warning'), 422);
            }

            // Lock the products in a stable order so concurrent checkouts cannot oversell or deadlock
            $products = Product::whereIn('id', $cart->items->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cart->items as $item) {
                $product = $products->get($item->product_id);

                if (! $product || ! $product->is_active) {
                    throw new ApiException('PRODUCT_UNAVAILABLE', __('store.cart.product_unavailable'), 422);
                }

                if ($item->quantity > $product->stock_quantity) {
                    throw new ApiException('OUT_OF_STOCK', __('api.checkout.out_of_stock', [
                        'product' => $product->name_ar,
                        'available' => $product->stock_quantity,
                    ]), 422);
                }
            }

            $cart->load('items.product');
            $summary = $this->cartService->getSummary($cart);

            // Lock the reward so two orders cannot spend it at once
            $reward = $summary['reward']
                ? CustomerReward::with('product')->whereKey($summary['reward']->id)->lockForUpdate()->first()
                : null;

            if ($summary['reward'] && ! $reward?->isUsable()) {
                throw new ApiException('INVALID_COUPON', __('store.cart.invalid_coupon'), 422);
            }

            $customer = null;
            $pointsNeeded = 0;

            if ($paymentMethod === 'wallet') {
                $customer = Customer::where('user_id', $user->id)->lockForUpdate()->first();
                $pointsNeeded = (int) ceil($summary['total'] * self::POINTS_PER_EGP);

                if (! $customer || $customer->points_balance < $pointsNeeded) {
                    throw new ApiException('INSUFFICIENT_POINTS', __('store.checkout.insufficient_points', [
                        'needed' => $pointsNeeded,
                        'balance' => $customer?->points_balance ?? 0,
                    ]), 422);
                }
            }

            $address = $this->resolveAddress($user, $shipping);
            $paidByWallet = $paymentMethod === 'wallet';

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->orderNumber(),
                'status' => $paidByWallet ? 'processing' : 'new',
                'subtotal' => $summary['subtotal'],
                'tax' => 0,
                'discount' => $summary['discount'],
                'shipping_cost' => $summary['shipping'],
                'total_amount' => $summary['total'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paidByWallet ? 'paid' : 'pending',
                'shipping_address_id' => $address->id,
                'coupon_id' => $summary['coupon']?->id,
                'customer_reward_id' => $reward?->id,
                'coupon_code' => $summary['coupon_code'],
            ]);

            $summary['coupon']?->increment('used_count');
            $reward?->markUsed($order->id);

            foreach ($cart->items as $item) {
                $unitPrice = $item->product_option_id ? (float) $item->unit_price : (float) $products[$item->product_id]->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_option_id' => $item->product_option_id,
                    'option_label' => $item->option_label,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $item->quantity,
                ]);

                $products[$item->product_id]->decrement('stock_quantity', $item->quantity);
            }

            // A gift-product reward ships free with the order; its stock was reserved when it was won
            if ($reward?->type === CustomerReward::TYPE_PRODUCT && $reward->product_id) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $reward->product_id,
                    'option_label' => __('api.checkout.gift_item', [], 'ar'),
                    'quantity' => 1,
                    'unit_price' => 0,
                    'subtotal' => 0,
                ]);
            }

            if ($paidByWallet) {
                $customer->points_balance -= $pointsNeeded;
                $customer->total_points_spent += $pointsNeeded;
                $customer->save();

                PointsTransaction::create([
                    'customer_id' => $customer->id,
                    'type' => 'spend',
                    'amount' => -$pointsNeeded,
                    'balance_after' => $customer->points_balance,
                    'description' => "Order #{$order->order_number} payment",
                    'transaction_date' => now(),
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'transaction_id' => 'TX-INIT-'.$order->id.'-'.Str::random(8),
                'gateway' => $paymentMethod,
                'amount' => $order->total_amount,
                'status' => $paidByWallet ? 'success' : 'pending',
                'paid_at' => $paidByWallet ? now() : null,
            ]);

            // Online payments keep the cart until the gateway session exists
            if ($paymentMethod !== 'kashier') {
                $this->emptyCart($cart);
            }

            return $order;
        });

        // An online order is announced once its payment is confirmed, not before
        if ($paymentMethod !== 'kashier') {
            $this->notifier->placed($order);
        }

        return $order;
    }

    /**
     * Whether the customer may still cancel this order themselves.
     */
    public function isCancellable(Order $order): bool
    {
        // Money already taken by the card gateway needs a manual refund by staff
        if ($order->payment_method === 'kashier' && $order->payment_status === 'paid') {
            return false;
        }

        return in_array($order->status, ['new', 'processing'], true);
    }

    /**
     * Cancel an order and give back everything it took: stock, coupon use, reward and wallet points.
     */
    public function cancel(Order $order, string $reason, bool $notify = true): Order
    {
        $cancelledNow = false;

        $order = DB::transaction(function () use ($order, $reason, &$cancelledNow) {
            $order = Order::with('items')->whereKey($order->id)->lockForUpdate()->first();

            if ($order->status === 'cancelled') {
                return $order;
            }

            $cancelledNow = true;

            $giftProductId = null;

            if ($order->customer_reward_id) {
                $reward = CustomerReward::whereKey($order->customer_reward_id)->lockForUpdate()->first();

                if ($reward && $reward->status === CustomerReward::STATUS_USED) {
                    $reward->forceFill(['status' => CustomerReward::STATUS_AVAILABLE, 'used_at' => null, 'order_id' => null])->save();
                    $giftProductId = $reward->type === CustomerReward::TYPE_PRODUCT ? $reward->product_id : null;
                }
            }

            foreach ($order->items as $item) {
                // The gift line never took stock at checkout, so it gives none back
                if ($item->product_id === $giftProductId && (float) $item->unit_price === 0.0) {
                    continue;
                }

                Product::whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
            }

            if ($order->coupon_id) {
                $order->coupon()->where('used_count', '>', 0)->decrement('used_count');
            }

            $refunded = false;

            if ($order->payment_method === 'wallet' && $order->payment_status === 'paid') {
                $customer = Customer::where('user_id', $order->user_id)->lockForUpdate()->first();
                $points = (int) ceil((float) $order->total_amount * self::POINTS_PER_EGP);

                if ($customer && $points > 0) {
                    $customer->points_balance += $points;
                    $customer->total_points_spent = max(0, $customer->total_points_spent - $points);
                    $customer->save();

                    PointsTransaction::create([
                        'customer_id' => $customer->id,
                        'type' => 'refund',
                        'amount' => $points,
                        'balance_after' => $customer->points_balance,
                        'description' => "Order #{$order->order_number} cancelled",
                        'transaction_date' => now(),
                    ]);

                    $refunded = true;
                }
            }

            $order->payments()->update(['status' => $refunded ? 'refunded' : 'failed']);
            $order->update([
                'status' => 'cancelled',
                'payment_status' => $refunded ? 'refunded' : ($order->payment_status === 'paid' ? 'paid' : 'failed'),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $order;
        });

        if ($cancelledNow && $notify) {
            $this->notifier->statusChanged($order);
        }

        return $order;
    }

    public function emptyCart(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['coupon_code' => null, 'total' => 0]);
    }

    /**
     * Use a saved address, or store the typed one once (repeat orders reuse the same row).
     */
    private function resolveAddress(User $user, array $shipping): ShippingAddress
    {
        if (! empty($shipping['address_id'])) {
            $address = ShippingAddress::where('user_id', $user->id)->find($shipping['address_id']);

            if (! $address) {
                throw new ApiException('ADDRESS_NOT_FOUND', __('api.address.not_found'), 422);
            }

            return $address;
        }

        return ShippingAddress::firstOrCreate([
            'user_id' => $user->id,
            'recipient_name' => $shipping['full_name'],
            'phone' => $shipping['phone'],
            'governorate' => $shipping['governorate'],
            'city' => $shipping['city'],
            'address_line1' => $shipping['address'],
            'address_line2' => $shipping['notes'] ?? null,
        ], [
            'country' => 'Egypt',
            'is_default' => ! ShippingAddress::where('user_id', $user->id)->exists(),
        ]);
    }

    private function orderNumber(): string
    {
        do {
            $number = 'MQ-'.date('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
