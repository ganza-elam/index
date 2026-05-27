<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/icons.php';

// Require admin access for creation
requireAdmin();

$message = '';
$intara_id = $_GET['intara_id'] ?? null;

// Get intara details
$intara = null;
if ($intara_id) {
    $stmt = $pdo->prepare("SELECT * FROM intara WHERE id = ?");
    $stmt->execute([$intara_id]);
    $intara = $stmt->fetch();
}

if (!$intara) {
    die("Intara not found! <a href='admin.php'>Go back</a>");
}

// Get existing itoreros for this intara
$itoreroList = getItoreroByIntara($pdo, $intara_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_itorero'])) {
    $name = trim($_POST['name'] ?? '');
    
    if (empty($name)) {
        $message = '<div class="alert error">Shyiramo izina ry\'Itorero</div>';
    } else {
        if (addItorero($pdo, $intara_id, $name)) {
            // Refresh itorero list
            $itoreroList = getItoreroByIntara($pdo, $intara_id);
            $message = '<div class="alert success">Itorero: ' . htmlspecialchars($name) . ' ryongewe!</div>';
        } else {
            $message = '<div class="alert error">Habaye ikibazo mu kubika.</div>';
        }
    }
}

// Handle delete itorero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_itorero'])) {
    $itorero_id = $_POST['itorero_id'];
    if (deleteItorero($pdo, $itorero_id)) {
        $itoreroList = getItoreroByIntara($pdo, $intara_id);
        $message = '<div class="alert success">Itorero sibitswe!</div>';
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
        .container { max-width: 700px; margin: 30px auto; }
        .intara-badge { 
            display: inline-block; 
            background: #667eea; 
            color: white; 
            padding: 8px 16px; 
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .form-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        input[type="text"] { width: 70%; }
        .itorero-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .itorero-item .name { font-size: 16px; font-weight: bold; }
        .itorero-item .actions { display: flex; gap: 10px; }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .back-link { text-align: center; margin-top: 20px; }
    </style>
</head>
<body class="app-body">
<?php require __DIR__ . '/includes/nav.php'; ?>
<div class="container">
    <div class="brand-header">
        <img class="brand-logo" src="assets/sda.png" alt="Adventist logo">
        <div class="brand-text">
            <h2>Seventh Day Adventist Church</h2>
            <small>Stewardship and offerings management</small>
        </div>
    </div>

    <?= $message ?>

    <div class="intara-badge">
        <?= mi('place', 18) ?> Intara: <?= htmlspecialchars($intara['name']) ?>
    </div>

    <h1><?= mi('church', 28) ?> Create Itorero</h1>
    
    <!-- Add New Itorero Form -->
    <div class="form-section">
        <h3>Ongeramo Itorero</h3>
        <form method="POST">
            <input type="text" name="name" placeholder="Urugero: Itorero ry'Ishyeri" required>
            <button type="submit" name="create_itorero">➕ Add Itorero</button>
        </form>
    </div>

    <!-- Existing Itoreros -->
    <h3>Itorero ziri muri <?= htmlspecialchars($intara['name']) ?> (<?= count($itoreroList) ?>)</h3>
    
    <?php if (empty($itoreroList)): ?>
        <div class="empty-state">
            <p>📭 Nta Itorero hari kugeza ubu</p>
            <p>Ongeramo itorero y mbere</p>
        </div>
    <?php else: ?>
        <?php foreach ($itoreroList as $itorero): ?>
        <div class="itorero-item">
            <div>
                <div class="name">🏛️ <?= htmlspecialchars($itorero['name']) ?></div>
                <small style="color: #666;">Created: <?= date('d/m/Y H:i', strtotime($itorero['created_at'])) ?></small>
            </div>
            <div class="actions">
                <form method="POST" onsubmit="return confirm('Urashaka gusiba iki Itorero?')">
                    <input type="hidden" name="itorero_id" value="<?= $itorero['id'] ?>">
                    <button type="submit" name="delete_itorero" class="delete">🗑️ Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div class="back-link">
        <a href="admin.php">← Back to Admin</a>
    </div>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>
</body>
</html>