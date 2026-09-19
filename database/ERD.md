# ERD — Gajiku (penggajian_db)

> **Status:** Aktual — mencerminkan `database/schema.sql`.
> Menggantikan ERD PDF lama (`dbdiagram.io`) yang sudah outdated
> (role `ADMIN,HR` tanpa golongan/pajak/BPJS).

---

## Diagram Relasi

```mermaid
erDiagram
    users ||--o{ penggajian : "diproses_oleh"

    jabatan ||--o{ karyawan : "jabatan_id"
    golongan ||--o{ karyawan : "golongan_id"

    karyawan ||--o{ absensi : "karyawan_id"
    karyawan ||--o{ penggajian : "karyawan_id"
    karyawan ||--o| data_pajak_karyawan : "karyawan_id"
    karyawan ||--o| data_bpjs_karyawan : "karyawan_id"

    penggajian ||--o{ detail_iuran_penggajian : "penggajian_id"
    penggajian ||--o{ penggajian : "koreksi_dari_id"

    users {
        int      id PK
        varchar  username UK
        varchar  password
        enum     role "ADMIN|HR|PEMIMPIN"
        timestamp created_at
        timestamp updated_at
    }

    jabatan {
        int      id PK
        varchar  nama_jabatan
        decimal  gaji_pokok
        decimal  tunjangan_default
        timestamp created_at
        timestamp updated_at
    }

    golongan {
        int      id PK
        varchar  nama_golongan UK
        decimal  uang_makan
        decimal  tunjangan
        timestamp created_at
        timestamp updated_at
    }

    karyawan {
        int      id PK
        varchar  nip UK
        varchar  nama
        enum     jenis_kelamin "L|P"
        date     tanggal_lahir
        date     tanggal_masuk
        int      jabatan_id FK
        int      golongan_id FK
        enum     status "Aktif|Nonaktif"
        timestamp created_at
        timestamp updated_at
    }

    absensi {
        int      id PK
        int      karyawan_id FK
        date     tanggal
        enum     status "Hadir|Sakit|Izin|Alpha|Cuti"
        time     jam_masuk
        time     jam_pulang
        varchar  keterangan
        timestamp created_at
    }

    data_pajak_karyawan {
        int      id PK
        int      karyawan_id FK,UK
        varchar  nik UK
        varchar  npwp
        enum     status_ptkp "TK/0..K/3"
        enum     kategori_ter "A|B|C"
        timestamp created_at
        timestamp updated_at
    }

    tarif_ter {
        int      id PK
        enum     kategori "A|B|C"
        decimal  min_bruto
        decimal  max_bruto
        decimal  tarif
        date     berlaku_mulai
        date     berlaku_sampai
    }

    tarif_pasal17 {
        int      id PK
        decimal  min_pkp
        decimal  max_pkp
        decimal  tarif
        date     berlaku_mulai
        date     berlaku_sampai
    }

    data_bpjs_karyawan {
        int      id PK
        int      karyawan_id FK,UK
        varchar  nomor_kesehatan
        varchar  nomor_ketenagakerjaan
        enum     status_kesehatan "AKTIF|TIDAK_AKTIF"
        enum     status_ketenagakerjaan "AKTIF|TIDAK_AKTIF"
        enum     risk_level_jkk "SANGAT_RENDAH..SANGAT_TINGGI"
        timestamp created_at
        timestamp updated_at
    }

    aturan_iuran {
        int      id PK
        enum     program "BPJS_KESEHATAN|JHT|JKK|JKM|JP|JKP"
        enum     payer "EMPLOYEE|EMPLOYER|GOVERNMENT|RECOMPOSITION"
        enum     risk_level
        decimal  rate
        enum     wage_base
        decimal  max_wage
        date     berlaku_mulai
        date     berlaku_sampai
    }

    detail_iuran_penggajian {
        int      id PK
        int      penggajian_id FK
        enum     program
        enum     payer
        decimal  dasar_upah
        decimal  tarif
        decimal  jumlah
        int      rule_id
        timestamp created_at
    }

    penggajian {
        int      id PK
        int      karyawan_id FK
        char     periode "MM-YYYY"
        smallint revisi
        int      koreksi_dari_id FK
        decimal  gaji_pokok
        decimal  total_tunjangan
        decimal  total_uang_makan
        decimal  potongan_uang_makan
        decimal  gaji_kotor
        decimal  pph21
        decimal  potongan_bpjs
        decimal  total_tanggungan_perusahaan
        varchar  nama_jabatan_snapshot
        varchar  nama_golongan_snapshot
        varchar  status_ptkp_snapshot
        char     kategori_ter_snapshot
        decimal  potongan_lain
        decimal  total_potongan
        decimal  gaji_bersih
        date     tanggal_proses
        int      diproses_oleh FK
        enum     status "Draft|Processed|Paid|Corrected"
        datetime dikoreksi_pada
        timestamp created_at
    }
```

