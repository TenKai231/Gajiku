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

$nama_golongan = trim($_POST['nama_golongan'] ?? '');
$uang_makan = filter_input(INPUT_POST, 'uang_makan', FILTER_VALIDATE_FLOAT);

if (empty($nama_golongan)) {
    $_SESSION['error'] = 'Nama golongan tidak boleh kosong.';
    header('Location: /?page=golongan/create');
    exit;
}

if ($uang_makan === false || $uang_makan < 0) {
    $_SESSION['error'] = 'Uang makan tidak valid.';
    header('Location: /?page=golongan/create');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

try {
    $stmt = $pdo->prepare('INSERT INTO golongan (nama_golongan, uang_makan) VALUES (?, ?)');
    $stmt->execute([$nama_golongan, $uang_makan]);

    $_SESSION['success'] = 'Golongan berhasil ditambahkan.';
    header('Location: /?page=golongan/index');
} catch (Exception $e) {
    $_SESSION['error'] = 'Gagal menambah golongan: ' . $e->getMessage();
    header('Location: /?page=golongan/create');
}
exit;