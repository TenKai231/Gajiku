<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/karyawan.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';
require_once dirname(__DIR__, 2) . '/includes/golongan.php';

requireAuth();
$user = currentUser();

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();

$search = trim((string) ($_GET['search'] ?? ''));

// Manual logic fetch since we have a search param
$sql = 'SELECT k.*, j.nama_jabatan, g.nama_golongan
        FROM karyawan k
        LEFT JOIN jabatan j ON k.jabatan_id = j.id
        LEFT JOIN golongan g ON k.golongan_id = g.id';
$params = [];

if ($search !== '') {
    $sql .= ' WHERE (k.nama LIKE :search_nama OR k.nip LIKE :search_nip)';
    $params[':search_nama'] = "%$search%";
    $params[':search_nip'] = "%$search%";
}

$sql .= ' ORDER BY k.nip ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$karyawans = $stmt->fetchAll();

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Kelola Karyawan</h1>
    <?php if ($user['role'] === 'ADMIN'): ?>
        <a href="/?page=karyawan/create" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Tambah Karyawan
        </a>
    <?php endif; ?>
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

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/">
            <input type="hidden" name="page" value="karyawan/index">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Cari berdasarkan NIP atau Nama..." name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                <?php if ($search !== ''): ?>
                    <a href="/?page=karyawan/index" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">NIP</th>
                        <th class="px-4 py-3">Nama Lengkap</th>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3">Golongan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($karyawans) > 0): ?>
                        <?php foreach ($karyawans as $index => $k): ?>
                            <tr>
                                <td class="px-4 py-3"><?= $index + 1 ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) $k['nip'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 fw-medium"><?= htmlspecialchars((string) $k['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><span class="badge bg-info text-dark"><?= htmlspecialchars((string) $k['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="px-4 py-3"><span class="badge bg-secondary"><?= htmlspecialchars((string) $k['nama_golongan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($k['status'] === 'Aktif'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/?page=karyawan/detail&id=<?= $k['id'] ?>" class="btn btn-outline-info" title="Detail">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                        <?php if ($user['role'] === 'ADMIN'): ?>
                                            <a href="/?page=karyawan/edit&id=<?= $k['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="/?action=karyawan/delete" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus karyawan ini?');">
                                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                Tidak ada data karyawan yang ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
