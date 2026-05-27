<?php
/**
 * Excel Export Endpoint
 * Uses SheetJS (xlsx) library loaded in the browser
 */

require_once 'config.php';
require_once 'auth.php';
requireLogin();

$intara_id = $_GET['intara_id'] ?? null;
if (isGuestUser()) {
    $assigned = getGuestIntaraId();
    if ($assigned === null) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([]);
        exit;
    }
    $intara_id = (string) $assigned;
}
$itorero_id = $_GET['itorero_id'] ?? null;
$month_filter = $_GET['month'] ?? null;

$selectedIntaraName = getSelectedName($pdo, 'intara', $intara_id);
$selectedItoreroName = getSelectedName($pdo, 'itorero', $itorero_id);
$monthOptions = imibareMonthOptions();
$selectedMonthLabel = null;
if ($month_filter !== null && $month_filter !== '') {
    $mi = (int) $month_filter;
    if ($mi >= 1 && $mi <= 12) {
        $selectedMonthLabel = $monthOptions[$mi];
    }
}

// Get data
$imibareList = getImibare($pdo, $intara_id, $itorero_id, $month_filter);

// Build Excel data as array of arrays
$excelData = [
    ['SEVENTH DAY ADVENTIST CHURCH'],
    // ['INTARA Y\'IMIBARE Y\'ITURO'],
    ['MAPATO B'],

    ['INTARA: ' . ($selectedIntaraName ?? 'All Intara')],
    ['ITORERO: ' . ($selectedItoreroName ?? 'All Itorero')],
    ['UKWEZI: ' . ($selectedMonthLabel ?? 'All months')],
    // ['Raporo - Generated: ' . date('d/m/Y H:i')],
    [],
    ['Lesi', 'Intara', 'Itorero', 'Ukwezi', 'Ibindi', 'Icyacumi', 'Icyacumi cya CFMS', 'Total Icyacumi(icyacumi&Icyacumi CFMS)', 'Amaturo', 'Amaturo ya CFMS', 'total(RECU&CFMS)', 'Umusaruro', 'Ituro ryiteraniro rikuru', 'inyubako ya Filide', 'SS Lesson', 'Udutabo twUbusonga', 'Udutabo twa Mifem', 'Udutabo twa JA', 'Total', 'Itariki']
];

$categoryTotals = [
    'icyacumi' => 0, 'icyacumi_cya_cms' => 0, 'total_icyacumi_pair' => 0, 'amaturo' => 0, 'amaturo_bya_cms' => 0, 'total_amaturo_pair' => 0,
    'umusaruro' => 0, 'ituro' => 0, 'filide' => 0, 'ss' => 0, 'ubusonga' => 0, 'mifem' => 0, 'ja' => 0
];

$grandTotal = 0;

foreach ($imibareList as $record) {
    $mk = isset($record['month']) ? (int) $record['month'] : 0;
    $monthCell = ($mk >= 1 && $mk <= 12) ? $monthOptions[$mk] : '-';
    $icyN = extractSum($record['icyacumi']);
    $icyCmsN = extractSum($record['icyacumi_cya_cms']);
    $amaN = extractSum($record['amaturo']);
    $amaCmsN = extractSum($record['amaturo_bya_cms']);
    $excelData[] = [
        $record['lesi'],
        $record['intara_name'] ?? '-',
        $record['itorero_name'] ?? '-',
        $monthCell,
        $record['ibindi'] ?? '',
        $icyN,
        $icyCmsN,
        $icyN + $icyCmsN,
        $amaN,
        $amaCmsN,
        $amaN + $amaCmsN,
        extractSum($record['umusaruro']),
        extractSum($record['ituro']),
        extractSum($record['filide']),
        extractSum($record['ss']),
        extractSum($record['ubusonga']),
        extractSum($record['mifem']),
        extractSum($record['ja']),
        $record['total'],
        date('d/m/Y H:i', strtotime($record['created_at']))
    ];
    
    $grandTotal += $record['total'];
    $categoryTotals['icyacumi'] += $icyN;
    $categoryTotals['icyacumi_cya_cms'] += $icyCmsN;
    $categoryTotals['total_icyacumi_pair'] += $icyN + $icyCmsN;
    $categoryTotals['amaturo'] += $amaN;
    $categoryTotals['amaturo_bya_cms'] += $amaCmsN;
    $categoryTotals['total_amaturo_pair'] += $amaN + $amaCmsN;
    $categoryTotals['umusaruro'] += extractSum($record['umusaruro']);
    $categoryTotals['ituro'] += extractSum($record['ituro']);
    $categoryTotals['filide'] += extractSum($record['filide']);
    $categoryTotals['ss'] += extractSum($record['ss']);
    $categoryTotals['ubusonga'] += extractSum($record['ubusonga']);
    $categoryTotals['mifem'] += extractSum($record['mifem']);
    $categoryTotals['ja'] += extractSum($record['ja']);
}

// Add totals row
$excelData[] = [
    'TOTAL',
    '',
    '',
    '',
    '',
    $categoryTotals['icyacumi'],
    $categoryTotals['icyacumi_cya_cms'],
    $categoryTotals['total_icyacumi_pair'],
    $categoryTotals['amaturo'],
    $categoryTotals['amaturo_bya_cms'],
    $categoryTotals['total_amaturo_pair'],
    $categoryTotals['umusaruro'],
    $categoryTotals['ituro'],
    $categoryTotals['filide'],
    $categoryTotals['ss'],
    $categoryTotals['ubusonga'],
    $categoryTotals['mifem'],
    $categoryTotals['ja'],
    $grandTotal,
    ''
];

function extractSum($formatted) {
    if (empty($formatted)) return 0;
    if (preg_match('/=\s*([\d.]+)$/', $formatted, $matches)) {
        return floatval($matches[1]);
    }
    return 0;
}

function getSelectedName($pdo, $table, $id) {
    if (empty($id)) {
        return null;
    }
    if (!in_array($table, ['intara', 'itorero'], true)) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT name FROM {$table} WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row['name'] ?? null;
}

// Output as JSON - JavaScript will handle the Excel generation
header('Content-Type: application/json; charset=utf-8');
echo json_encode($excelData);