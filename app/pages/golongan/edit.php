<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/golongan.php';
requireAuth();

if ($_SESSION['user']['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /?page=golongan/index');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$golongan = fetchGolonganById($pdo, $id);

if (!$golongan) {
    $_SESSION['error'] = 'Golongan tidak ditemukan.';
    header('Location: /?page=golongan/index');
    exit;
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Golongan</h1>
    <a href="/?page=golongan/index" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="/?action=golongan/update" method="POST">
            <input type="hidden" name="id" value="<?= $golongan['id'] ?>">

            <div class="mb-3">
                <label for="nama_golongan" class="form-label">Nama Golongan <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_golongan" name="nama_golongan" required value="<?= htmlspecialchars((string) $golongan['nama_golongan'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-4">
                <label for="uang_makan" class="form-label">Uang Makan <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" class="form-control" id="uang_makan" name="uang_makan" required min="0" step="1000" value="<?= (float) $golongan['uang_makan'] ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Golongan</button>
        </form>
    </div>
</div>