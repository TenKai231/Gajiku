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
    header('Location: /?page=karyawan/index');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$pdo = getPDO();

$input = [
    'nip' => $_POST['nip'] ?? '',
    'nama' => $_POST['nama'] ?? '',
    'jenis_kelamin' => $_POST['jenis_kelamin'] ?? '',
    'tanggal_lahir' => $_POST['tanggal_lahir'] ?? '',
    'tanggal_masuk' => $_POST['tanggal_masuk'] ?? '',
    'jabatan_id' => $_POST['jabatan_id'] ?? '',
    'golongan_id' => $_POST['golongan_id'] ?? '',
    'status' => $_POST['status'] ?? '',
];

$validation = validateKaryawanInput($input);
$errors = $validation['errors'];
$form = $validation['form'];
$data = $validation['data'];

// Cek duplikat NIP
if (empty($errors['nip'])) {
    if (nipExists($pdo, $data['nip'])) {
        $errors['nip'] = 'NIP sudah digunakan.';
    }
}

if (count($errors) > 0) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form'] = $form;
    header('Location: /?page=karyawan/create');
    exit;
}

try {
    createKaryawan($pdo, $data);
    $_SESSION['success'] = 'Data Karyawan berhasil ditambahkan.';
    header('Location: /?page=karyawan/index');
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat menyimpan data.';
    $_SESSION['form'] = $form;
    header('Location: /?page=karyawan/create');
}
exit;
