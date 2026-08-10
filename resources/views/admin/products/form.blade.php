@extends('admin.layouts.app')

@php
    use Illuminate\Support\Facades\Storage;

    $oldColors = old('colors');
    if ($oldColors === null) {
        $oldColors = $product->relationLoaded('colors')
            ? $product->colors->map(fn ($c) => ['name' => $c->name, 'hex' => $c->hex ?: '#C5A059'])->all()
            : [];
    }
    if ($oldColors === []) {
        $oldColors = [['name' => '', 'hex' => '#C5A059']];
    }

    $oldOptions = old('options');
    if ($oldOptions === null) {
        $oldOptions = $product->relationLoaded('options')
            ? $product->options->map(fn ($o) => ['name' => $o->name, 'value' => $o->value])->all()
            : [];
    }
    if ($oldOptions === []) {
        $oldOptions = [['name' => '', 'value' => '']];
    }

    $steps = [
        1 => __('admin.commerce.step_basics'),
        2 => __('admin.commerce.step_codes'),
        3 => __('admin.commerce.step_variants'),
        4 => __('admin.commerce.step_media'),
    ];
@endphp

@section('title', $product->exists ? __('admin.commerce.edit_product') : __('admin.commerce.add_product'))
@section('subtitle', __('admin.commerce.product_wizard_subtitle'))
@section('breadcrumbs')
<a href="{{ route('admin.categories.index') }}" class="hover:text-maqam-ink">{{ __('admin.nav.store_categories') }}</a>
<span class="mx-1">/</span>
<a href="{{ route('admin.products.index') }}" class="hover:text-maqam-ink">{{ __('admin.nav.products') }}</a>
<span class="mx-1">/</span>
<span>{{ $product->exists ? __('admin.edit') : __('admin.commerce.add_product') }}</span>
@endsection

@section('content')
@if($categories->isEmpty())
    <div class="mb-6 border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900" style="border-radius: 1rem">
        {{ __('admin.commerce.need_category_first') }}
        <a href="{{ route('admin.categories.create') }}" class="ms-2 font-semibold underline">{{ __('admin.commerce.add_category') }}</a>
    </div>
@endif

