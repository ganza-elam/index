<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/icons.php';
require_once __DIR__ . '/includes/mapato-pastor-fields.php';

define('REPORTS_PER_PAGE', 15);

function buildReportsPageUrl($page, $filter_intara, $filter_itorero, $filter_month, $report_type = 'insert_data', $filter_search = '', $hash = '') {
    $params = [];
    if ($report_type !== '' && $report_type !== 'insert_data') {
        $params['report_type'] = $report_type;
    }
    if ($filter_intara !== '') {
        $params['intara_id'] = $filter_intara;
    }
    if ($filter_itorero !== '' && $report_type === 'insert_data') {
        $params['itorero_id'] = $filter_itorero;
    }
    if ($filter_month !== '') {
        $params['month'] = $filter_month;
    }
    if ($filter_search !== '') {
        $params['search'] = $filter_search;
    }
    if ($page > 1) {
        $params['page'] = $page;
    }
    $query = http_build_query($params);
    $url = 'reports.php' . ($query !== '' ? '?' . $query : '');
    if ($hash === '' && $report_type === 'insert_data' && $page > 1) {
        $hash = 'inserted-data-table';
    }
    if ($hash !== '') {
        $url .= '#' . ltrim($hash, '#');
    }
    return $url;
}

// Require login before accessing this page
requireLogin();

// Get current user
$currentUser = getCurrentUser();
$isGuest = isGuestUser();
$guestIntaraId = getGuestIntaraId();

$message = '';
if ($isGuest) {
    if ($guestIntaraId === null) {
        $message = '<div class="alert error">Konti yawe nta Intara ifite. Saba admin aguhe Intara.</div>';
    } else {
        $message = '<div class="alert success">Pastor mode: ureba reports z\'Intara yawe gusa (view only).</div>';
    }
}
if (isset($_GET['updated']) && !$isGuest) {
    $message = '<div class="alert success">Record updated successfully.</div>';
}

$reportType = $_GET['report_type'] ?? 'insert_data';
if (!in_array($reportType, ['insert_data', 'correct_report', 'comparison_summary'], true)) {
    $reportType = 'insert_data';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record']) && !$isGuest) {
    $recordId = $_POST['record_id'] ?? '';
    if ($recordId && deleteImibare($pdo, $recordId)) {
        header('Location: reports.php?deleted=1&report_type=' . urlencode($reportType));
        exit;
    }
    $message = '<div class="alert error">Habaye ikibazo mu gusiba record.</div>';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pastor_record']) && !$isGuest) {
    $recordId = $_POST['record_id'] ?? '';
    if ($recordId && deleteMapatoPastor($pdo, $recordId)) {
        header('Location: reports.php?deleted_pastor=1&report_type=correct_report');
        exit;
    }
    $message = '<div class="alert error">Habaye ikibazo mu gusiba mapato ya pastoro.</div>';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_bank_slip']) && !$isGuest) {
    $recordId = $_POST['record_id'] ?? '';
    if ($recordId && deleteBankSlip($pdo, $recordId)) {
        header('Location: reports.php?deleted_slip=1&report_type=correct_report');
        exit;
    }
    $message = '<div class="alert error">Habaye ikibazo mu gusiba bank slip.</div>';
}
if (isset($_GET['deleted']) && !$isGuest) {
    $message = '<div class="alert success">Record deleted successfully.</div>';
}
if (isset($_GET['deleted_pastor']) && !$isGuest) {
    $message = '<div class="alert success">Mapato ya pastoro yasibwe neza.</div>';
}
if (isset($_GET['deleted_slip']) && !$isGuest) {
    $message = '<div class="alert success">Bank slip yasibwe neza.</div>';
}
if (isset($_GET['updated_pastor']) && !$isGuest) {
    $message = '<div class="alert success">Mapato ya pastoro yavuguruwe neza.</div>';
}
if (isset($_GET['updated_slip']) && !$isGuest) {
    $message = '<div class="alert success">Bank slip yavuguruwe neza.</div>';
}

// Get filter values
$filter_intara = $_GET['intara_id'] ?? '';
$filter_itorero = $_GET['itorero_id'] ?? '';
$filter_month = $_GET['month'] ?? '';
$filter_search = trim($_GET['search'] ?? '');
$highlightRecordId = (int) ($_GET['highlight_id'] ?? 0);

if ($isGuest && $guestIntaraId !== null) {
    $filter_intara = (string) $guestIntaraId;
    if ($filter_itorero !== '') {
        $stmt = $pdo->prepare("SELECT id FROM itorero WHERE id = ? AND intara_id = ?");
        $stmt->execute([(int) $filter_itorero, $guestIntaraId]);
        if (!$stmt->fetch()) {
            $filter_itorero = '';
        }
    }
}

$monthOptions = imibareMonthOptions();

ensureCorrectReportTables($pdo);

