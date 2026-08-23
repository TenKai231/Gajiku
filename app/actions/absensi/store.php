<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/absensi.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /app/pages/absensi/index.php');
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/config/database.php';

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

// Cek duplikat Absensi (1 karyawan tidak boleh punya 2 absensi di tanggal yg sama)
if (empty($errors['karyawan_id']) && empty($errors['tanggal'])) {
    if (absensiExists($pdo, $data['karyawan_id'], $data['tanggal'])) {
        $errors['tanggal'] = 'Karyawan ini sudah memiliki data absensi pada tanggal tersebut.';
    }
}

if (count($errors) > 0) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form'] = $form;
    header('Location: /app/pages/absensi/create.php');
    exit;
}

try {
    createAbsensi($pdo, $data);
    $_SESSION['success'] = 'Data Absensi berhasil ditambahkan.';
    header('Location: /app/pages/absensi/index.php');
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat menyimpan absensi.';
    $_SESSION['form'] = $form;
    header('Location: /app/pages/absensi/create.php');
}
exit;
