<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/golongan.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

if ($_SESSION['user']['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'ID Golongan tidak valid.';
    header('Location: /?page=golongan/index');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

try {
    $stmt = $pdo->prepare('DELETE FROM golongan WHERE id = ?');
    $stmt->execute([$id]);

    $_SESSION['success'] = 'Golongan berhasil dihapus.';
} catch (Exception $e) {
    // Handling foreign key constraint error
    if (strpos($e->getMessage(), 'fk_karyawan_golongan') !== false) {
        $_SESSION['error'] = 'Gagal menghapus! Golongan ini masih digunakan oleh karyawan aktif.';
    } else {
        $_SESSION['error'] = 'Gagal menghapus golongan: ' . $e->getMessage();
    }
}

header('Location: /?page=golongan/index');
exit;