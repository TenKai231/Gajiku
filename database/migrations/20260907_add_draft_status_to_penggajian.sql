-- Jalankan sekali pada database yang telah dibuat sebelum perubahan status Draft dan koreksi payroll.
ALTER TABLE penggajian
    ADD COLUMN revisi SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER periode,
    ADD COLUMN koreksi_dari_id INT UNSIGNED NULL AFTER revisi,
    ADD COLUMN dikoreksi_pada DATETIME NULL AFTER status,
    DROP INDEX uq_penggajian_karyawan_periode,
    ADD CONSTRAINT uq_penggajian_karyawan_periode_revisi UNIQUE (karyawan_id, periode, revisi),
    ADD CONSTRAINT fk_penggajian_koreksi_dari
        FOREIGN KEY (koreksi_dari_id) REFERENCES penggajian(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    MODIFY COLUMN status ENUM('Draft', 'Processed', 'Paid', 'Corrected') NOT NULL DEFAULT 'Draft';
