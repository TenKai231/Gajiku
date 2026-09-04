<?php
declare(strict_types=1);

function fetchAllGolongan(PDO $pdo): array {
    $stmt = $pdo->query('SELECT * FROM golongan ORDER BY id DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchGolonganById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM golongan WHERE id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}