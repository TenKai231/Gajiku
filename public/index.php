<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/includes/auth.php';

requireAuth();

$user = currentUser();

// Tangani Flash Message
$flash = getFlashMessage();


// Routing Action Backend
$action = $_GET['action'] ?? null;
if ($action) {
    // Sanitasi action untuk mencegah directory traversal
    $action = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', (string)$action);
    $actionFile = dirname(__DIR__) . '/app/actions/' . $action . '.php';

    if (file_exists($actionFile)) {
        require $actionFile;
        exit;
    } else {
        http_response_code(404);
        die("Action file not found: {$action}");
    }
}

// Routing Halaman/Pages Frontend
$page = $_GET['page'] ?? 'dashboard';

// Sanitasi page untuk mencegah directory traversal
$page = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', (string)$page);
$pageFile = dirname(__DIR__) . '/app/pages/' . $page . '.php';

// Cek apakah ada file index.php di dalam folder (jika $page merujuk ke direktori)
if (!file_exists($pageFile)) {
    $pageDirFile = dirname(__DIR__) . '/app/pages/' . $page . '/index.php';
    if (file_exists($pageDirFile)) {
        $pageFile = $pageDirFile;
    }
}

// Halaman cetak khusus (mis. /?page=laporan/print) dirender standalone
// tanpa layout utama (tanpa sidebar & topbar) agar hasil PDF murni konten dokumen.
$isStandalonePrint = (bool) preg_match('#(^|/)(print|cetak)(/|$)#', $page);
if ($isStandalonePrint && file_exists($pageFile)) {
    require $pageFile;
    exit;
}

// Tampilkan header
require dirname(__DIR__) . '/app/includes/header.php';
require dirname(__DIR__) . '/app/includes/navbar.php';

echo '<main class="container-fluid p-4">';

// Flash message (Alert) rendering if it exists in session
if ($flash) {
    $alertType = $flash['type'] === 'error' ? 'danger' : 'success';
    echo '<div class="alert alert-' . $alertType . ' alert-dismissible fade show mb-4" role="alert">';
    echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Generate Breadcrumbs
echo '<nav aria-label="breadcrumb" class="mb-4">';
echo '<ol class="breadcrumb bg-white p-3 rounded shadow-sm border mb-0">';
echo '<li class="breadcrumb-item"><a href="/?page=dashboard" class="text-decoration-none fw-medium"><i class="bi bi-house-door"></i> Home</a></li>';

$pageParts = explode('/', $page);
if (count($pageParts) > 1 && end($pageParts) === 'index') {
    array_pop($pageParts); // Remove 'index' from breadcrumb path
}

$pathSoFar = '';
foreach ($pageParts as $index => $part) {
    if (empty($part) || $part === 'dashboard') continue;
    
    $pathForLink = implode('/', array_slice($pageParts, 0, $index + 1));
    if (file_exists(dirname(__DIR__) . '/app/pages/' . $pathForLink . '/index.php')) {
        $pathForLink .= '/index';
    }
    
    $isLast = ($index === count($pageParts) - 1);
    $name = ucwords(str_replace(['_', '-'], ' ', $part));
    
    if ($isLast) {
        echo '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($name) . '</li>';
    } else {
        echo '<li class="breadcrumb-item"><a href="/?page=' . urlencode($pathForLink) . '" class="text-decoration-none fw-medium">' . htmlspecialchars($name) . '</a></li>';
    }
}
echo '</ol>';
echo '</nav>';

if (file_exists($pageFile)) {
    require $pageFile;
} else {
    echo '<div class="alert alert-warning text-center my-5">
            <h1 class="display-1"><i class="bi bi-exclamation-triangle"></i></h1>
            <h2>Halaman tidak ditemukan (404)</h2>
            <p>Halaman yang Anda cari tidak tersedia atau sedang dalam pengembangan.</p>
            <a href="/?page=dashboard" class="btn btn-primary mt-3"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
          </div>';
}

echo '</main>';

echo '</div>'; // close .main-content
echo '</div>'; // close .app-wrapper

// Tampilkan footer
require dirname(__DIR__) . '/app/includes/footer.php';
