<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

$errors = $_SESSION['errors'] ?? [];
$form = $_SESSION['form'] ?? ['nama_jabatan' => ''];
unset($_SESSION['errors'], $_SESSION['form']);
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Tambah Jabatan</h1>
            <a href="/?page=jabatan/index" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
            <div>
                <strong>Struktur Kompensasi:</strong> Gaji Pokok, Uang Makan, dan Tunjangan ditentukan berdasarkan <strong>Golongan</strong> pada menu <a href="/?page=golongan/index" class="alert-link">Kelola Golongan</a>. Jabatan hanya mengatur posisi/titel pekerjaan.
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="/?action=jabatan/store" method="POST">
                    <div class="mb-4">
                        <label for="nama_jabatan" class="form-label fw-semibold">Nama Jabatan <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control <?= isset($errors['nama_jabatan']) ? 'is-invalid' : '' ?>"
                               id="nama_jabatan"
                               name="nama_jabatan"
                               value="<?= htmlspecialchars((string) $form['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?>"
                               required
                               placeholder="Contoh: Staff IT, Manager, HR Specialist">
                        <?php if (isset($errors['nama_jabatan'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?></div>
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
