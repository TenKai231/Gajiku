<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/karyawan.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';

requireAuth();
$user = currentUser();
if (!in_array($user['role'], ['ADMIN', 'HR'], true)) {
    http_response_code(403);
    die('Akses ditolak.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /app/pages/karyawan/index.php');
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/config/database.php';
$karyawan = fetchKaryawanById($pdo, $id);

if (!$karyawan) {
    $_SESSION['error'] = 'Karyawan tidak ditemukan.';
    header('Location: /app/pages/karyawan/index.php');
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Karyawan — Sistem Informasi Penggajian</title>
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
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Detail Karyawan</h1>
                    <div>
                        <?php if ($user['role'] === 'ADMIN'): ?>
                        <a href="edit.php?id=<?= $karyawan['id'] ?>" class="btn btn-primary me-2">Edit Data</a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-borderless table-striped m-0">
                            <tbody>
                                <tr>
                                    <th class="ps-4 w-25 py-3">NIP</th>
                                    <td class="py-3"><?= htmlspecialchars($karyawan['nip'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-4 py-3">Nama Lengkap</th>
                                    <td class="py-3 fw-bold"><?= htmlspecialchars($karyawan['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-4 py-3">Jenis Kelamin</th>
                                    <td class="py-3"><?= $karyawan['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-4 py-3">Tanggal Lahir</th>
                                    <td class="py-3"><?= date('d F Y', strtotime($karyawan['tanggal_lahir'])) ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-4 py-3">Jabatan</th>
                                    <td class="py-3">
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars((string) $karyawan['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-4 py-3">Gaji Pokok</th>
                                    <td class="py-3"><?= formatCurrency((float) $karyawan['gaji_pokok']) ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-4 py-3">Tunjangan</th>
                                    <td class="py-3"><?= formatCurrency((float) $karyawan['tunjangan_default']) ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-4 py-3">Tanggal Masuk</th>
                                    <td class="py-3"><?= date('d F Y', strtotime($karyawan['tanggal_masuk'])) ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-4 py-3">Status</th>
                                    <td class="py-3">
                                        <?php if ($karyawan['status'] === 'Aktif'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>