@extends('admin.layouts.app')
@section('title', __('admin.settings.rbac_title'))
@section('subtitle', __('admin.team.subtitle'))
@section('actions')
<a href="{{ route('admin.admin-roles.create') }}" class="ui-btn ui-btn-ghost">{{ __('admin.team.new_role') }}</a>
<a href="{{ route('admin.admins.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.team.new_admin') }}</a>
@endsection
@section('content')
{{-- The team --}}
<div class="ui-table-wrap mb-8">
    <div class="flex items-center justify-between px-5 pt-5">
        <h2 class="ui-section-title">{{ __('admin.team.admins') }}</h2>
        <span class="ui-muted">{{ __('admin.team.admins_count', ['count' => $admins->count()]) }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="ui-table mt-3">
            <thead>
                <tr>
                    <th>{{ __('admin.team.name') }}</th>
                    <th>{{ __('admin.team.role') }}</th>
                    <th>{{ __('admin.team.status') }}</th>
                    <th>{{ __('admin.team.last_seen') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $admin)
                    @php $role = $roles->firstWhere('key', $admin->role); @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-maqam-tint text-sm font-bold text-maqam-gold-dark">
                                    {{ mb_substr($admin->user?->full_name ?? '?', 0, 1) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="font-semibold">
                                        {{ $admin->user?->full_name }}
                                        @if($admin->user_id === auth()->id())
                                            <span class="ui-badge ui-badge-muted ms-1">{{ __('admin.team.you') }}</span>
                                        @endif
                                    </div>
                                    <div class="ui-muted" dir="ltr">{{ $admin->user?->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="ui-badge ui-badge-gold">{{ $role?->name ?? $admin->role }}</span></td>
                        <td>
                            <span class="ui-badge {{ $admin->user?->is_active ? 'ui-badge-ok' : 'ui-badge-muted' }}">
                                {{ $admin->user?->is_active ? __('admin.team.active') : __('admin.team.disabled') }}
                            </span>
                        </td>
                        <td class="ui-muted">{{ $admin->user?->last_login_at?->diffForHumans() ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.admins.edit', $admin) }}" class="ui-btn ui-btn-ghost !px-3 !py-1.5 text-xs">{{ __('admin.team.edit') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Roles and what each one opens --}}
<div class="mb-3 flex items-center justify-between">
    <h2 class="ui-section-title">{{ __('admin.team.roles') }}</h2>
    <span class="ui-muted">{{ __('admin.team.roles_hint') }}</span>
</div>
<div class="ui-table-wrap">
    <div class="overflow-x-auto">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>{{ __('admin.team.module') }}</th>
                    @foreach($roles as $role)
                        <th class="text-center">
                            <a href="{{ route('admin.admin-roles.edit', $role) }}" class="inline-flex flex-col items-center gap-0.5 hover:text-maqam-ink">
                                <span class="font-semibold text-maqam-ink">{{ $role->name }}</span>
                                <span class="text-[11px]">{{ __('admin.team.admins_count', ['count' => $role->admins_count]) }}</span>
                            </a>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($labels as $module => $label)
                    <tr>
                        <td class="font-medium">{{ $label }}</td>
                        @foreach($roles as $role)
                            <td class="text-center">
                                @if(in_array($module, $matrix[$role->key] ?? [], true))
                                    <svg class="mx-auto h-4 w-4 text-[#16A34A]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <span class="mx-auto block h-1.5 w-1.5 rounded-full bg-[#E6E6EA]"></span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
