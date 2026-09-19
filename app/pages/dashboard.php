<?php
declare(strict_types=1);

require_once dirname(__DIR__, 1) . '/includes/auth.php';
requireAuth();
$user = currentUser();

require_once dirname(__DIR__, 1) . '/config/database.php';
require_once dirname(__DIR__, 1) . '/includes/jabatan.php';
require_once dirname(__DIR__, 1) . '/includes/payroll.php';
$pdo = getPDO();

// Periode payroll acuan dashboard: pakai periode terbaru yang ada datanya
// agar konsisten dengan halaman Laporan (bukan hardcode bulan).
$periodeTerbaru = getPeriodeTerbaru($pdo);
$bulanIni = $periodeTerbaru ?? date('m-Y');
list($blnIni, $thnIni) = explode('-', $bulanIni);

// 1. Total Karyawan Aktif
$stmtKaryawan = $pdo->query("SELECT COUNT(*) FROM karyawan WHERE status = 'Aktif'");
$totalKaryawan = (int) $stmtKaryawan->fetchColumn();

// 2. Kehadiran Hari Ini (fallback ke tanggal absensi terakhir bila hari ini kosong,
//    supaya angka tidak 0 padahal data demo ada di bulan lalu)
$hariIni = date('Y-m-d');
$stmtHadir = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE tanggal = :tanggal AND status = 'Hadir'");
$stmtHadir->execute([':tanggal' => $hariIni]);
$totalHadirHariIni = (int) $stmtHadir->fetchColumn();
$tanggalHadirLabel = $hariIni;
if ($totalHadirHariIni === 0) {
    $tglTerakhir = $pdo->query("SELECT MAX(tanggal) FROM absensi")->fetchColumn();
    if ($tglTerakhir) {
        $stmtHadir->execute([':tanggal' => $tglTerakhir]);
        $totalHadirHariIni = (int) $stmtHadir->fetchColumn();
        $tanggalHadirLabel = (string) $tglTerakhir;
    }
}

// 3+4. Total Payroll (Kotor & Bersih) — pakai logika REKAP yang sama dengan
// halaman Laporan: hanya revisi aktif per karyawan, status Corrected dikecualikan.
// (Sebelumnya dashboard pakai SUM mentah semua baris sehingga histori Corrected
// ikut terhitung dobel dan angkanya lebih besar dari laporan.)
$rekapSum = fetchRekapSumByPeriode($pdo, $bulanIni);
$totalPayrollKotor = $rekapSum['total_kotor'];
$totalPayrollBersih = $rekapSum['total_bersih'];

// 5. Rekap Tren Payroll (rekap aktif saja, samakan dengan laporan)
$trenPayroll = fetchTrenPayrollRekap($pdo, 6);
$labelTren = [];
$dataTren = [];
foreach ($trenPayroll as $t) {
    $labelTren[] = $t['periode'];
    $dataTren[] = (float) $t['total_gaji'];
}

// 6. Status Payroll Bulan Ini (Untuk Donut Chart) — rekap aktif saja
$statusPayrollData = fetchStatusCountRekapByPeriode($pdo, $bulanIni);
$statusProcessed = $statusPayrollData['Processed'] ?? 0;
$statusPaid = $statusPayrollData['Paid'] ?? 0;

