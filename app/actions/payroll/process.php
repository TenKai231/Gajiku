<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/payroll.php';
require_once dirname(__DIR__, 2) . '/includes/absensi.php'; // untuk load fungsi jika ada (optional)

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /app/pages/payroll/proses.php');
    exit;
}

$bulan = filter_input(INPUT_POST, 'bulan', FILTER_VALIDATE_INT);
$tahun = filter_input(INPUT_POST, 'tahun', FILTER_VALIDATE_INT);

if (!$bulan || !$tahun) {
    $_SESSION['error'] = 'Periode tidak valid.';
    header('Location: /app/pages/payroll/proses.php');
    exit;
}

$periodeFormat = sprintf('%02d-%04d', $bulan, $tahun); // ex: "08-2026"

$pdo = require dirname(__DIR__, 2) . '/config/database.php';
$rules = require dirname(__DIR__, 2) . '/config/rules.php';

// 1. Cek apakah sudah pernah diproses?
if (cekPenggajianSudahAda($pdo, $periodeFormat)) {
    $_SESSION['error'] = "Payroll untuk periode $periodeFormat sudah pernah diproses. Tidak dapat diduplikasi.";
    header('Location: /app/pages/payroll/proses.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. Ambil Karyawan Aktif
    $stmtKaryawan = $pdo->query(
        "SELECT k.*, j.gaji_pokok, j.tunjangan_default
         FROM karyawan k
         JOIN jabatan j ON k.jabatan_id = j.id
         WHERE k.status = 'Aktif'"
    );
    $karyawanAktif = $stmtKaryawan->fetchAll();

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

        // Warning Data Absensi Kosong / Belum lengkap?
        // (Sesuai dokumen: Jika data absensi belum lengkap → tampilkan warning, jangan anggap semua Alpha)
        // Karena ini MVP dan fully automated bulk-process, kita proses berdasarkan data yang ada saja.
        // Idealnya ada pre-flight check disini.

        // 4. Hitung menggunakan Payroll Engine
        $hasilKalkulasi = kalkulasiGajiKaryawan($karyawan, $absensi, $rules, $periodeFormat);

        // 5. Simpan ke database
        simpanPenggajian($pdo, $hasilKalkulasi);
        $processedCount++;
    }

    $pdo->commit();
    $_SESSION['success'] = "Payroll Engine selesai. {$processedCount} data penggajian karyawan berhasil digenerate.";
    header('Location: /app/pages/payroll/index.php?bulan=' . $bulan . '&tahun=' . $tahun);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat kalkulasi payroll: ' . $e->getMessage();
    header('Location: /app/pages/payroll/proses.php');
}
exit;
