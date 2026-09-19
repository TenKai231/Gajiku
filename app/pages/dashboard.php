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
        <h1 class="page-title mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Overview sistem penggajian — periode <?= htmlspecialchars($bulanIni, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</div>

<!-- TOP KPI ROW -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="metric-label">Karyawan Aktif</span>
                    <i class="bi bi-people text-primary fs-5"></i>
                </div>
                <div class="metric-number mb-1"><?= $totalKaryawan ?></div>
                <div class="metric-sublabel">Total karyawan terdaftar</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="metric-label">Kehadiran Hari Ini</span>
                    <i class="bi bi-calendar-check text-success fs-5"></i>
                </div>
                <div class="metric-number mb-1"><?= $totalHadirHariIni ?></div>
                <div class="metric-sublabel"><?= htmlspecialchars($tanggalHadirLabel, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="metric-label">Payroll Kotor</span>
                    <i class="bi bi-cash-stack text-warning fs-5"></i>
                </div>
                <div class="metric-number mb-1 fs-5"><?= formatCurrency($totalPayrollKotor) ?></div>
                <div class="metric-sublabel">Periode <?= htmlspecialchars($bulanIni, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="metric-label">Payroll Bersih</span>
                    <i class="bi bi-wallet2 text-info fs-5"></i>
                </div>
                <div class="metric-number mb-1 fs-5 text-success"><?= formatCurrency($totalPayrollBersih) ?></div>
                <div class="metric-sublabel">Take home pay total</div>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS ROW 1: TREN & STATUS -->
<div class="row g-4 mb-4">
    <!-- Tren Payroll (Line Chart) -->
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header pt-3 pb-3">
                <h6 class="card-title fw-semibold">Tren Payroll</h6>
            </div>
            <div class="card-body">
                <canvas id="trendChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Status Payroll (Donut Chart) -->
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header pt-3 pb-3">
                <h6 class="card-title fw-semibold">Status Payroll</h6>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center" style="min-height: 280px;">
                <?php if ($statusProcessed > 0 || $statusPaid > 0): ?>
                    <canvas id="statusChart" style="max-height: 240px;"></canvas>
                <?php else: ?>
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-pie-chart fs-1 d-block mb-2 text-muted opacity-75"></i>
                        <p class="mb-0 fw-medium">Belum ada data status payroll untuk periode ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS ROW 2: KEHADIRAN & KETERLAMBATAN -->
<div class="row g-4 mb-4">
    <!-- Kehadiran (Bar Chart) -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header pt-3 pb-3">
                <h6 class="card-title fw-semibold">Kehadiran Bulan Ini</h6>
            </div>
            <div class="card-body">
                <canvas id="kehadiranChart" style="max-height: 260px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Keterlambatan (Bar Chart) -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header pt-3 pb-3">
                <h6 class="card-title fw-semibold">Top 5 Keterlambatan</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 260px;">
                <?php if (count($dataTelat) > 0): ?>
                    <canvas id="keterlambatanChart" style="max-height: 260px; width: 100%;"></canvas>
                <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-secondary text-center">
                        <i class="bi bi-clock-check fs-1 text-success mb-2 opacity-75"></i>
                        <p class="mb-1 fw-medium text-primary">Belum ada data keterlambatan</p>
                        <small class="text-muted">Semua absensi hadir tercatat tepat waktu (sebelum 08:00 WIB).</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- PAYROLL TERBARU -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pt-3 pb-3">
                <h6 class="card-title fw-semibold"><i class="bi bi-receipt me-2"></i> Payroll Terbaru</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">NIP</th>
                                <th class="px-4 py-3">Nama Karyawan</th>
                                <th class="px-4 py-3 text-center">Periode</th>
                                <th class="px-4 py-3 text-end">Gaji Bersih</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($payrollTerbaru) > 0): ?>
                                <?php foreach ($payrollTerbaru as $pt): ?>
                                    <tr>
                                        <td class="px-4 py-3 nip-code text-secondary"><?= htmlspecialchars($pt['nip'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 fw-medium"><?= htmlspecialchars($pt['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 text-center"><span class="badge bg-light text-dark border"><?= $pt['periode'] ?></span></td>
                                        <td class="px-4 py-3 text-end text-success fw-semibold tabular-numbers"><?= formatCurrency((float) $pt['gaji_bersih']) ?></td>
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
                                    <td colspan="5" class="text-center py-5 text-secondary">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
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
            trendBg: dark ? 'rgba(59, 130, 246, 0.15)' : 'rgba(30, 58, 95, 0.08)',
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
    var trendEl = document.getElementById('trendChart');
    var trendChart = null;
    if (trendEl) {
        trendChart = new Chart(trendEl, {
            type: 'line',
            data: {
                labels: <?= json_encode($labelTren) ?>,
                datasets: [{
                    label: 'Total Gaji Bersih',
                    data: <?= json_encode($dataTren) ?>,
                    borderColor: theme.trendBorder,
                    backgroundColor: theme.trendBg,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.25,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var val = ctx.parsed.y || 0;
                                return ' Gaji Bersih: Rp ' + new Intl.NumberFormat('id-ID').format(val);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { color: theme.gridColor }, ticks: { color: theme.textColor } },
                    y: {
                        grid: { color: theme.gridColor },
                        ticks: {
                            color: theme.textColor,
                            callback: function(val) {
                                if (val >= 1000000) {
                                    return 'Rp ' + (val / 1000000).toLocaleString('id-ID') + ' jt';
                                }
                                return 'Rp ' + Number(val).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Status Payroll (Donut)
    var statusEl = document.getElementById('statusChart');
    var statusChart = null;
    if (statusEl) {
        statusChart = new Chart(statusEl, {
            type: 'doughnut',
            data: {
                labels: ['Processed: <?= (int)$statusProcessed ?>', 'Paid: <?= (int)$statusPaid ?>'],
                datasets: [{
                    data: [<?= (int)$statusProcessed ?>, <?= (int)$statusPaid ?>],
                    backgroundColor: theme.statusColors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: theme.textColor,
                            boxWidth: 12,
                            padding: 14
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var val = ctx.parsed || 0;
                                return ' ' + ctx.label.split(':')[0] + ': ' + val + ' karyawan';
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Kehadiran (Bar)
    var kehadiranEl = document.getElementById('kehadiranChart');
    var kehadiranChart = null;
    if (kehadiranEl) {
        kehadiranChart = new Chart(kehadiranEl, {
            type: 'bar',
            data: {
                labels: ['Hadir', 'Sakit', 'Izin', 'Alpha', 'Cuti'],
                datasets: [{
                    label: 'Kehadiran',
                    data: [
                        <?= (int)($rekapKehadiran['Hadir'] ?? 0) ?>,
                        <?= (int)($rekapKehadiran['Sakit'] ?? 0) ?>,
                        <?= (int)($rekapKehadiran['Izin'] ?? 0) ?>,
                        <?= (int)($rekapKehadiran['Alpha'] ?? 0) ?>,
                        <?= (int)($rekapKehadiran['Cuti'] ?? 0) ?>
                    ],
                    backgroundColor: theme.kehadiranColors,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.label + ': ' + (ctx.parsed.y || 0) + ' hari';
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { color: theme.gridColor }, ticks: { color: theme.textColor } },
                    y: {
                        grid: { color: theme.gridColor },
                        ticks: {
                            color: theme.textColor,
                            stepSize: 1,
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // 4. Keterlambatan (Bar)
    var telatEl = document.getElementById('keterlambatanChart');
    var keterlambatanChart = null;
    <?php if (count($dataTelat) > 0): ?>
    if (telatEl) {
        keterlambatanChart = new Chart(telatEl, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labelTelat) ?>,
                datasets: [{
                    label: 'Keterlambatan',
                    data: <?= json_encode($jumlahTelat) ?>,
                    backgroundColor: theme.telatColor,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' Terlambat: ' + (ctx.parsed.x || 0) + 'x';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: theme.gridColor },
                        ticks: {
                            color: theme.textColor,
                            stepSize: 1,
                            precision: 0,
                            callback: function(v) { return v + 'x'; }
                        }
                    },
                    y: { grid: { color: theme.gridColor }, ticks: { color: theme.textColor } }
                }
            }
        });
    }
    <?php endif; ?>

    // Sinkronisasi dinamis saat toggle tema diklik
    window.addEventListener('gajiku:themeChanged', function() {
        var newTheme = getChartTheme();
        Chart.defaults.color = newTheme.textColor;
        Chart.defaults.borderColor = newTheme.gridColor;

        if (trendChart) {
            trendChart.data.datasets[0].borderColor = newTheme.trendBorder;
            trendChart.data.datasets[0].backgroundColor = newTheme.trendBg;
            trendChart.options.scales.x.grid.color = newTheme.gridColor;
            trendChart.options.scales.x.ticks.color = newTheme.textColor;
            trendChart.options.scales.y.grid.color = newTheme.gridColor;
            trendChart.options.scales.y.ticks.color = newTheme.textColor;
            trendChart.update();
        }

        if (statusChart) {
            statusChart.data.datasets[0].backgroundColor = newTheme.statusColors;
            if (statusChart.options.plugins && statusChart.options.plugins.legend) {
                statusChart.options.plugins.legend.labels.color = newTheme.textColor;
            }
            statusChart.update();
        }

        if (kehadiranChart) {
            kehadiranChart.data.datasets[0].backgroundColor = newTheme.kehadiranColors;
            kehadiranChart.options.scales.x.grid.color = newTheme.gridColor;
            kehadiranChart.options.scales.x.ticks.color = newTheme.textColor;
            kehadiranChart.options.scales.y.grid.color = newTheme.gridColor;
            kehadiranChart.options.scales.y.ticks.color = newTheme.textColor;
            kehadiranChart.update();
        }

        if (keterlambatanChart) {
            keterlambatanChart.data.datasets[0].backgroundColor = newTheme.telatColor;
            keterlambatanChart.options.scales.x.grid.color = newTheme.gridColor;
            keterlambatanChart.options.scales.x.ticks.color = newTheme.textColor;
            keterlambatanChart.options.scales.y.grid.color = newTheme.gridColor;
            keterlambatanChart.options.scales.y.ticks.color = newTheme.textColor;
            keterlambatanChart.update();
        }
    });
});
</script>