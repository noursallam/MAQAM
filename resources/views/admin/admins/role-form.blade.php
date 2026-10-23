@extends('admin.layouts.app')
@php $editing = $role->exists; @endphp
@section('title', $editing ? __('admin.team.edit_role') : __('admin.team.new_role'))
@section('subtitle', __('admin.team.role_subtitle'))
@section('content')
<form method="POST" action="{{ $editing ? route('admin.admin-roles.update', $role) : route('admin.admin-roles.store') }}"
      class="grid max-w-4xl gap-6 lg:grid-cols-[1fr_1.4fr]">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="ui-card-static h-fit space-y-4 p-6">
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.team.role_name') }} *</label>
            <input name="name" value="{{ old('name', $role->name) }}" required maxlength="100" class="ui-input"
                   placeholder="{{ __('admin.team.role_name_hint') }}">
        </div>
        @if($role->is_system)
            <p class="ui-muted">{{ __('admin.team.role_system_hint') }}</p>
        @endif
        <button class="ui-btn ui-btn-primary w-full">{{ $editing ? __('admin.team.save') : __('admin.team.create_role') }}</button>
    </div>

    <div class="ui-card-static p-6">
        <h3 class="ui-section-title">{{ __('admin.team.access') }}</h3>
        <p class="ui-muted mb-4 mt-1">{{ $role->isSuperAdmin() ? __('admin.team.super_admin_all') : __('admin.team.access_hint') }}</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach($labels as $module => $label)
                @php $checked = in_array($module, old('modules', $role->modules ?? []), true) || $role->isSuperAdmin(); @endphp
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#ECECEF] px-3 py-2.5 transition hover:bg-[#FAFAFB] has-[:checked]:border-maqam-gold has-[:checked]:bg-maqam-tint">
                    <input type="checkbox" name="modules[]" value="{{ $module }}" @checked($checked) @disabled($role->isSuperAdmin())
                           class="h-4 w-4 accent-[#C5A059]">
                    <span class="text-sm">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>
</form>

@if($editing && ! $role->is_system)
    <form method="POST" action="{{ route('admin.admin-roles.destroy', $role) }}" class="mt-6 max-w-4xl"
          onsubmit="return confirm(@js(__('admin.team.delete_role_confirm')))">
        @csrf
        @method('DELETE')
        <button class="ui-btn ui-btn-ghost text-red-700">{{ __('admin.team.delete_role') }}</button>
    </form>
@endif
@endsection
