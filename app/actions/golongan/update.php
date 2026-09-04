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
$nama_golongan = trim($_POST['nama_golongan'] ?? '');
$uang_makan = filter_input(INPUT_POST, 'uang_makan', FILTER_VALIDATE_FLOAT);

if (!$id) {
    $_SESSION['error'] = 'ID Golongan tidak valid.';
    header('Location: /?page=golongan/index');
    exit;
}

if (empty($nama_golongan)) {
    $_SESSION['error'] = 'Nama golongan tidak boleh kosong.';
    header('Location: /?page=golongan/edit&id=' . $id);
    exit;
}

if ($uang_makan === false || $uang_makan < 0) {
    $_SESSION['error'] = 'Uang makan tidak valid.';
    header('Location: /?page=golongan/edit&id=' . $id);
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

try {
    $stmt = $pdo->prepare('UPDATE golongan SET nama_golongan = ?, uang_makan = ? WHERE id = ?');
    $stmt->execute([$nama_golongan, $uang_makan, $id]);

    $_SESSION['success'] = 'Data golongan berhasil diupdate.';
    header('Location: /?page=golongan/index');
} catch (Exception $e) {
    $_SESSION['error'] = 'Gagal mengupdate golongan: ' . $e->getMessage();
    header('Location: /?page=golongan/edit&id=' . $id);
}
exit;