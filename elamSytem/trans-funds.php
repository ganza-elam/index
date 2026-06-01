<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/icons.php';

requireLogin();

$currentUser = getCurrentUser();
$isGuest = isGuestUser();
$guestIntaraId = getGuestIntaraId();

$view = $_GET['view'] ?? 'amaturo';
if (!in_array($view, ['amaturo', 'icyacumi'], true)) {
    $view = 'amaturo';
}

$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

$restrictIntaraId = null;
if ($isGuest) {
    if ($guestIntaraId === null) {
        $restrictIntaraId = -1;
    } else {
        $restrictIntaraId = (int) $guestIntaraId;
    }
}

$monthOptions = imibareMonthOptions();
$pivot = getTransFundsIntaraMonthPivot($pdo, $view, $year, $restrictIntaraId > 0 ? $restrictIntaraId : null);

$viewTitle = $view === 'icyacumi' ? 'Icyacumi (Tithe)' : 'Amaturo (Offerings)';
$viewLabelRw = $view === 'icyacumi' ? 'Icyacumi' : 'Amaturo';

$yearChoices = [];
for ($y = (int) date('Y'); $y >= (int) date('Y') - 8; $y--) {
    $yearChoices[] = $y;
}
?>
<!DOCTYPE html>
<html lang="rw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/includes/material-icons-head.php'; ?>
    <link rel="stylesheet" href="styles.css">
    <style>
        .tf-pivot-wrap { overflow-x: auto; margin-top: 16px; }
        .tf-pivot-table { border-collapse: collapse; min-width: 100%; font-size: 0.9rem; }
        .tf-pivot-table th,
        .tf-pivot-table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: right;
            white-space: nowrap;
        }
        .tf-pivot-table th.tf-sticky-intara,
        .tf-pivot-table td.tf-sticky-intara {
            position: sticky;
            left: 0;
            z-index: 2;
            text-align: left;
            min-width: 140px;
            background: #fff;
            font-weight: 600;
        }
        .tf-pivot-table thead th {
            background: #1976d2;
            color: #fff;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .tf-pivot-table thead th.tf-sticky-intara {
            z-index: 3;
            background: #1565c0;
        }
        .tf-pivot-table tbody tr:nth-child(even) td.tf-sticky-intara { background: #f9f9f9; }
        .tf-pivot-table tbody tr:nth-child(even) { background: #fafafa; }
        .tf-pivot-table tfoot td,
        .tf-pivot-table tfoot th {
            background: #e8f5e9;
            font-weight: 700;
        }
        .tf-pivot-table tfoot td.tf-sticky-intara { background: #c8e6c9; }
        .tf-pivot-table td.tf-empty { color: #bbb; }
        .tf-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin: 16px 0; }
        .tf-tabs a {
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            background: #f0f0f0;
            color: #333;
            font-weight: 600;
        }
        .tf-tabs a.active { background: #1976d2; color: #fff; }
        .tf-year-form { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
    </style>
</head>
<body class="app-body">
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

    <h2 class="page-title">Trans-Funds</h2>
    <p class="subtitle">
        Pivot table: <strong>Intara</strong> (rows) × <strong>ukwezi</strong> (columns).
        Amafaranga avuye mu <strong>Mapato ya Pastoro</strong> (IBYAKIRIWE KURI RAPORT) —
        <?php if ($view === 'icyacumi'): ?>
            grand total ya <strong>Icyacumi</strong> (RECU + CFMS) ku Itorero zose muri iyo Intara.
        <?php else: ?>
            grand total ya <strong>Amaturo</strong> (RECU + CFMS) ku Itorero zose muri iyo Intara.
        <?php endif; ?>
    </p>

    <div class="tf-tabs">
        <a href="trans-funds.php?view=amaturo&amp;year=<?= $year ?>" class="<?= $view === 'amaturo' ? 'active' : '' ?>">Amaturo (Offerings)</a>
        <a href="trans-funds.php?view=icyacumi&amp;year=<?= $year ?>" class="<?= $view === 'icyacumi' ? 'active' : '' ?>">Icyacumi (Tithe)</a>
    </div>

    <form method="GET" class="tf-year-form filters">
        <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
        <div>
            <label>Umwaka:</label>
            <select name="year" onchange="this.form.submit()">
                <?php foreach ($yearChoices as $y): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <h3><?= mi('table_chart', 22) ?> <?= htmlspecialchars($viewTitle) ?> — <?= (int) $pivot['year'] ?></h3>

    <?php if ($restrictIntaraId === -1): ?>
        <div class="alert error">Konti yawe nta Intara ifite. Saba admin aguhe Intara.</div>
    <?php elseif (empty($pivot['rows'])): ?>
        <div class="no-data"><p>Nta Intara zihari.</p></div>
    <?php else: ?>
        <div style="margin-bottom:10px;">
            <button type="button" class="btn-icon" onclick="downloadTableToExcel('trans-funds-pivot-table', 'trans_funds_<?= htmlspecialchars($view) ?>_<?= (int) $pivot['year'] ?>')">
                <?= mi_btn('download', 'Download Excel') ?>
            </button>
        </div>
        <div class="tf-pivot-wrap table-wrap">
            <table class="tf-pivot-table" id="trans-funds-pivot-table">
                <thead>
                    <tr>
                        <th class="tf-sticky-intara">Intara</th>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <th><?= htmlspecialchars($monthOptions[$m] ?? (string) $m) ?></th>
                        <?php endfor; ?>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pivot['rows'] as $row): ?>
                    <tr>
                        <td class="tf-sticky-intara"><?= htmlspecialchars($row['intara_name']) ?></td>
                        <?php for ($m = 1; $m <= 12; $m++):
                            $val = $row['months'][$m];
                        ?>
                            <td class="<?= $val == 0.0 ? 'tf-empty' : '' ?>"><?= $val == 0.0 ? '—' : number_format($val, 0) ?></td>
                        <?php endfor; ?>
                        <td><strong><?= number_format($row['row_total'], 0) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="tf-sticky-intara">TOTAL</td>
                        <?php for ($m = 1; $m <= 12; $m++):
                            $val = $pivot['month_totals'][$m];
                        ?>
                            <td><?= number_format($val, 0) ?></td>
                        <?php endfor; ?>
                        <td><strong><?= number_format($pivot['grand_total'], 0) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script>
function downloadTableToExcel(tableId, baseName) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var wb = XLSX.utils.table_to_book(table, { sheet: 'Trans-Funds' });
    XLSX.writeFile(wb, baseName + '.xlsx');
}
</script>
</body>
</html>
