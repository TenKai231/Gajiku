<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/karyawan.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';
require_once dirname(__DIR__, 2) . '/includes/absensi.php';
require_once dirname(__DIR__, 2) . '/includes/pajak.php';

requireAuth();
$user = currentUser();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /?page=karyawan/index');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$karyawan = fetchKaryawanById($pdo, $id);

if (!$karyawan) {
    $_SESSION['error'] = 'Karyawan tidak ditemukan.';
    header('Location: /?page=karyawan/index');
    exit;
}

$bulan = filter_input(INPUT_GET, 'bulan', FILTER_VALIDATE_INT) ?: (int) date('n');
$tahun = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT) ?: (int) date('Y');

$rules = require dirname(__DIR__, 2) . '/config/rules.php';
$absensiList = fetchAbsensiKaryawanByPeriode($pdo, $id, $bulan, $tahun);

// Kalender & Agregasi
$totalDaysInMonth = (int) date('t', strtotime(sprintf('%04d-%02d-01', $tahun, $bulan)));
$firstDayOfWeek = (int) date('N', strtotime(sprintf('%04d-%02d-01', $tahun, $bulan))); // 1 = Senin, 7 = Minggu

$workDays = 0;
for ($d = 1; $d <= $totalDaysInMonth; $d++) {
    $dow = (int) date('N', strtotime(sprintf('%04d-%02d-%02d', $tahun, $bulan, $d)));
    if ($dow <= 5) {
        $workDays++;
    }
}

$absensiMap = [];
$hadir = 0;
$terlambat = 0;
$sakit = 0;
$izin = 0;
$cuti = 0;
$alpha = 0;

foreach ($absensiList as $absen) {
    $tgl = $absen['tanggal'];
    $isLate = false;
    if ($absen['status'] === 'Hadir') {
        $hadir++;
        if (!empty($absen['jam_masuk']) && isTerlambat(substr($absen['jam_masuk'], 0, 5), $rules['normal_entry_time'])) {
            $terlambat++;
            $isLate = true;
        }
    } elseif ($absen['status'] === 'Sakit') {
        $sakit++;
    } elseif ($absen['status'] === 'Izin') {
        $izin++;
    } elseif ($absen['status'] === 'Cuti') {
        $cuti++;
    } elseif ($absen['status'] === 'Alpha') {
        $alpha++;
    }
    $absensiMap[$tgl] = array_merge($absen, ['is_late' => $isLate]);
}

// Riwayat Penggajian Karyawan
$stmtPayroll = $pdo->prepare(
    'SELECT * FROM penggajian
     WHERE karyawan_id = :id
     ORDER BY periode DESC
     LIMIT 12'
);
$stmtPayroll->execute([':id' => $id]);
$payrollHistory = $stmtPayroll->fetchAll();

// Data Pajak & BPJS
$taxProfile = getTaxProfile($pdo, $id);
$stmtBpjs = $pdo->prepare('SELECT * FROM data_bpjs_karyawan WHERE karyawan_id = ?');
$stmtBpjs->execute([$id]);
$bpjsProfile = $stmtBpjs->fetch(PDO::FETCH_ASSOC);

