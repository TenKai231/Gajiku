<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';
require_once dirname(__DIR__, 2) . '/includes/payroll.php';

requireAuth();
$user = currentUser();

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

$bulan = filter_input(INPUT_GET, 'bulan', FILTER_VALIDATE_INT) ?: (int) date('m');
$tahun = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT) ?: (int) date('Y');

$periodeFormat = sprintf('%02d-%04d', $bulan, $tahun);
$dataPayroll = fetchPenggajianRekapByPeriode($pdo, $periodeFormat);

$bulanList = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$namaBulan = $bulanList[$bulan] ?? (string)$bulan;
$strPeriode = $namaBulan . ' ' . $tahun;

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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan - <?= htmlspecialchars($strPeriode, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            background: #fff;
            color: #0F172A;
            font-family: 'Segoe UI', 'Open Sans', Arial, sans-serif;
            font-size: 13px;
        }
        h2, h5 { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; }

        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop h2 { font-size: 18px; letter-spacing: 1px; }
        .kop h5 { font-size: 14px; margin-top: 4px; font-weight: 600; }
        .kop p { font-size: 13px; margin: 4px 0 0; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td { border: 1px solid #333; padding: 6px 8px; }
        thead th { background: #eef2f7; text-align: center; font-size: 12px; text-transform: uppercase; }
        tbody tr:nth-child(even) { background: #f7f9fc; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-danger { color: #c0392b; }
        .text-success { color: #1e7e34; }
        .fw-bold { font-weight: 700; }

        tfoot td { background: #eef2f7; font-weight: 700; }

        .ttd {
            margin-top: 48px;
            display: flex;
            justify-content: flex-end;
        }
        .ttd-box { text-align: center; width: 260px; }
        .ttd-box .line { height: 64px; }
        .ttd-box .underline { text-decoration: underline; font-weight: 700; }

        @media print {
            body { margin: 0; padding: 12mm; }
            tbody tr, thead, tfoot { page-break-inside: avoid; }
        }
    </style>
    <script>
        // Buka dialog print otomatis saat halaman selesai dimuat
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</head>
<body>
    <div class="kop">
        <h2>LAPORAN REKAPITULASI PENGGAJIAN</h2>
        <h5>Kantor Jaya Bersama</h5>
        <p>Periode: <?= htmlspecialchars($strPeriode, ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px;">No</th>
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
                        <td class="text-center"><?= htmlspecialchars((string)$row['nip'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end"><?= formatCurrency((float)$row['gaji_pokok']) ?></td>
                        <td class="text-end"><?= formatCurrency((float)$row['total_tunjangan']) ?></td>
                        <td class="text-end text-danger"><?= formatCurrency((float)$row['total_potongan']) ?></td>
                        <td class="text-end text-success fw-bold"><?= formatCurrency((float)$row['gaji_bersih']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 24px;">Tidak ada data penggajian untuk periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <?php if (count($dataPayroll) > 0): ?>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end">TOTAL KESELURUHAN</td>
                <td class="text-end"><?= formatCurrency($totalGajiPokok) ?></td>
                <td class="text-end"><?= formatCurrency($totalTunjangan) ?></td>
                <td class="text-end text-danger"><?= formatCurrency($totalPotongan) ?></td>
                <td class="text-end text-success"><?= formatCurrency($totalGajiBersih) ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <div class="ttd">
        <div class="ttd-box">
            <p>Mengetahui,<br>Pimpinan / HR Manager</p>
            <div class="line"></div>
            <p class="underline mb-0">( ________________________ )</p>
        </div>
    </div>
</body>
</html>
