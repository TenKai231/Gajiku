<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/golongan.php';
requireAuth();

if ($_SESSION['user']['role'] !== 'ADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /?page=golongan/index');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$golongan = fetchGolonganById($pdo, $id);

if (!$golongan) {
    $_SESSION['error'] = 'Golongan tidak ditemukan.';
    header('Location: /?page=golongan/index');
    exit;
}

$standardList = getStandardGolonganList();
$allGolongan = fetchAllGolongan($pdo);
$existingOther = [];
foreach ($allGolongan as $g) {
    if ((int) $g['id'] !== $id) {
        $existingOther[] = $g['nama_golongan'];
    }
}

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Golongan</h1>
    <a href="/?page=golongan/index" class="btn btn-outline-secondary">Kembali</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="/?action=golongan/update" method="POST">
            <input type="hidden" name="id" value="<?= $golongan['id'] ?>">

            <div class="mb-3">
                <label for="nama_golongan" class="form-label">Tingkat Golongan Perusahaan <span class="text-danger">*</span></label>
                <select class="form-select" id="nama_golongan" name="nama_golongan" required onchange="applyStandardValues(this)">
                    <option value="">-- Pilih Tingkat Golongan Standar --</option>
                    <?php foreach ($standardList as $nama => $cfg): ?>
                        <?php
                        $isSelected = ($golongan['nama_golongan'] === $nama);
                        $isTakenByOther = in_array($nama, $existingOther, true);
                        ?>
                        <option value="<?= htmlspecialchars($nama) ?>"
                                data-uang-makan="<?= $cfg['uang_makan'] ?>"
                                data-tunjangan="<?= $cfg['tunjangan'] ?>"
                                <?= $isSelected ? 'selected' : '' ?>
                                <?= ($isTakenByOther && !$isSelected) ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($nama) ?> <?= ($isTakenByOther && !$isSelected) ? '(Sudah dipakai golongan lain)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if (!array_key_exists($golongan['nama_golongan'], $standardList)): ?>
                        <option value="<?= htmlspecialchars((string) $golongan['nama_golongan']) ?>" selected>
                            <?= htmlspecialchars((string) $golongan['nama_golongan']) ?> (Non-standar)
                        </option>
                    <?php endif; ?>
                </select>
                <div class="form-text">Pilih golongan dari tingkatan standar kompensasi perusahaan.</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="uang_makan" class="form-label">Uang Makan / Hari <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="uang_makan" name="uang_makan" required min="0" step="1000" value="<?= (float) $golongan['uang_makan'] ?>">
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <label for="tunjangan" class="form-label">Tunjangan Golongan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="tunjangan" name="tunjangan" required min="0" step="1000" value="<?= (float) ($golongan['tunjangan'] ?? 0) ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Golongan</button>
        </form>
    </div>
</div>

<script>
function applyStandardValues(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const defaultMakan = selectedOption.getAttribute('data-uang-makan');
    const defaultTunjangan = selectedOption.getAttribute('data-tunjangan');
    if (defaultMakan) {
        document.getElementById('uang_makan').value = defaultMakan;
    }
    if (defaultTunjangan) {
        document.getElementById('tunjangan').value = defaultTunjangan;
    }
}
</script>
