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
$tunjangan = filter_input(INPUT_POST, 'tunjangan', FILTER_VALIDATE_FLOAT);

$standardList = getStandardGolonganList();

if (empty($nama_golongan)) {
    $_SESSION['error'] = 'Golongan wajib dipilih.';
    header('Location: /?page=golongan/create');
    exit;
}

if (!array_key_exists($nama_golongan, $standardList)) {
    $_SESSION['error'] = 'Golongan harus mengikuti tingkatan standar perusahaan.';
    header('Location: /?page=golongan/create');
    exit;
}

if ($uang_makan === false || $uang_makan < 0) {
    $_SESSION['error'] = 'Nominal uang makan tidak valid (tidak boleh negatif).';
    header('Location: /?page=golongan/create');
    exit;
}

if ($tunjangan === false || $tunjangan < 0) {
    $_SESSION['error'] = 'Nominal tunjangan tidak valid (tidak boleh negatif).';
    header('Location: /?page=golongan/create');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

if (golonganExists($pdo, $nama_golongan)) {
    $_SESSION['error'] = 'Golongan ' . htmlspecialchars($nama_golongan) . ' sudah terdaftar.';
    header('Location: /?page=golongan/create');
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO golongan (nama_golongan, uang_makan, tunjangan) VALUES (?, ?, ?)');
    $stmt->execute([$nama_golongan, $uang_makan, $tunjangan]);

    $_SESSION['success'] = 'Golongan ' . htmlspecialchars($nama_golongan) . ' berhasil ditambahkan.';
    header('Location: /?page=golongan/index');
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Gagal menambah golongan: Golongan mungkin sudah terdaftar.';
    header('Location: /?page=golongan/create');
}
exit;
