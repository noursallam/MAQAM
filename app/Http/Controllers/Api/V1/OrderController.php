<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Services\Payment\KashierService;
use App\Services\Store\CartService;
use App\Services\Store\OrderPlacementService;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class OrderController extends ApiController
{
    public function __construct(
        protected CartService $cart,
        protected OrderPlacementService $orders,
        protected KashierService $kashier,
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);

        $validated = $request->validate([
            // Either a saved address, or a typed one
            'address_id' => ['nullable', 'integer'],
            'full_name' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'phone' => ['required_without:address_id', 'nullable', 'regex:/^(20)?01[0125]\d{8}$/'],
            'governorate' => ['required_without:address_id', 'nullable', 'string', 'max:100'],
            'city' => ['required_without:address_id', 'nullable', 'string', 'max:100'],
            'address' => ['required_without:address_id', 'nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in(['cod', 'kashier', 'wallet'])],
        ]);

        if (str_starts_with((string) ($validated['phone'] ?? ''), '20')) {
            $validated['phone'] = '0'.substr($validated['phone'], 2);
        }

        $user = $request->user();
        $cart = $this->cart->getCart();
        $order = $this->orders->place($user, $cart, $validated, $validated['payment_method']);

        $paymentUrl = null;

        if ($validated['payment_method'] === 'kashier') {
            try {
                $session = $this->kashier->createPaymentSession($order, app()->getLocale(), forApp: true);
            } catch (Throwable $e) {
                report($e);
                // The customer sees the error on screen; the order was never announced to them
                $this->orders->cancel($order, 'Payment session could not be created', notify: false);

                throw new ApiException('PAYMENT_GATEWAY_ERROR', __('api.checkout.gateway_error'), 502);
            }

            $order->payments()->latest('id')->first()?->update([
                'transaction_id' => $session['sessionId'],
                'gateway_response' => $session['raw'] ?? null,
            ]);
            $this->orders->emptyCart($cart);
            $paymentUrl = $session['sessionUrl'];
        }

        return $this->ok([
            'order' => ApiPresenter::order($order->fresh(['items.product', 'shippingAddress']), true),
            'payment_url' => $paymentUrl,
            // The in-app WebView should close when it is redirected to a URL starting with this
            'payment_return_url' => $paymentUrl ? route('payment.kashier.callback') : null,
        ], $paymentUrl ? __('api.checkout.complete_payment') : __('api.checkout.placed'), 201);
    }

    /**
     * Cancel an order that has not shipped. Stock, coupon, reward and wallet points are given back.
     */
    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $order = Order::where('user_id', $request->user()->id)->where('order_number', $orderNumber)->firstOrFail();

        if (! $this->orders->isCancellable($order)) {
            throw new ApiException('ORDER_NOT_CANCELLABLE', __('api.order.not_cancellable'), 422);
        }

        $order = $this->orders->cancel($order, $validated['reason'] ?? 'Cancelled by customer');

        return $this->ok(ApiPresenter::order($order->fresh(['items.product', 'shippingAddress']), true), __('api.order.cancelled'));
    }

    public function index(Request $request): JsonResponse
    {
        $page = Order::where('user_id', $request->user()->id)->latest('id')->paginate($this->perPage($request));

        return $this->paginated($page, fn (Order $order) => ApiPresenter::order($order));
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        // Always scoped to the owner: someone else's order number is "not found"
        $order = Order::with(['items.product', 'shippingAddress'])
            ->where('user_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return $this->ok(ApiPresenter::order($order, true));
    }
}
