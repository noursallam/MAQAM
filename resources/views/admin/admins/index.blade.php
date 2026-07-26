@extends('admin.layouts.app')
@section('title', __('admin.settings.rbac_title'))
@section('subtitle', __('admin.settings.rbac_subtitle'))
@section('content')
<div class="mb-8 ui-table-wrap">
    <div class="overflow-x-auto">
        <table class="ui-table">
            <thead>
            <tr>
                <th>الوحدة</th>
                @foreach(array_keys($matrix) as $role)
                    <th class="text-center">{{ __('admin.settings.'.$role) }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @php
                $modules = ['dashboard','orders','merchants','customers','qr','loyalty','commerce','notifications','risk','settings','admins','coupons'];
                $labels = [
                    'dashboard' => __('admin.nav.command_center'),
                    'orders' => __('admin.nav.orders_pipeline'),
                    'merchants' => __('admin.nav.merchants'),
                    'customers' => __('admin.nav.customers'),
                    'qr' => __('admin.nav.qr_factory'),
                    'loyalty' => __('admin.nav.loyalty'),
                    'commerce' => __('admin.nav.commerce'),
                    'notifications' => __('admin.nav.communications'),
                    'risk' => __('admin.nav.risk_desk'),
                    'settings' => __('admin.nav.settings'),
                    'admins' => __('admin.nav.admins_rbac'),
                    'coupons' => __('admin.nav.coupons'),
                ];
            @endphp
            @foreach($modules as $mod)
                <tr>
                    <td class="font-medium">{{ $labels[$mod] }}</td>
                    @foreach($matrix as $perms)
                        <td class="text-center">
                            @if(in_array($mod, $perms, true))
                                <span class="inline-block h-2.5 w-2.5 rounded-full bg-maqam-gold"></span>
                            @else
                                <span class="inline-block h-2.5 w-2.5 rounded-full bg-[#D8D4CB]"></span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach($admins as $admin)
        <div class="ui-card-static p-5">
            <div class="font-semibold">{{ $admin->user?->full_name }}</div>
            <div class="mt-1 ui-muted">{{ $admin->user?->email }}</div>
            <div class="mt-3"><span class="ui-badge ui-badge-gold">{{ __('admin.settings.'.$admin->role) }}</span></div>
        </div>
    @endforeach
</div>
@endsection
