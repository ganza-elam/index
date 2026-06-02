<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/imibare-math.php';
require_once __DIR__ . '/includes/mapato-pastor-fields.php';
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
$itoreroList = getAllItorero($pdo);
$monthOptions = imibareMonthOptions();
$message = '';

function parseStoredInput($value) {
    if (!$value || $value === '0') {
        return '';
    }
    $parts = explode('=', $value);
    return trim($parts[0]);
}

$extraFieldDefs = getMapatoPastorFieldDefs($pdo, (int) $record['intara_id'], (int) $record['month']);
$extraStored = decodeMapatoPastorExtraFields($record);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
    $intara_id = $_POST['intara_id'] ?? '';
    $itorero_id = $_POST['itorero_id'] ?? '';
    $month_val = isset($_POST['month']) ? (int) $_POST['month'] : 0;

    $seg = [];
    foreach (mapatoPastorStaticFieldKeys() as $key) {
        $seg[$key] = trim($_POST[$key] ?? '');
    }

    $extraLabelBySlug = [];
    $extraDefsRaw = json_decode($_POST['extra_field_defs_json'] ?? '[]', true);
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

    $extraStoredOut = [];
    $extraSegValues = [];
    $extraPost = is_array($_POST['extra_field'] ?? null) ? $_POST['extra_field'] : [];
    foreach (array_keys($extraLabelBySlug) as $slug) {
        $raw = trim($extraPost[$slug] ?? '');
        $extraSegValues[] = $raw;
        $extraStoredOut[$slug] = formatStoredValue($raw);
    }

    $total = sumMapatoPastorRecordTotal($seg, $extraSegValues);

    if ($intara_id === '') {
        $message = '<div class="alert error">Hitamo Intara</div>';
    } elseif ($itorero_id === '') {
        $message = '<div class="alert error">Hitamo Itorero</div>';
    } elseif ($month_val < 1 || $month_val > 12) {
        $message = '<div class="alert error">Hitamo ukwezi</div>';
    } else {
        $data = [
            'intara_id' => $intara_id,
            'itorero_id' => $itorero_id,
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
            'extra_fields' => encodeMapatoPastorExtraFields($extraStoredOut),
            'total' => $total,
        ];
        if (updateMapatoPastor($pdo, $recordId, $data)) {
            header('Location: reports.php?report_type=correct_report&updated_pastor=1');
            exit;
        }
        $message = '<div class="alert error">Habaye ikibazo mu kuvugurura.</div>';
    }
}

