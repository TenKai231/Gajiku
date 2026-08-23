<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAuth();
$user = currentUser();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — Sistem Informasi Penggajian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <p class="text-uppercase text-secondary small mb-1">Dashboard</p>
                <h1 class="h3 mb-1">Sistem Informasi Penggajian</h1>
                <p class="text-secondary mb-0">
                    Selamat datang, <?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>.
                </p>
            </div>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5">Status Login</h2>
                        <p class="mb-2">Autentikasi berhasil dan session aktif.</p>
                        <span class="badge text-bg-success">Terverifikasi</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5">Role Pengguna</h2>
                        <p class="mb-2">Hak akses aktif untuk akun yang sedang digunakan.</p>
                        <span class="badge text-bg-primary">
                            <?= htmlspecialchars((string) ($user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (($user['role'] ?? '') === 'ADMIN'): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <h2 class="h5 mb-1">Master Jabatan</h2>
                                <p class="mb-0 text-secondary">Lanjutkan pengujian CRUD jabatan dari halaman manajemen data.</p>
                            </div>
                            <a href="/jabatan/" class="btn btn-primary">Buka CRUD Jabatan</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
