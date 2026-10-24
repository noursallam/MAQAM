@php
    $rtl = app()->getLocale() !== 'en';
    // Brand marks from Simple Icons, tinted with CSS so they match the page
    $icon = fn (string $slug) => "https://cdn.jsdelivr.net/npm/simple-icons@13/icons/{$slug}.svg";
    $slugs = [
        'android' => 'googleplay', 'ios' => 'apple', 'instagram' => 'instagram', 'facebook' => 'facebook',
        'tiktok' => 'tiktok', 'whatsapp' => 'whatsapp', 'youtube' => 'youtube', 'x' => 'x',
        'snapchat' => 'snapchat', 'telegram' => 'telegram',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>MAQAM — {{ __('store.links.title') }}</title>
    <meta name="description" content="{{ $bio }}">
    <meta property="og:title" content="MAQAM">
    <meta property="og:description" content="{{ $bio }}">
    <meta property="og:image" content="{{ asset('identity/MAQAM-24.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="theme-color" content="#0E121C">
    <link rel="icon" href="{{ asset('identity/MAQAM-03.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --navy: #0E121C; --navy-2: #161B29; --gold: #DCB479; --gold-deep: #C5A059; --ink: #F5F3EE; --muted: #A9AEBB; --line: rgba(255,255,255,.09); }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: "IBM Plex Sans Arabic", system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(60rem 30rem at 50% -10rem, rgba(220,180,121,.22), transparent 60%),
                radial-gradient(40rem 24rem at 110% 110%, rgba(220,180,121,.08), transparent 60%),
                linear-gradient(180deg, var(--navy-2), var(--navy));
            background-attachment: fixed;
            -webkit-font-smoothing: antialiased;
        }
        main { max-width: 34rem; margin: 0 auto; padding: max(3rem, env(safe-area-inset-top)) 1.25rem 2.5rem; }
        .head { text-align: center; animation: rise .5s ease both; }
        .mark { width: 96px; height: 96px; margin: 0 auto 1.1rem; border-radius: 50%; display: grid; place-items: center;
            background: radial-gradient(circle at 30% 25%, #2A3146, #12172A); border: 1px solid rgba(220,180,121,.45);
            box-shadow: 0 0 0 6px rgba(220,180,121,.06), 0 18px 40px rgba(0,0,0,.45); }
        .mark img { width: 64px; filter: brightness(0) invert(1); opacity: .95; }
        h1 { margin: 0; font-size: 1.6rem; letter-spacing: .32em; font-weight: 600; }
        .bio { margin: .7rem auto 0; max-width: 26rem; color: var(--muted); font-size: .95rem; line-height: 1.8; }
        .social { display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem; margin-top: 1.4rem; }
        .social a { width: 46px; height: 46px; border-radius: 50%; display: grid; place-items: center; background: rgba(255,255,255,.06);
            border: 1px solid var(--line); transition: transform .2s ease, background .2s ease, border-color .2s ease; }
        .social a:hover { transform: translateY(-2px); background: rgba(220,180,121,.14); border-color: rgba(220,180,121,.5); }
        .glyph { width: 20px; height: 20px; background: currentColor; -webkit-mask: var(--src) center / contain no-repeat; mask: var(--src) center / contain no-repeat; }
        .social .glyph { color: var(--ink); }
        .section-label { margin: 2.1rem 0 .8rem; font-size: .78rem; letter-spacing: .08em; color: var(--muted); text-align: center; }
        .apps { display: grid; gap: .7rem; }
        .store { position: relative; display: flex; align-items: center; gap: .9rem; padding: .95rem 1.1rem; border-radius: 18px; text-decoration: none;
            color: var(--ink); background: rgba(255,255,255,.05); border: 1px solid var(--line); transition: transform .2s ease, border-color .2s ease, background .2s ease; }
        .store:hover { transform: translateY(-2px); border-color: rgba(220,180,121,.45); }
        .store .glyph { width: 26px; height: 26px; }
        .store small { display: block; color: var(--muted); font-size: .72rem; }
        .store strong { display: block; font-size: 1.02rem; font-weight: 600; }
        .store.is-yours { order: -1; background: linear-gradient(135deg, #E6C48E, var(--gold-deep)); color: #161B29; border-color: transparent;
            box-shadow: 0 14px 34px rgba(197,160,89,.28); }
        .store.is-yours small { color: rgba(22,27,41,.7); }
        .store .tag { display: none; margin-inline-start: auto; font-size: .7rem; font-weight: 600; padding: .2rem .6rem; border-radius: 999px; background: rgba(22,27,41,.12); }
        .store.is-yours .tag { display: inline-block; }
        .links { display: grid; gap: .7rem; }
        .link { display: flex; align-items: center; gap: .85rem; padding: 1rem 1.1rem; border-radius: 18px; text-decoration: none; color: var(--ink);
            background: rgba(255,255,255,.05); border: 1px solid var(--line); font-weight: 500;
            transition: transform .2s ease, border-color .2s ease, background .2s ease; animation: rise .5s ease both; }
        .link:hover { transform: translateY(-2px); background: rgba(255,255,255,.08); border-color: rgba(220,180,121,.45); }
        .link .badge { width: 38px; height: 38px; flex: none; border-radius: 12px; display: grid; place-items: center; background: rgba(220,180,121,.12); color: var(--gold); }
        .link .badge svg { width: 19px; height: 19px; }
        .link .badge .glyph { width: 18px; height: 18px; color: var(--gold); }
        .link span { flex: 1; }
        .link .arrow { width: 18px; height: 18px; color: var(--muted); transform: scaleX({{ $rtl ? '-1' : '1' }}); }
        footer { margin-top: 2.6rem; text-align: center; color: rgba(169,174,187,.7); font-size: .78rem; }
        footer a { color: var(--gold); text-decoration: none; }
        @keyframes rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
    </style>
</head>
<body>
<main>
    <header class="head">
        <div class="mark"><img src="{{ asset('identity/MAQAM-logo-header.png') }}" alt="MAQAM"></div>
        <h1>MAQAM</h1>
        <p class="bio">{{ $bio }}</p>

        @if($social->isNotEmpty())
            <nav class="social" aria-label="{{ __('store.links.follow') }}">
                @foreach($social as $item)
                    <a href="{{ route('store.links.go', $item) }}" target="_blank" rel="noopener" aria-label="{{ $item->label() }}" title="{{ $item->label() }}">
                        <i class="glyph" style="--src: url('{{ $icon($slugs[$item->platform]) }}')"></i>
                    </a>
                @endforeach
            </nav>
        @endif
    </header>

    @if($apps->isNotEmpty())
        <div class="section-label">{{ __('store.links.get_app') }}</div>
        <div class="apps">
            @foreach($apps as $item)
                <a class="store" data-platform="{{ $item->platform }}" href="{{ route('store.links.go', $item) }}" target="_blank" rel="noopener">
                    <i class="glyph" style="--src: url('{{ $icon($slugs[$item->platform]) }}')"></i>
                    <span>
                        <small>{{ $item->platform === 'ios' ? __('store.links.download_on') : __('store.links.get_it_on') }}</small>
                        <strong>{{ $item->label_ar || $item->label_en ? $item->label() : ($item->platform === 'ios' ? 'App Store' : 'Google Play') }}</strong>
                    </span>
                    <span class="tag">{{ __('store.links.for_your_phone') }}</span>
                </a>
            @endforeach
        </div>
    @endif

    @if($links->isNotEmpty())
        <div class="section-label">{{ __('store.links.more') }}</div>
        <div class="links">
            @foreach($links as $item)
                <a class="link" href="{{ route('store.links.go', $item) }}" target="_blank" rel="noopener" style="animation-delay: {{ $loop->index * 60 }}ms">
                    <span class="badge">
                        @if(isset($slugs[$item->platform]))
                            <i class="glyph" style="--src: url('{{ $icon($slugs[$item->platform]) }}')"></i>
                        @elseif($item->platform === 'website')
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1.5-5h15L21 9M3 9h18M3 9v11h18V9M9 20v-6h6v6"/></svg>
                        @else
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14a5 5 0 007.07 0l3-3a5 5 0 00-7.07-7.07l-1 1M14 10a5 5 0 00-7.07 0l-3 3a5 5 0 007.07 7.07l1-1"/></svg>
                        @endif
                    </span>
                    <span>{{ $item->label() }}</span>
                    <svg class="arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endforeach
        </div>
    @endif

    <footer>© {{ date('Y') }} <a href="{{ route('store.home') }}">MAQAM</a></footer>
</main>
<script>
    // Lead with the store this phone uses
    (function () {
        const ua = navigator.userAgent || '';
        const ios = /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const android = /Android/i.test(ua);
        const mine = ios ? 'ios' : android ? 'android' : null;
        if (!mine) return;
        document.querySelectorAll('.store[data-platform="' + mine + '"]').forEach(el => el.classList.add('is-yours'));
    })();
</script>
</body>
</html>
