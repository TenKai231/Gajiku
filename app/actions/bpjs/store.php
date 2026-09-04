<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
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
$nomorKesehatan = trim($_POST['nomor_kesehatan'] ?? '');
$nomorKetenagakerjaan = trim($_POST['nomor_ketenagakerjaan'] ?? '');
$statusKesehatan = trim($_POST['status_kesehatan'] ?? 'AKTIF');
$statusKetenagakerjaan = trim($_POST['status_ketenagakerjaan'] ?? 'AKTIF');
$riskLevelJkk = trim($_POST['risk_level_jkk'] ?? '');

if (!$karyawanId) {
    setFlashMessage('error', 'Karyawan ID wajib diisi.');
    header('Location: /?page=karyawan/index');
    exit;
}

try {
    require_once dirname(__DIR__, 2) . '/config/database.php';
    $pdo = getPDO();

    // Validasi enum manual
    $validStatus = ['AKTIF', 'TIDAK_AKTIF'];
    $validRisk = ['SANGAT_RENDAH', 'RENDAH', 'SEDANG', 'TINGGI', 'SANGAT_TINGGI', ''];

    if (!in_array($statusKesehatan, $validStatus) || !in_array($statusKetenagakerjaan, $validStatus)) {
        throw new Exception("Status BPJS tidak valid.");
    }

    if (!in_array($riskLevelJkk, $validRisk)) {
        throw new Exception("Level Risiko JKK tidak valid.");
    }

    // Check if profile exists
    $stmt = $pdo->prepare('SELECT id FROM data_bpjs_karyawan WHERE karyawan_id = ?');
    $stmt->execute([$karyawanId]);
    $existing = $stmt->fetch();

    $riskLevel = $riskLevelJkk === '' ? null : $riskLevelJkk;

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE data_bpjs_karyawan SET nomor_kesehatan = ?, nomor_ketenagakerjaan = ?, status_kesehatan = ?, status_ketenagakerjaan = ?, risk_level_jkk = ? WHERE karyawan_id = ?');
        $stmt->execute([
            $nomorKesehatan ?: null, 
            $nomorKetenagakerjaan ?: null, 
            $statusKesehatan, 
            $statusKetenagakerjaan, 
            $riskLevel, 
            $karyawanId
        ]);
        setFlashMessage('success', 'Data BPJS karyawan berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO data_bpjs_karyawan (karyawan_id, nomor_kesehatan, nomor_ketenagakerjaan, status_kesehatan, status_ketenagakerjaan, risk_level_jkk) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $karyawanId, 
            $nomorKesehatan ?: null, 
            $nomorKetenagakerjaan ?: null, 
            $statusKesehatan, 
            $statusKetenagakerjaan, 
            $riskLevel
        ]);
        setFlashMessage('success', 'Data BPJS karyawan berhasil ditambahkan.');
    }

} catch (Exception $e) {
    setFlashMessage('error', 'Gagal menyimpan data BPJS: ' . $e->getMessage());
}

header('Location: /?page=karyawan/detail&id=' . $karyawanId . '#bpjs-section');
exit;