$staticLabels = mapatoPastorStaticFieldLabels();
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/includes/material-icons-head.php'; ?>
    <link rel="stylesheet" href="styles.css">
    <style>
        .cr-extra-fields-zone { border: 2px dashed #90caf9; border-radius: 10px; padding: 16px; margin-top: 8px; background: #f8fbff; grid-column: 1 / -1; }
        .cr-add-field-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin-bottom: 12px; }
    </style>
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
                <select name="intara_id" id="edit_intara_id" required>
                    <?php foreach ($intaraList as $intara): ?>
                        <option value="<?= $intara['id'] ?>" <?= (int)$record['intara_id'] === (int)$intara['id'] ? 'selected' : '' ?>><?= htmlspecialchars($intara['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Itorero:</label>
                <select name="itorero_id" id="edit_itorero_id" required>
                    <option value="">-- Hitamo Itorero --</option>
                    <?php foreach ($itoreroList as $itorero): ?>
                        <option value="<?= $itorero['id'] ?>" data-intara="<?= $itorero['intara_id'] ?>" <?= (int)($record['itorero_id'] ?? 0) === (int)$itorero['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($itorero['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Ukwezi:</label>
                <select name="month" id="edit_month" required>
                    <?php foreach ($monthOptions as $m => $label): ?>
                        <option value="<?= (int) $m ?>" <?= (int)$record['month'] === (int)$m ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php
            foreach ($staticLabels as $key => $label):
                $stored = $key === 'meeting' ? mapatoPastorMeeting($record) : ($record[$key] ?? '');
            ?>
            <div class="form-group">
                <label><?= htmlspecialchars($label) ?>:</label>
                <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars(parseStoredInput($stored)) ?>" oninput="calcEdit()">
            </div>
            <?php endforeach; ?>

            <div class="cr-extra-fields-zone">
                <h4 style="margin:0 0 12px;color:#1565c0;">+ Inzego nshya (extra fields)</h4>
                <div class="cr-add-field-row">
                    <input type="text" id="edit_new_field_label" placeholder="Izina ry'inzego nshya" style="flex:1;">
                    <button type="button" id="edit_add_extra_field_btn">+ Ongeraho</button>
                </div>
                <div id="edit_extra_fields_container"></div>
            </div>
            <input type="hidden" name="extra_field_defs_json" id="extra_field_defs_json" value="">
        </div>
        <p><b>Grand Total: <span id="edit_grand">0</span></b></p>
        <button type="submit" name="update_record">💾 UPDATE</button>
        <a href="reports.php?report_type=correct_report">← Back to Report</a>
    </form>
</div>
<script>
let editExtraFieldDefs = <?= json_encode(array_map(fn($d) => ['slug' => $d['field_slug'], 'label' => $d['field_label']], $extraFieldDefs), JSON_UNESCAPED_UNICODE) ?>;
const editExtraStored = <?= json_encode(array_map('parseStoredInput', $extraStored), JSON_UNESCAPED_UNICODE) ?>;

function sumValues(val) {
    if (!val) return 0;
    return val.replace(/\+/g, ',').split(',').map(x => parseFloat(x.trim()) || 0).reduce((a, b) => a + b, 0);
}
function syncEditExtraDefs() {
    document.getElementById('extra_field_defs_json').value = JSON.stringify(editExtraFieldDefs);
}
function renderEditExtraField(def) {
    if (document.getElementById('edit_extra_' + def.slug)) return;
    const wrap = document.createElement('div');
    wrap.className = 'form-group';
    wrap.innerHTML = '<label>' + def.label.replace(/</g, '&lt;') + ':</label>' +
        '<input type="text" class="edit-extra-input" id="edit_extra_' + def.slug + '" name="extra_field[' + def.slug + ']" oninput="calcEdit()">';
    document.getElementById('edit_extra_fields_container').appendChild(wrap);
    const inp = document.getElementById('edit_extra_' + def.slug);
    if (editExtraStored[def.slug]) inp.value = editExtraStored[def.slug];
}
function addEditExtraField(label) {
    label = (label || '').trim();
    if (!label) return;
    let slug = label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '') || ('field_' + Date.now());
    if (editExtraFieldDefs.some(d => d.slug === slug)) slug += '_' + Date.now();
    const def = { slug: slug, label: label };
    editExtraFieldDefs.push(def);
    syncEditExtraDefs();
    renderEditExtraField(def);
    calcEdit();
}
function calcEdit() {
    const ids = <?= json_encode(mapatoPastorStaticFieldKeys()) ?>;
    let t = 0;
    ids.forEach(id => { t += sumValues(document.querySelector('[name="'+id+'"]').value); });
    document.querySelectorAll('.edit-extra-input').forEach(inp => { t += sumValues(inp.value); });
    document.getElementById('edit_grand').innerText = t;
}
editExtraFieldDefs.forEach(renderEditExtraField);
syncEditExtraDefs();
document.getElementById('edit_add_extra_field_btn').addEventListener('click', function () {
    addEditExtraField(document.getElementById('edit_new_field_label').value);
    document.getElementById('edit_new_field_label').value = '';
});
const editIntaraSelect = document.getElementById('edit_intara_id');
const editItoreroSelect = document.getElementById('edit_itorero_id');
if (editIntaraSelect && editItoreroSelect) {
    function filterEditItorero() {
        const intaraId = editIntaraSelect.value;
        editItoreroSelect.querySelectorAll('option').forEach(function (opt) {
            if (!opt.value) return;
            opt.hidden = intaraId !== '' && opt.dataset.intara !== intaraId;
        });
        if (editItoreroSelect.selectedOptions[0] && editItoreroSelect.selectedOptions[0].hidden) {
            editItoreroSelect.value = '';
        }
    }
    editIntaraSelect.addEventListener('change', filterEditItorero);
    filterEditItorero();
}
calcEdit();
</script>
<?php require __DIR__ . '/includes/layout-end.php'; ?>
</body>
</html>
