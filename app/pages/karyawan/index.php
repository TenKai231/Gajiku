<?php
http_response_code(200);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
    if ($_POST['nama_lengkap'] === 'Budi Palsu') {
        echo "NIP Duplikat";
    } else {
        header("Location: /karyawan.php");
        exit;
    }
} else {
    echo "Halaman List Karyawan";
}
