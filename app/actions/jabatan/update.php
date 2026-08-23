<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /app/pages/jabatan/index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'ID Jabatan tidak valid.';
    header('Location: /app/pages/jabatan/index.php');
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/config/database.php';

// Pastikan jabatan ada
$jabatan = fetchJabatanById($pdo, $id);
if (!$jabatan) {
    $_SESSION['error'] = 'Jabatan tidak ditemukan.';
    header('Location: /app/pages/jabatan/index.php');
    exit;
}

$input = [
    'nama_jabatan' => $_POST['nama_jabatan'] ?? '',
    'gaji_pokok' => $_POST['gaji_pokok'] ?? '',
    'tunjangan_default' => $_POST['tunjangan_default'] ?? '',
];

$validation = validateJabatanInput($input);
$errors = $validation['errors'];
$form = $validation['form'];
$data = $validation['data'];

// Cek duplikat nama jabatan (kecualikan diri sendiri)
if (empty($errors['nama_jabatan'])) {
    if (jabatanNameExists($pdo, $data['nama_jabatan'], $id)) {
        $errors['nama_jabatan'] = 'Nama jabatan sudah digunakan oleh jabatan lain.';
    }
}

if (count($errors) > 0) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form'] = $form;
    header('Location: /app/pages/jabatan/edit.php?id=' . $id);
    exit;
}

try {
    updateJabatan($pdo, $id, $data);
    $_SESSION['success'] = 'Jabatan berhasil diupdate.';
    header('Location: /app/pages/jabatan/index.php');
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat mengupdate jabatan.';
    $_SESSION['form'] = $form;
    header('Location: /app/pages/jabatan/edit.php?id=' . $id);
}
exit;
