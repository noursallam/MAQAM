<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PointsTransaction;
use App\Models\Product;
use App\Models\Rank;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Services\Payment\KashierService;
use App\Services\Store\CartService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected KashierService $kashierService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if ($cart->items->isEmpty()) {
            return redirect()->route('store.cart')->with('info', __('store.cart.empty_warning'));
        }

        $summary = $this->cartService->getSummary($cart);
        $user = Auth::user();
        $addresses = $user ? ShippingAddress::where('user_id', $user->id)->get() : collect();
        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        $customer = $user?->customer;

        return view('store.checkout', compact('cart', 'summary', 'user', 'customer', 'addresses', 'defaultAddress'));
    }

    public function process(Request $request): RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if ($cart->items->isEmpty()) {
            return redirect()->route('store.cart')->with('info', __('store.cart.empty_warning'));
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'governorate' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cod,kashier,wallet'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $summary = $this->cartService->getSummary($cart);

        // Standardize Egyptian phone number (e.g. 01012345678)
        $cleanPhone = preg_replace('/\D/', '', $validated['phone']);
        if (str_starts_with($cleanPhone, '20')) {
            $cleanPhone = '0'.substr($cleanPhone, 2);
        }

        try {
            $order = DB::transaction(function () use ($validated, $cleanPhone, $cart, $summary) {
                // Ensure a User account exists for the order
                $user = Auth::user();

                if (! $user) {
                    $user = User::where('phone_number', $cleanPhone)->first();

                    if (! $user) {
                        $user = User::create([
                            'full_name' => $validated['full_name'],
                            'phone_number' => $cleanPhone,
                            'email' => $cleanPhone.'@customer.maqam-eg.com',
                            'password' => Hash::make(Str::random(16)),
                            'role' => 'customer',
                            'is_active' => true,
                            'preferred_language' => app()->getLocale(),
                        ]);

                        $silverRank = Rank::where('name_en', 'Silver')->first() ?? Rank::first();
                        Customer::create([
                            'user_id' => $user->id,
                            'rank_id' => $silverRank?->id,
                            'points_balance' => 0,
                            'total_points_earned' => 0,
                            'total_points_spent' => 0,
                        ]);
                    }
                }

                // Save or retrieve shipping address
                $shippingAddress = ShippingAddress::create([
                    'user_id' => $user->id,
                    'recipient_name' => $validated['full_name'],
                    'phone' => $cleanPhone,
                    'governorate' => $validated['governorate'],
                    'city' => $validated['city'],
                    'address_line1' => $validated['address'],
                    'address_line2' => $validated['notes'] ?? null,
                    'country' => 'Egypt',
                    'is_default' => ! ShippingAddress::where('user_id', $user->id)->exists(),
                ]);

                // Generate distinct order number
                $orderNumber = 'MQ-'.date('Ymd').'-'.strtoupper(Str::random(5));

                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => $orderNumber,
                    'status' => 'new',
                    'subtotal' => $summary['subtotal'],
                    'tax' => 0,
                    'discount' => $summary['discount'],
                    'shipping_cost' => $summary['shipping'],
                    'total_amount' => $summary['total'],
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'pending',
                    'shipping_address_id' => $shippingAddress->id,
                    'coupon_id' => $summary['coupon']?->id,
                    'coupon_code' => $summary['coupon_code'],
                ]);

                // Increment coupon used count if used
                if ($summary['coupon']) {
                    $summary['coupon']->increment('used_count');
                }

                // Create Order Items and decrease stock
                foreach ($cart->items as $cartItem) {
                    $product = $cartItem->product;
                    $unitPrice = (float) $cartItem->unit_price;
                    $itemSubtotal = $unitPrice * $cartItem->quantity;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $cartItem->product_id,
                        'product_option_id' => $cartItem->product_option_id,
                        'option_label' => $cartItem->option_label,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $itemSubtotal,
                    ]);

                    if ($product && $product->stock_quantity > 0) {
                        $product->decrement('stock_quantity', min($cartItem->quantity, $product->stock_quantity));
                    }
                }

                // Create Payment record
                Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => 'TX-INIT-'.$order->id.'-'.Str::random(8),
                    'gateway' => $validated['payment_method'],
                    'amount' => $order->total_amount,
                    'status' => 'pending',
                ]);

                return $order;
            });

            // Route based on payment method
            if ($validated['payment_method'] === 'cod') {
                $this->cartService->clear();

                return redirect()->route('store.order.confirmation', ['orderNumber' => $order->order_number])
                    ->with('success', __('store.checkout.order_placed_cod'));
            }

            if ($validated['payment_method'] === 'wallet') {
                $user = $order->user;
                $customer = $user?->customer;
                // E.g., 10 points = 1 EGP
                $pointsNeeded = (int) ceil((float) $order->total_amount * 10);

                if (! $customer || $customer->points_balance < $pointsNeeded) {
                    return back()->withErrors([
                        'payment' => __('store.checkout.insufficient_points', ['needed' => $pointsNeeded, 'balance' => $customer?->points_balance ?? 0]),
                    ]);
                }

                // Deduct points
                $customer->decrement('points_balance', $pointsNeeded);
                $customer->increment('total_points_spent', $pointsNeeded);

                PointsTransaction::create([
                    'customer_id' => $customer->id,
                    'type' => 'spend',
                    'amount' => -$pointsNeeded,
                    'balance_after' => $customer->points_balance,
                    'description' => "Order #{$order->order_number} payment",
                ]);

                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);

                $order->payments()->latest()->first()?->update([
                    'status' => 'success',
                    'paid_at' => now(),
                ]);

                $this->cartService->clear();

                return redirect()->route('store.order.confirmation', ['orderNumber' => $order->order_number])
                    ->with('success', __('store.checkout.order_paid_wallet'));
            }

            if ($validated['payment_method'] === 'kashier') {
                $session = $this->kashierService->createPaymentSession($order, app()->getLocale());

                $order->payments()->latest()->first()?->update([
                    'transaction_id' => $session['sessionId'],
                    'gateway_response' => $session['raw'] ?? null,
                ]);

                // Redirect user to Kashier hosted checkout URL
                return redirect()->away($session['sessionUrl']);
            }

            return redirect()->route('store.order.confirmation', ['orderNumber' => $order->order_number]);
        } catch (Exception $e) {
            Log::error('Checkout processing error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
        }
    }

    public function confirmation(string $orderNumber): View
    {
        $order = Order::with(['items.product.thumbnail', 'items.product.category', 'shippingAddress', 'payments', 'user'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return view('store.order_confirmation', compact('order'));
    }
}
