<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/database.php';

$results = [];

// Test 1: Koneksi PDO
try {
    $pdo = getPDO();
    $results[] = ['status' => 'OK', 'pesan' => 'Koneksi PDO berhasil'];
} catch (PDOException $e) {
    $results[] = ['status' => 'GAGAL', 'pesan' => 'Koneksi PDO gagal: ' . $e->getMessage()];
    $pdo = null;
}

// Test 2: Versi database
if ($pdo !== null) {
    try {
        $versi = $pdo->query('SELECT VERSION() AS v')->fetchColumn();
        $results[] = ['status' => 'OK', 'pesan' => 'Versi database: ' . $versi];
    } catch (PDOException $e) {
        $results[] = ['status' => 'GAGAL', 'pesan' => 'Gagal ambil versi: ' . $e->getMessage()];
    }
}

// Test 3: Cek semua tabel
if ($pdo !== null) {
    $tabelWajib = ['users', 'jabatan', 'karyawan', 'absensi', 'penggajian'];
    try {
        $tabelAda = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $missing  = array_diff($tabelWajib, $tabelAda);

        if (empty($missing)) {
            $results[] = ['status' => 'OK', 'pesan' => 'Semua tabel ditemukan: ' . implode(', ', $tabelWajib)];
        } else {
            $results[] = ['status' => 'GAGAL', 'pesan' => 'Tabel tidak ditemukan: ' . implode(', ', $missing)];
        }
    } catch (PDOException $e) {
        $results[] = ['status' => 'GAGAL', 'pesan' => 'Gagal cek tabel: ' . $e->getMessage()];
    }
}

// Test 4: Query data users
if ($pdo !== null) {
    try {
        $stmt  = $pdo->query("SELECT COUNT(*) FROM users");
        $total = (int) $stmt->fetchColumn();
        $results[] = ['status' => 'OK', 'pesan' => "Tabel users: {$total} data ditemukan"];
    } catch (PDOException $e) {
        $results[] = ['status' => 'GAGAL', 'pesan' => 'Gagal query users: ' . $e->getMessage()];
    }
}

// Test 5: Prepared statement
if ($pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT username, role FROM users WHERE role = :role");
        $stmt->execute([':role' => 'ADMIN']);
        $user = $stmt->fetch();
        if ($user) {
            $results[] = ['status' => 'OK', 'pesan' => "Prepared statement OK — user admin: {$user['username']} ({$user['role']})"];
        } else {
            $results[] = ['status' => 'GAGAL', 'pesan' => 'Prepared statement OK tapi user admin tidak ditemukan'];
        }
    } catch (PDOException $e) {
        $results[] = ['status' => 'GAGAL', 'pesan' => 'Prepared statement gagal: ' . $e->getMessage()];
    }
}

$semua_ok = !in_array('GAGAL', array_column($results, 'status'));

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Koneksi Database</title>
    <style>
        body { font-family: monospace; max-width: 700px; margin: 40px auto; padding: 0 20px; background: #0d1117; color: #c9d1d9; }
        h1 { color: #58a6ff; border-bottom: 1px solid #30363d; padding-bottom: 10px; }
        .item { padding: 10px 14px; margin: 8px 0; border-radius: 6px; display: flex; gap: 12px; align-items: flex-start; }
        .ok   { background: #0d2a1a; border-left: 4px solid #2ea043; }
        .fail { background: #2d1217; border-left: 4px solid #f85149; }
        .badge { font-size: 11px; font-weight: bold; padding: 2px 8px; border-radius: 4px; white-space: nowrap; margin-top: 1px; }
        .badge-ok   { background: #2ea043; color: #fff; }
        .badge-fail { background: #f85149; color: #fff; }
        .summary { margin-top: 24px; padding: 14px 18px; border-radius: 8px; font-size: 15px; font-weight: bold; }
        .summary-ok   { background: #0d2a1a; color: #3fb950; border: 1px solid #2ea043; }
        .summary-fail { background: #2d1217; color: #f85149; border: 1px solid #f85149; }
        .pesan { line-height: 1.5; }
    </style>
</head>
<body>
    <h1>🔌 Test Koneksi Database</h1>

    <?php foreach ($results as $r): ?>
        <?php $ok = $r['status'] === 'OK'; ?>
        <div class="item <?= $ok ? 'ok' : 'fail' ?>">
            <span class="badge <?= $ok ? 'badge-ok' : 'badge-fail' ?>"><?= $r['status'] ?></span>
            <span class="pesan"><?= htmlspecialchars($r['pesan']) ?></span>
        </div>
    <?php endforeach; ?>

    <div class="summary <?= $semua_ok ? 'summary-ok' : 'summary-fail' ?>">
        <?= $semua_ok
            ? '✅ Semua test lulus — database siap digunakan.'
            : '❌ Ada test yang gagal — periksa konfigurasi .env dan pastikan container DB berjalan.' ?>
    </div>
</body>
</html>
