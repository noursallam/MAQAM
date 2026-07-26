@extends('admin.layouts.app')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('title', $product->exists ? __('admin.commerce.edit_product') : __('admin.commerce.add_product'))
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
        يجب إضافة <strong>قسم</strong> للمنتجات أولاً قبل إضافة منتج.
        <a href="{{ route('admin.categories.create') }}" class="ms-2 font-semibold underline">إضافة قسم</a>
    </div>
@endif

<form method="POST"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      enctype="multipart/form-data"
      class="grid gap-6 lg:grid-cols-5"
      id="productForm">
    @csrf
    @if($product->exists) @method('PUT') @endif

    <div class="ui-card-static space-y-5 p-6 lg:col-span-3">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">اسم المنتج بالعربية</label>
                <input name="name_ar" value="{{ old('name_ar', $product->name_ar) }}" required class="ui-input" dir="rtl">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">اسم المنتج بالإنجليزية</label>
                <input name="name_en" value="{{ old('name_en', $product->name_en) }}" required class="ui-input" dir="ltr">
            </div>
        </div>

        <div>
            <div class="mb-1 flex items-center justify-between">
                <label class="block text-sm font-medium">القسم</label>
                <a href="{{ route('admin.categories.create') }}" class="text-xs font-semibold text-maqam-gold-dark hover:underline">+ قسم جديد</a>
            </div>
            <select name="category_id" required class="ui-select" @disabled($categories->isEmpty())>
                <option value="">اختر القسم…</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id)==$cat->id)>{{ $cat->name_ar }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">الوصف بالعربية</label>
            <textarea name="description_ar" rows="3" class="ui-textarea" dir="rtl">{{ old('description_ar', $product->description_ar) }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">الوصف بالإنجليزية</label>
            <textarea name="description_en" rows="2" class="ui-textarea" dir="ltr">{{ old('description_en', $product->description_en) }}</textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium">السعر (ج.م)</label>
                <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required class="ui-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">الكمية المتاحة</label>
                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required class="ui-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">كود المنتج (SKU)</label>
                <input name="sku" value="{{ old('sku', $product->sku) }}" required class="ui-input" dir="ltr">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">الصورة الرئيسية</label>
            <input type="file" name="image" accept="image/*" class="ui-input">
            @if($product->image_path)
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name_ar }}" class="h-20 w-20 rounded-lg object-cover border border-[#D8D4CB]">
                    <label class="flex items-center gap-2 text-sm text-red-600">
                        <input type="checkbox" name="delete_image" value="1">
                        حذف الصورة الحالية
                    </label>
                </div>
            @endif
            <p class="ui-muted mt-2">صورة المنتج الرئيسية (اختياري). إذا لم يتم إضافة صورة، سيتم استخدام أول صورة من المعرض.</p>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))>
            ظاهر في المتجر
        </label>

        <div class="flex gap-3">
            <button class="ui-btn ui-btn-primary" @disabled($categories->isEmpty())>{{ __('admin.save') }}</button>
            <a href="{{ route('admin.products.index') }}" class="ui-btn ui-btn-ghost">{{ __('admin.cancel') }}</a>
        </div>
    </div>

    {{-- Images studio --}}
    <div class="ui-card-static p-6 lg:col-span-2">
        <h3 class="ui-section-title">صور المنتج <span class="text-red-600">*</span></h3>
        <p class="ui-muted mt-1">يلزم صورة واحدة على الأقل. حدد الصورة الرئيسية التي تظهر في القائمة.</p>

        @if($product->exists && $product->images->isNotEmpty())
            <div class="mt-4 space-y-2">
                <div class="ui-muted font-semibold">الصور الحالية</div>
                <div class="grid grid-cols-2 gap-3" id="existingImages">
                    @foreach($product->images as $img)
                        <div class="ui-card-soft relative overflow-hidden" data-existing-id="{{ $img->id }}">
                            <img src="{{ $img->url() }}" alt="" class="aspect-square w-full object-cover">
                            <label class="absolute inset-x-0 bottom-0 flex cursor-pointer items-center gap-2 bg-black/55 px-2 py-2 text-[11px] text-white">
                                <input type="radio" name="thumbnail_pick" value="existing:{{ $img->id }}"
                                       @checked($img->is_thumbnail)
                                       onchange="setThumb('existing', {{ $img->id }})">
                                رئيسية
                            </label>
                            <label class="absolute start-2 top-2 flex cursor-pointer items-center gap-1 rounded bg-red-600/90 px-2 py-1 text-[10px] text-white">
                                <input type="checkbox" name="remove_images[]" value="{{ $img->id }}" class="accent-white" onchange="this.closest('[data-existing-id]').style.opacity=this.checked?'.35':'1'">
                                حذف
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

        <div class="mt-5">
            <label class="mb-2 block text-sm font-medium">{{ $product->exists ? 'إضافة صور جديدة' : 'رفع الصور' }}</label>
            <label class="ui-empty flex cursor-pointer flex-col items-center justify-center !p-8 transition hover:border-maqam-gold">
                <span class="text-sm font-medium text-maqam-ink">اضغط لاختيار الصور</span>
                <span class="ui-muted mt-1">JPG / PNG / WEBP — حتى 4 ميجابايت للصورة</span>
                <input type="file" name="images[]" id="imagesInput" accept="image/jpeg,image/png,image/webp" multiple class="hidden"
                       {{ $product->exists ? '' : 'required' }}
                       onchange="previewNewImages(this)">
            </label>
            <input type="hidden" name="thumbnail_index" id="thumbnail_index" value="0">
            <div id="newPreviews" class="mt-3 grid grid-cols-2 gap-3"></div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
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
    document.querySelectorAll('input[name="thumbnail_pick"]').forEach(r => { if (String(r.value).startsWith('existing:')) r.checked = false; });

    files.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const wrap = document.createElement('div');
            wrap.className = 'ui-card-soft relative overflow-hidden';
            wrap.innerHTML = `
                <img src="${e.target.result}" class="aspect-square w-full object-cover" alt="">
                <label class="absolute inset-x-0 bottom-0 flex cursor-pointer items-center gap-2 bg-black/55 px-2 py-2 text-[11px] text-white">
                    <input type="radio" name="thumbnail_pick" value="new:${i}" ${i===0?'checked':''} onchange="setThumb('new', ${i})">
                    رئيسية
                </label>`;
            box.appendChild(wrap);
        };
        reader.readAsDataURL(file);
    });
    setThumb('new', 0);
}
</script>
@endpush
