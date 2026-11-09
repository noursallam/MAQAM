<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RankController extends Controller
{
    public function index(): View
    {
        return view('admin.ranks.index', [
            'ranks' => Rank::orderBy('min_points')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.ranks.form', ['rank' => new Rank]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['icon_url'] = $this->storeBadge($request, null);

        Rank::create($data);

        return redirect()->route('admin.ranks.index')->with('success', 'Rank created.');
    }

    public function edit(Rank $rank): View
    {
        return view('admin.ranks.form', compact('rank'));
    }

    public function update(Request $request, Rank $rank): RedirectResponse
    {
        $data = $this->validated($request);
        $iconUrl = $this->storeBadge($request, $rank);
        if ($iconUrl !== null) {
            $data['icon_url'] = $iconUrl;
        }

        $rank->update($data);

        return redirect()->route('admin.ranks.index')->with('success', 'Rank updated.');
    }

    public function destroy(Rank $rank): RedirectResponse
    {
        $rank->delete();

        return redirect()->route('admin.ranks.index')->with('success', 'Rank deleted.');
    }

    protected function storeBadge(Request $request, ?Rank $rank): ?string
    {
        $request->validate(['badge_image' => ['nullable', 'image', 'max:1024']]);

        if (! $request->hasFile('badge_image')) {
            return null;
        }

        $slug = str($request->input('name_en', $rank?->name_en ?? 'rank'))
            ->slug()->value();

        $file = $request->file('badge_image');
        $ext = $file->getClientOriginalExtension() ?: 'png';
        $filename = "{$slug}.{$ext}";

        $file->move(public_path('badges'), $filename);

        return "/badges/{$filename}";
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:50'],
            'name_ar' => ['required', 'string', 'max:50'],
            'min_points' => ['required', 'integer', 'min:0'],
            'max_points' => ['nullable', 'integer', 'min:0'],
            'customer_points_per_scan' => ['required', 'integer', 'min:0'],
            'merchant_points_per_scan' => ['required', 'integer', 'min:0'],
            'wheel_win_probability' => ['required', 'numeric', 'min:0', 'max:1'],
            'wheel_cost_points' => ['required', 'integer', 'min:0'],
            'icon_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
