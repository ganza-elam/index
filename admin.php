<?php
require_once 'config.php';
require_once 'auth.php';

// Require admin access for management
requireAdmin();

// Get current user
$currentUser = getCurrentUser();

$message = '';

// Handle Admin User Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $newUsername = trim($_POST['new_username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $newRole = $_POST['new_role'] ?? 'guest';
    $newEmail = trim($_POST['new_email'] ?? '');

    $result = createUserByAdmin($pdo, $newUsername, $newPassword, $newRole, $newEmail);
    if ($result['success']) {
        $message = '<div class="alert success">' . htmlspecialchars($result['message']) . '</div>';
    } else {
        $message = '<div class="alert error">' . htmlspecialchars($result['message']) . '</div>';
    }
}

// Handle Add Intara
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_intara'])) {
    $name = trim($_POST['intara_name'] ?? '');
    if ($name) {
        if (addIntara($pdo, $name)) {
            $message = '<div class="alert success">Intara added successfully!</div>';
        } else {
            $message = '<div class="alert error">Failed to add Intara.</div>';
        }
    }
}

// Handle Add Itorero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_itorero'])) {
    $intara_id = $_POST['itorero_intara_id'] ?? '';
    $name = trim($_POST['itorero_name'] ?? '');
    if ($intara_id && $name) {
        if (addItorero($pdo, $intara_id, $name)) {
            $message = '<div class="alert success">Itorero added successfully!</div>';
        } else {
            $message = '<div class="alert error">Failed to add Itorero.</div>';
        }
    }
}

// Handle Update Intara
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_intara'])) {
    $id = $_POST['intara_id'];
    $name = trim($_POST['intara_name']);
    if ($id && $name) {
        if (updateIntara($pdo, $id, $name)) {
            $message = '<div class="alert success">Intara updated successfully!</div>';
        } else {
            $message = '<div class="alert error">Failed to update Intara.</div>';
        }
    }
}

// Handle Update Itorero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_itorero'])) {
    $id = $_POST['itorero_id'];
    $intara_id = $_POST['itorero_intara_id'];
    $name = trim($_POST['itorero_name']);
    if ($id && $intara_id && $name) {
        if (updateItorero($pdo, $id, $intara_id, $name)) {
            $message = '<div class="alert success">Itorero updated successfully!</div>';
        } else {
            $message = '<div class="alert error">Failed to update Itorero.</div>';
        }
    }
}

// Handle Delete Intara
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_intara'])) {
    $id = $_POST['intara_id'];
    if ($id) {
        if (hasImibareForIntara($pdo, $id)) {
            $message = '<div class="alert error">Ntibishoboka gusiba Intara ifite final reports (imibare) zijyanye na yo.</div>';
        } else
        if (deleteIntara($pdo, $id)) {
            $message = '<div class="alert success">Intara deleted successfully!</div>';
        } else {
            $message = '<div class="alert error">Failed to delete Intara.</div>';
        }
    }
}

// Handle Delete Itorero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_itorero'])) {
    $id = $_POST['itorero_id'];
    if ($id) {
        if (hasImibareForItorero($pdo, $id)) {
            $message = '<div class="alert error">Ntibishoboka gusiba Itorero rifite final reports (imibare) zijyanye na ryo.</div>';
        } else
        if (deleteItorero($pdo, $id)) {
            $message = '<div class="alert success">Itorero deleted successfully!</div>';
        } else {
            $message = '<div class="alert error">Failed to delete Itorero.</div>';
        }
    }
}

