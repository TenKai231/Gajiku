<?php
declare(strict_types=1);

// app/includes/payroll.php

function fetchPenggajianByPeriode(PDO $pdo, string $periode): array
{
    // $periode format: "MM-YYYY" misalnya "08-2026"
    $stmt = $pdo->prepare(
        'SELECT p.*, k.nama, k.nip, j.nama_jabatan
         FROM penggajian p
         JOIN karyawan k ON p.karyawan_id = k.id
         JOIN jabatan j ON k.jabatan_id = j.id
         WHERE p.periode = :periode
         ORDER BY k.nama ASC'
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
 * Core Payroll Engine Calculation
 */
function kalkulasiGajiKaryawan(array $karyawan, array $absensiBulanIni, array $rules, string $periode): array
{
    // 1. Ambil data dasar (jabatan)
    $gajiPokok = (float) $karyawan['gaji_pokok'];
    $tunjanganDefault = (float) $karyawan['tunjangan_default'];

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
    $gajiKotor = $gajiPokok + $totalTunjangan;
    $gajiBersih = $gajiKotor - $totalPotongan;

    return [
        'karyawan_id' => $karyawan['id'],
        'periode' => $periode,
        'gaji_pokok' => $gajiPokok,
        'total_tunjangan' => $totalTunjangan,
        'total_potongan' => $totalPotongan,
        'gaji_kotor' => $gajiKotor,
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

function simpanPenggajian(PDO $pdo, array $payrollData): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO penggajian (
            karyawan_id, periode, gaji_pokok, total_tunjangan,
            total_potongan, gaji_kotor, gaji_bersih, tanggal_proses, status
         ) VALUES (
            :karyawan_id, :periode, :gaji_pokok, :total_tunjangan,
            :total_potongan, :gaji_kotor, :gaji_bersih, :tanggal_proses, :status
         )'
    );

    $stmt->execute([
        ':karyawan_id' => $payrollData['karyawan_id'],
        ':periode' => $payrollData['periode'],
        ':gaji_pokok' => $payrollData['gaji_pokok'],
        ':total_tunjangan' => $payrollData['total_tunjangan'],
        ':total_potongan' => $payrollData['total_potongan'],
        ':gaji_kotor' => $payrollData['gaji_kotor'],
        ':gaji_bersih' => $payrollData['gaji_bersih'],
        ':tanggal_proses' => date('Y-m-d H:i:s'),
        ':status' => 'Processed' // Status default untuk MVP
    ]);
}