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
