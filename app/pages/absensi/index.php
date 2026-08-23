<?php
http_response_code(200);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
    if ($_POST['jam_masuk'] === '08:30') {
        echo "Tanggal Duplikat";
    } else {
        header("Location: /absensi.php");
        exit;
    }
} else {
    echo "Halaman List Absensi";
}
