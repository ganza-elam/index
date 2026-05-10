<?php
require_once 'config.php';
require_once 'auth.php';

// Require admin access for creation
requireAdmin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_intara'])) {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $message = '<div class="alert error">Shyiramo izina ry\'Intara</div>';
    } else {
        if (addIntara($pdo, $name)) {
            $stmt = $pdo->query("SELECT * FROM intara ORDER BY id DESC LIMIT 1");
            $newIntara = $stmt->fetch();
            header("Location: create-intara.php?id=" . $newIntara['id'] . "&created=1");
            exit;
        } else {
            $message = '<div class="alert error">Habaye ikibazo mu kubika.</div>';
        }
    }
}

$createdIntara = null;
if (isset($_GET['created']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM intara WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $createdIntara = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { max-width: 600px; margin: 30px auto; }
        input { height: 50px; font-size: 18px; padding: 0 15px; margin-bottom: 20px; }
        button { padding: 15px; font-size: 18px; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #006400; }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin-top: 20px;
        }
        .success-box h3 { color: #155724; margin-top: 0; }
        .success-box p { color: #155724; margin-bottom: 20px; }
        .success-box a {
            display: inline-block;
            padding: 15px 30px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
        }
        .success-box a:hover { background: #218838; }
    </style>
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
    </div>

    <?= $message ?>
    <h1>📍 Create Intara</h1>

    <?php if ($createdIntara): ?>
        <div class="success-box">
            <h3>✅ Intara: <?= htmlspecialchars($createdIntara['name']) ?> yashyirwamo!</h3>
            <p>Now create Itoreros under this Intara:</p>
            <a href="create-itorero.php?intara_id=<?= $createdIntara['id'] ?>">
                🏛️ Create Itorero for <?= htmlspecialchars($createdIntara['name']) ?>
            </a>
        </div>
        <div class="back-link">
            <a href="create-intara.php">Create another Intara</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <label>Izina ry'Intara:</label>
            <input type="text" name="name" placeholder="Urugero: Intara y'Iburengerazuba" required>
            <button type="submit" name="create_intara">💾 Create Intara</button>
        </form>
        <div class="back-link">
            <a href="admin.php">← Back to Admin</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
