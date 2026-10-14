@extends('admin.layouts.app')

@section('title', __('admin.audience.title'))
@section('subtitle', __('admin.audience.subtitle'))

@section('actions')
<a href="{{ route('admin.audience.export', request()->query()) }}" class="ui-btn ui-btn-ghost">{{ __('admin.audience.export') }}</a>
@endsection

@section('content')
@php
    $permStates = ['granted', 'denied', 'prompt', 'unsupported', 'none'];
    $permColors = [
        'granted' => '#059669',
        'denied' => '#DC2626',
        'prompt' => '#C4A574',
        'unsupported' => '#9CA3AF',
        'none' => '#E4E0D7',
    ];
    $permBadge = [
        'granted' => 'ui-badge-ok',
        'denied' => 'ui-badge-danger',
        'prompt' => 'ui-badge-warn',
        'unsupported' => 'ui-badge-muted',
        'none' => 'ui-badge-muted',
    ];
    $maxCountry = max(1, (int) $countries->max('total'));
    $maxRegion = max(1, (int) $regions->max('total'));
    $maxShares = max(1, (int) $sharesPerDay->max('value'));
    $hasFilters = collect($filters)->filter()->isNotEmpty();
@endphp

{{-- KPIs --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.audience.kpi_located') }}</div>
        <div class="ui-kpi-value">{{ number_format($stats['located']) }}</div>
        <div class="ui-kpi-hint">{{ __('admin.audience.rate_of_total', ['rate' => $stats['located_rate'], 'total' => number_format($stats['total'])]) }}</div>
    </div>
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.audience.kpi_notifying') }}</div>
        <div class="ui-kpi-value">{{ number_format($stats['notifying']) }}</div>
        <div class="ui-kpi-hint">{{ __('admin.audience.rate_of_total', ['rate' => $stats['notifying_rate'], 'total' => number_format($stats['total'])]) }} · {{ __('admin.audience.web_push_devices', ['count' => number_format($stats['web_push'])]) }}</div>
    </div>
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.audience.kpi_countries') }}</div>
        <div class="ui-kpi-value">{{ number_format($stats['countries']) }}</div>
        <div class="ui-kpi-hint">{{ __('admin.audience.kpi_regions', ['count' => $regions->count(), 'country' => \App\Support\GeoNames::country($regionCountry)]) }}</div>
    </div>
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.audience.kpi_not_asked') }}</div>
        <div class="ui-kpi-value">{{ number_format($stats['not_asked']) }}</div>
        <div class="ui-kpi-hint">{{ __('admin.audience.kpi_not_asked_hint') }}</div>
    </div>
</div>

{{-- Where people are --}}
<div class="mt-5 grid gap-5 xl:grid-cols-2">
    <div class="ui-card-static p-6">
        <h2 class="ui-section-title">{{ __('admin.audience.by_country') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse($countries->take(10) as $row)
                <a href="{{ route('admin.audience.index', ['country' => $row['code']]) }}" class="block rounded-lg px-1 py-0.5 transition hover:bg-[#F9F8F5]">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium">{{ $row['flag'] }} {{ $row['label'] }}</span>
                        <span class="text-maqam-muted">{{ number_format($row['total']) }} · {{ $stats['located'] ? round($row['total'] * 100 / $stats['located']) : 0 }}%</span>
                    </div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-[#F3F1EB]">
                        <div class="h-full rounded-full bg-maqam-navy" style="width: {{ max(2, $row['total'] * 100 / $maxCountry) }}%"></div>
                    </div>
                </a>
            @empty
                <p class="ui-muted">{{ __('admin.audience.no_locations') }}</p>
            @endforelse
        </div>
    </div>

    <div class="ui-card-static p-6">
        <h2 class="ui-section-title">{{ __('admin.audience.by_region', ['country' => \App\Support\GeoNames::country($regionCountry)]) }}</h2>
        <div class="mt-4 grid gap-x-6 gap-y-3 sm:grid-cols-2">
            @forelse($regions as $row)
                @php $regionLink = $row['code'] ? route('admin.audience.index', ['country' => $regionCountry, 'region' => $row['code']]) : null; @endphp
                <a @if($regionLink) href="{{ $regionLink }}" @endif class="block rounded-lg px-1 py-0.5 transition hover:bg-[#F9F8F5]">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium">{{ $row['label'] }}</span>
                        <span class="text-maqam-muted">{{ number_format($row['total']) }}</span>
                    </div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-[#F3F1EB]">
                        <div class="h-full rounded-full bg-maqam-gold" style="width: {{ max(2, $row['total'] * 100 / $maxRegion) }}%"></div>
                    </div>
                </a>
            @empty
                <p class="ui-muted sm:col-span-2">{{ __('admin.audience.no_locations') }}</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Permissions + trend --}}
<div class="mt-5 grid gap-5 xl:grid-cols-2">
    <div class="ui-card-static p-6">
        <h2 class="ui-section-title">{{ __('admin.audience.permissions') }}</h2>
        @foreach($permissionBreakdown as $kind => $counts)
            @php $sum = max(1, array_sum($counts)); @endphp
            <div class="mt-5">
                <div class="mb-2 text-sm font-medium">{{ __('admin.audience.perm_kind_'.$kind) }}</div>
                <div class="flex h-3 overflow-hidden rounded-full bg-[#F3F1EB]" role="img"
                     aria-label="{{ collect($counts)->map(fn ($n, $s) => __('admin.audience.state_'.$s).': '.$n)->implode(', ') }}">
                    @foreach($counts as $state => $n)
                        @if($n > 0)
                            <div style="width: {{ $n * 100 / $sum }}%; background: {{ $permColors[$state] }}" title="{{ __('admin.audience.state_'.$state) }}: {{ $n }}"></div>
                        @endif
                    @endforeach
                </div>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-maqam-muted">
                    @foreach($counts as $state => $n)
                        <a href="{{ route('admin.audience.index', [$kind => $state]) }}" class="inline-flex items-center gap-1.5 hover:text-maqam-ink">
                            <span class="inline-block h-2 w-2 rounded-full" style="background: {{ $permColors[$state] }}"></span>
                            {{ __('admin.audience.state_'.$state) }} <span class="font-semibold text-maqam-ink">{{ number_format($n) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="ui-card-static p-6">
        <div class="flex items-baseline justify-between gap-3">
            <h2 class="ui-section-title">{{ __('admin.audience.shares_trend') }}</h2>
            <span class="text-xs text-maqam-muted">{{ __('admin.audience.last_14_days') }} · {{ number_format($sharesPerDay->sum('value')) }}</span>
        </div>
        <div class="mt-5 flex h-40 items-end gap-1.5" role="img" aria-label="{{ __('admin.audience.shares_trend') }}">
            @foreach($sharesPerDay as $day)
                <div class="flex h-full flex-1 flex-col items-center justify-end gap-1" title="{{ $day['label'] }}: {{ $day['value'] }}">
                    @if($day['value'] > 0)
                        <span class="text-[10px] font-semibold text-maqam-muted">{{ $day['value'] }}</span>
                    @endif
                    <div class="w-full rounded-t bg-maqam-navy" style="height: {{ $day['value'] ? max(4, $day['value'] * 100 / $maxShares) : 1 }}%; {{ $day['value'] ? '' : 'opacity:.15' }}"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex justify-between text-[10px] text-maqam-muted">
            <span>{{ $sharesPerDay->first()['label'] }}</span>
            <span>{{ $sharesPerDay->last()['label'] }}</span>
        </div>
    </div>
</div>

{{-- People --}}
<div class="mt-6">
    <form class="ui-toolbar flex-wrap" method="GET">
        <input name="q" value="{{ $filters['q'] }}" placeholder="{{ __('admin.audience.search') }}" class="ui-input max-w-xs">
        <select name="country" class="ui-select max-w-[12rem]" onchange="this.form.region && (this.form.region.value=''); this.form.submit()">
            <option value="">{{ __('admin.audience.all_countries') }}</option>
            @foreach($countryOptions as $code => $label)
                <option value="{{ $code }}" @selected($filters['country'] === $code)>{{ $label }}</option>
            @endforeach
        </select>
        @if($regionOptions->isNotEmpty())
            <select name="region" class="ui-select max-w-[12rem]" onchange="this.form.submit()">
                <option value="">{{ __('admin.audience.all_regions') }}</option>
                @foreach($regionOptions as $code => $label)
                    <option value="{{ $code }}" @selected($filters['region'] === $code)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
        @foreach(['location', 'notification'] as $kind)
            <select name="{{ $kind }}" class="ui-select max-w-[12rem]" onchange="this.form.submit()">
                <option value="">{{ __('admin.audience.perm_kind_'.$kind) }}: {{ __('admin.audience.any') }}</option>
                @foreach($permStates as $state)
                    <option value="{{ $state }}" @selected($filters[$kind] === $state)>{{ __('admin.audience.perm_kind_'.$kind) }}: {{ __('admin.audience.state_'.$state) }}</option>
                @endforeach
            </select>
        @endforeach
        <button class="ui-btn ui-btn-dark">{{ __('admin.audience.filter') }}</button>
        @if($hasFilters)
            <a href="{{ route('admin.audience.index') }}" class="ui-btn ui-btn-ghost">{{ __('admin.audience.clear') }}</a>
        @endif
    </form>

    <div class="ui-table-wrap">
        <div class="overflow-x-auto">
            <table class="ui-table">
                <thead>
                <tr>
                    <th>{{ __('admin.audience.col_person') }}</th>
                    <th>{{ __('admin.audience.col_country') }}</th>
                    <th>{{ __('admin.audience.col_region') }}</th>
                    <th>{{ __('admin.audience.col_city') }}</th>
                    <th>{{ __('admin.audience.perm_kind_location') }}</th>
                    <th>{{ __('admin.audience.perm_kind_notification') }}</th>
                    <th>{{ __('admin.audience.col_seen') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($people as $person)
                    @php
                        $loc = $person->latestLocation;
                        $locState = $person->location_permission ?? 'none';
                        $notifState = $person->notification_permission ?? 'none';
                    @endphp
                    <tr>
                        <td>
                            @if($person->customer)
                                <a href="{{ route('admin.customers.show', $person->customer) }}" class="font-medium hover:underline">{{ $person->full_name }}</a>
                            @else
                                <span class="font-medium">{{ $person->full_name }}</span>
                            @endif
                            <div class="ui-muted text-xs" dir="ltr">{{ $person->phone_number }}</div>
                        </td>
                        <td>
                            @if($loc?->country_code)
                                {{ \App\Support\GeoNames::flag($loc->country_code) }} {{ $loc->countryLabel() }}
                            @elseif($loc)
                                <span class="ui-muted">{{ __('admin.audience.locating') }}</span>
                            @else
                                <span class="ui-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $loc?->regionLabel() ?? '—' }}</td>
                        <td>{{ $loc?->city ?? '—' }}</td>
                        <td><span class="ui-badge {{ $permBadge[$locState] }}">{{ __('admin.audience.state_'.$locState) }}</span></td>
                        <td><span class="ui-badge {{ $permBadge[$notifState] }}">{{ __('admin.audience.state_'.$notifState) }}</span></td>
                        <td class="whitespace-nowrap text-xs text-maqam-muted" title="{{ $person->permissions_reported_at?->toDateTimeString() }}">
                            {{ $loc?->updated_at?->diffForHumans() ?? $person->permissions_reported_at?->diffForHumans() ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap">
                            @if($loc)
                                <a href="{{ $loc->mapUrl() }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-maqam-gold-dark hover:underline"
                                   title="{{ $loc->accuracy_m ? __('admin.audience.accuracy', ['m' => number_format($loc->accuracy_m)]) : '' }}">{{ __('admin.audience.open_map') }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center">
                            <p class="font-semibold">{{ __('admin.empty_title') }}</p>
                            <p class="ui-muted mt-1">{{ $hasFilters ? __('admin.audience.empty_filtered') : __('admin.audience.empty') }}</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $people->links() }}</div>
</div>
@endsection
