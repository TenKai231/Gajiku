-- Jalankan sekali pada database yang dibuat sebelum penambahan role PEMIMPIN.
-- Role PEMIMPIN: review & approval payroll.
-- Akses: dashboard, rekap karyawan (read), rekap absensi (read), review payroll,
-- approve payroll (Draft->Processed, Processed->Paid), laporan & cetak.
-- Pemimpin TIDAK dapat mengubah nominal payroll secara langsung.
ALTER TABLE users
    MODIFY COLUMN role ENUM('ADMIN', 'HR', 'PEMIMPIN') NOT NULL;

-- Akun demo pemimpin (password: pemimpin12345)
INSERT INTO users (username, password, role)
VALUES ('pemimpin', '$2y$12$cN0bk/Yv0IveCFzttf0Z9..A0VVNPrpoBIiO2paaar2QUFeDntfte', 'PEMIMPIN')
ON DUPLICATE KEY UPDATE role = VALUES(role);
