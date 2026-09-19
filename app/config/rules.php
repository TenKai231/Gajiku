<?php

declare(strict_types=1);

/**
 * Aturan payroll dasar.
 *
 * Dipakai oleh:
 *   - app/includes/payroll.php        → kalkulasiGajiKaryawan()
 *   - app/pages/absensi/index.php     → isTerlambat()
 *   - app/pages/karyawan/detail.php   → isTerlambat()
 *   - app/actions/payroll/process.php → $rules
 *
 * CATATAN ATURAN TERLAMBAT:
 *   Terlambat (jam_masuk > normal_entry_time) HANYA memotong UANG MAKAN
 *   pada hari tersebut — BUKAN gaji pokok, dan BUKAN nominal tetap.
 *   Lihat app/includes/payroll.php (potongan_uang_makan = min(total, telat × uang_makan/hari)).
 *
 *   Karena itu TIDAK ada lagi aturan "denda keterlambatan Rp50.000/kejadian".
 *   Nominal lama tersebut (sisa desain MVP Rancangan 1.0) sudah dihapus agar
 *   tidak menyesatkan. Jika kebijakan denda tetap memang diberlakukan di masa
 *   depan, tambahkan sebagai key eksplisit di bawah dan konsumsi di payroll.php.
 */

return [
    // Jam masuk normal. Di atas ini dianggap terlambat.
    'normal_entry_time' => '08:00',

    // Pengali hari-gaji untuk potongan gaji pokok (gaji pokok / 30 per hari).
    // Izin & Cuti tidak dipotong (tidak tercantum di sini).
    'deductions' => [
        'alpha' => 1.0,   // 1 hari gaji / kejadian
        'sakit' => 0.5,   // 0.5 hari gaji / kejadian
    ],
];
