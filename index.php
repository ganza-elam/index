<?php
require_once 'config.php';
require_once 'auth.php';

// Require admin access for data entry
requireAdmin();

// Get current user
$currentUser = getCurrentUser();

// Get all intara for dropdown
$intaraList = getAllIntara($pdo);
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_record'])) {
    $lesi = $_POST['lesi'] ?? '';
    $intara_id = $_POST['intara_id'] ?? '';
    $itorero_id = $_POST['itorero_id'] ?? '';
    
    // Calculate sums
    $icyacumi = $_POST['icyacumi'] ?? '';
    $icyacumi_cya_cms = $_POST['icyacumi_cya_cms'] ?? '';
    $amaturo = $_POST['amaturo'] ?? '';
    $amaturo_bya_cms = $_POST['amaturo_bya_cms'] ?? '';
    $umusaruro = $_POST['umusaruro'] ?? '';
    $ituro = $_POST['ituro'] ?? '';
    $filide = $_POST['filide'] ?? '';
    $ss = $_POST['ss'] ?? '';
    $ubusonga = $_POST['ubusonga'] ?? '';
    $mifem = $_POST['mifem'] ?? '';
    $ja = $_POST['ja'] ?? '';
    
    // Calculate totals
    $sumIcyacumi = sumValues($icyacumi);
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
    
    $total = $sumIcyacumi + $sumIcyacumiCyaCms + $sumAmaturo + $sumAmaturoByaCms + $sumUmusaruro + $sumIturo + 
             $sumFilide + $sumSs + $sumUbusonga + $sumMifem + $sumJa;
    
    if (empty($lesi)) {
        $message = '<div class="alert error">Shyiramo Numero ya lesi</div>';
    } elseif (empty($intara_id)) {
        $message = '<div class="alert error">Hitamo Intara</div>';
    } else {
        $data = [
            'lesi' => $lesi,
            'intara_id' => $intara_id,
            'itorero_id' => $itorero_id ?: null,
            'icyacumi' => format($icyacumi),
            'icyacumi_cya_cms' => format($icyacumi_cya_cms),
            'amaturo' => format($amaturo, true),
            'amaturo_bya_cms' => format($amaturo_bya_cms, true),
            'umusaruro' => format($umusaruro),
            'ituro' => format($ituro),
            'filide' => format($filide),
            'ss' => format($ss),
            'ubusonga' => format($ubusonga),
            'mifem' => format($mifem),
            'ja' => format($ja),
            'total' => $total
        ];
        
        if (saveImibare($pdo, $data)) {
            $message = '<div class="alert success">Ibyinjira byakiriwe neza!</div>';
        } else {
            $message = '<div class="alert error">Habaye ikibazo mu kubika.</div>';
        }
    }
}

// Helper functions
function sumValues($val) {
    if (!$val) return 0;
    $normalized = str_replace('+', ',', $val);
    return array_sum(array_map('floatval', array_filter(explode(',', $normalized), 'trim')));
}

function sumAmaturo($val) {
    return sumValues($val) / 2;
}

