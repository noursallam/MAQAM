<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seminar;
use App\Services\MediaService;
use App\Services\SeminarDrawService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class SeminarController extends Controller
{
    /** Times are typed and shown in Egypt's time; the database keeps UTC like the rest of the app. */
    public const TIMEZONE = 'Africa/Cairo';

    public function __construct(private MediaService $media) {}

    public function index(): View
    {
        return view('admin.seminars.index', [
            'seminars' => Seminar::withCount([
                'registrations',
                'draws',
                'draws as drawn_count' => fn ($q) => $q->whereNotNull('drawn_at'),
            ])->latest('starts_at')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.seminars.form', ['seminar' => new Seminar, 'prizes' => '']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $seminar = DB::transaction(function () use ($data, $request) {
            $seminar = Seminar::create($this->fields($data, $request));
            $this->savePrizes($seminar, $data['prizes']);

            return $seminar;
        });

        return redirect()->route('admin.seminars.show', $seminar)->with('success', __('admin.seminars.created'));
    }

    public function show(Seminar $seminar): View
    {
        return view('admin.seminars.show', [
            'seminar' => $seminar->load('draws.winner'),
            'attendees' => $seminar->registrations()->with('user')->latest()->paginate(50),
            'remaining' => $seminar->draws->whereNull('drawn_at')->count(),
        ]);
    }

    public function edit(Seminar $seminar): View
    {
        return view('admin.seminars.form', [
            'seminar' => $seminar,
            'prizes' => $seminar->draws()->pluck('prize')->implode("\n"),
        ]);
    }

    public function update(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $this->validated($request, $seminar);

        DB::transaction(function () use ($seminar, $data, $request) {
            $seminar->update($this->fields($data, $request, $seminar));

            // Once drawing has begun, the prizes are part of the record and stay as they are
            if (! $seminar->hasDrawn()) {
                $seminar->draws()->delete();
                $this->savePrizes($seminar, $data['prizes']);
            }
        });

        return redirect()->route('admin.seminars.show', $seminar)->with('success', __('admin.seminars.saved'));
    }

    public function destroy(Seminar $seminar): RedirectResponse
    {
        $this->media->delete($seminar->image_path);
        $seminar->delete();

        return redirect()->route('admin.seminars.index')->with('success', __('admin.seminars.deleted'));
    }

    public function draw(Seminar $seminar, SeminarDrawService $draws): RedirectResponse
    {
        try {
            $draw = $draws->drawNext($seminar);
        } catch (RuntimeException $e) {
            return back()->withErrors(['draw' => __('admin.seminars.draw_'.$e->getMessage())]);
        }

        return back()->with('success', __('admin.seminars.drawn', [
            'prize' => $draw->prize,
            'winner' => $draw->winner?->full_name,
        ]));
    }

    public function drawAll(Seminar $seminar, SeminarDrawService $draws): RedirectResponse
    {
        try {
            $drawn = $draws->drawAll($seminar);
        } catch (RuntimeException $e) {
            return back()->withErrors(['draw' => __('admin.seminars.draw_'.$e->getMessage())]);
        }

        return back()->with('success', __('admin.seminars.drawn_all', ['count' => count($drawn)]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Seminar $seminar = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            // One prize per line, in the order they are drawn
            'prizes' => [$seminar?->hasDrawn() ? 'nullable' : 'required', 'string', 'max:20000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fields(array $data, Request $request, ?Seminar $seminar = null): array
    {
        $fields = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'starts_at' => Carbon::parse($data['starts_at'], self::TIMEZONE)->utc(),
            'ends_at' => Carbon::parse($data['ends_at'], self::TIMEZONE)->utc(),
        ];

        if ($request->hasFile('image') && ($path = $this->media->upload($request->file('image'), 'seminars'))) {
            $this->media->delete($seminar?->image_path);
            $fields['image_path'] = $path;
        }

        return $fields;
    }

    private function savePrizes(Seminar $seminar, ?string $prizes): void
    {
        $names = collect(preg_split('/\R/', (string) $prizes))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        foreach ($names as $index => $name) {
            $seminar->draws()->create(['position' => $index + 1, 'prize' => mb_substr($name, 0, 255)]);
        }
    }
}
