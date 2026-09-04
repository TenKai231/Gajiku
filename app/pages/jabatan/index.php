<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$jabatans = fetchAllJabatan($pdo);

// Ambil flash message dari $_SESSION['success'] atau $_SESSION['error'] (bukan $_SESSION['flash'] format baru)
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Kelola Jabatan</h1>
            <a href="/?page=jabatan/create" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Tambah Jabatan
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3">No</th>
                                <th class="px-4 py-3">Nama Jabatan</th>
                                <th class="px-4 py-3 text-end">Gaji Pokok</th>
                                <th class="px-4 py-3 text-end">Tunjangan Default</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($jabatans) > 0): ?>
                                <?php foreach ($jabatans as $index => $jabatan): ?>
                                    <tr>
                                        <td class="px-4 py-3"><?= $index + 1 ?></td>
                                        <td class="px-4 py-3 fw-medium"><?= htmlspecialchars((string) $jabatan['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 text-end">Rp <?= number_format((float) $jabatan['gaji_pokok'], 0, ',', '.') ?></td>
                                        <td class="px-4 py-3 text-end">Rp <?= number_format((float) $jabatan['tunjangan_default'], 0, ',', '.') ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="/?page=jabatan/edit&id=<?= $jabatan['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form action="/?action=jabatan/delete" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus jabatan ini?');">
                                                <input type="hidden" name="id" value="<?= $jabatan['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-secondary">
                                        Belum ada data jabatan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