---

## Constraint Penting

| Tabel | Constraint | Tujuan |
|---|---|---|
| `users` | `username UNIQUE` | Cegah username ganda |
| `karyawan` | `nip UNIQUE` | Cegah NIP ganda |
| `golongan` | `nama_golongan UNIQUE` | Cegah golongan ganda |
| `absensi` | `UNIQUE(karyawan_id, tanggal)` | 1 karyawan = 1 absensi/hari |
| `penggajian` | `UNIQUE(karyawan_id, periode, revisi)` | Cegah payroll dobel (per revisi) |
| `data_pajak_karyawan` | `karyawan_id UNIQUE`, `nik UNIQUE` | 1 profil pajak/karyawan |
| `data_bpjs_karyawan` | `karyawan_id UNIQUE` | 1 profil BPJS/karyawan |

## Foreign Key

```
karyawan.jabatan_id            → jabatan.id          (ON DELETE RESTRICT)
karyawan.golongan_id           → golongan.id         (ON DELETE RESTRICT)
absensi.karyawan_id            → karyawan.id         (ON DELETE CASCADE)
penggajian.karyawan_id         → karyawan.id         (ON DELETE RESTRICT)
penggajian.diproses_oleh       → users.id            (ON DELETE SET NULL)
penggajian.koreksi_dari_id     → penggajian.id       (ON DELETE RESTRICT)
data_pajak_karyawan.karyawan_id→ karyawan.id         (ON DELETE CASCADE)
data_bpjs_karyawan.karyawan_id → karyawan.id         (ON DELETE CASCADE)
detail_iuran_penggajian.penggajian_id → penggajian.id (ON DELETE CASCADE)
```

> `aturan_iuran` & `tarif_*` **tidak** punya FK — berupa tabel referensi/aturan
> (rules engine) dengan *effective dating* (`berlaku_mulai`, `berlaku_sampai`).

---

## Alur Data

```
jabatan ─┐
         ├─► karyawan ─┬─► absensi ──────────────────┐
golongan ┘             │                            │
                       ├─► data_pajak_karyawan ─┐   │
                       └─► data_bpjs_karyawan ──┼───┤
                                                │   │
  tarif_ter ─┐                                  │   │
tarif_pasal17┼─► PPh 21 Engine ─────────────────┘   │
 aturan_iuran┘   BPJS Engine ──────────────────────► │
                                                     ▼
                                              PAYROLL ENGINE
                                                     │
                                                     ▼
                                          penggajian (snapshot)
                                                     │
                                    ┌────────────────┴────────────────┐
                                    ▼                                 ▼
                      detail_iuran_penggajian                 penggajian (koreksi/revisi)
                          (snapshot BPJS)
```

---

## Catatan

- `penggajian` menyimpan **snapshot** — perubahan master tidak mengubah payroll historis.
- `revisi` + `koreksi_dari_id` mendukung workflow koreksi (`start-correction.php`).
- Rekap & laporan hanya menampilkan revisi aktif (status `<> 'Corrected'`).
