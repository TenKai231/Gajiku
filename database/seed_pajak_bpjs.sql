-- Seed data_pajak_karyawan & data_bpjs_karyawan untuk 18 karyawan aktif
-- Dijalankan setelah seed.sql dan seed_pph21.sql
-- Generated: 2026-11-18

USE penggajian_db;

-- ============================================
-- DATA PAJAK KARYAWAN
-- ============================================
-- Kategori TER ditentukan dari status PTKP:
-- A = TK/0, TK/1, K/0
-- B = TK/2, TK/3, K/1, K/2
-- C = K/3
-- NPWP format 16 digit (tanpa titik/tanda hubung)

INSERT INTO `data_pajak_karyawan` (`karyawan_id`, `nik`, `npwp`, `status_ptkp`, `kategori_ter`) VALUES
-- KRY005 - Nadine Ivanka Agustine (ID 7)
(7, '3273014501980001', '3273014501980001', 'TK/0', 'A'),
-- KRY012 - Melati Puspita Sari (ID 8)
(8, '3273015602950002', '3273015602950002', 'TK/0', 'A'),
-- KRY001 - Rama Ferdiansyah (ID 11)
(11, '3273010101900003', '3273010101900003', 'TK/0', 'A'),
-- KRY010 - Putri Maharani (ID 12)
(12, '3273012303920004', '3273012303920004', 'TK/0', 'A'),
-- KRY011 - Fajar Ramadhan (ID 13)
(13, '3273013404930005', '3273013404930005', 'TK/0', 'A'),
-- KRY013 - Yoga Firmansyah (ID 14)
(14, '3273014505940006', '3273014505940006', 'TK/0', 'A'),
-- KRY017 - Bagas Aditya Nugraha (ID 15)
(15, '3273015606950007', '3273015606950007', 'TK/0', 'A'),
-- KRY020 - Vina Oktaviani (ID 16)
(16, '3273016707960008', '3273016707960008', 'TK/0', 'A'),
-- KRY002 - Nurul Eksa (ID 17)
(17, '3273010108910009', '3273010108910009', 'TK/0', 'A'),
-- KRY003 - Krisdayanti (ID 18)
(18, '3273011209900010', '3273011209900010', 'TK/0', 'A'),
-- KRY007 - Andika Pratama (ID 19)
(19, '3273012310890011', '3273012310890011', 'TK/0', 'A'),
-- KRY008 - Siti Aulia Rahmah (ID 20)
(20, '3273013411920012', '3273013411920012', 'TK/0', 'A'),
-- KRY014 - Anisa Fitriani (ID 21)
(21, '3273014512930013', '3273014512930013', 'TK/0', 'A'),
-- KRY004 - Muhammad Rafly Alfasa (ID 22)
(22, '3273015601880014', '3273015601880014', 'K/0', 'A'),
-- KRY006 - Ronggur Van Hook Cai Sianturi (ID 23)
(23, '3273016702870015', '3273016702870015', 'K/1', 'B'),
-- KRY009 - Dimas Arya Saputra (ID 24)
(24, '3273017803890016', '3273017803890016', 'K/1', 'B'),
-- KRY016 - Dewi Anggraini (ID 25)
(25, '3273018904900017', '3273018904900017', 'K/2', 'B'),
-- KRY019 - Ardiansyah Putra (ID 26)
(26, '3273019005880018', '3273019005880018', 'K/3', 'C');

-- ============================================
-- DATA BPJS KARYAWAN
-- ============================================
-- Kolom DB: nomor_kesehatan, nomor_ketenagakerjaan, status_kesehatan, status_ketenagakerjaan, risk_level_jkk
-- risk_level_jkk enum: SANGAT_RENDAH, RENDAH, SEDANG, TINGGI, SANGAT_TINGGI

INSERT INTO `data_bpjs_karyawan` (`karyawan_id`, `nomor_kesehatan`, `nomor_ketenagakerjaan`, `status_kesehatan`, `status_ketenagakerjaan`, `risk_level_jkk`) VALUES
(7,  '0001234567890123', '1234567890123456', 'AKTIF', 'AKTIF', 'RENDAH'),
(8,  '0001234567890124', '1234567890123457', 'AKTIF', 'AKTIF', 'RENDAH'),
(11, '0001234567890125', '1234567890123458', 'AKTIF', 'AKTIF', 'RENDAH'),
(12, '0001234567890126', '1234567890123459', 'AKTIF', 'AKTIF', 'RENDAH'),
(13, '0001234567890127', '1234567890123460', 'AKTIF', 'AKTIF', 'RENDAH'),
(14, '0001234567890128', '1234567890123461', 'AKTIF', 'AKTIF', 'RENDAH'),
(15, '0001234567890129', '1234567890123462', 'AKTIF', 'AKTIF', 'RENDAH'),
(16, '0001234567890130', '1234567890123463', 'AKTIF', 'AKTIF', 'RENDAH'),
(17, '0001234567890131', '1234567890123464', 'AKTIF', 'AKTIF', 'RENDAH'),
(18, '0001234567890132', '1234567890123465', 'AKTIF', 'AKTIF', 'RENDAH'),
(19, '0001234567890133', '1234567890123466', 'AKTIF', 'AKTIF', 'RENDAH'),
(20, '0001234567890134', '1234567890123467', 'AKTIF', 'AKTIF', 'RENDAH'),
(21, '0001234567890135', '1234567890123468', 'AKTIF', 'AKTIF', 'RENDAH'),
(22, '0001234567890136', '1234567890123469', 'AKTIF', 'AKTIF', 'SEDANG'),
(23, '0001234567890137', '1234567890123470', 'AKTIF', 'AKTIF', 'SEDANG'),
(24, '0001234567890138', '1234567890123471', 'AKTIF', 'AKTIF', 'SEDANG'),
(25, '0001234567890139', '1234567890123472', 'AKTIF', 'AKTIF', 'SEDANG'),
(26, '0001234567890140', '1234567890123473', 'AKTIF', 'AKTIF', 'TINGGI');