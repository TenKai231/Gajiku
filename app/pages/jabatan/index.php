<?php
// Ini adalah file penampung sementara karena belum ada isinya.
// Nantinya UI list jabatan akan ditaruh di sini.
http_response_code(200);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
    // Validasi dummy untuk test Bruno
    if (empty($_POST['nama_jabatan']) || (int)$_POST['gaji_pokok'] < 0) {
        // Validasi gagal, tampilkan halaman lagi (200)
        echo "Validasi Error";
    } else {
        // Berhasil, redirect
        header("Location: /jabatan.php");
        exit;
    }
} else {
    echo "Halaman List Jabatan";
}
