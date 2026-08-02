document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('mqMenuToggle');
    var nav = document.getElementById('mqNav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    var key = 'maqam_store_theme';

    function currentTheme() {
        return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        try { localStorage.setItem(key, theme); } catch (e) {}
        document.querySelectorAll('[data-mq-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            var isAr = document.documentElement.lang === 'ar';
            btn.title = theme === 'dark'
                ? (isAr ? 'الوضع النهاري' : 'Light mode')
                : (isAr ? 'الوضع الليلي' : 'Dark mode');
        });
    }

    document.querySelectorAll('[data-mq-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
    });

    applyTheme(currentTheme());

    var filters = document.getElementById('mqShopFilters');
    var backdrop = document.querySelector('.mq-filters-backdrop');

    function setFiltersOpen(open) {
        if (!filters) return;
        filters.classList.toggle('is-open', open);
        if (backdrop) {
            backdrop.hidden = !open;
        }
        document.body.style.overflow = open ? 'hidden' : '';
    }

    document.querySelectorAll('[data-mq-filters-open]').forEach(function (btn) {
        btn.addEventListener('click', function () { setFiltersOpen(true); });
    });
    document.querySelectorAll('[data-mq-filters-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { setFiltersOpen(false); });
    });

    var products = document.querySelector('[data-mq-products]');
    document.querySelectorAll('[data-mq-view]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var mode = btn.getAttribute('data-mq-view');
            document.querySelectorAll('[data-mq-view]').forEach(function (el) {
                el.classList.toggle('is-active', el === btn);
            });
            if (products) {
                products.classList.toggle('is-list', mode === 'list');
            }
        });
    });

    document.querySelectorAll('.mq-cat-item').forEach(function (chip) {
        chip.addEventListener('click', function () {
            document.querySelectorAll('.mq-cat-item').forEach(function (el) {
                el.classList.toggle('is-active', el === chip);
            });
        });
    });

    var searchModal = document.getElementById('mqSearchModal');
    var searchInput = document.getElementById('mqSearchInput');
    var searchOpeners = document.querySelectorAll('[data-mq-search-open]');

    function setSearchOpen(open) {
        if (!searchModal) return;
        searchModal.hidden = !open;
        searchModal.classList.toggle('is-open', open);
        document.body.classList.toggle('mq-search-open', open);
        if (open && searchInput) {
            setTimeout(function () { searchInput.focus(); }, 30);
        }
    }

    searchOpeners.forEach(function (btn) {
        btn.addEventListener('click', function () { setSearchOpen(true); });
    });

    document.querySelectorAll('[data-mq-search-close]').forEach(function (el) {
        el.addEventListener('click', function () { setSearchOpen(false); });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setSearchOpen(false);
        if ((e.key === 'k' || e.key === 'K') && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            setSearchOpen(true);
        }
    });
});
