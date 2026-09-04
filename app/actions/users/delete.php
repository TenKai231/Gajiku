<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('error', 'ID user tidak valid.');
    header('Location: /?page=users/index');
    exit;
}

if ($id === (int) $user['id']) {
    setFlashMessage('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
    header('Location: /?page=users/index');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    setFlashMessage('success', 'User berhasil dihapus.');
} catch (PDOException $e) {
    setFlashMessage('error', 'Gagal menghapus user: ' . $e->getMessage());
}

header('Location: /?page=users/index');
exit;
