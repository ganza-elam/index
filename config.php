<?php
/**
 * Database Configuration
 * Plain PHP MySQL Connection
 */

function loadEnvFile($path) {
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $value = trim($value, "\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function envValue($key, $default = null) {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

loadEnvFile(__DIR__ . '/.env');

$db_host = envValue('DB_HOST', '127.0.0.1');
$db_name = envValue('DB_NAME', 'elam_system');
$db_user = envValue('DB_USER', 'root');
$db_pass = envValue('DB_PASS', '');

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

/**
 * Save Imibare record
 */
function saveImibare($pdo, $data) {
    $stmt = $pdo->prepare("INSERT INTO imibare (
        lesi, intara_id, itorero_id, icyacumi, icyacumi_cya_cms, amaturo, amaturo_bya_cms,
        umusaruro, ituro, filide, ss, ubusonga, mifem, ja, total
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    return $stmt->execute([
        $data['lesi'],
        $data['intara_id'],
        $data['itorero_id'],
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
        $data['total']
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
        lesi = ?, intara_id = ?, itorero_id = ?, icyacumi = ?, icyacumi_cya_cms = ?, amaturo = ?, amaturo_bya_cms = ?,
        umusaruro = ?, ituro = ?, filide = ?, ss = ?, ubusonga = ?, mifem = ?, ja = ?, total = ?
        WHERE id = ?");

    return $stmt->execute([
        $data['lesi'],
        $data['intara_id'],
        $data['itorero_id'],
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
function getImibare($pdo, $intara_id = null, $itorero_id = null) {
    $sql = "SELECT i.*, intara.name as intara_name, itorero.name as itorero_name 
            FROM imibare i 
            LEFT JOIN intara ON i.intara_id = intara.id 
            LEFT JOIN itorero ON i.itorero_id = itorero.id 
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
    
    $sql .= " ORDER BY i.created_at DESC";
    
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