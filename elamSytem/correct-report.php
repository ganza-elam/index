<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/imibare-math.php';

requireAdmin();

ensureCorrectReportTables($pdo);

$currentUser = getCurrentUser();
$intaraList = getAllIntara($pdo);
$itoreroList = getAllItorero($pdo);
$monthOptions = imibareMonthOptions();
$message = '';
$activeSection = $_GET['section'] ?? 'pastor';
if (!in_array($activeSection, ['pastor', 'bank'], true)) {
    $activeSection = 'pastor';
}

if (isset($_GET['change_bank_context'])) {
    unset($_SESSION['cr_bank_intara_id'], $_SESSION['cr_bank_month']);
    header('Location: correct-report.php?section=bank');
    exit;
}

$bankSessionIntaraId = isset($_SESSION['cr_bank_intara_id']) ? (string) $_SESSION['cr_bank_intara_id'] : '';
$bankSessionMonth = isset($_SESSION['cr_bank_month']) ? (int) $_SESSION['cr_bank_month'] : 0;

$pastorFieldKeys = [
    'icyacumi', 'icyacumi_cya_cms', 'meeting', 'amaturo', 'amaturo_bya_cms',
    'revival', 'ss', 'filide', 'umusaruro', 'ituro',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_bank_context'])) {
    $intara_id = $_POST['bank_intara_id'] ?? '';
    $month_val = isset($_POST['bank_month']) ? (int) $_POST['bank_month'] : 0;
    if (empty($intara_id)) {
        $message = '<div class="alert error">Hitamo Intara</div>';
        $activeSection = 'bank';
    } elseif ($month_val < 1 || $month_val > 12) {
        $message = '<div class="alert error">Hitamo ukwezi</div>';
        $activeSection = 'bank';
    } else {
        $_SESSION['cr_bank_intara_id'] = (int) $intara_id;
        $_SESSION['cr_bank_month'] = $month_val;
        header('Location: correct-report.php?section=bank');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pastor_mapato'])) {
    $intara_id = $_POST['intara_id'] ?? '';
    $month_val = isset($_POST['month']) ? (int) $_POST['month'] : 0;
    $itoreroNames = splitCommaList($_POST['itorero_names'] ?? '');

    if (empty($intara_id)) {
        $message = '<div class="alert error">Hitamo Intara</div>';
        $activeSection = 'pastor';
    } elseif ($itoreroNames === []) {
        $message = '<div class="alert error">Andika amazina y\'Amatorero atandukanyijwe na comma (,)</div>';
        $activeSection = 'pastor';
    } elseif ($month_val < 1 || $month_val > 12) {
        $message = '<div class="alert error">Hitamo ukwezi</div>';
        $activeSection = 'pastor';
    } else {
        $resolvedItorero = resolveItoreroNamesForIntara($pdo, (int) $intara_id, $itoreroNames);
        if ($resolvedItorero === null) {
            $message = '<div class="alert error">Hari Itorero ritabonetse muri iyi Intara. Reba amazina (comma-separated).</div>';
            $activeSection = 'pastor';
        } else {
            $n = count($resolvedItorero);
            $segmentsByField = [];
            $alignError = null;
            foreach ($pastorFieldKeys as $fieldKey) {
                $segmentsByField[$fieldKey] = alignCommaFieldSegments($_POST[$fieldKey] ?? '', $n);
                if ($segmentsByField[$fieldKey] === null) {
                    $alignError = $fieldKey;
                    break;
                }
            }
            if ($alignError !== null) {
                $message = '<div class="alert error">Imirongo y\'amafaranga igomba guhura n\'umubare w\'Amatorero (' . $n . '). Reba: <strong>' . htmlspecialchars($alignError) . '</strong></div>';
                $activeSection = 'pastor';
            } else {
                $savedCount = 0;
                foreach ($resolvedItorero as $idx => $it) {
                    $seg = [];
                    foreach ($pastorFieldKeys as $fieldKey) {
                        $seg[$fieldKey] = $segmentsByField[$fieldKey][$idx];
                    }
                    $total = sumValues($seg['icyacumi']) + sumValues($seg['icyacumi_cya_cms']) + sumValues($seg['meeting'])
                        + sumValues($seg['amaturo']) + sumValues($seg['amaturo_bya_cms'])
                        + sumValues($seg['revival']) + sumValues($seg['ss']) + sumValues($seg['filide'])
                        + sumValues($seg['umusaruro']) + sumValues($seg['ituro']);
                    $data = [
                        'intara_id' => $intara_id,
                        'itorero_id' => $it['id'],
                        'month' => $month_val,
                        'icyacumi' => formatStoredValue($seg['icyacumi']),
                        'icyacumi_cya_cms' => formatStoredValue($seg['icyacumi_cya_cms']),
                        'meeting' => formatStoredValue($seg['meeting']),
                        'amaturo' => formatStoredValue($seg['amaturo'], false),
                        'amaturo_bya_cms' => formatStoredValue($seg['amaturo_bya_cms'], false),
                        'revival' => formatStoredValue($seg['revival']),
                        'ss' => formatStoredValue($seg['ss']),
                        'filide' => formatStoredValue($seg['filide']),
                        'umusaruro' => formatStoredValue($seg['umusaruro']),
                        'ituro' => formatStoredValue($seg['ituro']),
                        'total' => $total,
                    ];
                    if (saveMapatoPastor($pdo, $data)) {
                        $savedCount++;
                    }
                }
                if ($savedCount === $n) {
                    $message = '<div class="alert success">Mapato ya Pastoro yabitswe neza kuri Amatorero ' . $savedCount . '!</div>';
                } elseif ($savedCount > 0) {
                    $message = '<div class="alert error">Bimwe byabitswe (' . $savedCount . '/' . $n . '); reba data usubiremo.</div>';
                } else {
                    $message = '<div class="alert error">Habaye ikibazo mu kubika mapato ya pastoro.</div>';
                }
                $activeSection = 'pastor';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bank_slip'])) {
    $intara_id = $_POST['bank_intara_id'] ?? $bankSessionIntaraId;
    $month_val = isset($_POST['bank_month']) ? (int) $_POST['bank_month'] : $bankSessionMonth;
    $slip_number = trim($_POST['slip_number'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $amount = (float) str_replace(',', '', $_POST['amount'] ?? '0');

    if (empty($intara_id)) {
        $message = '<div class="alert error">Banza hitamo Intara na Ukwezi (kanda Continue)</div>';
        $activeSection = 'bank';
    } elseif ($month_val < 1 || $month_val > 12) {
        $message = '<div class="alert error">Banza hitamo Intara na Ukwezi (kanda Continue)</div>';
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
            $_SESSION['cr_bank_intara_id'] = (int) $intara_id;
            $_SESSION['cr_bank_month'] = $month_val;
            $bankSessionIntaraId = (string) $intara_id;
            $bankSessionMonth = $month_val;
            header('Location: correct-report.php?section=bank&slip_saved=1');
            exit;
        } else {
            $message = '<div class="alert error">Habaye ikibazo mu kubika bank slip.</div>';
            $activeSection = 'bank';
        }
    }
}

if (isset($_GET['slip_saved'])) {
    $message = '<div class="alert success">Bank slip yabitswe neza! Shyiramo indi slip (Intara na Ukwezi biracyafite).</div>';
    $activeSection = 'bank';
}

$bankSessionIntaraName = '';
if ($bankSessionIntaraId !== '') {
    foreach ($intaraList as $intara) {
        if ((string) $intara['id'] === $bankSessionIntaraId) {
            $bankSessionIntaraName = $intara['name'];
            break;
        }
    }
}
$bankSessionMonthLabel = ($bankSessionMonth >= 1 && $bankSessionMonth <= 12)
    ? ($monthOptions[$bankSessionMonth] ?? '')
    : '';
$bankContextReady = $bankSessionIntaraId !== '' && $bankSessionMonth >= 1 && $bankSessionMonth <= 12;
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
    <p class="subtitle">Amatorero n'amafaranga: andika atandukanyijwe na <strong>comma (,)</strong> — icya mbere ni Itorero rya mbere, icya kabiri ni rya kabiri, n'ibindi. Mu gice kimwe ushobora gukoresha + (urugero 1000+2000).</p>

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
                    <select name="intara_id" id="cr_intara_id" required>
                        <option value="">-- Hitamo Intara --</option>
                        <?php foreach ($intaraList as $intara): ?>
                            <option value="<?= $intara['id'] ?>"><?= htmlspecialchars($intara['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Amatorero (comma-separated, kuva ku wa mbere kugeza ku wa nyuma):</label>
                    <input type="text" name="itorero_names" id="cr_itorero_names" placeholder="Itorero 1, Itorero 2, Itorero 3" required>
                    <small id="cr_itorero_hint" style="color:#666;display:block;margin-top:6px;">Hitamo Intara — amazina y'Amatorero yo muri iyo Intara azagaragara hepfo.</small>
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
                        <input type="text" id="icyacumi" name="icyacumi" placeholder="Urugero: 1000, 2000, 1500 (buri namba ku Itorero)" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s1">0</span></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Icyacumi cya CFMS:</label>
                    <div class="input-row">
                        <input type="text" id="icyacumi_cya_cms" name="icyacumi_cya_cms" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s1_cfms">0</span></span>
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
                        <input type="text" id="amaturo" name="amaturo" placeholder="Urugero: 5000, 3000 (RECU — buri Itorero)" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s2">0</span></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Amaturo ya CFMS:</label>
                    <div class="input-row">
                        <input type="text" id="amaturo_bya_cms" name="amaturo_bya_cms" placeholder="Urugero: 4000, 2500 (CFMS — buri Itorero)" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s2_cfms">0</span></span>
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

        <?php if (!$bankContextReady): ?>
        <p style="color:#666;margin-bottom:12px;">Hitamo <strong>Intara</strong> na <strong>Ukwezi</strong> rimwe — nyuma uzajya ushyiramo gusa numero, izina rya banki, n'amafaranga.</p>
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
            <div style="text-align: center; margin-top: 12px;">
                <button type="submit" name="set_bank_context">Continue — Injiza slips</button>
            </div>
        </form>
        <?php else: ?>
        <div class="alert" style="background:#e3f2fd;padding:12px;border-radius:8px;margin-bottom:16px;">
            <strong>Intara:</strong> <?= htmlspecialchars($bankSessionIntaraName) ?>
            &nbsp;|&nbsp;
            <strong>Ukwezi:</strong> <?= htmlspecialchars($bankSessionMonthLabel) ?>
            &nbsp;—&nbsp;
            <a href="correct-report.php?section=bank&amp;change_bank_context=1">Hindura Intara / Ukwezi</a>
        </div>
        <form method="POST" style="max-width: 600px;">
            <input type="hidden" name="bank_intara_id" value="<?= (int) $bankSessionIntaraId ?>">
            <input type="hidden" name="bank_month" value="<?= (int) $bankSessionMonth ?>">
            <div class="form-group">
                <label>Numero ya Bank Slip:</label>
                <input type="text" name="slip_number" placeholder="Numero idasanzwe" required autofocus>
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
        <?php endif; ?>
    </div>
</div>

<script>
function sumValues(val) {
    if (!val) return 0;
    return val.replace(/\+/g, ',').split(',').map(x => parseFloat(x.trim()) || 0).reduce((a, b) => a + b, 0);
}

function calcPastor() {
    const icy = sumValues(document.getElementById('icyacumi').value);
    const icyCfms = sumValues(document.getElementById('icyacumi_cya_cms').value);
    const meeting = sumValues(document.getElementById('meeting').value);
    const ama = sumValues(document.getElementById('amaturo').value);
    const amaCfms = sumValues(document.getElementById('amaturo_bya_cms').value);
    const rev = sumValues(document.getElementById('revival').value);
    const ss = sumValues(document.getElementById('ss').value);
    const fil = sumValues(document.getElementById('filide').value);
    const umu = sumValues(document.getElementById('umusaruro').value);
    const itu = sumValues(document.getElementById('ituro').value);

    document.getElementById('p_s1').innerText = icy;
    document.getElementById('p_s1_cfms').innerText = icyCfms;
    document.getElementById('p_s_meeting').innerText = meeting;
    document.getElementById('p_s2').innerText = ama;
    document.getElementById('p_s2_cfms').innerText = amaCfms;
    document.getElementById('p_s_rev').innerText = rev;
    document.getElementById('p_s6').innerText = ss;
    document.getElementById('p_s5').innerText = fil;
    document.getElementById('p_s3').innerText = umu;
    document.getElementById('p_s4').innerText = itu;
    calcPastorGrand();
}

const CR_ITORERO_BY_INTARA = <?= json_encode(array_reduce($itoreroList, function ($carry, $it) {
    $iid = (string) $it['intara_id'];
    if (!isset($carry[$iid])) {
        $carry[$iid] = [];
    }
    $carry[$iid][] = $it['name'];
    return $carry;
}, []), JSON_UNESCAPED_UNICODE) ?>;

const crIntaraSelect = document.getElementById('cr_intara_id');
const crItoreroHint = document.getElementById('cr_itorero_hint');
if (crIntaraSelect && crItoreroHint) {
    function updateItoreroHint() {
        const intaraId = crIntaraSelect.value;
        const names = CR_ITORERO_BY_INTARA[intaraId] || [];
        if (!intaraId) {
            crItoreroHint.textContent = 'Hitamo Intara — amazina y\'Amatorero yo muri iyo Intara azagaragara hepfo.';
            return;
        }
        if (names.length === 0) {
            crItoreroHint.textContent = 'Nta maturo ahari muri iyi Intara.';
            return;
        }
        crItoreroHint.textContent = 'Amatorero muri iyi Intara (urugero): ' + names.join(', ');
    }
    crIntaraSelect.addEventListener('change', updateItoreroHint);
    updateItoreroHint();
}

function sumPastorSegment(val) {
    if (!val) return 0;
    return val.replace(/\+/g, ',').split(',').map(x => parseFloat(x.trim()) || 0).reduce((a, b) => a + b, 0);
}

function calcPastorGrand() {
    const n = Math.max(1, (document.getElementById('cr_itorero_names').value || '').split(',').filter(s => s.trim() !== '').length);
    const fieldIds = ['icyacumi', 'icyacumi_cya_cms', 'meeting', 'amaturo', 'amaturo_bya_cms', 'revival', 'ss', 'filide', 'umusaruro', 'ituro'];
    let grand = 0;
    fieldIds.forEach(function (id) {
        const raw = document.getElementById(id).value || '';
        const parts = raw.split(',').map(s => s.trim()).filter(s => s !== '');
        if (parts.length === 0) return;
        const use = parts.length === 1 && n > 1 ? Array(n).fill(parts[0]) : parts;
        use.forEach(function (seg) { grand += sumPastorSegment(seg); });
    });
    document.getElementById('p_grand').innerText = grand;
}
</script>
<?php require __DIR__ . '/includes/layout-end.php'; ?>
</body>
</html>
