<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryPrize;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrizeCategoryController extends Controller
{
    public function __construct(
        private MediaService $mediaService
    ) {}

    public function index(): View
    {
        return view('admin.prize-categories.index', [
            'categories' => CategoryPrize::latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.prize-categories.form', [
            'category' => new CategoryPrize(['background_color' => '#22C55E', 'category_type' => 'gift', 'points_value' => 10]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $this->mediaService->upload($request->file('image'), 'prize-categories');
            if ($imagePath) {
                $data['image_path'] = $imagePath;
            }
        }

        CategoryPrize::create($data);

        return redirect()->route('admin.prize-categories.index')->with('success', __('admin.success'));
    }

    public function edit(CategoryPrize $prize_category): View
    {
        return view('admin.prize-categories.form', [
            'category' => $prize_category,
        ]);
    }

    public function update(Request $request, CategoryPrize $prize_category): RedirectResponse
    {
        $data = $this->validated($request);
        
        // Handle image deletion
        if ($request->boolean('delete_image')) {
            if ($prize_category->image_path) {
                $this->mediaService->delete($prize_category->image_path);
            }
            $data['image_path'] = null;
        }
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($prize_category->image_path) {
                $this->mediaService->delete($prize_category->image_path);
            }
            
            // Upload new image
            $imagePath = $this->mediaService->upload($request->file('image'), 'prize-categories');
            if ($imagePath) {
                $data['image_path'] = $imagePath;
            }
        }

        $prize_category->update($data);

        return redirect()->route('admin.prize-categories.index')->with('success', __('admin.success'));
    }

    public function destroy(CategoryPrize $prize_category): RedirectResponse
    {
        // Delete image
        if ($prize_category->image_path) {
            $this->mediaService->delete($prize_category->image_path);
        }
        
        $prize_category->delete();

        return redirect()->route('admin.prize-categories.index')->with('success', __('admin.success'));
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:100'],
            'name_ar' => ['required', 'string', 'max:100'],
            'category_type' => ['required', 'in:standard,gift'],
            'points_value' => ['required', 'integer', 'min:0'],
            'background_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'], // 5MB max
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['background_color'] = strtoupper($data['background_color']);

        return $data;
    }
}
