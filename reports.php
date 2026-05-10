<?php
require_once 'config.php';
require_once 'auth.php';

// Require login before accessing this page
requireLogin();

// Get current user
$currentUser = getCurrentUser();
$isGuest = isGuestUser();

$message = '';
if ($isGuest) {
    $message = '<div class="alert success">Guest mode: view only. You can view final reports but cannot edit admin data.</div>';
}
if (isset($_GET['updated']) && !$isGuest) {
    $message = '<div class="alert success">Record updated successfully.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record']) && !$isGuest) {
    $recordId = $_POST['record_id'] ?? '';
    if ($recordId && deleteImibare($pdo, $recordId)) {
        header('Location: reports.php?deleted=1');
        exit;
    }
    $message = '<div class="alert error">Habaye ikibazo mu gusiba record.</div>';
}
if (isset($_GET['deleted']) && !$isGuest) {
    $message = '<div class="alert success">Record deleted successfully.</div>';
}

// Get filter values
$filter_intara = $_GET['intara_id'] ?? '';
$filter_itorero = $_GET['itorero_id'] ?? '';
$filter_month = $_GET['month'] ?? '';
$monthOptions = imibareMonthOptions();

// Get data based on filters (month: empty = show all ukwezi)
$imibareList = getImibare($pdo, $filter_intara, $filter_itorero, $filter_month !== '' ? $filter_month : null);
$intaraList = getAllIntara($pdo);
$itoreroList = getAllItorero($pdo);
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
$categoryTotals = [
    'icyacumi' => 0, 'icyacumi_cya_cms' => 0, 'amaturo' => 0, 'amaturo_bya_cms' => 0,
    'umusaruro' => 0, 'ituro' => 0, 'filide' => 0, 'ss' => 0, 'ubusonga' => 0, 'mifem' => 0, 'ja' => 0
];

