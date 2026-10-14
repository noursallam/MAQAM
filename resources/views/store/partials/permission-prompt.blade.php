{{-- Asks a signed-in customer for location and notifications. Answers go to Store\VisitorPermissionController. --}}
@php
    $permUser = auth()->user();
    $permReportedAt = $permUser->permissions_reported_at;
    $permConfig = [
        'endpoint' => route('store.permissions.store'),
        // Right after sign-in, or a customer we have never asked
        'ask' => session('ask_permissions') === true || $permReportedAt === null,
        // Silently refresh the saved position of customers who already allowed it
        'refresh' => $permReportedAt === null || $permReportedAt->lt(now()->subHours(12)),
        'push' => \App\Http\Controllers\Store\WebPushController::config(),
        'pushSw' => route('store.push.sw', [], false),
        'firebase' => \App\Http\Controllers\Store\WebPushController::FIREBASE_VERSION,
    ];
@endphp
<style>
    .mq-perm { position: fixed; inset: 0; z-index: 95; display: grid; place-items: end center; padding: 1rem; }
    .mq-perm[hidden] { display: none !important; }
    .mq-perm-backdrop { position: absolute; inset: 0; background: rgba(5, 7, 13, .62); backdrop-filter: blur(4px); }
    .mq-perm-card { position: relative; width: min(440px, 100%); background: var(--mq-surface-2); color: var(--mq-text); border: 1px solid var(--mq-line); border-radius: 18px; box-shadow: var(--mq-shadow); padding: 1.35rem 1.25rem 1.15rem; font-family: var(--mq-font); animation: mqPermIn .22s ease; }
    @media (min-width: 640px) { .mq-perm { place-items: center; } }
    @keyframes mqPermIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    .mq-perm-card h2 { margin: 0 0 .35rem; font-size: 1.12rem; font-weight: 700; }
    .mq-perm-card > p { margin: 0 0 1rem; color: var(--mq-muted); font-size: .9rem; }
    .mq-perm-list { list-style: none; margin: 0 0 1.15rem; padding: 0; display: grid; gap: .65rem; }
    .mq-perm-item { display: flex; gap: .75rem; align-items: flex-start; padding: .75rem .85rem; border: 1px solid var(--mq-line); border-radius: 12px; background: var(--mq-surface); }
    .mq-perm-item[hidden] { display: none; }
    .mq-perm-icon { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 10px; display: grid; place-items: center; background: rgba(197, 160, 89, .14); color: var(--mq-gold); }
    .mq-perm-item strong { display: block; font-size: .92rem; }
    .mq-perm-item span { display: block; font-size: .8rem; color: var(--mq-muted); line-height: 1.5; }
    .mq-perm-actions { display: flex; gap: .6rem; }
    .mq-perm-actions .mq-btn { flex: 1; justify-content: center; }
    .mq-perm-note { margin: .8rem 0 0; font-size: .75rem; color: var(--mq-muted); text-align: center; }
    .mq-perm-note a { color: var(--mq-gold); }
</style>

<div class="mq-perm" id="mqPerm" hidden>
    <div class="mq-perm-backdrop" data-mq-perm-later></div>
    <section class="mq-perm-card" role="dialog" aria-modal="true" aria-labelledby="mqPermTitle" aria-describedby="mqPermText">
        <h2 id="mqPermTitle">{{ __('store.permissions.title') }}</h2>
        <p id="mqPermText">{{ __('store.permissions.intro') }}</p>
        <ul class="mq-perm-list">
            <li class="mq-perm-item" data-mq-perm-row="location">
                <div class="mq-perm-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                </div>
                <div>
                    <strong>{{ __('store.permissions.location_title') }}</strong>
                    <span>{{ __('store.permissions.location_text') }}</span>
                </div>
            </li>
            <li class="mq-perm-item" data-mq-perm-row="notification">
                <div class="mq-perm-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                </div>
                <div>
                    <strong>{{ __('store.permissions.notification_title') }}</strong>
                    <span>{{ __('store.permissions.notification_text') }}</span>
                </div>
            </li>
        </ul>
        <div class="mq-perm-actions">
            <button type="button" class="mq-btn mq-btn-primary" data-mq-perm-allow>{{ __('store.permissions.allow') }}</button>
            <button type="button" class="mq-btn mq-btn-ghost" data-mq-perm-later>{{ __('store.permissions.later') }}</button>
        </div>
        <p class="mq-perm-note">{!! __('store.permissions.privacy_note', ['url' => e(route('store.privacy'))]) !!}</p>
    </section>
</div>

