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

// Cek jika masih ada relasi dengan karyawan sebelum hapus
try {
    // 1. Cek karyawan yg pakai jabatan ini (kita belum buat fungsi ini, buat sql langsung)
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM karyawan WHERE jabatan_id = :id');
    $stmt->execute([':id' => $id]);
    $karyawanCount = (int) $stmt->fetch()['count'];

    if ($karyawanCount > 0) {
        $_SESSION['error'] = "Gagal menghapus! Jabatan ini sedang digunakan oleh {$karyawanCount} karyawan.";
        header('Location: /app/pages/jabatan/index.php');
        exit;
    }

    deleteJabatan($pdo, $id);
    $_SESSION['success'] = 'Jabatan berhasil dihapus.';
} catch (PDOException $e) {
    // Tangani FK Constraint fallback jika dicegat oleh MySQL
    if ($e->getCode() == 23000) {
        $_SESSION['error'] = 'Gagal menghapus! Jabatan ini sedang digunakan pada data lain.';
    } else {
        error_log($e->getMessage());
        $_SESSION['error'] = 'Terjadi kesalahan sistem saat menghapus jabatan.';
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat menghapus jabatan.';
}

header('Location: /app/pages/jabatan/index.php');
exit;
