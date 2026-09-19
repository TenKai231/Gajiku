# Sistem Informasi Penggajian Karyawan (Gajiku)

Aplikasi web penggajian berbasis **PHP Native** untuk perusahaan kecil/menengah.
Studi kasus: **Kantor Jaya Bersama (Furniture & Interior)**.

Fondasi proyek menyiapkan struktur kode, database, pengujian, dan lingkungan
pengembangan agar fitur dapat dikembangkan bertahap dan aman.

> **Sumber kebenaran adalah kode di repo ini.** Dokumen desain di Obsidian Vault
> bersifat aspirasi/riwayat dan sebagian sudah outdated. Lihat `CLAUDE.md`
> bagian "Drift Dokumentasi".

---

## Technology Stack

- **Backend:** PHP Native (tanpa framework)
- **Database:** MySQL/MariaDB — akses via **PDO + Prepared Statements**
- **Frontend:** HTML, Bootstrap 5 + Bootstrap Icons (CDN), Vanilla JavaScript
- **Autentikasi:** PHP Session + `password_hash()` / `password_verify()`
- **Export Excel:** PhpSpreadsheet (via Composer)
- **Cetak PDF:** Print browser + CSS `@media print`
- **Testing:** Bruno (HTTP flow)
- **Environment:** Docker Compose (rekomendasi) atau XAMPP

### Tidak digunakan

```
Laravel / framework PHP lain · React / Vue / Angular · Node.js backend
REST API · Redis · Microservices
```

> `compose.yaml` + `docker/` hanya untuk environment **development**, bukan
> bagian arsitektur aplikasi produksi.

---

## Arsitektur — Front Controller

Aplikasi memakai satu **front controller** di `public/index.php`.

```
GET  /?page=<path>        → render app/pages/<path>.php  (header + navbar + breadcrumb + footer)
GET  /?action=<path>      → jalankan app/actions/<path>.php
GET  /?page=laporan/print → halaman standalone tanpa layout (khusus cetak/PDF)
POST /login.php           → autentikasi
```

- Parameter `page` & `action` disanitasi (regex `[^a-zA-Z0-9_\-\/]`) lalu divalidasi `file_exists()`.
- `page` yang menunjuk direktori otomatis diarahkan ke `<dir>/index.php`.
- Document root Apache **wajib** diarahkan ke folder `public/`.

---

## Struktur Proyek

```text
Gajiku/
├── app/
│   ├── config/
│   │   ├── database.php        # loader .env + getPDO()
│   │   └── rules.php           # aturan payroll dasar
│   ├── includes/
│   │   ├── auth.php            # session, login/logout, guard, flash
│   │   ├── absensi.php
│   │   ├── golongan.php
│   │   ├── jabatan.php
│   │   ├── karyawan.php
│   │   ├── pajak.php           # PPh 21 (TER lookup, kategori)
│   │   ├── payroll.php         # payroll engine + query rekap
│   │   ├── header.php
│   │   ├── navbar.php          # sidebar + topbar (permission-aware)
│   │   └── footer.php
│   ├── actions/
│   │   ├── auth/logout.php
│   │   ├── users/       (store, update, delete)
│   │   ├── karyawan/    (store, update, delete)
│   │   ├── jabatan/     (store, update, delete)
│   │   ├── golongan/    (store, update, delete)
│   │   ├── absensi/     (store, update, delete)
│   │   ├── pajak/       (store)
│   │   ├── bpjs/        (store)
│   │   ├── payroll/     (process, change-status, start-correction)
│   │   └── laporan/     (export_excel)
│   └── pages/
│       ├── dashboard.php
│       ├── users/index.php
│       ├── karyawan/    (index, create, edit, detail)
│       ├── jabatan/     (index, create, edit)
│       ├── golongan/    (index, create, edit)
│       ├── absensi/     (index, create, edit)
│       ├── payroll/     (index, proses, detail)
│       └── laporan/     (index, print)
├── database/
│   ├── schema.sql
│   ├── seed.sql
│   ├── seed_pph21.sql
│   ├── demo_users.sql
│   ├── ERD.md                  # Entity Relationship Diagram (aktual)
│   └── migrations/
├── public/                     # Document Root
│   ├── index.php               # front controller
│   ├── login.php
│   ├── test-connection.php
│   └── assets/ (css, js, img)
├── testing/bruno/              # koleksi Bruno (auth, jabatan, karyawan, absensi, payroll)
├── docker/php/Dockerfile
├── compose.yaml
├── composer.json
├── .env.example
└── README.md
```

