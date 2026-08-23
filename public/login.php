<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/database.php';
require_once dirname(__DIR__) . '/app/includes/auth.php';

if (isAuthenticated()) {
    redirect('/index.php');
}

$errors = [];
$username = '';
$logoutMessage = isset($_GET['logout']) ? 'Sesi berhasil diakhiri.' : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = 'Username dan password wajib diisi.';
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare(
                'SELECT id, username, password, role
                 FROM users
                 WHERE username = :username
                 LIMIT 1'
            );
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user === false || !password_verify($password, (string) $user['password'])) {
                $errors[] = 'Username atau password salah.';
            } else {
                loginUser($user);
                redirect('/index.php');
            }
        } catch (PDOException) {
            $errors[] = 'Koneksi database gagal. Periksa konfigurasi lalu coba lagi.';
        }
    }
}

http_response_code(200);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — Sistem Informasi Penggajian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-lg-5">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-2">Masuk ke Gajiku</h1>
                            <p class="text-secondary mb-0">Gunakan akun internal untuk mengakses sistem penggajian.</p>
                        </div>

                        <?php if ($logoutMessage !== null): ?>
                            <div class="alert alert-success" role="alert">
                                <?= htmlspecialchars($logoutMessage, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($errors !== []): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($errors[0], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="username"
                                    name="username"
                                    value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                                    autocomplete="username"
                                    required
                                >
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    autocomplete="current-password"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>

                        <div class="mt-4 small text-secondary">
                            <div>Demo login setelah impor `database/demo_users.sql`:</div>
                            <div>`admin` / `admin12345`</div>
                            <div>`hrd` / `hrd12345`</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
