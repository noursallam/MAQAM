{{-- Where the payment gateway sends a customer who paid from inside the mobile app.
     The app closes this page by itself; the text is for the moment before it does,
     and for the case where the page was opened in a browser instead. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('api.payment_return.'.$outcome.'_title') }}</title>
    <style>
        html, body { height: 100%; margin: 0; }
        body {
            display: flex; align-items: center; justify-content: center;
            background: #F7F6F2; color: #161B29; padding: 24px; box-sizing: border-box;
            font-family: "IBM Plex Sans Arabic", system-ui, -apple-system, "Segoe UI", sans-serif;
            text-align: center;
        }
        .mark {
            width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 24px;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 700;
            background: {{ $outcome === 'failed' ? '#B3261E' : '#161B29' }};
            color: {{ $outcome === 'failed' ? '#FFFFFF' : '#DCB479' }};
        }
        h1 { font-size: 22px; margin: 0 0 8px; }
        p { font-size: 16px; line-height: 1.6; color: #5F6368; margin: 0 0 8px; }
        .number {
            display: inline-block; margin-top: 16px; padding: 8px 12px; border-radius: 12px;
            background: #EFEDE7; color: #161B29; font-weight: 600; direction: ltr;
        }
    </style>
</head>
<body>
    <main>
        <div class="mark" aria-hidden="true">{{ $outcome === 'failed' ? '!' : '✓' }}</div>
        <h1>{{ __('api.payment_return.'.$outcome.'_title') }}</h1>
        <p>{{ __('api.payment_return.'.$outcome.'_body') }}</p>
        <p><strong>{{ __('api.payment_return.back_to_app') }}</strong></p>
        @if ($orderNumber)
            <div class="number">{{ $orderNumber }}</div>
        @endif
    </main>
</body>
</html>
