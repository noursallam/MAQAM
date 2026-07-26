@extends('admin.layouts.app')
@section('title', $category->exists ? 'تعديل القسم' : 'إضافة قسم')
@section('breadcrumbs')
<a href="{{ route('admin.categories.index') }}" class="hover:text-maqam-ink">الأقسام</a>
<span class="mx-1">/</span>
<span>{{ $category->exists ? 'تعديل' : 'جديد' }}</span>
@endsection
@section('content')
<form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" 
      enctype="multipart/form-data" class="ui-card-static max-w-xl space-y-4 p-6">
    @csrf @if($category->exists) @method('PUT') @endif
    <div>
        <label class="mb-1 block text-sm font-medium">اسم القسم بالعربية</label>
        <input name="name_ar" value="{{ old('name_ar', $category->name_ar) }}" required class="ui-input" dir="rtl">
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium">اسم القسم بالإنجليزية</label>
        <input name="name_en" value="{{ old('name_en', $category->name_en) }}" required class="ui-input" dir="ltr">
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium">الرابط المختصر (Slug)</label>
        <input name="slug" value="{{ old('slug', $category->slug) }}" placeholder="يُنشأ تلقائياً إذا تُرك فارغاً" class="ui-input" dir="ltr">
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium">القسم الأب (اختياري)</label>
        <select name="parent_id" class="ui-select">
            <option value="">بدون — قسم رئيسي</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id)==$parent->id)>{{ $parent->name_ar }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium">صورة القسم</label>
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
        <p class="ui-muted mt-2">صورة القسم (اختياري). إذا لم يتم إضافة صورة، سيتم استخدام الصورة الافتراضية.</p>
    </div>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
        ظاهر في المتجر
    </label>
    <div class="flex gap-3">
        <button class="ui-btn ui-btn-primary">حفظ القسم</button>
        <a href="{{ route('admin.categories.index') }}" class="ui-btn ui-btn-ghost">إلغاء</a>
    </div>
</form>
@endsection
