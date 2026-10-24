<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Every text of the mobile app, by section, with a field per language that replaces it.
 */
class AppTextController extends Controller
{
    public function index(Request $request): View
    {
        $defaults = AppText::defaults();
        $groups = collect($defaults)->keys()->countBy(fn ($key) => AppText::groupOf($key))->sortKeys();
        $overrides = AppText::all()->keyBy('key');
        $search = trim((string) $request->query('q'));
        $group = $search === '' ? ($request->query('group') ?: $groups->keys()->first()) : null;

        $texts = collect($defaults)
            ->filter(function ($text, $key) use ($group, $search, $overrides) {
                if ($search === '') {
                    return AppText::groupOf($key) === $group;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $key, $text['ar'], $text['en'],
                    $overrides[$key]->value_ar ?? '', $overrides[$key]->value_en ?? '',
                ]));

                return str_contains($haystack, mb_strtolower($search));
            });

        return view('admin.app_texts.index', [
            'groups' => $groups,
            'group' => $group,
            'search' => $search,
            'texts' => $texts,
            'overrides' => $overrides,
            'changedCount' => $overrides->filter(fn ($row) => filled($row->value_ar) || filled($row->value_en))->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'texts' => ['required', 'array'],
            'texts.*.ar' => ['nullable', 'string', 'max:2000'],
            'texts.*.en' => ['nullable', 'string', 'max:2000'],
        ]);

        $defaults = AppText::defaults();
        $errors = [];

        foreach ($data['texts'] as $key => $values) {
            $default = $defaults[$key] ?? null;
            if (! $default || $default['plural']) {
                continue;
            }

            // A missing {name} would show the customer a sentence with a hole in it
            foreach (['ar', 'en'] as $locale) {
                $value = trim((string) ($values[$locale] ?? ''));
                foreach ($default['placeholders'] as $placeholder) {
                    if ($value !== '' && ! str_contains($value, '{'.$placeholder.'}')) {
                        $errors["texts.{$key}.{$locale}"] = __('admin.app_texts.missing_placeholder', ['placeholder' => '{'.$placeholder.'}']);
                    }
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($data['texts'] as $key => $values) {
            if (! isset($defaults[$key]) || $defaults[$key]['plural']) {
                continue;
            }

            $ar = trim((string) ($values['ar'] ?? ''));
            $en = trim((string) ($values['en'] ?? ''));

            // Back to the app's own wording when both are cleared (or typed back unchanged)
            $ar = $ar === $defaults[$key]['ar'] ? '' : $ar;
            $en = $en === $defaults[$key]['en'] ? '' : $en;

            if ($ar === '' && $en === '') {
                // Through the model, so the cached payload is dropped too
                AppText::where('key', $key)->first()?->delete();
            } else {
                AppText::updateOrCreate(['key' => $key], ['value_ar' => $ar ?: null, 'value_en' => $en ?: null]);
            }
        }

        return back()->with('success', __('admin.app_texts.saved'));
    }
}
