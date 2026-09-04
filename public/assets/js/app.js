/* Gajiku — aplikasi JS */
(function () {
    'use strict';

    var STORAGE_KEY = 'gajiku_sidebar_collapsed';

    document.addEventListener('DOMContentLoaded', function () {
        var body = document.body;
        var toggle = document.getElementById('sidebarToggle');
        var icon = document.getElementById('sidebarToggleIcon');

        if (!toggle) {
            return;
        }

        // Terapkan state tersimpan saat halaman dibuka (sebelum render pertama
        // supaya tidak kedip). Nilai disimpan dari sesi sebelumnya.
        try {
            if (localStorage.getItem(STORAGE_KEY) === '1') {
                body.classList.add('sidebar-collapsed');
            }
        } catch (e) {
            // localStorage tidak tersedia — abaikan
        }

        function updateAria() {
            var collapsed = body.classList.contains('sidebar-collapsed');
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            if (icon) {
                icon.classList.toggle('bi-list', !collapsed);
                icon.classList.toggle('bi-layout-sidebar-inset', collapsed);
            }
        }

        updateAria();

        toggle.addEventListener('click', function () {
            body.classList.toggle('sidebar-collapsed');
            try {
                var collapsed = body.classList.contains('sidebar-collapsed');
                localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
            } catch (e) {
                // abaikan
            }
            updateAria();
        });
    });
})();
