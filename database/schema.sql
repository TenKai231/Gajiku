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

CREATE TABLE IF NOT EXISTS karyawan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    tanggal_lahir DATE NOT NULL,
    tanggal_masuk DATE NOT NULL,
    jabatan_id INT UNSIGNED NOT NULL,
    status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_karyawan_jabatan
        FOREIGN KEY (jabatan_id) REFERENCES jabatan(id)
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

CREATE TABLE IF NOT EXISTS penggajian (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT UNSIGNED NOT NULL,
    periode CHAR(7) NOT NULL,
    gaji_pokok DECIMAL(15,2) NOT NULL,
    total_tunjangan DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_potongan DECIMAL(15,2) NOT NULL DEFAULT 0,
    gaji_kotor DECIMAL(15,2) NOT NULL,
    gaji_bersih DECIMAL(15,2) NOT NULL,
    tanggal_proses DATE NOT NULL,
    status ENUM('Processed', 'Paid') NOT NULL DEFAULT 'Processed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_penggajian_karyawan_periode UNIQUE (karyawan_id, periode),
    CONSTRAINT fk_penggajian_karyawan
        FOREIGN KEY (karyawan_id) REFERENCES karyawan(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
