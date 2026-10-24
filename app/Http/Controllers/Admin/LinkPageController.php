<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LinkPageItem;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LinkPageController extends Controller
{
    public function index(): View
    {
        return view('admin.links.index', [
            'items' => LinkPageItem::orderBy('sort_order')->orderBy('id')->get(),
            'bioAr' => SystemSetting::getValue('links_bio_ar'),
            'bioEn' => SystemSetting::getValue('links_bio_en'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LinkPageItem::create($this->validated($request) + [
            'sort_order' => (int) LinkPageItem::max('sort_order') + 1,
            'is_active' => true,
        ]);

        return back()->with('success', __('admin.links.added'));
    }

    public function update(Request $request, LinkPageItem $link): RedirectResponse
    {
        $link->update($this->validated($request) + [
            'sort_order' => (int) $request->integer('sort_order', $link->sort_order),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', __('admin.links.saved'));
    }

    public function destroy(LinkPageItem $link): RedirectResponse
    {
        $link->delete();

        return back()->with('success', __('admin.links.deleted'));
    }

    public function bio(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bio_ar' => ['nullable', 'string', 'max:300'],
            'bio_en' => ['nullable', 'string', 'max:300'],
        ]);

        foreach (['ar', 'en'] as $locale) {
            SystemSetting::updateOrCreate(
                ['key' => "links_bio_{$locale}"],
                ['value' => $data["bio_{$locale}"] ?? '', 'group' => 'links', 'description' => 'Links page bio', 'is_public' => false],
            );
        }

        return back()->with('success', __('admin.links.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'platform' => ['required', Rule::in(LinkPageItem::PLATFORMS)],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'label_en' => ['nullable', 'string', 'max:120'],
            // Web links and app links (whatsapp://, tel:) alike, but never javascript:
            'url' => ['required', 'string', 'max:1000', 'regex:/^(https?:\/\/|mailto:|tel:)/i'],
        ]);
    }
}
