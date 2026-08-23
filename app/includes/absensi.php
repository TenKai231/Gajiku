<?php
declare(strict_types=1);

function fetchAbsensiByPeriode(PDO $pdo, int $bulan, int $tahun): array
{
    // Mengambil absensi untuk satu bulan/tahun tertentu
    // Bulan 1-12, Tahun misal 2026
    $stmt = $pdo->prepare(
        'SELECT a.*, k.nama, k.nip
         FROM absensi a
         JOIN karyawan k ON a.karyawan_id = k.id
         WHERE MONTH(a.tanggal) = :bulan AND YEAR(a.tanggal) = :tahun
         ORDER BY a.tanggal DESC, k.nama ASC'
    );
    $stmt->execute([
        ':bulan' => $bulan,
        ':tahun' => $tahun
    ]);
    return $stmt->fetchAll();
}

function fetchAbsensiById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT a.*, k.nama, k.nip
         FROM absensi a
         JOIN karyawan k ON a.karyawan_id = k.id
         WHERE a.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $absensi = $stmt->fetch();
    return $absensi === false ? null : $absensi;
}

function absensiExists(PDO $pdo, int $karyawanId, string $tanggal, ?int $excludeId = null): bool
{
    // Mengecek apakah karyawan sudah absen di tanggal tersebut (mencegah duplikat)
    $sql = 'SELECT id FROM absensi WHERE karyawan_id = :karyawan_id AND tanggal = :tanggal';
    $params = [
        ':karyawan_id' => $karyawanId,
        ':tanggal' => $tanggal
    ];

    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params[':exclude_id'] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() !== false;
}

function validateAbsensiInput(array $input): array
{
    $form = [
        'karyawan_id' => trim((string) ($input['karyawan_id'] ?? '')),
        'tanggal' => trim((string) ($input['tanggal'] ?? '')),
        'status' => trim((string) ($input['status'] ?? '')),
        'jam_masuk' => trim((string) ($input['jam_masuk'] ?? '')),
        'jam_pulang' => trim((string) ($input['jam_pulang'] ?? '')),
        'keterangan' => trim((string) ($input['keterangan'] ?? '')),
    ];

    $errors = [];

    // Karyawan
    $karyawanId = (int) $form['karyawan_id'];
    if ($karyawanId <= 0) {
        $errors['karyawan_id'] = 'Karyawan wajib dipilih.';
    }

    // Tanggal
    if ($form['tanggal'] === '') {
        $errors['tanggal'] = 'Tanggal wajib diisi.';
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $form['tanggal']);
        if (!$d || $d->format('Y-m-d') !== $form['tanggal']) {
            $errors['tanggal'] = 'Format tanggal tidak valid.';
        }
    }

    // Status
    $validStatus = ['Hadir', 'Sakit', 'Izin', 'Alpha', 'Cuti'];
    if (!in_array($form['status'], $validStatus, true)) {
        $errors['status'] = 'Status absensi tidak valid.';
    }

    // Jam Masuk / Pulang (Hanya divalidasi ketat jika Hadir)
    // Jika tidak hadir (sakit, dll), biarkan kosong atau set null
    $jamMasuk = null;
    $jamPulang = null;

    if ($form['status'] === 'Hadir') {
        if ($form['jam_masuk'] === '') {
            $errors['jam_masuk'] = 'Jam masuk wajib diisi jika status Hadir.';
        } else {
            // Validasi format HH:MM
            if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $form['jam_masuk'])) {
                // Mencoba validasi HH:MM:SS jika browser mengirim detik
                if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $form['jam_masuk'])) {
                    $errors['jam_masuk'] = 'Format jam masuk tidak valid.';
                } else {
                     $jamMasuk = substr($form['jam_masuk'], 0, 5); // Ambil HH:MM saja
                }
            } else {
                $jamMasuk = $form['jam_masuk'];
            }
        }

        if ($form['jam_pulang'] !== '') {
            if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $form['jam_pulang'])) {
                 if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $form['jam_pulang'])) {
                    $errors['jam_pulang'] = 'Format jam pulang tidak valid.';
                 } else {
                     $jamPulang = substr($form['jam_pulang'], 0, 5);
                 }
            } else {
                $jamPulang = $form['jam_pulang'];
            }
        }
    }

    return [
        'errors' => $errors,
        'form' => $form,
        'data' => [
            'karyawan_id' => $karyawanId,
            'tanggal' => $form['tanggal'],
            'status' => $form['status'],
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => $jamPulang,
            'keterangan' => $form['keterangan'] === '' ? null : $form['keterangan'],
        ],
    ];
}

function createAbsensi(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO absensi (karyawan_id, tanggal, status, jam_masuk, jam_pulang, keterangan)
         VALUES (:karyawan_id, :tanggal, :status, :jam_masuk, :jam_pulang, :keterangan)'
    );
    $stmt->execute([
        ':karyawan_id' => $data['karyawan_id'],
        ':tanggal' => $data['tanggal'],
        ':status' => $data['status'],
        ':jam_masuk' => $data['jam_masuk'],
        ':jam_pulang' => $data['jam_pulang'],
        ':keterangan' => $data['keterangan'],
    ]);
}

function updateAbsensi(PDO $pdo, int $id, array $data): void
{
    $stmt = $pdo->prepare(
        'UPDATE absensi
         SET karyawan_id = :karyawan_id,
             tanggal = :tanggal,
             status = :status,
             jam_masuk = :jam_masuk,
             jam_pulang = :jam_pulang,
             keterangan = :keterangan
         WHERE id = :id'
    );
    $stmt->execute([
        ':karyawan_id' => $data['karyawan_id'],
        ':tanggal' => $data['tanggal'],
        ':status' => $data['status'],
        ':jam_masuk' => $data['jam_masuk'],
        ':jam_pulang' => $data['jam_pulang'],
        ':keterangan' => $data['keterangan'],
        ':id' => $id,
    ]);
}

function deleteAbsensi(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM absensi WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function isTerlambat(string $jamMasuk, string $jamBatas = '08:00'): bool
{
    // Jika jam_masuk > jam_batas maka terlambat
    return strtotime($jamMasuk) > strtotime($jamBatas);
}
