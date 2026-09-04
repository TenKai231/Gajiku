


function calculateBpjs(PDO $pdo, int $karyawanId, float $gajiPokok, float $tunjanganTetap, string $processDate): array {
    $gajiDasar = $gajiPokok + $tunjanganTetap;

    // 1. Get BPJS Profile
    $stmt = $pdo->prepare('SELECT * FROM data_bpjs_karyawan WHERE karyawan_id = ?');
    $stmt->execute([$karyawanId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    $results = [
        'deductions' => [ // Potongan Karyawan (mengurangi THP)
            'BPJS_KESEHATAN' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JHT' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JP' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
        ],
        'contributions' => [ // Tanggungan Perusahaan (TIDAK mengurangi THP)
            'BPJS_KESEHATAN' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JHT' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JP' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JKM' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
            'JKK' => ['amount' => 0.0, 'rate' => 0.0, 'base' => 0.0, 'rule_id' => null],
        ],
        'total_deduction' => 0.0,
        'total_contribution' => 0.0
    ];

    if (!$profile) {
        return $results; // Karyawan tidak terdaftar BPJS
    }

    // 2. Load Active Rules
    $stmt = $pdo->prepare('
        SELECT * FROM aturan_iuran 
        WHERE berlaku_mulai <= :tanggal 
        AND (berlaku_sampai >= :tanggal OR berlaku_sampai IS NULL)
    ');
    $stmt->execute([':tanggal' => $processDate]);
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $riskLevel = $profile['risk_level_jkk'];

    foreach ($rules as $rule) {
        $prog = $rule['program'];
        $payer = $rule['payer'];
        
        // Skip JKK if risk level doesn't match
        if ($prog === 'JKK' && $rule['risk_level'] !== $riskLevel) {
            continue;
        }

        // Determine Wage Base
        $wageBase = $gajiDasar; // default as per requirements: GAJI_POKOK_PLUS_TUNJANGAN_TETAP
        if ($rule['max_wage'] !== null && $wageBase > (float)$rule['max_wage']) {
            $wageBase = (float)$rule['max_wage'];
        }

        $amount = $wageBase * (float)$rule['rate'];

        if ($payer === 'EMPLOYEE' && isset($results['deductions'][$prog])) {
            $results['deductions'][$prog] = [
                'amount' => $amount, 'rate' => (float)$rule['rate'], 'base' => $wageBase, 'rule_id' => $rule['id']
            ];
            $results['total_deduction'] += $amount;
        } elseif ($payer === 'EMPLOYER' && isset($results['contributions'][$prog])) {
            $results['contributions'][$prog] = [
                'amount' => $amount, 'rate' => (float)$rule['rate'], 'base' => $wageBase, 'rule_id' => $rule['id']
            ];
            $results['total_contribution'] += $amount;
        }
    }

    return $results;
}
