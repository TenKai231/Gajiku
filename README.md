# Gajiku — Sistem Informasi Penggajian Otomatis

Gajiku adalah aplikasi web penggajian berbasis **PHP Native** untuk kebutuhan internal perusahaan kecil/menengah.
Dokumen ini dirapikan agar alur instalasi, struktur folder, dan cara kerja web lebih mudah dipahami.

## Ringkasan Teknologi

- **Backend:** PHP Native (tanpa framework)
- **Database:** MariaDB/MySQL via PDO + Prepared Statements
- **Frontend:** HTML, Bootstrap 5, Vanilla JavaScript
- **Testing:** Bruno collection (HTTP/API flow)
- **Environment:** Docker (rekomendasi) atau XAMPP

## Struktur Folder (Aktual)

```text
Gajiku/
├── app/
│   ├── actions/      # Handler proses (create/update/delete, logout, dll)
│   ├── config/       # Konfigurasi database dan helper konfigurasi
│   ├── includes/     # Auth, layout, helper session/flash
│   └── pages/        # Halaman UI per modul
├── database/
│   ├── schema.sql
│   ├── seed.sql
│   └── demo_users.sql
├── public/
│   ├── index.php     # Front controller utama setelah login
│   ├── login.php     # Halaman login
│   ├── test-connection.php
│   └── assets/
├── testing/
│   └── bruno/
├── docker/
├── compose.yaml
├── .env.example
└── bpjs_service.php
```

## Cara Kerja Web App (Flow)

1. User membuka `public/login.php`.
2. `login.php` memvalidasi kredensial ke tabel `users` memakai PDO prepared statement.
3. Jika valid, data user disimpan ke `$_SESSION` melalui `loginUser()` di `app/includes/auth.php`, lalu redirect ke `public/index.php`.
4. `public/index.php` menjalankan `requireAuth()` untuk memastikan hanya user login yang bisa mengakses halaman internal.
5. Routing di `index.php`:
   - `?action=...` → memanggil file di `app/actions/...` untuk proses backend.
   - `?page=...` → merender file di `app/pages/...` untuk tampilan.
6. Jika `page` mengandung `print`/`cetak`, halaman dirender tanpa layout utama (mode cetak).
7. Jika user logout, action `app/actions/auth/logout.php` akan menghapus session dan redirect ke `login.php?logout=1`.

## Menjalankan dengan Docker (Rekomendasi)

1. Salin environment file:
   ```bash
   cp .env.example .env
   ```
2. Gunakan nilai default berikut (jika belum diubah):
   ```env
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=penggajian_db
   DB_USERNAME=root
   DB_PASSWORD=root
   DB_ROOT_PASSWORD=root
   ```
3. Jalankan service:
   ```bash
   docker compose up -d
   ```
4. Import database:
   ```bash
   docker compose exec -T db mariadb -uroot -proot < database/schema.sql
   docker compose exec -T db mariadb -uroot -proot < database/seed.sql
   docker compose exec -T db mariadb -uroot -proot < database/demo_users.sql
   ```
5. Akses aplikasi:
   - Login page: `http://localhost:8080/login.php`
   - Dashboard (setelah login): `http://localhost:8080/index.php`

## Demo Akun

- **Admin:** `admin` / `admin12345`
- **HRD:** `hrd` / `hrd12345`

## Menjalankan Testing (Bruno)

Koleksi request ada di `testing/bruno/` (`auth`, `jabatan`, `karyawan`, `absensi`, `payroll`).

Saran urutan test:
1. Login valid
2. Jalankan test modul
3. Logout

Karena aplikasi berbasis session, pastikan cookie login masih valid saat menjalankan request terproteksi.

## Catatan Keamanan Dasar

- Password disimpan dengan `password_hash()` dan dicek dengan `password_verify()`.
- Query yang menerima input user harus memakai prepared statements.
- Kredensial database disimpan di environment variable (`.env`), bukan hardcoded.
- Otorisasi role dilakukan via `requireRole('ADMIN')` / `requireRole('HR')`.
