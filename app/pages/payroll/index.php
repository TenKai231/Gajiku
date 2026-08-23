<?php
http_response_code(200);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'generate') {
    header("Location: /payroll.php");
    exit;
} else {
    echo "Halaman List Payroll";
}
