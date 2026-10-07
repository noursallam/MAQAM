<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\Product;
use App\Services\Store\CartService;
use App\Support\ApiPresenter;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in user's cart: the same cart the web store shows for that account.
 */
class CartController extends ApiController
{
    public function __construct(
        protected CartService $cart
    ) {}

    public function show(): JsonResponse
    {
        return $this->respond();
    }

    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'option_id' => ['nullable', 'integer'],
            'color' => ['nullable', 'string', 'max:100'],
        ]);

        $product = Product::where('is_active', true)->find($validated['product_id']);

        if (! $product) {
            throw new ApiException('PRODUCT_UNAVAILABLE', __('store.cart.product_unavailable'), 404);
        }

        if ($product->stock_quantity <= 0) {
            throw new ApiException('OUT_OF_STOCK', __('api.checkout.out_of_stock', ['product' => $product->name_ar, 'available' => 0]), 422);
        }

        if (! empty($validated['option_id']) && ! $product->options()->whereKey($validated['option_id'])->exists()) {
            throw new ApiException('VALIDATION_ERROR', __('api.validation_failed'), 422, [
                'option_id' => [__('api.cart.invalid_option')],
            ]);
        }

        $this->cart->addItem(
            $product->id,
            (int) ($validated['quantity'] ?? 1),
            $validated['color'] ?? null,
            null,
            $validated['option_id'] ?? null,
        );

        return $this->respond(__('api.cart.added'));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        // Scoped to this user's cart, so another user's item id is simply "not found"
        if (! $this->cart->getCart()->items()->whereKey($validated['item_id'])->exists()) {
            throw new ApiException('NOT_FOUND', __('api.not_found'), 404);
        }

        $this->cart->updateQuantity($validated['item_id'], $validated['quantity']);

        return $this->respond(__('api.cart.updated'));
    }

    public function remove(int $itemId): JsonResponse
    {
        if (! $this->cart->removeItem($itemId)) {
            throw new ApiException('NOT_FOUND', __('api.not_found'), 404);
        }

        return $this->respond(__('api.cart.updated'));
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:50']]);

        try {
            $this->cart->applyCoupon($validated['code']);
        } catch (Exception $e) {
            throw new ApiException('INVALID_COUPON', $e->getMessage(), 422);
        }

        return $this->respond(__('api.cart.coupon_applied'));
    }

    public function removeCoupon(): JsonResponse
    {
        $this->cart->removeCoupon();

        return $this->respond(__('api.cart.updated'));
    }

    private function respond(string $message = ''): JsonResponse
    {
        $cart = $this->cart->getCart();

        return $this->ok(ApiPresenter::cart($cart, $this->cart->getSummary($cart)), $message);
    }
}
