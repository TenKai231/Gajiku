DATA DUMMY GAJIKU — JUNI S.D. DESEMBER 2026

Isi:
- jabatan.csv: master jabatan (existing file)
- golongan.csv: diperbarui mengikuti pilihan pada UI: Golongan I/A s.d. IV/D dan Golongan I s.d. IV (20 pilihan)
- karyawan.csv: 20 karyawan; KRY015 dan KRY018 berstatus Nonaktif untuk skenario pengujian
- absensi_juni_desember_2026.csv: absensi hari kerja Senin–Jumat selama Juni–Desember 2026, setelah tanggal masuk karyawan

Skenario uji:
- Rama (KRY001): lebih banyak terlambat/izin/alpha dibanding karyawan lain.
- Ronggur (KRY006): absensi relatif konsisten.
- Nadine (KRY005): baru mulai 2026-09-01, jadi tidak punya absensi sebelum tanggal masuk.
- KRY015 dan KRY018: ditandai Nonaktif pada data master, tetapi absensi November–Desember tetap ada untuk menguji anomali.
- Skenario “gaji tetap diproses untuk karyawan nonaktif” belum dimasukkan ke tabel penggajian; perlu data payroll terpisah karena user meminta absensi dan golongan. Gunakan ini sebagai kasus uji, bukan perilaku yang diinginkan.

Catatan:
- karyawan_id pada CSV absensi diasumsikan mengikuti ID 1–20 setelah karyawan.csv diimpor ke database kosong dengan urutan yang sama.
- Impor jabatan.csv dan golongan.csv sebelum karyawan.csv, lalu absensi_juni_desember_2026.csv.
- Jika database sudah berisi data, jangan impor dengan ID langsung sebelum mencocokkan ID aktual; lebih aman pakai staging table dan mapping NIP -> id.
- Weekend tidak dimasukkan. Hari libur nasional belum dikecualikan.
- Data sepenuhnya fiktif untuk demo/testing.

- penggajian_juni_desember_2026.csv: contoh payroll bulanan untuk karyawan yang sudah masuk hingga periode terkait; termasuk payroll setelah status Nonaktif untuk KRY015 dan KRY018 sebagai anomali pengujian.
- Perhitungan pajak 2% dan BPJS 4% di file payroll hanyalah angka ilustratif untuk demo, bukan kalkulasi hukum/aturan resmi. Periksa ulang dengan engine payroll Gajiku sebelum dipakai.
- Impor penggajian setelah users dan karyawan tersedia. Kolom diproses_oleh/koreksi_dari_id/dikoreksi_pada kosong.
