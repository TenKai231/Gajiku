<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/payroll.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    exit('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=payroll/index');
    exit;
}

$bulan = filter_input(INPUT_POST, 'bulan', FILTER_VALIDATE_INT);
$tahun = filter_input(INPUT_POST, 'tahun', FILTER_VALIDATE_INT);
if (!$bulan || !$tahun) {
    $_SESSION['error'] = 'Periode koreksi tidak valid.';
    header('Location: /?page=payroll/index');
    exit;
}

$periode = sprintf('%02d-%04d', $bulan, $tahun);
$pdo = getPDO();
if (!payrollPeriodeMemilikiStatus($pdo, $periode, 'Paid')) {
    $_SESSION['error'] = 'Koreksi hanya dapat dibuat jika payroll periode tersebut berstatus Paid.';
    header('Location: /?page=payroll/index&bulan=' . $bulan . '&tahun=' . $tahun);
    exit;
}

header('Location: /?page=payroll/proses&bulan=' . $bulan . '&tahun=' . $tahun . '&mode=koreksi');
exit;
