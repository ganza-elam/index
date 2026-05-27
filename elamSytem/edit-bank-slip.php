<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/icons.php';

requireAdmin();
ensureCorrectReportTables($pdo);

$recordId = $_GET['id'] ?? $_POST['record_id'] ?? null;
if (!$recordId) {
    header('Location: reports.php?report_type=correct_report');
    exit;
}

$record = getBankSlipById($pdo, $recordId);
if (!$record) {
    header('Location: reports.php?report_type=correct_report');
    exit;
}

$intaraList = getAllIntara($pdo);
$monthOptions = imibareMonthOptions();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
    $intara_id = $_POST['intara_id'] ?? '';
    $month_val = isset($_POST['month']) ? (int) $_POST['month'] : 0;
    $slip_number = trim($_POST['slip_number'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $amount = (float) str_replace(',', '', $_POST['amount'] ?? '0');

    if ($intara_id === '') {
        $message = '<div class="alert error">Hitamo Intara</div>';
    } elseif ($month_val < 1 || $month_val > 12) {
        $message = '<div class="alert error">Hitamo ukwezi</div>';
    } elseif ($slip_number === '') {
        $message = '<div class="alert error">Shyiramo numero ya bank slip</div>';
    } elseif ($bank_name === '') {
        $message = '<div class="alert error">Shyiramo izina rya banki</div>';
    } elseif ($amount <= 0) {
        $message = '<div class="alert error">Shyiramo amafaranga yemewe</div>';
    } elseif (bankSlipNumberExists($pdo, $slip_number, $recordId)) {
        $message = '<div class="alert error">Numero ya bank slip isanzwe ikoreshwa.</div>';
    } else {
        if (updateBankSlip($pdo, $recordId, [
            'intara_id' => $intara_id,
            'month' => $month_val,
            'slip_number' => $slip_number,
            'bank_name' => $bank_name,
            'amount' => $amount,
        ])) {
            header('Location: reports.php?report_type=correct_report&updated_slip=1');
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
    <h2 class="page-title">Hindura Bank Slip</h2>
    <form method="POST" style="max-width: 600px;">
        <input type="hidden" name="record_id" value="<?= (int) $recordId ?>">
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
                    <option value="<?= (int) $m ?>" <?= (int)($record['month'] ?? 0) === (int)$m ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Numero ya Bank Slip:</label>
            <input type="text" name="slip_number" value="<?= htmlspecialchars($record['slip_number']) ?>" required>
        </div>
        <div class="form-group">
            <label>Izina rya Banki:</label>
            <input type="text" name="bank_name" value="<?= htmlspecialchars($record['bank_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Amafaranga:</label>
            <input type="number" name="amount" min="0.01" step="0.01" value="<?= htmlspecialchars($record['amount']) ?>" required>
        </div>
        <button type="submit" name="update_record">💾 UPDATE</button>
        <a href="reports.php?report_type=correct_report">← Back to Report</a>
    </form>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>
</body>
</html>
