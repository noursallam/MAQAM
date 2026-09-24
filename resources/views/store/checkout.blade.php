@extends('store.layouts.app')

@section('title', __('store.checkout.title'))

@section('content')
@php
    $locale = app()->getLocale();
    $pointsRate = 10; // 10 points = 1 EGP
    $pointsNeeded = (int) ceil($summary['total'] * $pointsRate);
    $userPoints = $customer?->points_balance ?? 0;
    $hasEnoughPoints = $userPoints >= $pointsNeeded && $pointsNeeded > 0;
@endphp

<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('store.cart') }}">{{ __('store.nav.cart') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.checkout.heading') }}</span>
        </div>
        <h1 class="mq-page-title">{{ __('store.checkout.heading') }}</h1>
        <p class="mq-page-lead">{{ __('store.checkout.lead') }}</p>

        <div class="mq-checkout-layout">
            <form class="mq-panel" action="{{ route('store.checkout.process') }}" method="POST">
                @csrf
                <h3 class="mq-side-title">{{ __('store.checkout.shipping_data') }}</h3>

                <div class="mq-field">
                    <label for="checkout_name">{{ __('store.checkout.full_name') }} *</label>
                    <input type="text" id="checkout_name" name="full_name" value="{{ old('full_name', $user?->full_name) }}" placeholder="{{ __('store.checkout.full_name_ph') }}" required>
                </div>

                <div class="mq-field">
                    <label for="checkout_phone">{{ __('store.checkout.phone') }} *</label>
                    <input type="tel" id="checkout_phone" name="phone" value="{{ old('phone', $user?->phone_number) }}" placeholder="01xxxxxxxxx" dir="ltr" required>
                </div>

                <div class="mq-field">
                    <label for="checkout_gov">{{ __('store.checkout.city') }} (المحافظة) *</label>
                    <select id="checkout_gov" name="governorate" required>
                        @php
                            $selectedGov = old('governorate', $defaultAddress?->governorate ?? 'القاهرة');
                            $govs = [
                                'القاهرة' => 'القاهرة (Cairo)',
                                'الجيزة' => 'الجيزة (Giza)',
                                'الإسكندرية' => 'الإسكندرية (Alexandria)',
                                'القليوبية' => 'القليوبية (Qalyubia)',
                                'الدقهلية' => 'الدقهلية - المنصورة (Dakahlia)',
                                'الشرقية' => 'الشرقية - الزقازيق (Sharqia)',
                                'الغربية' => 'الغربية - طنطا (Gharbia)',
                                'المنوفية' => 'المنوفية (Menofia)',
                                'البحيرة' => 'البحيرة (Beheira)',
                                'دمياط' => 'دمياط (Damietta)',
                                'بورسعيد' => 'بورسعيد (Port Said)',
                                'الإسماعيلية' => 'الإسماعيلية (Ismailia)',
                                'السويس' => 'السويس (Suez)',
                                'كفر الشيخ' => 'كفر الشيخ (Kafr El Sheikh)',
                                'الفيوم' => 'الفيوم (Fayoum)',
                                'بني سويف' => 'بني سويف (Beni Suef)',
                                'المنيا' => 'المنيا (Minya)',
                                'أسيوط' => 'أسيوط (Asyut)',
                                'سوهاج' => 'سوهاج (Sohag)',
                                'قنا' => 'قنا (Qena)',
                                'الأقصر' => 'الأقصر (Luxor)',
                                'أسوان' => 'أسوان (Aswan)',
                                'البحر الأحمر' => 'البحر الأحمر - الغردقة (Red Sea)',
                            ];
                        @endphp
                        @foreach ($govs as $val => $label)
                            <option value="{{ $val }}" {{ $selectedGov === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mq-field">
                    <label for="checkout_city">المدينة / الحي *</label>
                    <input type="text" id="checkout_city" name="city" value="{{ old('city', $defaultAddress?->city ?? 'مدينة نصر') }}" placeholder="مثال: التجمع الخامس، المعادي، الدقي..." required>
                </div>

                <div class="mq-field">
                    <label for="checkout_address">{{ __('store.checkout.address') }} (اسم الشارع، رقم العقار، الشقة) *</label>
                    <textarea id="checkout_address" name="address" rows="3" placeholder="{{ __('store.checkout.address_ph') }}" required>{{ old('address', $defaultAddress?->address_line1) }}</textarea>
                </div>

                <div class="mq-field">
                    <label for="checkout_notes">ملاحظات التوصيل (اختياري)</label>
                    <input type="text" id="checkout_notes" name="notes" value="{{ old('notes') }}" placeholder="مثال: يرجى الاتصال قبل الوصول بنصف ساعة">
                </div>

                <h3 class="mq-side-title" style="margin-top:2rem;">{{ __('store.checkout.payment') }}</h3>
                <div class="mq-filter-group" style="display:flex;flex-direction:column;gap:.75rem;">
                    <label style="padding:.75rem 1rem;border:1px solid var(--mq-border);border-radius:8px;background:rgba(255,255,255,.03);cursor:pointer;display:flex;align-items:center;gap:.75rem;">
                        <input type="radio" name="payment_method" value="kashier" checked>
                        <div>
                            <strong>الدفع الإلكتروني الآمن (Kashier)</strong>
                            <div style="font-size:.82rem;color:var(--mq-muted);margin-top:.15rem;">
                                بطاقات فيزا / ماستركارد / ميزة، فودافون كاش، ومحافظ الهاتف المحمول
                            </div>
                        </div>
                    </label>

                    <label style="padding:.75rem 1rem;border:1px solid var(--mq-border);border-radius:8px;background:rgba(255,255,255,.03);cursor:pointer;display:flex;align-items:center;gap:.75rem;">
                        <input type="radio" name="payment_method" value="cod">
                        <div>
                            <strong>{{ __('store.checkout.cod') }} (الدفع عند الاستلام)</strong>
                            <div style="font-size:.82rem;color:var(--mq-muted);margin-top:.15rem;">
                                ادفع نقدًا عند استلام الشحنة من مندوب التوصيل
                            </div>
                        </div>
                    </label>

                    @auth
                        <label style="padding:.75rem 1rem;border:1px solid var(--mq-border);border-radius:8px;background:rgba(255,255,255,.03);cursor:{{ $hasEnoughPoints ? 'pointer' : 'not-allowed' }};opacity:{{ $hasEnoughPoints ? '1' : '.6' }};display:flex;align-items:center;gap:.75rem;">
                            <input type="radio" name="payment_method" value="wallet" {{ $hasEnoughPoints ? '' : 'disabled' }}>
                            <div>
                                <strong>{{ __('store.checkout.wallet') }} (محفظة نقاط الولاء)</strong>
                                <div style="font-size:.82rem;color:var(--mq-muted);margin-top:.15rem;">
                                    المطلوب: <strong>{{ number_format($pointsNeeded) }} نقطة</strong> (رصيدك الحالي: {{ number_format($userPoints) }} نقطة)
                                    @if (! $hasEnoughPoints)
                                        — <span style="color:#f87171;">الرصيد غير كافٍ</span>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endauth
                </div>

                <button type="submit" class="mq-btn mq-btn-primary mq-btn-block" style="margin-top:2rem;padding:.9rem 1.5rem;font-size:1.05rem;">
                    {{ __('store.checkout.confirm') }}
                </button>
            </form>

            <aside class="mq-panel">
                <h3 class="mq-side-title">{{ __('store.checkout.summary') }}</h3>

                <div style="max-height:280px;overflow-y:auto;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid var(--mq-border);">
                    @foreach ($cart->items as $item)
                        @php
                            $prod = $item->product;
                            $pName = $prod ? ($locale === 'ar' ? $prod->name_ar : $prod->name_en) : 'Product #' . $item->product_id;
                        @endphp
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem;margin-bottom:.75rem;font-size:.9rem;">
                            <div>
                                <strong>{{ $pName }}</strong>
                                <div style="color:var(--mq-muted);font-size:.82rem;">× {{ $item->quantity }}</div>
                            </div>
                            <div style="white-space:nowrap;font-weight:600;">
                                {{ number_format($item->unit_price * $item->quantity, 2) }} {{ __('store.common.egp') }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mq-summary-row">
                    <span>{{ __('store.common.subtotal') }}</span>
                    <span>{{ number_format($summary['subtotal'], 2) }} {{ __('store.common.egp') }}</span>
                </div>

                @if ($summary['discount'] > 0)
                    <div class="mq-summary-row" style="color:#22c55e;">
                        <span>{{ __('store.cart.discount') ?? 'الخصم' }}</span>
                        <span>−{{ number_format($summary['discount'], 2) }} {{ __('store.common.egp') }}</span>
                    </div>
                @endif

                <div class="mq-summary-row">
                    <span>{{ __('store.common.shipping') }}</span>
                    <span>
                        @if ($summary['shipping'] <= 0)
                            <em style="color:#22c55e;font-style:normal;">{{ __('store.cart.free_shipping') ?? 'شحن مجاني' }}</em>
                        @else
                            {{ number_format($summary['shipping'], 2) }} {{ __('store.common.egp') }}
                        @endif
                    </span>
                </div>

                <div class="mq-summary-row total">
                    <span>{{ __('store.common.total') }}</span>
                    <span>{{ number_format($summary['total'], 2) }} {{ __('store.common.egp') }}</span>
                </div>

                <div style="margin-top:1rem;font-size:.82rem;color:var(--mq-muted);line-height:1.5;">
                    🔒 جميع عمليات الدفع الإلكتروني مشفرة ومؤمنة بأعلى معايير الحماية عبر بوابة Kashier المعتمدة من البنك المركزي المصري.
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
