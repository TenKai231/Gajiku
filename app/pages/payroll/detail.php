<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';

requireAuth();
$user = currentUser();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /app/pages/payroll/index.php');
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/config/database.php';

$stmt = $pdo->prepare(
    'SELECT p.*, k.nama, k.nip, j.nama_jabatan
     FROM penggajian p
     JOIN karyawan k ON p.karyawan_id = k.id
     JOIN jabatan j ON k.jabatan_id = j.id
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
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slip Gaji - <?= htmlspecialchars((string)$slip['nama'], ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .slip-gaji {
            max-width: 600px;
            margin: 40px auto;
            border: 1px dashed #ccc;
            padding: 30px;
            background: #fff;
        }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .slip-gaji { border: none; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body class="bg-light">

    <div class="container">
        <div class="mb-4 mt-4 no-print text-center">
            <a href="index.php" class="btn btn-outline-secondary me-2">Kembali</a>
            <button onclick="window.print()" class="btn btn-primary">Cetak / Export PDF</button>
        </div>

        <div class="slip-gaji shadow-sm">
            <div class="text-center mb-4 border-bottom pb-3">
                <h2 class="mb-1 fw-bold">SLIP GAJI</h2>
                <h5 class="text-muted mb-0">PT GAJIKU MAJU SEJAHTERA</h5>
            </div>

            <div class="row mb-4">
                <div class="col-6">
                    <p class="mb-1"><strong>NIP:</strong> <?= htmlspecialchars((string)$slip['nip'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mb-0"><strong>Nama:</strong> <?= htmlspecialchars((string)$slip['nama'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="col-6 text-end">
                    <p class="mb-1"><strong>Jabatan:</strong> <?= htmlspecialchars((string)$slip['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mb-0"><strong>Periode:</strong> <?= $strPeriode ?></p>
                </div>
            </div>

            <h6 class="fw-bold bg-light p-2 border">PENDAPATAN</h6>
            <div class="d-flex justify-content-between mb-2 px-2">
                <span>Gaji Pokok</span>
                <span><?= formatCurrency((float)$slip['gaji_pokok']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3 px-2">
                <span>Tunjangan</span>
                <span><?= formatCurrency((float)$slip['total_tunjangan']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-4 px-2 fw-bold">
                <span>Total Gaji Kotor</span>
                <span><?= formatCurrency((float)$slip['gaji_kotor']) ?></span>
            </div>

            <h6 class="fw-bold bg-light p-2 border">POTONGAN</h6>
            <div class="d-flex justify-content-between mb-3 px-2">
                <span>Total Potongan (Absensi & Keterlambatan)</span>
                <span class="text-danger">- <?= formatCurrency((float)$slip['total_potongan']) ?></span>
            </div>

            <div class="d-flex justify-content-between p-3 mt-4 bg-light border fw-bold" style="font-size: 1.2rem;">
                <span>GAJI BERSIH (TAKE HOME PAY)</span>
                <span class="text-success"><?= formatCurrency((float)$slip['gaji_bersih']) ?></span>
            </div>

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

</body>
</html>