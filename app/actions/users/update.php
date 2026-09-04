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
$username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$role = trim($_POST['role'] ?? '');
$password = $_POST['password'] ?? '';

if (!$id || !$username || !$role) {
    setFlashMessage('error', 'ID, Username, dan Role wajib diisi.');
    header('Location: /?page=users/index');
    exit;
}

if (!in_array($role, ['ADMIN', 'HR'], true)) {
    setFlashMessage('error', 'Role tidak valid.');
    header('Location: /?page=users/index');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

try {
    if ($password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
        $stmt->execute([$username, $role, $hashedPassword, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $role, $id]);
    }
    
    setFlashMessage('success', 'Data user berhasil diperbarui.');
} catch (PDOException $e) {
    if ($e->errorInfo[1] === 1062) {
        setFlashMessage('error', 'Gagal menyimpan: Username sudah digunakan.');
    } else {
        setFlashMessage('error', 'Gagal memperbarui user: ' . $e->getMessage());
    }
}

header('Location: /?page=users/index');
exit;
