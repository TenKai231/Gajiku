<?php
declare(strict_types=1);

// app/includes/payroll.php

function fetchPenggajianByPeriode(PDO $pdo, string $periode, ?string $status = null, ?string $search = null): array
{
    // $periode format: "MM-YYYY" misalnya "08-2026"
    $sql =
        'SELECT p.*, k.nama, k.nip, j.nama_jabatan
         FROM penggajian p
         JOIN karyawan k ON p.karyawan_id = k.id
         JOIN jabatan j ON k.jabatan_id = j.id
         WHERE p.periode = :periode';
    $params = [':periode' => $periode];

    if ($status !== null) {
        $sql .= ' AND p.status = :status';
        $params[':status'] = $status;
    }

    if ($search !== null && $search !== '') {
        $sql .= ' AND (k.nama LIKE :search OR k.nip LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    $stmt = $pdo->prepare($sql . ' ORDER BY k.nama ASC');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Ambil data penggajian rekap untuk suatu periode.
 * Hanya menampilkan revisi aktif/terbaru per karyawan; revisi lama yang
 * berstatus 'Corrected' (hasil perbaikan koreksi) disembunyikan dari laporan.
 */
function fetchPenggajianRekapByPeriode(PDO $pdo, string $periode): array
{
    $stmt = $pdo->prepare(
        "SELECT p.*, k.nama, k.nip, j.nama_jabatan
         FROM penggajian p
         JOIN karyawan k ON p.karyawan_id = k.id
         JOIN jabatan j ON k.jabatan_id = j.id
         WHERE p.periode = :periode
           AND p.status <> 'Corrected'
           AND p.revisi = (
               SELECT MAX(p2.revisi)
               FROM penggajian p2
               WHERE p2.karyawan_id = p.karyawan_id
                 AND p2.periode = p.periode
                 AND p2.status <> 'Corrected'
           )
         ORDER BY k.nama ASC"
    );
    $stmt->execute([':periode' => $periode]);
    return $stmt->fetchAll();
}

function cekPenggajianSudahAda(PDO $pdo, string $periode): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM penggajian WHERE periode = :periode');
    $stmt->execute([':periode' => $periode]);
    return ((int) $stmt->fetch()['total']) > 0;
}

function payrollPeriodeHanyaDraft(PDO $pdo, string $periode): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total, SUM(status = 'Draft') AS total_draft
         FROM penggajian WHERE periode = :periode"
    );
    $stmt->execute([':periode' => $periode]);
    $result = $stmt->fetch();

    return (int) $result['total'] > 0 && (int) $result['total'] === (int) $result['total_draft'];
}

function hapusPayrollDraftPeriode(PDO $pdo, string $periode): void
{
    $stmt = $pdo->prepare("DELETE FROM penggajian WHERE periode = :periode AND status = 'Draft'");
    $stmt->execute([':periode' => $periode]);
}

function payrollPeriodeMemilikiStatus(PDO $pdo, string $periode, string $status): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM penggajian WHERE periode = :periode AND status = :status LIMIT 1');
    $stmt->execute([':periode' => $periode, ':status' => $status]);
    return $stmt->fetchColumn() !== false;
}

/**
 * Validasi transisi status payroll yang diperbolehkan.
 * Draft -> Processed -> Paid (alur normal)
 * Paid -> Corrected (hanya melalui proses koreksi, bukan transisi manual)
 */
function transisiStatusPayrollValid(string $dari, string $ke): bool
{
    $allowed = [
        'Draft'     => ['Processed'],
        'Processed' => ['Paid', 'Draft'], // Draft di sini = batalkan kunci kembali (revert)
        'Paid'      => ['Corrected'],     // hanya via proses koreksi, bukan manual
    ];
    return in_array($ke, $allowed[$dari] ?? [], true);
}

/**
 * Mengubah status sebuah record payroll (ADMIN only, dipanggil dari action).
 * Record Approved/Paid tidak boleh diturunkan statusnya bebas.
 */
