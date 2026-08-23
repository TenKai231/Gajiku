<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

$bulanList = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$bulanSekarang = (int) date('m');
$tahunSekarang = (int) date('Y');

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proses Penggajian — Sistem Informasi Penggajian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="/index.php">Gajiku</a>
        </div>
    </nav>

    <main class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white pt-4 pb-3 border-0">
                        <h1 class="h4 mb-0 text-center">Jalankan Payroll Engine</h1>
                    </div>
                    <div class="card-body p-4">

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <p class="text-muted text-center mb-4">
                            Sistem akan mengambil data karyawan yang aktif, menghitung absensi, tunjangan, dan potongan untuk menentukan gaji bersih otomatis.
                        </p>

                        <form action="/app/actions/payroll/process.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memproses payroll periode ini? Proses ini tidak dapat dibatalkan.');">
                            <div class="row mb-4">
                                <div class="col-md-7">
                                    <label for="bulan" class="form-label">Periode Bulan</label>
                                    <select class="form-select" id="bulan" name="bulan" required>
                                        <?php foreach ($bulanList as $num => $name): ?>
                                            <option value="<?= $num ?>" <?= $bulanSekarang === $num ? 'selected' : '' ?>><?= $name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="tahun" class="form-label">Tahun</label>
                                    <input type="number" class="form-control" id="tahun" name="tahun" value="<?= $tahunSekarang ?>" min="2020" max="2099" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Proses Kalkulasi Gaji</button>
                                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>