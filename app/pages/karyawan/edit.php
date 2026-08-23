<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/karyawan.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
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
$jabatans = fetchAllJabatan($pdo);

if (!$karyawan) {
    $_SESSION['error'] = 'Karyawan tidak ditemukan.';
    header('Location: /app/pages/karyawan/index.php');
    exit;
}

$errors = $_SESSION['errors'] ?? [];
$form = $_SESSION['form'] ?? [
    'nip' => $karyawan['nip'],
    'nama' => $karyawan['nama'],
    'jenis_kelamin' => $karyawan['jenis_kelamin'],
    'tanggal_lahir' => $karyawan['tanggal_lahir'],
    'tanggal_masuk' => $karyawan['tanggal_masuk'],
    'jabatan_id' => $karyawan['jabatan_id'],
    'status' => $karyawan['status']
];
unset($_SESSION['errors'], $_SESSION['form']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Karyawan — Sistem Informasi Penggajian</title>
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
                    <h1 class="h3 mb-0">Edit Karyawan</h1>
                    <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form action="/app/actions/karyawan/update.php" method="POST">
                            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nip" class="form-label">NIP</label>
                                    <input type="text" class="form-control <?= isset($errors['nip']) ? 'is-invalid' : '' ?>"
                                           id="nip" name="nip" value="<?= htmlspecialchars((string) $form['nip'], ENT_QUOTES, 'UTF-8') ?>" required>
                                    <?php if (isset($errors['nip'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['nip'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nama" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                                           id="nama" name="nama" value="<?= htmlspecialchars((string) $form['nama'], ENT_QUOTES, 'UTF-8') ?>" required>
                                    <?php if (isset($errors['nama'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['nama'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jenis Kelamin</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input <?= isset($errors['jenis_kelamin']) ? 'is-invalid' : '' ?>"
                                                   type="radio" name="jenis_kelamin" id="jk_l" value="L"
                                                   <?= $form['jenis_kelamin'] === 'L' ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="jk_l">Laki-laki</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input <?= isset($errors['jenis_kelamin']) ? 'is-invalid' : '' ?>"
                                                   type="radio" name="jenis_kelamin" id="jk_p" value="P"
                                                   <?= $form['jenis_kelamin'] === 'P' ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="jk_p">Perempuan</label>
                                        </div>
                                    </div>
                                    <?php if (isset($errors['jenis_kelamin'])): ?>
                                        <div class="small text-danger mt-1"><?= htmlspecialchars($errors['jenis_kelamin'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control <?= isset($errors['tanggal_lahir']) ? 'is-invalid' : '' ?>"
                                           id="tanggal_lahir" name="tanggal_lahir" value="<?= htmlspecialchars((string) $form['tanggal_lahir'], ENT_QUOTES, 'UTF-8') ?>" required>
                                    <?php if (isset($errors['tanggal_lahir'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['tanggal_lahir'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="jabatan_id" class="form-label">Jabatan</label>
                                    <select class="form-select <?= isset($errors['jabatan_id']) ? 'is-invalid' : '' ?>"
                                            id="jabatan_id" name="jabatan_id" required>
                                        <option value="">-- Pilih Jabatan --</option>
                                        <?php foreach ($jabatans as $j): ?>
                                            <option value="<?= $j['id'] ?>" <?= ((string) $form['jabatan_id'] === (string) $j['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($j['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['jabatan_id'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['jabatan_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_masuk" class="form-label">Tanggal Masuk Kerja</label>
                                    <input type="date" class="form-control <?= isset($errors['tanggal_masuk']) ? 'is-invalid' : '' ?>"
                                           id="tanggal_masuk" name="tanggal_masuk" value="<?= htmlspecialchars((string) $form['tanggal_masuk'], ENT_QUOTES, 'UTF-8') ?>" required>
                                    <?php if (isset($errors['tanggal_masuk'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['tanggal_masuk'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>"
                                            id="status" name="status" required>
                                        <option value="Aktif" <?= $form['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="Nonaktif" <?= $form['status'] === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                    </select>
                                    <?php if (isset($errors['status'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Update Karyawan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>