function ubahStatusPenggajian(PDO $pdo, int $payrollId, string $statusBaru, int $userId): void
{
    $stmt = $pdo->prepare('SELECT id, status FROM penggajian WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $payrollId]);
    $payroll = $stmt->fetch();

    if (!$payroll) {
        throw new RuntimeException('Data payroll tidak ditemukan.');
    }

    $statusLama = $payroll['status'];
    if ($statusLama === $statusBaru) {
        throw new RuntimeException('Status payroll sudah ' . $statusBaru . '.');
    }

    if (!transisiStatusPayrollValid($statusLama, $statusBaru)) {
        throw new RuntimeException(
            "Transisi status '{$statusLama}' → '{$statusBaru}' tidak diperbolehkan. " .
            'Payroll Paid hanya dapat dikoreksi melalui fitur koreksi (Corrected), bukan diubah manual.'
        );
    }

    $stmt = $pdo->prepare(
        'UPDATE penggajian
         SET status = :status,
             diproses_oleh = :diproses_oleh,
             tanggal_proses = :tanggal_proses
         WHERE id = :id'
    );
    $stmt->execute([
        ':status' => $statusBaru,
        ':diproses_oleh' => $userId,
        ':tanggal_proses' => date('Y-m-d'),
        ':id' => $payrollId,
    ]);
}

function tandaiPayrollPaidSebagaiDikoreksi(PDO $pdo, string $periode): void
{
    $stmt = $pdo->prepare(
        "UPDATE penggajian
         SET status = 'Corrected', dikoreksi_pada = NOW()
         WHERE periode = :periode AND status = 'Paid'"
    );
    $stmt->execute([':periode' => $periode]);
}

function cekPenggajianSudahAdaKaryawan(PDO $pdo, int $karyawanId, string $periode): bool
{
    $stmt = $pdo->prepare('SELECT id FROM penggajian WHERE karyawan_id = :karyawan_id AND periode = :periode LIMIT 1');
    $stmt->execute([
        ':karyawan_id' => $karyawanId,
        ':periode' => $periode
    ]);
    return $stmt->fetch() !== false;
}

/**
 * Ambil sumber koreksi untuk karyawan pada periode tertentu.
 * Mengembalikan record bertatus 'Corrected' dengan revisi tertinggi,
 * sehingga koreksi baru mendapat revisi = revisi tertinggi + 1 dan
 * koreksi_dari_id = id record Corrected tersebut.
 *
 * @return array{revisi: int, koreksi_dari_id: ?int}
 */
function ambilPayrollSumberKoreksi(PDO $pdo, int $karyawanId, string $periode): array
{
    $stmt = $pdo->prepare(
        'SELECT id, revisi
         FROM penggajian
         WHERE karyawan_id = :karyawan_id AND periode = :periode AND status = \'Corrected\'
         ORDER BY revisi DESC
         LIMIT 1'
    );
    $stmt->execute([
        ':karyawan_id' => $karyawanId,
        ':periode' => $periode,
    ]);
    $sumber = $stmt->fetch();

    if ($sumber === false) {
        throw new RuntimeException(
            "Tidak ditemukan payroll Corrected untuk karyawan #{$karyawanId} periode {$periode}."
        );
    }

    return [
        'revisi' => (int) $sumber['revisi'] + 1,
        'koreksi_dari_id' => (int) $sumber['id'],
    ];
}

/**
 * Core Payroll Engine Calculation
 */
function kalkulasiGajiKaryawan(PDO $pdo, array $karyawan, array $absensiBulanIni, array $taxProfile, float $terRate, array $rules, string $periode): array
{
    // 1. Ambil data dasar (jabatan)
    $gajiPokok = (float) $karyawan['gaji_pokok'];
    $tunjanganDefault = (float) $karyawan['tunjangan_default'];
    $uangMakanUtuh = (float) ($karyawan['uang_makan'] ?? 0);

    // 2. Analisis data absen
    $totalHadir = 0;
    $totalAlpha = 0;
    $totalSakit = 0;
    $totalTerlambat = 0;

    foreach ($absensiBulanIni as $absen) {
        if ($absen['status'] === 'Hadir') {
            $totalHadir++;
            if ($absen['jam_masuk']) {
                $jamMasuk = substr($absen['jam_masuk'], 0, 5);
                // Cek terlambat
                if (strtotime($jamMasuk) > strtotime($rules['normal_entry_time'])) {
                    $totalTerlambat++;
                }
            }
        } elseif ($absen['status'] === 'Alpha') {
            $totalAlpha++;
        } elseif ($absen['status'] === 'Sakit') {
            $totalSakit++;
        }
    }

    // 3. Pro-rate Karyawan Baru (masuk di bulan berjalan)
    // Periode contoh "08-2026"
    list($blnProses, $thnProses) = explode('-', $periode);
    $blnProses = (int) $blnProses;
    $thnProses = (int) $thnProses;

    $tglMasuk = strtotime($karyawan['tanggal_masuk']);
    $blnMasuk = (int) date('n', $tglMasuk);
    $thnMasuk = (int) date('Y', $tglMasuk);

    $isProrata = false;
    $gajiPerHari = $gajiPokok / 30; // Standar perhitungan perusahaan

    // Jika karyawan baru masuk bulan ini, prorate berdasarkan kehadiran aktual + ijin/sakit
    if ($thnMasuk === $thnProses && $blnMasuk === $blnProses) {
        $isProrata = true;

        // Asumsi: karyawan dibayar berdasarkan hari dia benar-benar sudah masuk (hadir/sakit/izin)
        // Hitung total hari kerja dia semenjak tgl masuk
        $hariDalamSebulan = (int) date('t', strtotime("$thnProses-$blnProses-01"));
        $hariAktifKerja = $hariDalamSebulan - (int) date('j', $tglMasuk) + 1; // Contoh: masuk tgl 15. Hari bulan ini 31. Aktif = 31 - 15 + 1 = 17 hari

        // Prorata basic salary & tunjangan
        $gajiPokok = ($gajiPokok / 30) * $hariAktifKerja;
        $tunjanganDefault = ($tunjanganDefault / 30) * $hariAktifKerja;
    }

    // 4. Hitung Potongan
    // Potongan Alpha = n hari * (Gaji Pokok Awal / 30) * rules (1 hari)
    $potonganAlpha = $totalAlpha * $gajiPerHari * $rules['deductions']['alpha'];

    // Potongan Sakit = n hari * (Gaji Pokok Awal / 30) * rules (0.5 hari)
    $potonganSakit = $totalSakit * $gajiPerHari * $rules['deductions']['sakit'];

    // Potongan Terlambat
    $potonganTerlambat = $totalTerlambat * $rules['deductions']['late_per_occurrence'];

    $totalPotongan = $potonganAlpha + $potonganSakit + $potonganTerlambat;
    $totalTunjangan = $tunjanganDefault;

    // 5. Hitung Gaji Kotor & Bersih
    $potonganLain = $potonganAlpha + $potonganSakit;
    
    // 4.5 Hitung Uang Makan (Hanya diberikan sesuai kehadiran nyata, dipotong jika terlambat)
    $totalUangMakan = $totalHadir * $uangMakanUtuh;
    $potonganUangMakan = $totalTerlambat * $rules['deductions']['late_per_occurrence'];
    if ($potonganUangMakan > $totalUangMakan) {
        $potonganUangMakan = $totalUangMakan; // Jangan sampai minus uang makan
    }

    $gajiKotor = $gajiPokok + $totalTunjangan + $totalUangMakan;
    // Hitung BPJS
    $processDate = sprintf('%04d-%02d-01', explode('-', $periode)[1], explode('-', $periode)[0]);
    $bpjs = calculateBpjs($pdo, (int)$karyawan['id'], $gajiPokok, $totalTunjangan, $processDate);

    $pph21 = $gajiKotor * $terRate;
    $totalPotongan = $potonganLain + $bpjs['total_deduction'] + $pph21 + $potonganUangMakan;
    $gajiBersih = $gajiKotor - $totalPotongan;

    return [
        'karyawan_id' => $karyawan['id'],
        'periode' => $periode,
        'gaji_pokok' => $gajiPokok,
        'total_tunjangan' => $totalTunjangan,
        'total_uang_makan' => $totalUangMakan,
        'potongan_uang_makan' => $potonganUangMakan,
        'potongan_lain' => $potonganLain,
        'total_potongan' => $totalPotongan,
        'gaji_kotor' => $gajiKotor,
        'pph21' => $pph21 ?? 0,
        'potongan_bpjs' => $bpjs['total_deduction'] ?? 0,
        'total_tanggungan_perusahaan' => $bpjs['total_contribution'] ?? 0,
        'bpjs_detail' => $bpjs ?? [],
        'status_ptkp_snapshot' => $taxProfile ? $taxProfile['status_ptkp'] : null,
        'kategori_ter_snapshot' => $taxProfile ? $taxProfile['kategori_ter'] : null,
        'gaji_bersih' => $gajiBersih,
        // Meta data untuk slip gaji
        '_meta' => [
            'total_hadir' => $totalHadir,
            'total_alpha' => $totalAlpha,
            'total_sakit' => $totalSakit,
            'total_terlambat' => $totalTerlambat,
            'potongan_alpha' => $potonganAlpha,
            'potongan_sakit' => $potonganSakit,
            'potongan_terlambat' => $potonganTerlambat,
            'is_prorata' => $isProrata
        ]
    ];
}

function simpanPenggajian(PDO $pdo, array $payrollData, string $status = 'Draft', int $revisi = 1, ?int $koreksiDariId = null, ?int $diprosesOleh = null): void
{
    if (!in_array($status, ['Draft', 'Processed'], true)) {
        throw new InvalidArgumentException('Status payroll tidak valid untuk disimpan.');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO penggajian (
            karyawan_id, periode, revisi, koreksi_dari_id, gaji_pokok, total_tunjangan, gaji_kotor,
            total_uang_makan, potongan_uang_makan,
            potongan_lain, potongan_bpjs, total_tanggungan_perusahaan, total_potongan, gaji_bersih, tanggal_proses, status, pph21, status_ptkp_snapshot, kategori_ter_snapshot, diproses_oleh
         ) VALUES (
            :karyawan_id, :periode, :revisi, :koreksi_dari_id, :gaji_pokok, :total_tunjangan, :gaji_kotor,
            :total_uang_makan, :potongan_uang_makan,
            :potongan_lain, :potongan_bpjs, :total_tanggungan_perusahaan, :total_potongan, :gaji_bersih, :tanggal_proses, :status, :pph21, :status_ptkp_snapshot, :kategori_ter_snapshot, :diproses_oleh
         )"
    );

    $stmt->execute([
        ':karyawan_id' => $payrollData['karyawan_id'],
        ':periode' => $payrollData['periode'],
        ':revisi' => $revisi,
        ':koreksi_dari_id' => $koreksiDariId,
        ':gaji_pokok' => $payrollData['gaji_pokok'],
        ':total_tunjangan' => $payrollData['total_tunjangan'],
        ':gaji_kotor' => $payrollData['gaji_kotor'],
        ':total_uang_makan' => $payrollData['total_uang_makan'] ?? 0,
        ':potongan_uang_makan' => $payrollData['potongan_uang_makan'] ?? 0,
        ':potongan_lain' => $payrollData['potongan_lain'] ?? 0,
        ':potongan_bpjs' => $payrollData['potongan_bpjs'],
        ':total_tanggungan_perusahaan' => $payrollData['total_tanggungan_perusahaan'],
        ':total_potongan' => $payrollData['total_potongan'],
        ':gaji_bersih' => $payrollData['gaji_bersih'],
        ':tanggal_proses' => date('Y-m-d'),
        ':status' => $status,
        ':pph21' => $payrollData['pph21'],
        ':status_ptkp_snapshot' => $payrollData['status_ptkp_snapshot'],
        ':kategori_ter_snapshot' => $payrollData['kategori_ter_snapshot'],
        ':diproses_oleh' => $diprosesOleh
    ]);

    $penggajianId = (int)$pdo->lastInsertId();

    // Insert BPJS Snapshots
    $stmtBpjs = $pdo->prepare(
        'INSERT INTO detail_iuran_penggajian (
            penggajian_id, program, payer, dasar_upah, tarif, jumlah, rule_id
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?
        )'
    );

    $bpjs = $payrollData['bpjs_detail'] ?? ['deductions' => [], 'contributions' => []];

    foreach ($bpjs['deductions'] as $program => $detail) {
        if ($detail['amount'] > 0) {
            $stmtBpjs->execute([
                $penggajianId, $program, 'EMPLOYEE', $detail['base'], $detail['rate'], $detail['amount'], $detail['rule_id']
            ]);
        }
    }

    foreach ($bpjs['contributions'] as $program => $detail) {
        if ($detail['amount'] > 0) {
            $stmtBpjs->execute([
                $penggajianId, $program, 'EMPLOYER', $detail['base'], $detail['rate'], $detail['amount'], $detail['rule_id']
            ]);
        }
    }
}

