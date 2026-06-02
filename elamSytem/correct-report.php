<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/imibare-math.php';
require_once __DIR__ . '/includes/mapato-pastor-fields.php';

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

$pastorFieldKeys = mapatoPastorStaticFieldKeys();
$pastorFieldLabels = mapatoPastorStaticFieldLabels();
$pastorForm = null;
$pastorHighlightIds = [];

function capturePastorFormFromPost(array $fieldKeys): array {
    $form = [
        'intara_id' => trim((string) ($_POST['intara_id'] ?? '')),
        'itorero_names' => trim((string) ($_POST['itorero_names'] ?? '')),
        'month' => trim((string) ($_POST['month'] ?? '')),
        'extra_field_defs_json' => (string) ($_POST['extra_field_defs_json'] ?? '[]'),
        'extra_field' => is_array($_POST['extra_field'] ?? null) ? $_POST['extra_field'] : [],
    ];
    foreach ($fieldKeys as $key) {
        $form[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    return $form;
}

function crPastorVal(?array $form, string $key): string {
    if ($form === null) {
        return '';
    }
    return htmlspecialchars((string) ($form[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}

function crPastorSelected(?array $form, string $key, $optionValue): string {
    if ($form === null) {
        return '';
    }
    return (string) ($form[$key] ?? '') === (string) $optionValue ? ' selected' : '';
}

function crPastorFieldClass(?array $form, array $highlightIds, string $elementId): string {
    if ($form !== null && in_array($elementId, $highlightIds, true)) {
        return ' field-error';
    }
    return '';
}

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
    $pastorForm = capturePastorFormFromPost($pastorFieldKeys);
    $intara_id = $pastorForm['intara_id'];
    $month_val = isset($pastorForm['month']) ? (int) $pastorForm['month'] : 0;
    $itoreroNames = splitCommaList($pastorForm['itorero_names']);

    if (empty($intara_id)) {
        $message = '<div class="alert error">Hitamo Intara</div>';
        $pastorHighlightIds[] = 'cr_intara_id';
        $activeSection = 'pastor';
    } elseif ($itoreroNames === []) {
        $message = '<div class="alert error">Andika amazina y\'Amatorero atandukanyijwe na comma (,)</div>';
        $pastorHighlightIds[] = 'cr_itorero_names';
        $activeSection = 'pastor';
    } elseif ($month_val < 1 || $month_val > 12) {
        $message = '<div class="alert error">Hitamo ukwezi</div>';
        $pastorHighlightIds[] = 'cr_month';
        $activeSection = 'pastor';
    } else {
        $resolvedItorero = resolveItoreroNamesForIntara($pdo, (int) $intara_id, $itoreroNames);
        if ($resolvedItorero === null) {
            $message = '<div class="alert error">Hari Itorero ritabonetse muri iyi Intara. Reba amazina (comma-separated).</div>';
            $pastorHighlightIds[] = 'cr_itorero_names';
            $activeSection = 'pastor';
        } else {
            $n = count($resolvedItorero);
            $segmentsByField = [];
            $alignError = null;
            foreach ($pastorFieldKeys as $fieldKey) {
                $segmentsByField[$fieldKey] = alignCommaFieldSegments($pastorForm[$fieldKey] ?? '', $n);
                if ($segmentsByField[$fieldKey] === null) {
                    $alignError = $fieldKey;
                    break;
                }
            }
            if ($alignError !== null) {
                $alignLabel = $pastorFieldLabels[$alignError] ?? $alignError;
                $message = '<div class="alert error">Imirongo y\'amafaranga igomba guhura n\'umubare w\'Amatorero (' . $n . '). Reba: <strong>' . htmlspecialchars($alignLabel) . '</strong></div>';
                $pastorHighlightIds[] = $alignError;
                $activeSection = 'pastor';
            } else {
                $extraLabelBySlug = [];
                $extraDefsRaw = json_decode($pastorForm['extra_field_defs_json'] ?? '[]', true);
                if (is_array($extraDefsRaw)) {
                    foreach ($extraDefsRaw as $def) {
                        $slug = trim((string) ($def['slug'] ?? ''));
                        $label = trim((string) ($def['label'] ?? ''));
                        if ($slug !== '' && $label !== '') {
                            $extraLabelBySlug[$slug] = $label;
                        }
                    }
                }
                syncMapatoPastorFieldDefs($pdo, (int) $intara_id, $month_val, $extraLabelBySlug);

                $extraSlugs = array_keys($extraLabelBySlug);
                $extraSegmentsBySlug = [];
                $extraAlignError = null;
                $extraAlignErrorSlug = null;
                $extraPost = $pastorForm['extra_field'];
                foreach ($extraSlugs as $slug) {
                    $extraSegmentsBySlug[$slug] = alignCommaFieldSegments($extraPost[$slug] ?? '', $n);
                    if ($extraSegmentsBySlug[$slug] === null) {
                        $extraAlignError = $extraLabelBySlug[$slug] ?? $slug;
                        $extraAlignErrorSlug = $slug;
                        break;
                    }
                }
                if ($extraAlignError !== null) {
                    $message = '<div class="alert error">Imirongo y\'inzego nshya igomba guhura n\'umubare w\'Amatorero (' . $n . '). Reba: <strong>' . htmlspecialchars($extraAlignError) . '</strong></div>';
                    if ($extraAlignErrorSlug !== null) {
                        $pastorHighlightIds[] = 'extra_field_' . $extraAlignErrorSlug;
                    }
                    $activeSection = 'pastor';
                } else {
                $savedCount = 0;
                foreach ($resolvedItorero as $idx => $it) {
                    $seg = [];
                    foreach ($pastorFieldKeys as $fieldKey) {
                        $seg[$fieldKey] = $segmentsByField[$fieldKey][$idx];
                    }
                    $extraSegValues = [];
                    $extraStored = [];
                    foreach ($extraSlugs as $slug) {
                        $extraSegValues[] = $extraSegmentsBySlug[$slug][$idx];
                        $extraStored[$slug] = formatStoredValue($extraSegmentsBySlug[$slug][$idx]);
                    }
                    $total = sumMapatoPastorRecordTotal($seg, $extraSegValues);
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
                        'mifem' => formatStoredValue($seg['mifem']),
                        'extra_fields' => encodeMapatoPastorExtraFields($extraStored),
                        'total' => $total,
                        'inserted_by' => (int) ($currentUser['id'] ?? 0),
                    ];
                    if (saveMapatoPastor($pdo, $data)) {
                        $savedCount++;
                    }
                }
                if ($savedCount === $n) {
                    $message = '<div class="alert success">Mapato ya Pastoro yabitswe neza kuri Amatorero ' . $savedCount . '!</div>';
                    $pastorForm = null;
                    $pastorHighlightIds = [];
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

$pastorRepublishJson = 'null';
if ($pastorForm !== null) {
    $extraDefsForJs = json_decode($pastorForm['extra_field_defs_json'] ?? '[]', true);
    if (!is_array($extraDefsForJs)) {
        $extraDefsForJs = [];
    }
    $pastorRepublishJson = json_encode([
        'extra_defs' => $extraDefsForJs,
        'extra_field' => $pastorForm['extra_field'],
    ], JSON_UNESCAPED_UNICODE);
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
        .cr-extra-fields-zone {
            grid-column: 1 / -1;
            border: 2px dashed #90caf9;
            border-radius: 10px;
            padding: 16px;
            margin-top: 8px;
            background: #f8fbff;
        }
        .cr-extra-fields-zone h4 { margin: 0 0 12px; font-size: 1rem; color: #1565c0; }
        .cr-add-field-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
            margin-bottom: 16px;
        }
        .cr-add-field-row input[type="text"] { flex: 1; min-width: 160px; }
        .cr-add-field-row .cr-add-value { max-width: 180px; }
        #cr_extra_fields_container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px 24px;
        }
        .cr-grand-total-bar {
            text-align: center;
            margin-top: 16px;
            padding: 14px;
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border-radius: 10px;
        }
        .cr-grand-total-bar b { font-size: 1.15rem; }
        #p_grand { color: #1976d2; font-size: 1.35rem; }
        .form-group.field-error label { color: #c62828; }
        .form-group.field-error input,
        .form-group.field-error select {
            border-color: #c62828;
            box-shadow: 0 0 0 2px rgba(198, 40, 40, 0.2);
        }
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
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'cr_intara_id') ?>">
                    <label>Intara:</label>
                    <select name="intara_id" id="cr_intara_id" required>
                        <option value="">-- Hitamo Intara --</option>
                        <?php foreach ($intaraList as $intara): ?>
                            <option value="<?= $intara['id'] ?>"<?= crPastorSelected($pastorForm, 'intara_id', $intara['id']) ?>><?= htmlspecialchars($intara['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'cr_itorero_names') ?>" style="grid-column: 1 / -1;">
                    <label>Amatorero (comma-separated, kuva ku wa mbere kugeza ku wa nyuma):</label>
                    <input type="text" name="itorero_names" id="cr_itorero_names" value="<?= crPastorVal($pastorForm, 'itorero_names') ?>" placeholder="Itorero 1, Itorero 2, Itorero 3" required>
                    <small id="cr_itorero_hint" style="color:#666;display:block;margin-top:6px;">Hitamo Intara — amazina y'Amatorero yo muri iyo Intara azagaragara hepfo.</small>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'cr_month') ?>">
                    <label>Ukwezi:</label>
                    <select name="month" id="cr_month" required>
                        <option value="">-- Hitamo ukwezi --</option>
                        <?php foreach ($monthOptions as $m => $label): ?>
                            <option value="<?= (int) $m ?>"<?= crPastorSelected($pastorForm, 'month', $m) ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'icyacumi') ?>">
                    <label>Icyacumi (Grand Total):</label>
                    <div class="input-row">
                        <input type="text" id="icyacumi" name="icyacumi" value="<?= crPastorVal($pastorForm, 'icyacumi') ?>" placeholder="Urugero: 1000, 2000, 1500 (buri namba ku Itorero)" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s1">0</span></span>
                    </div>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'icyacumi_cya_cms') ?>">
                    <label>Icyacumi cya CFMS:</label>
                    <div class="input-row">
                        <input type="text" id="icyacumi_cya_cms" name="icyacumi_cya_cms" value="<?= crPastorVal($pastorForm, 'icyacumi_cya_cms') ?>" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s1_cfms">0</span></span>
                    </div>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'meeting') ?>">
                    <label>CM (Meeting):</label>
                    <div class="input-row">
                        <input type="text" id="meeting" name="meeting" value="<?= crPastorVal($pastorForm, 'meeting') ?>" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s_meeting">0</span></span>
                    </div>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'amaturo') ?>">
                    <label>Amaturo (Grand Total):</label>
                    <div class="input-row">
                        <input type="text" id="amaturo" name="amaturo" value="<?= crPastorVal($pastorForm, 'amaturo') ?>" placeholder="Urugero: 5000, 3000 (RECU — buri Itorero)" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s2">0</span></span>
                    </div>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'amaturo_bya_cms') ?>">
                    <label>Amaturo ya CFMS:</label>
                    <div class="input-row">
                        <input type="text" id="amaturo_bya_cms" name="amaturo_bya_cms" value="<?= crPastorVal($pastorForm, 'amaturo_bya_cms') ?>" placeholder="Urugero: 4000, 2500 (CFMS — buri Itorero)" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s2_cfms">0</span></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Amaturo Total (RECU + CFMS) &amp; ÷2:</label>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                        <span class="sum"><strong>Total:</strong> <span id="p_s2_pair">0</span></span>
                        <span class="sum"><strong>÷2:</strong> <span id="p_s2_half">0</span></span>
                    </div>
                    <small style="color:#666;display:block;margin-top:6px;">Iyi (RECU + CFMS) ÷ 2 niyo ibarwa muri Grand Total na raporo.</small>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'revival') ?>">
                    <label>Revival:</label>
                    <div class="input-row">
                        <input type="text" id="revival" name="revival" value="<?= crPastorVal($pastorForm, 'revival') ?>" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s_rev">0</span></span>
                    </div>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'ss') ?>">
                    <label>SS Lesson:</label>
                    <div class="input-row">
                        <input type="text" id="ss" name="ss" value="<?= crPastorVal($pastorForm, 'ss') ?>" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s6">0</span></span>
                    </div>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'filide') ?>">
                    <label>Inyubako (Filide):</label>
                    <div class="input-row">
                        <input type="text" id="filide" name="filide" value="<?= crPastorVal($pastorForm, 'filide') ?>" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s5">0</span></span>
                    </div>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'umusaruro') ?>">
                    <label>Umusaruro:</label>
                    <div class="input-row">
                        <input type="text" id="umusaruro" name="umusaruro" value="<?= crPastorVal($pastorForm, 'umusaruro') ?>" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s3">0</span></span>
                    </div>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'ituro') ?>">
                    <label>Udutabo twa JA:</label>
                    <div class="input-row">
                        <input type="text" id="ituro" name="ituro" value="<?= crPastorVal($pastorForm, 'ituro') ?>" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s4">0</span></span>
                    </div>
                </div>
                <div class="form-group<?= crPastorFieldClass($pastorForm, $pastorHighlightIds, 'mifem') ?>">
                    <label>Udutabo twa Mifem:</label>
                    <div class="input-row">
                        <input type="text" id="mifem" name="mifem" value="<?= crPastorVal($pastorForm, 'mifem') ?>" placeholder="Urugero: 1000+2000" oninput="calcPastor()">
                        <span class="sum">= <span id="p_s_mifem">0</span></span>
                    </div>
                </div>

                <div class="cr-extra-fields-zone">
                    <h4>+ Ongeraho inzego nshya (add new field)</h4>
                    <div class="cr-add-field-row">
                        <div class="form-group" style="margin:0;flex:1;">
                            <label>Izina ry'inzego nshya (field name):</label>
                            <input type="text" id="cr_new_field_label" placeholder="Urugero: Udutabo twa SS">
                        </div>
                        <div class="form-group cr-add-value" style="margin:0;">
                            <label>Value (optional):</label>
                            <input type="text" id="cr_new_field_value" placeholder="1000+2000">
                        </div>
                        <button type="button" class="btn-icon" id="cr_add_extra_field_btn" style="margin-bottom:2px;">+ Ongeraho</button>
                    </div>
                    <div id="cr_extra_fields_container"></div>
                </div>
                <input type="hidden" name="extra_field_defs_json" id="extra_field_defs_json" value="<?= $pastorForm !== null ? htmlspecialchars($pastorForm['extra_field_defs_json'], ENT_QUOTES, 'UTF-8') : '[]' ?>">
            </div>
            <div class="cr-grand-total-bar">
                <p style="margin:0;"><b>Grand Total: <span id="p_grand">0</span></b></p>
                <button type="submit" name="save_pastor_mapato" style="margin-top:12px;">💾 SAVE Mapato ya Pastoro</button>
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
const CR_PASTOR_REPUBLISH = <?= $pastorRepublishJson ?>;
const CR_PASTOR_HIGHLIGHT_IDS = <?= json_encode(array_values($pastorHighlightIds), JSON_UNESCAPED_UNICODE) ?>;

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
    const mif = sumValues(document.getElementById('mifem').value);

    document.getElementById('p_s1').innerText = icy;
    document.getElementById('p_s1_cfms').innerText = icyCfms;
    document.getElementById('p_s_meeting').innerText = meeting;
    document.getElementById('p_s2').innerText = ama;
    document.getElementById('p_s2_cfms').innerText = amaCfms;
    const amaPair = ama + amaCfms;
    document.getElementById('p_s2_pair').innerText = amaPair;
    document.getElementById('p_s2_half').innerText = amaPair / 2;
    document.getElementById('p_s_rev').innerText = rev;
    document.getElementById('p_s6').innerText = ss;
    document.getElementById('p_s5').innerText = fil;
    document.getElementById('p_s3').innerText = umu;
    document.getElementById('p_s4').innerText = itu;
    document.getElementById('p_s_mifem').innerText = mif;
    document.querySelectorAll('.cr-extra-sum').forEach(function (el) {
        const input = document.getElementById(el.dataset.for);
        el.innerText = input ? sumValues(input.value) : 0;
    });
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

function expandSegments(raw, n) {
    const parts = (raw || '').split(',').map(s => s.trim()).filter(s => s !== '');
    if (parts.length === 0) return [];
    return (parts.length === 1 && n > 1) ? Array(n).fill(parts[0]) : parts;
}

function calcPastorGrand() {
    const n = Math.max(1, (document.getElementById('cr_itorero_names').value || '').split(',').filter(s => s.trim() !== '').length);
    let grand = 0;
    // Normal fields
    ['icyacumi', 'icyacumi_cya_cms', 'meeting', 'revival', 'ss', 'filide', 'umusaruro', 'ituro', 'mifem'].forEach(function (id) {
        expandSegments((document.getElementById(id).value || ''), n).forEach(function (seg) {
            grand += sumPastorSegment(seg);
        });
    });
    // Amaturo rule: (RECU + CFMS) ÷ 2
    const amaSegs = expandSegments((document.getElementById('amaturo').value || ''), n);
    const amaCfmsSegs = expandSegments((document.getElementById('amaturo_bya_cms').value || ''), n);
    const len = Math.max(amaSegs.length, amaCfmsSegs.length, n);
    for (let i = 0; i < len; i++) {
        const a = sumPastorSegment(amaSegs[i] || '');
        const c = sumPastorSegment(amaCfmsSegs[i] || '');
        grand += (a + c) / 2;
    }
    document.querySelectorAll('.cr-extra-field-input').forEach(function (input) {
        const raw = input.value || '';
        expandSegments(raw, n).forEach(function (seg) { grand += sumPastorSegment(seg); });
    });
    document.getElementById('p_grand').innerText = grand;
}

let crExtraFieldDefs = [];

function fieldSlugFromLabel(label) {
    let slug = label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    if (!slug) slug = 'field_' + Date.now();
    return slug.substring(0, 80);
}

function syncExtraFieldDefsHidden() {
    document.getElementById('extra_field_defs_json').value = JSON.stringify(crExtraFieldDefs);
}

function renderExtraFieldInput(def, presetValue) {
    const container = document.getElementById('cr_extra_fields_container');
    if (document.getElementById('extra_field_' + def.slug)) return;

    const wrap = document.createElement('div');
    wrap.className = 'form-group cr-extra-field-wrap';
    wrap.dataset.slug = def.slug;
    wrap.innerHTML =
        '<label>' + def.label.replace(/</g, '&lt;') + ':</label>' +
        '<div class="input-row">' +
        '<input type="text" class="cr-extra-field-input" id="extra_field_' + def.slug + '" name="extra_field[' + def.slug + ']" placeholder="Urugero: 1000+2000" oninput="calcPastor()">' +
        '<span class="sum">= <span class="cr-extra-sum" data-for="extra_field_' + def.slug + '">0</span></span>' +
        '</div>';
    container.appendChild(wrap);
    const inputEl = document.getElementById('extra_field_' + def.slug);
    if (inputEl && presetValue) {
        inputEl.value = presetValue;
    }
    if (CR_PASTOR_HIGHLIGHT_IDS.indexOf('extra_field_' + def.slug) >= 0) {
        wrap.classList.add('field-error');
    }
}

function restorePastorFormExtras() {
    if (!CR_PASTOR_REPUBLISH) {
        return false;
    }
    const defs = CR_PASTOR_REPUBLISH.extra_defs || [];
    const values = CR_PASTOR_REPUBLISH.extra_field || {};
    crExtraFieldDefs = [];
    document.getElementById('cr_extra_fields_container').innerHTML = '';
    defs.forEach(function (def) {
        const slug = def.slug || def.field_slug;
        const label = def.label || def.field_label;
        if (!slug || !label) {
            return;
        }
        crExtraFieldDefs.push({ slug: slug, label: label });
        renderExtraFieldInput({ slug: slug, label: label }, values[slug] || '');
    });
    syncExtraFieldDefsHidden();
    return true;
}

function addExtraField(label, presetValue) {
    label = (label || '').trim();
    if (!label) return;
    let slug = fieldSlugFromLabel(label);
    let base = slug;
    let i = 2;
    while (crExtraFieldDefs.some(function (d) { return d.slug === slug; })) {
        slug = base + '_' + i;
        i++;
    }
    const def = { slug: slug, label: label };
    crExtraFieldDefs.push(def);
    syncExtraFieldDefsHidden();
    renderExtraFieldInput(def, presetValue || '');
    calcPastor();
}

function loadExtraFieldsForPeriod() {
    const intaraId = document.getElementById('cr_intara_id').value;
    const month = document.getElementById('cr_month').value;
    crExtraFieldDefs = [];
    document.getElementById('cr_extra_fields_container').innerHTML = '';
    syncExtraFieldDefsHidden();
    if (!intaraId || !month) return;

    fetch('get_mapato_pastor_fields.php?intara_id=' + encodeURIComponent(intaraId) + '&month=' + encodeURIComponent(month))
        .then(function (r) { return r.json(); })
        .then(function (data) {
            (data.fields || []).forEach(function (f) {
                crExtraFieldDefs.push({ slug: f.field_slug, label: f.field_label });
                renderExtraFieldInput({ slug: f.field_slug, label: f.field_label }, '');
            });
            syncExtraFieldDefsHidden();
            calcPastor();
        })
        .catch(function () {});
}

const crMonthSelect = document.getElementById('cr_month');
if (crIntaraSelect) {
    crIntaraSelect.addEventListener('change', function () {
        updateItoreroHint();
        loadExtraFieldsForPeriod();
    });
}
if (crMonthSelect) {
    crMonthSelect.addEventListener('change', loadExtraFieldsForPeriod);
}

const crAddBtn = document.getElementById('cr_add_extra_field_btn');
if (crAddBtn) {
    crAddBtn.addEventListener('click', function () {
        const label = document.getElementById('cr_new_field_label').value;
        const val = document.getElementById('cr_new_field_value').value;
        addExtraField(label, val);
        document.getElementById('cr_new_field_label').value = '';
        document.getElementById('cr_new_field_value').value = '';
    });
}
if (restorePastorFormExtras()) {
    if (typeof updateItoreroHint === 'function') {
        updateItoreroHint();
    }
    calcPastor();
} else {
    loadExtraFieldsForPeriod();
    calcPastor();
}
</script>
<?php require __DIR__ . '/includes/layout-end.php'; ?>
</body>
</html>
