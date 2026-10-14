<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use App\Models\UserLocation;
use App\Support\GeoNames;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Where signed-in visitors are, and what they let the website do.
 */
class AudienceController extends Controller
{
    public const PERMISSION_FILTERS = ['granted', 'denied', 'prompt', 'unsupported', 'none'];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $people = $this->filtered($filters)
            ->with(['latestLocation', 'customer.rank'])
            ->orderByDesc('permissions_reported_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $total = $this->people()->count();
        $located = $this->people()->whereNotNull('latest_location_id')->count();
        $notifying = $this->people()->where('notification_permission', 'granted')->count();

        $stats = [
            'total' => $total,
            'located' => $located,
            'located_rate' => $this->rate($located, $total),
            'notifying' => $notifying,
            'notifying_rate' => $this->rate($notifying, $total),
            'web_push' => DeviceToken::where('platform', 'web')->distinct()->count('user_id'),
            'not_asked' => $this->people()->whereNull('permissions_reported_at')->count(),
            'countries' => $this->latestLocations()->whereNotNull('user_locations.country_code')->distinct()->count('user_locations.country_code'),
        ];

        $countries = $this->latestLocations()
            ->whereNotNull('user_locations.country_code')
            ->select('user_locations.country_code', DB::raw('MAX(user_locations.country_name) as country_name'), DB::raw('COUNT(*) as total'))
            ->groupBy('user_locations.country_code')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->country_code,
                'label' => GeoNames::country($row->country_code, $row->country_name),
                'flag' => GeoNames::flag($row->country_code),
                'total' => (int) $row->total,
            ]);

        // Governorates of the chosen country, Egypt unless the admin picked another
        $regionCountry = $filters['country'] ?: 'EG';
        $regions = $this->latestLocations()
            ->where('user_locations.country_code', $regionCountry)
            ->select(
                DB::raw("COALESCE(user_locations.region_code, user_locations.region_name, '') as region_key"),
                DB::raw('MAX(user_locations.region_code) as region_code'),
                DB::raw('MAX(user_locations.region_name) as region_name'),
                DB::raw('COUNT(*) as total'),
            )
            ->groupBy('region_key')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->region_code,
                'label' => GeoNames::region($row->region_code, $row->region_name) ?: __('admin.audience.unknown_region'),
                'total' => (int) $row->total,
            ]);

        $permissionBreakdown = [
            'location' => $this->breakdown('location_permission'),
            'notification' => $this->breakdown('notification_permission'),
        ];

        $days = collect(range(13, 0))->map(fn ($i) => Carbon::today()->subDays($i));
        $sharesByDay = UserLocation::where('created_at', '>=', $days->first())
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(DISTINCT user_id) as total'))
            ->groupBy('day')
            ->pluck('total', 'day');
        $sharesPerDay = $days->map(fn (Carbon $day) => [
            'label' => $day->translatedFormat('j M'),
            'value' => (int) ($sharesByDay[$day->toDateString()] ?? 0),
        ]);

        $countryOptions = $countries->pluck('label', 'code');
        $regionOptions = $filters['country']
            ? $regions->filter(fn ($r) => $r['code'])->pluck('label', 'code')
            : collect();

        return view('admin.audience.index', compact(
            'people', 'stats', 'countries', 'regions', 'regionCountry', 'permissionBreakdown',
            'sharesPerDay', 'filters', 'countryOptions', 'regionOptions',
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($this->filters($request))->with('latestLocation')->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            // Lets Excel read Arabic names correctly
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'id', 'name', 'phone', 'country', 'governorate', 'city', 'latitude', 'longitude', 'accuracy_m',
                'location_permission', 'notification_permission', 'location_saved_at', 'reported_at',
            ]);

            $query->chunk(500, function ($users) use ($out) {
                foreach ($users as $user) {
                    $location = $user->latestLocation;
                    fputcsv($out, [
                        $user->id,
                        $user->full_name,
                        $user->phone_number,
                        $location?->countryLabel(),
                        $location?->regionLabel(),
                        $location?->city,
                        $location?->latitude,
                        $location?->longitude,
                        $location?->accuracy_m,
                        $user->location_permission ?? 'none',
                        $user->notification_permission ?? 'none',
                        $location?->updated_at?->toDateTimeString(),
                        $user->permissions_reported_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, 'maqam-audience-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{q: string, country: string, region: string, location: string, notification: string}
     */
    private function filters(Request $request): array
    {
        $permission = fn (string $key) => in_array($request->query($key), self::PERMISSION_FILTERS, true) ? $request->query($key) : '';

        return [
            'q' => trim((string) $request->query('q', '')),
            'country' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $request->query('country', '')), 0, 2)),
            'region' => substr(preg_replace('/[^A-Za-z0-9-]/', '', (string) $request->query('region', '')), 0, 10),
            'location' => $permission('location'),
            'notification' => $permission('notification'),
        ];
    }

    private function filtered(array $filters): Builder
    {
        return $this->people()
            ->when($filters['q'] !== '', function (Builder $q) use ($filters) {
                $term = '%'.addcslashes($filters['q'], '%_\\').'%';
                $q->where(fn (Builder $w) => $w->where('full_name', 'like', $term)->orWhere('phone_number', 'like', $term));
            })
            ->when($filters['country'] !== '', fn (Builder $q) => $q->whereHas('latestLocation', fn (Builder $l) => $l->where('country_code', $filters['country'])))
            ->when($filters['region'] !== '', fn (Builder $q) => $q->whereHas('latestLocation', fn (Builder $l) => $l->where('region_code', $filters['region'])))
            ->when($filters['location'] !== '', fn (Builder $q) => $this->wherePermission($q, 'location_permission', $filters['location']))
            ->when($filters['notification'] !== '', fn (Builder $q) => $this->wherePermission($q, 'notification_permission', $filters['notification']));
    }

    private function wherePermission(Builder $query, string $column, string $value): Builder
    {
        return $value === 'none' ? $query->whereNull($column) : $query->where($column, $value);
    }

    /**
     * Website accounts that can answer the prompt; staff are left out.
     */
    private function people(): Builder
    {
        return User::query()->where('role', '!=', 'admin');
    }

    /**
     * Each person's most recent location, one row per person.
     */
    private function latestLocations()
    {
        return DB::table('users')
            ->join('user_locations', 'user_locations.id', '=', 'users.latest_location_id')
            ->where('users.role', '!=', 'admin');
    }

    /**
     * @return array<string, int> keyed by granted / denied / prompt / unsupported / none
     */
    private function breakdown(string $column): array
    {
        $counts = $this->people()
            ->select(DB::raw("COALESCE({$column}, 'none') as state"), DB::raw('COUNT(*) as total'))
            ->groupBy('state')
            ->pluck('total', 'state');

        return collect(self::PERMISSION_FILTERS)
            ->mapWithKeys(fn ($state) => [$state => (int) ($counts[$state] ?? 0)])
            ->all();
    }

    private function rate(int $part, int $total): int
    {
        return $total > 0 ? (int) round($part * 100 / $total) : 0;
    }
}