function calculateBpjs(PDO $pdo, int $karyawanId, float $gajiPokok, float $tunjanganTetap, string $processDate): array {
    $gajiDasar = $gajiPokok + $tunjanganTetap;

    // 1. Get BPJS Profile
    $stmt = $pdo->prepare('SELECT * FROM data_bpjs_karyawan WHERE karyawan_id = ?');
    $stmt->execute([$karyawanId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    $results = [
        'deductions' => [ // Potongan Karyawan (mengurangi THP)
            'BPJS_KESEHATAN' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JHT' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JP' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
        ],
        'contributions' => [ // Tanggungan Perusahaan (TIDAK mengurangi THP)
            'BPJS_KESEHATAN' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JHT' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JP' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JKM' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JKK' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
        ],
        'total_deduction' => 0.0,
        'total_contribution' => 0.0
    ];

    if (!$profile) {
        return $results; // Karyawan tidak terdaftar BPJS
    }

    // 2. Load Active Rules
    $stmt = $pdo->prepare('
        SELECT * FROM aturan_iuran 
        WHERE berlaku_mulai <= :tanggal1 
        AND (berlaku_sampai >= :tanggal2 OR berlaku_sampai IS NULL)
    ');
    $stmt->execute([':tanggal1' => $processDate, ':tanggal2' => $processDate]);
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $riskLevel = $profile['risk_level_jkk'];

    foreach ($rules as $rule) {
        $prog = $rule['program'];
        $payer = $rule['payer'];
        
        // Skip JKK if risk level doesn't match
        if ($prog === 'JKK' && $rule['risk_level'] !== $riskLevel) {
            continue;
        }

        // Determine Wage Base
        $wageBase = $gajiDasar; // default as per requirements: GAJI_POKOK_PLUS_TUNJANGAN_TETAP
        if ($rule['max_wage'] !== null && $wageBase > (float)$rule['max_wage']) {
            $wageBase = (float)$rule['max_wage'];
        }

        $amount = $wageBase * (float)$rule['rate'];

        if ($payer === 'EMPLOYEE' && isset($results['deductions'][$prog])) {
            $results['deductions'][$prog] = [
                'amount' => $amount, 'rate' => (float)$rule['rate'], 'base' => $wageBase, 'rule_id' => $rule['id']
            ];
            $results['total_deduction'] += $amount;
        } elseif ($payer === 'EMPLOYER' && isset($results['contributions'][$prog])) {
            $results['contributions'][$prog] = [
                'amount' => $amount, 'rate' => (float)$rule['rate'], 'base' => $wageBase, 'rule_id' => $rule['id']
            ];
            $results['total_contribution'] += $amount;
        }
    }

    return $results;
}
