@extends('admin.layouts.app')

@section('title', __('admin.notifications.composer'))
@section('subtitle', __('admin.notifications.composer_subtitle'))

@section('content')
<form method="POST" action="{{ route('admin.notifications.store') }}" class="grid gap-6 lg:grid-cols-2" id="notifForm">
    @csrf
    <div class="ui-card-static space-y-4 p-6">
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.notifications.title_field') }} (AR)</label>
            <input name="title" id="title_ar" value="{{ old('title') }}" required class="ui-input" dir="rtl"
                   oninput="document.getElementById('prev_title').textContent=this.value||'—'">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.notifications.body') }} (AR)</label>
            <textarea name="body" id="body_ar" rows="4" required class="ui-textarea" dir="rtl"
                      oninput="document.getElementById('prev_body').textContent=this.value||'—'">{{ old('body') }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.notifications.title_field') }} (EN)</label>
            <input name="title_en" value="{{ old('title_en') }}" class="ui-input" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.notifications.body') }} (EN)</label>
            <textarea name="body_en" rows="3" class="ui-textarea" dir="ltr">{{ old('body_en') }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.notifications.type') }}</label>
            <select name="type" class="ui-select">
                @foreach(['rank_upgrade','offer','reminder','order_update','promotion'] as $type)
                    <option value="{{ $type }}">{{ __('admin.notifications.'.$type) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.notifications.audience') }}</label>
            <select name="segment" id="segment" class="ui-select" onchange="document.getElementById('rankBox').classList.toggle('hidden', this.value!=='rank')">
                <option value="all">{{ __('admin.notifications.all_users') }}</option>
                <option value="customers">{{ __('admin.notifications.all_customers') }}</option>
                <option value="merchants">{{ __('admin.notifications.all_merchants') }}</option>
                <option value="rank">{{ __('admin.notifications.by_rank') }}</option>
                <option value="inactive">{{ __('admin.notifications.inactive') }}</option>
                <option value="new">{{ __('admin.notifications.new_users') }}</option>
                <option value="active">{{ __('admin.notifications.active_users') }}</option>
            </select>
        </div>
        <div id="rankBox" class="hidden">
            <label class="mb-1 block text-sm font-medium">{{ __('admin.nav.ranks') }}</label>
            <select name="rank_id" class="ui-select">
                @foreach($ranks as $rank)
                    <option value="{{ $rank->id }}">{{ $rank->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <button class="ui-btn ui-btn-primary">{{ __('admin.notifications.send') }}</button>
    </div>

    <div class="ui-card-static p-6">
        <h3 class="ui-section-title mb-4">{{ __('admin.notifications.preview') }}</h3>
        <div class="mx-auto max-w-sm rounded-2xl bg-maqam-navy p-4 text-white">
            <div class="mb-3 text-center text-[10px] text-white/40">MAQAM</div>
            <div class="rounded-xl bg-white/10 p-4">
                <div id="prev_title" class="font-semibold">—</div>
                <div id="prev_body" class="mt-2 text-sm text-white/70">—</div>
            </div>
        </div>
        <p class="ui-muted mt-6">{{ __('admin.notifications.composer_subtitle') }}</p>
    </div>
</form>
@endsection
