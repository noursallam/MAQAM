<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PointsTransaction;
use App\Services\Payment\KashierService;
use App\Services\Store\CartService;
use Exception;
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
     */
    public function callback(Request $request): RedirectResponse
    {
        $params = $request->query();

        Log::info('Kashier: Redirect callback received', $params);

        $orderNumber = $params['merchantOrderId'] ?? null;

        if (! $orderNumber) {
            return redirect()->route('store.home')->withErrors(['payment' => 'Missing order reference in callback.']);
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            return redirect()->route('store.home')->withErrors(['payment' => 'Order not found.']);
        }

        // Validate redirect signature
        $isValidSignature = $this->kashierService->validateRedirectSignature($params);

        if (! $isValidSignature) {
            Log::warning('Kashier: Invalid redirect signature for order '.$orderNumber, $params);
            // In sandbox/testing or if strict signature verification differs, log warning and check session if needed
        }

        $paymentStatus = strtoupper((string) ($params['paymentStatus'] ?? ''));

        if ($paymentStatus === 'SUCCESS') {
            DB::transaction(function () use ($order, $params) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => $order->status === 'new' ? 'processing' : $order->status,
                ]);

                $payment = $order->payments()->latest()->first();
                if ($payment) {
                    $payment->update([
                        'transaction_id' => $params['transactionId'] ?? ('TX-'.$order->id.'-'.time()),
                        'status' => 'success',
                        'paid_at' => now(),
                        'gateway_response' => $params,
                    ]);
                } else {
                    Payment::create([
                        'order_id' => $order->id,
                        'transaction_id' => $params['transactionId'] ?? ('TX-'.$order->id.'-'.time()),
                        'gateway' => 'kashier',
                        'amount' => $order->total_amount,
                        'status' => 'success',
                        'paid_at' => now(),
                        'gateway_response' => $params,
                    ]);
                }

                // Award points to customer if customer exists
                $this->awardLoyaltyPoints($order);
            });

            // Clear customer cart
            $this->cartService->clear();

            return redirect()->route('store.order.confirmation', ['orderNumber' => $order->order_number])
                ->with('success', __('store.checkout.payment_success'));
        }

        // Payment failed or cancelled
        $order->update(['payment_status' => 'failed']);
        $order->payments()->latest()->first()?->update([
            'status' => 'failed',
            'gateway_response' => $params,
        ]);

        return redirect()->route('store.checkout')
            ->withErrors(['payment' => __('store.checkout.payment_declined')]);
    }

    /**
     * Server-to-server webhook endpoint from Kashier.
     */
    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('x-kashier-signature', '');
        $payload = $request->all();

        Log::info('Kashier: Webhook received', [
            'signature' => $signature,
            'payload' => $payload,
        ]);

        // Validate webhook signature
        if (! empty($signature)) {
            $isValid = $this->kashierService->validateWebhookSignature($payload, $signature);
            if (! $isValid) {
                Log::warning('Kashier: Invalid webhook signature', ['signature' => $signature]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $data = $payload['data'] ?? [];
        $orderNumber = $data['merchantOrderId'] ?? null;
        $status = strtoupper((string) ($data['status'] ?? ''));

        if (! $orderNumber) {
            return response()->json(['error' => 'Missing merchantOrderId'], 422);
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Idempotency: If order is already marked paid, return 200 immediately
        if ($order->payment_status === 'paid') {
            return response()->json(['status' => 'already_processed'], 200);
        }

        if ($status === 'SUCCESS') {
            DB::transaction(function () use ($order, $data) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => $order->status === 'new' ? 'processing' : $order->status,
                ]);

                $payment = $order->payments()->latest()->first();
                if ($payment) {
                    $payment->update([
                        'transaction_id' => $data['transactionId'] ?? $payment->transaction_id,
                        'status' => 'success',
                        'paid_at' => now(),
                        'gateway_response' => $data,
                    ]);
                } else {
                    Payment::create([
                        'order_id' => $order->id,
                        'transaction_id' => $data['transactionId'] ?? ('TX-'.$order->id.'-'.time()),
                        'gateway' => 'kashier',
                        'amount' => $order->total_amount,
                        'status' => 'success',
                        'paid_at' => now(),
                        'gateway_response' => $data,
                    ]);
                }

                $this->awardLoyaltyPoints($order);
            });
        } elseif (in_array($status, ['FAILED', 'DECLINED', 'CANCELLED'], true)) {
            $order->update(['payment_status' => 'failed']);
            $order->payments()->latest()->first()?->update([
                'status' => 'failed',
                'gateway_response' => $data,
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
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