<form method="POST"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      enctype="multipart/form-data"
      class="mx-auto max-w-4xl"
      id="productForm">
    @csrf
    @if($product->exists) @method('PUT') @endif

    {{-- Step indicator --}}
    <div class="mb-8 flex items-center justify-between gap-2">
        @foreach($steps as $n => $label)
            <button type="button" onclick="goProductStep({{ $n }})" class="flex flex-1 flex-col items-center gap-2">
                <div id="stepDot{{ $n }}" class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold {{ $n === 1 ? 'bg-maqam-gold text-maqam-navy' : 'border border-maqam-line bg-white text-maqam-muted' }}">{{ $n }}</div>
                <span class="ui-muted hidden text-center text-xs sm:block">{{ $label }}</span>
            </button>
            @if($n < 4)<div class="mb-5 h-px flex-1 bg-maqam-line"></div>@endif
        @endforeach
    </div>

    {{-- Step 1: Basics --}}
    <div id="productStep1" class="ui-card-static space-y-5 p-6">
        <div>
            <h2 class="ui-section-title">{{ __('admin.commerce.step_basics') }}</h2>
            <p class="ui-muted mt-1 text-sm">{{ __('admin.commerce.step_basics_hint') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.commerce.name_ar') }}</label>
                <input name="name_ar" value="{{ old('name_ar', $product->name_ar) }}" required class="ui-input" dir="rtl">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.commerce.name_en') }}</label>
                <input name="name_en" id="nameEnInput" value="{{ old('name_en', $product->name_en) }}" required class="ui-input" dir="ltr">
            </div>
        </div>

        <div>
            <div class="mb-1 flex items-center justify-between">
                <label class="block text-sm font-medium">{{ __('admin.commerce.category') }}</label>
                <a href="{{ route('admin.categories.create') }}" class="text-xs font-semibold text-maqam-gold-dark hover:underline">+ {{ __('admin.commerce.add_category') }}</a>
            </div>
            <select name="category_id" required class="ui-select" @disabled($categories->isEmpty())>
                <option value="">{{ __('admin.commerce.choose_category') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id)==$cat->id)>{{ $cat->name_ar }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.commerce.description_ar') }}</label>
                <textarea name="description_ar" rows="4" class="ui-textarea" dir="rtl">{{ old('description_ar', $product->description_ar) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.commerce.description_en') }}</label>
                <textarea name="description_en" rows="4" class="ui-textarea" dir="ltr">{{ old('description_en', $product->description_en) }}</textarea>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.commerce.price') }} (ج.م)</label>
                <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required class="ui-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.commerce.stock') }}</label>
                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required class="ui-input">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))>
            {{ __('admin.commerce.visible_in_store') }}
        </label>

        <div class="flex justify-end gap-3 border-t border-[#E4E0D7] pt-5">
            <button type="button" onclick="goProductStep(2)" class="ui-btn ui-btn-primary" @disabled($categories->isEmpty())>{{ __('admin.commerce.next') }}</button>
        </div>
    </div>

    {{-- Step 2: Codes & barcode --}}
    <div id="productStep2" class="ui-card-static hidden space-y-5 p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="ui-section-title">{{ __('admin.commerce.step_codes') }}</h2>
                <p class="ui-muted mt-1 text-sm">{{ __('admin.commerce.step_codes_hint') }}</p>
            </div>
            @if($product->exists && $product->sku)
                <a href="{{ route('admin.products.barcode', ['product' => $product, 'download' => 1]) }}"
                   class="ui-btn ui-btn-primary">
                    {{ __('admin.commerce.download_barcode') }}
                </a>
            @endif
        </div>

        <div>
            <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                <label class="block text-sm font-medium">{{ __('admin.commerce.sku') }}</label>
                <button type="button" id="skuGenerateBtn" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.commerce.sku_generate') }}</button>
            </div>
            <input id="skuInput" name="sku" value="{{ old('sku', $product->sku) }}" class="ui-input font-mono" dir="ltr"
                   placeholder="MQM-SW-1G1W24-WHT" maxlength="100">
            <p class="ui-muted mt-1 text-xs">{{ __('admin.commerce.sku_hint') }}</p>
        </div>

        <div class="rounded-xl border border-[#E4E0D7] bg-[#F7F5F0] p-6 text-center">
            <img id="skuBarcodePreview"
                 src="{{ $product->exists && $product->sku ? route('admin.products.barcode', $product) : '' }}"
                 alt="barcode"
                 class="mx-auto max-h-32 {{ $product->exists && $product->sku ? '' : 'hidden' }}">
            <p id="skuBarcodeEmpty" class="ui-muted text-sm {{ $product->exists && $product->sku ? 'hidden' : '' }}">{{ __('admin.commerce.barcode_preview_hint') }}</p>
            @unless($product->exists)
                <p class="ui-muted mt-2 text-xs">{{ __('admin.commerce.barcode_download_after_save') }}</p>
            @endunless
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.commerce.production_code') }}</label>
                <input name="production_code" value="{{ old('production_code', $product->production_code) }}" class="ui-input" dir="ltr" placeholder="Q5001">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.commerce.system_code') }}</label>
                <input type="number" name="system_code" value="{{ old('system_code', $product->system_code) }}" class="ui-input" dir="ltr" placeholder="5001">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.commerce.catalog_code') }}</label>
                <input id="catalogCodeInput" name="catalog_code" value="{{ old('catalog_code', $product->catalog_code) }}" class="ui-input" dir="ltr" placeholder="MQM-SW-1G1W24-WHT">
            </div>
        </div>

        <div class="flex justify-between gap-3 border-t border-[#E4E0D7] pt-5">
            <button type="button" onclick="goProductStep(1)" class="ui-btn ui-btn-ghost">{{ __('admin.commerce.prev') }}</button>
            <button type="button" onclick="goProductStep(3)" class="ui-btn ui-btn-primary">{{ __('admin.commerce.next') }}</button>
        </div>
    </div>

    {{-- Step 3: Colors & options --}}
    <div id="productStep3" class="ui-card-static hidden space-y-5 p-6">
        <div>
            <h2 class="ui-section-title">{{ __('admin.commerce.step_variants') }}</h2>
            <p class="ui-muted mt-1 text-sm">{{ __('admin.commerce.step_variants_hint') }}</p>
        </div>

        <div class="rounded-xl border border-[#E4E0D7] p-4" x-data="productRows(@js($oldColors))">
            <div class="mb-3 flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold">{{ __('admin.commerce.colors') }}</h3>
                    <p class="ui-muted text-xs">{{ __('admin.commerce.colors_hint') }}</p>
                </div>
                <button type="button" class="ui-btn ui-btn-ghost text-xs" @click="add({name:'', hex:'#C5A059'})">+ {{ __('admin.commerce.add_color') }}</button>
            </div>
            <div class="space-y-2">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="grid grid-cols-[1fr_auto_auto] items-center gap-2">
                        <input type="text" :name="`colors[${index}][name]`" x-model="row.name" class="ui-input" placeholder="{{ __('admin.commerce.color_name') }}" dir="rtl">
                        <input type="color" :name="`colors[${index}][hex]`" x-model="row.hex" class="h-10 w-12 cursor-pointer rounded border border-[#D8D4CB] bg-white p-1">
                        <button type="button" class="ui-btn ui-btn-ghost text-xs text-red-700" @click="remove(index)" x-show="rows.length > 1">{{ __('admin.delete') }}</button>
                    </div>
                </template>
            </div>
        </div>

        <div class="rounded-xl border border-[#E4E0D7] p-4" x-data="productRows(@js($oldOptions))">
            <div class="mb-3 flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold">{{ __('admin.commerce.options') }}</h3>
                    <p class="ui-muted text-xs">{{ __('admin.commerce.options_hint') }}</p>
                </div>
                <button type="button" class="ui-btn ui-btn-ghost text-xs" @click="add({name:'', value:''})">+ {{ __('admin.commerce.add_option') }}</button>
            </div>
            <div class="space-y-2">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="grid grid-cols-[1fr_1fr_auto] items-center gap-2">
                        <input type="text" :name="`options[${index}][name]`" x-model="row.name" class="ui-input" placeholder="{{ __('admin.commerce.option_key') }}" dir="rtl">
                        <input type="text" :name="`options[${index}][value]`" x-model="row.value" class="ui-input" placeholder="{{ __('admin.commerce.option_value') }}" dir="rtl">
                        <button type="button" class="ui-btn ui-btn-ghost text-xs text-red-700" @click="remove(index)" x-show="rows.length > 1">{{ __('admin.delete') }}</button>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex justify-between gap-3 border-t border-[#E4E0D7] pt-5">
            <button type="button" onclick="goProductStep(2)" class="ui-btn ui-btn-ghost">{{ __('admin.commerce.prev') }}</button>
            <button type="button" onclick="goProductStep(4)" class="ui-btn ui-btn-primary">{{ __('admin.commerce.next') }}</button>
        </div>
    </div>

    {{-- Step 4: Media & save --}}
    <div id="productStep4" class="ui-card-static hidden space-y-5 p-6">
        <div>
            <h2 class="ui-section-title">{{ __('admin.commerce.step_media') }}</h2>
            <p class="ui-muted mt-1 text-sm">{{ __('admin.commerce.step_media_hint') }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.commerce.thumbnail') }}</label>
            <input type="file" name="image" accept="image/*" class="ui-input">
            @if($product->image_path)
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name_ar }}" class="h-20 w-20 rounded-lg border border-[#D8D4CB] object-cover">
                    <label class="flex items-center gap-2 text-sm text-red-600">
                        <input type="checkbox" name="delete_image" value="1">
                        {{ __('admin.commerce.delete_current_image') }}
                    </label>
                </div>
            @endif
        </div>

        @if($product->exists && $product->images->isNotEmpty())
            <div class="space-y-2">
                <div class="text-sm font-semibold">{{ __('admin.commerce.current_images') }}</div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3" id="existingImages">
                    @foreach($product->images as $img)
                        <div class="ui-card-soft relative overflow-hidden" data-existing-id="{{ $img->id }}">
                            <img src="{{ $img->url() }}" alt="" class="aspect-square w-full object-cover">
                            <label class="absolute inset-x-0 bottom-0 flex cursor-pointer items-center gap-2 bg-black/55 px-2 py-2 text-[11px] text-white">
                                <input type="radio" name="thumbnail_pick" value="existing:{{ $img->id }}"
                                       @checked($img->is_thumbnail)
                                       onchange="setThumb('existing', {{ $img->id }})">
                                {{ __('admin.commerce.thumbnail') }}
                            </label>
                            <label class="absolute start-2 top-2 flex cursor-pointer items-center gap-1 rounded bg-red-600/90 px-2 py-1 text-[10px] text-white">
                                <input type="checkbox" name="remove_images[]" value="{{ $img->id }}" class="accent-white" onchange="this.closest('[data-existing-id]').style.opacity=this.checked?'.35':'1'">
                                {{ __('admin.delete') }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
            <input type="hidden" name="thumbnail_source" id="thumbnail_source" value="existing">
            <input type="hidden" name="thumbnail_existing_id" id="thumbnail_existing_id" value="{{ $product->images->firstWhere('is_thumbnail', true)?->id ?? $product->images->first()?->id }}">
        @else
            <input type="hidden" name="thumbnail_source" id="thumbnail_source" value="new">
            <input type="hidden" name="thumbnail_existing_id" id="thumbnail_existing_id" value="">
        @endif

        <div>
            <label class="mb-2 block text-sm font-medium">{{ $product->exists ? __('admin.commerce.add_images') : __('admin.commerce.upload_images') }} <span class="text-red-600">*</span></label>
            <label class="ui-empty flex cursor-pointer flex-col items-center justify-center !p-8 transition hover:border-maqam-gold">
                <span class="text-sm font-medium text-maqam-ink">{{ __('admin.commerce.click_to_select_images') }}</span>
                <span class="ui-muted mt-1">JPG / PNG / WEBP — {{ __('admin.commerce.max_image_size') }}</span>
                <input type="file" name="images[]" id="imagesInput" accept="image/jpeg,image/png,image/webp" multiple class="hidden"
                       {{ $product->exists ? '' : 'required' }}
                       onchange="previewNewImages(this)">
            </label>
            <input type="hidden" name="thumbnail_index" id="thumbnail_index" value="0">
            <div id="newPreviews" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
        </div>

        <div class="flex flex-wrap justify-between gap-3 border-t border-[#E4E0D7] pt-5">
            <button type="button" onclick="goProductStep(3)" class="ui-btn ui-btn-ghost">{{ __('admin.commerce.prev') }}</button>
            <div class="flex gap-3">
                <a href="{{ route('admin.products.index') }}" class="ui-btn ui-btn-ghost">{{ __('admin.cancel') }}</a>
                <button class="ui-btn ui-btn-primary" @disabled($categories->isEmpty())>{{ __('admin.save') }}</button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function productRows(initial) {
    return {
        rows: Array.isArray(initial) && initial.length ? initial : [{}],
        add(row) { this.rows.push(row); },
        remove(index) { if (this.rows.length > 1) this.rows.splice(index, 1); },
    };
}

function goProductStep(n) {
    if (n > 1) {
        const nameAr = document.querySelector('input[name="name_ar"]')?.value?.trim();
        const nameEn = document.querySelector('input[name="name_en"]')?.value?.trim();
        const category = document.querySelector('select[name="category_id"]')?.value;
        if (!nameAr || !nameEn || !category) {
            alert(@json(__('admin.commerce.complete_basics_first')));
            n = 1;
        }
    }

    [1,2,3,4].forEach(i => {
        const panel = document.getElementById('productStep' + i);
        if (panel) panel.classList.toggle('hidden', i !== n);
        const dot = document.getElementById('stepDot' + i);
        if (!dot) return;
        const active = i === n || i < n;
        dot.className = 'flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold ' +
            (active ? 'bg-maqam-gold text-maqam-navy' : 'border border-maqam-line bg-white text-maqam-muted');
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function setThumb(source, idOrIndex) {
    document.getElementById('thumbnail_source').value = source;
    if (source === 'existing') {
        document.getElementById('thumbnail_existing_id').value = idOrIndex;
    } else {
        document.getElementById('thumbnail_index').value = idOrIndex;
        document.getElementById('thumbnail_existing_id').value = '';
    }
}

function previewNewImages(input) {
    const box = document.getElementById('newPreviews');
    box.innerHTML = '';
    const files = Array.from(input.files || []);
    if (!files.length) return;

    document.getElementById('thumbnail_source').value = 'new';
    document.querySelectorAll('input[name="thumbnail_pick"]').forEach(r => {
        if (String(r.value).startsWith('existing:')) r.checked = false;
    });

    files.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const wrap = document.createElement('div');
            wrap.className = 'ui-card-soft relative overflow-hidden';
            wrap.innerHTML = `
                <img src="${e.target.result}" class="aspect-square w-full object-cover" alt="">
                <label class="absolute inset-x-0 bottom-0 flex cursor-pointer items-center gap-2 bg-black/55 px-2 py-2 text-[11px] text-white">
                    <input type="radio" name="thumbnail_pick" value="new:${i}" ${i===0?'checked':''} onchange="setThumb('new', ${i})">
                    ${@json(__('admin.commerce.thumbnail'))}
                </label>`;
            box.appendChild(wrap);
        };
        reader.readAsDataURL(file);
    });
    setThumb('new', 0);
}

(function skuTools(){
    const btn = document.getElementById('skuGenerateBtn');
    const skuInput = document.getElementById('skuInput');
    const catalogInput = document.getElementById('catalogCodeInput');
    const preview = document.getElementById('skuBarcodePreview');
    const emptyHint = document.getElementById('skuBarcodeEmpty');
    if (!btn || !skuInput) return;

    const suggestUrl = @json(route('admin.products.sku-suggest'));
    const previewUrl = @json(route('admin.products.barcode-preview'));
    const productId = @json($product->exists ? $product->id : null);

    function firstColorName(){
        const el = document.querySelector('input[name^="colors"][name$="[name]"]');
        return el ? el.value.trim() : '';
    }

    function refreshPreview(code){
        code = (code || '').trim();
        if (!code) {
            if (preview) { preview.classList.add('hidden'); preview.removeAttribute('src'); }
            if (emptyHint) emptyHint.classList.remove('hidden');
            return;
        }
        if (preview) {
            preview.src = previewUrl + '?code=' + encodeURIComponent(code) + '&t=' + Date.now();
            preview.classList.remove('hidden');
            if (emptyHint) emptyHint.classList.add('hidden');
        }
    }

    btn.addEventListener('click', async () => {
        const nameEn = document.getElementById('nameEnInput')?.value?.trim();
        if (!nameEn) {
            alert(@json(__('admin.commerce.sku_need_name')));
            goProductStep(1);
            return;
        }
        btn.disabled = true;
        try {
            const params = new URLSearchParams({ name_en: nameEn, color: firstColorName() });
            if (productId) params.set('product_id', String(productId));
            const res = await fetch(suggestUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('failed');
            const data = await res.json();
            skuInput.value = data.sku || '';
            if (catalogInput && (!catalogInput.value || catalogInput.dataset.autogen === '1')) {
                catalogInput.value = data.catalog_code || data.sku || '';
                catalogInput.dataset.autogen = '1';
            }
            refreshPreview(skuInput.value);
        } catch (e) {
            alert(@json(__('admin.commerce.sku_generate_failed')));
        } finally {
            btn.disabled = false;
        }
    });

    skuInput.addEventListener('input', () => {
        clearTimeout(skuInput._t);
        skuInput._t = setTimeout(() => refreshPreview(skuInput.value), 400);
    });
    if (skuInput.value.trim()) refreshPreview(skuInput.value);
})();

@if($errors->any())
document.addEventListener('DOMContentLoaded', () => goProductStep(1));
@endif
</script>
@endpush
