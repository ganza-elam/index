<?php
/**
 * Mapato ya Pastoro — static labels, extra field defs, and JSON storage helpers.
 */

function mapatoPastorStaticFieldLabels(): array {
    return [
        'icyacumi' => 'Icyacumi (Grand Total)',
        'icyacumi_cya_cms' => 'Icyacumi cya CFMS',
        'meeting' => 'CM (Meeting)',
        'amaturo' => 'Amaturo (Grand Total)',
        'amaturo_bya_cms' => 'Amaturo ya CFMS',
        'revival' => 'Revival',
        'ss' => 'SS Lesson',
        'filide' => 'Inyubako (Filide)',
        'umusaruro' => 'Umusaruro',
        'ituro' => 'Udutabo twa JA',
        'mifem' => 'Udutabo twa Mifem',
    ];
}

function mapatoPastorStaticFieldKeys(): array {
    return array_keys(mapatoPastorStaticFieldLabels());
}

function mapatoPastorFieldSlug(string $label): string {
    $slug = preg_replace('/[^a-z0-9]+/i', '_', strtolower(trim($label)));
    $slug = trim($slug, '_');
    if ($slug === '') {
        $slug = 'field_' . substr(md5($label . microtime(true)), 0, 8);
    }
    return substr($slug, 0, 80);
}

