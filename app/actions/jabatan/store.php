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
    header('Location: /?page=jabatan');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

$input = [
    'nama_jabatan' => $_POST['nama_jabatan'] ?? '',
    'gaji_pokok' => $_POST['gaji_pokok'] ?? '',
    'tunjangan_default' => $_POST['tunjangan_default'] ?? '',
];

$validation = validateJabatanInput($input);
$errors = $validation['errors'];
$form = $validation['form'];
$data = $validation['data'];

// Cek duplikat nama jabatan
if (empty($errors['nama_jabatan'])) {
    if (jabatanNameExists($pdo, $data['nama_jabatan'])) {
        $errors['nama_jabatan'] = 'Nama jabatan sudah digunakan.';
    }
}

if (count($errors) > 0) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form'] = $form;
    header('Location: /?page=jabatan/create');
    exit;
}

try {
    createJabatan($pdo, $data);
    $_SESSION['success'] = 'Jabatan baru berhasil ditambahkan.';
    header('Location: /?page=jabatan');
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat menyimpan jabatan.';
    $_SESSION['form'] = $form;
    header('Location: /?page=jabatan/create');
}
exit;
