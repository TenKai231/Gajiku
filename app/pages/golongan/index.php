<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/golongan.php';

requireAuth();
$user = currentUser();
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$golongans = fetchAllGolongan($pdo);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Kelola Golongan</h1>
    <a href="/?page=golongan/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Tambah Golongan
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
                        <th class="px-4 py-3">Nama Golongan</th>
                        <th class="px-4 py-3 text-end">Uang Makan</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($golongans) > 0): ?>
                        <?php foreach ($golongans as $index => $golongan): ?>
                            <tr>
                                <td class="px-4 py-3"><?= $index + 1 ?></td>
                                <td class="px-4 py-3 fw-medium"><?= htmlspecialchars((string) $golongan['nama_golongan'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-end">Rp <?= number_format((float) $golongan['uang_makan'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-center">
                                    <a href="/?page=golongan/edit&id=<?= $golongan['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="/?action=golongan/delete" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus golongan ini?');">
                                        <input type="hidden" name="id" value="<?= $golongan['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-secondary">
                                Belum ada data golongan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>