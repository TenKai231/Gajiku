<?php
$user = currentUser();
$page = $_GET['page'] ?? 'dashboard';

// Helper for active link
function is_active($currentPage, $targetPage) {
    if (is_array($targetPage)) {
        foreach ($targetPage as $t) {
            if (strpos($currentPage, $t) === 0) return 'active';
        }
        return '';
    }
    return strpos($currentPage, $targetPage) === 0 ? 'active' : '';
}
?>
<!-- Sidebar -->
<div class="sidebar d-flex flex-column vh-100">
    <div class="p-3 text-center border-bottom border-light border-opacity-25 mb-3">
        <a href="/?page=dashboard" class="text-white text-decoration-none fs-4 fw-bold d-flex align-items-center justify-content-center">
            <i class="bi bi-cash-stack me-2"></i>Gajiku
        </a>
    </div>
    
    <div class="p-3 flex-grow-1 overflow-auto">
        <ul class="nav nav-pills flex-column mb-auto gap-1">
            <li class="nav-item mb-2">
                <a class="nav-link text-white <?= is_active($page, 'dashboard') ?>" href="/?page=dashboard">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>

            <!-- SECTION: MASTER DATA -->
            <li class="nav-item mt-3 mb-1">
                <small class="text-white text-opacity-50 text-uppercase fw-bold ps-3" style="font-size: 0.75rem; letter-spacing: 1px;">Master Data</small>
            </li>

            <?php if ($user['role'] === 'ADMIN'): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_active($page, 'karyawan') ?>" href="/?page=karyawan/index">
                    <i class="bi bi-people me-2"></i> Karyawan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_active($page, 'jabatan') ?>" href="/?page=jabatan/index">
                    <i class="bi bi-briefcase me-2"></i> Jabatan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_active($page, 'golongan') ?>" href="/?page=golongan/index">
                    <i class="bi bi-diagram-3 me-2"></i> Golongan
                </a>
            </li>
            
            
            <?php elseif ($user['role'] === 'HR'): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_active($page, 'karyawan') ?>" href="/?page=karyawan/index">
                    <i class="bi bi-people me-2"></i> Karyawan
                </a>
            </li>
            <?php elseif ($user['role'] === 'PEMIMPIN'): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_active($page, 'karyawan') ?>" href="/?page=karyawan/index">
                    <i class="bi bi-people me-2"></i> Karyawan
                </a>
            </li>
            <?php endif; ?>

            <!-- SECTION: TRANSAKSI -->
            <li class="nav-item mt-3 mb-1">
                <small class="text-white text-opacity-50 text-uppercase fw-bold ps-3" style="font-size: 0.75rem; letter-spacing: 1px;">Transaksi</small>
            </li>

            <?php if (in_array($user['role'], ['ADMIN', 'HR', 'PEMIMPIN'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_active($page, 'absensi') ?>" href="/?page=absensi/index">
                    <i class="bi bi-calendar-check me-2"></i> Absensi
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link text-white <?= is_active($page, 'penggajian') ?: is_active($page, 'payroll') ?>" href="/?page=payroll/index">
                    <i class="bi bi-wallet2 me-2"></i> Penggajian
                </a>
            </li>

            <!-- SECTION: LAPORAN -->
            <li class="nav-item mt-3 mb-1">
                <small class="text-white text-opacity-50 text-uppercase fw-bold ps-3" style="font-size: 0.75rem; letter-spacing: 1px;">Laporan</small>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white <?= is_active($page, 'laporan') ?>" href="/?page=laporan/index">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i> Payroll
                </a>
            </li>

            <!-- SECTION: ADMINISTRATION -->
            <?php if ($user['role'] === 'ADMIN'): ?>
            <li class="nav-item mt-3 mb-1">
                <small class="text-white text-opacity-50 text-uppercase fw-bold ps-3" style="font-size: 0.75rem; letter-spacing: 1px;">Administration</small>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white <?= is_active($page, 'users') ?>" href="/?page=users/index">
                    <i class="bi bi-person-gear me-2"></i> Users
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </div>
</div>

<!-- Main Content Area Wrapper (Closed in index.php) -->
<div class="main-content d-flex flex-column w-100 min-vh-100">
    
    <!-- Topbar -->
    <header class="shadow-sm px-4 py-3 d-flex justify-content-between align-items-center sticky-top" style="z-index: 1020;">
        <div class="d-flex align-items-center gap-3">
            <button type="button" id="sidebarToggle" onclick="toggleSidebar(event)" class="btn btn-outline-secondary btn-sm p-1 no-print" title="Sembunyikan/tampilkan menu" aria-label="Sembunyikan/tampilkan menu" aria-expanded="true">
                <i class="bi bi-list fs-5" id="sidebarToggleIcon" style="pointer-events: none;"></i>
            </button>
            <h5 class="mb-0 text-primary fw-bold d-none d-md-block">Sistem Informasi Penggajian</h5>
        </div>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <!-- Theme Toggle Button -->
            <button type="button" id="themeToggle" onclick="toggleGajikuTheme()" class="theme-toggle-btn btn btn-sm no-print" aria-label="Ganti mode gelap atau terang" aria-pressed="false" title="Ganti mode gelap/terang">
                <i class="bi bi-moon-stars fs-6" id="themeToggleIcon"></i>
            </button>

            <div class="user-badge d-flex align-items-center px-3 py-1 rounded-pill border">
                <span class="me-2 fw-medium"><i class="bi bi-person-circle text-primary fs-5"></i></span>
                <span class="badge bg-primary rounded-pill"><?= htmlspecialchars((string) ($user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <a class="btn btn-outline-danger btn-sm d-flex align-items-center px-3" href="/?action=auth/logout">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </header>