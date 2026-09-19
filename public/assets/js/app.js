/* Gajiku — Aplikasi JS (Theme Management & UI Interactions) */
(function () {
    'use strict';

    var SIDEBAR_STORAGE_KEY = 'gajiku_sidebar_collapsed';
    var THEME_STORAGE_KEY = 'gajiku-theme';

    /**
     * Helper untuk membaca tema aktif saat ini
     */
    function getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }

    /**
     * Update UI ikon dan atribut ARIA tombol toggle di seluruh halaman
     */
    function updateThemeToggleUI(theme) {
        var isDark = theme === 'dark';
        var toggles = document.querySelectorAll('#themeToggle, .theme-toggle-btn, .login-theme-toggle');

        toggles.forEach(function (btn) {
            btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            btn.setAttribute('aria-label', isDark ? 'Beralih ke mode terang' : 'Beralih ke mode gelap');
            btn.setAttribute('title', isDark ? 'Beralih ke mode terang' : 'Beralih ke mode gelap');

            var icon = btn.querySelector('i');
            if (icon) {
                if (isDark) {
                    icon.classList.remove('bi-moon-stars');
                    icon.classList.add('bi-sun');
                } else {
                    icon.classList.remove('bi-sun');
                    icon.classList.add('bi-moon-stars');
                }
            }
        });
    }

    /**
     * Terapkan tema ke atribut <html> dan sinkronkan preferensi
     */
    function applyTheme(theme, persist) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        if (persist) {
            try {
                localStorage.setItem(THEME_STORAGE_KEY, theme);
            } catch (e) {
                // localStorage tidak tersedia (private mode)
            }
        }
        updateThemeToggleUI(theme);

        // Dispatch event agar komponen dinamis (mis. Chart.js) dapat merespons
        try {
            window.dispatchEvent(new CustomEvent('gajiku:themeChanged', {
                detail: { theme: theme }
            }));
        } catch (e) {}
    }

    var _lastThemeToggle = 0;
    /**
     * Expose fungsi toggle global ke window untuk integrasi langsung onclick
     */
    window.toggleGajikuTheme = function () {
        var now = Date.now();
        if (now - _lastThemeToggle < 250) return;
        _lastThemeToggle = now;

        var current = getCurrentTheme();
        var nextTheme = current === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme, true);
    };

    var _lastSidebarToggleTime = 0;
    /**
     * Expose fungsi toggle sidebar global
     */
    window.toggleSidebar = function (e) {
        if (e) {
            if (typeof e.preventDefault === 'function') e.preventDefault();
            if (typeof e.stopPropagation === 'function') e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
        }
        var now = Date.now();
        if (now - _lastSidebarToggleTime < 80) {
            return;
        }
        _lastSidebarToggleTime = now;

        var body = document.body;
        if (!body) return;
        var isCollapsed = body.classList.toggle('sidebar-collapsed');
        try {
            localStorage.setItem(SIDEBAR_STORAGE_KEY, isCollapsed ? '1' : '0');
        } catch (err) {}

        var sidebarToggle = document.getElementById('sidebarToggle');
        var sidebarIcon = document.getElementById('sidebarToggleIcon');
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        }
        if (sidebarIcon) {
            sidebarIcon.className = isCollapsed ? 'bi bi-layout-sidebar-inset fs-5' : 'bi bi-list fs-5';
        }
    };

    /**
     * Dengarkan perubahan preferensi warna dari sistem operasi
     */
    if (window.matchMedia) {
        var systemColorQuery = window.matchMedia('(prefers-color-scheme: dark)');
        var onSystemThemeChange = function (e) {
            var hasUserPref = false;
            try {
                hasUserPref = localStorage.getItem(THEME_STORAGE_KEY) !== null;
            } catch (err) {}

            // Hanya ikuti sistem jika user belum override tema via tombol
            if (!hasUserPref) {
                applyTheme(e.matches ? 'dark' : 'light', false);
            }
        };

        if (systemColorQuery.addEventListener) {
            systemColorQuery.addEventListener('change', onSystemThemeChange);
        } else if (systemColorQuery.addListener) {
            systemColorQuery.addListener(onSystemThemeChange);
        }
    }

    // Event delegation fallback jika tombol tema belum memiliki onclick langsung
    document.addEventListener('click', function (e) {
        var themeBtn = e.target.closest('#themeToggle, .theme-toggle-btn, .login-theme-toggle');
        if (themeBtn && !themeBtn.getAttribute('onclick')) {
            e.preventDefault();
            window.toggleGajikuTheme();
        }
    });

    function initUI() {
        var savedTheme = null;
        try {
            savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
        } catch (e) {}

        var initialTheme = savedTheme || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        applyTheme(initialTheme, false);

        // Pulihkan state sidebar
        var body = document.body;
        if (body) {
            try {
                if (localStorage.getItem(SIDEBAR_STORAGE_KEY) === '1') {
                    body.classList.add('sidebar-collapsed');
                    var sidebarToggle = document.getElementById('sidebarToggle');
                    var sidebarIcon = document.getElementById('sidebarToggleIcon');
                    if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
                    if (sidebarIcon) {
                        sidebarIcon.classList.remove('bi-list');
                        sidebarIcon.classList.add('bi-layout-sidebar-inset');
                    }
                }
            } catch (e) {}
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUI);
    } else {
        initUI();
    }
})();
