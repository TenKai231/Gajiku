# Sistem Informasi Penggajian Otomatis (Gajiku)

Aplikasi web penggajian sederhana berbasis PHP Native untuk perusahaan kecil/menengah. Dirancang sebagai proyek perkuliahan, fondasi proyek ini menyiapkan struktur kode, database, pengujian (*testing*), dan lingkungan pengembangan agar fitur-fitur dapat dikembangkan secara bertahap dan aman.

## Technology Stack

- **Backend:** PHP Native (tanpa framework)
- **Database:** MySQL/MariaDB (Akses menggunakan PDO + Prepared Statements)
- **Frontend:** HTML, Bootstrap 5, Vanilla JavaScript
- **Testing:** Bruno (API & HTTP Flow Testing)
- **Environment:** Docker (opsional/rekomendasi) atau XAMPP (untuk tim non-Docker)

## Struktur Proyek

```text
sistem-informasi-penggajian/
│
├── app/
│   ├── actions/          # (Opsional) Penampung logika pemrosesan form
│   ├── config/           # Konfigurasi database & rules payroll
│   ├── includes/         # Modul auth, session, helper
│   ├── pages/            # Halaman UI internal (dashboard, CRUD entitas)
│   └── assets/           # CSS, JS, dan img tĩnh
│
├── database/
│   ├── schema.sql
│   ├── seed.sql
│   └── demo_users.sql    # Kredensial akun untuk demo (Admin & HR)
│
├── public/               # ENTRY POINTS / Document Root (Apache)
│   ├── index.php         # Entry point Dashboard
│   ├── login.php         # Halaman autentikasi
│   ├── logout.php        # Aksi logout
│   ├── jabatan.php       # Entry point fitur Jabatan
│   ├── karyawan.php      # Entry point fitur Karyawan
│   ├── absensi.php       # Entry point fitur Absensi
│   └── payroll.php       # Entry point fitur Penggajian
│
├── testing/
│   └── bruno/            # Kumpulan koleksi Bruno test (Auth, CRUD, Dll)
│
├── docker/
├── compose.yaml
├── .env.example
├── .gitignore
└── README.md
```

## Update Terbaru (Agustus 2026)
*   **Keamanan & Entry Point**: Memisahkan direktori web ter-ekspos (`public/`) dengan direktori *backend logic* (`app/`). Web server dikonfigurasi untuk hanya merender apa yang ada di folder `public/`.
*   **Autentikasi Aktif**: Implementasi `requireAuth()` dan `requireRole()` yang solid menggunakan *PHP Session* dan PDO Prepared Statement di `app/includes/auth.php`.
*   **Test Matrix Komprehensif (Bruno)**: Menambahkan koleksi pengujian Bruno untuk *Authentication*, serta *Test Matrix* kompleks (CREATE, READ, UPDATE, DELETE, AUTHORIZATION) untuk entitas Jabatan, Karyawan, Absensi, dan Payroll.

---

## Requirements

### Developer Docker (Arch Linux / OS Lain)

- Docker
- Docker Compose
- Git
- (Opsional) Bruno / Bruno CLI untuk menjalankan Automated Test

### Developer XAMPP (Windows)

- XAMPP (Apache + MySQL/MariaDB)
- Git

## Menjalankan dengan Docker (Rekomendasi)

1. Salin file environment:
   ```bash
   cp .env.example .env
   ```
2. Sesuaikan `.env` (Jika memakai default docker-compose):
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
   docker compose exec -T db mariadb -uroot -proot < database/demo_users.sql
   ```
5. Buka aplikasi: `http://localhost:8080/login.php`

> **Catatan Demo Akun:**
> *   Admin: `admin` / `admin12345`
> *   HRD: `hrd` / `hrd12345`

## Menjalankan Pengujian (Bruno Test)
Koleksi pengujian HTTP (Automated) tersedia di dalam folder `testing/bruno/`.
1. Instal aplikasi **Bruno** (Open-source API Client).
2. Buka folder/koleksi di `testing/bruno/` (seperti `auth`, `jabatan`, dll).
3. **Penting:** Karena aplikasi ini berbasis Session PHP, hindari menekan tombol "Run All" apabila *sequence* belum terurut (Test Negatif > Login Valid > Logout). Jika ada request yang gagal/403, pastikan Anda memiliki *cookie* session valid dari file _Login Valid_.

## Catatan Fondasi Keamanan

- Password wajib disimpan menggunakan `password_hash()` dan diverifikasi dengan `password_verify()`.
- Semua query yang menerima input pengguna harus memakai PDO prepared statements.
- Kredensial database disimpan melalui environment variable, bukan hardcoded di source code.
- Otorisasi dibatasi menggunakan helper `requireRole('ADMIN')` atau `'HR'` yang memeriksa array sesi saat runtime.
