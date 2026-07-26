<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private MediaService $mediaService
    ) {}

    public function index(Request $request): View
    {
        $products = Product::with(['category', 'thumbnail', 'images'])
            ->when($request->q, fn ($q, $term) => $q->where('name_en', 'like', "%{$term}%")->orWhere('name_ar', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"))
            ->when($request->low_stock, fn ($q) => $q->where('stock_quantity', '<', 50))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product,
            'categories' => Category::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        
        // Handle main image upload
        if ($request->hasFile('image')) {
            $imagePath = $this->mediaService->upload($request->file('image'), 'products');
            if ($imagePath) {
                $data['image_path'] = $imagePath;
            }
        }
        
        $files = $request->file('images', []);

        if (count($files) < 1 && empty($data['image_path'])) {
            return back()->withErrors(['images' => __('admin.commerce.images_required')])->withInput();
        }

        $thumbnailIndex = (int) $request->input('thumbnail_index', 0);
        if ($thumbnailIndex < 0 || $thumbnailIndex >= count($files)) {
            $thumbnailIndex = 0;
        }

        DB::transaction(function () use ($data, $files, $thumbnailIndex) {
            $product = Product::create($data);

            foreach ($files as $i => $file) {
                $path = $file->store('products/'.$product->id, 'public');
                $isThumb = $i === $thumbnailIndex;

                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'is_thumbnail' => $isThumb,
                    'sort_order' => $i,
                ]);

                if ($isThumb && empty($product->image_path)) {
                    $product->update(['image_path' => $path]);
                }
            }
        });

        return redirect()->route('admin.products.index')->with('success', __('admin.commerce.product_saved'));
    }

    public function edit(Product $product): View
    {
        $product->load('images');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        
        // Handle main image deletion
        if ($request->boolean('delete_image')) {
            if ($product->image_path) {
                $this->mediaService->delete($product->image_path);
            }
            $data['image_path'] = null;
        }
        
        // Handle main image upload
        if ($request->hasFile('image')) {
            // Delete old main image if exists
            if ($product->image_path) {
                $this->mediaService->delete($product->image_path);
            }
            
            // Upload new main image
            $imagePath = $this->mediaService->upload($request->file('image'), 'products');
            if ($imagePath) {
                $data['image_path'] = $imagePath;
            }
        }
        
        $newFiles = $request->file('images', []) ?? [];
        $removeIds = collect($request->input('remove_images', []))->map(fn ($id) => (int) $id)->all();
        $keepCount = $product->images()->whereNotIn('id', $removeIds)->count();
        $totalAfter = $keepCount + count($newFiles);

        if ($totalAfter < 1 && empty($data['image_path'])) {
            return back()->withErrors(['images' => __('admin.commerce.images_required')])->withInput();
        }

        $thumbnailSource = $request->input('thumbnail_source', 'existing'); // existing|new
        $thumbnailExistingId = $request->integer('thumbnail_existing_id') ?: null;
        $thumbnailNewIndex = (int) $request->input('thumbnail_index', 0);

        DB::transaction(function () use ($product, $data, $newFiles, $removeIds, $thumbnailSource, $thumbnailExistingId, $thumbnailNewIndex) {
            $product->update($data);

            if ($removeIds) {
                $toDelete = $product->images()->whereIn('id', $removeIds)->get();
                foreach ($toDelete as $img) {
                    Storage::disk('public')->delete($img->path);
                    $img->delete();
                }
            }

            $created = [];
            foreach ($newFiles as $i => $file) {
                $path = $file->store('products/'.$product->id, 'public');
                $created[] = ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'is_thumbnail' => false,
                    'sort_order' => 1000 + $i,
                ]);
            }

            $product->images()->update(['is_thumbnail' => false]);

            $thumb = null;
            if ($thumbnailSource === 'new' && isset($created[$thumbnailNewIndex])) {
                $thumb = $created[$thumbnailNewIndex];
            } elseif ($thumbnailExistingId) {
                $thumb = $product->images()->where('id', $thumbnailExistingId)->first();
            }

            if (! $thumb) {
                $thumb = $product->images()->orderBy('sort_order')->orderBy('id')->first();
            }

            if ($thumb && empty($product->image_path)) {
                $thumb->update(['is_thumbnail' => true]);
                $product->update(['image_path' => $thumb->path]);
            }
        });

        return redirect()->route('admin.products.index')->with('success', __('admin.commerce.product_saved'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Delete main image
        if ($product->image_path) {
            $this->mediaService->delete($product->image_path);
        }
        
        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->path);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', __('admin.success'));
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'max:5120'], // 5MB max
            'is_active' => ['nullable', 'boolean'],
            'images' => [$product ? 'nullable' : 'required', 'array', $product ? 'min:0' : 'min:1'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'thumbnail_index' => ['nullable', 'integer', 'min:0'],
            'thumbnail_source' => ['nullable', 'in:existing,new'],
            'thumbnail_existing_id' => ['nullable', 'integer'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['image'], $data['images'], $data['thumbnail_index'], $data['thumbnail_source'], $data['thumbnail_existing_id'], $data['remove_images']);

        return $data;
    }
}
