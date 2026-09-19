<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/payroll.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';

requireAuth();
$user = currentUser();

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

$bulan = filter_input(INPUT_GET, 'bulan', FILTER_VALIDATE_INT) ?: 8;
$tahun = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT) ?: (int) date('Y');

$statusFilter = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW);
$statusFilter = in_array($statusFilter, ['Draft', 'Processed', 'Paid', 'Corrected'], true) ? $statusFilter : null;
$search = trim((string) filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW));
$periodeFormat = sprintf('%02d-%04d', $bulan, $tahun); // misal "08-2026"
$dataPayroll = fetchPenggajianByPeriode($pdo, $periodeFormat, $statusFilter, $search);

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
    <div>
        <h1 class="h3 mb-1">Penggajian</h1>
        <p class="text-secondary mb-0">Kelola proses payroll perusahaan</p>
    </div>
    <?php if ($user['role'] === 'ADMIN'): ?>
        <a href="/?page=payroll/proses" class="btn btn-primary">
            <i class="bi bi-gear-fill me-1"></i> Proses Payroll Baru
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

<?php $adaPayrollPaid = count(array_filter($dataPayroll, static fn(array $row): bool => $row['status'] === 'Paid')) > 0; ?>
<?php if ($user['role'] === 'ADMIN' && $adaPayrollPaid): ?>
    <form action="/?action=payroll/start-correction" method="POST" class="mb-4" onsubmit="return confirm('Payroll Paid akan disimpan sebagai histori Corrected dan dibuatkan revisi baru. Lanjutkan?');">
        <input type="hidden" name="bulan" value="<?= $bulan ?>">
        <input type="hidden" name="tahun" value="<?= $tahun ?>">
        <button type="submit" class="btn btn-outline-warning">
            <i class="bi bi-arrow-repeat me-1"></i> Buat Koreksi Payroll
        </button>
    </form>
<?php endif; ?>

<!-- UI untuk tombol ubah status per-record -->
<?php
/**
 * Transisi status yang boleh dilakukan ADMIN secara manual dari UI:
 *   Draft     → Processed (kunci hasil)
 *   Processed → Paid (setelah pembayaran)
 *   Processed → Draft  (batalkan kunci, kembali ke Draft untuk direvisi)
 * Catatan: Paid → Corrected TIDAK tersedia di sini, karena hanya boleh
 * dilakukan lewat fitur "Buat Koreksi Payroll" (ter-audit).
 */
$statusTransisi = [
    'Draft'     => ['Processed' => ['label' => 'Kunci (Processed)', 'icon' => 'bi-lock-fill', 'class' => 'btn-outline-primary', 'confirm' => 'Kunci payroll ini sebagai Processed? Payroll yang sudah Processed tidak dapat diubah bebas.']],
    'Processed' => [
        'Paid'  => ['label' => 'Tandai Paid', 'icon' => 'bi-cash-coin', 'class' => 'btn-outline-success', 'confirm' => 'Tandai payroll ini sebagai Paid? Pastikan pembayaran sudah benar-benar dilakukan.'],
        'Draft' => ['label' => 'Buka ke Draft', 'icon' => 'bi-unlock-fill', 'class' => 'btn-outline-secondary', 'confirm' => 'Kembalikan payroll ini ke Draft? Nominal tetap tersimpan tetapi dapat dihitung ulang.'],
    ],
];

/**
 * PEMIMPIN hanya melakukan APPROVAL (transisi maju):
 *   Draft     → Processed (approve hasil hitungan)
 *   Processed → Paid      (approve pembayaran)
 * Pemimpin TIDAK bisa revert (Processed → Draft) dan tidak mengubah nominal payroll.
 */
