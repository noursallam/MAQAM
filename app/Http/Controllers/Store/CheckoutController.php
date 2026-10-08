<?php

namespace App\Http\Controllers\Store;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Rank;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Services\Payment\KashierService;
use App\Services\Store\CartService;
use App\Services\Store\OrderPlacementService;
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
        protected OrderPlacementService $orders,
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

        // Standardize Egyptian phone number (e.g. 01012345678)
        $cleanPhone = preg_replace('/\D/', '', $validated['phone']);
        if (str_starts_with($cleanPhone, '20')) {
            $cleanPhone = '0'.substr($cleanPhone, 2);
        }

        // Paying with points spends an account's balance, so the account owner must be signed in
        if ($validated['payment_method'] === 'wallet' && ! Auth::check()) {
            return redirect()->route('store.login')->withErrors(['phone' => __('store.auth.login_required')]);
        }

        try {
            $user = Auth::user() ?? DB::transaction(fn () => $this->findOrCreateCustomer($cleanPhone, $validated['full_name']));

            $order = $this->orders->place($user, $cart, [
                'full_name' => $validated['full_name'],
                'phone' => $cleanPhone,
                'governorate' => $validated['governorate'],
                'city' => $validated['city'],
                'address' => $validated['address'],
                'notes' => $validated['notes'] ?? null,
            ], $validated['payment_method']);
        } catch (ApiException $e) {
            return back()->withErrors([
                $e->errorCode === 'INSUFFICIENT_POINTS' ? 'payment' : 'checkout' => $e->getMessage(),
            ])->withInput();
        }

        if ($validated['payment_method'] === 'kashier') {
            try {
                $session = $this->kashierService->createPaymentSession($order, app()->getLocale());
            } catch (Exception $e) {
                Log::error('Checkout processing error: '.$e->getMessage());
                // The customer sees the error on screen; the order was never announced to them
                $this->orders->cancel($order, 'Payment session could not be created', notify: false);

                return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
            }

            $order->payments()->latest('id')->first()?->update([
                'transaction_id' => $session['sessionId'],
                'gateway_response' => $session['raw'] ?? null,
            ]);

            // Redirect user to Kashier hosted checkout URL
            return redirect()->away($session['sessionUrl']);
        }

        return redirect()->route('store.order.confirmation', ['orderNumber' => $order->order_number])
            ->with('success', $validated['payment_method'] === 'wallet'
                ? __('store.checkout.order_paid_wallet')
                : __('store.checkout.order_placed_cod'));
    }

    /**
     * Guest checkout: the order is filed under the account of the phone number given.
     */
    private function findOrCreateCustomer(string $phone, string $fullName): User
    {
        $user = User::where('phone_number', $phone)->first();

        if ($user) {
            return $user;
        }

        $user = User::create([
            'full_name' => $fullName,
            'phone_number' => $phone,
            'email' => $phone.'@customer.maqam-eg.com',
            'password' => Hash::make(Str::random(16)),
            'role' => 'customer',
            'is_active' => true,
            'preferred_language' => app()->getLocale(),
        ]);

        Customer::create([
            'user_id' => $user->id,
            'rank_id' => (Rank::where('name_en', 'Silver')->first() ?? Rank::first())?->id,
            'points_balance' => 0,
            'total_points_earned' => 0,
            'total_points_spent' => 0,
        ]);

        return $user;
    }

    public function confirmation(string $orderNumber): View
    {
        $order = Order::with(['items.product.thumbnail', 'items.product.category', 'shippingAddress', 'payments', 'user'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return view('store.order_confirmation', compact('order'));
    }
}
