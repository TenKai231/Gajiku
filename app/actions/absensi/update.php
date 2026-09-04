<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/absensi.php';

requireAuth();
$user = currentUser();
if (!in_array($user['role'], ['ADMIN', 'FINANCE'], true)) {
    http_response_code(403);
    die('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=absensi/index');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'ID Absensi tidak valid.';
    header('Location: /?page=absensi/index');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$absensi = fetchAbsensiById($pdo, $id);
if (!$absensi) {
    $_SESSION['error'] = 'Data absensi tidak ditemukan.';
    header('Location: /?page=absensi/index');
    exit;
}

$input = [
    'karyawan_id' => $_POST['karyawan_id'] ?? '',
    'tanggal' => $_POST['tanggal'] ?? '',
    'status' => $_POST['status'] ?? '',
    'jam_masuk' => $_POST['jam_masuk'] ?? '',
    'jam_pulang' => $_POST['jam_pulang'] ?? '',
    'keterangan' => $_POST['keterangan'] ?? '',
];

$validation = validateAbsensiInput($input);
$errors = $validation['errors'];
$form = $validation['form'];
$data = $validation['data'];

// Cek duplikat (exclude id ini)
if (empty($errors['karyawan_id']) && empty($errors['tanggal'])) {
    if (absensiExists($pdo, $data['karyawan_id'], $data['tanggal'], $id)) {
        $errors['tanggal'] = 'Karyawan ini sudah memiliki data absensi pada tanggal tersebut.';
    }
}

if (count($errors) > 0) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form'] = $form;
    header('Location: /?page=absensi/edit&id=' . $id);
    exit;
}

try {
    updateAbsensi($pdo, $id, $data);
    $_SESSION['success'] = 'Data Absensi berhasil diupdate.';
    header('Location: /?page=absensi/index');
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat mengupdate absensi.';
    $_SESSION['form'] = $form;
    header('Location: /?page=absensi/edit&id=' . $id);
}
exit;
