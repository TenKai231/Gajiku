<?php

declare(strict_types=1);

function fetchAllJabatan(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, nama_jabatan, gaji_pokok, tunjangan_default
         FROM jabatan
         ORDER BY id ASC'
    );

    return $stmt->fetchAll();
}

function fetchJabatanById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, nama_jabatan, gaji_pokok, tunjangan_default
         FROM jabatan
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $jabatan = $stmt->fetch();

    return $jabatan === false ? null : $jabatan;
}

function jabatanNameExists(PDO $pdo, string $namaJabatan, ?int $excludeId = null): bool
{
    $sql = 'SELECT id
            FROM jabatan
            WHERE nama_jabatan = :nama_jabatan';
    $params = [':nama_jabatan' => $namaJabatan];

    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params[':exclude_id'] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch() !== false;
}

function validateJabatanInput(array $input): array
{
    $form = [
        'nama_jabatan' => trim((string) ($input['nama_jabatan'] ?? '')),
        'gaji_pokok' => trim((string) ($input['gaji_pokok'] ?? '')),
        'tunjangan_default' => trim((string) ($input['tunjangan_default'] ?? '')),
    ];

    $errors = [];

    if ($form['nama_jabatan'] === '') {
        $errors['nama_jabatan'] = 'Nama jabatan wajib diisi.';
    } elseif (strlen($form['nama_jabatan']) > 100) {
        $errors['nama_jabatan'] = 'Nama jabatan maksimal 100 karakter.';
    }

    $gajiPokok = normalizeJabatanAmount($form['gaji_pokok']);
    if ($gajiPokok === null) {
        $errors['gaji_pokok'] = 'Gaji pokok harus berupa angka.';
    } elseif ($gajiPokok < 0) {
        $errors['gaji_pokok'] = 'Gaji pokok tidak boleh negatif.';
    }

    $tunjanganDefault = normalizeJabatanAmount($form['tunjangan_default']);
    if ($tunjanganDefault === null) {
        $errors['tunjangan_default'] = 'Tunjangan harus berupa angka.';
    } elseif ($tunjanganDefault < 0) {
        $errors['tunjangan_default'] = 'Tunjangan tidak boleh negatif.';
    }

    return [
        'errors' => $errors,
        'form' => $form,
        'data' => [
            'nama_jabatan' => $form['nama_jabatan'],
            'gaji_pokok' => $gajiPokok,
            'tunjangan_default' => $tunjanganDefault,
        ],
    ];
}

function normalizeJabatanAmount(string $value): ?float
{
    $normalized = str_replace([',', ' '], '', $value);
    if ($normalized === '' || !is_numeric($normalized)) {
        return null;
    }

    return (float) $normalized;
}

function createJabatan(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO jabatan (nama_jabatan, gaji_pokok, tunjangan_default)
         VALUES (:nama_jabatan, :gaji_pokok, :tunjangan_default)'
    );
    $stmt->execute([
        ':nama_jabatan' => $data['nama_jabatan'],
        ':gaji_pokok' => $data['gaji_pokok'],
        ':tunjangan_default' => $data['tunjangan_default'],
    ]);
}

function updateJabatan(PDO $pdo, int $id, array $data): void
{
    $stmt = $pdo->prepare(
        'UPDATE jabatan
         SET nama_jabatan = :nama_jabatan,
             gaji_pokok = :gaji_pokok,
             tunjangan_default = :tunjangan_default
         WHERE id = :id'
    );
    $stmt->execute([
        ':nama_jabatan' => $data['nama_jabatan'],
        ':gaji_pokok' => $data['gaji_pokok'],
        ':tunjangan_default' => $data['tunjangan_default'],
        ':id' => $id,
    ]);
}

function deleteJabatan(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM jabatan WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function formatCurrency(float $amount): string
{
    return 'Rp' . number_format($amount, 0, ',', '.');
}
