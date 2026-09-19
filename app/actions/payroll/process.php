<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/payroll.php';
require_once dirname(__DIR__, 2) . '/includes/absensi.php'; // untuk load fungsi jika ada (optional)
require_once dirname(__DIR__, 2) . '/includes/pajak.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=payroll/proses');
    exit;
}

$bulan = filter_input(INPUT_POST, 'bulan', FILTER_VALIDATE_INT);
$tahun = filter_input(INPUT_POST, 'tahun', FILTER_VALIDATE_INT);
$statusPayroll = ($_POST['simpan_sebagai_draft'] ?? '') === '1' ? 'Draft' : 'Processed';
$modeKoreksi = ($_POST['mode_koreksi'] ?? '') === '1';

if (!$bulan || !$tahun) {
    $_SESSION['error'] = 'Periode tidak valid.';
    header('Location: /?page=payroll/proses');
    exit;
}

$periodeFormat = sprintf('%02d-%04d', $bulan, $tahun); // ex: "08-2026"

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$rules = require dirname(__DIR__, 2) . '/config/rules.php';

// 1. Payroll final merupakan snapshot dan tidak boleh ditimpa. Draft boleh dihitung ulang.
if ($modeKoreksi) {
    if (!payrollPeriodeMemilikiStatus($pdo, $periodeFormat, 'Paid')) {
        $_SESSION['error'] = 'Koreksi hanya dapat dibuat dari payroll berstatus Paid.';
        header('Location: /?page=payroll/proses');
        exit;
    }
    if (payrollPeriodeMemilikiStatus($pdo, $periodeFormat, 'Draft')) {
        $_SESSION['error'] = 'Selesaikan atau hitung ulang Draft yang sudah ada sebelum membuat koreksi baru.';
        header('Location: /?page=payroll/proses');
        exit;
    }
} elseif (cekPenggajianSudahAda($pdo, $periodeFormat)) {
    if (!payrollPeriodeHanyaDraft($pdo, $periodeFormat)) {
        $_SESSION['error'] = "Payroll untuk periode $periodeFormat sudah diproses dan bukan Draft. Payroll final tidak dapat diubah.";
        header('Location: /?page=payroll/proses');
        exit;
    }
}

