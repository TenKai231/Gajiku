# Sistem Informasi Penggajian Otomatis

Aplikasi web penggajian sederhana untuk perusahaan kecil/menengah sebagai proyek perkuliahan. Fondasi proyek ini menyiapkan struktur kode, database, dan lingkungan pengembangan agar tim bisa mengembangkan fitur secara bertahap.

## Technology Stack

- PHP Native
- MySQL/MariaDB
- PDO
- HTML
- Bootstrap 5
- Vanilla JavaScript
- Docker (opsional untuk pengembangan)
- XAMPP (untuk tim non-Docker)

## Struktur Proyek

```text
sistem-informasi-penggajian/
│
├── app/
│   ├── config/
│   ├── includes/
│   ├── pages/
│   ├── actions/
│   └── assets/
│
├── database/
│   ├── schema.sql
│   └── seed.sql
│
├── docker/
│   └── php/
│       └── Dockerfile
│
├── compose.yaml
├── .env.example
├── .gitignore
└── README.md
```

## Requirements

### Developer Docker (Arch Linux)

- Docker
- Docker Compose
- Git

### Developer XAMPP (Windows)

- XAMPP (Apache + MySQL/MariaDB)
- Git

## Menjalankan dengan Docker

1. Salin file environment:

   ```bash
   cp .env.example .env
   ```

2. Sesuaikan `.env` untuk Docker (minimal):

   ```env
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=penggajian_db
   DB_USERNAME=root
   DB_PASSWORD=root
   DB_ROOT_PASSWORD=root
   ```

3. Jalankan container:

   ```bash
   docker compose up -d
   ```

4. Import database:

   ```bash
   docker compose exec -T db mariadb -uroot -proot < database/schema.sql
   docker compose exec -T db mariadb -uroot -proot < database/seed.sql
   ```

5. Buka aplikasi:

   ```text
   http://localhost:8080
   ```

## Menjalankan dengan XAMPP (tanpa Docker)

1. Clone repository.
2. Copy folder project ke `xampp/htdocs`.
3. Start Apache dan MySQL dari XAMPP Control Panel.
4. Buat database `penggajian_db` (jika belum ada).
5. Import `database/schema.sql`.
6. Import `database/seed.sql`.
7. Copy `.env.example` menjadi `.env`, lalu sesuaikan (contoh XAMPP):

   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=penggajian_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

8. Akses aplikasi via localhost sesuai path project di `htdocs`.

## Catatan Fondasi Keamanan

- Password wajib disimpan menggunakan `password_hash()` dan diverifikasi dengan `password_verify()`.
- Semua query yang menerima input pengguna harus memakai PDO prepared statements.
- Kredensial database disimpan melalui environment variable, bukan hardcoded di source code.
- Pondasi autentikasi disiapkan di `app/includes/auth.php` berbasis PHP session.

## Catatan Pengembangan

Tahap ini hanya menyiapkan fondasi proyek. Fitur seperti login UI, dashboard, CRUD, penggajian, laporan, PDF/Excel belum diimplementasikan.