if ($user['role'] === 'PEMIMPIN') {
    $statusTransisi = [
        'Draft'     => ['Processed' => ['label' => 'Approve (Processed)', 'icon' => 'bi-check2-circle', 'class' => 'btn-outline-primary', 'confirm' => 'Setujui payroll ini sebagai Processed? Payroll yang sudah Processed tidak dapat diubah bebas.']],
        'Processed' => ['Paid'  => ['label' => 'Approve (Paid)', 'icon' => 'bi-check2-all', 'class' => 'btn-outline-success', 'confirm' => 'Setujui payroll ini sebagai Paid? Pastikan pembayaran sudah benar-benar dilakukan.']],
    ];
}
?>


<!-- Filter Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="payroll/index">
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
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua status</option>
                    <?php foreach (['Draft', 'Processed', 'Paid', 'Corrected'] as $status): ?>
                        <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $status ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label">Cari karyawan</label>
                <input type="search" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nama atau NIP">
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
                <thead class="table-light text-center">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">NIP</th>
                        <th class="px-4 py-3">Nama Karyawan</th>
                        <th class="px-4 py-3 text-center">Revisi</th>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3 text-end">Gaji Kotor</th>
                        <th class="px-4 py-3 text-end">PPh 21</th>
                        <th class="px-4 py-3 text-end">Gaji Bersih</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($dataPayroll) > 0): ?>
                        <?php $no = 1; foreach ($dataPayroll as $row): ?>
                            <tr>
                                <td class="px-4 py-3 text-center"><?= $no++ ?></td>
                                <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['nip'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 fw-medium"><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-center">R<?= (int) $row['revisi'] ?></td>
                                <td class="px-4 py-3 text-center"><span class="badge bg-info text-dark"><?= htmlspecialchars($row['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="px-4 py-3 text-end"><?= formatCurrency((float) $row['gaji_kotor']) ?></td>
                                <td class="px-4 py-3 text-end text-danger"><?= formatCurrency((float) $row['pph21']) ?></td>
                                <td class="px-4 py-3 text-end text-success fw-bold"><?= formatCurrency((float) $row['gaji_bersih']) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    $statusClass = ['Draft' => 'bg-warning text-dark', 'Processed' => 'bg-info text-dark', 'Paid' => 'bg-success', 'Corrected' => 'bg-secondary'];
                                    ?>
                                    <span class="badge <?= $statusClass[$row['status']] ?? 'bg-secondary' ?>"><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="/?page=payroll/detail&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Lihat Slip Gaji">
                                        <i class="bi bi-receipt"></i> <?= $row['status'] === 'Draft' ? 'Detail' : 'Slip' ?>
                                    </a>
                                    <?php if ($user['role'] === 'ADMIN' && $row['status'] === 'Draft'): ?>
                                        <a href="/?page=payroll/proses&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-sm btn-outline-warning mt-1" title="Hitung ulang Draft">
                                            <i class="bi bi-pencil-square"></i> Ubah Draft
                                        </a>
                                    <?php endif; ?>
                                    <?php if (in_array($user['role'], ['ADMIN', 'PEMIMPIN'], true) && isset($statusTransisi[$row['status']])): ?>
                                        <?php foreach ($statusTransisi[$row['status']] as $targetStatus => $btn): ?>
                                            <form action="/?action=payroll/change-status" method="POST" class="d-inline mt-1"
                                                  onsubmit="return confirm('<?= htmlspecialchars($btn['confirm'], ENT_QUOTES, 'UTF-8') ?>');">
                                                <input type="hidden" name="payroll_id" value="<?= (int) $row['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $targetStatus ?>">
                                                <input type="hidden" name="bulan" value="<?= $bulan ?>">
                                                <input type="hidden" name="tahun" value="<?= $tahun ?>">
                                                <button type="submit" class="btn btn-sm <?= $btn['class'] ?>" title="<?= htmlspecialchars($btn['label'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="bi <?= $btn['icon'] ?>"></i> <?= htmlspecialchars($btn['label'], ENT_QUOTES, 'UTF-8') ?>
                                                </button>
                                            </form>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-secondary">
                                <i class="bi bi-wallet2 fs-1 d-block mb-2"></i>
                                Belum ada data penggajian untuk periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
