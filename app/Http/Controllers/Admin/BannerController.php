<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(private MediaService $mediaService) {}

    public function index(): View
    {
        $banners = Banner::query()
            ->mobile()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('admin.banners.index', compact('banners'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title_ar' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $this->mediaService->upload($request->file('image'), 'banners');
        if (! $path) {
            return back()->withErrors(['image' => __('admin.banners.upload_failed')])->withInput();
        }

        Banner::create([
            'slot' => Banner::PLATFORM_MOBILE,
            'title_ar' => $data['title_ar'] ?? null,
            'title_en' => $data['title_en'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => true,
            'image_path' => $path,
        ]);

        return back()->with('success', __('admin.banners.added'));
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        if ($banner->slot !== Banner::PLATFORM_MOBILE) {
            abort(404);
        }

        if ($banner->image_path) {
            $this->mediaService->delete($banner->image_path);
        }

        $banner->delete();

        return back()->with('success', __('admin.banners.deleted'));
    }

    public function toggle(Banner $banner): RedirectResponse
    {
        if ($banner->slot !== Banner::PLATFORM_MOBILE) {
            abort(404);
        }

        $banner->update(['is_active' => ! $banner->is_active]);

        return back()->with('success', __('admin.banners.saved'));
    }
}