// Get data based on filters (month: empty = show all ukwezi)
if ($isGuest && $guestIntaraId === null) {
    $imibareList = [];
    $mapatoPastorList = [];
    $bankSlipsList = [];
    $comparisonRows = [];
    $comparisonInsertRows = [];
    $grandTotalsRows = [];
} elseif ($reportType === 'comparison_summary') {
    if ($filter_month !== '' && $filter_intara !== '') {
        $imibareList = getImibare($pdo, $filter_intara, $filter_itorero ?: null, (int) $filter_month);
        $mapatoPastorList = getMapatoPastor($pdo, $filter_intara, (int) $filter_month, $filter_itorero ?: null);
        $bankSlipsList = getBankSlips($pdo, $filter_intara, (int) $filter_month);
    } else {
        $imibareList = [];
        $mapatoPastorList = [];
        $bankSlipsList = [];
    }
    $comparisonRows = $filter_month !== ''
        ? getCorrectReportComparison($pdo, $filter_month, $filter_intara ?: null)
        : [];
    $comparisonInsertRows = $filter_month !== ''
        ? getBankVsInsertDataComparison($pdo, $filter_month, $filter_intara ?: null)
        : [];
    $grandTotalsRows = $filter_month !== ''
        ? getGrandTotalsComparison($pdo, $filter_month, $filter_intara ?: null)
        : [];
} elseif ($reportType === 'correct_report') {
    $imibareList = [];
    $mapatoPastorList = $filter_month !== ''
        ? getMapatoPastor($pdo, $filter_intara ?: null, (int) $filter_month, $filter_itorero ?: null)
        : [];
    $bankSlipsList = getBankSlips($pdo, $filter_intara ?: null, $filter_month !== '' ? $filter_month : null);
    $comparisonRows = $filter_month !== ''
        ? getCorrectReportComparison($pdo, $filter_month, $filter_intara ?: null)
        : [];
    $comparisonInsertRows = $filter_month !== ''
        ? getBankVsInsertDataComparison($pdo, $filter_month, $filter_intara ?: null)
        : [];
    $grandTotalsRows = $filter_month !== ''
        ? getGrandTotalsComparison($pdo, $filter_month, $filter_intara ?: null)
        : [];
} else {
    $imibareList = getImibare($pdo, $filter_intara, $filter_itorero, $filter_month !== '' ? $filter_month : null);
    if ($filter_search !== '') {
        $imibareList = array_values(array_filter($imibareList, function ($record) use ($filter_search) {
            if ((string) ($record['id'] ?? '') === $filter_search) {
                return true;
            }
            if (stripos((string) ($record['lesi'] ?? ''), $filter_search) !== false) {
                return true;
            }
            if (stripos((string) ($record['itorero_name'] ?? ''), $filter_search) !== false) {
                return true;
            }
            if (stripos((string) ($record['intara_name'] ?? ''), $filter_search) !== false) {
                return true;
            }
            return false;
        }));
        if ($highlightRecordId === 0 && count($imibareList) === 1) {
            $highlightRecordId = (int) $imibareList[0]['id'];
        }
    }
    $mapatoPastorList = [];
    $bankSlipsList = [];
    $comparisonRows = [];
    $comparisonInsertRows = [];
    $grandTotalsRows = [];
}
$adminInsertStats = [];
if ($reportType === 'insert_data' && !$isGuest) {
    $adminInsertStats = getImibareInsertsByAdmin(
        $pdo,
        $filter_intara ?: null,
        $filter_itorero ?: null,
        $filter_month !== '' ? $filter_month : null
    );
}
$itoreroComparisonRows = [];
if ($reportType === 'correct_report' && $filter_month !== '') {
    $itoreroComparisonRows = getItoreroOfferingsComparison(
        $pdo,
        $filter_month,
        $filter_intara ?: null,
        $filter_itorero ?: null
    );
}
$intaraList = getAllIntara($pdo);
$itoreroList = getAllItorero($pdo);

if ($isGuest && $guestIntaraId !== null) {
    $intaraList = array_values(array_filter($intaraList, fn($i) => (int) $i['id'] === $guestIntaraId));
    $itoreroList = array_values(array_filter($itoreroList, fn($i) => (int) $i['intara_id'] === $guestIntaraId));
}
$intaraNameMap = [];
foreach ($intaraList as $intaraItem) {
    $intaraNameMap[(string) $intaraItem['id']] = $intaraItem['name'];
}
$itoreroNameMap = [];
foreach ($itoreroList as $itoreroItem) {
    $itoreroNameMap[(string) $itoreroItem['id']] = $itoreroItem['name'];
}

// Calculate totals
$grandTotal = 0;
$bankSlipsTotal = 0;
$categoryTotals = [
    'ibindi' => 0,
    'icyacumi' => 0, 'icyacumi_cya_cms' => 0, 'total_icyacumi_pair' => 0, 'amaturo' => 0, 'amaturo_bya_cms' => 0,
    'total_amaturo_pair' => 0,
    'total_amaturo_half' => 0,
    'umusaruro' => 0, 'ituro' => 0, 'filide' => 0, 'ss' => 0, 'ubusonga' => 0, 'mifem' => 0, 'ja' => 0,
    'revival' => 0,
    // 'mifem' already tracked above; keep for backward compatibility access.
];

