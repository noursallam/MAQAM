<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\LinkPageItem;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The page behind the link in every social bio: app downloads, social accounts, other links.
 */
class LinkPageController extends Controller
{
    public function show(): View
    {
        $items = LinkPageItem::visible()->get();
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';

        return view('store.links', [
            'apps' => $items->whereIn('platform', LinkPageItem::APP_STORES)->values(),
            'social' => $items->whereIn('platform', LinkPageItem::SOCIAL)->values(),
            // Social accounts are the round icons; WhatsApp is also a button, since people come to talk
            'links' => $items->reject(fn (LinkPageItem $item) => in_array($item->platform, LinkPageItem::APP_STORES, true)
                || (in_array($item->platform, LinkPageItem::SOCIAL, true) && $item->platform !== 'whatsapp'))->values(),
            'bio' => SystemSetting::getValue("links_bio_{$locale}") ?: __('store.links.default_bio'),
        ]);
    }

    /**
     * Counts the click, then sends the visitor on.
     */
    public function go(LinkPageItem $item): RedirectResponse
    {
        abort_unless($item->is_active, 404);
        $item->increment('clicks');

        return redirect()->away($item->url);
    }
}
