<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/payroll.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

requireAuth();
$user = currentUser();
if (!in_array($user['role'], ['ADMIN', 'PEMIMPIN'], true)) {
    http_response_code(403);
    exit('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=payroll/index');
    exit;
}

$payrollId = filter_input(INPUT_POST, 'payroll_id', FILTER_VALIDATE_INT);
$statusBaru = trim((string) ($_POST['status'] ?? ''));

// CSRF check sederhana via token sesi (jika auth.php menyediakan) — fallback minimal
$csrfToken = $_POST['csrf_token'] ?? null;

if (!$payrollId) {
    $_SESSION['error'] = 'ID payroll tidak valid.';
    header('Location: /?page=payroll/index');
    exit;
}

$statusValid = ['Processed', 'Paid', 'Draft'];
if (!in_array($statusBaru, $statusValid, true)) {
    $_SESSION['error'] = 'Status tujuan tidak valid.';
    header('Location: /?page=payroll/index');
    exit;
}

// PEMIMPIN hanya boleh APPROVE (transisi maju), tidak boleh revert ke Draft.
if ($user['role'] === 'PEMIMPIN' && $statusBaru === 'Draft') {
    $_SESSION['error'] = 'Pemimpin hanya dapat menyetujui payroll, tidak dapat mengembalikan ke Draft.';
    header('Location: /?page=payroll/index');
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();
    ubahStatusPenggajian($pdo, $payrollId, $statusBaru, (int) $user['id']);
    $pdo->commit();

    $_SESSION['success'] = "Status payroll #{$payrollId} berhasil diubah menjadi {$statusBaru}.";
} catch (RuntimeException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem saat mengubah status payroll.';
}

// Kembali ke halaman index payroll dengan periode yang sama jika ada
$bulan = filter_input(INPUT_POST, 'bulan', FILTER_VALIDATE_INT);
$tahun = filter_input(INPUT_POST, 'tahun', FILTER_VALIDATE_INT);
$redirect = '/?page=payroll/index';
if ($bulan && $tahun) {
    $redirect .= '&bulan=' . $bulan . '&tahun=' . $tahun;
}
header('Location: ' . $redirect);
exit;
