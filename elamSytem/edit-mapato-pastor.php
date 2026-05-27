<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/imibare-math.php';
require_once __DIR__ . '/includes/icons.php';

requireAdmin();
ensureCorrectReportTables($pdo);

$recordId = $_GET['id'] ?? $_POST['record_id'] ?? null;
if (!$recordId) {
    header('Location: reports.php?report_type=correct_report');
    exit;
}

$record = getMapatoPastorById($pdo, $recordId);
if (!$record) {
    header('Location: reports.php?report_type=correct_report');
    exit;
}

$intaraList = getAllIntara($pdo);
$monthOptions = imibareMonthOptions();
$message = '';

function parseStoredInput($value) {
    if (!$value || $value === '0') {
        return '';
    }
    $parts = explode('=', $value);
    return trim($parts[0]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
    $intara_id = $_POST['intara_id'] ?? '';
    $month_val = isset($_POST['month']) ? (int) $_POST['month'] : 0;
    $icyacumi = trim($_POST['icyacumi'] ?? '');
    $meeting = trim($_POST['meeting'] ?? '');
    $amaturo = trim($_POST['amaturo'] ?? '');
    $revival = trim($_POST['revival'] ?? '');
    $ss = trim($_POST['ss'] ?? '');
    $filide = trim($_POST['filide'] ?? '');
    $umusaruro = trim($_POST['umusaruro'] ?? '');
    $ituro = trim($_POST['ituro'] ?? '');

    $total = sumValues($icyacumi) + sumValues($meeting) + sumValues($amaturo)
        + sumValues($revival) + sumValues($ss) + sumValues($filide)
        + sumValues($umusaruro) + sumValues($ituro);

    if ($intara_id === '') {
        $message = '<div class="alert error">Hitamo Intara</div>';
    } elseif ($month_val < 1 || $month_val > 12) {
        $message = '<div class="alert error">Hitamo ukwezi</div>';
    } else {
        $data = [
            'intara_id' => $intara_id,
            'month' => $month_val,
            'icyacumi' => formatStoredValue($icyacumi),
            'meeting' => formatStoredValue($meeting),
            'amaturo' => formatStoredValue($amaturo, false),
            'revival' => formatStoredValue($revival),
            'ss' => formatStoredValue($ss),
            'filide' => formatStoredValue($filide),
            'umusaruro' => formatStoredValue($umusaruro),
            'ituro' => formatStoredValue($ituro),
            'total' => $total,
        ];
        if (updateMapatoPastor($pdo, $recordId, $data)) {
            header('Location: reports.php?report_type=correct_report&updated_pastor=1');
            exit;
        }
        $message = '<div class="alert error">Habaye ikibazo mu kuvugurura.</div>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/includes/material-icons-head.php'; ?>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-body">
<?php require __DIR__ . '/includes/nav.php'; ?>
<div class="container">
    <?= $message ?>
    <h2 class="page-title">Hindura Mapato ya Pastoro</h2>
    <form method="POST" style="max-width: 1000px;">
        <input type="hidden" name="record_id" value="<?= (int) $recordId ?>">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            <div class="form-group">
                <label>Intara:</label>
                <select name="intara_id" required>
                    <?php foreach ($intaraList as $intara): ?>
                        <option value="<?= $intara['id'] ?>" <?= (int)$record['intara_id'] === (int)$intara['id'] ? 'selected' : '' ?>><?= htmlspecialchars($intara['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Ukwezi:</label>
                <select name="month" required>
                    <?php foreach ($monthOptions as $m => $label): ?>
                        <option value="<?= (int) $m ?>" <?= (int)$record['month'] === (int)$m ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php
            $fields = [
                'icyacumi' => 'Icyacumi',
                'meeting' => 'CM (Meeting)',
                'amaturo' => 'Amaturo',
                'revival' => 'Revival',
                'ss' => 'SS Lesson',
                'filide' => 'Inyubako',
                'umusaruro' => 'Umusaruro',
                'ituro' => 'Ituro Rikuru',
            ];
            foreach ($fields as $key => $label):
                $stored = $key === 'meeting' ? mapatoPastorMeeting($record) : ($record[$key] ?? '');
            ?>
            <div class="form-group">
                <label><?= htmlspecialchars($label) ?>:</label>
                <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars(parseStoredInput($stored)) ?>" oninput="calcEdit()">
            </div>
            <?php endforeach; ?>
        </div>
        <p><b>Grand Total: <span id="edit_grand">0</span></b></p>
        <button type="submit" name="update_record">💾 UPDATE</button>
        <a href="reports.php?report_type=correct_report">← Back to Report</a>
    </form>
</div>
<script>
function sumValues(val) {
    if (!val) return 0;
    return val.replace(/\+/g, ',').split(',').map(x => parseFloat(x.trim()) || 0).reduce((a, b) => a + b, 0);
}
function calcEdit() {
    const ids = ['icyacumi','meeting','amaturo','revival','ss','filide','umusaruro','ituro'];
    let t = 0;
    ids.forEach(id => { t += sumValues(document.querySelector('[name="'+id+'"]').value); });
    document.getElementById('edit_grand').innerText = t;
}
calcEdit();
</script>
<?php require __DIR__ . '/includes/layout-end.php'; ?>
</body>
</html>
