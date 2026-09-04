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

$bulanSaatIni = filter_input(INPUT_GET, 'bulan', FILTER_VALIDATE_INT) ?: (int) date('m');
$tahunSaatIni = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT) ?: (int) date('Y');
$modeKoreksi = ($_GET['mode'] ?? '') === 'koreksi';

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Proses Payroll</h1>
            <a href="/?page=payroll/index" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <h5 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i>Informasi Proses</h5>
            <p class="mb-0">Payroll Engine akan menghitung gaji secara otomatis untuk seluruh karyawan aktif berdasarkan data absensi, uang makan, tunjangan, dan PPh 21 pada periode yang dipilih.</p>
        </div>

        <?php if ($modeKoreksi): ?>
            <div class="alert alert-warning">
                <strong>Mode Koreksi Payroll.</strong> Payroll <em>Paid</em> lama akan dipertahankan sebagai histori berstatus <em>Corrected</em>; sistem membuat revisi baru untuk periode ini.
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="/?action=payroll/process" method="POST" onsubmit="return confirm('Payroll akan dihitung berdasarkan data terbaru. Draft dapat dihitung ulang, sedangkan payroll Processed tidak dapat diubah. Lanjutkan?');">
                    <?php if ($modeKoreksi): ?>
                        <input type="hidden" name="mode_koreksi" value="1">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="bulan" class="form-label">Periode Bulan <span class="text-danger">*</span></label>
                        <select class="form-select" id="bulan" name="bulan" required>
                            <option value="">-- Pilih Bulan --</option>
                            <?php foreach ($bulanList as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $bulanSaatIni === $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="tahun" class="form-label">Tahun <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="tahun" name="tahun" value="<?= $tahunSaatIni ?>" required min="2020" max="2099">
                    </div>

                    <div class="form-check form-switch border rounded p-3 mb-4">
                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="simpan_sebagai_draft" name="simpan_sebagai_draft" value="1" checked>
                        <label class="form-check-label fw-semibold" for="simpan_sebagai_draft">Simpan sebagai Draft</label>
                        <div class="form-text ms-0">Draft dapat diubah dengan memperbarui data absensi atau master data, lalu menjalankan ulang payroll untuk periode ini. Matikan toggle untuk mengunci hasil sebagai <em>Processed</em>.</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-gear-wide-connected me-2"></i> Proses Payroll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