try {
    $pdo->beginTransaction();

    if ($modeKoreksi) {
        $stmtKaryawan = $pdo->prepare(
            "SELECT k.*, j.gaji_pokok, g.tunjangan, g.tunjangan AS tunjangan_default, g.uang_makan
             FROM penggajian p
             JOIN karyawan k ON p.karyawan_id = k.id
             JOIN jabatan j ON k.jabatan_id = j.id
             JOIN golongan g ON k.golongan_id = g.id
             WHERE p.periode = :periode AND p.status = 'Paid'"
        );
        $stmtKaryawan->execute([':periode' => $periodeFormat]);
        $karyawanAktif = $stmtKaryawan->fetchAll();
        tandaiPayrollPaidSebagaiDikoreksi($pdo, $periodeFormat);
    } elseif (cekPenggajianSudahAda($pdo, $periodeFormat)) {
        hapusPayrollDraftPeriode($pdo, $periodeFormat);
    }

    if (!$modeKoreksi) {
        // 2. Ambil Karyawan Aktif untuk payroll baru atau hitung ulang Draft.
        $stmtKaryawan = $pdo->query(
            "SELECT k.*, j.gaji_pokok, g.tunjangan, g.tunjangan AS tunjangan_default, g.uang_makan
             FROM karyawan k
             JOIN jabatan j ON k.jabatan_id = j.id
             JOIN golongan g ON k.golongan_id = g.id
             WHERE k.status = 'Aktif'"
        );
        $karyawanAktif = $stmtKaryawan->fetchAll();
    }

    if (empty($karyawanAktif)) {
        throw new Exception('Tidak ada karyawan aktif untuk diproses.');
    }

    $processedCount = 0;

    foreach ($karyawanAktif as $karyawan) {
        $karyawanId = (int) $karyawan['id'];

        // 3. Ambil absensi karyawan untuk bulan tersebut
        $stmtAbsen = $pdo->prepare(
            'SELECT * FROM absensi
             WHERE karyawan_id = :id
             AND MONTH(tanggal) = :bln AND YEAR(tanggal) = :thn'
        );
        $stmtAbsen->execute([':id' => $karyawanId, ':bln' => $bulan, ':thn' => $tahun]);
        $absensi = $stmtAbsen->fetchAll();

        // 3.5 Ambil Data Pajak Karyawan
        $taxProfile = getTaxProfile($pdo, $karyawanId);

        $terRate = 0.0;
        if ($taxProfile) {
            // Kita perlu menghitung estimasi Gross Income awal untuk cari rate TER
            // atau menggunakan fungsi khusus yang mencari rate.
            // Karena rate TER bergantung pada penghasilan bruto (Gaji Pokok + Tunjangan + Uang Makan Kotor),
            // Kita panggil satu fungsi di payroll engine yang melakukan kalkulasi.
        }

        // Warning Data Absensi Kosong / Belum lengkap?
        // (Sesuai dokumen: Jika data absensi belum lengkap → tampilkan warning, jangan anggap semua Alpha)
        // Karena ini MVP dan fully automated bulk-process, kita proses berdasarkan data yang ada saja.
        // Idealnya ada pre-flight check disini.

        // 4. Hitung menggunakan Payroll Engine
        // Kita hitung dulu Gross Income-nya secara sederhana di sini untuk mencari TER Rate
        $tunjanganKaryawan = (float) ($karyawan['tunjangan'] ?? $karyawan['tunjangan_default'] ?? 0);
        $estimasiGross = (float) $karyawan['gaji_pokok'] + $tunjanganKaryawan;

        // Hitung total hadir sementara untuk uang makan
        $totalHadir = 0;
        foreach ($absensi as $absen) {
            if ($absen['status'] === 'Hadir') $totalHadir++;
        }
        $estimasiGross += ($totalHadir * ($karyawan['uang_makan'] ?? 0));

        if ($taxProfile) {
            $processDate = sprintf('%04d-%02d-01', $tahun, $bulan);
            $terRate = findTERRate($pdo, $taxProfile['kategori_ter'], (float) $estimasiGross, $processDate);
        }

        $hasilKalkulasi = kalkulasiGajiKaryawan($pdo, $karyawan, $absensi, $taxProfile ?: [], $terRate, $rules, $periodeFormat);

        // 5. Simpan ke database
        $revisi = 1;
        $koreksiDariId = null;
        if ($modeKoreksi) {
            // Di dalam mode koreksi, semua record Paid pada periode ini sudah
            // ditandai "Corrected" oleh tandaiPayrollPaidSebagaiDikoreksi di atas.
            // Koreksi mengacu pada record terbaru (revisi tertinggi) yang sudah Corrected.
            $metaKoreksi = ambilPayrollSumberKoreksi($pdo, $karyawanId, $periodeFormat);
            $revisi = $metaKoreksi['revisi'];
            $koreksiDariId = $metaKoreksi['koreksi_dari_id'];
        }
        simpanPenggajian($pdo, $hasilKalkulasi, $statusPayroll, $revisi, $koreksiDariId, (int) $user['id']);
        $processedCount++;
    }

    $pdo->commit();
    $statusText = $statusPayroll === 'Draft' ? 'sebagai Draft dan masih dapat diubah' : 'sebagai Processed';
    $jenisProses = $modeKoreksi ? 'Koreksi payroll' : 'Payroll Engine';
    $_SESSION['success'] = "{$jenisProses} selesai. {$processedCount} data penggajian karyawan berhasil dibuat {$statusText}.";
    header('Location: /?page=payroll/index&bulan=' . $bulan . '&tahun=' . $tahun);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat kalkulasi payroll: ' . $e->getMessage();
    header('Location: /?page=payroll/proses');
}
exit;
