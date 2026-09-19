<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/database.php';
require_once dirname(__DIR__) . '/app/includes/auth.php';

if (isAuthenticated()) {
    redirect('/index.php');
}

$errors = [];
$username = '';
$logoutMessage = isset($_GET['logout']) ? 'Sesi berhasil diakhiri.' : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = 'Username dan password wajib diisi.';
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare(
                'SELECT id, username, password, role
                 FROM users
                 WHERE username = :username
                 LIMIT 1'
            );
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user === false || !password_verify($password, (string) $user['password'])) {
                $errors[] = 'Username atau password salah.';
            } else {
                loginUser($user);
                redirect('/index.php');
            }
        } catch (PDOException) {
            $errors[] = 'Koneksi database gagal. Periksa konfigurasi lalu coba lagi.';
        }
    }
}

http_response_code(200);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — Gajiku</title>

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
            var toggles = document.querySelectorAll('#themeToggle, .login-theme-toggle');
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

        window.toggleGajikuTheme = function () {
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

        window.fillLogin = function (u, p) {
            var userInput = document.getElementById('username');
            var passInput = document.getElementById('password');
            if (userInput) userInput.value = u;
            if (passInput) passInput.value = p;
        };

        document.addEventListener('DOMContentLoaded', function () {
            var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            updateThemeToggleUI(currentTheme);
        });
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&family=Noto+Serif:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* ===== Theme Variables ===== */
        :root {
            --login-bg: #F8FAFC;
            --login-surface: #FFFFFF;
            --login-text: #0F172A;
            --login-muted: #64748B;
            --login-border: #CBD5E1;
            --login-input-bg: #FFFFFF;
        }

        [data-theme="dark"], [data-bs-theme="dark"] {
            --login-bg: #0B1420;
            --login-surface: #111D2E;
            --login-text: #E2E8F0;
            --login-muted: #94A3B8;
            --login-border: #33465F;
            --login-input-bg: #0E1A2B;
        }

        /* ===== Reset & halaman ===== */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            background-color: var(--login-bg) !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            color: var(--login-text) !important;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* ===== Split-screen Layout Desktop ===== */
        .login-card {
            display: flex;
            width: 100%;
            min-height: 100vh;
            background-color: var(--login-surface) !important;
            transition: background-color 0.2s ease;
        }

        /* ===== Panel kiri (biru malam + glow lampu) ===== */
        .login-aside {
            flex: 0 0 45%;
            width: 45%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 56px;
            background-color: #0D1B2E;
            background-image: radial-gradient(
                ellipse 75% 50% at 50% 6%,
                rgba(251, 191, 36, 0.22) 0%,
                rgba(251, 191, 36, 0) 70%
            );
            color: #E9EFF6;
            overflow-y: auto;
        }

        .aside-brand {
            max-width: 460px;
        }

        .aside-brand__logo {
            margin: 0;
            font-family: 'Noto Serif', Georgia, serif;
            font-weight: 700;
            font-size: 24px;
            line-height: 1.2;
            letter-spacing: 2.5px;
            color: #E9EFF6;
        }

        .aside-brand__company {
            margin: 6px 0 0;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #FBBF24;
        }

        .aside-brand__title {
            margin: 24px 0 0;
            font-family: 'Noto Serif', Georgia, serif;
            font-weight: 700;
            font-size: 26px;
            line-height: 1.3;
            color: #E9EFF6;
        }

        .aside-brand__desc {
            margin: 12px 0 0;
            font-size: 15px;
            line-height: 1.6;
            color: rgba(233, 239, 246, 0.7);
        }

        .aside-list {
            list-style: none;
            margin: 24px 0 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 460px;
        }

        .aside-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            line-height: 1.5;
            color: rgba(233, 239, 246, 0.85);
        }

        .aside-list svg { flex-shrink: 0; }

        .aside-art {
            flex: 1 1 auto;
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 0;
            max-width: 460px;
            width: 100%;
        }

        .aside-art svg {
            display: block;
            width: 100%;
            max-width: 320px;
            max-height: 240px;
            height: auto;
            opacity: 0.38;
        }

        .aside-foot {
            margin: 0;
            padding-top: 16px;
            font-size: 12px;
            line-height: 1.4;
            color: rgba(233, 239, 246, 0.4);
            max-width: 460px;
        }

        /* ===== Panel kanan (form) ===== */
        .login-panel {
            flex: 1 1 55%;
            width: 55%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 32px;
            background-color: var(--login-surface) !important;
            overflow-y: auto;
            transition: background-color 0.2s ease;
        }

        .login-panel__inner {
            width: 100%;
            max-width: 400px;
        }

        .login-mobile-brand { display: none; }

        .login-mobile-badge {
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #FBBF24;
            background-color: #0D1B2E;
            border-radius: 4px;
        }

        .panel-brand { text-align: center; margin-bottom: 28px; }

        .panel-brand__logo {
            margin: 0;
            font-family: 'Noto Serif', Georgia, serif;
            font-weight: 700;
            font-size: 26px;
            line-height: 1.2;
            letter-spacing: 2px;
            color: #1E3A5F;
        }

        .panel-brand__subtitle {
            margin: 6px 0 0;
            font-size: 14px;
            line-height: 1.4;
            color: #64748B;
        }

        .login-field + .login-field { margin-top: 18px; }

        .login-label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
            color: var(--login-text) !important;
        }

        .login-input {
            display: block;
            width: 100%;
            padding: 10px 14px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: var(--login-text) !important;
            background-color: var(--login-input-bg) !important;
            border: 1px solid var(--login-border) !important;
            border-radius: 6px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.2s ease, color 0.2s ease;
        }

        .login-input:focus {
            border-color: #1E3A5F;
            box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.12);
        }

        .login-submit {
            display: block;
            width: 100%;
            margin-top: 24px;
            padding: 11px 16px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
            color: #FFFFFF;
            background-color: #1E3A5F;
            border: 1px solid #1E3A5F;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .login-submit:hover { background-color: #152a45; border-color: #152a45; }
        .login-submit:focus-visible { outline: 2px solid #1E3A5F; outline-offset: 2px; }

        /* ===== Catatan akun demo ===== */
        .login-demo {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #E2E8F0;
        }

        .login-demo__label {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.4;
            color: #64748B;
        }

        .login-demo__chips { display: flex; flex-wrap: wrap; gap: 8px; }

        .chip {
            padding: 5px 10px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #0F172A;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 4px;
        }

        /* ===== Alert ===== */
        .login-alert {
            margin: 0 0 20px;
            padding: 10px 14px;
            font-size: 13px;
            line-height: 1.5;
            border: 1px solid;
            border-radius: 6px;
        }

        .login-alert--error {
            color: #DC2626;
            background-color: #FEF2F2;
            border-color: #FECACA;
        }

        .login-alert--success {
            color: #16A34A;
            background-color: #F0FDF4;
            border-color: #BBF7D0;
        }

        /* ===== Responsif: Tablet & Mobile (<= 860px) ===== */
        @media (max-width: 860px) {
            body {
                padding: 24px 16px;
                align-items: center;
                justify-content: center;
            }

            .login-card {
                min-height: auto;
                max-width: 420px;
                margin: auto;
                border: 1px solid #E2E8F0;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
                overflow: hidden;
            }

            .login-aside { display: none; }

            .login-panel {
                min-height: auto;
                width: 100%;
                padding: 32px 24px;
                border-radius: 12px;
            }

            .login-mobile-brand {
                display: flex;
                justify-content: center;
                margin-bottom: 24px;
            }
        }

        /* ===== Dark Mode Support ===== */
        [data-theme="dark"] body {
            background-color: #0B1420;
            color: #E2E8F0;
        }

        [data-theme="dark"] .login-card {
            background-color: #111D2E;
            border-color: #24344C;
        }

        [data-theme="dark"] .login-panel {
            background-color: #111D2E;
        }

        [data-theme="dark"] .panel-brand__logo {
            color: #3B82C4;
        }

        [data-theme="dark"] .panel-brand__subtitle {
            color: #94A3B8;
        }

        [data-theme="dark"] .login-label {
            color: #E2E8F0;
        }

        [data-theme="dark"] .login-input {
            background-color: #0E1A2B;
            border-color: #33465F;
            color: #E2E8F0;
        }

        [data-theme="dark"] .login-input:focus {
            border-color: #3B82C4;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        [data-theme="dark"] .login-submit {
            background-color: #3B82C4;
            border-color: #3B82C4;
        }

        [data-theme="dark"] .login-submit:hover {
            background-color: #2E5A8F;
            border-color: #2E5A8F;
        }

        [data-theme="dark"] .login-demo {
            border-top-color: #24344C;
        }

        [data-theme="dark"] .login-demo__label {
            color: #94A3B8;
        }

        [data-theme="dark"] .chip {
            color: #E2E8F0;
            background-color: #16263A;
            border-color: #24344C;
        }

        [data-theme="dark"] .login-mobile-badge {
            background-color: #0E1A2B;
            border: 1px solid #33465F;
        }

        [data-theme="dark"] .login-alert--error {
            color: #F87171;
            background-color: #450A0A;
            border-color: #991B1B;
        }

        [data-theme="dark"] .login-alert--success {
            color: #4ADE80;
            background-color: #052E16;
            border-color: #166534;
        }

        .login-top-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 16px;
        }

        .login-theme-toggle {
            background: transparent;
            border: 1px solid #CBD5E1;
            color: #64748B;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .login-theme-toggle:hover {
            background-color: #F1F5F9;
            color: #1E3A5F;
        }

        [data-theme="dark"] .login-theme-toggle {
            border-color: #33465F;
            color: #94A3B8;
        }

        [data-theme="dark"] .login-theme-toggle:hover {
            background-color: #16263A;
            color: #3B82C4;
        }
    </style>
</head>
<body>
    <main class="login-card">
        <!-- ===== Panel kiri: branding + suasana ruangan malam ===== -->
        <aside class="login-aside">
            <div class="aside-brand">
                <p class="aside-brand__logo">GAJIKU</p>
                <p class="aside-brand__company">Kantor Jaya Bersama</p>
                <h1 class="aside-brand__title">Furniture &amp; Interior</h1>
                <p class="aside-brand__desc">
                    Sistem penggajian internal untuk seluruh karyawan Kantor Jaya Bersama.
                </p>
            </div>

            <ul class="aside-list">
                <li>
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8.5L6.2 11.7L13 5" stroke="#FBBF24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Payroll akurat &amp; tepat waktu
                </li>
                <li>
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8.5L6.2 11.7L13 5" stroke="#FBBF24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Slip gaji digital
                </li>
                <li>
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8.5L6.2 11.7L13 5" stroke="#FBBF24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Data keuangan tersimpan aman
                </li>
            </ul>

            <div class="aside-art">
                <svg viewBox="0 0 320 260" preserveAspectRatio="xMidYMax meet" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="presentation">
                    <g stroke="#FBBF24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <!-- kabel lampu gantung -->
                        <line x1="160" y1="0" x2="160" y2="52"/>

                        <!-- kap lampu trapesium -->
                        <path d="M132 52 H188 L206 100 H114 Z"/>

                        <!-- socket + bohlam -->
                        <line x1="160" y1="100" x2="160" y2="111"/>
                        <circle cx="160" cy="124" r="13"/>

                        <!-- garis-garis sinar -->
                        <line x1="179" y1="124" x2="187" y2="124"/>
                        <line x1="141" y1="124" x2="133" y2="124"/>
                        <line x1="160" y1="143" x2="160" y2="151"/>
                        <line x1="173.4" y1="137.4" x2="179.1" y2="143.1"/>
                        <line x1="146.6" y1="137.4" x2="140.9" y2="143.1"/>
                        <line x1="173.4" y1="110.6" x2="179.1" y2="104.9"/>
                        <line x1="146.6" y1="110.6" x2="140.9" y2="104.9"/>

                        <!-- meja: permukaan + dua kaki -->
                        <line x1="72" y1="208" x2="248" y2="208"/>
                        <line x1="72" y1="214" x2="248" y2="214"/>
                        <line x1="72" y1="208" x2="72" y2="214"/>
                        <line x1="248" y1="208" x2="248" y2="214"/>
                        <line x1="88" y1="214" x2="88" y2="258"/>
                        <line x1="232" y1="214" x2="232" y2="258"/>

                        <!-- laptop terbuka -->
                        <path d="M130 208 L198 208 L192 199 L136 199 Z"/>
                        <path d="M139 199 L147 170 L193 170 L191 199 Z"/>

                        <!-- tumpukan buku -->
                        <rect x="204" y="198" width="40" height="10"/>
                        <rect x="207" y="189" width="35" height="9"/>
                        <rect x="210" y="181" width="28" height="8"/>
                    </g>
                </svg>
            </div>

            <p class="aside-foot">&copy; <?= date('Y') ?> Kantor Jaya Bersama</p>
        </aside>

        <!-- ===== Panel kanan: form login ===== -->
        <section class="login-panel">
            <div class="login-panel__inner">
                <div class="login-top-bar">
                    <button type="button" id="themeToggle" onclick="toggleGajikuTheme()" class="login-theme-toggle" aria-label="Ganti mode gelap atau terang" aria-pressed="false" title="Ganti mode gelap/terang">
                        <i class="bi bi-moon-stars" id="themeToggleIcon"></i>
                    </button>
                </div>

                <div class="login-mobile-brand">
                    <span class="login-mobile-badge">Kantor Jaya Bersama</span>
                </div>

                <div class="panel-brand">
                    <p class="panel-brand__logo">GAJIKU</p>
                    <p class="panel-brand__subtitle">Sistem Informasi Penggajian</p>
                </div>

                <?php if ($logoutMessage !== null): ?>
                    <div class="login-alert login-alert--success" role="alert">
                        <?= htmlspecialchars($logoutMessage, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if ($errors !== []): ?>
                    <div class="login-alert login-alert--error" role="alert">
                        <?= htmlspecialchars($errors[0], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="login-field">
                        <label for="username" class="login-label">Username</label>
                        <input
                            type="text"
                            class="login-input"
                            id="username"
                            name="username"
                            value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <div class="login-field">
                        <label for="password" class="login-label">Password</label>
                        <input
                            type="password"
                            class="login-input"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <button type="submit" class="login-submit">Login</button>
                </form>

                <div class="login-demo">
                    <p class="login-demo__label">Akun demo (klik untuk mengisi):</p>
                    <div class="login-demo__chips">
                        <button type="button" class="chip" onclick="fillLogin('admin', 'admin12345')" title="Klik untuk mengisi akun admin" style="cursor: pointer; border: none; font: inherit;">admin / admin12345</button>
                        <button type="button" class="chip" onclick="fillLogin('hrd', 'hrd12345')" title="Klik untuk mengisi akun HRD" style="cursor: pointer; border: none; font: inherit;">hrd / hrd12345</button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Custom JS (Theme Toggle & State) -->
    <script src="assets/js/app.js"></script>
    <script>
        if (typeof window.toggleGajikuTheme === 'undefined') {
            document.write('<script src="/assets/js/app.js"><\/script>');
        }
    </script>
</body>
</html>
