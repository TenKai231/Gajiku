<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

// Ambil error / form data lama dari session (jika ada error validasi)
$errors = $_SESSION['errors'] ?? [];
$form = $_SESSION['form'] ?? ['nama_jabatan' => '', 'gaji_pokok' => '', 'tunjangan_default' => ''];
unset($_SESSION['errors'], $_SESSION['form']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Jabatan — Sistem Informasi Penggajian</title>
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
                    <h1 class="h3 mb-0">Tambah Jabatan</h1>
                    <a href="/?page=jabatan" class="btn btn-outline-secondary">Kembali</a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form action="/?action=jabatan/store" method="POST">
                            <div class="mb-3">
                                <label for="nama_jabatan" class="form-label">Nama Jabatan</label>
                                <input type="text"
                                       class="form-control <?= isset($errors['nama_jabatan']) ? 'is-invalid' : '' ?>"
                                       id="nama_jabatan"
                                       name="nama_jabatan"
                                       value="<?= htmlspecialchars((string) $form['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?>"
                                       required>
                                <?php if (isset($errors['nama_jabatan'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="gaji_pokok" class="form-label">Gaji Pokok</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                           class="form-control <?= isset($errors['gaji_pokok']) ? 'is-invalid' : '' ?>"
                                           id="gaji_pokok"
                                           name="gaji_pokok"
                                           value="<?= htmlspecialchars((string) $form['gaji_pokok'], ENT_QUOTES, 'UTF-8') ?>"
                                           min="0" step="1" required>
                                </div>
                                <?php if (isset($errors['gaji_pokok'])): ?>
                                    <div class="small text-danger mt-1"><?= htmlspecialchars($errors['gaji_pokok'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <label for="tunjangan_default" class="form-label">Tunjangan Default</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                           class="form-control <?= isset($errors['tunjangan_default']) ? 'is-invalid' : '' ?>"
                                           id="tunjangan_default"
                                           name="tunjangan_default"
                                           value="<?= htmlspecialchars((string) $form['tunjangan_default'], ENT_QUOTES, 'UTF-8') ?>"
                                           min="0" step="1" required>
                                </div>
                                <?php if (isset($errors['tunjangan_default'])): ?>
                                    <div class="small text-danger mt-1"><?= htmlspecialchars($errors['tunjangan_default'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Simpan Jabatan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>