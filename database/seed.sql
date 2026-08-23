USE penggajian_db;

INSERT INTO users (username, password, role)
VALUES
    ('admin', '$2y$10$DV24R71KeAouMMQCJYNz3eZLpDcDpQKgH9HgjbEEuwVVqZRDSIJ.6', 'ADMIN'),
    ('hrd', '$2y$10$IIGyfw8N1bAFhU3LRy33Jelv6zqQFtr9dGtbW7ahNnsC3xlot8BKe', 'HR');

INSERT INTO jabatan (nama_jabatan, gaji_pokok, tunjangan_default)
VALUES
    ('Manager', 12000000.00, 2000000.00),
    ('Staff HR', 7000000.00, 1000000.00),
    ('Staff Administrasi', 5500000.00, 750000.00);

INSERT INTO karyawan (nip, nama, jenis_kelamin, tanggal_lahir, tanggal_masuk, jabatan_id, status)
VALUES
    ('KRY001', 'Budi Santoso', 'L', '1995-03-12', '2022-01-10', 1, 'Aktif'),
    ('KRY002', 'Siti Rahma', 'P', '1998-07-23', '2023-04-01', 2, 'Aktif'),
    ('KRY003', 'Andi Pratama', 'L', '1997-11-05', '2021-09-15', 3, 'Aktif');

INSERT INTO absensi (karyawan_id, tanggal, status, jam_masuk, jam_pulang, keterangan)
VALUES
    (1, '2026-08-20', 'Hadir', '08:05:00', '17:03:00', NULL),
    (2, '2026-08-20', 'Sakit', NULL, NULL, 'Surat dokter terlampir'),
    (3, '2026-08-20', 'Hadir', '07:58:00', '17:10:00', NULL);

INSERT INTO penggajian (
    karyawan_id,
    periode,
    gaji_pokok,
    total_tunjangan,
    total_potongan,
    gaji_kotor,
    gaji_bersih,
    tanggal_proses,
    status
)
VALUES
    (1, '2026-08', 12000000.00, 2000000.00, 50000.00, 14000000.00, 13950000.00, '2026-08-21', 'Processed'),
    (2, '2026-08', 7000000.00, 1000000.00, 250000.00, 8000000.00, 7750000.00, '2026-08-21', 'Processed');
