<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $threshold = (int) (SystemSetting::where('key', 'low_stock_threshold')->value('value') ?? 50);

        $products = Product::with('category')
            ->orderBy('stock_quantity')
            ->paginate(24);

        $low = Product::where('stock_quantity', '<', $threshold)->where('stock_quantity', '>', 0)->count();
        $out = Product::where('stock_quantity', '<=', 0)->count();

        return view('admin.inventory.index', compact('products', 'threshold', 'low', 'out'));
    }

    public function adjust(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $product->update(['stock_quantity' => $data['stock_quantity']]);

        return back()->with('success', __('admin.commerce.adjust_stock').': '.$product->name_ar);
    }
}
