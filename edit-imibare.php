<?php
require_once 'config.php';
require_once 'auth.php';

// Only admin can edit records
requireAdmin();

$recordId = $_GET['id'] ?? $_POST['record_id'] ?? null;
if (!$recordId) {
    header('Location: reports.php');
    exit;
}

$record = getImibareById($pdo, $recordId);
if (!$record) {
    header('Location: reports.php');
    exit;
}

$intaraList = getAllIntara($pdo);
$itoreroList = getItoreroByIntara($pdo, $record['intara_id']);
$monthOptions = imibareMonthOptions();
$message = '';

function sumValues($val) {
    if (!$val) return 0;
    $normalized = str_replace('+', ',', $val);
    return array_sum(array_map('floatval', array_filter(explode(',', $normalized), 'trim')));
}

function sumAmaturo($val) {
    return sumValues($val) / 2;
}

function formatField($input, $isAmaturo = false) {
    $s = sumValues($input);
    if ($isAmaturo) {
        return $input ? $input . ' = ' . $s . ' ÷ 2 = ' . ($s / 2) : '0';
    }
    return $input ? $input . ' = ' . $s : '0';
}

function parseStoredInput($value) {
    if (!$value || $value === '0') {
        return '';
    }
    $parts = explode('=', $value);
    return trim($parts[0]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
    $lesi = trim($_POST['lesi'] ?? '');
    $intara_id = $_POST['intara_id'] ?? '';
    $itorero_id = $_POST['itorero_id'] ?? '';
    $month_val = isset($_POST['month']) ? (int) $_POST['month'] : 0;
    // $ibindi = trim($_POST['ibindi'] ?? '');
    $ibindi = $_POST['ibindi'] ?? '';
    $icyacumi = trim($_POST['icyacumi'] ?? '');
    $icyacumi_cya_cms = trim($_POST['icyacumi_cya_cms'] ?? '');
    $amaturo = trim($_POST['amaturo'] ?? '');
    $amaturo_bya_cms = trim($_POST['amaturo_bya_cms'] ?? '');
    $umusaruro = trim($_POST['umusaruro'] ?? '');
    $ituro = trim($_POST['ituro'] ?? '');
    $filide = trim($_POST['filide'] ?? '');
    $ss = trim($_POST['ss'] ?? '');
    $ubusonga = trim($_POST['ubusonga'] ?? '');
    $mifem = trim($_POST['mifem'] ?? '');
    $ja = trim($_POST['ja'] ?? '');

    $sumIcyacumi = sumValues($icyacumi);
    $sumibindi = sumValues($ibindi);
    $sumIcyacumiCyaCms = sumValues($icyacumi_cya_cms);
    $sumAmaturo = sumAmaturo($amaturo);
    $sumAmaturoByaCms = sumAmaturo($amaturo_bya_cms);
    $sumUmusaruro = sumValues($umusaruro);
    $sumIturo = sumValues($ituro);
    $sumFilide = sumValues($filide);
    $sumSs = sumValues($ss);
    $sumUbusonga = sumValues($ubusonga);
    $sumMifem = sumValues($mifem);
    $sumJa = sumValues($ja);

    $total = $sumIcyacumi + $sumibindi + $sumIcyacumiCyaCms + $sumAmaturo + $sumAmaturoByaCms + $sumUmusaruro + $sumIturo +
             $sumFilide + $sumSs + $sumUbusonga + $sumMifem + $sumJa;

    if ($lesi === '') {
        $message = '<div class="alert error">Shyiramo Numero ya lesi</div>';
    } elseif ($intara_id === '') {
        $message = '<div class="alert error">Hitamo Intara</div>';
    } elseif ($month_val < 1 || $month_val > 12) {
        $message = '<div class="alert error">Hitamo ukwezi</div>';
    } else {
        $data = [
            'lesi' => $lesi,
            'intara_id' => $intara_id,
            'itorero_id' => $itorero_id ?: null,
            'month' => $month_val,
            'ibindi' => formatField($ibindi),
            'icyacumi' => formatField($icyacumi),
            'icyacumi_cya_cms' => formatField($icyacumi_cya_cms),
            'amaturo' => formatField($amaturo, true),
            'amaturo_bya_cms' => formatField($amaturo_bya_cms, true),
            'umusaruro' => formatField($umusaruro),
            'ituro' => formatField($ituro),
            'filide' => formatField($filide),
            'ss' => formatField($ss),
            'ubusonga' => formatField($ubusonga),
            'mifem' => formatField($mifem),
            'ja' => formatField($ja),
            'total' => $total
        ];

        if (updateImibare($pdo, $recordId, $data)) {
            header('Location: reports.php?updated=1');
            exit;
        }
        $message = '<div class="alert error">Habaye ikibazo mu kuvugurura.</div>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hindura Imibare - Church Ledger</title>
    <link rel="icon" type="image/png" href="sda.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="brand-header">
        <img class="brand-logo" src="sda.png" alt="Adventist logo">
        <div class="brand-text">
            <h2>Seventh Day Adventist church</h2>
            <small>Stewardship and offerings management</small>
        </div>
    </div>
    <div class="nav">
        <a href="index.php">📝 INSERT DATA</a>
        <a href="reports.php">📊 REPORT</a>
        <a href="logout.php" style="color: #dc3545;">🚪 LOG OUT</a>
    </div>

    <h2 class="page-title">UPDATE RECORD</h2>
    <?= $message ?>

    <form method="POST" style="max-width: 1000px; margin-left: auto;">
        <input type="hidden" name="record_id" value="<?= (int)$recordId ?>">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px 24px;">
            <div class="form-group">
                <label>Numero ya Lesi:</label>
                <input type="text" id="lesi" name="lesi" value="<?= htmlspecialchars($record['lesi']) ?>" required>
            </div>

            <div class="form-group">
                <label>Intara:</label>
                <select id="intara" name="intara_id" onchange="loadItorero()" required>
                    <option value="">-- Hitamo Intara --</option>
                    <?php foreach ($intaraList as $intara): ?>
                        <option value="<?= $intara['id'] ?>" <?= ((string)$record['intara_id'] === (string)$intara['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($intara['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Itorero:</label>
                <select id="itorero" name="itorero_id">
                    <option value="">-- Hitamo Itorero --</option>
                    <?php foreach ($itoreroList as $itorero): ?>
                        <option value="<?= $itorero['id'] ?>" <?= ((string)$record['itorero_id'] === (string)$itorero['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($itorero['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Ukwezi:</label>
                <select name="month" id="month" required>
                    <option value="">-- Hitamo ukwezi --</option>
                    <?php foreach ($monthOptions as $m => $label): ?>
                        <option value="<?= (int) $m ?>" <?= (isset($record['month']) && (string)$record['month'] === (string)$m) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- <div class="form-group" style="grid-column: 1 / -1;">
                <label>Ibindi:</label>
                <textarea name="ibindi" id="ibindi" rows="2"><?= htmlspecialchars($record['ibindi'] ?? '') ?></textarea>
            </div> -->

            <div class="form-group"><label>Icyacumi:</label><input type="text" id="icyacumi" name="icyacumi" value="<?= htmlspecialchars(parseStoredInput($record['icyacumi'])) ?>"></div>
            <div class="form-group"><label>Ibindi:</label><input type="text" id="ibindi" name="ibindi" value="<?= htmlspecialchars(parseStoredInput($record['ibindi'])) ?>"></div>
            <div class="form-group"><label>Icyacumi cya CFMS:</label><input type="text" id="icyacumi_cya_cms" name="icyacumi_cya_cms" value="<?= htmlspecialchars(parseStoredInput($record['icyacumi_cya_cms'])) ?>"></div>
            <div class="form-group"><label>Amaturo:</label><input type="text" id="amaturo" name="amaturo" value="<?= htmlspecialchars(parseStoredInput($record['amaturo'])) ?>"></div>
            <div class="form-group"><label>Amaturo bya CFMS:</label><input type="text" id="amaturo_bya_cms" name="amaturo_bya_cms" value="<?= htmlspecialchars(parseStoredInput($record['amaturo_bya_cms'])) ?>"></div>
            <div class="form-group"><label>Umusaruro:</label><input type="text" id="umusaruro" name="umusaruro" value="<?= htmlspecialchars(parseStoredInput($record['umusaruro'])) ?>"></div>
            <div class="form-group"><label>Ituro Rikuru:</label><input type="text" id="ituro" name="ituro" value="<?= htmlspecialchars(parseStoredInput($record['ituro'])) ?>"></div>
            <div class="form-group"><label>Inyubako ya Filide:</label><input type="text" id="filide" name="filide" value="<?= htmlspecialchars(parseStoredInput($record['filide'])) ?>"></div>
            <div class="form-group"><label>SS Lesson:</label><input type="text" id="ss" name="ss" value="<?= htmlspecialchars(parseStoredInput($record['ss'])) ?>"></div>
            <div class="form-group"><label>Udutabo tw'Ubusonga:</label><input type="text" id="ubusonga" name="ubusonga" value="<?= htmlspecialchars(parseStoredInput($record['ubusonga'])) ?>"></div>
            <div class="form-group"><label>Udutabo twa Mifem:</label><input type="text" id="mifem" name="mifem" value="<?= htmlspecialchars(parseStoredInput($record['mifem'])) ?>"></div>
            <div class="form-group"><label>Udutabo twa JA:</label><input type="text" id="ja" name="ja" value="<?= htmlspecialchars(parseStoredInput($record['ja'])) ?>"></div>
        </div>

        <div style="text-align: center; margin-top: 12px;">
            <button type="submit" name="update_record">💾 UPDATE</button>
            <a href="reports.php" style="margin-left: 10px;">Cancel</a>
        </div>
    </form>
</div>

<script>
function loadItorero() {
    const intaraId = document.getElementById('intara').value;
    const itoreroSelect = document.getElementById('itorero');
    const selected = itoreroSelect.value;

    itoreroSelect.innerHTML = '<option value="">-- Hitamo Itorero --</option>';
    if (!intaraId) return;

    fetch('get_itorero.php?intara_id=' + encodeURIComponent(intaraId))
        .then(response => response.json())
        .then(data => {
            data.forEach(itorero => {
                const option = document.createElement('option');
                option.value = itorero.id;
                option.textContent = itorero.name;
                if (String(itorero.id) === String(selected)) {
                    option.selected = true;
                }
                itoreroSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error:', error));
}
</script>
</body>
</html>
