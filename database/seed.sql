USE penggajian_db;

-- Demo credentials:
-- admin / admin12345
-- hrd / hrd12345
-- pemimpin / pemimpin12345
INSERT INTO users (username, password, role)
VALUES
    ('admin', '$2y$12$EmLWFcSUpU7cyQyEEo3DTe4BipF2XE/t57K5WfiRW7AOEBU0/fBmi', 'ADMIN'),
    ('hrd', '$2y$12$GanuIYv.xyRskDG180iWKuT40Z60XKzR43KT2C8sFdcdHN2l97zRK', 'HR'),
    ('pemimpin', '$2y$12$cN0bk/Yv0IveCFzttf0Z9..A0VVNPrpoBIiO2paaar2QUFeDntfte', 'PEMIMPIN');

INSERT INTO jabatan (nama_jabatan, gaji_pokok, tunjangan_default)
VALUES
    ('Manager', 12000000.00, 2000000.00),
    ('Staff HR', 7000000.00, 1000000.00),
    ('Staff Administrasi', 5500000.00, 750000.00);

INSERT INTO golongan (nama_golongan, uang_makan, tunjangan)
VALUES
    ('Golongan I', 50000.00, 750000.00),
    ('Golongan II', 75000.00, 1000000.00),
    ('Golongan III', 100000.00, 2000000.00);

INSERT INTO karyawan (nip, nama, jenis_kelamin, tanggal_lahir, tanggal_masuk, jabatan_id, golongan_id, status)
VALUES
    ('KRY001', 'Budi Santoso', 'L', '1995-03-12', '2022-01-10', 1, 3, 'Aktif'),
    ('KRY002', 'Siti Rahma', 'P', '1998-07-23', '2023-04-01', 2, 2, 'Aktif'),
    ('KRY003', 'Andi Pratama', 'L', '1997-11-05', '2021-09-15', 3, 1, 'Aktif');

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
    uang_makan,
    total_potongan,
    gaji_kotor,
    gaji_bersih,
    tanggal_proses,
    status
)
VALUES
    (1, '2026-08', 12000000.00, 2000000.00, 100000.00, 100000.00, 14100000.00, 14000000.00, '2026-08-21', 'Processed'),
    (2, '2026-08', 7000000.00, 1000000.00, 75000.00, 250000.00, 8075000.00, 7825000.00, '2026-08-21', 'Processed');
