<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/pajak.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

$user = currentUser();
if (!in_array($user['role'], ['ADMIN', 'HR'], true)) {
    http_response_code(403);
    die('Akses ditolak.');
}

$karyawanId = filter_input(INPUT_POST, 'karyawan_id', FILTER_VALIDATE_INT);
$nik = trim($_POST['nik'] ?? '');
$npwp = trim($_POST['npwp'] ?? '');
$statusPtkp = trim($_POST['status_ptkp'] ?? '');

if (!$karyawanId || !$nik || !$statusPtkp) {
    setFlashMessage('error', 'Karyawan ID, NIK, dan Status PTKP wajib diisi.');
    header('Location: /app/pages/karyawan/detail.php?id=' . ($karyawanId ?: ''));
    exit;
}

try {
    $kategoriTer = determineTERCategory($statusPtkp);
    require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

    // Check if tax profile exists
    $existing = getTaxProfile($pdo, $karyawanId);

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE data_pajak_karyawan SET nik = ?, npwp = ?, status_ptkp = ?, kategori_ter = ? WHERE karyawan_id = ?');
        $stmt->execute([$nik, $npwp ?: null, $statusPtkp, $kategoriTer, $karyawanId]);
        setFlashMessage('success', 'Data pajak karyawan berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO data_pajak_karyawan (karyawan_id, nik, npwp, status_ptkp, kategori_ter) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$karyawanId, $nik, $npwp ?: null, $statusPtkp, $kategoriTer]);
        setFlashMessage('success', 'Data pajak karyawan berhasil ditambahkan.');
    }

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'uq_pajak_nik') !== false) {
        setFlashMessage('error', 'NIK sudah digunakan oleh karyawan lain.');
    } else {
        setFlashMessage('error', 'Gagal menyimpan data pajak: ' . $e->getMessage());
    }
}

header('Location: /app/pages/karyawan/detail.php?id=' . $karyawanId);
exit;
