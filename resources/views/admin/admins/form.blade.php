@extends('admin.layouts.app')
@php
    $editing = $admin->exists;
    $isSelf = $editing && $admin->user_id === auth()->id();
@endphp
@section('title', $editing ? __('admin.team.edit_admin') : __('admin.team.new_admin'))
@section('subtitle', __('admin.team.admin_subtitle'))
@section('content')
<form method="POST" action="{{ $editing ? route('admin.admins.update', $admin) : route('admin.admins.store') }}"
      class="ui-card-static max-w-xl space-y-4 p-6">
    @csrf
    @if($editing) @method('PUT') @endif

    <div>
        <label class="mb-1 block text-sm font-medium">{{ __('admin.team.name') }} *</label>
        <input name="full_name" value="{{ old('full_name', $admin->user?->full_name) }}" required maxlength="255" class="ui-input">
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium">{{ __('admin.team.email') }} *</label>
        <input type="email" name="email" value="{{ old('email', $admin->user?->email) }}" required class="ui-input" dir="ltr" autocomplete="off">
        <p class="ui-muted mt-1">{{ __('admin.team.email_hint') }}</p>
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium">{{ __('admin.team.password') }} {{ $editing ? '' : '*' }}</label>
        <input type="password" name="password" @required(! $editing) minlength="10" class="ui-input" dir="ltr" autocomplete="new-password">
        <p class="ui-muted mt-1">{{ $editing ? __('admin.team.password_keep') : __('admin.team.password_hint') }}</p>
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium">{{ __('admin.team.role') }} *</label>
        <select name="role" class="ui-select" @disabled($isSelf)>
            @foreach($roles as $role)
                <option value="{{ $role->key }}" @selected(old('role', $admin->role) === $role->key)>{{ $role->name }}</option>
            @endforeach
        </select>
        @if($isSelf)
            {{-- A disabled select is not sent; keep the current role --}}
            <input type="hidden" name="role" value="{{ $admin->role }}">
            <p class="ui-muted mt-1">{{ __('admin.team.not_yourself') }}</p>
        @endif
    </div>
    @if($editing)
        <label class="flex items-center gap-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $admin->user?->is_active)) @disabled($isSelf)
                   class="h-4 w-4 accent-[#C5A059]">
            <span class="text-sm">{{ __('admin.team.can_sign_in') }}</span>
            @if($isSelf)
                <input type="hidden" name="is_active" value="1">
            @endif
        </label>
    @endif
    <div class="flex gap-2 pt-2">
        <button class="ui-btn ui-btn-primary">{{ $editing ? __('admin.team.save') : __('admin.team.create_admin') }}</button>
        <a href="{{ route('admin.admins.index') }}" class="ui-btn ui-btn-ghost">{{ __('admin.cancel') }}</a>
    </div>
</form>
@endsection