// Get all data
$intaraList = getAllIntara($pdo);
$itoreroList = getAllItorero($pdo);
$usersList = getAllUsers($pdo);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gererera Intara na Itorero - Church Ledger</title>
    <link rel="icon" type="image/png" href="sda.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <style>
        .section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .section h2 { margin-top: 0; }
        
        .actions { display: flex; gap: 5px; }
        .actions form { display: inline; }
        .actions button { padding: 5px 10px; font-size: 12px; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
        }
        .modal h3 { margin-top: 0; }
        .modal-buttons { display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px; }
        .modal-buttons button { padding: 8px 16px; }
        .modal-buttons .cancel { background: #6c757d; }
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
        <a href="create-intara.php" style="color: #28a745;">➕ ADD Intara</a>
        <a href="logout.php" style="color: #dc3545;">🚪 LOG OUT</a>
    </div>
    
    <p style="text-align:right;color:#666;">May The Lord be with you: <b><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></b></p>

    <?= $message ?>

    <h1>⚙️ ONGERAMO Intara n'Itorero</h1>

    <!-- User Management Section -->
    <div class="section">
        <h2>👥 Add new User </h2>
        <form method="POST" class="form-row">
            <input type="text" name="new_username" placeholder="Username" required>
            <input type="password" name="new_password" placeholder="Password (min 6)" required>
            <select name="new_role">
                <option value="guest">Guest</option>
                <option value="admin">Admin</option>
            </select>
            <input type="email" name="new_email" placeholder="Email (optional)">
            <button type="submit" name="create_user">Create User</button>
        </form>
        <p style="margin-top: 10px; color: #666; font-size: 13px;">
            Niba email idatanzwe, system izakora email yo muri local ifite username.
        </p>
    </div>

    <div class="section">
        <h2>📋 List y'Abakoresha</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usersList as $appUser): ?>
                <tr>
                    <td><?= (int) $appUser['id'] ?></td>
                    <td><?= htmlspecialchars($appUser['username']) ?></td>
                    <td><?= htmlspecialchars($appUser['email']) ?></td>
                    <td><?= htmlspecialchars(strtoupper($appUser['role'] ?? 'admin')) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($usersList)): ?>
                <tr><td colspan="4" style="text-align:center;">Nta user uhari</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Intara Section -->
    <div class="section">
        <h2>📍 Ongeramo Intara</h2>
        <form method="POST" class="form-row">
            <input type="text" name="intara_name" placeholder="Izina ry'Intara" required>
            <button type="submit" name="add_intara">Ongeramo</button>
        </form>
        <p style="text-align: center; margin-top: 15px;">
            <a href="create-intara.php" style="color: #006400; text-decoration: none;">
                ➕ <strong>Create New Intara</strong> (INTARA y'indi)
            </a>
        </p>
    </div>

    <!-- Add Itorero Section -->
    <div class="section">
        <h2>🏛️ Ongeramo Itorero</h2>
        <form method="POST" class="form-row">
            <select name="itorero_intara_id" required>
                <option value="">-- Hitamo Intara --</option>
                <?php foreach ($intaraList as $intara): ?>
                    <option value="<?= $intara['id'] ?>"><?= htmlspecialchars($intara['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="itorero_name" placeholder="Izina ry'Itorero" required>
            <button type="submit" name="add_itorero">Ongeramo</button>
        </form>
    </div>

    <!-- Intara List -->
    <div class="section">
        <h2>📍 List y'Intara</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Izina</th>
                    <th>Itariki</th>
                    <th>Ibikorwa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($intaraList as $intara): ?>
                <tr>
                    <td><?= $intara['id'] ?></td>
                    <td><?= htmlspecialchars($intara['name']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($intara['created_at'])) ?></td>
                    <td>
                        <div class="actions">
                            <a href="create-itorero.php?intara_id=<?= $intara['id'] ?>" class="btn-link" style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 12px;">+ Itorero</a>
                            <button class="edit" onclick="editIntara(<?= $intara['id'] ?>, '<?= htmlspecialchars($intara['name'], ENT_QUOTES) ?>')">Hindura</button>
                            <form method="POST" onsubmit="return confirm('Urashaka gusiba iyi Intara?')">
                                <input type="hidden" name="intara_id" value="<?= $intara['id'] ?>">
                                <button type="submit" name="delete_intara" class="delete">Siba</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($intaraList)): ?>
                <tr><td colspan="4" style="text-align:center;">Nta Intara zihari</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Itorero List -->
    <div class="section">
        <h2>🏛️ List y'Itorero</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Izina</th>
                    <th>Intara</th>
                    <th>Itariki</th>
                    <th>Ibikorwa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itoreroList as $itorero): 
                    $itoreroIntara = array_filter($intaraList, fn($i) => $i['id'] == $itorero['intara_id']);
                    $itoreroIntara = reset($itoreroIntara);
                ?>
                <tr>
                    <td><?= $itorero['id'] ?></td>
                    <td><?= htmlspecialchars($itorero['name']) ?></td>
                    <td><?= htmlspecialchars($itoreroIntara['name'] ?? 'N/A') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($itorero['created_at'])) ?></td>
                    <td>
                        <div class="actions">
                            <button class="edit" onclick="editItorero(<?= $itorero['id'] ?>, <?= $itorero['intara_id'] ?>, '<?= htmlspecialchars($itorero['name'], ENT_QUOTES) ?>')">Hindura</button>
                            <form method="POST" onsubmit="return confirm('Urashaka gusiba iki Itorero?')">
                                <input type="hidden" name="itorero_id" value="<?= $itorero['id'] ?>">
                                <button type="submit" name="delete_itorero" class="delete">Siba</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($itoreroList)): ?>
                <tr><td colspan="5" style="text-align:center;">Nta Itorero rihari</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Intara Modal -->
<div id="editIntaraModal" class="modal">
    <div class="modal-content">
        <h3>Hindura Intara</h3>
        <form method="POST">
            <input type="hidden" name="intara_id" id="edit_intara_id">
            <label>Izina:</label>
            <input type="text" name="intara_name" id="edit_intara_name" required style="width:100%;margin-bottom:15px;">
            <div class="modal-buttons">
                <button type="button" class="cancel" onclick="closeModal('editIntaraModal')">Funga</button>
                <button type="submit" name="update_intara">Bika</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Itorero Modal -->
<div id="editItoreroModal" class="modal">
    <div class="modal-content">
        <h3>Hindura Itorero</h3>
        <form method="POST">
            <input type="hidden" name="itorero_id" id="edit_itorero_id">
            <label>Intara:</label>
            <select name="itorero_intara_id" id="edit_itorero_intara_id" required style="width:100%;margin-bottom:15px;">
                <?php foreach ($intaraList as $intara): ?>
                    <option value="<?= $intara['id'] ?>"><?= htmlspecialchars($intara['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Izina:</label>
            <input type="text" name="itorero_name" id="edit_itorero_name" required style="width:100%;margin-bottom:15px;">
            <div class="modal-buttons">
                <button type="button" class="cancel" onclick="closeModal('editItoreroModal')">Funga</button>
                <button type="submit" name="update_itorero">Bika</button>
            </div>
        </form>
    </div>
</div>

<script>
function editIntara(id, name) {
    document.getElementById('edit_intara_id').value = id;
    document.getElementById('edit_intara_name').value = name;
    document.getElementById('editIntaraModal').style.display = 'block';
}

function editItorero(id, intaraId, name) {
    document.getElementById('edit_itorero_id').value = id;
    document.getElementById('edit_itorero_intara_id').value = intaraId;
    document.getElementById('edit_itorero_name').value = name;
    document.getElementById('editItoreroModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

</body>
</html>
