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
    <link rel="icon" type="image/png" href="/assets/img/Gajiku_logo-removebg-preview.png">

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

        /* ===== Panel kiri (biru malam + sorotan lampu) ===== */
        .login-aside {
            flex: 0 0 45%;
            width: 45%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 56px;
            background-color: #0D1B2E;
            color: #E9EFF6;
            overflow-y: auto;
            position: relative;
            transition: background-image 0.4s ease;
        }

        .login-aside.lamp-lit {
            background-image: radial-gradient(
                ellipse 85% 55% at 50% 4%,
                rgba(251, 191, 36, 0.28) 0%,
                rgba(251, 191, 36, 0.06) 45%,
                rgba(13, 27, 46, 0) 75%
            );
        }

        .login-aside.lamp-dim {
            background-image: radial-gradient(
                ellipse 85% 55% at 50% 4%,
                rgba(30, 41, 59, 0.25) 0%,
                rgba(13, 27, 46, 0) 75%
            );
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
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0 12px;
            max-width: 460px;
            width: 100%;
            margin: 0 auto;
            position: relative;
        }

        .aside-spotlight {
            position: absolute;
            top: -6px;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 460px;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-aside.lamp-lit .aside-spotlight {
            opacity: 1;
        }

        .login-aside.lamp-dim .aside-spotlight {
            opacity: 0;
        }

        .aside-illustration {
            position: relative;
            z-index: 2;
            display: block;
            width: 100%;
            max-width: 420px;
            height: auto;
            margin: 0 auto;
            object-fit: contain;
            transition: filter 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-aside.lamp-lit .aside-illustration {
            opacity: 1;
            filter: drop-shadow(0 14px 30px rgba(0, 0, 0, 0.45)) drop-shadow(0 0 35px rgba(251, 191, 36, 0.22)) brightness(1.05);
        }

        .login-aside.lamp-dim .aside-illustration {
            opacity: 0.55;
            filter: brightness(0.48) contrast(1.05) drop-shadow(0 4px 10px rgba(0, 0, 0, 0.7));
        }

        .aside-illustration:hover {
            transform: scale(1.02) translateY(-2px);
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

        .logo-glow {
            filter: drop-shadow(0 2px 12px rgba(59, 130, 246, 0.45)) drop-shadow(0 0 22px rgba(251, 191, 36, 0.35));
            transition: filter 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .logo-glow:hover {
            filter: drop-shadow(0 4px 18px rgba(59, 130, 246, 0.65)) drop-shadow(0 0 30px rgba(251, 191, 36, 0.55));
            transform: scale(1.04);
        }

        [data-theme="dark"] .logo-glow {
            filter: drop-shadow(0 0 16px rgba(96, 165, 250, 0.65)) drop-shadow(0 0 28px rgba(251, 191, 36, 0.5));
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

        .login-submit:disabled {
            background-color: #2D5A8E;
            border-color: #2D5A8E;
            cursor: not-allowed;
            opacity: 1;
        }

        @keyframes gajiku-spin { to { transform: rotate(360deg); } }

        .login-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: gajiku-spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
            flex-shrink: 0;
        }

        [data-theme="dark"] .login-submit:disabled {
            background-color: #1E4A7A;
            border-color: #1E4A7A;
        }

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


        /* ===== Interactive Pull-Cord Lamp ===== */
        .login-lamp-widget {
            position: fixed;
            top: 0;
            right: 48px;
            width: 100px;
            height: 220px;
            z-index: 100;
            user-select: none;
            -webkit-user-select: none;
            touch-action: none;
            pointer-events: none;
        }

        .login-lamp-interactive {
            pointer-events: auto;
        }

        .login-lamp-halo {
            position: absolute;
            top: 25px;
            left: 50%;
            transform: translate(-50%, -20%) scale(0.6);
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.42) 0%, rgba(245, 158, 11, 0.16) 42%, rgba(217, 119, 6, 0) 70%);
            opacity: 0;
            filter: blur(10px);
            transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }

        .login-lamp-widget.on .login-lamp-halo {
            opacity: 1;
            transform: translate(-50%, -20%) scale(1);
        }

        .login-lamp-bulb {
            fill: #64748B;
            transition: fill 0.3s ease, filter 0.3s ease;
        }

        .login-lamp-widget.on .login-lamp-bulb {
            fill: #FEF08A;
            filter: drop-shadow(0 0 8px rgba(250, 204, 21, 0.95)) drop-shadow(0 0 20px rgba(245, 158, 11, 0.6));
        }

        .login-lamp-filament {
            stroke: #475569;
            transition: stroke 0.3s ease;
        }

        .login-lamp-widget.on .login-lamp-filament {
            stroke: #D97706;
        }

        .login-lamp-handle {
            cursor: grab;
            cursor: -webkit-grab;
        }

        .login-lamp-handle.dragging {
            cursor: grabbing;
            cursor: -webkit-grabbing;
        }

        @media (max-width: 900px) {
            .login-lamp-widget {
                right: 20px;
                transform: scale(0.9);
                transform-origin: top right;
            }
        }
    </style>
</head>
<body>
    <!-- Interactive Pull-Cord Lamp -->
    <div class="login-lamp-widget off" id="loginLampWidget" aria-label="Lampu gantung tarik pengubah tema" title="Tarik tali lampu untuk mengganti tema terang/gelap">
        <div class="login-lamp-halo"></div>
        <svg viewBox="0 0 100 220" width="100" height="220" style="overflow: visible;">
            <defs>
                <linearGradient id="loginShadeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#1E293B"/>
                    <stop offset="50%" stop-color="#334155"/>
                    <stop offset="100%" stop-color="#0F172A"/>
                </linearGradient>
                <linearGradient id="loginKnobGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#FDE047"/>
                    <stop offset="60%" stop-color="#EAB308"/>
                    <stop offset="100%" stop-color="#A16207"/>
                </linearGradient>
            </defs>

            <!-- Tali dari plafon -->
            <line x1="50" y1="0" x2="50" y2="30" stroke="#334155" stroke-width="2"/>
            <!-- Fitting cap -->
            <rect x="45" y="27" width="10" height="5" rx="1.5" fill="#EAB308"/>

            <!-- Bohlam -->
            <circle cx="50" cy="55" r="10" class="login-lamp-bulb"/>
            <path d="M47 53 Q50 50 53 53" fill="none" class="login-lamp-filament" stroke-width="1"/>

            <!-- Kap Lampu -->
            <path d="M 37 32 L 63 32 L 77 50 L 23 50 Z" fill="url(#loginShadeGrad)"/>
            <line x1="22" y1="50" x2="78" y2="50" stroke="#EAB308" stroke-width="1.8" stroke-linecap="round"/>
            <rect x="47.5" y="50" width="5" height="4" rx="1" fill="#EAB308"/>

            <!-- Tali pull cord & handle -->
            <line id="loginCordLine" x1="50" y1="54" x2="50" y2="135" stroke="#CA8A04" stroke-width="1.4" stroke-dasharray="2.5,2"/>

            <g id="loginLampHandle" class="login-lamp-handle login-lamp-interactive" role="button" tabindex="0" aria-label="Tarik tali untuk menyalakan atau mematikan lampu">
                <circle cx="50" cy="135" r="18" fill="transparent"/>
                <circle cx="50" cy="135" r="5.5" fill="url(#loginKnobGrad)" stroke="#854D0E" stroke-width="0.8"/>
                <path d="M47.5 129.5 L52.5 129.5 L50 127 Z" fill="#EAB308"/>
            </g>
        </svg>
    </div>

    <main class="login-card">
        <!-- ===== Panel kiri: branding + suasana ruangan malam ===== -->
        <aside class="login-aside lamp-dim" id="loginAside">
            <div class="aside-brand">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="/assets/img/Gajiku_logo-removebg-preview.png" alt="Logo Gajiku" class="logo-glow" style="width: 72px; height: 72px; object-fit: contain;">
                    <div>
                        <p class="aside-brand__logo" style="margin: 0; line-height: 1;">GAJIKU</p>
                        <p class="aside-brand__company" style="margin: 4px 0 0;">Kantor Jaya Bersama</p>
                    </div>
                </div>
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
                <svg class="aside-spotlight" viewBox="0 0 460 380" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <!-- Gradien berkas lampu sorot kerucut vertikal -->
                        <linearGradient id="asideSpotCone" x1="50%" y1="0%" x2="50%" y2="100%">
                            <stop offset="0%" stop-color="#FEF08A" stop-opacity="0.55"/>
                            <stop offset="15%" stop-color="#FDE047" stop-opacity="0.38"/>
                            <stop offset="45%" stop-color="#FBBF24" stop-opacity="0.18"/>
                            <stop offset="80%" stop-color="#F59E0B" stop-opacity="0.05"/>
                            <stop offset="100%" stop-color="#F59E0B" stop-opacity="0"/>
                        </linearGradient>

                        <!-- Pendaran lensa pusat lampu sorot -->
                        <radialGradient id="asideSpotLens" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.95"/>
                            <stop offset="25%" stop-color="#FEF08A" stop-opacity="0.9"/>
                            <stop offset="65%" stop-color="#FBBF24" stop-opacity="0.4"/>
                            <stop offset="100%" stop-color="#FBBF24" stop-opacity="0"/>
                        </radialGradient>

                        <!-- Pendaran cahaya di atas permukaan meja -->
                        <radialGradient id="asideSpotPool" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#FEF08A" stop-opacity="0.28"/>
                            <stop offset="45%" stop-color="#FBBF24" stop-opacity="0.12"/>
                            <stop offset="100%" stop-color="#FBBF24" stop-opacity="0"/>
                        </radialGradient>
                    </defs>

                    <!-- Kap & gantungan lampu sorot dari atas -->
                    <line x1="230" y1="0" x2="230" y2="15" stroke="#475569" stroke-width="2"/>
                    <path d="M 212 15 L 248 15 L 256 26 L 204 26 Z" fill="#1E293B" stroke="#334155" stroke-width="1"/>
                    <line x1="202" y1="26" x2="258" y2="26" stroke="#EAB308" stroke-width="2.2" stroke-linecap="round"/>

                    <!-- Berkas cahaya lampu sorot (cone beam) menyinari gambar dari atas -->
                    <polygon points="212,26 248,26 448,370 12,370" fill="url(#asideSpotCone)"/>

                    <!-- Lensa bohlam lampu sorot yang bersinar -->
                    <ellipse cx="230" cy="27" rx="18" ry="4.5" fill="url(#asideSpotLens)"/>
                    <ellipse cx="230" cy="27" rx="46" ry="18" fill="url(#asideSpotLens)" opacity="0.65"/>

                    <!-- Genangan cahaya lembut di sekitar meja -->
                    <ellipse cx="230" cy="345" rx="190" ry="25" fill="url(#asideSpotPool)"/>
                </svg>

                <img
                    src="/assets/img/163119-OVAK49-352-removebg-preview.png"
                    alt="Ilustrasi Ruang Kerja Kantor Jaya Bersama"
                    class="aside-illustration"
                    width="420"
                    height="420"
                    loading="lazy"
                >
            </div>

            <p class="aside-foot">&copy; <?= date('Y') ?> Kantor Jaya Bersama</p>
        </aside>

        <!-- ===== Panel kanan: form login ===== -->
        <section class="login-panel">
            <div class="login-panel__inner">
                <div class="login-mobile-brand">
                    <span class="login-mobile-badge">Kantor Jaya Bersama</span>
                </div>

                <div class="panel-brand">
                    <img src="/assets/img/Gajiku_logo-removebg-preview.png" alt="Logo Gajiku" class="logo-glow mb-2" style="width: 120px; height: 120px; object-fit: contain;">
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
                    <p id="login-status" role="status" aria-live="polite" aria-atomic="true" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;"></p>
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

    <!-- Login loading state -->
    <script>
        (function () {
            var form = document.querySelector('form[method="post"]');
            var btn  = form && form.querySelector('button[type="submit"]');
            if (!form || !btn) return;

            var originalHTML = btn.innerHTML;
            var submitted = false;

            form.addEventListener('submit', function (e) {
                if (submitted) { e.preventDefault(); return; }

                var username = (form.querySelector('#username') || {}).value || '';
                var password = (form.querySelector('#password') || {}).value || '';
                if (!username.trim() || !password) return; // let native validation fire

                // Prevent double-submit immediately
                submitted = true;

                // Show spinner synchronously so paint fires before network request
                btn.disabled = true;
                btn.innerHTML = '<span class="login-spinner" aria-hidden="true"></span>Memproses…';
                btn.setAttribute('aria-busy', 'true');

                var sr = document.getElementById('login-status');
                if (sr) sr.textContent = 'Memproses login, harap tunggu…';

                // Defer actual submit until after browser has painted the spinner
                e.preventDefault();
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        form.submit();
                    });
                });
            });

            // Guard bfcache back-navigation
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    submitted = false;
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    btn.removeAttribute('aria-busy');
                }
            });
        })();
    </script>

    <!-- Custom JS (Theme Toggle & State) -->
    <script src="assets/js/app.js"></script>
    <script>
        if (typeof window.toggleGajikuTheme === 'undefined') {
            document.write('<script src="/assets/js/app.js"><\/script>');
        }
    </script>
    <script>
        (function () {
            var widget = document.getElementById('loginLampWidget');
            var handle = document.getElementById('loginLampHandle');
            var line = document.getElementById('loginCordLine');
            if (!widget || !handle || !line) return;

            var svg = widget.querySelector('svg');
            var isDragging = false;
            var dragStartX = 0;
            var dragStartY = 0;
            var lastX = 0;
            var lastTime = 0;
            var releaseVelX = 0;
            var animId = null;

            var pivotX = 50;
            var pivotY = 54;
            var baseLength = 81; // 135 - 54
            var currentLength = baseLength;
            var lengthVel = 0;
            var theta = 0; // angle in radians (0 = straight down)
            var omega = 0; // angular velocity
            var lastSide = 1;

            var threshold = 18;
            var maxDrag = 48;
            var gravityFactor = 0.0085; // pendulum restoring acceleration
            var angularDamping = 0.985;  // air resistance damping for natural swing
            var lengthStiffness = 0.24;
            var lengthDamping = 0.72;

            var aside = document.getElementById('loginAside') || document.querySelector('.login-aside');

            function syncLampWithTheme(theme) {
                // Mode dark  = Lampu HIDUP (menyinari ruangan malam dengan spotlight)
                // Mode light = Lampu MATI (siang hari, spotlight padam)
                var isLampOn = (theme === 'dark');

                if (isLampOn) {
                    widget.classList.add('on');
                    widget.classList.remove('off');
                    if (aside) {
                        aside.classList.add('lamp-lit');
                        aside.classList.remove('lamp-dim');
                    }
                } else {
                    widget.classList.add('off');
                    widget.classList.remove('on');
                    if (aside) {
                        aside.classList.add('lamp-dim');
                        aside.classList.remove('lamp-lit');
                    }
                }
            }

            var initialTheme = document.documentElement.getAttribute('data-theme') || 'light';
            syncLampWithTheme(initialTheme);

            window.addEventListener('gajiku:themeChanged', function (e) {
                if (e.detail && e.detail.theme) {
                    syncLampWithTheme(e.detail.theme);
                }
            });

            function getSvgPoint(e) {
                if (svg && svg.createSVGPoint && svg.getScreenCTM) {
                    try {
                        var pt = svg.createSVGPoint();
                        pt.x = e.clientX;
                        pt.y = e.clientY;
                        var screenCTM = svg.getScreenCTM();
                        if (screenCTM) {
                            return pt.matrixTransform(screenCTM.inverse());
                        }
                    } catch (_) {}
                }
                var rect = widget.getBoundingClientRect();
                return {
                    x: (e.clientX - rect.left) * (100 / (rect.width || 100)),
                    y: (e.clientY - rect.top) * (220 / (rect.height || 220))
                };
            }

            function updateCord() {
                var knobX = pivotX + currentLength * Math.sin(theta);
                var knobY = pivotY + currentLength * Math.cos(theta);
                line.setAttribute('x2', knobX.toFixed(2));
                line.setAttribute('y2', knobY.toFixed(2));
                var deg = (theta * 180 / Math.PI).toFixed(2);
                var tx = (knobX - 50).toFixed(2);
                var ty = (knobY - 135).toFixed(2);
                handle.setAttribute('transform', 'translate(' + tx + ', ' + ty + ') rotate(' + deg + ', 50, 135)');
            }

            function physicsTick() {
                // 1. Damped spring for cord length (restores to baseLength)
                var lengthForce = (baseLength - currentLength) * lengthStiffness;
                lengthVel = (lengthVel + lengthForce) * lengthDamping;
                currentLength += lengthVel;

                // 2. Gravitational pendulum restoring torque: alpha = -(g/L)*sin(theta)
                var alpha = -gravityFactor * Math.sin(theta);
                omega = (omega + alpha) * angularDamping;
                theta += omega;

                updateCord();

                var isLengthSettled = Math.abs(currentLength - baseLength) < 0.05 && Math.abs(lengthVel) < 0.05;
                var isAngleSettled = Math.abs(theta) < 0.0015 && Math.abs(omega) < 0.0003;

                if (isLengthSettled && isAngleSettled) {
                    currentLength = baseLength;
                    lengthVel = 0;
                    theta = 0;
                    omega = 0;
                    updateCord();
                    animId = null;
                } else {
                    animId = requestAnimationFrame(physicsTick);
                }
            }

            function startPhysics() {
                if (animId) cancelAnimationFrame(animId);
                animId = requestAnimationFrame(physicsTick);
            }

            handle.addEventListener('pointerdown', function (e) {
                if (animId) {
                    cancelAnimationFrame(animId);
                    animId = null;
                }
                isDragging = true;
                var pt = getSvgPoint(e);
                dragStartX = pt.x;
                dragStartY = pt.y;
                lastX = pt.x;
                lastTime = performance.now();
                releaseVelX = 0;
                handle.classList.add('dragging');
                try { handle.setPointerCapture(e.pointerId); } catch (_) {}
            });

            handle.addEventListener('pointermove', function (e) {
                if (!isDragging) return;
                var pt = getSvgPoint(e);
                var dx = pt.x - dragStartX;
                var dy = pt.y - dragStartY;

                var pullY = Math.max(0, Math.min(maxDrag, dy));
                var pullX = Math.max(-36, Math.min(36, dx));

                var targetX = pivotX + pullX;
                var targetY = (pivotY + baseLength) + pullY;

                currentLength = Math.hypot(targetX - pivotX, targetY - pivotY);
                theta = Math.atan2(targetX - pivotX, targetY - pivotY);

                var now = performance.now();
                var dt = (now - lastTime) / 1000;
                if (dt > 0.005) {
                    releaseVelX = (pt.x - lastX) / dt;
                    lastX = pt.x;
                    lastTime = now;
                }

                updateCord();
            });

            function onDragEnd(e) {
                if (!isDragging) return;
                isDragging = false;
                handle.classList.remove('dragging');
                try { handle.releasePointerCapture(e.pointerId); } catch (_) {}

                var pulledDist = currentLength - baseLength;
                if (pulledDist >= threshold) {
                    if (typeof window.toggleGajikuTheme === 'function') {
                        window.toggleGajikuTheme();
                    } else {
                        var isCurrentlyOn = widget.classList.contains('on');
                        syncLampWithTheme(isCurrentlyOn ? 'light' : 'dark');
                    }
                }

                // Momentum transfer to pendulum sway
                var impulse = (releaseVelX / currentLength) * 0.012;
                impulse = Math.max(-0.14, Math.min(0.14, impulse));

                if (Math.abs(theta) < 0.04 && Math.abs(impulse) < 0.02) {
                    // Natural release wobble when pulled straight down
                    lastSide = -lastSide;
                    omega = lastSide * (0.065 + Math.min(0.035, pulledDist * 0.001));
                } else {
                    omega = impulse;
                }

                lengthVel = 0;
                startPhysics();
            }

            handle.addEventListener('pointerup', onDragEnd);
            handle.addEventListener('pointercancel', onDragEnd);

            handle.addEventListener('click', function (e) {
                var pulledDist = currentLength - baseLength;
                if (pulledDist < threshold) {
                    if (typeof window.toggleGajikuTheme === 'function') {
                        window.toggleGajikuTheme();
                    }
                    lastSide = -lastSide;
                    lengthVel = 9;
                    omega = lastSide * 0.075;
                    startPhysics();
                }
            });

            handle.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (typeof window.toggleGajikuTheme === 'function') {
                        window.toggleGajikuTheme();
                    }
                    lastSide = -lastSide;
                    lengthVel = 10;
                    omega = lastSide * 0.085;
                    startPhysics();
                }
            });
        })();
    </script>
</body>
</html>