// 7. Rekap Kehadiran Bulan Ini (Untuk Bar Chart)
$stmtKehadiranChart = $pdo->prepare("
    SELECT status, COUNT(*) as total 
    FROM absensi 
    WHERE MONTH(tanggal) = :bulan AND YEAR(tanggal) = :tahun
    GROUP BY status
");
$stmtKehadiranChart->execute([':bulan' => $blnIni, ':tahun' => $thnIni]);
$rekapKehadiran = $stmtKehadiranChart->fetchAll(PDO::FETCH_KEY_PAIR);

// 8. Rekap Keterlambatan Bulan Ini (Untuk Bar Chart Keterlambatan)
$stmtTelat = $pdo->prepare("
    SELECT k.nama, COUNT(*) as total_telat
    FROM absensi a
    JOIN karyawan k ON a.karyawan_id = k.id
    WHERE MONTH(a.tanggal) = :bulan AND YEAR(a.tanggal) = :tahun AND a.status = 'Hadir' AND a.jam_masuk > '08:00:00'
    GROUP BY a.karyawan_id
    ORDER BY total_telat DESC
    LIMIT 5
");
$stmtTelat->execute([':bulan' => $blnIni, ':tahun' => $thnIni]);
$dataTelat = $stmtTelat->fetchAll(PDO::FETCH_ASSOC);
$labelTelat = array_column($dataTelat, 'nama');
$jumlahTelat = array_column($dataTelat, 'total_telat');

// 9. Payroll Terbaru (5 Terakhir)
$stmtTerbaru = $pdo->query("
    SELECT p.periode, p.gaji_bersih, p.status, k.nama, k.nip 
    FROM penggajian p
    JOIN karyawan k ON p.karyawan_id = k.id
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$payrollTerbaru = $stmtTerbaru->fetchAll(PDO::FETCH_ASSOC);

$bulanArr = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Overview sistem penggajian — periode <?= htmlspecialchars($bulanIni, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</div>

<!-- TOP KPI ROW -->
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <h6 class="card-title text-muted mb-2"><i class="bi bi-people-fill me-2 text-primary"></i> Karyawan</h6>
                <h3 class="fw-bold mb-0 text-dark"><?= $totalKaryawan ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <h6 class="card-title text-muted mb-2"><i class="bi bi-calendar-check-fill me-2 text-success"></i> Hadir</h6>
                <h3 class="fw-bold mb-0 text-dark"><?= $totalHadirHariIni ?></h3>
                <small class="text-muted"><?= htmlspecialchars($tanggalHadirLabel, ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <h6 class="card-title text-muted mb-2"><i class="bi bi-cash-stack me-2 text-warning"></i> Payroll Kotor</h6>
                <h3 class="fw-bold mb-0 text-dark fs-5"><?= formatCurrency($totalPayrollKotor) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <h6 class="card-title text-muted mb-2"><i class="bi bi-wallet2 me-2 text-info"></i> Payroll Bersih</h6>
                <h3 class="fw-bold mb-0 text-dark fs-5"><?= formatCurrency($totalPayrollBersih) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS ROW 1: TREN & STATUS -->
<div class="row g-4 mb-4">
    <!-- Tren Payroll (Line Chart) -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0 text-primary">Tren Payroll</h6>
            </div>
            <div class="card-body">
                <canvas id="trendChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Status Payroll (Donut Chart) -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0 text-primary">Status Payroll</h6>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <canvas id="statusChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS ROW 2: KEHADIRAN & KETERLAMBATAN -->
<div class="row g-4 mb-4">
    <!-- Kehadiran (Bar Chart) -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0 text-primary">Kehadiran Bulan Ini</h6>
            </div>
            <div class="card-body">
                <canvas id="kehadiranChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Keterlambatan (Bar Chart) -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0 text-primary">Top 5 Keterlambatan</h6>
            </div>
            <div class="card-body">
                <canvas id="keterlambatanChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- PAYROLL TERBARU -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-receipt me-2"></i> Payroll Terbaru</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 mt-2">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3">NIP</th>
                                <th class="px-4 py-3">Nama Karyawan</th>
                                <th class="px-4 py-3">Periode</th>
                                <th class="px-4 py-3 text-end">Gaji Bersih</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($payrollTerbaru) > 0): ?>
                                <?php foreach ($payrollTerbaru as $pt): ?>
                                    <tr>
                                        <td class="px-4 py-3"><?= htmlspecialchars($pt['nip'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 fw-medium"><?= htmlspecialchars($pt['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3"><span class="badge bg-light text-dark border"><?= $pt['periode'] ?></span></td>
                                        <td class="px-4 py-3 text-end text-success fw-bold"><?= formatCurrency((float) $pt['gaji_bersih']) ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <?php if ($pt['status'] === 'Paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Processed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-secondary">
                                        Belum ada histori payroll yang diproses.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CHART.JS LIBRARY -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    function isDark() {
        return document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function getChartTheme() {
        var dark = isDark();
        return {
            textColor: dark ? '#94A3B8' : '#64748B',
            gridColor: dark ? '#24344C' : '#E2E8F0',
            trendBorder: dark ? '#3B82C4' : '#1E3A5F',
            trendBg: dark ? 'rgba(59, 130, 246, 0.15)' : 'rgba(30, 58, 95, 0.1)',
            statusColors: dark ? ['#94A3B8', '#4ADE80'] : ['#6B7280', '#16A34A'],
            kehadiranColors: dark
                ? ['#4ADE80', '#FBBF24', '#60A5FA', '#F87171', '#94A3B8']
                : ['#16A34A', '#CA8A04', '#2563EB', '#DC2626', '#6B7280'],
            telatColor: dark ? '#F87171' : '#DC2626'
        };
    }

    var theme = getChartTheme();
    Chart.defaults.color = theme.textColor;
    Chart.defaults.borderColor = theme.gridColor;

    // 1. Tren Payroll (Line)
    var trendChart = new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($labelTren) ?>,
            datasets: [{
                label: 'Total Gaji Bersih (Rp)',
                data: <?= json_encode($dataTren) ?>,
                borderColor: theme.trendBorder,
                backgroundColor: theme.trendBg,
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { color: theme.gridColor }, ticks: { color: theme.textColor } },
                y: { grid: { color: theme.gridColor }, ticks: { color: theme.textColor } }
            }
        }
    });

    // 2. Status Payroll (Donut)
    var statusChart = new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Processed', 'Paid'],
            datasets: [{
                data: [<?= $statusProcessed ?>, <?= $statusPaid ?>],
                backgroundColor: theme.statusColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { labels: { color: theme.textColor } }
            }
        }
    });

    // 3. Kehadiran (Bar)
    var kehadiranChart = new Chart(document.getElementById('kehadiranChart'), {
        type: 'bar',
        data: {
            labels: ['Hadir', 'Sakit', 'Izin', 'Alpha', 'Cuti'],
            datasets: [{
                label: 'Total Kehadiran',
                data: [
                    <?= $rekapKehadiran['Hadir'] ?? 0 ?>,
                    <?= $rekapKehadiran['Sakit'] ?? 0 ?>,
                    <?= $rekapKehadiran['Izin'] ?? 0 ?>,
                    <?= $rekapKehadiran['Alpha'] ?? 0 ?>,
                    <?= $rekapKehadiran['Cuti'] ?? 0 ?>
                ],
                backgroundColor: theme.kehadiranColors
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: theme.gridColor }, ticks: { color: theme.textColor } },
                y: { grid: { color: theme.gridColor }, ticks: { color: theme.textColor } }
            }
        }
    });

    // 4. Keterlambatan (Bar)
    var keterlambatanChart = new Chart(document.getElementById('keterlambatanChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labelTelat) ?>,
            datasets: [{
                label: 'Total Keterlambatan',
                data: <?= json_encode($jumlahTelat) ?>,
                backgroundColor: theme.telatColor
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: theme.gridColor }, ticks: { color: theme.textColor } },
                y: { grid: { color: theme.gridColor }, ticks: { color: theme.textColor } }
            }
        }
    });

    // Sinkronisasi dinamis saat toggle tema diklik
    window.addEventListener('gajiku:themeChanged', function() {
        var newTheme = getChartTheme();
        Chart.defaults.color = newTheme.textColor;
        Chart.defaults.borderColor = newTheme.gridColor;

        // Update Tren
        trendChart.data.datasets[0].borderColor = newTheme.trendBorder;
        trendChart.data.datasets[0].backgroundColor = newTheme.trendBg;
        trendChart.options.scales.x.grid.color = newTheme.gridColor;
        trendChart.options.scales.x.ticks.color = newTheme.textColor;
        trendChart.options.scales.y.grid.color = newTheme.gridColor;
        trendChart.options.scales.y.ticks.color = newTheme.textColor;
        trendChart.update();

        // Update Status
        statusChart.data.datasets[0].backgroundColor = newTheme.statusColors;
        if (statusChart.options.plugins && statusChart.options.plugins.legend) {
            statusChart.options.plugins.legend.labels.color = newTheme.textColor;
        }
        statusChart.update();

        // Update Kehadiran
        kehadiranChart.data.datasets[0].backgroundColor = newTheme.kehadiranColors;
        kehadiranChart.options.scales.x.grid.color = newTheme.gridColor;
        kehadiranChart.options.scales.x.ticks.color = newTheme.textColor;
        kehadiranChart.options.scales.y.grid.color = newTheme.gridColor;
        kehadiranChart.options.scales.y.ticks.color = newTheme.textColor;
        kehadiranChart.update();

        // Update Keterlambatan
        keterlambatanChart.data.datasets[0].backgroundColor = newTheme.telatColor;
        keterlambatanChart.options.scales.x.grid.color = newTheme.gridColor;
        keterlambatanChart.options.scales.x.ticks.color = newTheme.textColor;
        keterlambatanChart.options.scales.y.grid.color = newTheme.gridColor;
        keterlambatanChart.options.scales.y.ticks.color = newTheme.textColor;
        keterlambatanChart.update();
    });
});
</script>