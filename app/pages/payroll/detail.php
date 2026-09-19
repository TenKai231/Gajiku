<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';

requireAuth();
$user = currentUser();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /?page=payroll/index');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT p.*, k.nama, k.nip, j.nama_jabatan, g.nama_golongan
     FROM penggajian p
     JOIN karyawan k ON p.karyawan_id = k.id
     LEFT JOIN jabatan j ON k.jabatan_id = j.id
     LEFT JOIN golongan g ON k.golongan_id = g.id
     WHERE p.id = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$slip = $stmt->fetch();

if (!$slip) {
    die('Slip gaji tidak ditemukan.');
}

// Convert format periode (Misal 08-2026 -> Agustus 2026)
list($b, $t) = explode('-', (string) $slip['periode']);
$bulanArr = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$namaBulan = $bulanArr[(int)$b] ?? $b;
$strPeriode = $namaBulan . ' ' . $t;

?>
<style>
    .slip-gaji {
        max-width: 600px;
        margin: 40px auto;
        border: 1px dashed #ccc;
        padding: 30px;
        background: #fff;
    }
    @media print {
        .no-print { display: none !important; }
        .slip-gaji { border: none; margin: 0; padding: 0; box-shadow: none; max-width: none; }
    }
</style>

<div class="row justify-content-center">
    <div class="col-12">
        <?php if ($slip['status'] === 'Draft'): ?>
            <div class="alert alert-warning no-print" role="alert">
                <strong>Payroll masih Draft.</strong> Nominal dapat berubah saat payroll dihitung ulang.
            </div>
        <?php endif; ?>
        <?php if ($slip['status'] === 'Corrected'): ?>
            <div class="alert alert-secondary no-print" role="alert">
                <strong>Dokumen histori.</strong> Payroll ini sudah dikoreksi oleh revisi berikutnya dan tidak dapat diubah.
            </div>
        <?php endif; ?>
        <div class="mb-4 no-print text-center">
            <a href="/?page=payroll/index" class="btn btn-outline-secondary me-2">Kembali</a>
            <button onclick="window.print()" class="btn btn-primary">Cetak / Export PDF</button>
        </div>

        <div class="slip-gaji shadow-sm">
            <div class="text-center mb-4 border-bottom pb-3">
                <h2 class="mb-1 fw-bold">SLIP GAJI</h2>
                <h5 class="text-muted mb-0">Kantor Jaya Bersama</h5>
                <span class="badge <?= $slip['status'] === 'Draft' ? 'bg-warning text-dark' : ($slip['status'] === 'Paid' ? 'bg-success' : ($slip['status'] === 'Corrected' ? 'bg-secondary' : 'bg-info text-dark')) ?> mt-2"><?= htmlspecialchars($slip['status'], ENT_QUOTES, 'UTF-8') ?> · Revisi <?= (int) $slip['revisi'] ?></span>
            </div>

            <?php
            $jabatanDisplay = $slip['nama_jabatan_snapshot'] ?: ($slip['nama_jabatan'] ?? '-');
            $golonganDisplay = $slip['nama_golongan_snapshot'] ?: ($slip['nama_golongan'] ?? '-');
            ?>
            <div class="row mb-4">
                <div class="col-6">
                    <p class="mb-1"><strong>NIP:</strong> <?= htmlspecialchars((string)$slip['nip'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mb-1"><strong>Nama:</strong> <?= htmlspecialchars((string)$slip['nama'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mb-0"><strong>Golongan:</strong> <?= htmlspecialchars((string)$golonganDisplay, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="col-6 text-end">
                    <p class="mb-1"><strong>Jabatan:</strong> <?= htmlspecialchars((string)$jabatanDisplay, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mb-0"><strong>Periode:</strong> <?= $strPeriode ?></p>
                </div>
            </div>

            <h6 class="fw-bold bg-light p-2 border">PENDAPATAN</h6>
            <div class="d-flex justify-content-between mb-2 px-2">
                <span>Gaji Pokok</span>
                <span><?= formatCurrency((float)$slip['gaji_pokok']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2 px-2">
                <span>Tunjangan</span>
                <span><?= formatCurrency((float)$slip['total_tunjangan']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3 px-2">
                <span>Uang Makan</span>
                <span><?= formatCurrency((float)$slip['total_uang_makan']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-4 px-2 fw-bold">
                <span>Total Penghasilan Bruto (Gaji Kotor)</span>
                <span><?= formatCurrency((float)$slip['gaji_kotor']) ?></span>
            </div>

            <h6 class="fw-bold bg-light p-2 border">POTONGAN</h6>
            <div class="d-flex justify-content-between mb-2 px-2">
                <span>Potongan Absensi (Alpha/Sakit)</span>
                <span class="text-danger">- <?= formatCurrency((float)$slip['potongan_lain']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2 px-2">
                <span>Potongan Keterlambatan (Uang Makan)</span>
                <span class="text-danger">- <?= formatCurrency((float)$slip['potongan_uang_makan']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3 px-2">
                <span>PPh Pasal 21</span>
                <span class="text-danger">- <?= formatCurrency((float)$slip['pph21']) ?></span>
            </div>
            
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                <span>Potongan BPJS Karyawan</span>
                <span class="text-danger">- <?= formatCurrency((float)($slip['potongan_bpjs'] ?? 0)) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-4 px-2 fw-bold">
                <span>Total Potongan</span>
                <span class="text-danger">- <?= formatCurrency((float)$slip['total_potongan']) ?></span>
            </div>

            <div class="d-flex justify-content-between p-3 mt-4 bg-light border fw-bold" style="font-size: 1.2rem;">
                <span>GAJI BERSIH (TAKE HOME PAY)</span>
                <span class="text-success"><?= formatCurrency((float)$slip['gaji_bersih']) ?></span>
            </div>

            <?php if ($user['role'] === 'ADMIN' || $user['role'] === 'HR'): ?>
            <div class="mt-4 border-top pt-3 text-muted" style="font-size: 0.85rem;">
                <p class="fw-bold mb-1">Informasi Pajak (Hanya tampil untuk HR/Admin):</p>
                <ul class="mb-0 ps-3">
                    <li>Status PTKP Snapshot: <strong><?= htmlspecialchars($slip['status_ptkp_snapshot'] ?? '-', ENT_QUOTES) ?></strong></li>
                    <li>Kategori TER Snapshot: <strong><?= htmlspecialchars($slip['kategori_ter_snapshot'] ?? '-', ENT_QUOTES) ?></strong></li>
                </ul>
            </div>
            <?php endif; ?>

            <div class="row mt-5 pt-4 text-center">
                <div class="col-6">
                    <p class="mb-5">HR / Finance</p>
                    <p class="text-decoration-underline mb-0">Administrator</p>
                </div>
                <div class="col-6">
                    <p class="mb-5">Penerima</p>
                    <p class="text-decoration-underline mb-0"><?= htmlspecialchars((string)$slip['nama'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
