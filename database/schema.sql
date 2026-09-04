CREATE DATABASE IF NOT EXISTS penggajian_db;
USE penggajian_db;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'HR') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jabatan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_jabatan VARCHAR(100) NOT NULL,
    gaji_pokok DECIMAL(15,2) NOT NULL,
    tunjangan_default DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS golongan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_golongan VARCHAR(50) NOT NULL,
    uang_makan DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS karyawan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    tanggal_lahir DATE NOT NULL,
    tanggal_masuk DATE NOT NULL,
    jabatan_id INT UNSIGNED NOT NULL,
    golongan_id INT UNSIGNED NOT NULL,
    status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_karyawan_jabatan
        FOREIGN KEY (jabatan_id) REFERENCES jabatan(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_karyawan_golongan
        FOREIGN KEY (golongan_id) REFERENCES golongan(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS absensi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('Hadir', 'Sakit', 'Izin', 'Alpha', 'Cuti') NOT NULL,
    jam_masuk TIME NULL,
    jam_pulang TIME NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_absensi_karyawan_tanggal UNIQUE (karyawan_id, tanggal),
    CONSTRAINT fk_absensi_karyawan
        FOREIGN KEY (karyawan_id) REFERENCES karyawan(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Data Pajak Karyawan
CREATE TABLE IF NOT EXISTS data_pajak_karyawan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT UNSIGNED NOT NULL UNIQUE,
    nik VARCHAR(16) NOT NULL,
    npwp VARCHAR(16) NULL,
    status_ptkp ENUM('TK/0', 'TK/1', 'TK/2', 'TK/3', 'K/0', 'K/1', 'K/2', 'K/3') NOT NULL,
    kategori_ter ENUM('A', 'B', 'C') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pajak_karyawan
        FOREIGN KEY (karyawan_id) REFERENCES karyawan(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT uq_pajak_nik UNIQUE (nik)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Tarif TER
CREATE TABLE IF NOT EXISTS tarif_ter (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kategori ENUM('A', 'B', 'C') NOT NULL,
    min_bruto DECIMAL(15,2) NOT NULL,
    max_bruto DECIMAL(15,2) NULL,
    tarif DECIMAL(6,4) NOT NULL,
    berlaku_mulai DATE NOT NULL,
    berlaku_sampai DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Tarif Pasal 17
CREATE TABLE IF NOT EXISTS tarif_pasal17 (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    min_pkp DECIMAL(15,2) NOT NULL,
    max_pkp DECIMAL(15,2) NULL,
    tarif DECIMAL(6,4) NOT NULL,
    berlaku_mulai DATE NOT NULL,
    berlaku_sampai DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS penggajian (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT UNSIGNED NOT NULL,
    periode CHAR(7) NOT NULL,
    revisi SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    koreksi_dari_id INT UNSIGNED NULL,
    gaji_pokok DECIMAL(15,2) NOT NULL,
    total_tunjangan DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_uang_makan DECIMAL(15,2) NOT NULL DEFAULT 0,
    potongan_uang_makan DECIMAL(15,2) NOT NULL DEFAULT 0,
    gaji_kotor DECIMAL(15,2) NOT NULL,
    pph21 DECIMAL(15,2) NOT NULL DEFAULT 0,
    status_ptkp_snapshot VARCHAR(10) NULL,
    kategori_ter_snapshot CHAR(1) NULL,
    potongan_lain DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_potongan DECIMAL(15,2) NOT NULL DEFAULT 0,
    gaji_bersih DECIMAL(15,2) NOT NULL,
    tanggal_proses DATE NOT NULL,
    diproses_oleh INT UNSIGNED NULL,
    status ENUM('Draft', 'Processed', 'Paid', 'Corrected') NOT NULL DEFAULT 'Draft',
    dikoreksi_pada DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_penggajian_karyawan_periode_revisi UNIQUE (karyawan_id, periode, revisi),
    CONSTRAINT fk_penggajian_karyawan
        FOREIGN KEY (karyawan_id) REFERENCES karyawan(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_penggajian_koreksi_dari
        FOREIGN KEY (koreksi_dari_id) REFERENCES penggajian(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_penggajian_diproses_oleh
        FOREIGN KEY (diproses_oleh) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Tabel Data BPJS Karyawan
CREATE TABLE IF NOT EXISTS data_bpjs_karyawan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT UNSIGNED NOT NULL UNIQUE,
    nomor_kesehatan VARCHAR(20) NULL,
    nomor_ketenagakerjaan VARCHAR(20) NULL,
    status_kesehatan ENUM('AKTIF', 'TIDAK_AKTIF') NOT NULL DEFAULT 'AKTIF',
    status_ketenagakerjaan ENUM('AKTIF', 'TIDAK_AKTIF') NOT NULL DEFAULT 'AKTIF',
    risk_level_jkk ENUM('SANGAT_RENDAH', 'RENDAH', 'SEDANG', 'TINGGI', 'SANGAT_TINGGI') NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bpjs_karyawan
        FOREIGN KEY (karyawan_id) REFERENCES karyawan(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Aturan Iuran (BPJS Rules)
CREATE TABLE IF NOT EXISTS aturan_iuran (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program ENUM('BPJS_KESEHATAN', 'JHT', 'JKK', 'JKM', 'JP', 'JKP') NOT NULL,
    payer ENUM('EMPLOYEE', 'EMPLOYER', 'GOVERNMENT', 'RECOMPOSITION') NOT NULL,
    risk_level ENUM('SANGAT_RENDAH', 'RENDAH', 'SEDANG', 'TINGGI', 'SANGAT_TINGGI') NULL,
    rate DECIMAL(8,6) NOT NULL,
    wage_base ENUM('GAJI_POKOK', 'GAJI_POKOK_PLUS_TUNJANGAN_TETAP', 'GAJI_BRUTO', 'CUSTOM') NOT NULL,
    max_wage DECIMAL(15,2) NULL,
    berlaku_mulai DATE NOT NULL,
    berlaku_sampai DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Detail Iuran Penggajian (Snapshot)
CREATE TABLE IF NOT EXISTS detail_iuran_penggajian (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    penggajian_id INT UNSIGNED NOT NULL,
    program ENUM('BPJS_KESEHATAN', 'JHT', 'JKK', 'JKM', 'JP', 'JKP') NOT NULL,
    payer ENUM('EMPLOYEE', 'EMPLOYER', 'GOVERNMENT', 'RECOMPOSITION') NOT NULL,
    dasar_upah DECIMAL(15,2) NOT NULL,
    tarif DECIMAL(8,6) NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    rule_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_detail_iuran_penggajian
        FOREIGN KEY (penggajian_id) REFERENCES penggajian(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
