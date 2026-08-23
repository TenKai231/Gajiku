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

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'ID Absensi tidak valid.';
    header('Location: /app/pages/absensi/index.php');
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/config/database.php';

try {
    deleteAbsensi($pdo, $id);
    $_SESSION['success'] = 'Data Absensi berhasil dihapus.';
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat menghapus absensi.';
}

header('Location: /app/pages/absensi/index.php');
exit;
