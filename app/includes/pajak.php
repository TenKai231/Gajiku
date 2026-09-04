<?php
declare(strict_types=1);

function getTaxProfile(PDO $pdo, int $karyawanId): ?array {
    $stmt = $pdo->prepare('SELECT * FROM data_pajak_karyawan WHERE karyawan_id = ?');
    $stmt->execute([$karyawanId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

function determineTERCategory(string $statusPtkp): string {
    $statusPtkp = strtoupper($statusPtkp);

    if (in_array($statusPtkp, ['TK/0', 'TK/1', 'K/0'], true)) {
        return 'A';
    }

    if (in_array($statusPtkp, ['TK/2', 'TK/3', 'K/1', 'K/2'], true)) {
        return 'B';
    }

    if ($statusPtkp === 'K/3') {
        return 'C';
    }

    throw new InvalidArgumentException("Status PTKP tidak valid: {$statusPtkp}");
}

function findTERRate(PDO $pdo, string $kategori, float $grossIncome, string $processDate): float {
    // Find the correct rate based on category and gross income range
    $stmt = $pdo->prepare(
        'SELECT tarif FROM tarif_ter
         WHERE kategori = :kategori
         AND min_bruto <= :bruto1
         AND (max_bruto >= :bruto2 OR max_bruto IS NULL)
         AND berlaku_mulai <= :tanggal1
         AND (berlaku_sampai >= :tanggal2 OR berlaku_sampai IS NULL)
         LIMIT 1'
    );
    $stmt->execute([
        ':kategori' => $kategori,
        ':bruto1' => $grossIncome,
        ':bruto2' => $grossIncome,
        ':tanggal1' => $processDate,
        ':tanggal2' => $processDate
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? (float) $result['tarif'] : 0.0;
}
