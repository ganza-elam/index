<?php
/**
 * Database Configuration
 * Plain PHP MySQL Connection
 */

$db_host = 'localhost';
$db_name = 'elam_system';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/** Month keys 1–12 for dropdowns / filters (labels in Kinyarwanda) */
function imibareMonthOptions(): array {
    return [
        1 => 'Mutarama',
        2 => 'Gashyantare',
        3 => 'Werurwe',
        4 => 'Mata',
        5 => 'Gicurasi',
        6 => 'Kamena',
        7 => 'Nyakanga',
        8 => 'Kanama',
        9 => 'Nzeli',
        10 => 'Ukwakira',
        11 => 'Ugushyingo',
        12 => 'Ukuboza',
    ];
}

/**
 * Get all Intara records
 */
function getAllIntara($pdo) {
    $stmt = $pdo->query("SELECT * FROM intara ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get Itorero by Intara ID
 */
function getItoreroByIntara($pdo, $intara_id) {
    $stmt = $pdo->prepare("SELECT * FROM itorero WHERE intara_id = ? ORDER BY name");
    $stmt->execute([$intara_id]);
    return $stmt->fetchAll();
}

/**
 * Get all Itorero records
 */
function getAllItorero($pdo) {
    $stmt = $pdo->query("SELECT * FROM itorero ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Add new Intara
 */
function addIntara($pdo, $name) {
    $stmt = $pdo->prepare("INSERT INTO intara (name) VALUES (?)");
    return $stmt->execute([$name]);
}

/**
 * Add new Itorero
 */
function addItorero($pdo, $intara_id, $name) {
    $stmt = $pdo->prepare("INSERT INTO itorero (intara_id, name) VALUES (?, ?)");
    return $stmt->execute([$intara_id, $name]);
}

/**
 * Update Intara
 */
function updateIntara($pdo, $id, $name) {
    $stmt = $pdo->prepare("UPDATE intara SET name = ? WHERE id = ?");
    return $stmt->execute([$name, $id]);
}

/**
 * Update Itorero
 */
function updateItorero($pdo, $id, $intara_id, $name) {
    $stmt = $pdo->prepare("UPDATE itorero SET intara_id = ?, name = ? WHERE id = ?");
    return $stmt->execute([$intara_id, $name, $id]);
}

/**
 * Delete Intara
 */
function deleteIntara($pdo, $id) {
    if (hasImibareForIntara($pdo, $id)) {
        return false;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM intara WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete Itorero
 */
function deleteItorero($pdo, $id) {
    if (hasImibareForItorero($pdo, $id)) {
        return false;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM itorero WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

function hasImibareForIntara($pdo, $intara_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM imibare WHERE intara_id = ?");
    $stmt->execute([$intara_id]);
    return (int) $stmt->fetchColumn() > 0;
}

function hasImibareForItorero($pdo, $itorero_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM imibare WHERE itorero_id = ?");
    $stmt->execute([$itorero_id]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensureImibareInsertedByColumn($pdo) {
    static $done = false;
    if ($done) {
        return;
    }
    $cols = $pdo->query("SHOW COLUMNS FROM imibare")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('inserted_by', $cols, true)) {
        $pdo->exec("ALTER TABLE imibare ADD COLUMN inserted_by int(11) DEFAULT NULL AFTER total");
        $pdo->exec("ALTER TABLE imibare ADD KEY idx_imibare_inserted_by (inserted_by)");
    }
    $done = true;
}

/**
 * Save Imibare record
 */
function saveImibare($pdo, $data) {
    ensureImibareInsertedByColumn($pdo);
    $insertedBy = isset($data['inserted_by']) && $data['inserted_by'] !== '' ? (int) $data['inserted_by'] : null;

    $stmt = $pdo->prepare("INSERT INTO imibare (
        lesi, intara_id, itorero_id, month, ibindi,
        icyacumi, icyacumi_cya_cms, amaturo, amaturo_bya_cms,
        umusaruro, ituro, filide, ss, ubusonga, mifem, ja, total, inserted_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    return $stmt->execute([
        $data['lesi'],
        $data['intara_id'],
        $data['itorero_id'],
        $data['month'],
        $data['ibindi'],
        $data['icyacumi'],
        $data['icyacumi_cya_cms'],
        $data['amaturo'],
        $data['amaturo_bya_cms'],
        $data['umusaruro'],
        $data['ituro'],
        $data['filide'],
        $data['ss'],
        $data['ubusonga'],
        $data['mifem'],
        $data['ja'],
        $data['total'],
        $insertedBy,
    ]);
}

/**
 * Get single Imibare record by ID
 */
function getImibareById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM imibare WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Update Imibare record
 */
function updateImibare($pdo, $id, $data) {
    $stmt = $pdo->prepare("UPDATE imibare SET
        lesi = ?, intara_id = ?, itorero_id = ?, month = ?, ibindi = ?, icyacumi = ?, icyacumi_cya_cms = ?, amaturo = ?, amaturo_bya_cms = ?,
        umusaruro = ?, ituro = ?, filide = ?, ss = ?, ubusonga = ?, mifem = ?, ja = ?, total = ?
        WHERE id = ?");

    return $stmt->execute([
        $data['lesi'],
        $data['intara_id'],
        $data['itorero_id'],
        $data['month'],
        $data['ibindi'],
        $data['icyacumi'],
        $data['icyacumi_cya_cms'],
        $data['amaturo'],
        $data['amaturo_bya_cms'],
        $data['umusaruro'],
        $data['ituro'],
        $data['filide'],
        $data['ss'],
        $data['ubusonga'],
        $data['mifem'],
        $data['ja'],
        $data['total'],
        $id
    ]);
}

/**
 * Delete Imibare record
 */
function deleteImibare($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM imibare WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get all Imibare records with optional filters
 */
function getImibare($pdo, $intara_id = null, $itorero_id = null, $month = null) {
    ensureImibareInsertedByColumn($pdo);
    $sql = "SELECT i.*, intara.name as intara_name, itorero.name as itorero_name,
            u.username AS inserted_by_username
            FROM imibare i 
            LEFT JOIN intara ON i.intara_id = intara.id 
            LEFT JOIN itorero ON i.itorero_id = itorero.id 
            LEFT JOIN users u ON i.inserted_by = u.id
            WHERE 1=1";
    
    $params = [];
    
    if ($intara_id) {
        $sql .= " AND i.intara_id = ?";
        $params[] = $intara_id;
    }
    
    if ($itorero_id) {
        $sql .= " AND i.itorero_id = ?";
        $params[] = $itorero_id;
    }

    if ($month !== null && $month !== '' && $month !== false) {
        $monthInt = (int) $month;
        if ($monthInt >= 1 && $monthInt <= 12) {
            $sql .= " AND i.month = ?";
            $params[] = $monthInt;
        }
    }
    
    $sql .= " ORDER BY i.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Count insert-data records per admin (for bar chart on reports).
 */
function getImibareInsertsByAdmin($pdo, $intara_id = null, $itorero_id = null, $month = null) {
    ensureImibareInsertedByColumn($pdo);
    $sql = "SELECT u.id, u.username, COUNT(i.id) AS record_count
            FROM imibare i
            INNER JOIN users u ON i.inserted_by = u.id
            WHERE i.inserted_by IS NOT NULL";
    $params = [];

    if ($intara_id) {
        $sql .= " AND i.intara_id = ?";
        $params[] = $intara_id;
    }
    if ($itorero_id) {
        $sql .= " AND i.itorero_id = ?";
        $params[] = $itorero_id;
    }
    if ($month !== null && $month !== '' && $month !== false) {
        $monthInt = (int) $month;
        if ($monthInt >= 1 && $monthInt <= 12) {
            $sql .= " AND i.month = ?";
            $params[] = $monthInt;
        }
    }

    $sql .= " GROUP BY u.id, u.username ORDER BY record_count DESC, u.username ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get totals by intara
 */
function getTotalsByIntara($pdo) {
    $stmt = $pdo->query("
        SELECT intara.id, intara.name, 
               SUM(imibare.total) as total_sum,
               COUNT(imibare.id) as record_count
        FROM intara
        LEFT JOIN imibare ON intara.id = imibare.intara_id
        GROUP BY intara.id, intara.name
        ORDER BY intara.name
    ");
    return $stmt->fetchAll();
}

/**
 * Get totals by itorero
 */
function getTotalsByItorero($pdo) {
    $stmt = $pdo->query("
        SELECT itorero.id, itorero.name, intara.name as intara_name,
               SUM(imibare.total) as total_sum,
               COUNT(imibare.id) as record_count
        FROM itorero
        LEFT JOIN imibare ON itorero.id = imibare.itorero_id
        LEFT JOIN intara ON itorero.intara_id = intara.id
        GROUP BY itorero.id, itorero.name, intara.name
        ORDER BY intara.name, itorero.name
    ");
    return $stmt->fetchAll();
}

/**
 * Create Correct Report tables if missing (mapato_pastor, bank_slips).
 */
function ensureCorrectReportTables($pdo) {
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS mapato_pastor (
        id int(11) NOT NULL AUTO_INCREMENT,
        intara_id int(11) NOT NULL,
        itorero_id int(11) DEFAULT NULL,
        month tinyint unsigned NOT NULL,
        icyacumi varchar(500) DEFAULT NULL,
        icyacumi_cya_cms varchar(500) DEFAULT NULL,
        amaturo varchar(500) DEFAULT NULL,
        amaturo_bya_cms varchar(500) DEFAULT NULL,
        revival varchar(500) DEFAULT NULL,
        ss varchar(500) DEFAULT NULL,
        filide varchar(500) DEFAULT NULL,
        umusaruro varchar(500) DEFAULT NULL,
        ituro varchar(500) DEFAULT NULL,
        total decimal(15,2) DEFAULT 0.00,
        created_at datetime DEFAULT current_timestamp(),
        PRIMARY KEY (id),
        KEY idx_mapato_pastor_intara (intara_id),
        KEY idx_mapato_pastor_itorero (itorero_id),
        KEY idx_mapato_pastor_month (month)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bank_slips (
        id int(11) NOT NULL AUTO_INCREMENT,
        intara_id int(11) NOT NULL,
        month tinyint unsigned DEFAULT NULL,
        slip_number varchar(100) NOT NULL,
        bank_name varchar(255) NOT NULL,
        amount decimal(15,2) NOT NULL DEFAULT 0.00,
        created_at datetime DEFAULT current_timestamp(),
        PRIMARY KEY (id),
        UNIQUE KEY uq_bank_slip_number (slip_number),
        KEY idx_bank_slips_intara (intara_id),
        KEY idx_bank_slips_month (month)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    migrateCorrectReportColumns($pdo);

    require_once __DIR__ . '/includes/mapato-pastor-fields.php';
    ensureMapatoPastorExtraFieldsSchema($pdo);

    $ensured = true;
}

/**
 * Add meeting column, bank_slips.month; migrate legacy CFMS column data.
 */
function migrateCorrectReportColumns($pdo) {
    static $migrated = false;
    if ($migrated) {
        return;
    }
    $cols = $pdo->query("SHOW COLUMNS FROM mapato_pastor")->fetchAll(PDO::FETCH_COLUMN);
    if ($cols && !in_array('itorero_id', $cols, true)) {
        $pdo->exec("ALTER TABLE mapato_pastor ADD COLUMN itorero_id int(11) DEFAULT NULL AFTER intara_id");
        $pdo->exec("ALTER TABLE mapato_pastor ADD KEY idx_mapato_pastor_itorero (itorero_id)");
    }
    if ($cols && !in_array('meeting', $cols, true)) {
        $pdo->exec("ALTER TABLE mapato_pastor ADD COLUMN meeting varchar(500) DEFAULT NULL AFTER icyacumi");
        if (in_array('icyacumi_cya_cms', $cols, true)) {
            $pdo->exec("UPDATE mapato_pastor SET meeting = icyacumi_cya_cms WHERE meeting IS NULL AND icyacumi_cya_cms IS NOT NULL AND icyacumi_cya_cms != ''");
        }
    }
    $bankCols = $pdo->query("SHOW COLUMNS FROM bank_slips")->fetchAll(PDO::FETCH_COLUMN);
    if ($bankCols && !in_array('month', $bankCols, true)) {
        $pdo->exec("ALTER TABLE bank_slips ADD COLUMN month tinyint unsigned DEFAULT NULL AFTER intara_id");
    }
    $migrated = true;
}

/** Meeting field (CM) with legacy column fallback. */
function mapatoPastorMeeting($record) {
    if (!empty($record['meeting'])) {
        return $record['meeting'];
    }
    return $record['icyacumi_cya_cms'] ?? '';
}

function saveMapatoPastor($pdo, $data) {
    ensureCorrectReportTables($pdo);
    $stmt = $pdo->prepare("INSERT INTO mapato_pastor (
        intara_id, itorero_id, month, icyacumi, icyacumi_cya_cms, meeting, amaturo, amaturo_bya_cms,
        revival, ss, filide, umusaruro, ituro, mifem, extra_fields, total
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['intara_id'],
        $data['itorero_id'] ?? null,
        $data['month'],
        $data['icyacumi'],
        $data['icyacumi_cya_cms'] ?? null,
        $data['meeting'],
        $data['amaturo'],
        $data['amaturo_bya_cms'] ?? null,
        $data['revival'],
        $data['ss'],
        $data['filide'],
        $data['umusaruro'],
        $data['ituro'],
        $data['mifem'] ?? null,
        $data['extra_fields'] ?? null,
        $data['total'],
    ]);
}

function updateMapatoPastor($pdo, $id, $data) {
    ensureCorrectReportTables($pdo);
    $stmt = $pdo->prepare("UPDATE mapato_pastor SET
        intara_id = ?, itorero_id = ?, month = ?, icyacumi = ?, icyacumi_cya_cms = ?, meeting = ?, amaturo = ?, amaturo_bya_cms = ?,
        revival = ?, ss = ?, filide = ?, umusaruro = ?, ituro = ?, mifem = ?, extra_fields = ?, total = ?
        WHERE id = ?");
    return $stmt->execute([
        $data['intara_id'],
        $data['itorero_id'] ?? null,
        $data['month'],
        $data['icyacumi'],
        $data['icyacumi_cya_cms'] ?? null,
        $data['meeting'],
        $data['amaturo'],
        $data['amaturo_bya_cms'] ?? null,
        $data['revival'],
        $data['ss'],
        $data['filide'],
        $data['umusaruro'],
        $data['ituro'],
        $data['mifem'] ?? null,
        $data['extra_fields'] ?? null,
        $data['total'],
        $id,
    ]);
}

function getMapatoPastorById($pdo, $id) {
    ensureCorrectReportTables($pdo);
    $stmt = $pdo->prepare("SELECT mp.*, intara.name AS intara_name, it.name AS itorero_name
        FROM mapato_pastor mp
        LEFT JOIN intara ON mp.intara_id = intara.id
        LEFT JOIN itorero it ON mp.itorero_id = it.id
        WHERE mp.id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getMapatoPastor($pdo, $intara_id = null, $month = null, $itorero_id = null) {
    ensureCorrectReportTables($pdo);
    $sql = "SELECT mp.*, intara.name AS intara_name, it.name AS itorero_name
            FROM mapato_pastor mp
            LEFT JOIN intara ON mp.intara_id = intara.id
            LEFT JOIN itorero it ON mp.itorero_id = it.id
            WHERE 1=1";
    $params = [];
    if ($intara_id) {
        $sql .= " AND mp.intara_id = ?";
        $params[] = $intara_id;
    }
    if ($month !== null && $month !== '' && $month !== false) {
        $monthInt = (int) $month;
        if ($monthInt >= 1 && $monthInt <= 12) {
            $sql .= " AND mp.month = ?";
            $params[] = $monthInt;
        }
    }
    if ($itorero_id) {
        $sql .= " AND mp.itorero_id = ?";
        $params[] = (int) $itorero_id;
    }
    $sql .= " ORDER BY mp.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function deleteMapatoPastor($pdo, $id) {
    ensureCorrectReportTables($pdo);
    $stmt = $pdo->prepare("DELETE FROM mapato_pastor WHERE id = ?");
    return $stmt->execute([$id]);
}

function bankSlipNumberExists($pdo, $slip_number, $excludeId = null) {
    ensureCorrectReportTables($pdo);
    $sql = "SELECT COUNT(*) FROM bank_slips WHERE slip_number = ?";
    $params = [trim($slip_number)];
    if ($excludeId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

function saveBankSlip($pdo, $data) {
    ensureCorrectReportTables($pdo);
    $stmt = $pdo->prepare("INSERT INTO bank_slips (intara_id, month, slip_number, bank_name, amount)
        VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['intara_id'],
        $data['month'],
        $data['slip_number'],
        $data['bank_name'],
        $data['amount'],
    ]);
}

function updateBankSlip($pdo, $id, $data) {
    ensureCorrectReportTables($pdo);
    $stmt = $pdo->prepare("UPDATE bank_slips SET
        intara_id = ?, month = ?, slip_number = ?, bank_name = ?, amount = ?
        WHERE id = ?");
    return $stmt->execute([
        $data['intara_id'],
        $data['month'],
        $data['slip_number'],
        $data['bank_name'],
        $data['amount'],
        $id,
    ]);
}

function getBankSlipById($pdo, $id) {
    ensureCorrectReportTables($pdo);
    $stmt = $pdo->prepare("SELECT bs.*, intara.name AS intara_name
        FROM bank_slips bs
        LEFT JOIN intara ON bs.intara_id = intara.id
        WHERE bs.id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getBankSlips($pdo, $intara_id = null, $month = null) {
    ensureCorrectReportTables($pdo);
    $sql = "SELECT bs.*, intara.name AS intara_name
            FROM bank_slips bs
            LEFT JOIN intara ON bs.intara_id = intara.id
            WHERE 1=1";
    $params = [];
    if ($intara_id) {
        $sql .= " AND bs.intara_id = ?";
        $params[] = $intara_id;
    }
    if ($month !== null && $month !== '' && $month !== false) {
        $monthInt = (int) $month;
        if ($monthInt >= 1 && $monthInt <= 12) {
            $sql .= " AND bs.month = ?";
            $params[] = $monthInt;
        }
    }
    $sql .= " ORDER BY bs.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function deleteBankSlip($pdo, $id) {
    ensureCorrectReportTables($pdo);
    $stmt = $pdo->prepare("DELETE FROM bank_slips WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Compare pastor mapato grand totals vs bank slip amounts per Intara for one month.
 * Only includes Intara rows that have pastor and/or bank data for that month.
 */
function getCorrectReportComparison($pdo, $month, $intara_id = null) {
    ensureCorrectReportTables($pdo);
    $monthInt = (int) $month;
    if ($monthInt < 1 || $monthInt > 12) {
        return [];
    }

    $sql = "SELECT
            i.id AS intara_id,
            i.name AS intara_name,
            COALESCE(p.pastor_total, 0) AS pastor_total,
            COALESCE(b.bank_total, 0) AS bank_total
        FROM intara i
        LEFT JOIN (
            SELECT intara_id, SUM(total) AS pastor_total
            FROM mapato_pastor
            WHERE month = ?
            GROUP BY intara_id
        ) p ON p.intara_id = i.id
        LEFT JOIN (
            SELECT intara_id, SUM(amount) AS bank_total
            FROM bank_slips
            WHERE month = ?
            GROUP BY intara_id
        ) b ON b.intara_id = i.id
        WHERE 1=1";

    $params = [$monthInt, $monthInt];
    if ($intara_id) {
        $sql .= " AND i.id = ?";
        $params[] = (int) $intara_id;
    } else {
        $sql .= " AND (COALESCE(p.pastor_total, 0) > 0 OR COALESCE(b.bank_total, 0) > 0)";
    }

    $sql .= " ORDER BY i.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $raw = $stmt->fetchAll();

    $rows = [];
    foreach ($raw as $row) {
        $pastorTotal = (float) $row['pastor_total'];
        $bankTotal = (float) $row['bank_total'];
        $diff = $bankTotal - $pastorTotal;
        [$status, $statusLabel] = correctReportStatusFromDiff($diff);
        $rows[] = [
            'intara_id' => (int) $row['intara_id'],
            'intara_name' => $row['intara_name'],
            'pastor_total' => $pastorTotal,
            'bank_total' => $bankTotal,
            'difference' => $diff,
            'status' => $status,
            'status_label' => $statusLabel,
        ];
    }
    return $rows;
}

/**
 * Group INSERT DATA rows by Itorero (same logic as Mapato A Excel export).
 *
 * @return array{rows: list<array>, overall: array, grand_total: float}
 */
function aggregateImibareByItorero(array $imibareList, $intaraName = '') {
    require_once __DIR__ . '/includes/imibare-math.php';

    $totalsByItorero = [];
    $overall = [
        'icyacumi' => 0.0,
        'ibindi' => 0.0,
        'icyacumi_cya_cms' => 0.0,
        'amaturo' => 0.0,
        'amaturo_bya_cms' => 0.0,
        'umusaruro' => 0.0,
        'ituro' => 0.0,
        'filide' => 0.0,
        'ss' => 0.0,
        'ubusonga' => 0.0,
        'mifem' => 0.0,
        'ja' => 0.0,
        'record_count' => 0,
    ];
    $grandTotal = 0.0;

    foreach ($imibareList as $row) {
        $itoreroKey = $row['itorero_id'] ?? ('name_' . ($row['itorero_name'] ?? 'unknown'));
        if (!isset($totalsByItorero[$itoreroKey])) {
            $totalsByItorero[$itoreroKey] = [
                'intara_name' => $intaraName !== '' ? $intaraName : ($row['intara_name'] ?? '—'),
                'itorero_name' => $row['itorero_name'] ?? '—',
                'record_count' => 0,
                'icyacumi' => 0.0,
                'ibindi' => 0.0,
                'icyacumi_cya_cms' => 0.0,
                'amaturo' => 0.0,
                'amaturo_bya_cms' => 0.0,
                'umusaruro' => 0.0,
                'ituro' => 0.0,
                'filide' => 0.0,
                'ss' => 0.0,
                'ubusonga' => 0.0,
                'mifem' => 0.0,
                'ja' => 0.0,
                'grand_total' => 0.0,
            ];
        }

        $totalsByItorero[$itoreroKey]['record_count']++;
        $totalsByItorero[$itoreroKey]['icyacumi'] += extractSumFromStored($row['icyacumi'] ?? '');
        $totalsByItorero[$itoreroKey]['ibindi'] += extractSumFromStored($row['ibindi'] ?? '');
        $totalsByItorero[$itoreroKey]['icyacumi_cya_cms'] += extractSumFromStored($row['icyacumi_cya_cms'] ?? '');
        $totalsByItorero[$itoreroKey]['amaturo'] += extractAmaturoComparableSum($row['amaturo'] ?? '');
        $totalsByItorero[$itoreroKey]['amaturo_bya_cms'] += extractAmaturoComparableSum($row['amaturo_bya_cms'] ?? '');
        $totalsByItorero[$itoreroKey]['umusaruro'] += extractSumFromStored($row['umusaruro'] ?? '');
        $totalsByItorero[$itoreroKey]['ituro'] += extractSumFromStored($row['ituro'] ?? '');
        $totalsByItorero[$itoreroKey]['filide'] += extractSumFromStored($row['filide'] ?? '');
        $totalsByItorero[$itoreroKey]['ss'] += extractSumFromStored($row['ss'] ?? '');
        $totalsByItorero[$itoreroKey]['ubusonga'] += extractSumFromStored($row['ubusonga'] ?? '');
        $totalsByItorero[$itoreroKey]['mifem'] += extractSumFromStored($row['mifem'] ?? '');
        $totalsByItorero[$itoreroKey]['ja'] += extractSumFromStored($row['ja'] ?? '');
        $totalsByItorero[$itoreroKey]['grand_total'] += (float) ($row['total'] ?? 0);
    }

    $rows = array_values($totalsByItorero);
    usort($rows, fn($a, $b) => strcmp($a['itorero_name'], $b['itorero_name']));

    foreach ($rows as $r) {
        $overall['record_count'] += $r['record_count'];
        $overall['icyacumi'] += $r['icyacumi'];
        $overall['ibindi'] += $r['ibindi'];
        $overall['icyacumi_cya_cms'] += $r['icyacumi_cya_cms'];
        $overall['amaturo'] += $r['amaturo'];
        $overall['amaturo_bya_cms'] += $r['amaturo_bya_cms'];
        $overall['umusaruro'] += $r['umusaruro'];
        $overall['ituro'] += $r['ituro'];
        $overall['filide'] += $r['filide'];
        $overall['ss'] += $r['ss'];
        $overall['ubusonga'] += $r['ubusonga'];
        $overall['mifem'] += $r['mifem'];
        $overall['ja'] += $r['ja'];
        $grandTotal += $r['grand_total'];
    }

    return [
        'rows' => $rows,
        'overall' => $overall,
        'grand_total' => $grandTotal,
    ];
}

/**
 * Mapato A style totals per Intara (from INSERT DATA / imibare), with Itorero breakdown.
 */
function getMapatoASummaryByIntara($pdo, $month, $intara_id = null) {
    require_once __DIR__ . '/includes/imibare-math.php';
    $monthInt = (int) $month;
    if ($monthInt < 1 || $monthInt > 12) {
        return [];
    }

    $list = getImibare($pdo, $intara_id ?: null, null, $monthInt);
    $byIntara = [];

    foreach ($list as $row) {
        $iid = (int) ($row['intara_id'] ?? 0);
        if ($iid < 1) {
            continue;
        }
        if (!isset($byIntara[$iid])) {
            $byIntara[$iid] = [
                'intara_id' => $iid,
                'intara_name' => $row['intara_name'] ?? '—',
                'record_count' => 0,
                'grand_total' => 0.0,
                'icyacumi' => 0.0,
                'ibindi' => 0.0,
                'amaturo' => 0.0,
                'itorero_breakdown' => [],
            ];
        }
        $byIntara[$iid]['record_count']++;
        $byIntara[$iid]['grand_total'] += (float) ($row['total'] ?? 0);
        $byIntara[$iid]['icyacumi'] += extractSumFromStored($row['icyacumi'] ?? '');
        $byIntara[$iid]['ibindi'] += extractSumFromStored($row['ibindi'] ?? '');
        $byIntara[$iid]['amaturo'] += extractAmaturoComparableSum($row['amaturo'] ?? '') + extractAmaturoComparableSum($row['amaturo_bya_cms'] ?? '');

        $tid = (int) ($row['itorero_id'] ?? 0);
        $tkey = $tid > 0 ? 'id_' . $tid : 'name_' . ($row['itorero_name'] ?? 'unknown');
        if (!isset($byIntara[$iid]['itorero_breakdown'][$tkey])) {
            $byIntara[$iid]['itorero_breakdown'][$tkey] = [
                'itorero_name' => $row['itorero_name'] ?? '—',
                'record_count' => 0,
                'grand_total' => 0.0,
            ];
        }
        $byIntara[$iid]['itorero_breakdown'][$tkey]['record_count']++;
        $byIntara[$iid]['itorero_breakdown'][$tkey]['grand_total'] += (float) ($row['total'] ?? 0);
    }

    $out = [];
    foreach ($byIntara as $block) {
        $block['itorero_breakdown'] = array_values($block['itorero_breakdown']);
        usort($block['itorero_breakdown'], fn($a, $b) => strcmp($a['itorero_name'], $b['itorero_name']));
        $out[] = $block;
    }
    usort($out, fn($a, $b) => strcmp($a['intara_name'], $b['intara_name']));
    return $out;
}

/**
 * Plain-language summary of comparison results for documentation block / PDF.
 */
function buildComparisonSummaryNarrative($comparisonRows, $comparisonInsertRows, $grandTotalsRows, $mapatoARows, $monthLabel, $intaraFilterName) {
    $lines = [];
    $scope = $intaraFilterName !== '' ? 'Intara <strong>' . htmlspecialchars($intaraFilterName) . '</strong>' : 'Intara zose';
    $lines[] = '<p>Iyi raporo igenera ' . $scope . ' ku kwezi <strong>' . htmlspecialchars($monthLabel) . '</strong>. '
        . 'Gereranya amakuru atatu: <em>Correct Report (Mapato ya Pastoro)</em>, <em>INSERT DATA (Mapato A)</em>, '
        . 'na <em>Bank Slips</em> (amafaranga yinjiye muri banki).</p>';

    if (empty($comparisonRows) && empty($grandTotalsRows) && empty($mapatoARows)) {
        $lines[] = '<p><strong>Nta data:</strong> Hitamo ukwezi kandi wongere amakuru muri Correct Report, INSERT DATA, cyangwa Bank Slips.</p>';
        return implode("\n", $lines);
    }

    $lines[] = '<h4>1. Mapato A (INSERT DATA) — extract per Intara</h4>';
    $lines[] = '<p>Ibi ni grand total zivuye mu <strong>Insert Data</strong> (Mapato A export). Zigereranywa n\'amafaranga ya banki n\'mapato ya pastoro.</p>';
    if (!empty($mapatoARows)) {
        $mapatoTotal = array_sum(array_column($mapatoARows, 'grand_total'));
        $lines[] = '<p>Grand total ya Mapato A kuri iyi filter: <strong>' . number_format($mapatoTotal, 0) . '</strong> FRW.</p>';
    } else {
        $lines[] = '<p>Nta Mapato A (INSERT DATA) yinjijwe kuri uku kwezi.</p>';
    }

    $lines[] = '<h4>2. Mapato ya Pastoro vs Bank Slip</h4>';
    if (!empty($comparisonRows)) {
        $profit = $loss = $equal = 0;
        foreach ($comparisonRows as $r) {
            if ($r['status'] === 'profit') {
                $profit++;
            } elseif ($r['status'] === 'loss') {
                $loss++;
            } else {
                $equal++;
            }
        }
        $lines[] = '<p><strong>Surplus</strong> (bank &gt; pastoro): ' . $profit . ' Intara. '
            . '<strong>Deficit</strong> (pastoro &gt; bank): ' . $loss . ' Intara. <strong>Equal</strong>: ' . $equal . ' Intara.</p>';
    } else {
        $lines[] = '<p>Nta gereranya Pastoro vs Bank.</p>';
    }

    $lines[] = '<h4>3. Bank Slip vs INSERT DATA</h4>';
    $lines[] = !empty($comparisonInsertRows)
        ? '<p>Iyi table igaragaza niba amafaranga ya bank slip ahura n\'INSERT DATA ku bwanjye bwa Intara.</p>'
        : '<p>Nta gereranya Bank vs INSERT DATA.</p>';

    $lines[] = '<h4>4. Grand Totals (Pastoro + INSERT DATA + Bank)</h4>';
    $lines[] = !empty($grandTotalsRows)
        ? '<p>Table igaragaza grand total zose n\'impari n\'status (Surplus / Deficit / Equal).</p>'
        : '<p>Nta grand totals.</p>';

    $lines[] = '<p style="font-size:12px;color:#666;margin-top:12px;">Generated ' . htmlspecialchars(date('d/m/Y H:i')) . ' (Africa/Kigali).</p>';

    return implode("\n", $lines);
}

/** Status label for a pastor vs bank difference. */
function correctReportStatusFromDiff($diff) {
    if (abs($diff) < 0.01) {
        return ['equal', 'Equal'];
    }
    if ($diff > 0) {
        return ['profit', 'Surplus'];
    }
    return ['loss', 'Deficit'];
}

/**
 * Grand totals: Mapato ya Pastoro, INSERT DATA (imibare), and Bank Slip per Intara for one month.
 */
function getGrandTotalsComparison($pdo, $month, $intara_id = null) {
    ensureCorrectReportTables($pdo);
    $monthInt = (int) $month;
    if ($monthInt < 1 || $monthInt > 12) {
        return [];
    }

    $sql = "SELECT
            i.id AS intara_id,
            i.name AS intara_name,
            COALESCE(p.pastor_total, 0) AS pastor_total,
            COALESCE(m.insert_total, 0) AS insert_total,
            COALESCE(b.bank_total, 0) AS bank_total
        FROM intara i
        LEFT JOIN (
            SELECT intara_id, SUM(total) AS pastor_total
            FROM mapato_pastor
            WHERE month = ?
            GROUP BY intara_id
        ) p ON p.intara_id = i.id
        LEFT JOIN (
            SELECT intara_id, SUM(total) AS insert_total
            FROM imibare
            WHERE month = ?
            GROUP BY intara_id
        ) m ON m.intara_id = i.id
        LEFT JOIN (
            SELECT intara_id, SUM(amount) AS bank_total
            FROM bank_slips
            WHERE month = ?
            GROUP BY intara_id
        ) b ON b.intara_id = i.id
        WHERE 1=1";

    $params = [$monthInt, $monthInt, $monthInt];
    if ($intara_id) {
        $sql .= " AND i.id = ?";
        $params[] = (int) $intara_id;
    } else {
        $sql .= " AND (COALESCE(p.pastor_total, 0) > 0 OR COALESCE(m.insert_total, 0) > 0 OR COALESCE(b.bank_total, 0) > 0)";
    }

    $sql .= " ORDER BY i.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $pastorTotal = (float) $row['pastor_total'];
        $insertTotal = (float) $row['insert_total'];
        $bankTotal = (float) $row['bank_total'];
        $diffBankPastor = $bankTotal - $pastorTotal;
        $diffBankInsert = $bankTotal - $insertTotal;
        $diffPastorInsert = $pastorTotal - $insertTotal;
        [$statusBP, $labelBP] = correctReportStatusFromDiff($diffBankPastor);
        [$statusBI, $labelBI] = correctReportStatusFromDiff($diffBankInsert);
        [$statusPI, $labelPI] = correctReportStatusFromDiff($diffPastorInsert);
        $rows[] = [
            'intara_id' => (int) $row['intara_id'],
            'intara_name' => $row['intara_name'],
            'pastor_total' => $pastorTotal,
            'insert_total' => $insertTotal,
            'bank_total' => $bankTotal,
            'diff_bank_pastor' => $diffBankPastor,
            'diff_bank_insert' => $diffBankInsert,
            'diff_pastor_insert' => $diffPastorInsert,
            'status_bank_pastor' => $statusBP,
            'status_label_bank_pastor' => $labelBP,
            'status_bank_insert' => $statusBI,
            'status_label_bank_insert' => $labelBI,
            'status_pastor_insert' => $statusPI,
            'status_label_pastor_insert' => $labelPI,
        ];
    }
    return $rows;
}

/**
 * Compare bank slip grand totals vs INSERT DATA (imibare) totals per Intara for one month.
 */
function getBankVsInsertDataComparison($pdo, $month, $intara_id = null) {
    ensureCorrectReportTables($pdo);
    $monthInt = (int) $month;
    if ($monthInt < 1 || $monthInt > 12) {
        return [];
    }

    $sql = "SELECT
            i.id AS intara_id,
            i.name AS intara_name,
            COALESCE(m.insert_total, 0) AS insert_total,
            COALESCE(b.bank_total, 0) AS bank_total
        FROM intara i
        LEFT JOIN (
            SELECT intara_id, SUM(total) AS insert_total
            FROM imibare
            WHERE month = ?
            GROUP BY intara_id
        ) m ON m.intara_id = i.id
        LEFT JOIN (
            SELECT intara_id, SUM(amount) AS bank_total
            FROM bank_slips
            WHERE month = ?
            GROUP BY intara_id
        ) b ON b.intara_id = i.id
        WHERE 1=1";

    $params = [$monthInt, $monthInt];
    if ($intara_id) {
        $sql .= " AND i.id = ?";
        $params[] = (int) $intara_id;
    } else {
        $sql .= " AND (COALESCE(m.insert_total, 0) > 0 OR COALESCE(b.bank_total, 0) > 0)";
    }

    $sql .= " ORDER BY i.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $insertTotal = (float) $row['insert_total'];
        $bankTotal = (float) $row['bank_total'];
        $diff = $bankTotal - $insertTotal;
        [$status, $statusLabel] = correctReportStatusFromDiff($diff);
        $rows[] = [
            'intara_id' => (int) $row['intara_id'],
            'intara_name' => $row['intara_name'],
            'insert_total' => $insertTotal,
            'bank_total' => $bankTotal,
            'difference' => $diff,
            'status' => $status,
            'status_label' => $statusLabel,
        ];
    }
    return $rows;
}

/**
 * Per-Itorero comparison of RECU/CFMS for Icyacumi and Amaturo
 * between IBYAKIRIWE KURI RAPORT (mapato_pastor) and IBYANYUZE MUMA SUCHE (imibare).
 */
function getItoreroOfferingsComparison($pdo, $month, $intara_id = null, $itorero_id = null) {
    ensureCorrectReportTables($pdo);
    $monthInt = (int) $month;
    if ($monthInt < 1 || $monthInt > 12) {
        return [];
    }
    require_once __DIR__ . '/includes/imibare-math.php';
    $mapatoPastorList = getMapatoPastor($pdo, $intara_id ?: null, $monthInt, $itorero_id ?: null);
    $insertDataList = getImibare($pdo, $intara_id ?: null, $itorero_id ?: null, $monthInt);

    $rowsByItorero = [];
    foreach ($mapatoPastorList as $row) {
        $id = (int) ($row['itorero_id'] ?? 0);
        if ($id < 1) {
            continue;
        }
        if (!isset($rowsByItorero[$id])) {
            $rowsByItorero[$id] = [
                'itorero_id' => $id,
                'itorero_name' => $row['itorero_name'] ?? '—',
                'intara_name' => $row['intara_name'] ?? '—',
                'pastor_icyacumi_recu' => 0.0,
                'pastor_icyacumi_cfms' => 0.0,
                'pastor_amaturo_recu' => 0.0,
                'pastor_amaturo_cfms' => 0.0,
                'insert_icyacumi_recu' => 0.0,
                'insert_icyacumi_cfms' => 0.0,
                'insert_amaturo_recu' => 0.0,
                'insert_amaturo_cfms' => 0.0,
            ];
        }
        $rowsByItorero[$id]['pastor_icyacumi_recu'] += extractSumFromStored($row['icyacumi'] ?? '');
        $rowsByItorero[$id]['pastor_icyacumi_cfms'] += extractSumFromStored($row['icyacumi_cya_cms'] ?? '');
        $rowsByItorero[$id]['pastor_amaturo_recu'] += extractAmaturoGrossSumFromStored($row['amaturo'] ?? '');
        $rowsByItorero[$id]['pastor_amaturo_cfms'] += extractAmaturoGrossSumFromStored($row['amaturo_bya_cms'] ?? '');
    }
    foreach ($insertDataList as $row) {
        $id = (int) ($row['itorero_id'] ?? 0);
        if ($id < 1) {
            continue;
        }
        if (!isset($rowsByItorero[$id])) {
            $rowsByItorero[$id] = [
                'itorero_id' => $id,
                'itorero_name' => $row['itorero_name'] ?? '—',
                'intara_name' => $row['intara_name'] ?? '—',
                'pastor_icyacumi_recu' => 0.0,
                'pastor_icyacumi_cfms' => 0.0,
                'pastor_amaturo_recu' => 0.0,
                'pastor_amaturo_cfms' => 0.0,
                'insert_icyacumi_recu' => 0.0,
                'insert_icyacumi_cfms' => 0.0,
                'insert_amaturo_recu' => 0.0,
                'insert_amaturo_cfms' => 0.0,
            ];
        }
        $rowsByItorero[$id]['insert_icyacumi_recu'] += extractSumFromStored($row['icyacumi'] ?? '');
        $rowsByItorero[$id]['insert_icyacumi_cfms'] += extractSumFromStored($row['icyacumi_cya_cms'] ?? '');
        $rowsByItorero[$id]['insert_amaturo_recu'] += extractAmaturoGrossSumFromStored($row['amaturo'] ?? '');
        $rowsByItorero[$id]['insert_amaturo_cfms'] += extractAmaturoGrossSumFromStored($row['amaturo_bya_cms'] ?? '');
    }

    $rows = [];
    foreach ($rowsByItorero as $row) {
        $pastorIcyTotal = (float) $row['pastor_icyacumi_recu'] + (float) $row['pastor_icyacumi_cfms'];
        $insertIcyTotal = (float) $row['insert_icyacumi_recu'] + (float) $row['insert_icyacumi_cfms'];
        $pastorAmaRecu = (float) $row['pastor_amaturo_recu'];
        $pastorAmaCfms = (float) $row['pastor_amaturo_cfms'];
        $insertAmaRecu = (float) $row['insert_amaturo_recu'];
        $insertAmaCfms = (float) $row['insert_amaturo_cfms'];
        $pastorAmaTotal = $pastorAmaRecu + $pastorAmaCfms;
        $insertAmaTotal = $insertAmaRecu + $insertAmaCfms;
        $pastorAmaHalf = $pastorAmaTotal / 2;
        $insertAmaHalf = $insertAmaTotal / 2;
        $diffIcy = $insertIcyTotal - $pastorIcyTotal;
        $diffAma = $insertAmaHalf - $pastorAmaHalf;
        [$icyStatus, $icyLabel] = correctReportStatusFromDiff($diffIcy);
        [$amaStatus, $amaLabel] = correctReportStatusFromDiff($diffAma);

        $rows[] = [
            'itorero_id' => (int) $row['itorero_id'],
            'itorero_name' => $row['itorero_name'],
            'intara_name' => $row['intara_name'],
            'pastor_icyacumi_recu' => (float) $row['pastor_icyacumi_recu'],
            'pastor_icyacumi_cfms' => (float) $row['pastor_icyacumi_cfms'],
            'pastor_icyacumi_total' => $pastorIcyTotal,
            'pastor_amaturo_recu' => $pastorAmaRecu,
            'pastor_amaturo_cfms' => $pastorAmaCfms,
            'pastor_amaturo_total' => $pastorAmaTotal,
            'pastor_amaturo_half' => $pastorAmaHalf,
            'insert_icyacumi_recu' => (float) $row['insert_icyacumi_recu'],
            'insert_icyacumi_cfms' => (float) $row['insert_icyacumi_cfms'],
            'insert_icyacumi_total' => $insertIcyTotal,
            'insert_amaturo_recu' => $insertAmaRecu,
            'insert_amaturo_cfms' => $insertAmaCfms,
            'insert_amaturo_total' => $insertAmaTotal,
            'insert_amaturo_half' => $insertAmaHalf,
            'diff_icyacumi' => $diffIcy,
            'diff_amaturo' => $diffAma,
            'status_icyacumi' => $icyStatus,
            'status_icyacumi_label' => $icyLabel,
            'status_amaturo' => $amaStatus,
            'status_amaturo_label' => $amaLabel,
        ];
    }
    usort($rows, function ($a, $b) {
        $intaraCmp = strcmp((string) $a['intara_name'], (string) $b['intara_name']);
        return $intaraCmp !== 0 ? $intaraCmp : strcmp((string) $a['itorero_name'], (string) $b['itorero_name']);
    });
    return $rows;
}

/**
 * Trans-Funds pivot: grand totals per Intara per month from Mapato ya Pastoro (mapato_pastor).
 *
 * @param 'amaturo'|'icyacumi' $category
 * @return array{
 *   rows: list<array{intara_id:int,intara_name:string,months:array<int,float>,row_total:float}>,
 *   month_totals: array<int,float>,
 *   grand_total: float,
 *   year: int
 * }
 */
function getTransFundsIntaraMonthPivot($pdo, $category, $year, $restrictIntaraId = null) {
    require_once __DIR__ . '/includes/imibare-math.php';
    ensureCorrectReportTables($pdo);

    $yearInt = (int) $year;
    if ($yearInt < 2000 || $yearInt > 2100) {
        $yearInt = (int) date('Y');
    }

    $intaraList = getAllIntara($pdo);
    $grid = [];
    foreach ($intaraList as $intara) {
        $id = (int) $intara['id'];
        if ($restrictIntaraId !== null && $id !== (int) $restrictIntaraId) {
            continue;
        }
        $grid[$id] = [
            'intara_id' => $id,
            'intara_name' => $intara['name'],
            'months' => array_fill(1, 12, 0.0),
            'row_total' => 0.0,
        ];
    }

    $sql = "SELECT intara_id, month, icyacumi, icyacumi_cya_cms, amaturo, amaturo_bya_cms
            FROM mapato_pastor
            WHERE month BETWEEN 1 AND 12
              AND YEAR(created_at) = ?";
    $params = [$yearInt];
    if ($restrictIntaraId !== null) {
        $sql .= ' AND intara_id = ?';
        $params[] = (int) $restrictIntaraId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    foreach ($stmt->fetchAll() as $row) {
        $intaraId = (int) ($row['intara_id'] ?? 0);
        $month = (int) ($row['month'] ?? 0);
        if ($intaraId < 1 || $month < 1 || $month > 12 || !isset($grid[$intaraId])) {
            continue;
        }
        if ($category === 'icyacumi') {
            $amount = extractSumFromStored($row['icyacumi'] ?? '')
                + extractSumFromStored($row['icyacumi_cya_cms'] ?? '');
        } else {
            $amount = extractSumFromStored($row['amaturo'] ?? '')
                + extractSumFromStored($row['amaturo_bya_cms'] ?? '');
        }
        $grid[$intaraId]['months'][$month] += $amount;
        $grid[$intaraId]['row_total'] += $amount;
    }

    $rows = array_values($grid);
    usort($rows, fn($a, $b) => strcmp($a['intara_name'], $b['intara_name']));

    $monthTotals = array_fill(1, 12, 0.0);
    $grandTotal = 0.0;
    foreach ($rows as $r) {
        $grandTotal += $r['row_total'];
        for ($m = 1; $m <= 12; $m++) {
            $monthTotals[$m] += $r['months'][$m];
        }
    }

    return [
        'rows' => $rows,
        'month_totals' => $monthTotals,
        'grand_total' => $grandTotal,
        'year' => $yearInt,
    ];
}