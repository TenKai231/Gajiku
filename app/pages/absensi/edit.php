<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/absensi.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /app/pages/absensi/index.php');
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/config/database.php';
$absensi = fetchAbsensiById($pdo, $id);

if (!$absensi) {
    $_SESSION['error'] = 'Data absensi tidak ditemukan.';
    header('Location: /app/pages/absensi/index.php');
    exit;
}

// Untuk edit, tampilkan info karyawan tanpa bisa diganti (hanya id disembunyikan/readonly)
$errors = $_SESSION['errors'] ?? [];
$form = $_SESSION['form'] ?? [
    'karyawan_id' => $absensi['karyawan_id'],
    'tanggal' => $absensi['tanggal'],
    'status' => $absensi['status'],
    'jam_masuk' => $absensi['jam_masuk'] ? substr($absensi['jam_masuk'], 0, 5) : '',
    'jam_pulang' => $absensi['jam_pulang'] ? substr($absensi['jam_pulang'], 0, 5) : '',
    'keterangan' => $absensi['keterangan'] ?? ''
];
unset($_SESSION['errors'], $_SESSION['form']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Absensi — Sistem Informasi Penggajian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="/index.php">Gajiku</a>
        </div>
    </nav>

    <main class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Edit Absensi</h1>
                    <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form action="/app/actions/absensi/update.php" method="POST" id="formAbsensi">
                            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>">
                            <!-- Karyawan ID tetap dikirim tp readonly dari sisi UI -->
                            <input type="hidden" name="karyawan_id" value="<?= htmlspecialchars((string) $form['karyawan_id'], ENT_QUOTES, 'UTF-8') ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal" class="form-label">Tanggal</label>
                                    <input type="date" class="form-control <?= isset($errors['tanggal']) ? 'is-invalid' : '' ?>"
                                           id="tanggal" name="tanggal" value="<?= htmlspecialchars((string) $form['tanggal'], ENT_QUOTES, 'UTF-8') ?>" required>
                                    <?php if (isset($errors['tanggal'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['tanggal'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Karyawan</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($absensi['nip'] . ' - ' . $absensi['nama'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <label for="status" class="form-label">Status Absensi</label>
                                <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>"
                                        id="status" name="status" required onchange="toggleJamFields()">
                                    <?php
                                    $statuses = ['Hadir', 'Sakit', 'Izin', 'Alpha', 'Cuti'];
                                    foreach ($statuses as $st): ?>
                                        <option value="<?= $st ?>" <?= $form['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['status'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="row" id="jamFields">
                                <div class="col-md-6 mb-3">
                                    <label for="jam_masuk" class="form-label">Jam Masuk</label>
                                    <input type="time" class="form-control <?= isset($errors['jam_masuk']) ? 'is-invalid' : '' ?>"
                                           id="jam_masuk" name="jam_masuk" value="<?= htmlspecialchars((string) $form['jam_masuk'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (isset($errors['jam_masuk'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['jam_masuk'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="jam_pulang" class="form-label">Jam Pulang</label>
                                    <input type="time" class="form-control <?= isset($errors['jam_pulang']) ? 'is-invalid' : '' ?>"
                                           id="jam_pulang" name="jam_pulang" value="<?= htmlspecialchars((string) $form['jam_pulang'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (isset($errors['jam_pulang'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['jam_pulang'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="2"><?= htmlspecialchars((string) $form['keterangan'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Update Absensi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleJamFields() {
            const status = document.getElementById('status').value;
            const jamFields = document.getElementById('jamFields');
            const jamMasuk = document.getElementById('jam_masuk');

            if (status === 'Hadir') {
                jamFields.style.display = 'flex';
                jamMasuk.required = true;
            } else {
                jamFields.style.display = 'none';
                jamMasuk.required = false;
            }
        }
        document.addEventListener('DOMContentLoaded', toggleJamFields);
    </script>
</body>
</html>