if ($reportType === 'correct_report') {
    $categoryTotals['meeting'] = 0;
    $categoryTotals['icyacumi_cya_cms'] = 0;
    $categoryTotals['amaturo_bya_cms'] = 0;
    if (!empty($mapatoPastorList)) {
        $pastorComputed = computeMapatoPastorCategoryTotals($mapatoPastorList);
        $categoryTotals = array_merge($categoryTotals, $pastorComputed['category']);
        $categoryTotals['meeting'] = $pastorComputed['meeting'];
        $grandTotal = $pastorComputed['grand'];
        $pastorExtraTotals = $pastorComputed['extra'];
    }
    foreach ($bankSlipsList as $slip) {
        $bankSlipsTotal += (float) $slip['amount'];
    }
    $totalRecords = count($mapatoPastorList);
    $pagedImibareList = [];
} else {
    foreach ($imibareList as $record) {
        $grandTotal += $record['total'];
        $categoryTotals['ibindi'] += extractSum($record['ibindi']);
        $categoryTotals['icyacumi'] += extractSum($record['icyacumi']);
        $categoryTotals['icyacumi_cya_cms'] += extractSum($record['icyacumi_cya_cms']);
        $categoryTotals['total_icyacumi_pair'] += extractSum($record['icyacumi']) + extractSum($record['icyacumi_cya_cms']);
        $categoryTotals['amaturo'] += extractSum($record['amaturo']);
        $categoryTotals['amaturo_bya_cms'] += extractSum($record['amaturo_bya_cms']);
        $categoryTotals['total_amaturo_pair'] += extractSum($record['amaturo']) + extractSum($record['amaturo_bya_cms']);
        $categoryTotals['umusaruro'] += extractSum($record['umusaruro']);
        $categoryTotals['ituro'] += extractSum($record['ituro']);
        $categoryTotals['filide'] += extractSum($record['filide']);
        $categoryTotals['ss'] += extractSum($record['ss']);
        $categoryTotals['ubusonga'] += extractSum($record['ubusonga']);
        $categoryTotals['mifem'] += extractSum($record['mifem']);
        $categoryTotals['ja'] += extractSum($record['ja']);
    }
    $totalRecords = count($imibareList);
}

$pastorExtraColumns = [];
$pastorExtraTotals = [];
$mapatoPastorInsertedByNames = [];
if (in_array($reportType, ['correct_report', 'comparison_summary'], true)) {
    if ($reportType === 'comparison_summary' && $filter_intara !== '' && $filter_month !== '') {
        $pastorExtraColumns = mapatoPastorExtraColumnsForIntaraMonth(
            $pdo,
            (int) $filter_intara,
            (int) $filter_month,
            $mapatoPastorList
        );
    } elseif ($reportType === 'correct_report' && $filter_intara !== '' && $filter_month !== '') {
        $pastorExtraColumns = mapatoPastorExtraColumnsForIntaraMonth(
            $pdo,
            (int) $filter_intara,
            (int) $filter_month,
            $mapatoPastorList
        );
    } elseif (!empty($mapatoPastorList)) {
        $pastorExtraColumns = collectMapatoPastorExtraColumns($pdo, $mapatoPastorList);
    }
    if (!empty($mapatoPastorList)) {
        $pastorComputed = computeMapatoPastorCategoryTotals($mapatoPastorList);
        $pastorExtraTotals = $pastorComputed['extra'];
        foreach ($pastorExtraColumns as $col) {
            if (!isset($pastorExtraTotals[$col['slug']])) {
                $pastorExtraTotals[$col['slug']] = 0.0;
            }
        }
        $mapatoPastorInsertedByNames = mapatoPastorDistinctInsertedBy($mapatoPastorList);
        if ($reportType === 'comparison_summary') {
            $categoryTotals = array_merge($categoryTotals, $pastorComputed['category']);
            $categoryTotals['meeting'] = $pastorComputed['meeting'];
            $grandTotal = $pastorComputed['grand'];
        }
    }
}

$perPage = (int) REPORTS_PER_PAGE;
$totalPages = max(1, (int) ceil($totalRecords / $perPage));
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;
if ($reportType === 'correct_report') {
    $pagedPastorList = array_slice($mapatoPastorList, $offset, $perPage);
    $pagedImibareList = [];
} else {
    $pagedImibareList = array_slice($imibareList, $offset, $perPage);
    $pagedPastorList = [];
}

function extractSum($formatted) {
    if (empty($formatted)) return 0;
    // Extract the final number from formatted string like "1000,2000 = 3000"
    if (preg_match('/=\s*([\d.]+)$/', $formatted, $matches)) {
        return floatval($matches[1]);
    }
    return 0;
}

