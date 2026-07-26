<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private MediaService $mediaService
    ) {}

    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::with('parent')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category,
            'parents' => Category::orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $this->mediaService->upload($request->file('image'), 'categories');
            if ($imagePath) {
                $data['image_path'] = $imagePath;
            }
        }

        $category = Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'اتحفظ القسم — تقدّر تضيف منتجات دلوقتي');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::where('id', '!=', $category->id)->orderBy('name_ar')->get(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        
        // Handle image deletion
        if ($request->boolean('delete_image')) {
            if ($category->image_path) {
                $this->mediaService->delete($category->image_path);
            }
            $data['image_path'] = null;
        }
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image_path) {
                $this->mediaService->delete($category->image_path);
            }
            
            // Upload new image
            $imagePath = $this->mediaService->upload($request->file('image'), 'categories');
            if ($imagePath) {
                $data['image_path'] = $imagePath;
            }
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', __('admin.success'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Delete image
        if ($category->image_path) {
            $this->mediaService->delete($category->image_path);
        }
        
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', __('admin.success'));
    }

    protected function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:100'],
            'name_ar' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:categories,slug,'.($category?->id ?? 'NULL')],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'], // 5MB max
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name_en']);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
