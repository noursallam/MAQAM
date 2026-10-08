<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\KashierService;
use App\Services\Store\CartService;
use App\Services\Store\OrderNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class KashierPaymentController extends Controller
{
    public function __construct(
        protected KashierService $kashierService,
        protected CartService $cartService,
    ) {}

    /**
     * User return callback from Kashier hosted checkout.
     *
     * The query string is only trusted when Kashier's signature on it is valid:
     * anyone can open this URL by hand, so an unsigned "SUCCESS" proves nothing.
     */
    public function callback(Request $request): RedirectResponse
    {
        [$order, $outcome] = $this->settleFromRedirect($request);

        if (! $order) {
            return redirect()->route('store.home')->withErrors(['payment' => __('store.checkout.payment_declined')]);
        }

        $confirmation = redirect()->route('store.order.confirmation', ['orderNumber' => $order->order_number]);

        return match ($outcome) {
            'paid' => $confirmation->with('success', __('store.checkout.payment_success')),
            // The signed server-to-server webhook is what settles the order
            'pending' => $confirmation->with('info', __('store.checkout.payment_pending_confirmation')),
            default => redirect()->route('store.checkout')
                ->withErrors(['payment' => __('store.checkout.payment_declined')]),
        };
    }

    /**
     * The same return, for a payment made inside the mobile app. The app closes the page as soon
     * as it loads; the page says so in case it does not, rather than dropping the customer into
     * the website.
     */
    public function appCallback(Request $request): View
    {
        [$order, $outcome] = $this->settleFromRedirect($request);

        return view('store.payment_return', [
            'outcome' => $order ? $outcome : 'failed',
            'orderNumber' => $order?->order_number,
        ]);
    }

    /**
     * Apply what the gateway's redirect says, when it can be trusted.
     *
     * @return array{0: ?Order, 1: 'paid'|'pending'|'failed'}
     */
    protected function settleFromRedirect(Request $request): array
    {
        $params = $request->query();
        $orderNumber = $params['merchantOrderId'] ?? null;

        Log::info('Kashier: Redirect callback received', ['order' => $orderNumber, 'status' => $params['paymentStatus'] ?? null]);

        $order = $orderNumber ? Order::where('order_number', $orderNumber)->first() : null;

        if (! $order) {
            return [null, 'failed'];
        }

        if ($order->payment_status === 'paid') {
            return [$order, 'paid'];
        }

        if (! $this->kashierService->validateRedirectSignature($params)) {
            Log::warning('Kashier: Invalid redirect signature, order left untouched', ['order' => $orderNumber]);

            return [$order, 'pending'];
        }

        if (strtoupper((string) ($params['paymentStatus'] ?? '')) === 'SUCCESS' && $this->amountMatches($order, $params['amount'] ?? null)) {
            $this->markPaid($order, $params['transactionId'] ?? null, $params);
            $this->cartService->clear();

            return [$order, 'paid'];
        }

        $this->markFailed($order, $params);

        return [$order, 'failed'];
    }

    /**
     * Server-to-server webhook endpoint from Kashier.
     */
    public function webhook(Request $request): JsonResponse
    {
        $signature = (string) $request->header('x-kashier-signature', '');
        $payload = $request->all();
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        Log::info('Kashier: Webhook received', ['order' => $data['merchantOrderId'] ?? null, 'status' => $data['status'] ?? null]);

        // A webhook without a valid signature could come from anyone
        if ($signature === '' || ! $this->kashierService->validateWebhookSignature($payload, $signature)) {
            Log::warning('Kashier: Webhook rejected, missing or invalid signature');

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $orderNumber = $data['merchantOrderId'] ?? null;
        $status = strtoupper((string) ($data['status'] ?? ''));

        if (! $orderNumber) {
            return response()->json(['error' => 'Missing merchantOrderId'], 422);
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['status' => 'already_processed'], 200);
        }

        if ($status === 'SUCCESS') {
            if (! $this->amountMatches($order, $data['amount'] ?? null)) {
                Log::warning('Kashier: Webhook amount does not match the order', ['order' => $orderNumber]);

                return response()->json(['error' => 'Amount mismatch'], 422);
            }

            $this->markPaid($order, $data['transactionId'] ?? null, $data);
        } elseif (in_array($status, ['FAILED', 'DECLINED', 'CANCELLED'], true)) {
            $this->markFailed($order, $data);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Settle the order exactly once, even when the callback and the webhook arrive together.
     */
    protected function markPaid(Order $order, ?string $transactionId, array $gatewayResponse): void
    {
        $paidNow = false;

        DB::transaction(function () use ($order, $transactionId, $gatewayResponse, &$paidNow) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->payment_status === 'paid' || $order->status === 'cancelled') {
                return;
            }

            $paidNow = true;

            $order->update([
                'payment_status' => 'paid',
                'status' => $order->status === 'new' ? 'processing' : $order->status,
            ]);

            $attributes = [
                'transaction_id' => $transactionId ?: 'TX-'.$order->id.'-'.time(),
                'status' => 'success',
                'paid_at' => now(),
                'gateway_response' => $gatewayResponse,
            ];

            $payment = $order->payments()->latest('id')->first();
            $payment
                ? $payment->update($attributes)
                : Payment::create($attributes + ['order_id' => $order->id, 'gateway' => 'kashier', 'amount' => $order->total_amount]);
        });

        // Once only, however many times the gateway reports the same payment
        if ($paidNow) {
            app(OrderNotifier::class)->paymentConfirmed($order->fresh());
        }
    }

    protected function markFailed(Order $order, array $gatewayResponse): void
    {
        $failedNow = false;

        // Locked like markPaid, so the redirect and the webhook reporting the same failure
        // together record it, and announce it, once
        DB::transaction(function () use ($order, $gatewayResponse, &$failedNow) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->payment_status === 'paid' || $order->status === 'cancelled') {
                return;
            }

            $failedNow = $order->payment_status !== 'failed';

            $order->update(['payment_status' => 'failed']);
            $order->payments()->latest('id')->first()?->update([
                'status' => 'failed',
                'gateway_response' => $gatewayResponse,
            ]);
        });

        if ($failedNow) {
            app(OrderNotifier::class)->paymentFailed($order->fresh());
        }
    }

    /**
     * The paid amount must be the order total; a missing amount is not accepted.
     */
    protected function amountMatches(Order $order, mixed $amount): bool
    {
        return is_numeric($amount) && abs((float) $amount - (float) $order->total_amount) < 0.01;
    }
}
