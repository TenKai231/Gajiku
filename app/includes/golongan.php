<?php
declare(strict_types=1);

/**
 * Daftar tingkatan golongan terstandar perusahaan beserta estimasi uang makan dan tunjangan default
 */
function getStandardGolonganList(): array
{
    return [
        'Golongan I/A'   => ['uang_makan' => 30000.0, 'tunjangan' => 300000.0],
        'Golongan I/B'   => ['uang_makan' => 35000.0, 'tunjangan' => 400000.0],
        'Golongan I/C'   => ['uang_makan' => 35000.0, 'tunjangan' => 500000.0],
        'Golongan I/D'   => ['uang_makan' => 40000.0, 'tunjangan' => 600000.0],
        'Golongan II/A'  => ['uang_makan' => 40000.0, 'tunjangan' => 750000.0],
        'Golongan II/B'  => ['uang_makan' => 45000.0, 'tunjangan' => 850000.0],
        'Golongan II/C'  => ['uang_makan' => 45000.0, 'tunjangan' => 950000.0],
        'Golongan II/D'  => ['uang_makan' => 50000.0, 'tunjangan' => 1000000.0],
        'Golongan III/A' => ['uang_makan' => 50000.0, 'tunjangan' => 1250000.0],
        'Golongan III/B' => ['uang_makan' => 55000.0, 'tunjangan' => 1500000.0],
        'Golongan III/C' => ['uang_makan' => 55000.0, 'tunjangan' => 1750000.0],
        'Golongan III/D' => ['uang_makan' => 60000.0, 'tunjangan' => 2000000.0],
        'Golongan IV/A'  => ['uang_makan' => 60000.0, 'tunjangan' => 2250000.0],
        'Golongan IV/B'  => ['uang_makan' => 65000.0, 'tunjangan' => 2500000.0],
        'Golongan IV/C'  => ['uang_makan' => 70000.0, 'tunjangan' => 2750000.0],
        'Golongan IV/D'  => ['uang_makan' => 75000.0, 'tunjangan' => 3000000.0],
        'Golongan I'     => ['uang_makan' => 30000.0, 'tunjangan' => 750000.0],
        'Golongan II'    => ['uang_makan' => 40000.0, 'tunjangan' => 1000000.0],
        'Golongan III'   => ['uang_makan' => 50000.0, 'tunjangan' => 2000000.0],
        'Golongan IV'    => ['uang_makan' => 60000.0, 'tunjangan' => 3000000.0],
    ];
}

function fetchAllGolongan(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM golongan ORDER BY id ASC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchGolonganById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM golongan WHERE id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

function golonganExists(PDO $pdo, string $namaGolongan, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM golongan WHERE LOWER(nama_golongan) = LOWER(?)';
    $params = [$namaGolongan];
    if ($excludeId !== null) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() !== false;
}