foreach ($imibareList as $record) {
    $grandTotal += $record['total'];
    
    // Extract numeric values from formatted strings
    $categoryTotals['icyacumi'] += extractSum($record['icyacumi']);
    $categoryTotals['icyacumi_cya_cms'] += extractSum($record['icyacumi_cya_cms']);
    $categoryTotals['amaturo'] += extractSum($record['amaturo']);
    $categoryTotals['amaturo_bya_cms'] += extractSum($record['amaturo_bya_cms']);
    $categoryTotals['umusaruro'] += extractSum($record['umusaruro']);
    $categoryTotals['ituro'] += extractSum($record['ituro']);
    $categoryTotals['filide'] += extractSum($record['filide']);
    $categoryTotals['ss'] += extractSum($record['ss']);
    $categoryTotals['ubusonga'] += extractSum($record['ubusonga']);
    $categoryTotals['mifem'] += extractSum($record['mifem']);
    $categoryTotals['ja'] += extractSum($record['ja']);
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
?>
<!DOCTYPE html>
<html>
<head>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container">
    <div class="brand-header">
        <img class="brand-logo" src="assets/sda.png" alt="Adventist logo">
        <div class="brand-text">
            <h2>Seventh Day Adventist Church</h2>
            <small>Stewardship and offerings management</small>
        </div>
    </div>
    <div class="nav">
        <a href="index.php">📝 INSERT DATA</a>
        <a href="admin.php">⚙️ ADMIN PORTAL</a>
        <a href="reports.php">📊 REPORT</a>
        <a href="create-intara.php" style="color: #28a745;">➕ ADD Intara</a>
        <a href="logout.php" style="color: #dc3545;">🚪 LOG OUT</a>
    </div>
    
    <p style="text-align:right;color:#666;">May The Lord be with you: <b><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></b></p>
    <?= $message ?>

    <h1>📊 Raporo ya mapato A na Mapato B</h1>

    <!-- Filters -->
    <div class="filters">
        <form method="GET">
            <div>
                <label>Filter by Intara:</label>
                <select name="intara_id" id="filter_intara" onchange="loadItoreroFilter()">
                    <option value="">-- All Intara --</option>
                    <?php foreach ($intaraList as $intara): ?>
                        <option value="<?= $intara['id'] ?>" <?= $filter_intara == $intara['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($intara['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Filter by Itorero:</label>
                <select name="itorero_id" id="filter_itorero">
                    <option value="">-- All Itorero --</option>
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
                    <option value="">-- Byose / All months --</option>
                    <?php foreach ($monthOptions as $m => $label): ?>
                        <option value="<?= (int) $m ?>" <?= (string)$filter_month === (string)$m ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit">🔍 Search</button>
                <button type="button" class="clear" onclick="clearFilters()">🔄 Clear</button>
                <?php if (!$isGuest): ?>
                    <button type="button" onclick="downloadExcel()">⬇️ Download mapato B</button>
                    <button type="button" onclick="downloadMapatoA()">⬇️ Download mapato A</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
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
    <h3>Category Totals</h3>
    <div class="category-summary">
        <div class="cat-item">
            <div class="label">Icyacumi</div>
            <div class="value"><?= number_format($categoryTotals['icyacumi'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Icyacumi cya CMS</div>
            <div class="value"><?= number_format($categoryTotals['icyacumi_cya_cms'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Amaturo</div>
            <div class="value"><?= number_format($categoryTotals['amaturo'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Amaturo bya CMS</div>
            <div class="value"><?= number_format($categoryTotals['amaturo_bya_cms'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Umusaruro</div>
            <div class="value"><?= number_format($categoryTotals['umusaruro'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Ituro</div>
            <div class="value"><?= number_format($categoryTotals['ituro'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Filide</div>
            <div class="value"><?= number_format($categoryTotals['filide'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">SS</div>
            <div class="value"><?= number_format($categoryTotals['ss'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Ubusonga</div>
            <div class="value"><?= number_format($categoryTotals['ubusonga'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">Mifem</div>
            <div class="value"><?= number_format($categoryTotals['mifem'], 0) ?></div>
        </div>
        <div class="cat-item">
            <div class="label">JA</div>
            <div class="value"><?= number_format($categoryTotals['ja'], 0) ?></div>
        </div>
    </div>

    <!-- Data Table -->
    <h3>inserted datae</h3>
    <?php if (empty($imibareList)): ?>
        <div class="no-data">
            <p>📭 Nta data ihari</p>
            <p>Shyiramo ibinyejana ukoresheje <a href="index.php">form y'injira</a></p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Lesi</th>
                    <th>Intara</th>
                    <th>Itorero</th>
                    <th>Ukwezi</th>
                    <th>Ibindi</th>
                    <th>Icyacumi</th>
                    <th>Icyacumi CMS</th>
                    <th>Amaturo</th>
                    <th>Amaturo CMS</th>
                    <th>Umusaruro</th>
                    <th>Ituro</th>
                    <th>Filide</th>
                    <th>SS</th>
                    <th>Ubusonga</th>
                    <th>Mifem</th>
                    <th>JA</th>
                    <th>Total</th>
                    <th>Itariki</th>
                    <?php if (!$isGuest): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($imibareList as $record): ?>
                <tr>
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
                    <td><?= htmlspecialchars($record['amaturo'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['amaturo_bya_cms'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['umusaruro'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['ituro'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['filide'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['ss'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['ubusonga'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['mifem'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($record['ja'] ?? '0') ?></td>
                    <td><strong><?= number_format($record['total'], 0) ?></strong></td>
                    <td class="date"><?= date('d/m/Y H:i', strtotime($record['created_at'])) ?></td>
                    <?php if (!$isGuest): ?>
                        <td>
                            <a href="edit-imibare.php?id=<?= (int) $record['id'] ?>" style="margin-right:8px;">✏️ Update</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Urashaka gusiba iyi record?')">
                                <input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>">
                                <button type="submit" name="delete_record" class="delete">🗑️ Delete</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #e8f5e9; font-weight: bold;">
                    <td colspan="5">TOTAL</td>
                    <td><?= number_format($categoryTotals['icyacumi'], 0) ?></td>
                    <td><?= number_format($categoryTotals['icyacumi_cya_cms'], 0) ?></td>
                    <td><?= number_format($categoryTotals['amaturo'], 0) ?></td>
                    <td><?= number_format($categoryTotals['amaturo_bya_cms'], 0) ?></td>
                    <td><?= number_format($categoryTotals['umusaruro'], 0) ?></td>
                    <td><?= number_format($categoryTotals['ituro'], 0) ?></td>
                    <td><?= number_format($categoryTotals['filide'], 0) ?></td>
                    <td><?= number_format($categoryTotals['ss'], 0) ?></td>
                    <td><?= number_format($categoryTotals['ubusonga'], 0) ?></td>
                    <td><?= number_format($categoryTotals['mifem'], 0) ?></td>
                    <td><?= number_format($categoryTotals['ja'], 0) ?></td>
                    <td><?= number_format($grandTotal, 0) ?></td>
                    <td></td>
                    <?php if (!$isGuest): ?>
                        <td></td>
                    <?php endif; ?>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>

<script>
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
    window.location.href = 'reports.php';
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

// Initialize filter on page load
document.addEventListener('DOMContentLoaded', loadItoreroFilter);
</script>

</body>
</html>
