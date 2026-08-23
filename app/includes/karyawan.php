<?php
declare(strict_types=1);

function fetchAllKaryawan(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT k.*, j.nama_jabatan
         FROM karyawan k
         LEFT JOIN jabatan j ON k.jabatan_id = j.id
         ORDER BY k.nip ASC'
    );
    return $stmt->fetchAll();
}

function fetchKaryawanById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT k.*, j.nama_jabatan, j.gaji_pokok, j.tunjangan_default
         FROM karyawan k
         LEFT JOIN jabatan j ON k.jabatan_id = j.id
         WHERE k.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $karyawan = $stmt->fetch();
    return $karyawan === false ? null : $karyawan;
}

function nipExists(PDO $pdo, string $nip, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM karyawan WHERE nip = :nip';
    $params = [':nip' => $nip];

    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params[':exclude_id'] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() !== false;
}

function validateKaryawanInput(array $input): array
{
    $form = [
        'nip' => trim((string) ($input['nip'] ?? '')),
        'nama' => trim((string) ($input['nama'] ?? '')),
        'jenis_kelamin' => trim((string) ($input['jenis_kelamin'] ?? '')),
        'tanggal_lahir' => trim((string) ($input['tanggal_lahir'] ?? '')),
        'tanggal_masuk' => trim((string) ($input['tanggal_masuk'] ?? '')),
        'jabatan_id' => trim((string) ($input['jabatan_id'] ?? '')),
        'status' => trim((string) ($input['status'] ?? '')),
    ];

    $errors = [];

    // NIP
    if ($form['nip'] === '') {
        $errors['nip'] = 'NIP wajib diisi.';
    } elseif (strlen($form['nip']) > 50) {
        $errors['nip'] = 'NIP maksimal 50 karakter.';
    }

    // Nama
    if ($form['nama'] === '') {
        $errors['nama'] = 'Nama wajib diisi.';
    } elseif (strlen($form['nama']) > 100) {
        $errors['nama'] = 'Nama maksimal 100 karakter.';
    }

    // Jenis Kelamin
    if (!in_array($form['jenis_kelamin'], ['L', 'P'], true)) {
        $errors['jenis_kelamin'] = 'Jenis kelamin tidak valid.';
    }

    // Tanggal Lahir
    if ($form['tanggal_lahir'] === '') {
        $errors['tanggal_lahir'] = 'Tanggal lahir wajib diisi.';
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $form['tanggal_lahir']);
        if (!$d || $d->format('Y-m-d') !== $form['tanggal_lahir']) {
            $errors['tanggal_lahir'] = 'Format tanggal lahir tidak valid.';
        }
    }

    // Tanggal Masuk
    if ($form['tanggal_masuk'] === '') {
        $errors['tanggal_masuk'] = 'Tanggal masuk wajib diisi.';
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $form['tanggal_masuk']);
        if (!$d || $d->format('Y-m-d') !== $form['tanggal_masuk']) {
            $errors['tanggal_masuk'] = 'Format tanggal masuk tidak valid.';
        }
    }

    // Jabatan
    $jabatanId = (int) $form['jabatan_id'];
    if ($jabatanId <= 0) {
        $errors['jabatan_id'] = 'Jabatan wajib dipilih.';
    }

    // Status
    if (!in_array($form['status'], ['Aktif', 'Nonaktif'], true)) {
        $errors['status'] = 'Status tidak valid.';
    }

    return [
        'errors' => $errors,
        'form' => $form,
        'data' => [
            'nip' => $form['nip'],
            'nama' => $form['nama'],
            'jenis_kelamin' => $form['jenis_kelamin'],
            'tanggal_lahir' => $form['tanggal_lahir'],
            'tanggal_masuk' => $form['tanggal_masuk'],
            'jabatan_id' => $jabatanId,
            'status' => $form['status'],
        ],
    ];
}

function createKaryawan(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO karyawan (nip, nama, jenis_kelamin, tanggal_lahir, tanggal_masuk, jabatan_id, status)
         VALUES (:nip, :nama, :jenis_kelamin, :tanggal_lahir, :tanggal_masuk, :jabatan_id, :status)'
    );
    $stmt->execute([
        ':nip' => $data['nip'],
        ':nama' => $data['nama'],
        ':jenis_kelamin' => $data['jenis_kelamin'],
        ':tanggal_lahir' => $data['tanggal_lahir'],
        ':tanggal_masuk' => $data['tanggal_masuk'],
        ':jabatan_id' => $data['jabatan_id'],
        ':status' => $data['status'],
    ]);
}

function updateKaryawan(PDO $pdo, int $id, array $data): void
{
    $stmt = $pdo->prepare(
        'UPDATE karyawan
         SET nip = :nip,
             nama = :nama,
             jenis_kelamin = :jenis_kelamin,
             tanggal_lahir = :tanggal_lahir,
             tanggal_masuk = :tanggal_masuk,
             jabatan_id = :jabatan_id,
             status = :status
         WHERE id = :id'
    );
    $stmt->execute([
        ':nip' => $data['nip'],
        ':nama' => $data['nama'],
        ':jenis_kelamin' => $data['jenis_kelamin'],
        ':tanggal_lahir' => $data['tanggal_lahir'],
        ':tanggal_masuk' => $data['tanggal_masuk'],
        ':jabatan_id' => $data['jabatan_id'],
        ':status' => $data['status'],
        ':id' => $id,
    ]);
}

function deleteKaryawan(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM karyawan WHERE id = :id');
    $stmt->execute([':id' => $id]);
}
