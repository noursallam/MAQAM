{{-- Floating support assistant. Bot checks live in Store\ChatController. --}}
<style>
    .mq-chat-toggle { position: fixed; right: 1rem; bottom: 1rem; z-index: 61; width: 3.25rem; height: 3.25rem; border-radius: 50%; border: 0; cursor: pointer; background: var(--mq-gold); color: var(--mq-on-gold); display: grid; place-items: center; box-shadow: 0 10px 30px rgba(0, 0, 0, .35); }
    .mq-chat-toggle:hover { background: var(--mq-gold-2); }
    .mq-chat-panel { position: fixed; right: 1rem; bottom: 5rem; z-index: 61; width: min(22.5rem, calc(100vw - 2rem)); height: min(30rem, calc(100vh - 7rem)); display: flex; flex-direction: column; overflow: hidden; background: var(--mq-surface); color: var(--mq-text); border: 1px solid var(--mq-line); border-radius: 1rem; box-shadow: 0 24px 60px rgba(0, 0, 0, .45); font-family: var(--mq-font); }
    .mq-chat-panel[hidden] { display: none; }
    .mq-chat-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .85rem 1rem; background: var(--mq-surface-2); border-bottom: 1px solid var(--mq-line); }
    .mq-chat-head strong { display: block; font-size: .95rem; }
    .mq-chat-head span { font-size: .75rem; color: var(--mq-muted); }
    .mq-chat-close { background: none; border: 0; color: var(--mq-muted); cursor: pointer; font-size: 1.4rem; line-height: 1; padding: .25rem; }
    .mq-chat-log { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: .6rem; }
    .mq-chat-msg { max-width: 85%; padding: .55rem .8rem; border-radius: .9rem; font-size: .88rem; line-height: 1.55; white-space: pre-wrap; overflow-wrap: anywhere; }
    .mq-chat-msg.is-bot { align-self: flex-start; background: var(--mq-surface-2); border: 1px solid var(--mq-line); }
    .mq-chat-msg.is-user { align-self: flex-end; background: var(--mq-gold); color: var(--mq-on-gold); }
    .mq-chat-msg.is-error { align-self: center; background: rgba(239, 68, 68, .12); border: 1px solid rgba(239, 68, 68, .3); color: #f87171; font-size: .8rem; }
    .mq-chat-form { display: flex; gap: .5rem; padding: .75rem; border-top: 1px solid var(--mq-line); }
    .mq-chat-form input[type="text"] { flex: 1; min-width: 0; padding: .6rem .8rem; border-radius: .7rem; border: 1px solid var(--mq-line); background: var(--mq-bg); color: var(--mq-text); font: inherit; font-size: .88rem; }
    .mq-chat-form button { padding: .6rem .9rem; border-radius: .7rem; border: 0; cursor: pointer; background: var(--mq-gold); color: var(--mq-on-gold); font: inherit; font-size: .85rem; font-weight: 600; }
    .mq-chat-form button:disabled { opacity: .5; cursor: default; }
    .mq-chat-trap { position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; }
</style>

<button type="button" class="mq-chat-toggle" id="mqChatToggle" aria-label="{{ __('store.chat.open') }}" aria-expanded="false" aria-controls="mqChatPanel">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m8-2a9 9 0 01-13.2 7.96L3 21l1.1-4.4A9 9 0 1121 12z"/></svg>
</button>

<section class="mq-chat-panel" id="mqChatPanel" hidden aria-label="{{ __('store.chat.title') }}">
    <header class="mq-chat-head">
        <div>
            <strong>{{ __('store.chat.title') }}</strong>
            <span>{{ __('store.chat.subtitle') }}</span>
        </div>
        <button type="button" class="mq-chat-close" id="mqChatClose" aria-label="{{ __('store.chat.close') }}">&times;</button>
    </header>
    <div class="mq-chat-log" id="mqChatLog" aria-live="polite">
        <div class="mq-chat-msg is-bot">{{ __('store.chat.greeting') }}</div>
    </div>
    <form class="mq-chat-form" id="mqChatForm" autocomplete="off">
        {{-- Honeypot: invisible to people, tempting to bots --}}
        <input type="text" name="website" class="mq-chat-trap" tabindex="-1" aria-hidden="true">
        <input type="text" id="mqChatInput" maxlength="500" placeholder="{{ __('store.chat.placeholder') }}" aria-label="{{ __('store.chat.placeholder') }}" required>
        <button type="submit" id="mqChatSend">{{ __('store.chat.send') }}</button>
    </form>
</section>

<script>
(function () {
    const toggle = document.getElementById('mqChatToggle');
    const panel = document.getElementById('mqChatPanel');
    const log = document.getElementById('mqChatLog');
    const form = document.getElementById('mqChatForm');
    const input = document.getElementById('mqChatInput');
    const sendButton = document.getElementById('mqChatSend');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const text = {
        error: @js(__('store.chat.unavailable')),
        thinking: @js(__('store.chat.thinking')),
    };
    let challenge = null;

    function add(message, kind) {
        const bubble = document.createElement('div');
        bubble.className = 'mq-chat-msg ' + kind;
        // textContent, never innerHTML: replies are shown as plain text
        bubble.textContent = message;
        log.appendChild(bubble);
        log.scrollTop = log.scrollHeight;
        return bubble;
    }

    async function request(url, options) {
        const response = await fetch(url, Object.assign({
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            credentials: 'same-origin',
        }, options));
        const body = await response.json().catch(() => ({}));
        if (body.challenge) challenge = body.challenge;
        return { ok: response.ok, body };
    }

    // Proof of work: find a counter whose SHA-256 with the nonce starts with `bits` zero bits
    async function solve(puzzle) {
        const encoder = new TextEncoder();
        for (let counter = 0; ; counter++) {
            const digest = new Uint8Array(await crypto.subtle.digest('SHA-256', encoder.encode(puzzle.nonce + ':' + counter)));
            let zeros = 0;
            for (const byte of digest) {
                if (byte === 0) { zeros += 8; continue; }
                zeros += Math.clz32(byte) - 24;
                break;
            }
            if (zeros >= puzzle.bits) return counter;
        }
    }

    function open() {
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        input.focus();
        if (!challenge) request(@js(route('store.chat.challenge')), { method: 'GET' });
    }

    toggle.addEventListener('click', () => (panel.hidden ? open() : close()));
    document.getElementById('mqChatClose').addEventListener('click', close);

    function close() {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;

        add(message, 'is-user');
        input.value = '';
        sendButton.disabled = true;
        const pending = add(text.thinking, 'is-bot');

        try {
            if (!challenge) await request(@js(route('store.chat.challenge')), { method: 'GET' });
            const puzzle = challenge;
            challenge = null;
            const counter = await solve(puzzle);
            const { ok, body } = await request(@js(route('store.chat.send')), {
                method: 'POST',
                body: JSON.stringify({ message, counter, website: form.website.value }),
            });
            pending.remove();
            add(ok ? body.reply : (body.message || text.error), ok ? 'is-bot' : 'is-error');
        } catch (e) {
            pending.remove();
            add(text.error, 'is-error');
        } finally {
            sendButton.disabled = false;
            input.focus();
        }
    });
})();
</script>
