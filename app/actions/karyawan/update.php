<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/karyawan.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /app/pages/karyawan/index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'ID Karyawan tidak valid.';
    header('Location: /app/pages/karyawan/index.php');
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/config/database.php';

$karyawan = fetchKaryawanById($pdo, $id);
if (!$karyawan) {
    $_SESSION['error'] = 'Karyawan tidak ditemukan.';
    header('Location: /app/pages/karyawan/index.php');
    exit;
}

$input = [
    'nip' => $_POST['nip'] ?? '',
    'nama' => $_POST['nama'] ?? '',
    'jenis_kelamin' => $_POST['jenis_kelamin'] ?? '',
    'tanggal_lahir' => $_POST['tanggal_lahir'] ?? '',
    'tanggal_masuk' => $_POST['tanggal_masuk'] ?? '',
    'jabatan_id' => $_POST['jabatan_id'] ?? '',
    'status' => $_POST['status'] ?? '',
];

$validation = validateKaryawanInput($input);
$errors = $validation['errors'];
$form = $validation['form'];
$data = $validation['data'];

// Cek duplikat NIP (exclude diri sendiri)
if (empty($errors['nip'])) {
    if (nipExists($pdo, $data['nip'], $id)) {
        $errors['nip'] = 'NIP sudah digunakan.';
    }
}

if (count($errors) > 0) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form'] = $form;
    header('Location: /app/pages/karyawan/edit.php?id=' . $id);
    exit;
}

try {
    updateKaryawan($pdo, $id, $data);
    $_SESSION['success'] = 'Data Karyawan berhasil diupdate.';
    header('Location: /app/pages/karyawan/index.php');
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat mengupdate data.';
    $_SESSION['form'] = $form;
    header('Location: /app/pages/karyawan/edit.php?id=' . $id);
}
exit;
