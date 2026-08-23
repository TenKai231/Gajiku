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

try {
    // Di sini seharusnya idealnya mengecek apakah karyawan punya absensi/penggajian (karena FK).
    // Tapi karena database menangani relasi, kalau masih terikat FK MySQL akan throw 23000.
    deleteKaryawan($pdo, $id);
    $_SESSION['success'] = 'Data Karyawan berhasil dihapus.';
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        $_SESSION['error'] = 'Gagal menghapus! Karyawan ini memiliki data Absensi atau Penggajian. Nonaktifkan saja jika sudah tidak bekerja.';
    } else {
        error_log($e->getMessage());
        $_SESSION['error'] = 'Terjadi kesalahan sistem saat menghapus karyawan.';
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat menghapus karyawan.';
}

header('Location: /app/pages/karyawan/index.php');
exit;