function format($input, $isAmaturo = false) {
    $s = sumValues($input);
    if ($isAmaturo) {
        return $input ? $input . ' = ' . $s . ' ÷ 2 = ' . ($s / 2) : '0';
    }
    return $input ? $input . ' = ' . $s : '0';
}
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
            <h2>Seventh Day Adventist Church </h2>
            <small>Stewardship and offerings management</small>
        </div>
    </div>
    <div class="nav">
        <a href="index.php">📝 INSEERT DATA </a>
        <a href="admin.php">⚙️ ADMIN PORTAL</a>
        <a href="reports.php">📊 REPORT</a>
        <a href="create-intara.php" style="color: #28a745;">➕ ADD Intara</a>
        <a href="logout.php" style="color: #dc3545;">🚪 LOG OUT</a>
    </div>
    
    <p style="text-align:right;color:#666;">May The Lord be with you: <b><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></b></p>

    <?= $message ?>

    <h2 class="page-title">INSERT DATA</h2>
    <p class="subtitle">Urugero: 1000+2000+500</p>

    <form method="POST" style="max-width: 1000px; margin-left: auto;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px 24px;">
            <div class="form-group">
                <label>Numero ya Lesi:</label>
                <input type="text" id="lesi" name="lesi" placeholder="Numero ya lesi" required>
            </div>

            <div class="form-group">
                <label>Intara:</label>
                <select id="intara" name="intara_id" onchange="loadItorero()" required>
                    <option value="">-- Hitamo Intara --</option>
                    <?php foreach ($intaraList as $intara): ?>
                        <option value="<?= $intara['id'] ?>"><?= htmlspecialchars($intara['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Itorero:</label>
                <select id="itorero" name="itorero_id">
                    <option value="">-- Hitamo Itorero  --</option>
                </select>
            </div>

            <!-- Input Fields -->
            <div class="form-group">
                <label>Icyacumi:</label>
                <div class="input-row">
                    <input type="text" id="icyacumi" name="icyacumi" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s1">0</span></span>
                </div>
            </div>

            <div class="form-group">
                <label>Icyacumi cya CFMS:</label>
                <div class="input-row">
                    <input type="text" id="icyacumi_cya_cms" name="icyacumi_cya_cms" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s1b">0</span></span>
                </div>
            </div>
            <div class="form-group">
                <label>Amaturo:</label>
                <div class="input-row">
                    <input type="text" id="amaturo" name="amaturo" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s2">0</span></span>
                </div>
            </div>

            <div class="form-group">
                <label>Amaturo bya CFMS:</label>
                <div class="input-row">
                    <input type="text" id="amaturo_bya_cms" name="amaturo_bya_cms" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s2b">0</span></span>
                </div>
            </div>

            

            

            <div class="form-group">
                <label>Umusaruro:</label>
                <div class="input-row">
                    <input type="text" id="umusaruro" name="umusaruro" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s3">0</span></span>
                </div>
            </div>

            <div class="form-group">
                <label>Ituro Rikuru:</label>
                <div class="input-row">
                    <input type="text" id="ituro" name="ituro" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s4">0</span></span>
                </div>
            </div>

            <div class="form-group">
                <label>Inyubako ya Filide:</label>
                <div class="input-row">
                    <input type="text" id="filide" name="filide" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s5">0</span></span>
                </div>
            </div>

            <div class="form-group">
                <label>SS Lesson:</label>
                <div class="input-row">
                    <input type="text" id="ss" name="ss" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s6">0</span></span>
                </div>
            </div>

            <div class="form-group">
                <label>Udutabo tw'Ubusonga:</label>
                <div class="input-row">
                    <input type="text" id="ubusonga" name="ubusonga" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s7">0</span></span>
                </div>
            </div>

            <div class="form-group">
                <label>Udutabo twa Mifem:</label>
                <div class="input-row">
                    <input type="text" id="mifem" name="mifem" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s8">0</span></span>
                </div>
            </div>

            <div class="form-group">
                <label>Udutabo twa JA:</label>
                <div class="input-row">
                    <input type="text" id="ja" name="ja" placeholder="Urugero: 1000+2000" oninput="calc()">
                    <span class="sum">= <span id="s9">0</span></span>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 12px;">
            <button type="submit" name="save_record">💾 SAVE</button>
            <button type="button" onclick="downloadExcel()">⬇️ Download Excel</button>
            <button type="reset" onclick="resetForm()">🔄 Tangura</button>
        </div>
    </form>

    <div class="totals-section">
        <h3>Final Totals</h3>
        <div id="totals"></div>
        <p><b>Grand Total: <span id="grand">0</span></b></p>
    </div>
</div>

<script>
// Store totals in memory
let totals = {
    icyacumi: 0, icyacumi_cya_cms: 0, amaturo: 0, amaturo_bya_cms: 0,
    umusaruro: 0, ituro: 0, filide: 0, ss: 0, ubusonga: 0, mifem: 0, ja: 0
};

function sumValues(val) {
    if (!val) return 0;
    return val
        .replace(/\+/g, ',')
        .split(',')
        .map(x => parseFloat(x.trim()) || 0)
        .reduce((a, b) => a + b, 0);
}

function sumAmaturo(val) {
    return sumValues(val) / 2;
}

function calc() {
    document.getElementById('s1').innerText = sumValues(document.getElementById('icyacumi').value);
    document.getElementById('s1b').innerText = sumValues(document.getElementById('icyacumi_cya_cms').value);
    document.getElementById('s2').innerText = sumAmaturo(document.getElementById('amaturo').value);
    document.getElementById('s2b').innerText = sumAmaturo(document.getElementById('amaturo_bya_cms').value);
    document.getElementById('s3').innerText = sumValues(document.getElementById('umusaruro').value);
    document.getElementById('s4').innerText = sumValues(document.getElementById('ituro').value);
    document.getElementById('s5').innerText = sumValues(document.getElementById('filide').value);
    document.getElementById('s6').innerText = sumValues(document.getElementById('ss').value);
    document.getElementById('s7').innerText = sumValues(document.getElementById('ubusonga').value);
    document.getElementById('s8').innerText = sumValues(document.getElementById('mifem').value);
    document.getElementById('s9').innerText = sumValues(document.getElementById('ja').value);
}

function loadItorero() {
    const intaraId = document.getElementById('intara').value;
    const itoreroSelect = document.getElementById('itorero');
    
    // Clear existing options
    itoreroSelect.innerHTML = '<option value="">-- Hitamo Itorero --</option>';
    
    if (intaraId) {
        // Fetch itorero via AJAX
        fetch('get_itorero.php?intara_id=' + intaraId)
            .then(response => response.json())
            .then(data => {
                data.forEach(itorero => {
                    const option = document.createElement('option');
                    option.value = itorero.id;
                    option.textContent = itorero.name;
                    itoreroSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error:', error));
    }
}

function resetForm() {
    document.querySelectorAll('input').forEach(i => i.value = '');
    document.getElementById('itorero').innerHTML = '<option value="">-- Hitamo Itorero --</option>';
    calc();
}

function downloadExcel() {
    alert('Download kugira muri raporo!');
}
</script>

</body>
</html>