@extends('admin.layouts.app')

@section('title', $category->exists ? __('admin.edit').' — '.__('admin.qr.prize_studio') : __('admin.qr.add_category'))
@section('subtitle', __('admin.qr.color_hint'))

@section('content')
<form method="POST" action="{{ $category->exists ? route('admin.prize-categories.update', $category) : route('admin.prize-categories.store') }}"
      enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-2">
    @csrf
    @if($category->exists) @method('PUT') @endif

    <div class="ui-card-static space-y-5 p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.name_ar') }}</label>
                <input name="name_ar" value="{{ old('name_ar', $category->name_ar) }}" required class="ui-input" dir="rtl">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.name_en') }}</label>
                <input name="name_en" value="{{ old('name_en', $category->name_en) }}" required class="ui-input" dir="ltr">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.points') }}</label>
                <input type="number" name="points_value" min="0" value="{{ old('points_value', $category->points_value) }}" required class="ui-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.type') }}</label>
                <select name="category_type" class="ui-select">
                    <option value="gift" @selected(old('category_type', $category->category_type) === 'gift')>{{ __('admin.qr.gift') }}</option>
                    <option value="standard" @selected(old('category_type', $category->category_type) === 'standard')>{{ __('admin.qr.standard') }}</option>
                </select>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.print_color') }}</label>
            <div class="flex items-center gap-3">
                <input type="color" id="color_picker" value="{{ old('background_color', $category->background_color ?: '#22C55E') }}"
                       class="h-12 w-16 cursor-pointer rounded border-0 bg-transparent p-0"
                       oninput="syncColor(this.value)">
                <input type="text" name="background_color" id="background_color" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                       value="{{ old('background_color', $category->background_color ?: '#22C55E') }}" required
                       class="ui-input flex-1 font-mono" dir="ltr"
                       oninput="syncColor(this.value)">
            </div>
            <p class="ui-muted mt-2">مثال: فئة هدايا 1 ← أخضر <code dir="ltr">#22C55E</code></p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">صورة الفئة</label>
            <input type="file" name="image" accept="image/*" class="ui-input">
            @if($category->hasImage())
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ $category->image_url }}" alt="{{ $category->name_ar }}" class="h-20 w-20 rounded-lg object-cover border border-[#D8D4CB]">
                    <label class="flex items-center gap-2 text-sm text-red-600">
                        <input type="checkbox" name="delete_image" value="1">
                        حذف الصورة الحالية
                    </label>
                </div>
            @endif
            <p class="ui-muted mt-2">صورة الفئة (اختياري). إذا لم يتم إضافة صورة، سيتم استخدام الصورة الافتراضية.</p>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
            {{ __('admin.active') }}
        </label>

        <div class="flex gap-3">
            <button class="ui-btn ui-btn-primary">{{ __('admin.save') }}</button>
            <a href="{{ route('admin.prize-categories.index') }}" class="ui-btn ui-btn-ghost">{{ __('admin.cancel') }}</a>
        </div>
    </div>

    <div class="ui-card-static p-6">
        <h3 class="ui-section-title mb-3">{{ __('admin.qr.live_preview') }}</h3>
        <p class="ui-muted mb-4">{{ __('admin.qr.color_hint') }}</p>
        <div id="preview" class="flex aspect-square max-w-sm items-center justify-center" style="border-radius: 1rem; background: {{ old('background_color', $category->background_color ?: '#22C55E') }}">
            <div class="ui-card-static p-8 text-center">
                <div class="mx-auto mb-3 grid w-24 grid-cols-7 gap-0.5">
                    @for($i=0;$i<49;$i++)
                        <span class="h-2 w-2 rounded-[1px] bg-maqam-ink {{ $i%4===0?'opacity-100':'opacity-30' }}"></span>
                    @endfor
                </div>
                <div class="ui-muted">{{ __('admin.qr.qr_zone') }}</div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function syncColor(v){
    const hex = v.startsWith('#') ? v : '#'+v;
    document.getElementById('background_color').value = hex.toUpperCase();
    document.getElementById('color_picker').value = hex;
    document.getElementById('preview').style.background = hex;
}
</script>
@endpush
