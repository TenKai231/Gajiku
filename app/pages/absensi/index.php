<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/absensi.php';

requireAuth();
$user = currentUser();
if (!in_array($user['role'], ['ADMIN', 'HR'], true)) {
    http_response_code(403);
    die('Akses ditolak.');
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$rules = require dirname(__DIR__, 2) . '/config/rules.php';

$bulan = filter_input(INPUT_GET, 'bulan', FILTER_VALIDATE_INT) ?: 8;
$tahun = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT) ?: (int) date('Y');

// Limit queries to current month for performance and view logic
$absensiList = fetchAbsensiByPeriode($pdo, $bulan, $tahun);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$bulanList = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Kelola Absensi</h1>
    <?php if (in_array($user['role'], ['ADMIN', 'HR'], true)): ?>
        <a href="/?page=absensi/create" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Input Absensi
        </a>
    <?php endif; ?>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filter Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="absensi">
            <div class="col-md-3">
                <label for="bulan" class="form-label">Periode Bulan</label>
                <select class="form-select" id="bulan" name="bulan">
                    <?php foreach ($bulanList as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $bulan === $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="tahun" class="form-label">Tahun</label>
                <input type="number" class="form-control" id="tahun" name="tahun" value="<?= $tahun ?>" min="2020" max="2099">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">NIP</th>
                        <th class="px-4 py-3">Nama Karyawan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Jam Masuk</th>
                        <th class="px-4 py-3 text-center">Jam Pulang</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <?php if (in_array($user['role'], ['ADMIN', 'HR'], true)): ?>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($absensiList) > 0): ?>
                        <?php foreach ($absensiList as $absensi): ?>
                            <tr>
                                <td class="px-4 py-3"><?= date('d-m-Y', strtotime($absensi['tanggal'])) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) $absensi['nip'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 fw-medium"><?= htmlspecialchars((string) $absensi['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    $statusClass = [
                                        'Hadir' => 'success',
                                        'Sakit' => 'warning',
                                        'Izin' => 'info',
                                        'Alpha' => 'danger',
                                        'Cuti' => 'secondary'
                                    ];
                                    $badge = $statusClass[$absensi['status']] ?? 'light';
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= $absensi['status'] ?></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($absensi['status'] === 'Hadir' && $absensi['jam_masuk']): ?>
                                        <?php
                                            $jm = substr($absensi['jam_masuk'], 0, 5);
                                            $isLate = isTerlambat($jm, $rules['normal_entry_time']);
                                        ?>
                                        <span class="<?= $isLate ? 'text-danger fw-bold' : '' ?>" title="<?= $isLate ? 'Terlambat' : '' ?>">
                                            <?= $jm ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?= $absensi['jam_pulang'] ? substr($absensi['jam_pulang'], 0, 5) : '-' ?>
                                </td>
                                <td class="px-4 py-3 text-muted small">
                                    <?= htmlspecialchars((string) ($absensi['keterangan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <?php if (in_array($user['role'], ['ADMIN', 'HR'], true)): ?>
                                    <td class="px-4 py-3 text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/?page=absensi/edit&id=<?= $absensi['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="/?action=absensi/delete" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data absensi ini?');">
                                                <input type="hidden" name="id" value="<?= $absensi['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= in_array($user['role'], ['ADMIN', 'HR'], true) ? 8 : 7 ?>" class="text-center py-5 text-secondary">
                                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                Belum ada data absensi untuk periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>