<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAuth();
$user = currentUser();

// Only ADMIN can access User Management
if ($user['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

// Fetch all users
$stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();


?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Kelola Users</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-plus-circle me-1"></i> Tambah User
    </button>
</div>



<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Username</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Created At</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $index => $u): ?>
                            <tr>
                                <td class="px-4 py-3"><?= $index + 1 ?></td>
                                <td class="px-4 py-3 fw-medium"><?= htmlspecialchars((string)$u['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3">
                                    <span class="badge <?= $u['role'] === 'ADMIN' ? 'bg-primary' : 'bg-info text-dark' ?>">
                                        <?= htmlspecialchars((string)$u['role'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted"><?= date('d M Y H:i', strtotime($u['created_at'])) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>" title="Edit">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    
                                    <?php if ($u['id'] !== $user['id']): // Prevent self-deletion ?>
                                    <form action="/?action=users/delete" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan.');">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary disabled" title="Anda tidak dapat menghapus akun Anda sendiri">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit Modal for this User -->
                            <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?= $u['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="/?action=users/update" method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editUserModalLabel<?= $u['id'] ?>">Edit User</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Username</label>
                                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars((string)$u['username'], ENT_QUOTES, 'UTF-8') ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Role</label>
                                                    <select class="form-select" name="role" required>
                                                        <option value="ADMIN" <?= $u['role'] === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                                                        <option value="HR" <?= $u['role'] === 'HR' ? 'selected' : '' ?>>HR</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Password Baru (Opsional)</label>
                                                    <input type="password" class="form-control" name="password" placeholder="Kosongkan jika tidak ingin mengubah password">
                                                    <div class="form-text">Isi hanya jika Anda ingin me-reset password user ini.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-secondary">
                                Belum ada user terdaftar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/?action=users/store" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="">Pilih Role...</option>
                            <option value="ADMIN">ADMIN</option>
                            <option value="HR">HR</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
