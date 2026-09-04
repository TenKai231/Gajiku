<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/karyawan.php';
require_once dirname(__DIR__, 2) . '/includes/jabatan.php';
require_once dirname(__DIR__, 2) . '/includes/pajak.php';

requireAuth();
$user = currentUser();

// Karyawan detail can be accessed by all logged-in roles
// (Admin & Finance can edit tax data, Pemimpin just views)

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /?page=karyawan/index');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';
$pdo = getPDO();
$karyawan = fetchKaryawanById($pdo, $id);

if (!$karyawan) {
    $_SESSION['error'] = 'Karyawan tidak ditemukan.';
    header('Location: /?page=karyawan/index');
    exit;
}

$taxProfile = getTaxProfile($pdo, $id);
$stmtBpjs = $pdo->prepare('SELECT * FROM data_bpjs_karyawan WHERE karyawan_id = ?');
$stmtBpjs->execute([$id]);
$bpjsProfile = $stmtBpjs->fetch(PDO::FETCH_ASSOC);
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Detail Karyawan</h1>
            <div>
                <?php if ($user['role'] === 'ADMIN'): ?>
                <a href="/?page=karyawan/edit&id=<?= $karyawan['id'] ?>" class="btn btn-primary me-2">Edit Data</a>
                <?php endif; ?>
                <a href="/?page=karyawan/index" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-borderless table-striped m-0">
                    <tbody>
                        <tr>
                            <th class="ps-4 w-25 py-3">NIP</th>
                            <td class="py-3"><?= htmlspecialchars($karyawan['nip'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <th class="ps-4 py-3">Nama Lengkap</th>
                            <td class="py-3 fw-bold"><?= htmlspecialchars($karyawan['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <th class="ps-4 py-3">Jenis Kelamin</th>
                            <td class="py-3"><?= $karyawan['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                        </tr>
                        <tr>
                            <th class="ps-4 py-3">Tanggal Lahir</th>
                            <td class="py-3"><?= date('d F Y', strtotime($karyawan['tanggal_lahir'])) ?></td>
                        </tr>
                        <tr>
                            <th class="ps-4 py-3">Jabatan</th>
                            <td class="py-3">
                                <span class="badge bg-info text-dark"><?= htmlspecialchars((string) $karyawan['nama_jabatan'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th class="ps-4 py-3">Gaji Pokok</th>
                            <td class="py-3"><?= formatCurrency((float) $karyawan['gaji_pokok']) ?></td>
                        </tr>
                        <tr>
                            <th class="ps-4 py-3">Tunjangan</th>
                            <td class="py-3"><?= formatCurrency((float) $karyawan['tunjangan_default']) ?></td>
                        </tr>
                        <tr>
                            <th class="ps-4 py-3">Tanggal Masuk</th>
                            <td class="py-3"><?= date('d F Y', strtotime($karyawan['tanggal_masuk'])) ?></td>
                        </tr>
                        <tr>
                            <th class="ps-4 py-3">Status</th>
                            <td class="py-3">
                                <?php if ($karyawan['status'] === 'Aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Data Pajak (PPh 21)</h5>
            </div>
            <div class="card-body">
                

                

                <form action="/?action=pajak/store" method="post">
                    <input type="hidden" name="karyawan_id" value="<?= $karyawan['id'] ?>">
                    <div class="mb-3">
                        <label for="nik" class="form-label">NIK (Nomor Induk Kependudukan)</label>
                        <input type="text" class="form-control" id="nik" name="nik" required
                               value="<?= htmlspecialchars($taxProfile['nik'] ?? '', ENT_QUOTES) ?>"
                               <?= $user['role'] !== 'ADMIN' && $user['role'] !== 'HR' && $user['role'] !== 'FINANCE' ? 'readonly' : '' ?>>
                    </div>
                    <div class="mb-3">
                        <label for="npwp" class="form-label">NPWP (Opsional)</label>
                        <input type="text" class="form-control" id="npwp" name="npwp"
                               value="<?= htmlspecialchars($taxProfile['npwp'] ?? '', ENT_QUOTES) ?>"
                               <?= $user['role'] !== 'ADMIN' && $user['role'] !== 'HR' && $user['role'] !== 'FINANCE' ? 'readonly' : '' ?>>
                    </div>
                    <div class="mb-3">
                        <label for="status_ptkp" class="form-label">Status PTKP</label>
                        <?php if ($user['role'] === 'ADMIN' || $user['role'] === 'HR' || $user['role'] === 'FINANCE'): ?>
                        <select class="form-select" id="status_ptkp" name="status_ptkp" required>
                            <option value="">Pilih Status PTKP...</option>
                            <?php
                            $ptkps = ['TK/0', 'TK/1', 'TK/2', 'TK/3', 'K/0', 'K/1', 'K/2', 'K/3'];
                            foreach ($ptkps as $ptkp):
                                $selected = ($taxProfile['status_ptkp'] ?? '') === $ptkp ? 'selected' : '';
                            ?>
                                <option value="<?= $ptkp ?>" <?= $selected ?>><?= $ptkp ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" class="form-control" readonly value="<?= htmlspecialchars($taxProfile['status_ptkp'] ?? '-', ENT_QUOTES) ?>">
                        <?php endif; ?>
                    </div>
                    <?php if ($taxProfile): ?>
                    <div class="mb-3">
                        <label class="form-label">Kategori TER Aktif</label>
                        <div>
                            <span class="badge bg-primary fs-6">Kategori <?= htmlspecialchars($taxProfile['kategori_ter']) ?></span>
                            <small class="text-muted ms-2">Ditentukan otomatis berdasarkan Status PTKP</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($user['role'] === 'ADMIN' || $user['role'] === 'HR' || $user['role'] === 'FINANCE'): ?>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <?= $taxProfile ? 'Update Data Pajak' : 'Simpan Data Pajak' ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <!-- Data BPJS -->
        <div class="card border-0 shadow-sm mt-4 mb-4" id="bpjs-section">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Data BPJS</h5>
            </div>
            <div class="card-body">
                

                

                <form action="/?action=bpjs/store" method="post">
                    <input type="hidden" name="karyawan_id" value="<?= $karyawan['id'] ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nomor_kesehatan" class="form-label">Nomor BPJS Kesehatan</label>
                            <input type="text" class="form-control" id="nomor_kesehatan" name="nomor_kesehatan"
                                   value="<?= htmlspecialchars($bpjsProfile['nomor_kesehatan'] ?? '', ENT_QUOTES) ?>"
                                   <?= $user['role'] !== 'ADMIN' && $user['role'] !== 'HR' ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status_kesehatan" class="form-label">Status BPJS Kesehatan</label>
                            <?php if ($user['role'] === 'ADMIN' || $user['role'] === 'HR'): ?>
                            <select class="form-select" id="status_kesehatan" name="status_kesehatan">
                                <option value="AKTIF" <?= ($bpjsProfile['status_kesehatan'] ?? '') === 'AKTIF' ? 'selected' : '' ?>>Aktif</option>
                                <option value="TIDAK_AKTIF" <?= ($bpjsProfile['status_kesehatan'] ?? '') === 'TIDAK_AKTIF' ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                            <?php else: ?>
                            <input type="text" class="form-control" readonly value="<?= ($bpjsProfile['status_kesehatan'] ?? 'AKTIF') === 'AKTIF' ? 'Aktif' : 'Tidak Aktif' ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nomor_ketenagakerjaan" class="form-label">Nomor BPJS Ketenagakerjaan</label>
                            <input type="text" class="form-control" id="nomor_ketenagakerjaan" name="nomor_ketenagakerjaan"
                                   value="<?= htmlspecialchars($bpjsProfile['nomor_ketenagakerjaan'] ?? '', ENT_QUOTES) ?>"
                                   <?= $user['role'] !== 'ADMIN' && $user['role'] !== 'HR' ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status_ketenagakerjaan" class="form-label">Status BPJS Ketenagakerjaan</label>
                            <?php if ($user['role'] === 'ADMIN' || $user['role'] === 'HR'): ?>
                            <select class="form-select" id="status_ketenagakerjaan" name="status_ketenagakerjaan">
                                <option value="AKTIF" <?= ($bpjsProfile['status_ketenagakerjaan'] ?? '') === 'AKTIF' ? 'selected' : '' ?>>Aktif</option>
                                <option value="TIDAK_AKTIF" <?= ($bpjsProfile['status_ketenagakerjaan'] ?? '') === 'TIDAK_AKTIF' ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                            <?php else: ?>
                            <input type="text" class="form-control" readonly value="<?= ($bpjsProfile['status_ketenagakerjaan'] ?? 'AKTIF') === 'AKTIF' ? 'Aktif' : 'Tidak Aktif' ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="risk_level_jkk" class="form-label">Tingkat Risiko JKK (Jaminan Kecelakaan Kerja)</label>
                        <?php if ($user['role'] === 'ADMIN' || $user['role'] === 'HR'): ?>
                        <select class="form-select" id="risk_level_jkk" name="risk_level_jkk">
                            <option value="">-- Tidak Terdaftar JKK --</option>
                            <?php
                            $riskLevels = [
                                'SANGAT_RENDAH' => 'Sangat Rendah (0.24%)',
                                'RENDAH' => 'Rendah (0.54%)',
                                'SEDANG' => 'Sedang (0.89%)',
                                'TINGGI' => 'Tinggi (1.27%)',
                                'SANGAT_TINGGI' => 'Sangat Tinggi (1.74%)'
                            ];
                            foreach ($riskLevels as $val => $label):
                                $selected = ($bpjsProfile['risk_level_jkk'] ?? '') === $val ? 'selected' : '';
                            ?>
                                <option value="<?= $val ?>" <?= $selected ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Risiko menentukan persentase tanggungan JKK oleh Perusahaan.</div>
                        <?php else: ?>
                        <input type="text" class="form-control" readonly value="<?= htmlspecialchars($bpjsProfile['risk_level_jkk'] ?? 'Tidak Terdaftar', ENT_QUOTES) ?>">
                        <?php endif; ?>
                    </div>

                    <?php if ($user['role'] === 'ADMIN' || $user['role'] === 'HR'): ?>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <?= $bpjsProfile ? 'Update Data BPJS' : 'Simpan Data BPJS' ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>