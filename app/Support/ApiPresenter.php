<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PointsTransaction;
use App\Models\Product;
use App\Models\Rank;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Services\MediaService;
use App\Services\Store\OrderPlacementService;

/**
 * The JSON shapes of the mobile API. Only fields listed here ever leave the server.
 */
class ApiPresenter
{
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'phone_number' => $user->phone_number,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'preferred_language' => $user->preferred_language,
            'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
        ];
    }

    public static function rank(?Rank $rank): ?array
    {
        if (! $rank) {
            return null;
        }

        return [
            'id' => $rank->id,
            'name' => self::localized($rank, 'name'),
            'name_ar' => $rank->name_ar,
            'name_en' => $rank->name_en,
            'min_points' => (int) $rank->min_points,
            'max_points' => $rank->max_points !== null ? (int) $rank->max_points : null,
            'merchant_points_per_scan' => (int) $rank->merchant_points_per_scan,
            'wheel_cost_points' => (int) $rank->wheel_cost_points,
            'icon_url' => $rank->icon_url,
        ];
    }

    /**
     * Spendable balance, plus rank and progress, which follow lifetime earned points.
     */
    public static function wallet(Customer $customer): array
    {
        $customer->loadMissing('rank');
        $rank = $customer->rank;
        $balance = (int) $customer->points_balance;
        $earned = (int) $customer->total_points_earned;

        $nextRank = Rank::where('is_active', true)
            ->where('min_points', '>', (int) ($rank?->min_points ?? -1))
            ->orderBy('min_points')
            ->first();

        return [
            'customer_id' => $customer->id,
            'points_balance' => $balance,
            'total_points_earned' => (int) $customer->total_points_earned,
            'total_points_spent' => (int) $customer->total_points_spent,
            'date_of_birth' => $customer->date_of_birth?->toDateString(),
            'rank' => self::rank($rank),
            'next_rank' => self::rank($nextRank),
            'points_left_for_next_rank' => $nextRank ? max(0, (int) $nextRank->min_points - $earned) : 0,
            'progress_percent' => $nextRank && $nextRank->min_points > 0
                ? (int) min(100, max(5, round($earned / $nextRank->min_points * 100)))
                : 100,
        ];
    }

    public static function merchant(Merchant $merchant): array
    {
        return [
            'id' => $merchant->id,
            'merchant_code' => $merchant->merchant_code,
            'business_name' => $merchant->business_name,
            'business_address' => $merchant->business_address,
            'logo_url' => $merchant->logo_url ? app(MediaService::class)->url($merchant->logo_url) : null,
            'is_approved' => (bool) $merchant->is_approved,
            'approved_at' => $merchant->approved_at?->toIso8601String(),
        ];
    }

    public static function transaction(PointsTransaction $tx): array
    {
        return [
            'id' => $tx->id,
            'type' => $tx->type,
            'amount' => (int) $tx->amount,
            'balance_after' => $tx->balance_after !== null ? (int) $tx->balance_after : null,
            'description' => $tx->description,
            'date' => ($tx->transaction_date ?? $tx->created_at)?->toIso8601String(),
        ];
    }

    public static function reward(CustomerReward $reward): array
    {
        $status = $reward->status === CustomerReward::STATUS_AVAILABLE && $reward->expires_at?->isPast()
            ? CustomerReward::STATUS_EXPIRED
            : $reward->status;

        return [
            'id' => $reward->id,
            'code' => $reward->code,
            'type' => $reward->type,
            'source' => $reward->source,
            'status' => $status,
            'amount_type' => $reward->amount_type,
            'amount_value' => $reward->amount_value,
            'product' => $reward->product ? self::productSummary($reward->product) : null,
            'expires_at' => $reward->expires_at?->toIso8601String(),
            'used_at' => $reward->used_at?->toIso8601String(),
        ];
    }

    public static function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => self::localized($category, 'name'),
            'name_ar' => $category->name_ar,
            'name_en' => $category->name_en,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'image_url' => $category->image_url,
        ];
    }

    public static function productSummary(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => self::localized($product, 'name'),
            'name_ar' => $product->name_ar,
            'name_en' => $product->name_en,
            'price' => number_format((float) $product->price, 2, '.', ''),
            'min_price' => number_format($product->minPrice(), 2, '.', ''),
            'max_price' => number_format($product->maxPrice(), 2, '.', ''),
            'stock_quantity' => (int) $product->stock_quantity,
            'sku' => $product->sku,
            'image_url' => $product->image_url,
            'category' => $product->relationLoaded('category') && $product->category
                ? ['id' => $product->category->id, 'name' => self::localized($product->category, 'name')]
                : null,
        ];
    }

    public static function product(Product $product): array
    {
        return self::productSummary($product) + [
            'description' => self::localized($product, 'description'),
            'description_ar' => $product->description_ar,
            'description_en' => $product->description_en,
            'weight' => $product->weight,
            'dimensions' => $product->dimensions,
            'images' => $product->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url(),
                'is_thumbnail' => (bool) $image->is_thumbnail,
            ])->values(),
            'colors' => $product->colors->map(fn ($color) => [
                'id' => $color->id,
                'name' => $color->name,
                'hex' => $color->hex,
            ])->values(),
            'options' => $product->options->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'value' => $option->value,
                'price' => number_format($option->effectivePrice(), 2, '.', ''),
            ])->values(),
        ];
    }

    /**
     * @param  array{subtotal: float, discount: float, shipping: float, total: float, items_count: int, coupon_code: ?string}  $summary
     */
    public static function cart(Cart $cart, array $summary): array
    {
        return [
            'id' => $cart->id,
            'items' => $cart->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => $item->product ? self::productSummary($item->product) : null,
                'product_option_id' => $item->product_option_id,
                'option_label' => $item->option_label,
                'quantity' => (int) $item->quantity,
                'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
                'line_total' => number_format((float) $item->unit_price * $item->quantity, 2, '.', ''),
            ])->values(),
            'items_count' => (int) $summary['items_count'],
            'coupon_code' => $summary['coupon_code'],
            'subtotal' => number_format($summary['subtotal'], 2, '.', ''),
            'discount' => number_format($summary['discount'], 2, '.', ''),
            'shipping' => number_format($summary['shipping'], 2, '.', ''),
            'total' => number_format($summary['total'], 2, '.', ''),
            'currency' => 'EGP',
        ];
    }

    public static function address(ShippingAddress $address): array
    {
        return [
            'id' => $address->id,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'governorate' => $address->governorate,
            'city' => $address->city,
            'address' => $address->address_line1,
            'notes' => $address->address_line2,
            'is_default' => (bool) $address->is_default,
        ];
    }

    public static function order(Order $order, bool $withItems = false): array
    {
        $data = [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'shipping_cost' => $order->shipping_cost,
            'total_amount' => $order->total_amount,
            'currency' => 'EGP',
            'coupon_code' => $order->coupon_code,
            'can_cancel' => app(OrderPlacementService::class)->isCancellable($order),
            'cancellation_reason' => $order->cancellation_reason,
            'created_at' => $order->created_at?->toIso8601String(),
            'shipped_at' => $order->shipped_at?->toIso8601String(),
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
        ];

        if ($withItems) {
            $data['shipping_address'] = $order->shippingAddress ? self::address($order->shippingAddress) : null;
            $data['items'] = $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => $item->product ? self::productSummary($item->product) : null,
                'option_label' => $item->option_label,
                'quantity' => (int) $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ])->values();
        }

        return $data;
    }

    public static function notification(AppNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'body' => $notification->body,
            'type' => $notification->type,
            'is_read' => (bool) $notification->is_read,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    private static function localized(object $model, string $field): ?string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';

        return $model->{"{$field}_{$locale}"} ?: $model->{"{$field}_ar"};
    }
}
