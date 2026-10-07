<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends ApiController
{
    public function banners(): JsonResponse
    {
        return $this->ok(Banner::mobile()->active()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'title' => app()->getLocale() === 'en' ? ($banner->title_en ?: $banner->title_ar) : $banner->title_ar,
                'title_ar' => $banner->title_ar,
                'title_en' => $banner->title_en,
                'image_url' => $banner->imageUrl(),
                'link_url' => $banner->link_url,
                'sort_order' => $banner->sort_order,
            ])->values());
    }

    public function categories(): JsonResponse
    {
        return $this->ok(Category::where('is_active', true)->orderBy('id')->get()->map(ApiPresenter::category(...))->values());
    }

    public function products(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc'])],
        ]);

        $page = Product::with(['category', 'thumbnail', 'options'])
            ->where('is_active', true)
            ->when($validated['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($validated['search'] ?? null, function ($q, $search) {
                // Escape LIKE wildcards so the term is matched literally
                $term = '%'.addcslashes($search, '%_\\').'%';
                $q->where(fn ($w) => $w->where('name_ar', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('sku', 'like', $term));
            })
            ->when(true, fn ($q) => match ($validated['sort'] ?? 'newest') {
                'price_asc' => $q->orderBy('price'),
                'price_desc' => $q->orderByDesc('price'),
                default => $q->latest('id'),
            })
            ->paginate($this->perPage($request));

        return $this->paginated($page, ApiPresenter::productSummary(...));
    }

    public function product(int $id): JsonResponse
    {
        $product = Product::with(['category', 'thumbnail', 'images', 'colors', 'options'])
            ->where('is_active', true)
            ->findOrFail($id);

        return $this->ok(ApiPresenter::product($product));
    }

    /**
     * Settings the admin marked as public (shipping cost, support contacts, ...).
     */
    public function settings(): JsonResponse
    {
        return $this->ok(SystemSetting::where('is_public', true)->pluck('value', 'key'));
    }
}