<script>
(function () {
    var cfg = @json($permConfig);
    var SNOOZE_KEY = 'maqam_perm_snooze_until';
    var SNOOZE_DAYS = 3;
    var modal = document.getElementById('mqPerm');
    if (!modal) return;

    function store(key, value) { try { value === null ? localStorage.removeItem(key) : localStorage.setItem(key, value); } catch (e) {} }
    function read(key) { try { return localStorage.getItem(key); } catch (e) { return null; } }

    function notificationState() {
        if (!('Notification' in window) || !window.isSecureContext) return 'unsupported';
        return Notification.permission === 'default' ? 'prompt' : Notification.permission;
    }

    function locationState() {
        if (!('geolocation' in navigator) || !window.isSecureContext) return Promise.resolve('unsupported');
        if (!navigator.permissions || !navigator.permissions.query) return Promise.resolve('prompt');
        return navigator.permissions.query({ name: 'geolocation' })
            .then(function (s) { return s.state; })
            .catch(function () { return 'prompt'; });
    }

    // Resolves with { state, coords }. Never rejects.
    function position() {
        return new Promise(function (resolve) {
            navigator.geolocation.getCurrentPosition(
                function (p) { resolve({ state: 'granted', coords: p.coords }); },
                function (err) { resolve({ state: err.code === 1 ? 'denied' : 'granted', coords: null }); },
                { enableHighAccuracy: false, timeout: 15000, maximumAge: 300000 }
            );
        });
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src; s.async = true; s.onload = resolve; s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    // FCM token for this browser, or null when web push isn't set up
    function pushToken() {
        if (!cfg.push || !('serviceWorker' in navigator)) return Promise.resolve(null);
        var base = 'https://www.gstatic.com/firebasejs/' + cfg.firebase + '/';
        return loadScript(base + 'firebase-app-compat.js')
            .then(function () { return loadScript(base + 'firebase-messaging-compat.js'); })
            .then(function () { return navigator.serviceWorker.register(cfg.pushSw, { scope: '/' }); })
            .then(function (registration) {
                if (!firebase.apps.length) firebase.initializeApp(cfg.push);
                return firebase.messaging().getToken({ vapidKey: cfg.push.vapidKey, serviceWorkerRegistration: registration });
            })
            .catch(function () { return null; });
    }

    function report(location, notification, coords, token) {
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var body = { location_permission: location, notification_permission: notification };
        if (coords) {
            body.latitude = coords.latitude;
            body.longitude = coords.longitude;
            body.accuracy = Math.round(coords.accuracy || 0);
        }
        if (token) body.push_token = token;

        return fetch(cfg.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.content : '' },
            body: JSON.stringify(body)
        }).catch(function () {});
    }

    // Collects whatever is already allowed (no browser prompt) and saves it
    function collect(location, notification) {
        return Promise.all([
            location === 'granted' ? position() : Promise.resolve({ state: location, coords: null }),
            notification === 'granted' ? pushToken() : Promise.resolve(null)
        ]).then(function (r) { return report(r[0].state, notification, r[0].coords, r[1]); });
    }

    function open(location, notification) {
        modal.querySelector('[data-mq-perm-row="location"]').hidden = location !== 'prompt';
        modal.querySelector('[data-mq-perm-row="notification"]').hidden = notification !== 'prompt';
        modal.hidden = false;
        modal.querySelector('[data-mq-perm-allow]').focus();
        document.addEventListener('keydown', onKey);
    }

    function close() {
        modal.hidden = true;
        document.removeEventListener('keydown', onKey);
    }

    function onKey(e) { if (e.key === 'Escape') later(); }

    var current = { location: 'prompt', notification: 'prompt' };

    function later() {
        close();
        store(SNOOZE_KEY, String(Date.now() + SNOOZE_DAYS * 864e5));
        collect(current.location, current.notification);
    }

    modal.querySelectorAll('[data-mq-perm-later]').forEach(function (el) { el.addEventListener('click', later); });

    modal.querySelector('[data-mq-perm-allow]').addEventListener('click', function () {
        close();
        store(SNOOZE_KEY, null);
        // Both browser prompts start inside the click, which some browsers require
        var notificationAsk = current.notification === 'prompt'
            ? new Promise(function (resolve) {
                // Older Safari only supports the callback form
                var asked = Notification.requestPermission(resolve);
                if (asked && asked.then) asked.then(resolve, resolve);
            }).then(notificationState)
            : Promise.resolve(current.notification);
        var locationAsk = current.location === 'unsupported'
            ? Promise.resolve({ state: 'unsupported', coords: null })
            : (current.location === 'denied' ? Promise.resolve({ state: 'denied', coords: null }) : position());

        Promise.all([locationAsk, notificationAsk]).then(function (r) {
            return (r[1] === 'granted' ? pushToken() : Promise.resolve(null)).then(function (token) {
                return report(r[0].state, r[1], r[0].coords, token);
            });
        });
    });

    locationState().then(function (location) {
        current = { location: location, notification: notificationState() };
        var undecided = current.location === 'prompt' || current.notification === 'prompt';
        var snoozed = Number(read(SNOOZE_KEY) || 0) > Date.now();

        if (undecided && cfg.ask && !snoozed) {
            // Let the page settle first so the prompt doesn't feel like a wall
            setTimeout(function () { open(current.location, current.notification); }, 900);
        } else if (cfg.ask || cfg.refresh) {
            collect(current.location, current.notification);
        }
    });
})();
</script>
