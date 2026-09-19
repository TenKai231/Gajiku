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
$tunjangan = filter_input(INPUT_POST, 'tunjangan', FILTER_VALIDATE_FLOAT);

$standardList = getStandardGolonganList();

if (!$id) {
    $_SESSION['error'] = 'ID Golongan tidak valid.';
    header('Location: /?page=golongan/index');
    exit;
}

if (empty($nama_golongan)) {
    $_SESSION['error'] = 'Golongan wajib dipilih.';
    header('Location: /?page=golongan/edit&id=' . $id);
    exit;
}

if (!array_key_exists($nama_golongan, $standardList)) {
    $_SESSION['error'] = 'Golongan harus mengikuti tingkatan standar perusahaan.';
    header('Location: /?page=golongan/edit&id=' . $id);
    exit;
}

if ($uang_makan === false || $uang_makan < 0) {
    $_SESSION['error'] = 'Nominal uang makan tidak valid (tidak boleh negatif).';
    header('Location: /?page=golongan/edit&id=' . $id);
    exit;
}

if ($tunjangan === false || $tunjangan < 0) {
    $_SESSION['error'] = 'Nominal tunjangan tidak valid (tidak boleh negatif).';
    header('Location: /?page=golongan/edit&id=' . $id);
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

if (golonganExists($pdo, $nama_golongan, $id)) {
    $_SESSION['error'] = 'Golongan ' . htmlspecialchars($nama_golongan) . ' sudah terdaftar pada data lain.';
    header('Location: /?page=golongan/edit&id=' . $id);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE golongan SET nama_golongan = ?, uang_makan = ?, tunjangan = ? WHERE id = ?');
    $stmt->execute([$nama_golongan, $uang_makan, $tunjangan, $id]);

    $_SESSION['success'] = 'Data golongan berhasil diupdate.';
    header('Location: /?page=golongan/index');
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Gagal mengupdate golongan: Terjadi kesalahan sistem.';
    header('Location: /?page=golongan/edit&id=' . $id);
}
exit;
