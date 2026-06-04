<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/imibare-math.php';
require_once __DIR__ . '/includes/mapato-pastor-fields.php';
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

if (!$intara_id || $month_filter === null || $month_filter === '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

$monthInt = (int) $month_filter;
if ($monthInt < 1 || $monthInt > 12) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

$selectedIntaraName = getSelectedName($pdo, 'intara', $intara_id);
$selectedItoreroName = getSelectedName($pdo, 'itorero', $itorero_id);
$monthOptions = imibareMonthOptions();
$selectedMonthLabel = $monthOptions[$monthInt] ?? '';

$mapatoPastorList = getMapatoPastor($pdo, $intara_id, $monthInt, $itorero_id ?: null);
$pastorExtraColumns = mapatoPastorExtraColumnsForIntaraMonth($pdo, (int) $intara_id, $monthInt, $mapatoPastorList);
$computed = computeMapatoPastorCategoryTotals($mapatoPastorList);
$cat = $computed['category'];
$extraTotals = $computed['extra'];

$header = [
    'Itorero',
    'Icyacumi (Grand Total)',
    'Icyacumi cya CFMS',
    'CM (Meeting)',
    'Amaturo (Grand Total)',
    'Amaturo ya CFMS',
    'Amaturo (RECU+CFMS)',
    'Amaturo ÷2',
    'Revival',
    'SS Lesson',
    'Inyubako (Filide)',
    'Umusaruro',
    'Udutabo twa JA',
    'Udutabo twa Mifem',
];
foreach ($pastorExtraColumns as $col) {
    $header[] = $col['label'];
}
$header[] = 'Total';
$header[] = 'Itariki';
$header[] = 'Admin (yashyizeho)';

function extractSum($formatted) {
    if (empty($formatted)) {
        return 0;
    }
    if (preg_match('/=\s*([\d.]+)$/', $formatted, $matches)) {
        return (float) $matches[1];
    }
    return 0;
}

$excelData = [
    ['SEVENTH DAY ADVENTIST CHURCH'],
    ['MAPATO YA PASTORO'],
    ['INTARA: ' . ($selectedIntaraName ?? '')],
    ['ITORERO: ' . ($selectedItoreroName ?? 'All Itorero')],
    ['UKWEZI: ' . $selectedMonthLabel],
    [],
    $header,
];

foreach ($mapatoPastorList as $record) {
    $meetingDisplay = mapatoPastorMeeting($record);
    $amaRecu = extractSum($record['amaturo'] ?? '');
    $amaCfms = extractSum($record['amaturo_bya_cms'] ?? '');
    $amaPair = $amaRecu + $amaCfms;
    $row = [
        $record['itorero_name'] ?? '—',
        $record['icyacumi'] ?? '0',
        $record['icyacumi_cya_cms'] ?? '0',
        $meetingDisplay ?: '0',
        $record['amaturo'] ?? '0',
        $record['amaturo_bya_cms'] ?? '0',
        $amaPair,
        $amaPair / 2,
        $record['revival'] ?? '0',
        $record['ss'] ?? '0',
        $record['filide'] ?? '0',
        $record['umusaruro'] ?? '0',
        $record['ituro'] ?? '0',
        $record['mifem'] ?? '0',
    ];
    foreach ($pastorExtraColumns as $col) {
        $extra = decodeMapatoPastorExtraFields($record);
        $row[] = $extra[$col['slug']] ?? '0';
    }
    $row[] = (float) ($record['total'] ?? 0);
    $row[] = !empty($record['created_at']) ? date('d/m/Y H:i', strtotime($record['created_at'])) : '';
    $row[] = $record['inserted_by_username'] ?? '';
    $excelData[] = $row;
}

$totalRow = [
    'TOTAL',
    $cat['icyacumi'],
    $cat['icyacumi_cya_cms'],
    $cat['meeting'],
    $cat['amaturo'],
    $cat['amaturo_bya_cms'],
    $cat['total_amaturo_pair'],
    $cat['total_amaturo_half'],
    $cat['revival'],
    $cat['ss'],
    $cat['filide'],
    $cat['umusaruro'],
    $cat['ituro'],
    $cat['mifem'],
];
foreach ($pastorExtraColumns as $col) {
    $totalRow[] = $extraTotals[$col['slug']] ?? 0;
}
$totalRow[] = $computed['grand'];
$totalRow[] = '';
$totalRow[] = implode(', ', mapatoPastorDistinctInsertedBy($mapatoPastorList));
$excelData[] = $totalRow;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($excelData, JSON_UNESCAPED_UNICODE);
