<?php
/**
 * Shared summation helpers for data entry (insert data + correct report).
 */

function sumValues($val) {
    if (!$val) {
        return 0;
    }
    $normalized = str_replace('+', ',', $val);
    return array_sum(array_map('floatval', array_filter(explode(',', $normalized), 'trim')));
}

function sumAmaturo($val) {
    return sumValues($val) / 2;
}

function formatStoredValue($input, $isAmaturo = false) {
    $s = sumValues($input);
    if ($isAmaturo) {
        return $input ? $input . ' = ' . $s . ' ÷ 2 = ' . ($s / 2) : '0';
    }
    return $input ? $input . ' = ' . $s : '0';
}

function extractSumFromStored($formatted) {
    if (empty($formatted)) {
        return 0;
    }
    if (preg_match('/=\s*([\d.]+)$/', $formatted, $matches)) {
        return (float) $matches[1];
    }
    return 0;
}

/**
 * Gross Amaturo sum before ÷2 (from stored display string).
 */
function extractAmaturoGrossSumFromStored($formatted) {
    if ($formatted === null || trim((string) $formatted) === '' || trim((string) $formatted) === '0') {
        return 0.0;
    }
    $s = (string) $formatted;
    // Insert format: "input = 10000 ÷ 2 = 5000" — gross is the number before ÷ 2
    if (preg_match('/=\s*([\d.]+)\s*÷\s*2/ui', $s, $m)) {
        return (float) $m[1];
    }
    // Pastor / plain: use entered values before the first "="
    $parts = explode('=', $s, 2);
    $raw = trim($parts[0]);
    if ($raw !== '' && $raw !== '0') {
        return sumValues($raw);
    }
    return extractSumFromStored($s);
}

/** Amaturo amount for comparisons — always gross sum ÷ 2. */
function extractAmaturoComparableSum($formatted) {
    return extractAmaturoGrossSumFromStored($formatted) / 2;
}

/** Split comma-separated list (one segment per Itorero). */
function splitCommaList($val) {
    if ($val === null || trim((string) $val) === '') {
        return [];
    }
    return array_map('trim', explode(',', (string) $val));
}

/**
 * Align a field to N Itorero segments (exact comma count, or single value repeated).
 *
 * @return list<string>|null null when count mismatch
 */
function alignCommaFieldSegments($raw, $expectedCount) {
    if ($expectedCount < 1) {
        return null;
    }
    $parts = splitCommaList($raw);
    if ($parts === []) {
        $parts = array_fill(0, $expectedCount, '');
    } elseif (count($parts) === 1 && $expectedCount > 1) {
        $parts = array_fill(0, $expectedCount, $parts[0]);
    }
    if (count($parts) !== $expectedCount) {
        return null;
    }
    return $parts;
}

/**
 * Resolve Itorero names to rows for one Intara (order preserved).
 *
 * @return list<array{id:int,name:string}>|null
 */
function resolveItoreroNamesForIntara($pdo, $intara_id, array $names) {
    $stmt = $pdo->prepare('SELECT id, name FROM itorero WHERE intara_id = ? ORDER BY name ASC');
    $stmt->execute([(int) $intara_id]);
    $byLower = [];
    foreach ($stmt->fetchAll() as $it) {
        $byLower[mb_strtolower(trim($it['name']))] = $it;
    }
    $resolved = [];
    foreach ($names as $name) {
        $key = mb_strtolower(trim($name));
        if ($key === '' || !isset($byLower[$key])) {
            return null;
        }
        $resolved[] = [
            'id' => (int) $byLower[$key]['id'],
            'name' => $byLower[$key]['name'],
        ];
    }
    return $resolved;
}
