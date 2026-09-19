<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gajiku - Sistem Informasi Penggajian</title>

    <!-- Theme Engine & FOUC Prevention Script (Inline for Zero-Latency & Cache-Proof Execution) -->
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('gajiku-theme');
                var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.setAttribute('data-bs-theme', theme);
            } catch (e) {}
        })();

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

        var _lastThemeToggle = 0;
        window.toggleGajikuTheme = function () {
            var now = Date.now();
            if (now - _lastThemeToggle < 250) {
                return;
            }
            _lastThemeToggle = now;

            var current = document.documentElement.getAttribute('data-theme') || 'light';
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            document.documentElement.setAttribute('data-bs-theme', next);
            try {
                localStorage.setItem('gajiku-theme', next);
            } catch (e) {}
            updateThemeToggleUI(next);
            try {
                window.dispatchEvent(new CustomEvent('gajiku:themeChanged', { detail: { theme: next } }));
            } catch (e) {}
        };

        var _lastSidebarToggleTime = 0;
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
                localStorage.setItem('gajiku_sidebar_collapsed', isCollapsed ? '1' : '0');
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

        document.addEventListener('DOMContentLoaded', function () {
            var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            updateThemeToggleUI(currentTheme);

            try {
                if (localStorage.getItem('gajiku_sidebar_collapsed') === '1' && document.body) {
                    document.body.classList.add('sidebar-collapsed');
                    var sidebarToggle = document.getElementById('sidebarToggle');
                    var sidebarIcon = document.getElementById('sidebarToggleIcon');
                    if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
                    if (sidebarIcon) {
                        sidebarIcon.classList.remove('bi-list');
                        sidebarIcon.classList.add('bi-layout-sidebar-inset');
                    }
                }
            } catch (e) {}
        });
    </script>

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <?php
    $styleVersion = file_exists(dirname(__DIR__, 2) . '/public/assets/css/style.css')
        ? (string) filemtime(dirname(__DIR__, 2) . '/public/assets/css/style.css')
        : '1.0';
    ?>
    <link href="assets/css/style.css?v=<?= $styleVersion ?>" rel="stylesheet">
    <link href="/assets/css/style.css?v=<?= $styleVersion ?>" rel="stylesheet">

    <!-- Critical Dark Mode Override CSS (Guarantees Instant Dark Theme across Bootstrap & Containers) -->
    <style>
        :root {
            --bg-color: #F8FAFC;
            --surface-color: #FFFFFF;
            --surface-muted: #F1F5F9;
            --surface-hover: #E2E8F0;
            --border-color: #E2E8F0;
            --text-primary: #0F172A;
            --text-secondary: #64748B;
            --sidebar-bg: #1E3A5F;
            --topbar-bg: #FFFFFF;
        }

        [data-theme="dark"], [data-bs-theme="dark"] {
            --bg-color: #0B1420;
            --surface-color: #111D2E;
            --surface-muted: #16263A;
            --surface-hover: #1A2C44;
            --border-color: #24344C;
            --text-primary: #E2E8F0;
            --text-secondary: #94A3B8;
            --sidebar-bg: #0E1A2B;
            --topbar-bg: #111D2E;
        }

        html, body {
            overflow-x: hidden;
        }

        body {
            background-color: var(--bg-color) !important;
            color: var(--text-primary) !important;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        [data-theme="dark"] body,
        [data-theme="dark"] body.bg-light,
        [data-theme="dark"] .bg-light,
        [data-theme="dark"] .app-wrapper,
        [data-theme="dark"] .main-content,
        [data-theme="dark"] main,
        [data-bs-theme="dark"] body,
        [data-bs-theme="dark"] body.bg-light,
        [data-bs-theme="dark"] .bg-light,
        [data-bs-theme="dark"] .app-wrapper,
        [data-bs-theme="dark"] .main-content,
        [data-bs-theme="dark"] main {
            background-color: #0B1420 !important;
            color: #E2E8F0 !important;
        }

        [data-theme="dark"] .bg-white,
        [data-theme="dark"] .card.bg-white,
        [data-theme="dark"] .card-header.bg-white,
        [data-theme="dark"] .card-footer.bg-white,
        [data-theme="dark"] .breadcrumb.bg-white,
        [data-theme="dark"] .modal-content.bg-white,
        [data-bs-theme="dark"] .bg-white,
        [data-bs-theme="dark"] .card.bg-white,
        [data-bs-theme="dark"] .card-header.bg-white,
        [data-bs-theme="dark"] .card-footer.bg-white,
        [data-bs-theme="dark"] .breadcrumb.bg-white,
        [data-bs-theme="dark"] .modal-content.bg-white {
            background-color: #111D2E !important;
            color: #E2E8F0 !important;
            border-color: #24344C !important;
        }

        [data-theme="dark"] .card,
        [data-bs-theme="dark"] .card {
            background-color: #111D2E !important;
            border-color: #24344C !important;
            color: #E2E8F0 !important;
        }

        [data-theme="dark"] .card-header,
        [data-bs-theme="dark"] .card-header {
            background-color: #16263A !important;
            border-bottom-color: #24344C !important;
            color: #E2E8F0 !important;
        }

        [data-theme="dark"] header.sticky-top,
        [data-bs-theme="dark"] header.sticky-top {
            background-color: #111D2E !important;
            border-bottom-color: #24344C !important;
        }

        /* ===== Sidebar Base & Layout Rules ===== */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
            background-color: var(--bg-color);
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1030;
            background-color: var(--sidebar-bg) !important;
            border-right: 1px solid var(--sidebar-border);
            color: var(--sidebar-text);
            flex-shrink: 0;
            will-change: margin-left;
            transition: margin-left 0.15s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s ease;
        }

        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            min-width: 0;
            flex-grow: 1;
            background-color: var(--bg-color) !important;
            transition: margin-left 0.15s cubic-bezier(0.4, 0, 0.2, 1), width 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-collapsed .sidebar {
            margin-left: -250px !important;
        }

        .sidebar-collapsed .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }

        [data-theme="dark"] .sidebar,
        [data-bs-theme="dark"] .sidebar {
            background-color: #0E1A2B !important;
            border-right-color: #24344C !important;
        }

        [data-theme="dark"] .text-dark,
        [data-bs-theme="dark"] .text-dark {
            color: #E2E8F0 !important;
        }

        [data-theme="dark"] .text-muted,
        [data-bs-theme="dark"] .text-muted {
            color: #94A3B8 !important;
        }

        [data-theme="dark"] .border,
        [data-bs-theme="dark"] .border {
            border-color: #24344C !important;
        }

        /* ===== Table Dark Mode Precision & Seamless Card Integration ===== */
        .table-responsive {
            background-color: transparent !important;
            border: none !important;
        }

        .card > .table-responsive,
        .card-body > .table-responsive {
            border: none !important;
            border-radius: 0 !important;
            margin: 0 !important;
        }

        .table {
            --bs-table-bg: transparent !important;
            --bs-table-accent-bg: transparent !important;
            --bs-table-color: var(--text-primary) !important;
            --bs-table-border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
            background-color: transparent !important;
            margin-bottom: 0 !important;
        }

        .table > :not(caption) > * > * {
            box-shadow: none !important;
            color: inherit !important;
            background-color: transparent !important;
        }

        [data-theme="dark"] .table,
        [data-bs-theme="dark"] .table {
            --bs-table-border-color: #24344C !important;
            border-color: #24344C !important;
        }

        [data-theme="dark"] .table thead,
        [data-theme="dark"] .table thead tr,
        [data-theme="dark"] .table thead th,
        [data-theme="dark"] .table .table-light,
        [data-theme="dark"] .table thead.table-light,
        [data-theme="dark"] .table thead.table-light tr,
        [data-theme="dark"] .table thead.table-light th,
        [data-bs-theme="dark"] .table thead,
        [data-bs-theme="dark"] .table thead tr,
        [data-bs-theme="dark"] .table thead th,
        [data-bs-theme="dark"] .table .table-light,
        [data-bs-theme="dark"] .table thead.table-light,
        [data-bs-theme="dark"] .table thead.table-light tr,
        [data-bs-theme="dark"] .table thead.table-light th {
            background-color: #16263A !important;
            color: #E2E8F0 !important;
            border-bottom: 2px solid #24344C !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            padding: 12px 16px !important;
            box-shadow: none !important;
        }

        [data-theme="dark"] .table tbody tr,
        [data-bs-theme="dark"] .table tbody tr {
            background-color: #111D2E !important;
            transition: background-color 0.15s ease;
        }

        [data-theme="dark"] .table tbody td,
        [data-bs-theme="dark"] .table tbody td {
            padding: 12px 16px !important;
            border-bottom: 1px solid #24344C !important;
            color: #E2E8F0 !important;
            background-color: transparent !important;
            box-shadow: none !important;
        }

        [data-theme="dark"] .table tbody tr:last-child td,
        [data-bs-theme="dark"] .table tbody tr:last-child td {
            border-bottom: none !important;
        }

        [data-theme="dark"] .table-hover > tbody > tr:hover,
        [data-theme="dark"] .table tbody tr:hover,
        [data-bs-theme="dark"] .table-hover > tbody > tr:hover,
        [data-bs-theme="dark"] .table tbody tr:hover {
            background-color: #1A2C44 !important;
        }

        [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd),
        [data-bs-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #0E1A2B !important;
        }

        [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(even),
        [data-bs-theme="dark"] .table-striped > tbody > tr:nth-of-type(even) {
            background-color: #111D2E !important;
        }

        /* Status Badges in Tables */
        [data-theme="dark"] .badge.bg-light,
        [data-bs-theme="dark"] .badge.bg-light {
            background-color: #16263A !important;
            color: #E2E8F0 !important;
            border: 1px solid #24344C !important;
        }

        [data-theme="dark"] .badge.bg-warning,
        [data-bs-theme="dark"] .badge.bg-warning,
        [data-theme="dark"] .badge.bg-warning.text-dark,
        [data-bs-theme="dark"] .badge.bg-warning.text-dark {
            background-color: rgba(251, 191, 36, 0.16) !important;
            color: #FBBF24 !important;
            border: 1px solid rgba(251, 191, 36, 0.28) !important;
        }

        [data-theme="dark"] .badge.bg-info,
        [data-bs-theme="dark"] .badge.bg-info,
        [data-theme="dark"] .badge.bg-info.text-dark,
        [data-bs-theme="dark"] .badge.bg-info.text-dark {
            background-color: rgba(96, 165, 250, 0.16) !important;
            color: #60A5FA !important;
            border: 1px solid rgba(96, 165, 250, 0.28) !important;
        }

        [data-theme="dark"] .badge.bg-success,
        [data-bs-theme="dark"] .badge.bg-success {
            background-color: rgba(74, 222, 128, 0.16) !important;
            color: #4ADE80 !important;
            border: 1px solid rgba(74, 222, 128, 0.28) !important;
        }

        [data-theme="dark"] .badge.bg-danger,
        [data-bs-theme="dark"] .badge.bg-danger {
            background-color: rgba(248, 113, 113, 0.16) !important;
            color: #F87171 !important;
            border: 1px solid rgba(248, 113, 113, 0.28) !important;
        }

        [data-theme="dark"] .badge.bg-secondary,
        [data-bs-theme="dark"] .badge.bg-secondary {
            background-color: rgba(148, 163, 184, 0.16) !important;
            color: #94A3B8 !important;
            border: 1px solid rgba(148, 163, 184, 0.28) !important;
        }
    </style>
</head>
<body>
<div class="app-wrapper">
