-- Jalankan sekali pada database yang telah dibuat sebelum fitur ubah status payroll (audit trail).
-- Menambah kolom diproses_oleh untuk mencatat user ADMIN yang mengubah status payroll.
ALTER TABLE penggajian
    ADD COLUMN diproses_oleh INT UNSIGNED NULL AFTER tanggal_proses,
    ADD CONSTRAINT fk_penggajian_diproses_oleh
        FOREIGN KEY (diproses_oleh) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
