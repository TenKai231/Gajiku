<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/absensi.php';

requireAuth();
$user = currentUser();
if (!in_array($user['role'], ['ADMIN', 'HR'], true)) {
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

try {
    deleteAbsensi($pdo, $id);
    $_SESSION['success'] = 'Data Absensi berhasil dihapus.';
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat menghapus absensi.';
}

header('Location: /?page=absensi/index');
exit;