$bulanList = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$tarifUangMakan = (float) ($karyawan['uang_makan'] ?? 0);
$uangMakanBruto = $hadir * $tarifUangMakan;
$potonganMakanTelat = $terlambat * $tarifUangMakan;
$uangMakanNeto = max(0, $uangMakanBruto - $potonganMakanTelat);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/?page=karyawan/index">Karyawan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard Kehadiran</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0"><?= htmlspecialchars($karyawan['nama'], ENT_QUOTES, 'UTF-8') ?> <span class="text-muted fs-5">(NIP: <?= htmlspecialchars($karyawan['nip'], ENT_QUOTES, 'UTF-8') ?>)</span></h1>
    </div>
    <div>
        <?php if ($user['role'] === 'ADMIN'): ?>
            <a href="/?page=karyawan/edit&id=<?= $karyawan['id'] ?>" class="btn btn-primary me-2">
                <i class="bi bi-pencil me-1"></i> Edit Profil
            </a>
        <?php endif; ?>
        <a href="/?page=karyawan/index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<!-- Profil Ringkas Karyawan -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <small class="text-muted d-block">Jabatan</small>
                <span class="badge bg-info text-dark fs-6 mt-1"><?= htmlspecialchars((string) ($karyawan['nama_jabatan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted d-block">Golongan</small>
                <span class="badge bg-secondary fs-6 mt-1"><?= htmlspecialchars((string) ($karyawan['nama_golongan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted d-block">Gaji Pokok</small>
                <span class="fw-bold fs-6"><?= formatCurrency((float) $karyawan['gaji_pokok']) ?></span>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted d-block">Status Karyawan</small>
                <?php if ($karyawan['status'] === 'Aktif'): ?>
                    <span class="badge bg-success mt-1">Aktif</span>
                <?php else: ?>
                    <span class="badge bg-danger mt-1">Nonaktif</span>
                <?php endif; ?>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted d-block">Uang Makan / Hari</small>
                <span class="fw-semibold"><?= formatCurrency($tarifUangMakan) ?></span>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted d-block">Tunjangan Golongan</small>
                <span class="fw-semibold text-success"><?= formatCurrency((float) ($karyawan['tunjangan'] ?? $karyawan['tunjangan_default'] ?? 0)) ?></span>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted d-block">Tanggal Masuk</small>
                <span><?= date('d M Y', strtotime($karyawan['tanggal_masuk'])) ?></span>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted d-block">Jenis Kelamin</small>
                <span><?= $karyawan['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Periode Absensi -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="karyawan/detail">
            <input type="hidden" name="id" value="<?= $karyawan['id'] ?>">
            <div class="col-md-4">
                <label for="bulan" class="form-label fw-semibold">Periode Bulan</label>
                <select class="form-select" id="bulan" name="bulan">
                    <?php foreach ($bulanList as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $bulan === $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="tahun" class="form-label fw-semibold">Tahun</label>
                <input type="number" class="form-control" id="tahun" name="tahun" value="<?= $tahun ?>" min="2020" max="2099">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- KPI Summary Attendance Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center p-3 h-100">
            <small class="text-muted text-uppercase fw-semibold">Hari Kerja</small>
            <div class="fs-3 fw-bold text-dark mt-2"><?= $workDays ?></div>
            <small class="text-muted">Senin - Jumat</small>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center p-3 h-100 border-start border-success border-4">
            <small class="text-success text-uppercase fw-semibold">Hadir</small>
            <div class="fs-3 fw-bold text-success mt-2"><?= $hadir ?></div>
            <small class="text-muted">Total hadir</small>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center p-3 h-100 border-start border-danger border-4">
            <small class="text-danger text-uppercase fw-semibold">Terlambat</small>
            <div class="fs-3 fw-bold text-danger mt-2"><?= $terlambat ?></div>
            <small class="text-muted">&gt; <?= htmlspecialchars($rules['normal_entry_time']) ?></small>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center p-3 h-100 border-start border-warning border-4">
            <small class="text-warning text-uppercase fw-semibold">Sakit</small>
            <div class="fs-3 fw-bold text-warning mt-2"><?= $sakit ?></div>
            <small class="text-muted">Hari sakit</small>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center p-3 h-100 border-start border-info border-4">
            <small class="text-info text-uppercase fw-semibold">Izin / Cuti</small>
            <div class="fs-3 fw-bold text-info mt-2"><?= $izin + $cuti ?></div>
            <small class="text-muted"><?= $izin ?> Izin, <?= $cuti ?> Cuti</small>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center p-3 h-100 border-start border-dark border-4">
            <small class="text-dark text-uppercase fw-semibold">Alpha</small>
            <div class="fs-3 fw-bold text-dark mt-2"><?= $alpha ?></div>
            <small class="text-muted">Tanpa keterangan</small>
        </div>
    </div>
</div>

<!-- Row: Kalender Absensi + Distribusi Kehadiran & Uang Makan -->
<div class="row g-4 mb-4">
    <!-- Kalender Bulanan -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fs-6 fw-bold">
                    <i class="bi bi-calendar3 me-2"></i>Kalender Kehadiran (<?= $bulanList[$bulan] ?> <?= $tahun ?>)
                </h5>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle mb-2">
                        <thead class="table-light">
                            <tr class="small text-muted">
                                <th style="width: 14.28%;">Sen</th>
                                <th style="width: 14.28%;">Sel</th>
                                <th style="width: 14.28%;">Rab</th>
                                <th style="width: 14.28%;">Kam</th>
                                <th style="width: 14.28%;">Jum</th>
                                <th style="width: 14.28%;" class="text-danger">Sab</th>
                                <th style="width: 14.28%;" class="text-danger">Min</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                $cellCount = 0;
                                // Offset awal bulan
                                for ($empty = 1; $empty < $firstDayOfWeek; $empty++):
                                    $cellCount++;
                                ?>
                                    <td class="bg-light text-muted p-2" style="height: 54px;"></td>
                                <?php endfor; ?>

                                <?php for ($day = 1; $day <= $totalDaysInMonth; $day++):
                                    $dateStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $day);
                                    $dow = (int) date('N', strtotime($dateStr));
                                    $isWeekend = ($dow >= 6);
                                    $record = $absensiMap[$dateStr] ?? null;
                                    $cellCount++;
                                ?>
                                    <td class="p-1 position-relative <?= $isWeekend ? 'bg-light' : '' ?>" style="height: 54px; min-width: 40px;">
                                        <div class="small fw-semibold <?= $isWeekend ? 'text-danger' : 'text-secondary' ?> mb-1">
                                            <?= $day ?>
                                        </div>
                                        <?php if ($record): ?>
                                            <?php if ($record['status'] === 'Hadir'): ?>
                                                <?php if ($record['is_late']): ?>
                                                    <span class="badge bg-danger" title="Hadir Terlambat (<?= htmlspecialchars(substr((string) $record['jam_masuk'], 0, 5)) ?>)">T</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success" title="Hadir Tepat Waktu (<?= htmlspecialchars(substr((string) $record['jam_masuk'], 0, 5)) ?>)">H</span>
                                                <?php endif; ?>
                                            <?php elseif ($record['status'] === 'Sakit'): ?>
                                                <span class="badge bg-warning text-dark" title="Sakit">S</span>
                                            <?php elseif ($record['status'] === 'Izin'): ?>
                                                <span class="badge bg-info text-dark" title="Izin">I</span>
                                            <?php elseif ($record['status'] === 'Cuti'): ?>
                                                <span class="badge bg-secondary" title="Cuti">C</span>
                                            <?php elseif ($record['status'] === 'Alpha'): ?>
                                                <span class="badge bg-dark" title="Alpha">A</span>
                                            <?php endif; ?>
                                        <?php elseif ($isWeekend): ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($cellCount % 7 === 0 && $day < $totalDaysInMonth): ?>
                                        </tr><tr>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php
                                while ($cellCount % 7 !== 0):
                                    $cellCount++;
                                ?>
                                    <td class="bg-light text-muted p-2" style="height: 54px;"></td>
                                <?php endwhile; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Legend -->
                <div class="d-flex flex-wrap gap-3 small text-muted justify-content-center pt-2">
                    <span><span class="badge bg-success me-1">H</span> Hadir Tepat Waktu</span>
                    <span><span class="badge bg-danger me-1">T</span> Terlambat</span>
                    <span><span class="badge bg-warning text-dark me-1">S</span> Sakit</span>
                    <span><span class="badge bg-info text-dark me-1">I</span> Izin</span>
                    <span><span class="badge bg-secondary me-1">C</span> Cuti</span>
                    <span><span class="badge bg-dark me-1">A</span> Alpha</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Distribusi Kehadiran & Uang Makan -->
    <div class="col-lg-5">
        <!-- Distribusi Kehadiran -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fs-6 fw-bold">
                    <i class="bi bi-bar-chart me-2"></i>Distribusi Kehadiran Bulan Ini
                </h5>
            </div>
            <div class="card-body">
                <?php
                $denom = max(1, $workDays);
                $pctHadir = round(($hadir / $denom) * 100);
                $pctTelat = round(($terlambat / $denom) * 100);
                $pctSakit = round(($sakit / $denom) * 100);
                $pctIzinCuti = round((($izin + $cuti) / $denom) * 100);
                $pctAlpha = round(($alpha / $denom) * 100);
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Hadir (<?= $hadir ?> hari)</span>
                        <span class="fw-semibold"><?= $pctHadir ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?= min(100, $pctHadir) ?>%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Terlambat (<?= $terlambat ?> hari)</span>
                        <span class="fw-semibold text-danger"><?= $pctTelat ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-danger" style="width: <?= min(100, $pctTelat) ?>%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Sakit (<?= $sakit ?> hari)</span>
                        <span class="fw-semibold text-warning"><?= $pctSakit ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: <?= min(100, $pctSakit) ?>%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Izin / Cuti (<?= $izin + $cuti ?> hari)</span>
                        <span class="fw-semibold text-info"><?= $pctIzinCuti ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: <?= min(100, $pctIzinCuti) ?>%"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Alpha (<?= $alpha ?> hari)</span>
                        <span class="fw-semibold text-dark"><?= $pctAlpha ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-dark" style="width: <?= min(100, $pctAlpha) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Uang Makan Estimasi -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fs-6 fw-bold">
                    <i class="bi bi-cash-coin me-2"></i>Estimasi Uang Makan Periode Ini
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted">Tarif Uang Makan / Hari</td>
                            <td class="text-end fw-semibold"><?= formatCurrency($tarifUangMakan) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Hari Berhak (Hadir)</td>
                            <td class="text-end fw-semibold"><?= $hadir ?> hari</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Uang Makan Bruto</td>
                            <td class="text-end fw-semibold"><?= formatCurrency($uangMakanBruto) ?></td>
                        </tr>
                        <tr>
                            <td class="text-danger">Potongan Terlambat (<?= $terlambat ?>x)</td>
                            <td class="text-end text-danger fw-semibold">- <?= formatCurrency($potonganMakanTelat) ?></td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-bold pt-2">Estimasi Uang Makan Neto</td>
                            <td class="text-end fw-bold text-success pt-2 fs-6"><?= formatCurrency($uangMakanNeto) ?></td>
                        </tr>
                    </tbody>
                </table>
                <small class="text-muted d-block mt-2 font-monospace" style="font-size: 0.75rem;">
                    * Keterlambatan memotong hak uang makan harian, tidak memotong gaji pokok.
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Absensi Periode Ini -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fs-6 fw-bold">
            <i class="bi bi-clock-history me-2"></i>Riwayat Absensi: <?= $bulanList[$bulan] ?> <?= $tahun ?>
        </h5>
        <span class="badge bg-secondary"><?= count($absensiList) ?> Catatan</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Jam Masuk</th>
                        <th class="px-4 py-3 text-center">Jam Pulang</th>
                        <th class="px-4 py-3 text-center">Terlambat</th>
                        <th class="px-4 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($absensiList) > 0): ?>
                        <?php foreach ($absensiList as $abs): ?>
                            <?php
                            $isLate = false;
                            if ($abs['status'] === 'Hadir' && !empty($abs['jam_masuk'])) {
                                $isLate = isTerlambat(substr($abs['jam_masuk'], 0, 5), $rules['normal_entry_time']);
                            }
                            ?>
                            <tr>
                                <td class="px-4 py-2 fw-medium"><?= date('d-m-Y', strtotime($abs['tanggal'])) ?></td>
                                <td class="px-4 py-2 text-center">
                                    <?php
                                    $statusBadges = [
                                        'Hadir' => 'success', 'Sakit' => 'warning', 'Izin' => 'info',
                                        'Alpha' => 'danger', 'Cuti' => 'secondary'
                                    ];
                                    $bg = $statusBadges[$abs['status']] ?? 'light';
                                    ?>
                                    <span class="badge bg-<?= $bg ?>"><?= $abs['status'] ?></span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <?= !empty($abs['jam_masuk']) ? htmlspecialchars(substr($abs['jam_masuk'], 0, 5)) : '-' ?>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <?= !empty($abs['jam_pulang']) ? htmlspecialchars(substr($abs['jam_pulang'], 0, 5)) : '-' ?>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <?php if ($isLate): ?>
                                        <span class="badge bg-danger">Ya</span>
                                    <?php elseif ($abs['status'] === 'Hadir'): ?>
                                        <span class="badge bg-light text-muted border">Tidak</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2 text-muted small">
                                    <?= htmlspecialchars((string) ($abs['keterangan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Belum ada catatan absensi untuk periode <?= $bulanList[$bulan] ?> <?= $tahun ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Riwayat Penggajian Karyawan -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fs-6 fw-bold">
            <i class="bi bi-wallet2 me-2"></i>Riwayat Penggajian Terakhir
        </h5>
        <span class="badge bg-secondary"><?= count($payrollHistory) ?> Periode</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-end">Gaji Pokok</th>
                        <th class="px-4 py-3 text-end">Uang Makan</th>
                        <th class="px-4 py-3 text-end">Tunjangan</th>
                        <th class="px-4 py-3 text-end">Potongan</th>
                        <th class="px-4 py-3 text-end">PPh 21</th>
                        <th class="px-4 py-3 text-end">Gaji Bersih</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payrollHistory) > 0): ?>
                        <?php foreach ($payrollHistory as $pay): ?>
                            <tr>
                                <td class="px-4 py-3 fw-bold font-monospace"><?= htmlspecialchars((string) $pay['periode']) ?></td>
                                <td class="px-4 py-3 text-end"><?= formatCurrency((float) $pay['gaji_pokok']) ?></td>
                                <td class="px-4 py-3 text-end"><?= formatCurrency((float) ($pay['total_uang_makan'] ?? 0)) ?></td>
                                <td class="px-4 py-3 text-end"><?= formatCurrency((float) ($pay['total_tunjangan'] ?? 0)) ?></td>
                                <td class="px-4 py-3 text-end text-danger"><?= formatCurrency((float) ($pay['total_potongan'] ?? 0)) ?></td>
                                <td class="px-4 py-3 text-end text-danger"><?= formatCurrency((float) ($pay['pph21'] ?? 0)) ?></td>
                                <td class="px-4 py-3 text-end fw-bold text-success"><?= formatCurrency((float) $pay['gaji_bersih']) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    $st = $pay['status'];
                                    $stClass = 'secondary';
                                    if ($st === 'Paid') $stClass = 'success';
                                    elseif ($st === 'Approved') $stClass = 'primary';
                                    elseif ($st === 'Processed') $stClass = 'info text-dark';
                                    elseif ($st === 'Draft') $stClass = 'warning text-dark';
                                    ?>
                                    <span class="badge bg-<?= $stClass ?>"><?= $st ?></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="/?page=payroll/detail&id=<?= $pay['id'] ?>" class="btn btn-sm btn-outline-primary" title="Lihat Slip">
                                        <i class="bi bi-file-earmark-text"></i> Slip
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                Belum ada riwayat penggajian untuk karyawan ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab / Accordion: Data Pajak & Data BPJS -->
<div class="row g-4 mb-4">
    <!-- Data Pajak (PPh 21) -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fs-6 fw-bold">
                    <i class="bi bi-receipt me-2"></i>Data Pajak (PPh 21)
                </h5>
            </div>
            <div class="card-body">
                <form action="/?action=pajak/store" method="post">
                    <input type="hidden" name="karyawan_id" value="<?= $karyawan['id'] ?>">
                    <div class="mb-3">
                        <label for="nik" class="form-label">NIK (Nomor Induk Kependudukan)</label>
                        <input type="text" class="form-control" id="nik" name="nik" required
                               value="<?= htmlspecialchars($taxProfile['nik'] ?? '', ENT_QUOTES) ?>"
                               <?= !in_array($user['role'], ['ADMIN', 'HR'], true) ? 'readonly' : '' ?>>
                    </div>
                    <div class="mb-3">
                        <label for="npwp" class="form-label">NPWP (Opsional)</label>
                        <input type="text" class="form-control" id="npwp" name="npwp"
                               value="<?= htmlspecialchars($taxProfile['npwp'] ?? '', ENT_QUOTES) ?>"
                               <?= !in_array($user['role'], ['ADMIN', 'HR'], true) ? 'readonly' : '' ?>>
                    </div>
                    <div class="mb-3">
                        <label for="status_ptkp" class="form-label">Status PTKP</label>
                        <?php if (in_array($user['role'], ['ADMIN', 'HR'], true)): ?>
                        <select class="form-select" id="status_ptkp" name="status_ptkp" required>
                            <option value="">Pilih Status PTKP...</option>
                            <?php
                            $ptkps = ['TK/0', 'TK/1', 'TK/2', 'TK/3', 'K/0', 'K/1', 'K/2', 'K/3'];
                            foreach ($ptkps as $ptkp):
                                $selected = ($taxProfile['status_ptkp'] ?? '') === $ptkp ? 'selected' : '';
                            ?>
                                <option value="<?= $ptkp ?>" <?= $selected ?>><?= $ptkp ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" class="form-control" readonly value="<?= htmlspecialchars($taxProfile['status_ptkp'] ?? '-', ENT_QUOTES) ?>">
                        <?php endif; ?>
                    </div>
                    <?php if ($taxProfile): ?>
                    <div class="mb-3">
                        <label class="form-label">Kategori TER Aktif</label>
                        <div>
                            <span class="badge bg-primary fs-6">Kategori <?= htmlspecialchars($taxProfile['kategori_ter']) ?></span>
                            <small class="text-muted ms-2">Otomatis dari Status PTKP</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array($user['role'], ['ADMIN', 'HR'], true)): ?>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <?= $taxProfile ? 'Update Data Pajak' : 'Simpan Data Pajak' ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Data BPJS -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100" id="bpjs-section">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fs-6 fw-bold">
                    <i class="bi bi-shield-check me-2"></i>Data BPJS
                </h5>
            </div>
            <div class="card-body">
                <form action="/?action=bpjs/store" method="post">
                    <input type="hidden" name="karyawan_id" value="<?= $karyawan['id'] ?>">

                    <div class="mb-3">
                        <label for="nomor_kesehatan" class="form-label">Nomor BPJS Kesehatan</label>
                        <input type="text" class="form-control" id="nomor_kesehatan" name="nomor_kesehatan"
                               value="<?= htmlspecialchars($bpjsProfile['nomor_kesehatan'] ?? '', ENT_QUOTES) ?>"
                               <?= !in_array($user['role'], ['ADMIN', 'HR'], true) ? 'readonly' : '' ?>>
                    </div>
                    <div class="mb-3">
                        <label for="status_kesehatan" class="form-label">Status BPJS Kesehatan</label>
                        <?php if (in_array($user['role'], ['ADMIN', 'HR'], true)): ?>
                        <select class="form-select" id="status_kesehatan" name="status_kesehatan">
                            <option value="AKTIF" <?= ($bpjsProfile['status_kesehatan'] ?? '') === 'AKTIF' ? 'selected' : '' ?>>Aktif</option>
                            <option value="TIDAK_AKTIF" <?= ($bpjsProfile['status_kesehatan'] ?? '') === 'TIDAK_AKTIF' ? 'selected' : '' ?>>Tidak Aktif</option>
                        </select>
                        <?php else: ?>
                        <input type="text" class="form-control" readonly value="<?= ($bpjsProfile['status_kesehatan'] ?? 'AKTIF') === 'AKTIF' ? 'Aktif' : 'Tidak Aktif' ?>">
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="nomor_ketenagakerjaan" class="form-label">Nomor BPJS Ketenagakerjaan</label>
                        <input type="text" class="form-control" id="nomor_ketenagakerjaan" name="nomor_ketenagakerjaan"
                               value="<?= htmlspecialchars($bpjsProfile['nomor_ketenagakerjaan'] ?? '', ENT_QUOTES) ?>"
                               <?= !in_array($user['role'], ['ADMIN', 'HR'], true) ? 'readonly' : '' ?>>
                    </div>
                    <div class="mb-3">
                        <label for="status_ketenagakerjaan" class="form-label">Status BPJS Ketenagakerjaan</label>
                        <?php if (in_array($user['role'], ['ADMIN', 'HR'], true)): ?>
                        <select class="form-select" id="status_ketenagakerjaan" name="status_ketenagakerjaan">
                            <option value="AKTIF" <?= ($bpjsProfile['status_ketenagakerjaan'] ?? '') === 'AKTIF' ? 'selected' : '' ?>>Aktif</option>
                            <option value="TIDAK_AKTIF" <?= ($bpjsProfile['status_ketenagakerjaan'] ?? '') === 'TIDAK_AKTIF' ? 'selected' : '' ?>>Tidak Aktif</option>
                        </select>
                        <?php else: ?>
                        <input type="text" class="form-control" readonly value="<?= ($bpjsProfile['status_ketenagakerjaan'] ?? 'AKTIF') === 'AKTIF' ? 'Aktif' : 'Tidak Aktif' ?>">
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="risk_level_jkk" class="form-label">Tingkat Risiko JKK</label>
                        <?php if (in_array($user['role'], ['ADMIN', 'HR'], true)): ?>
                        <select class="form-select" id="risk_level_jkk" name="risk_level_jkk">
                            <option value="">-- Tidak Terdaftar JKK --</option>
                            <?php
                            $riskLevels = [
                                'SANGAT_RENDAH' => 'Sangat Rendah (0.24%)',
                                'RENDAH' => 'Rendah (0.54%)',
                                'SEDANG' => 'Sedang (0.89%)',
                                'TINGGI' => 'Tinggi (1.27%)',
                                'SANGAT_TINGGI' => 'Sangat Tinggi (1.74%)'
                            ];
                            foreach ($riskLevels as $val => $label):
                                $selected = ($bpjsProfile['risk_level_jkk'] ?? '') === $val ? 'selected' : '';
                            ?>
                                <option value="<?= $val ?>" <?= $selected ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Menentukan persentase tanggungan JKK Perusahaan.</div>
                        <?php else: ?>
                        <input type="text" class="form-control" readonly value="<?= htmlspecialchars($bpjsProfile['risk_level_jkk'] ?? 'Tidak Terdaftar', ENT_QUOTES) ?>">
                        <?php endif; ?>
                    </div>

                    <?php if (in_array($user['role'], ['ADMIN', 'HR'], true)): ?>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <?= $bpjsProfile ? 'Update Data BPJS' : 'Simpan Data BPJS' ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