---

## Role Pengguna

Role login **hanya tiga** — ini role **akses aplikasi**, bukan jabatan pekerjaan.

| Role       | Akses |
|------------|-------|
| `ADMIN`    | Dashboard, Users, Karyawan, Jabatan, Golongan, Absensi, Payroll, Laporan |
| `HR`       | Dashboard, Karyawan, Absensi, Payroll, Laporan |
| `PEMIMPIN` | Dashboard, Karyawan (lihat), Absensi, Payroll (review/approval), Laporan |

> Role `FINANCE` disebut di dokumen desain tetapi **tidak dipakai** di kode.

---

## Sumber Nilai Kompensasi

```
jabatan.gaji_pokok   → GAJI POKOK
golongan.uang_makan  → UANG MAKAN per hari
golongan.tunjangan   → TUNJANGAN
```

> Berbeda dari Rancangan 1.0 (semua di jabatan) maupun 2.0 (gaji pokok di golongan).
> Lihat `app/actions/payroll/process.php`.

---

## Menjalankan dengan Docker (Rekomendasi)

1. Salin environment:

   ```bash
   cp .env.example .env
   ```

2. Sesuaikan `.env` (default docker-compose):

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

4. Import database (berurutan):

   ```bash
   docker compose exec -T db mariadb -uroot -proot < database/schema.sql
   docker compose exec -T db mariadb -uroot -proot < database/seed.sql
   docker compose exec -T db mariadb -uroot -proot < database/seed_pph21.sql
   docker compose exec -T db mariadb -uroot -proot < database/demo_users.sql
   ```

5. Buka aplikasi: `http://localhost:8080/login.php`

### Akun Demo

| Username   | Password          | Role       |
|------------|-------------------|------------|
| `admin`    | `admin12345`      | `ADMIN`    |
| `hrd`      | `hrd12345`        | `HR`       |
| `pemimpin` | `pemimpin12345`   | `PEMIMPIN` |

> Kredensial demo hanya untuk development/presentasi.

### XAMPP (Windows)

- Arahkan Document Root ke folder `public/`.
- Salin `.env.example` → `.env`, set `DB_HOST=127.0.0.1`.
- Import file SQL di `database/` via phpMyAdmin (urut: schema → seed → seed_pph21 → demo_users).

---

## Konfigurasi Database

Dibaca dari `.env` oleh `app/config/database.php` (bukan hardcoded):

```env
DB_HOST=db          # 'db' saat Docker, '127.0.0.1' saat lokal
DB_PORT=3306
DB_DATABASE=penggajian_db
DB_USERNAME=root
DB_PASSWORD=root
```

Cek koneksi: `http://localhost:8080/test-connection.php`

---

## Pengujian (Bruno)

Koleksi HTTP tersedia di `testing/bruno/`.

1. Instal **Bruno** (open-source API client).
2. Buka folder koleksi (mis. `testing/bruno/auth`).
3. **Penting:** aplikasi berbasis Session PHP, jadi perhatikan urutan:
   test negatif → login valid → request terproteksi → logout.
   Hindari "Run All" tanpa urutan yang benar.

---

## Catatan Fondasi Keamanan

- Password disimpan dengan `password_hash()`, diverifikasi `password_verify()`.
- Semua query yang menerima input user memakai PDO prepared statements.
- Kredensial database lewat environment variable, bukan hardcoded.
- Otorisasi dibatasi helper `requireAuth()`, `requireRole()`, `requireAnyRole()`.
- Menyembunyikan menu **bukan** security — otorisasi tetap dicek di backend.
