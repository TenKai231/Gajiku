<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAuth();

if ($_SESSION['user']['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Tambah Golongan</h1>
    <a href="/?page=golongan/index" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="/?action=golongan/store" method="POST">
            <div class="mb-3">
                <label for="nama_golongan" class="form-label">Nama Golongan <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_golongan" name="nama_golongan" required placeholder="Contoh: I/A">
            </div>
            <div class="mb-4">
                <label for="uang_makan" class="form-label">Uang Makan <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" class="form-control" id="uang_makan" name="uang_makan" required min="0" step="1000" placeholder="0">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Golongan</button>
        </form>
    </div>
</div>