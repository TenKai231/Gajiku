<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireAuth();
$user = currentUser();
if (!in_array($user['role'], ['ADMIN', 'HR'], true)) {
    http_response_code(403);
    die('Akses ditolak.');
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
// Ambil karyawan aktif saja untuk diabsen
$stmt = $pdo->query("SELECT id, nip FROM karyawan WHERE status = 'Aktif' ORDER BY nip ASC");
$karyawans = $stmt->fetchAll();

$errors = $_SESSION['errors'] ?? [];
$form = $_SESSION['form'] ?? [
    'karyawan_id' => '', 'tanggal' => date('Y-m-d'), 'status' => 'Hadir',
    'jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'keterangan' => ''
];
unset($_SESSION['errors'], $_SESSION['form']);
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Input Absensi</h1>
            <a href="/?page=absensi/index" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="/?action=absensi/store" method="POST" id="formAbsensi">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control <?= isset($errors['tanggal']) ? 'is-invalid' : '' ?>"
                                   id="tanggal" name="tanggal" value="<?= htmlspecialchars((string) $form['tanggal'], ENT_QUOTES, 'UTF-8') ?>" required>
                            <?php if (isset($errors['tanggal'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['tanggal'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="karyawan_id" class="form-label">NIP Karyawan</label>
                            <select class="form-select <?= isset($errors['karyawan_id']) ? 'is-invalid' : '' ?>"
                                    id="karyawan_id" name="karyawan_id" required>
                                <option value="">-- Pilih NIP Karyawan --</option>
                                <?php foreach ($karyawans as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= ((string) $form['karyawan_id'] === (string) $k['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $k['nip'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['karyawan_id'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['karyawan_id'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label for="status" class="form-label">Status Absensi</label>
                        <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>"
                                id="status" name="status" required onchange="toggleJamFields()">
                            <?php
                            $statuses = ['Hadir', 'Sakit', 'Izin', 'Alpha', 'Cuti'];
                            foreach ($statuses as $st): ?>
                                <option value="<?= $st ?>" <?= $form['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row" id="jamFields">
                        <div class="col-md-6 mb-3">
                            <label for="jam_masuk" class="form-label">Jam Masuk</label>
                            <input type="time" class="form-control <?= isset($errors['jam_masuk']) ? 'is-invalid' : '' ?>"
                                   id="jam_masuk" name="jam_masuk" value="<?= htmlspecialchars((string) $form['jam_masuk'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (isset($errors['jam_masuk'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['jam_masuk'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="jam_pulang" class="form-label">Jam Pulang</label>
                            <input type="time" class="form-control <?= isset($errors['jam_pulang']) ? 'is-invalid' : '' ?>"
                                   id="jam_pulang" name="jam_pulang" value="<?= htmlspecialchars((string) $form['jam_pulang'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (isset($errors['jam_pulang'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['jam_pulang'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="2"><?= htmlspecialchars((string) $form['keterangan'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Simpan Absensi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleJamFields() {
        const status = document.getElementById('status').value;
        const jamFields = document.getElementById('jamFields');
        const jamMasuk = document.getElementById('jam_masuk');

        if (status === 'Hadir') {
            jamFields.style.display = 'flex';
            jamMasuk.required = true;
        } else {
            jamFields.style.display = 'none';
            jamMasuk.required = false;
        }
    }

    // Panggil saat load pertama kali
    document.addEventListener('DOMContentLoaded', toggleJamFields);
</script>