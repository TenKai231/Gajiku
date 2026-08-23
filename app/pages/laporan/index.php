<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';
require_once dirname(__DIR__, 2) . '/includes/payroll.php';

requireAuth();
$user = currentUser();

$pdo = require dirname(__DIR__, 2) . '/config/database.php';

$bulan = filter_input(INPUT_GET, 'bulan', FILTER_VALIDATE_INT) ?: (int) date('m');
$tahun = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT) ?: (int) date('Y');

$periodeFormat = sprintf('%02d-%04d', $bulan, $tahun); // misal "08-2026"
$dataPayroll = fetchPenggajianByPeriode($pdo, $periodeFormat);

$bulanList = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$namaBulan = $bulanList[$bulan] ?? (string)$bulan;
$strPeriode = $namaBulan . ' ' . $tahun;

// Hitung total untuk laporan
$totalGajiPokok = 0;
$totalTunjangan = 0;
$totalPotongan = 0;
$totalGajiBersih = 0;

foreach ($dataPayroll as $row) {
    $totalGajiPokok += (float)$row['gaji_pokok'];
    $totalTunjangan += (float)$row['total_tunjangan'];
    $totalPotongan += (float)$row['total_potongan'];
    $totalGajiBersih += (float)$row['gaji_bersih'];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penggajian <?= $strPeriode ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .card-body { padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 no-print">
        <div class="container">
            <a class="navbar-brand" href="/index.php">Gajiku</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/index.php">Dashboard</a></li>
                    <?php if ($user['role'] === 'ADMIN'): ?>
                        <li class="nav-item"><a class="nav-link" href="/app/pages/jabatan/index.php">Jabatan</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/app/pages/karyawan/index.php">Karyawan</a></li>
                    <li class="nav-item"><a class="nav-link" href="/app/pages/absensi/index.php">Absensi</a></li>
                    <li class="nav-item"><a class="nav-link" href="/app/pages/payroll/index.php">Penggajian</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/app/pages/laporan/index.php">Laporan</a></li>
                </ul>
                <span class="navbar-text me-3">
                    <?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <a href="/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <main class="container mb-5">

        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h1 class="h3 mb-0">Laporan Penggajian</h1>
            <button onclick="window.print()" class="btn btn-primary">Cetak / PDF Laporan</button>
        </div>

        <!-- Filter Form (Hanya Tampil di Layar) -->
        <div class="card border-0 shadow-sm mb-4 no-print">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="bulan" class="form-label">Periode Bulan</label>
                        <select class="form-select" id="bulan" name="bulan">
                            <?php foreach ($bulanList as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $bulan === $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="tahun" class="form-label">Tahun</label>
                        <input type="number" class="form-control" id="tahun" name="tahun" value="<?= $tahun ?>" min="2020" max="2099">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary w-100">Filter Laporan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Area yang akan di-print -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <div class="text-center mb-4 pb-3 border-bottom">
                    <h2 class="mb-1 fw-bold">LAPORAN REKAPITULASI PENGGAJIAN</h2>
                    <h5 class="mb-2">PT GAJIKU MAJU SEJAHTERA</h5>
                    <p class="mb-0 text-muted">Periode: <?= $strPeriode ?></p>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped table-hover mb-0 align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama Karyawan</th>
                                <th>Gaji Pokok</th>
                                <th>Tunjangan</th>
                                <th>Potongan</th>
                                <th>Gaji Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($dataPayroll) > 0): ?>
                                <?php $no = 1; foreach ($dataPayroll as $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row['nip'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end"><?= formatCurrency((float) $row['gaji_pokok']) ?></td>
                                        <td class="text-end"><?= formatCurrency((float) $row['total_tunjangan']) ?></td>
                                        <td class="text-end text-danger"><?= formatCurrency((float) $row['total_potongan']) ?></td>
                                        <td class="text-end text-success fw-bold"><?= formatCurrency((float) $row['gaji_bersih']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">Tidak ada data penggajian untuk periode ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (count($dataPayroll) > 0): ?>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end pe-3">TOTAL KESELURUHAN</td>
                                <td class="text-end"><?= formatCurrency($totalGajiPokok) ?></td>
                                <td class="text-end"><?= formatCurrency($totalTunjangan) ?></td>
                                <td class="text-end text-danger"><?= formatCurrency($totalPotongan) ?></td>
                                <td class="text-end text-success fs-5"><?= formatCurrency($totalGajiBersih) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>

                <div class="row mt-5 pt-3">
                    <div class="col-8"></div>
                    <div class="col-4 text-center">
                        <p class="mb-5">Mengetahui,</p>
                        <p class="mb-0 text-decoration-underline fw-bold">Pimpinan / HR Manager</p>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>