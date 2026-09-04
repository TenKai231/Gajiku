<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/payroll.php';
requireAuth();

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$pdo = getPDO();

$bulan = filter_input(INPUT_GET, 'bulan', FILTER_VALIDATE_INT) ?: (int) date('m');
$tahun = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT) ?: (int) date('Y');

$periodeFormat = sprintf('%02d-%04d', $bulan, $tahun); // misal "08-2026"
$dataPayroll = fetchPenggajianRekapByPeriode($pdo, $periodeFormat);

$bulanList = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$namaBulan = $bulanList[$bulan] ?? (string)$bulan;
$strPeriode = $namaBulan . ' ' . $tahun;

// Output headers to trigger download
$filename = 'Laporan_Penggajian_' . str_replace(' ', '_', $strPeriode) . '.xls';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$separator = "\t";

echo "LAPORAN REKAPITULASI PENGGAJIAN\n";
echo "Kantor Jaya Bersama\n";
echo "Periode: " . $strPeriode . "\n\n";

// Headers
echo "No" . $separator . "NIP" . $separator . "Nama Karyawan" . $separator . "Gaji Pokok" . $separator . "Tunjangan" . $separator . "Potongan" . $separator . "Gaji Bersih" . "\n";

$no = 1;
$totalGajiPokok = 0;
$totalTunjangan = 0;
$totalPotongan = 0;
$totalGajiBersih = 0;

foreach ($dataPayroll as $data) {
    echo $no++ . $separator;
    echo $data['nip'] . $separator;
    echo $data['nama'] . $separator;
    echo (float) $data['gaji_pokok'] . $separator;
    echo (float) $data['total_tunjangan'] . $separator;
    echo (float) $data['total_potongan'] . $separator;
    echo (float) $data['gaji_bersih'] . "\n";

    $totalGajiPokok += (float)$data['gaji_pokok'];
    $totalTunjangan += (float)$data['total_tunjangan'];
    $totalPotongan += (float)$data['total_potongan'];
    $totalGajiBersih += (float)$data['gaji_bersih'];
}

// Totals
echo "TOTAL KESELURUHAN" . $separator . "" . $separator . "" . $separator;
echo $totalGajiPokok . $separator;
echo $totalTunjangan . $separator;
echo $totalPotongan . $separator;
echo $totalGajiBersih . "\n";
exit;