function ensureMapatoPastorExtraFieldsSchema($pdo) {
    static $done = false;
    if ($done) {
        return;
    }

    $cols = $pdo->query('SHOW COLUMNS FROM mapato_pastor')->fetchAll(PDO::FETCH_COLUMN);
    if ($cols && !in_array('mifem', $cols, true)) {
        $pdo->exec('ALTER TABLE mapato_pastor ADD COLUMN mifem varchar(500) DEFAULT NULL AFTER ituro');
    }
    if ($cols && !in_array('extra_fields', $cols, true)) {
        $pdo->exec('ALTER TABLE mapato_pastor ADD COLUMN extra_fields longtext DEFAULT NULL AFTER mifem');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS mapato_pastor_field_defs (
        id int(11) NOT NULL AUTO_INCREMENT,
        intara_id int(11) NOT NULL,
        month tinyint unsigned NOT NULL,
        field_slug varchar(100) NOT NULL,
        field_label varchar(255) NOT NULL,
        created_at datetime DEFAULT current_timestamp(),
        PRIMARY KEY (id),
        UNIQUE KEY uq_mp_field_def (intara_id, month, field_slug),
        KEY idx_mp_field_intara_month (intara_id, month)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $done = true;
}

function getMapatoPastorFieldDefs($pdo, $intara_id, $month) {
    ensureCorrectReportTables($pdo);
    ensureMapatoPastorExtraFieldsSchema($pdo);
    $stmt = $pdo->prepare(
        'SELECT field_slug, field_label FROM mapato_pastor_field_defs
         WHERE intara_id = ? AND month = ?
         ORDER BY id ASC'
    );
    $stmt->execute([(int) $intara_id, (int) $month]);
    return $stmt->fetchAll();
}

function syncMapatoPastorFieldDefs($pdo, $intara_id, $month, array $labelBySlug) {
    ensureCorrectReportTables($pdo);
    ensureMapatoPastorExtraFieldsSchema($pdo);
    if ($labelBySlug === []) {
        return;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO mapato_pastor_field_defs (intara_id, month, field_slug, field_label)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE field_label = VALUES(field_label)'
    );
    foreach ($labelBySlug as $slug => $label) {
        $label = trim((string) $label);
        if ($slug === '' || $label === '') {
            continue;
        }
        $stmt->execute([(int) $intara_id, (int) $month, (string) $slug, $label]);
    }
}

function decodeMapatoPastorExtraFields($record): array {
    if (empty($record['extra_fields'])) {
        return [];
    }
    $decoded = json_decode((string) $record['extra_fields'], true);
    return is_array($decoded) ? $decoded : [];
}

function encodeMapatoPastorExtraFields(array $fields): ?string {
    if ($fields === []) {
        return null;
    }
    return json_encode($fields, JSON_UNESCAPED_UNICODE);
}

/**
 * First extra field definition with no value entered.
 *
 * @param array<string, string> $valuesBySlug
 * @param array<string, string> $labelBySlug slug => label
 * @return array{slug: string, label: string}|null
 */
function findEmptyMapatoPastorExtraField(array $valuesBySlug, array $labelBySlug): ?array {
    foreach ($labelBySlug as $slug => $label) {
        if (trim((string) ($valuesBySlug[$slug] ?? '')) === '') {
            return ['slug' => (string) $slug, 'label' => (string) $label];
        }
    }
    return null;
}

/**
 * Distinct extra field columns for a set of pastor records (preserves label order).
 *
 * @return list<array{slug:string,label:string}>
 */
function collectMapatoPastorExtraColumns($pdo, array $records): array {
    ensureCorrectReportTables($pdo);
    ensureMapatoPastorExtraFieldsSchema($pdo);
    $seen = [];
    $columns = [];
    $pairs = [];
    foreach ($records as $record) {
        $pairs[(int) ($record['intara_id'] ?? 0) . ':' . (int) ($record['month'] ?? 0)] = [
            (int) ($record['intara_id'] ?? 0),
            (int) ($record['month'] ?? 0),
        ];
    }
    foreach ($pairs as [$intaraId, $month]) {
        if ($intaraId < 1 || $month < 1) {
            continue;
        }
        foreach (getMapatoPastorFieldDefs($pdo, $intaraId, $month) as $def) {
            $slug = $def['field_slug'];
            if (isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $columns[] = ['slug' => $slug, 'label' => $def['field_label']];
        }
    }
    foreach ($records as $record) {
        foreach (decodeMapatoPastorExtraFields($record) as $slug => $stored) {
            if (isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $columns[] = ['slug' => $slug, 'label' => ucwords(str_replace('_', ' ', $slug))];
        }
    }
    return $columns;
}

function mapatoPastorExtraFieldDisplay($record, string $slug): string {
    $extra = decodeMapatoPastorExtraFields($record);
    return htmlspecialchars($extra[$slug] ?? '0');
}

/**
 * Extra columns for Intara+month (same order as Insert Mapato from Pastor dropdown).
 *
 * @return list<array{slug:string,label:string}>
 */
function mapatoPastorExtraColumnsForIntaraMonth($pdo, int $intaraId, int $month, array $records = []): array {
    if ($intaraId < 1 || $month < 1 || $month > 12) {
        return collectMapatoPastorExtraColumns($pdo, $records);
    }
    $columns = [];
    $seen = [];
    foreach (getMapatoPastorFieldDefs($pdo, $intaraId, $month) as $def) {
        $slug = (string) $def['field_slug'];
        if ($slug === '' || isset($seen[$slug])) {
            continue;
        }
        $seen[$slug] = true;
        $columns[] = ['slug' => $slug, 'label' => (string) $def['field_label']];
    }
    foreach (collectMapatoPastorExtraColumns($pdo, $records) as $col) {
        if (isset($seen[$col['slug']])) {
            continue;
        }
        $seen[$col['slug']] = true;
        $columns[] = $col;
    }
    return $columns;
}

/**
 * @return list<string> distinct admin usernames who inserted pastor mapato
 */
function mapatoPastorDistinctInsertedBy(array $records): array {
    $names = [];
    foreach ($records as $record) {
        $username = trim((string) ($record['inserted_by_username'] ?? ''));
        if ($username !== '') {
            $names[$username] = $username;
        }
    }
    return array_values($names);
}

/**
 * Category totals for Mapato ya Pastoro (matches insert form / reports table).
 *
 * @return array{category: array<string, float>, extra: array<string, float>, grand: float, meeting: float}
 */
function computeMapatoPastorCategoryTotals(array $records): array {
    require_once __DIR__ . '/imibare-math.php';
    $category = [
        'icyacumi' => 0.0,
        'icyacumi_cya_cms' => 0.0,
        'total_icyacumi_pair' => 0.0,
        'meeting' => 0.0,
        'amaturo' => 0.0,
        'amaturo_bya_cms' => 0.0,
        'total_amaturo_pair' => 0.0,
        'total_amaturo_half' => 0.0,
        'revival' => 0.0,
        'ss' => 0.0,
        'filide' => 0.0,
        'umusaruro' => 0.0,
        'ituro' => 0.0,
        'mifem' => 0.0,
    ];
    $extra = [];
    $grand = 0.0;
    foreach ($records as $record) {
        $grand += (float) ($record['total'] ?? 0);
        $icyRecu = extractSum($record['icyacumi'] ?? '');
        $icyCfms = extractSum($record['icyacumi_cya_cms'] ?? '');
        $category['icyacumi'] += $icyRecu;
        $category['icyacumi_cya_cms'] += $icyCfms;
        $category['total_icyacumi_pair'] += ($icyRecu + $icyCfms);
        $category['meeting'] += extractSum(mapatoPastorMeeting($record));
        $amaRecu = extractSum($record['amaturo'] ?? '');
        $amaCfms = extractSum($record['amaturo_bya_cms'] ?? '');
        $category['amaturo'] += $amaRecu;
        $category['amaturo_bya_cms'] += $amaCfms;
        $category['total_amaturo_pair'] += ($amaRecu + $amaCfms);
        $category['total_amaturo_half'] += (($amaRecu + $amaCfms) / 2);
        $category['revival'] += extractSum($record['revival'] ?? '');
        $category['ss'] += extractSum($record['ss'] ?? '');
        $category['filide'] += extractSum($record['filide'] ?? '');
        $category['umusaruro'] += extractSum($record['umusaruro'] ?? '');
        $category['ituro'] += extractSum($record['ituro'] ?? '');
        $category['mifem'] += extractSum($record['mifem'] ?? '');
        foreach (decodeMapatoPastorExtraFields($record) as $slug => $stored) {
            if (!isset($extra[$slug])) {
                $extra[$slug] = 0.0;
            }
            $extra[$slug] += extractSum($stored);
        }
    }
    return [
        'category' => $category,
        'extra' => $extra,
        'grand' => $grand,
        'meeting' => $category['meeting'],
    ];
}

function sumMapatoPastorRecordTotal(array $seg, array $extraSegs = []): float {
    require_once __DIR__ . '/imibare-math.php';
    $total = 0.0;
    // NOTE: For Amaturo, business rule is (RECU + CFMS) ÷ 2.
    // All other fields are summed normally.
    $total += sumValues($seg['icyacumi'] ?? '');
    $total += sumValues($seg['icyacumi_cya_cms'] ?? '');
    $total += sumValues($seg['meeting'] ?? '');
    $total += (sumValues($seg['amaturo'] ?? '') + sumValues($seg['amaturo_bya_cms'] ?? '')) / 2;
    $total += sumValues($seg['revival'] ?? '');
    $total += sumValues($seg['ss'] ?? '');
    $total += sumValues($seg['filide'] ?? '');
    $total += sumValues($seg['umusaruro'] ?? '');
    $total += sumValues($seg['ituro'] ?? '');
    $total += sumValues($seg['mifem'] ?? '');
    foreach ($extraSegs as $val) {
        $total += sumValues($val);
    }
    return $total;
}
