<?php
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

if (!$intara_id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

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

$imibareList = getImibare($pdo, $intara_id, $itorero_id, $month_filter);

$excelData = [
    ['SEVENTH DAY ADVENTIST CHURCH'],
    ['MAPATO A'],
    ['INTARA: ' . ($selectedIntaraName ?? 'All Intara')],
    ['ITORERO: ' . ($selectedItoreroName ?? 'All Itorero')],
    ['UKWEZI: ' . ($selectedMonthLabel ?? 'All months')],
    // ['Raporo - Generated: ' . date('d/m/Y H:i')],
    [],
    ['Intara', 'Itorero', 'Umubare w\'ibyanditswe', 'Icyacumi', 'Ibindi', 'Icyacumi cya CFMS', 'Total Icyacumi(RECU&CFMS)', 'Amaturo', 'Amaturo ya CFMS', 'total(RECU&CFMS)', 'Umusaruro', 'Ituro ryiteraniro rikuru', 'inyubako ya Filide', 'SS Lesson', 'Udutabo twUbusonga', 'Udutabo twa Mifem', 'Udutabo twa JA', 'Grand Total']
];

$totalsByItorero = [];
$overallCategoryTotals = [
    'icyacumi' => 0,
    'ibindi' => 0,
    'icyacumi_cya_cms' => 0,
    'amaturo' => 0,
    'amaturo_bya_cms' => 0,
    'umusaruro' => 0,
    'ituro' => 0,
    'filide' => 0,
    'ss' => 0,
    'ubusonga' => 0,
    'mifem' => 0,
    'ja' => 0
];
$overallGrandTotal = 0;

foreach ($imibareList as $row) {
    $itoreroId = $row['itorero_id'] ?? ('name_' . ($row['itorero_name'] ?? 'unknown'));
    if (!isset($totalsByItorero[$itoreroId])) {
        $totalsByItorero[$itoreroId] = [
            'itorero_name' => $row['itorero_name'] ?? '-',
            'record_count' => 0,
            'icyacumi' => 0,
            'ibindi' => 0,
            'icyacumi_cya_cms' => 0,
            'amaturo' => 0,
            'amaturo_bya_cms' => 0,
            'umusaruro' => 0,
            'ituro' => 0,
            'filide' => 0,
            'ss' => 0,
            'ubusonga' => 0,
            'mifem' => 0,
            'ja' => 0,
            'grand_total' => 0
        ];
    }

    $totalsByItorero[$itoreroId]['record_count']++;
    $totalsByItorero[$itoreroId]['icyacumi'] += extractSum($row['icyacumi']);
    $totalsByItorero[$itoreroId]['ibindi'] += extractSum($row['ibindi']);
    $totalsByItorero[$itoreroId]['icyacumi_cya_cms'] += extractSum($row['icyacumi_cya_cms']);
    $totalsByItorero[$itoreroId]['amaturo'] += extractSum($row['amaturo']);
    $totalsByItorero[$itoreroId]['amaturo_bya_cms'] += extractSum($row['amaturo_bya_cms']);
    $totalsByItorero[$itoreroId]['umusaruro'] += extractSum($row['umusaruro']);
    $totalsByItorero[$itoreroId]['ituro'] += extractSum($row['ituro']);
    $totalsByItorero[$itoreroId]['filide'] += extractSum($row['filide']);
    $totalsByItorero[$itoreroId]['ss'] += extractSum($row['ss']);
    $totalsByItorero[$itoreroId]['ubusonga'] += extractSum($row['ubusonga']);
    $totalsByItorero[$itoreroId]['mifem'] += extractSum($row['mifem']);
    $totalsByItorero[$itoreroId]['ja'] += extractSum($row['ja']);
    $totalsByItorero[$itoreroId]['grand_total'] += (float) $row['total'];
}

foreach ($totalsByItorero as $itoreroTotals) {
    $totalIcyacumiPair = $itoreroTotals['icyacumi'] + $itoreroTotals['icyacumi_cya_cms'];
    $totalAmaturoPair = $itoreroTotals['amaturo'] + $itoreroTotals['amaturo_bya_cms'];
    $excelData[] = [
        $selectedIntaraName ?? '-',
        $itoreroTotals['itorero_name'],
        $itoreroTotals['record_count'],
        $itoreroTotals['icyacumi'],
        $itoreroTotals['ibindi'],
        $itoreroTotals['icyacumi_cya_cms'],
        $totalIcyacumiPair,
        $itoreroTotals['amaturo'],
        $itoreroTotals['amaturo_bya_cms'],
        $totalAmaturoPair,
        $itoreroTotals['umusaruro'],
        $itoreroTotals['ituro'],
        $itoreroTotals['filide'],
        $itoreroTotals['ss'],
        $itoreroTotals['ubusonga'],
        $itoreroTotals['mifem'],
        $itoreroTotals['ja'],
        $itoreroTotals['grand_total']
    ];

    $overallCategoryTotals['icyacumi'] += $itoreroTotals['icyacumi'];
    $overallCategoryTotals['ibindi'] += $itoreroTotals['ibindi'];
    $overallCategoryTotals['icyacumi_cya_cms'] += $itoreroTotals['icyacumi_cya_cms'];
    $overallCategoryTotals['amaturo'] += $itoreroTotals['amaturo'];
    $overallCategoryTotals['amaturo_bya_cms'] += $itoreroTotals['amaturo_bya_cms'];
    $overallCategoryTotals['umusaruro'] += $itoreroTotals['umusaruro'];
    $overallCategoryTotals['ituro'] += $itoreroTotals['ituro'];
    $overallCategoryTotals['filide'] += $itoreroTotals['filide'];
    $overallCategoryTotals['ss'] += $itoreroTotals['ss'];
    $overallCategoryTotals['ubusonga'] += $itoreroTotals['ubusonga'];
    $overallCategoryTotals['mifem'] += $itoreroTotals['mifem'];
    $overallCategoryTotals['ja'] += $itoreroTotals['ja'];
    $overallGrandTotal += $itoreroTotals['grand_total'];
}

$overallTotalIcyacumiPair = $overallCategoryTotals['icyacumi'] + $overallCategoryTotals['icyacumi_cya_cms'];
$overallTotalAmaturoPair = $overallCategoryTotals['amaturo'] + $overallCategoryTotals['amaturo_bya_cms'];
$excelData[] = [
    'TOTAL',
    '',
    '',
    $overallCategoryTotals['icyacumi'],
    $overallCategoryTotals['ibindi'],
    $overallCategoryTotals['icyacumi_cya_cms'],
    $overallTotalIcyacumiPair,
    $overallCategoryTotals['amaturo'],
    $overallCategoryTotals['amaturo_bya_cms'],
    $overallTotalAmaturoPair,
    $overallCategoryTotals['umusaruro'],
    $overallCategoryTotals['ituro'],
    $overallCategoryTotals['filide'],
    $overallCategoryTotals['ss'],
    $overallCategoryTotals['ubusonga'],
    $overallCategoryTotals['mifem'],
    $overallCategoryTotals['ja'],
    $overallGrandTotal
];

function extractSum($formatted) {
    if (empty($formatted)) {
        return 0;
    }
    if (preg_match('/=\s*([\d.]+)$/', $formatted, $matches)) {
        return (float) $matches[1];
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

header('Content-Type: application/json; charset=utf-8');
echo json_encode($excelData);
