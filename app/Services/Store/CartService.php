<?php

namespace App\Services\Store;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\SystemSetting;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    /**
     * Get or create the active Cart instance for current user/session.
     */
    public function getCart(): Cart
    {
        $userId = Auth::id();
        $sessionId = Session::getId();

        $cart = null;

        if ($userId) {
            $cart = Cart::with(['items.product.category', 'items.product.thumbnail', 'items.product.images', 'items.productOption'])
                ->where('user_id', $userId)
                ->latest()
                ->first();
        }

        if (! $cart) {
            $cart = Cart::with(['items.product.category', 'items.product.thumbnail', 'items.product.images', 'items.productOption'])
                ->where('session_id', $sessionId)
                ->whereNull('user_id')
                ->latest()
                ->first();
        }

        if (! $cart) {
            $cart = Cart::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'expires_at' => now()->addDays(14),
                'total' => 0,
            ]);
            $cart->load(['items.product.category', 'items.product.thumbnail', 'items.product.images', 'items.productOption']);
        }

        return $cart;
    }

    /**
     * Add a product to the cart with optional variant and option-specific price.
     */
    public function addItem(int $productId, int $quantity = 1, ?string $color = null, ?string $option = null, ?int $optionId = null): CartItem
    {
        $product = Product::with(['options', 'colors'])->findOrFail($productId);

        if (! $product->is_active) {
            throw new Exception(__('store.cart.product_unavailable'));
        }

        $cart = $this->getCart();

        $quantity = max(1, $quantity);

        // Resolve product option if selected
        $productOption = null;
        if ($optionId) {
            $productOption = $product->options->firstWhere('id', $optionId);
        } elseif ($option) {
            $productOption = $product->options->firstWhere('id', (int) $option)
                ?? $product->options->firstWhere('value', $option);
        }

        // Determine price: use option price if set, otherwise base product price
        $unitPrice = (float) $product->price;
        if ($productOption && $productOption->price !== null && (float) $productOption->price > 0) {
            $unitPrice = (float) $productOption->price;
        }

        // Build descriptive option label
        $labels = [];
        if ($color) {
            $labels[] = __('store.product.colors', [], app()->getLocale()) ?: 'اللون' . ': ' . $color;
        }
        if ($productOption) {
            $labels[] = ($productOption->name ? $productOption->name . ': ' : '') . $productOption->value;
        } elseif ($option) {
            $labels[] = $option;
        }
        $optionLabel = !empty($labels) ? implode(' · ', $labels) : null;

        // Find existing cart line with exact same product and option
        $query = $cart->items()->where('product_id', $product->id);
        if ($productOption) {
            $query->where('product_option_id', $productOption->id);
        } else {
            $query->whereNull('product_option_id');
        }
        $item = $query->first();

        if ($item) {
            $newQty = $item->quantity + $quantity;
            if ($product->stock_quantity > 0 && $newQty > $product->stock_quantity) {
                $newQty = $product->stock_quantity;
            }
            $item->update([
                'quantity' => $newQty,
                'unit_price' => $unitPrice,
                'option_label' => $optionLabel ?: $item->option_label,
            ]);
        } else {
            $item = $cart->items()->create([
                'product_id' => $product->id,
                'product_option_id' => $productOption?->id,
                'option_label' => $optionLabel,
                'quantity' => min($quantity, $product->stock_quantity > 0 ? $product->stock_quantity : $quantity),
                'unit_price' => $unitPrice,
            ]);
        }

        $this->refreshTotals($cart);

        return $item;
    }

    /**
     * Update quantity of a cart item.
     */
    public function updateQuantity(int $itemId, int $quantity): ?CartItem
    {
        $cart = $this->getCart();
        $item = $cart->items()->find($itemId);

        if (! $item) {
            return null;
        }

        if ($quantity <= 0) {
            $item->delete();
            $this->refreshTotals($cart);
            return null;
        }

        $product = $item->product;
        if ($product && $product->stock_quantity > 0 && $quantity > $product->stock_quantity) {
            $quantity = $product->stock_quantity;
        }

        $item->update(['quantity' => $quantity]);
        $this->refreshTotals($cart);

        return $item;
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $itemId): bool
    {
        $cart = $this->getCart();
        $deleted = (bool) $cart->items()->where('id', $itemId)->delete();
        $this->refreshTotals($cart);

        return $deleted;
    }

    /**
     * Clear all items in the cart.
     */
    public function clear(): void
    {
        $cart = $this->getCart();
        $cart->items()->delete();
        $cart->update([
            'coupon_code' => null,
            'total' => 0,
        ]);
    }

    /**
     * Apply coupon code to the cart.
     */
    public function applyCoupon(string $code): array
    {
        $code = trim($code);
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isCurrentlyValid()) {
            throw new Exception(__('store.cart.invalid_coupon'));
        }

        $cart = $this->getCart();
        $subtotal = $this->calculateSubtotal($cart);

        if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
            throw new Exception(__('store.cart.coupon_min_spend', ['amount' => $coupon->min_order_amount]));
        }

        $cart->update(['coupon_code' => $coupon->code]);
        $this->refreshTotals($cart);

        return [
            'success' => true,
            'code' => $coupon->code,
            'discount' => $this->calculateDiscount($cart, $coupon, $subtotal),
        ];
    }

    /**
     * Remove applied coupon.
     */
    public function removeCoupon(): void
    {
        $cart = $this->getCart();
        $cart->update(['coupon_code' => null]);
        $this->refreshTotals($cart);
    }

    /**
     * Calculate all totals for the cart.
     */
    public function getSummary(?Cart $cart = null): array
    {
        $cart = $cart ?? $this->getCart();
        $cart->loadMissing(['items.product']);

        $subtotal = $this->calculateSubtotal($cart);
        $itemsCount = $cart->items->sum('quantity');

        $discount = 0.00;
        $coupon = null;
        if ($cart->coupon_code) {
            $coupon = Coupon::where('code', $cart->coupon_code)->first();
            if ($coupon && $coupon->isCurrentlyValid()) {
                $discount = $this->calculateDiscount($cart, $coupon, $subtotal);
            }
        }

        $shippingFlat = (float) SystemSetting::getValue('shipping_cost', 35.00);
        $freeShippingThreshold = (float) SystemSetting::getValue('free_shipping_threshold', 500.00);

        $shipping = ($subtotal >= $freeShippingThreshold && $subtotal > 0) ? 0.00 : ($itemsCount > 0 ? $shippingFlat : 0.00);

        $total = max(0, $subtotal - $discount + $shipping);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shipping' => round($shipping, 2),
            'total' => round($total, 2),
            'items_count' => $itemsCount,
            'coupon_code' => $coupon?->code,
            'coupon' => $coupon,
        ];
    }

    /**
     * Total items count in the active cart.
     */
    public function count(): int
    {
        return (int) $this->getCart()->items()->sum('quantity');
    }

    /**
     * Reassociate guest cart with logged-in user.
     */
    public function transferGuestCartToUser(int $userId): void
    {
        $sessionId = Session::getId();
        $guestCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->latest()->first();

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::where('user_id', $userId)->latest()->first();

        if (! $userCart) {
            $guestCart->update(['user_id' => $userId]);
            return;
        }

        // Merge items
        foreach ($guestCart->items as $item) {
            $existing = $userCart->items()->where('product_id', $item->product_id)->first();
            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $item->quantity]);
            } else {
                $item->update(['cart_id' => $userCart->id]);
            }
        }

        $guestCart->delete();
        $this->refreshTotals($userCart);
    }

    protected function calculateSubtotal(Cart $cart): float
    {
        return (float) $cart->items->sum(function ($item) {
            $price = $item->product ? (float) $item->product->price : (float) $item->unit_price;
            return $price * $item->quantity;
        });
    }

    protected function calculateDiscount(Cart $cart, Coupon $coupon, float $subtotal): float
    {
        if ($coupon->type === 'percentage') {
            $discount = ($subtotal * (float) $coupon->value) / 100;
            if ($coupon->max_discount && $discount > (float) $coupon->max_discount) {
                $discount = (float) $coupon->max_discount;
            }
            return min($subtotal, $discount);
        }

        return min($subtotal, (float) $coupon->value);
    }

    protected function refreshTotals(Cart $cart): void
    {
        $summary = $this->getSummary($cart);
        $cart->update(['total' => $summary['total']]);
    }
}
