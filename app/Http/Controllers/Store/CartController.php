<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\CartService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(): View
    {
        $cart = $this->cartService->getCart();
        $summary = $this->cartService->getSummary($cart);

        return view('store.cart', compact('cart', 'summary'));
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'color' => ['nullable', 'string', 'max:50'],
            'option' => ['nullable', 'string', 'max:50'],
            'option_id' => ['nullable', 'integer'],
        ]);

        try {
            $item = $this->cartService->addItem(
                (int) $validated['product_id'],
                (int) ($validated['quantity'] ?? 1),
                $validated['color'] ?? null,
                $validated['option'] ?? null,
                !empty($validated['option_id']) ? (int) $validated['option_id'] : null
            );

            $count = $this->cartService->count();
            $summary = $this->cartService->getSummary();

            if ($request->boolean('buy_now')) {
                return redirect()->route('store.checkout');
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('store.cart.item_added'),
                    'count' => $count,
                    'summary' => $summary,
                ]);
            }

            return back()->with('success', __('store.cart.item_added'));
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['cart' => $e->getMessage()]);
        }
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:500'],
        ]);

        $item = $this->cartService->updateQuantity((int) $validated['item_id'], (int) $validated['quantity']);
        $summary = $this->cartService->getSummary();
        $count = $this->cartService->count();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'item' => $item,
                'summary' => $summary,
                'count' => $count,
            ]);
        }

        return back()->with('success', __('store.cart.updated'));
    }

    public function remove(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->cartService->removeItem($id);
        $summary = $this->cartService->getSummary();
        $count = $this->cartService->count();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'count' => $count,
            ]);
        }

        return back()->with('success', __('store.cart.removed'));
    }

    public function applyCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        try {
            $result = $this->cartService->applyCoupon($request->input('code'));
            $summary = $this->cartService->getSummary();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('store.cart.coupon_applied'),
                    'summary' => $summary,
                ]);
            }

            return back()->with('success', __('store.cart.coupon_applied'));
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['coupon' => $e->getMessage()]);
        }
    }

    public function removeCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $this->cartService->removeCoupon();
        $summary = $this->cartService->getSummary();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
            ]);
        }

        return back()->with('success', __('store.cart.coupon_removed'));
    }

    public function count(): JsonResponse
    {
        return response()->json([
            'count' => $this->cartService->count(),
        ]);
    }
}
