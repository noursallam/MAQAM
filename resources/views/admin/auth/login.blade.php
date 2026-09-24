<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,300..700&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:ital,opsz,wght@0,14..32,300..700;1,14..32,300..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-maqam-bg font-sans text-maqam-ink" style="font-family:'IBM Plex Sans Arabic', Inter, 'DM Sans', ui-sans-serif, system-ui, sans-serif">
<div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_rgba(197,160,89,0.18),_transparent_55%)]"></div>
    <div class="ui-card-static relative w-full max-w-md p-8">
        <div class="mb-8 text-center">
            <div class="text-3xl font-bold tracking-wide text-maqam-gold">MAQAM</div>
            <p class="ui-muted mt-2 text-sm">{{ __('admin.admin_panel') }}</p>
            <p class="mt-1 text-xs text-maqam-gold-dark">{{ __('admin.tagline') }}</p>
        </div>
        @if($errors->any())
            <div class="ui-toast-err mb-4">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="ui-input" dir="ltr">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.password') }}</label>
                <input type="password" name="password" required class="ui-input" dir="ltr">
            </div>
            <label class="ui-muted flex items-center gap-2">
                <input type="checkbox" name="remember">
                {{ __('admin.remember') }}
            </label>
            <button class="ui-btn ui-btn-primary ui-btn-block">
                {{ __('admin.sign_in') }}
            </button>
        </form>
    </div>
</div>
</body>
</html>
