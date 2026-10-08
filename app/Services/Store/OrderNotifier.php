<?php

namespace App\Services\Store;

use App\Models\Order;
use App\Services\NotificationService;
use App\Services\WhatsApp\SenderBotService;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Tells a customer what is happening with their order: an in-app notification and push for every
 * event, plus a WhatsApp message carrying the order's details and current status.
 *
 * Nothing here may fail the request that triggered it. WhatsApp goes out after the response has
 * been sent, so a slow or unreachable gateway never delays checkout or the admin panel.
 */
class OrderNotifier
{
    public const TYPE = 'order_update';

    public function __construct(
        protected NotificationService $notifications,
        protected SenderBotService $whatsApp,
    ) {}

    /**
     * The order exists and will be prepared (cash on delivery, or paid in full with points).
     */
    public function placed(Order $order): void
    {
        $this->announce($order, 'placed');
    }

    /**
     * The online payment went through; this is also the first the customer hears of the order.
     */
    public function paymentConfirmed(Order $order): void
    {
        $this->announce($order, 'paid');
    }

    public function paymentFailed(Order $order): void
    {
        $this->announce($order, 'payment_failed');
    }

    /**
     * The order moved to another status: by staff, or cancelled by the customer.
     */
    public function statusChanged(Order $order): void
    {
        $this->announce($order, 'status_'.$order->status);
    }

    /**
     * @param  string  $event  suffix of the `api.order.*` wording for this event
     */
    protected function announce(Order $order, string $event): void
    {
        if (! $order->user_id) {
            return;
        }

        try {
            $this->notifications->notifyTranslated(
                [$order->user_id],
                'api.order.status_title',
                'api.order.'.$event,
                ['number' => $order->order_number],
                self::TYPE,
                ['order_number' => $order->order_number, 'status' => $order->status],
            );
        } catch (Throwable $e) {
            report($e);
        }

        $orderId = $order->id;
        defer(fn () => $this->sendWhatsApp($orderId, $event));
    }

    protected function sendWhatsApp(int $orderId, string $event): void
    {
        try {
            // Loaded fresh: this runs after the response, when the order may have moved on
            $order = Order::with(['items.product', 'shippingAddress', 'user'])->find($orderId);
            $phone = $order?->user?->phone_number;

            // Only real mobile numbers; staff and developer accounts have none
            if (! $order || ! $this->whatsApp->isConfigured() || ! preg_match('/^01[0125]\d{8}$/', (string) $phone)) {
                return;
            }

            $locale = $order->user->preferred_language === 'en' ? 'en' : 'ar';
            $this->whatsApp->sendText($phone, $this->message($order, $event, $locale));
        } catch (Throwable $e) {
            // The in-app notification already told them; a missed WhatsApp must stay silent
            Log::warning('Order WhatsApp message failed', ['order_id' => $orderId, 'event' => $event, 'error' => $e->getMessage()]);
        }
    }

    /**
     * The WhatsApp text: what happened, then the order as it stands now.
     */
    public function message(Order $order, string $event, string $locale): string
    {
        $t = fn (string $key, array $replace = []) => __('api.order.wa.'.$key, $replace, $locale);
        $money = fn ($amount) => number_format((float) $amount, 2).' '.$t('currency');

        $lines = [
            '*'.__('api.order.status_title', ['number' => $order->order_number], $locale).'*',
            __('api.order.'.$event, ['number' => $order->order_number], $locale),
            '',
            $t('status', ['status' => $t('status_'.$order->status)]),
            '',
            $t('items'),
        ];

        foreach ($order->items as $item) {
            $name = $locale === 'en'
                ? ($item->product?->name_en ?: $item->product?->name_ar)
                : $item->product?->name_ar;
            $label = trim(($name ?: $t('item')).($item->option_label ? ' ('.$item->option_label.')' : ''));
            $lines[] = '• '.$label.' × '.(int) $item->quantity.' — '
                .((float) $item->subtotal > 0 ? $money($item->subtotal) : $t('free'));
        }

        $lines[] = '';
        $lines[] = $t('subtotal', ['amount' => $money($order->subtotal)]);
        if ((float) $order->discount > 0) {
            $lines[] = $t('discount', ['amount' => $money($order->discount)]);
        }
        $lines[] = $t('shipping', ['amount' => $money($order->shipping_cost)]);
        $lines[] = '*'.$t('total', ['amount' => $money($order->total_amount)]).'*';
        $lines[] = $t('payment', ['method' => $t('method_'.$order->payment_method)]);

        if ($address = $order->shippingAddress) {
            $lines[] = $t('address', ['address' => implode('، ', array_filter([
                $address->governorate, $address->city, $address->address_line1,
            ]))]);
        }

        return implode("\n", $lines);
    }
}
