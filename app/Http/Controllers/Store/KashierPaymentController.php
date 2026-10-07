<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PointsTransaction;
use App\Services\Payment\KashierService;
use App\Services\RankService;
use App\Services\Store\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $params = $request->query();
        $orderNumber = $params['merchantOrderId'] ?? null;

        Log::info('Kashier: Redirect callback received', ['order' => $orderNumber, 'status' => $params['paymentStatus'] ?? null]);

        $order = $orderNumber ? Order::where('order_number', $orderNumber)->first() : null;

        if (! $order) {
            return redirect()->route('store.home')->withErrors(['payment' => __('store.checkout.payment_declined')]);
        }

        $confirmation = redirect()->route('store.order.confirmation', ['orderNumber' => $order->order_number]);

        if ($order->payment_status === 'paid') {
            return $confirmation->with('success', __('store.checkout.payment_success'));
        }

        if (! $this->kashierService->validateRedirectSignature($params)) {
            Log::warning('Kashier: Invalid redirect signature, order left untouched', ['order' => $orderNumber]);

            // The signed server-to-server webhook is what settles the order
            return $confirmation->with('info', __('store.checkout.payment_pending_confirmation'));
        }

        if (strtoupper((string) ($params['paymentStatus'] ?? '')) === 'SUCCESS' && $this->amountMatches($order, $params['amount'] ?? null)) {
            $this->markPaid($order, $params['transactionId'] ?? null, $params);
            $this->cartService->clear();

            return $confirmation->with('success', __('store.checkout.payment_success'));
        }

        $this->markFailed($order, $params);

        return redirect()->route('store.checkout')
            ->withErrors(['payment' => __('store.checkout.payment_declined')]);
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
        DB::transaction(function () use ($order, $transactionId, $gatewayResponse) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->payment_status === 'paid' || $order->status === 'cancelled') {
                return;
            }

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

            $this->awardLoyaltyPoints($order);
        });
    }

    protected function markFailed(Order $order, array $gatewayResponse): void
    {
        if ($order->payment_status === 'paid') {
            return;
        }

        $order->update(['payment_status' => 'failed']);
        $order->payments()->latest('id')->first()?->update([
            'status' => 'failed',
            'gateway_response' => $gatewayResponse,
        ]);
    }

    /**
     * The paid amount must be the order total; a missing amount is not accepted.
     */
    protected function amountMatches(Order $order, mixed $amount): bool
    {
        return is_numeric($amount) && abs((float) $amount - (float) $order->total_amount) < 0.01;
    }

    /**
     * Award loyalty points for paid orders.
     */
    protected function awardLoyaltyPoints(Order $order): void
    {
        $customer = $order->user?->customer;
        if (! $customer) {
            return;
        }

        // Award 1 point per 10 EGP spent
        $points = (int) floor((float) $order->total_amount / 10);
        if ($points > 0) {
            $customer->increment('points_balance', $points);
            $customer->increment('total_points_earned', $points);
            app(RankService::class)->promoteIfEarned($customer);

            PointsTransaction::create([
                'customer_id' => $customer->id,
                'type' => 'earn',
                'amount' => $points,
                'balance_after' => $customer->points_balance,
                'description' => "Reward points for Order #{$order->order_number}",
            ]);
        }
    }
}