// Get totals by intara for summary
$totalsByIntara = getTotalsByIntara($pdo);
$totalsByItorero = getTotalsByItorero($pdo);
if ($isGuest && $guestIntaraId !== null) {
    $totalsByIntara = array_values(array_filter($totalsByIntara, fn($r) => (int) $r['id'] === $guestIntaraId));
    $guestIntaraName = $intaraList[0]['name'] ?? null;
    if ($guestIntaraName !== null) {
        $totalsByItorero = array_values(array_filter($totalsByItorero, fn($r) => ($r['intara_name'] ?? '') === $guestIntaraName));
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/includes/material-icons-head.php'; ?>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <?php if ($reportType === 'insert_data' && !$isGuest): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <?php endif; ?>
</head>
<body class="app-body" data-default-nav-section="<?= $reportType === 'correct_report' ? 'comparison-pastor-bank' : ($reportType === 'insert_data' ? (($currentPage > 1 || $filter_search !== '') ? 'inserted-data-table' : 'report-summary') : 'comparison-summary') ?>">
<?php require __DIR__ . '/includes/nav.php'; ?>
<div class="container">
    <div class="brand-header">
        <img class="brand-logo" src="assets/sda.png" alt="Adventist logo">
        <div class="brand-text">
            <h2>Seventh Day Adventist Church</h2>
            <small>Stewardship and offerings management</small>
        </div>
    </div>
    
    <p style="text-align:right;color:#666;">May The Lord be with you: <b><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></b></p>
    <?= $message ?>

    <?php if ($reportType === 'insert_data'): ?>
    <h1><?= mi('assessment', 28) ?> Raporo ya mapato A na Mapato B</h1>
    <?php endif; ?>

    <?php
    $filterFormHash = 'inserted-data-table';
    if ($reportType === 'correct_report') {
        $filterFormHash = 'comparison-pastor-bank';
    } elseif ($reportType === 'comparison_summary') {
        $filterFormHash = 'comparison-summary';
    }
    ?>
    <!-- Filters -->
    <div class="filters nav-page-section nav-page-section--always" id="report-filters" data-nav-section="report-filters">
        <form method="GET" id="report_filters_form" action="reports.php#<?= htmlspecialchars($filterFormHash) ?>">
            <?php if ($reportType !== 'insert_data'): ?>
            <div>
                <label>Ubwoko bwa raporo:</label>
                <select name="report_type" id="report_type" onchange="toggleReportFilters()">
                    <option value="insert_data" <?= $reportType === 'insert_data' ? 'selected' : '' ?>>IBYANYUZE MUMA SUCHE (Raporo)</option>
                    <option value="correct_report" <?= $reportType === 'correct_report' ? 'selected' : '' ?>>RAPORO YIBYAKIRIWE</option>
                    <option value="comparison_summary" <?= $reportType === 'comparison_summary' ? 'selected' : '' ?>>Comparison Summary &amp; PDF</option>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="report_type" value="insert_data">
            <?php endif; ?>
            <div>
                <label>search: Intara:</label>
                <select name="intara_id" id="filter_intara" onchange="loadItoreroFilter()" <?= $isGuest && $guestIntaraId ? 'disabled' : '' ?>>
                    <?php if (!$isGuest): ?>
                    <option value=""> Intara zose </option>
                    <?php endif; ?>
                    <?php foreach ($intaraList as $intara): ?>
                        <option value="<?= $intara['id'] ?>" <?= $filter_intara == $intara['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($intara['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isGuest && $guestIntaraId): ?>
                    <input type="hidden" name="intara_id" value="<?= (int) $guestIntaraId ?>">
                <?php endif; ?>
            </div>
            <div id="itorero_filter_wrap">
                <label>search: Itorero:</label>
                <select name="itorero_id" id="filter_itorero">
                    <option value="">Amatorero yose</option>
                    <?php foreach ($itoreroList as $itorero): ?>
                        <option value="<?= $itorero['id'] ?>" <?= $filter_itorero == $itorero['id'] ? 'selected' : '' ?> data-intara="<?= $itorero['intara_id'] ?>">
                            <?= htmlspecialchars($itorero['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Ukwezi:</label>
                <select name="month" id="filter_month">
                    <option value="">Amezi yose / All months </option>
                    <?php foreach ($monthOptions as $m => $label): ?>
                        <option value="<?= (int) $m ?>" <?= (string)$filter_month === (string)$m ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($reportType === 'insert_data'): ?>
            <div>
                <label>Search one record (Lesi / ID / Itorero):</label>
                <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="e.g. 00125 or record ID">
            </div>
            <?php endif; ?>
            <div>
                <button type="submit" class="btn-icon"><?= mi_btn('search', 'Search') ?></button>
                <button type="button" class="clear btn-icon" onclick="clearFilters()"><?= mi_btn('refresh', 'Clear') ?></button>
                <?php if (!$isGuest && $reportType === 'insert_data'): ?>
                    <button type="button" class="btn-icon" onclick="downloadExcel()"><?= mi_btn('download', 'Download mapato B') ?></button>
                    <button type="button" class="btn-icon" onclick="downloadMapatoA()"><?= mi_btn('download', 'Download mapato A') ?></button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($reportType === 'comparison_summary'): ?>
        <?php require __DIR__ . '/includes/comparison-summary-body.php'; ?>
    <?php elseif ($reportType === 'correct_report'): ?>
        <?php require __DIR__ . '/includes/reports-correct-body.php'; ?>
    <?php else: ?>

    <?php if ($filter_search !== ''): ?>
    <div class="alert" style="background:#e3f2fd;margin-bottom:12px;">
        Search: <strong><?= htmlspecialchars($filter_search) ?></strong> — <?= count($imibareList) ?> record(s) found.
        <?php if (count($imibareList) === 1): ?>
            <a href="#record-<?= (int) $imibareList[0]['id'] ?>">Go to row</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="summary-cards nav-page-section" data-nav-section="report-summary" id="report-summary">
        <div class="card">
            <h3>Total Records</h3>
            <div class="value"><?= count($imibareList) ?></div>
        </div>
        <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <h3>Grand Total</h3>
            <div class="value"><?= number_format($grandTotal, 0) ?></div>
        </div>
        <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <h3>Intara</h3>
            <div class="value"><?= count($intaraList) ?></div>
        </div>
        <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <h3>Itorero</h3>
            <div class="value"><?= count($itoreroList) ?></div>
        </div>
    </div>

    <!-- Category Totals -->
    <div class="nav-page-section" data-nav-section="report-summary" id="report-category-totals">
    <h3>Category Totals</h3>
    <div class="category-summary">
        <div class="cat-item">
            <div class="label">Ibindi</div>
            <div class="value"><?= number_format($categoryTotals['ibindi'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Icyacumi</div>
            <div class="value"><?= number_format($categoryTotals['icyacumi'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Icyacumi cyanyuze muri CFMS</div>
            <div class="value"><?= number_format($categoryTotals['icyacumi_cya_cms'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Total Icyacumi(RECU & CFMS)</div>
            <div class="value"><?= number_format($categoryTotals['total_icyacumi_pair'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Amaturo</div>
            <div class="value"><?= number_format($categoryTotals['amaturo'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Amaturo yanyuze muri CFMS</div>
            <div class="value"><?= number_format($categoryTotals['amaturo_bya_cms'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">total Y'amaturo(RECU & CFMS)</div>
            <div class="value"><?= number_format($categoryTotals['total_amaturo_pair'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Umusaruro</div>
            <div class="value"><?= number_format($categoryTotals['umusaruro'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Ituro ry'iteraniro rikuru</div>
            <div class="value"><?= number_format($categoryTotals['ituro'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Inyubako ya Filide</div>
            <div class="value"><?= number_format($categoryTotals['filide'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">SS Lesson</div>
            <div class="value"><?= number_format($categoryTotals['ss'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Udutabo tw'Ubusonga</div>
            <div class="value"><?= number_format($categoryTotals['ubusonga'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Udutabo twa Mifem</div>
            <div class="value"><?= number_format($categoryTotals['mifem'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Udutabo twa JA</div>
            <div class="value"><?= number_format($categoryTotals['ja'], 0) ?></div>
        </div>
    </div>
    </div>

    <?php if (!$isGuest): ?>
    <div class="section admin-insert-insights nav-page-section" data-nav-section="admin-insert-chart" id="admin-insert-chart" style="margin-bottom:24px;padding:20px;border:1px solid #ddd;border-radius:8px;background:#fafafa;min-height:360px;">
        <h3><?= mi('bar_chart', 22) ?> Admin data entry activity</h3>
        <p style="font-size:13px;color:#666;margin-bottom:12px;">
            Rows highlighted in blue below were inserted by an admin. Chart shows how many records each admin entered for the current filters.
        </p>
        <?php if (empty($adminInsertStats)): ?>
            <p style="color:#666;">No admin-attributed inserts for these filters yet (older records may not have an admin name stored).</p>
        <?php else: ?>
            <div class="admin-chart-wrap">
                <canvas id="adminInsertChart" aria-label="Bar chart of records inserted per admin"></canvas>
            </div>
            <ul class="admin-insert-legend" style="margin-top:12px;font-size:13px;">
                <?php foreach ($adminInsertStats as $stat): ?>
                    <li><strong><?= htmlspecialchars($stat['username']) ?></strong>: <?= (int) $stat['record_count'] ?> record(s)</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="nav-page-section" data-nav-section="inserted-data-table" id="inserted-data-table-wrap">
    <h3 id="inserted-data-table"><?= mi('table_chart', 22) ?> Inserted data</h3>
    <?php if (empty($imibareList)): ?>
        <div class="no-data">
            <p><?= mi('inbox', 32) ?> Nta data ihari</p>
            <p>insert the data in the form below <a href="index.php">form y'injira</a></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Lesi</th>
                    <th>Intara</th>
                    <th>Itorero</th>
                    <th>Ukwezi</th>
                    <th>Ibindi</th>
                    <th>Icyacumi</th>
                    <th>Icyacumi CFMS</th>
                    <th>Total Icyacumi(RECU AND CFMS)</th>
                    <th>Amaturo</th>
                    <th>Amaturo CFMS</th>
                    <th>total Y'amaturo(RECU AND CFMS)</th>
                    <th>Umusaruro</th>
                    <th>Ituro ry'iteraniro rikuru</th>
                    <th>inyubako ya Filide</th>
                    <th>SS Lesson</th>
                    <th>Udutabo tw'Ubusonga</th>
                    <th>Udutabo twa Mifem</th>
                    <th>Udutabo twa JA</th>
                    <th>Total</th>
                    <th>Inserted by</th>
                    <th>Itariki</th>
                    <?php if (!$isGuest): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagedImibareList as $record):
                    $rowHighlight = ($highlightRecordId && (int) $record['id'] === $highlightRecordId)
                        || ($filter_search !== '' && count($imibareList) === 1);
                    $adminInserted = !empty($record['inserted_by']);
                    $rowClasses = [];
                    if ($rowHighlight) {
                        $rowClasses[] = 'report-row-highlight';
                    }
                    if ($adminInserted) {
                        $rowClasses[] = 'report-row-admin-insert';
                    }
                ?>
                <tr id="record-<?= (int) $record['id'] ?>"<?= $rowClasses ? ' class="' . implode(' ', $rowClasses) . '"' : '' ?>>
                    <td><?= htmlspecialchars($record['lesi']) ?></td>
                    <td><?= htmlspecialchars($record['intara_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($record['itorero_name'] ?? '-') ?></td>
                    <td><?php
                        $mk = isset($record['month']) ? (int) $record['month'] : 0;
                        echo $mk >= 1 && $mk <= 12 ? htmlspecialchars($monthOptions[$mk]) : '-';
                    ?></td>
                    <td><?= htmlspecialchars($record['ibindi'] ?? '') ?></td>
                    <td><?= htmlspecialchars($record['icyacumi'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['icyacumi_cya_cms'] ?? '0') ?></td>
                    <td><?= number_format(extractSum($record['icyacumi'] ?? '') + extractSum($record['icyacumi_cya_cms'] ?? ''), 0) ?></td>
                    <td><?= htmlspecialchars($record['amaturo'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['amaturo_bya_cms'] ?? '0') ?></td>
                    <td><?= number_format(extractSum($record['amaturo'] ?? '') + extractSum($record['amaturo_bya_cms'] ?? ''), 0) ?></td>
                    <td><?= htmlspecialchars($record['umusaruro'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['ituro'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['filide'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['ss'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['ubusonga'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['mifem'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['ja'] ?? '0') ?></td>
                    <td><strong><?= number_format($record['total'], 0) ?></strong></td>
                    <td>
                        <?php if (!empty($record['inserted_by_username'])): ?>
                            <span class="badge-admin-insert" title="Admin ID: <?= (int) $record['inserted_by'] ?>"><?= htmlspecialchars($record['inserted_by_username']) ?></span>
                        <?php else: ?>
                            <span style="color:#999;">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="date">
                        <?php
                            // Print 'created_at' in Africa/Kigali/ GMT+2 timezone
                            $tz = new DateTimeZone('Africa/Kigali');
                            $dt = new DateTime($record['created_at']);
                            $dt->setTimezone($tz);
                            echo $dt->format('d/m/Y H:i');
                        ?>
                    </td>
                    <?php if (!$isGuest): ?>
                        <td>
                            <a href="edit-imibare.php?id=<?= (int) $record['id'] ?>" class="btn-icon" style="margin-right:8px;"><?= mi_btn('edit', 'Update', 16) ?></a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Urashaka gusiba iyi record?')">
                                <input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>">
                                <button type="submit" name="delete_record" class="delete btn-icon"><?= mi_btn('delete', 'Delete', 16) ?></button>
                            </form>
                        </td>
                    <?php endif; ?>
            
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #e8f5e9; font-weight: bold;">
                    <td colspan="4">TOTAL</td>
                    <td><?= number_format($categoryTotals['ibindi'], 0) ?></td>
                    <td><?= number_format($categoryTotals['icyacumi'], 0) ?></td>
                    <td><?= number_format($categoryTotals['icyacumi_cya_cms'], 0) ?></td>
                    <td><?= number_format($categoryTotals['total_icyacumi_pair'], 0) ?></td>
                    <td><?= number_format($categoryTotals['amaturo'], 0) ?></td>
                    <td><?= number_format($categoryTotals['amaturo_bya_cms'], 0) ?></td>
                    <td><?= number_format($categoryTotals['total_amaturo_pair'], 0) ?></td>
                    <td><?= number_format($categoryTotals['umusaruro'], 0) ?></td>
                    <td><?= number_format($categoryTotals['ituro'], 0) ?></td>
                    <td><?= number_format($categoryTotals['filide'], 0) ?></td>
                    <td><?= number_format($categoryTotals['ss'], 0) ?></td>
                    <td><?= number_format($categoryTotals['ubusonga'], 0) ?></td>
                    <td><?= number_format($categoryTotals['mifem'], 0) ?></td>
                    <td><?= number_format($categoryTotals['ja'], 0) ?></td>
                    <td><?= number_format($grandTotal, 0) ?></td>
                    <td></td>
                    <td></td>
                    <?php if (!$isGuest): ?>
                        <td></td>
                    <?php endif; ?>
                </tr>
            </tfoot>
        </table>
        </div>

        <?php require __DIR__ . '/includes/reports-pagination.php'; ?>
    <?php endif; ?>
    </div>

    <?php endif; /* insert_data vs correct_report vs comparison_summary */ ?>
</div>

<script>
function toggleReportFilters() {
    const rt = document.getElementById('report_type').value;
    const wrap = document.getElementById('itorero_filter_wrap');
    if (wrap) {
        wrap.style.display = (rt === 'correct_report' || rt === 'comparison_summary') ? 'none' : '';
    }
}

const INTARA_NAME_MAP = <?= json_encode($intaraNameMap, JSON_UNESCAPED_UNICODE) ?>;
const ITORERO_NAME_MAP = <?= json_encode($itoreroNameMap, JSON_UNESCAPED_UNICODE) ?>;
const MONTH_LABEL_MAP = <?= json_encode($monthOptions, JSON_UNESCAPED_UNICODE) ?>;

// Filter Itorero based on selected Intara
function loadItoreroFilter() {
    const intaraId = document.getElementById('filter_intara').value;
    const itoreroSelect = document.getElementById('filter_itorero');
    const options = itoreroSelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') return; // Keep the first option
        if (intaraId === '' || option.dataset.intara === intaraId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
    
    // Reset itorero selection
    if (intaraId === '') {
        itoreroSelect.value = '';
    }
}

function clearFilters() {
    const rt = document.getElementById('report_type') ? document.getElementById('report_type').value : 'insert_data';
    let url = 'reports.php?report_type=' + encodeURIComponent(rt);
    <?php if ($isGuest && $guestIntaraId): ?>
    url += '&intara_id=<?= (int) $guestIntaraId ?>';
    <?php endif; ?>
    window.location.href = url;
}

function getSelectedFilterNames() {
    const intaraSelect = document.getElementById('filter_intara');
    const itoreroSelect = document.getElementById('filter_itorero');
    const monthSelect = document.getElementById('filter_month');

    const intaraName = intaraSelect.value
        ? (INTARA_NAME_MAP[intaraSelect.value] || 'Selected-Intara')
        : 'All-Intara';
    const itoreroName = itoreroSelect.value
        ? (ITORERO_NAME_MAP[itoreroSelect.value] || 'Selected-Itorero')
        : 'All-Itorero';
    const monthNum = monthSelect.value;
    const monthName = monthNum
        ? (MONTH_LABEL_MAP[monthNum] || MONTH_LABEL_MAP[String(monthNum)] || 'Ukwezi-' + monthNum)
        : 'All-months';

    return { intaraName, itoreroName, monthName };
}

function sanitizeFilePart(value) {
    return String(value)
        .trim()
        .replace(/[\\/:*?"<>|]+/g, '-')
        .replace(/\s+/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_+|_+$/g, '');
}

function downloadTableToExcel(tableId, baseName) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('Table ntabashije kuboneka.');
        return;
    }
    const wb = XLSX.utils.table_to_book(table, { sheet: 'Igereranya' });
    const { intaraName, itoreroName, monthName } = getSelectedFilterNames();
    const filename = sanitizeFilePart(baseName || 'report')
        + '_' + sanitizeFilePart(intaraName)
        + '_' + sanitizeFilePart(itoreroName)
        + '_' + sanitizeFilePart(monthName)
        + '.xlsx';
    XLSX.writeFile(wb, filename);
}

function downloadExcel() {
    const intaraId = document.getElementById('filter_intara').value;
    const itoreroId = document.getElementById('filter_itorero').value;
    const monthVal = document.getElementById('filter_month').value;
    
    let url = 'export_excel.php';
    let params = [];
    if (intaraId) params.push('intara_id=' + intaraId);
    if (itoreroId) params.push('itorero_id=' + itoreroId);
    if (monthVal) params.push('month=' + encodeURIComponent(monthVal));
    if (params.length > 0) url += '?' + params.join('&');
    
    // Fetch data and generate Excel using SheetJS
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.length <= 4) {
                alert('Nta data ihari yo kwishyiramo Excel!');
                return;
            }
            
            // Create workbook and worksheet
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            XLSX.utils.book_append_sheet(wb, ws, "Raporo");
            
            // Download the file
            const { intaraName, itoreroName, monthName } = getSelectedFilterNames();
            const filename = 'mapato_b_' + sanitizeFilePart(intaraName) + '_' + sanitizeFilePart(itoreroName) + '_' + sanitizeFilePart(monthName) + '.xlsx';
            XLSX.writeFile(wb, filename);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Habaye ikibazo mu gutanga data!');
        });
}

function downloadMapatoPastor() {
    const intaraId = document.getElementById('filter_intara').value;
    const itoreroId = document.getElementById('filter_itorero').value;
    const monthVal = document.getElementById('filter_month').value;

    if (!intaraId) {
        alert('Banza uhitemo Intara kugira ngo ubone Download Mapato ya Pastoro.');
        return;
    }
    if (!monthVal) {
        alert('Banza uhitemo Ukwezi kugira ngo ubone Download Mapato ya Pastoro.');
        return;
    }

    let url = 'export_mapato_pastor.php?intara_id=' + encodeURIComponent(intaraId)
        + '&month=' + encodeURIComponent(monthVal);
    if (itoreroId) {
        url += '&itorero_id=' + encodeURIComponent(itoreroId);
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.length <= 4) {
                alert('Nta data ihari ya Mapato ya Pastoro kuri iyi Intara n\'ukwezi!');
                return;
            }

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            XLSX.utils.book_append_sheet(wb, ws, 'Mapato Pastoro');

            const { intaraName, itoreroName, monthName } = getSelectedFilterNames();
            const filename = 'mapato_pastor_' + sanitizeFilePart(intaraName) + '_' + sanitizeFilePart(itoreroName) + '_' + sanitizeFilePart(monthName) + '.xlsx';
            XLSX.writeFile(wb, filename);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Habaye ikibazo mu gukora Mapato ya Pastoro!');
        });
}

function downloadMapatoA() {
    const intaraId = document.getElementById('filter_intara').value;
    const itoreroId = document.getElementById('filter_itorero').value;
    const monthVal = document.getElementById('filter_month').value;

    if (!intaraId) {
        alert('Banza uhitemo Intara kugira ngo ubone Download mapato A.');
        return;
    }

    let url = 'export_mapato_a.php?intara_id=' + encodeURIComponent(intaraId);
    if (itoreroId) {
        url += '&itorero_id=' + encodeURIComponent(itoreroId);
    }
    if (monthVal) {
        url += '&month=' + encodeURIComponent(monthVal);
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.length <= 4) {
                alert('Nta data ihari ya Mapato A kuri iyi Intara!');
                return;
            }

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            XLSX.utils.book_append_sheet(wb, ws, "Mapato A");

            const { intaraName, itoreroName, monthName } = getSelectedFilterNames();
            const filename = 'mapato_a_' + sanitizeFilePart(intaraName) + '_' + sanitizeFilePart(itoreroName) + '_' + sanitizeFilePart(monthName) + '.xlsx';
            XLSX.writeFile(wb, filename);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Habaye ikibazo mu gukora Mapato A!');
        });
}

// Keep the current report section when searching (sidebar hash links)
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('report_filters_form');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            let hash = window.location.hash.replace(/^#/, '');
            if (!hash || hash === 'report-filters') {
                hash = document.body.getAttribute('data-default-nav-section') || '';
            }
            if (hash && document.querySelector('.nav-page-section[data-nav-section="' + hash + '"]')) {
                filterForm.action = 'reports.php#' + hash;
            }
        });
    }
});

// Initialize filter on page load
document.addEventListener('DOMContentLoaded', function() {
    loadItoreroFilter();
    toggleReportFilters();

    const chartCanvas = document.getElementById('adminInsertChart');
    let adminInsertChartInstance = null;
    if (chartCanvas && typeof Chart !== 'undefined') {
        const adminStats = <?= json_encode(array_map(function ($r) {
            return ['username' => $r['username'], 'count' => (int) $r['record_count']];
        }, $adminInsertStats), JSON_UNESCAPED_UNICODE) ?>;
        if (adminStats.length > 0) {
            adminInsertChartInstance = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: adminStats.map(function (s) { return s.username; }),
                    datasets: [{
                        label: 'Records inserted',
                        data: adminStats.map(function (s) { return s.count; }),
                        backgroundColor: 'rgba(25, 118, 210, 0.65)',
                        borderColor: 'rgba(13, 71, 161, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'IBYANYUZE MUMA SUCHE — per admin' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        }
    }

    function refreshAdminInsertChart() {
        if (!adminInsertChartInstance) {
            return;
        }
        var chartSection = document.getElementById('admin-insert-chart');
        if (!chartSection || chartSection.hidden) {
            return;
        }
        adminInsertChartInstance.resize();
    }

    window.addEventListener('hashchange', refreshAdminInsertChart);
    window.addEventListener('nav-section-changed', function (e) {
        if (e.detail && e.detail.sectionId === 'admin-insert-chart') {
            refreshAdminInsertChart();
        }
    });
    setTimeout(refreshAdminInsertChart, 150);
});
</script>

<?php require __DIR__ . '/includes/layout-end.php'; ?>
</body>
</html>