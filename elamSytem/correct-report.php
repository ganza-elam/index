<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/imibare-math.php';

requireAdmin();

ensureCorrectReportTables($pdo);

$currentUser = getCurrentUser();
$intaraList = getAllIntara($pdo);
$monthOptions = imibareMonthOptions();
$message = '';
$activeSection = $_GET['section'] ?? 'pastor';
if (!in_array($activeSection, ['pastor', 'bank'], true)) {
    $activeSection = 'pastor';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pastor_mapato'])) {
    $intara_id = $_POST['intara_id'] ?? '';
    $month_val = isset($_POST['month']) ? (int) $_POST['month'] : 0;

    $icyacumi = $_POST['icyacumi'] ?? '';
    $meeting = $_POST['meeting'] ?? '';
    $amaturo = $_POST['amaturo'] ?? '';
    $revival = $_POST['revival'] ?? '';
    $ss = $_POST['ss'] ?? '';
    $filide = $_POST['filide'] ?? '';
    $umusaruro = $_POST['umusaruro'] ?? '';
    $ituro = $_POST['ituro'] ?? '';

    $total = sumValues($icyacumi) + sumValues($meeting) + sumValues($amaturo)
        + sumValues($revival) + sumValues($ss) + sumValues($filide)
        + sumValues($umusaruro) + sumValues($ituro);

    if (empty($intara_id)) {
        $message = '<div class="alert error">Hitamo Intara</div>';
        $activeSection = 'pastor';
    } elseif ($month_val < 1 || $month_val > 12) {
        $message = '<div class="alert error">Hitamo ukwezi</div>';
        $activeSection = 'pastor';
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
        if (saveMapatoPastor($pdo, $data)) {
            $message = '<div class="alert success">Mapato ya Pastoro yabitswe neza!</div>';
            $activeSection = 'pastor';
        } else {
            $message = '<div class="alert error">Habaye ikibazo mu kubika mapato ya pastoro.</div>';
            $activeSection = 'pastor';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bank_slip'])) {
    $intara_id = $_POST['bank_intara_id'] ?? '';
    $month_val = isset($_POST['bank_month']) ? (int) $_POST['bank_month'] : 0;
    $slip_number = trim($_POST['slip_number'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $amount = (float) str_replace(',', '', $_POST['amount'] ?? '0');

    if (empty($intara_id)) {
        $message = '<div class="alert error">Hitamo Intara</div>';
        $activeSection = 'bank';
    } elseif ($month_val < 1 || $month_val > 12) {
        $message = '<div class="alert error">Hitamo ukwezi</div>';
        $activeSection = 'bank';
    } elseif ($slip_number === '') {
        $message = '<div class="alert error">Shyiramo numero ya bank slip</div>';
        $activeSection = 'bank';
    } elseif ($bank_name === '') {
        $message = '<div class="alert error">Shyiramo izina rya banki</div>';
        $activeSection = 'bank';
    } elseif ($amount <= 0) {
        $message = '<div class="alert error">Shyiramo amafaranga yemewe</div>';
        $activeSection = 'bank';
    } elseif (bankSlipNumberExists($pdo, $slip_number)) {
        $message = '<div class="alert error">Numero ya bank slip isanzwe ibashyizwemo. Ntushobora kuyongera.</div>';
        $activeSection = 'bank';
    } else {
        if (saveBankSlip($pdo, [
            'intara_id' => $intara_id,
            'month' => $month_val,
            'slip_number' => $slip_number,
            'bank_name' => $bank_name,
            'amount' => $amount,
        ])) {
            $message = '<div class="alert success">Bank slip yabitswe neza!</div>';
            $activeSection = 'bank';
        } else {
            $message = '<div class="alert error">Habaye ikibazo mu kubika bank slip.</div>';
            $activeSection = 'bank';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/includes/material-icons-head.php'; ?>
    <link rel="stylesheet" href="styles.css">
    <style>
        .cr-tabs { display: flex; gap: 8px; margin: 20px 0; flex-wrap: wrap; }
        .cr-tabs a {
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            background: #f0f0f0;
            color: #333;
            font-weight: 600;
        }
        .cr-tabs a.active { background: #1976d2; color: #fff; }
        .cr-section { display: none; }
        .cr-section.active { display: block; }
    </style>
</head>
<body class="app-body" data-skip-page-sections="1">
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

    <h2 class="page-title">IBYAKIRIWE KURI RAPORT</h2>
    <p class="subtitle">Urugero: 1000+2000+500 — Amaturo ntabwo agabanywamo kabiri muri raporo</p>

    <div class="cr-tabs">
        <a href="?section=pastor" class="<?= $activeSection === 'pastor' ? 'active' : '' ?>">1. Insert Mapato from the Pastor</a>
        <a href="?section=bank" class="<?= $activeSection === 'bank' ? 'active' : '' ?>">2. Take Bank Slip</a>
    </div>

    <div id="section-pastor" class="cr-section <?= $activeSection === 'pastor' ? 'active' : '' ?>">
        <h3>Insert Mapato from the Pastor</h3>
        <form method="POST" style="max-width: 1000px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px 24px;">
                <div class="form-group">
                    <label>Intara:</label>
                    <select name="intara_id" required>
                        <option value="">-- Hitamo Intara --</option>
                        <?php foreach ($intaraList as $intara): ?>
                            <option value="<?= $intara['id'] ?>"><?= htmlspecialchars($intara['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ukwezi:</label>
                    <select name="month" required>
                        <option value="">-- Hitamo ukwezi --</option>
                        <?php foreach ($monthOptions as $m => $label): ?>
                            <option value="<?= (int) $m ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Icyacumi (Grand Total):</label>
                    <div class="input-row">
                        <input type="text" id="icyacumi" name="icyacumi" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s1">0</span></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>CM (Meeting):</label>
                    <div class="input-row">
                        <input type="text" id="meeting" name="meeting" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s_meeting">0</span></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Amaturo (Grand Total):</label>
                    <div class="input-row">
                        <input type="text" id="amaturo" name="amaturo" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s2">0</span></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Revival:</label>
                    <div class="input-row">
                        <input type="text" id="revival" name="revival" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s_rev">0</span></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>SS Lesson:</label>
                    <div class="input-row">
                        <input type="text" id="ss" name="ss" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s6">0</span></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Inyubako (Filide):</label>
                    <div class="input-row">
                        <input type="text" id="filide" name="filide" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s5">0</span></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Umusaruro:</label>
                    <div class="input-row">
                        <input type="text" id="umusaruro" name="umusaruro" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s3">0</span></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ituro ry'iteraniro rikuru:</label>
                    <div class="input-row">
                        <input type="text" id="ituro" name="ituro" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s4">0</span></span>
                    </div>
                </div>
            </div>
            <div style="text-align: center; margin-top: 12px;">
                <p><b>Grand Total: <span id="p_grand">0</span></b></p>
                <button type="submit" name="save_pastor_mapato">💾 SAVE Mapato ya Pastoro</button>
            </div>
        </form>
    </div>

    <div id="section-bank" class="cr-section <?= $activeSection === 'bank' ? 'active' : '' ?>">
        <h3>Take Bank Slip</h3>
        <form method="POST" style="max-width: 600px;">
            <div class="form-group">
                <label>Intara:</label>
                <select name="bank_intara_id" required>
                    <option value="">-- Hitamo Intara --</option>
                    <?php foreach ($intaraList as $intara): ?>
                        <option value="<?= $intara['id'] ?>"><?= htmlspecialchars($intara['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Ukwezi:</label>
                <select name="bank_month" required>
                    <option value="">-- Hitamo ukwezi --</option>
                    <?php foreach ($monthOptions as $m => $label): ?>
                        <option value="<?= (int) $m ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Numero ya Bank Slip:</label>
                <input type="text" name="slip_number" placeholder="Numero idasanzwe" required>
            </div>
            <div class="form-group">
                <label>Izina rya Banki:</label>
                <input type="text" name="bank_name" placeholder="Urugero: BK, Equity..." required>
            </div>
            <div class="form-group">
                <label>Amafaranga (Amount):</label>
                <input type="number" name="amount" min="0.01" step="0.01" placeholder="0" required>
            </div>
            <div style="text-align: center; margin-top: 12px;">
                <button type="submit" name="save_bank_slip">💾 SAVE Bank Slip</button>
            </div>
        </form>
    </div>
</div>

<script>
function sumValues(val) {
    if (!val) return 0;
    return val.replace(/\+/g, ',').split(',').map(x => parseFloat(x.trim()) || 0).reduce((a, b) => a + b, 0);
}

function calcPastor() {
    const icy = sumValues(document.getElementById('icyacumi').value);
    const meeting = sumValues(document.getElementById('meeting').value);
    const ama = sumValues(document.getElementById('amaturo').value);
    const rev = sumValues(document.getElementById('revival').value);
    const ss = sumValues(document.getElementById('ss').value);
    const fil = sumValues(document.getElementById('filide').value);
    const umu = sumValues(document.getElementById('umusaruro').value);
    const itu = sumValues(document.getElementById('ituro').value);

    document.getElementById('p_s1').innerText = icy;
    document.getElementById('p_s_meeting').innerText = meeting;
    document.getElementById('p_s2').innerText = ama;
    document.getElementById('p_s_rev').innerText = rev;
    document.getElementById('p_s6').innerText = ss;
    document.getElementById('p_s5').innerText = fil;
    document.getElementById('p_s3').innerText = umu;
    document.getElementById('p_s4').innerText = itu;
    document.getElementById('p_grand').innerText = icy + meeting + ama + rev + ss + fil + umu + itu;
}
</script>
<?php require __DIR__ . '/includes/layout-end.php'; ?>
</body>
</html>
