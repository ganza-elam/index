<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/icons.php';

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
    $newIntaraId = $_POST['new_intara_id'] ?? null;

    $result = createUserByAdmin($pdo, $newUsername, $newPassword, $newRole, $newEmail, $newIntaraId);
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

// Handle Update User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    $username = trim($_POST['user_username']);
    $email = trim($_POST['user_email']);
    $role = $_POST['user_role'];
    $intaraId = ($role === 'guest') ? ($_POST['user_intara_id'] ?: null) : null;

    if ($id && $username && $email && $role) {
        if (updateUser($pdo, $id, $username, $email, $role, $intaraId)) {
            $message = '<div class="alert success">User updated successfully!</div>';
        } else {
            $message = '<div class="alert error">Failed to update User.</div>';
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

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = $_POST['user_id'];
    if ($id) {
        if (deleteUser($pdo, $id)) {
            $message = '<div class="alert success">User deleted successfully!</div>';
        } else {
            $message = '<div class="alert error">Failed to delete User.</div>';
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
    <title>Gererera Intara na Itorero - elamSystem</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/includes/material-icons-head.php'; ?>
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
    <?php require __DIR__ . '/includes/nav.php'; ?>
    
    <p style="text-align:right;color:#666;">May The Lord be with you: <b><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></b></p>

    <?= $message ?>

    <h1><?= mi('settings', 28) ?> ONGERAMO Intara n'Itorero</h1>

    <!-- User Management Section -->
    <div class="section">
        <h2><?= mi('group_add', 22) ?> Add new User</h2>
        <form method="POST" class="form-row" id="create-user-form">
            <input type="text" name="new_username" placeholder="Username" required>
            <div class="password-wrapper">
                <input type="password" name="new_password" placeholder="Password (min 6)" required>
                <button type="button" class="password-toggle" data-shown="false" aria-label="Show password" title="Show password">
                    <svg class="icon-show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="icon-hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
            <select name="new_role" id="new_role" onchange="toggleGuestIntaraField()">
                <option value="guest">Pastor</option>
                <option value="admin">Admin</option>
            </select>
            <select name="new_intara_id" id="new_intara_id" required>
                <option value="">Hitamo Intara (pastor) </option>
                <?php foreach ($intaraList as $intara): ?>
                    <option value="<?= (int) $intara['id'] ?>"><?= htmlspecialchars($intara['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="email" name="new_email" placeholder="Email (optional)">
            <button type="submit" name="create_user">Create User</button>
        </form>
        <p style="margin-top: 10px; color: #666; font-size: 13px;">
            Guest agomba guhabwa Intara imwe — azabona reports zayo gusa. Admin akajya kuri Admin Portal nyuma yo kwinjira.
            Niba email idatanzwe, system izakora email yo muri local ifite username.
        </p>
    </div>

    <div class="section">
        <h2><?= mi('list_alt', 22) ?> List y'Abakoresha</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Intara (Guest)</th>
                    <th>Ibikorwa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usersList as $appUser): ?>
                <tr>
                    <td><?= (int) $appUser['id'] ?></td>
                    <td><?= htmlspecialchars($appUser['username']) ?></td>
                    <td><?= htmlspecialchars($appUser['email']) ?></td>
                    <td><?= htmlspecialchars(strtoupper($appUser['role'] ?? 'admin')) ?></td>
                    <td><?= ($appUser['role'] ?? '') === 'guest' ? htmlspecialchars($appUser['intara_name'] ?? '—') : '—' ?></td>
                    <td>
                        <div class="actions">
                        <button class="edit" onclick="updateUser(
                            <?= $appUser['id'] ?>, 
                            '<?= htmlspecialchars($appUser['username'], ENT_QUOTES) ?>', 
                            '<?= htmlspecialchars($appUser['email'], ENT_QUOTES) ?>', 
                            '<?= htmlspecialchars($appUser['role'], ENT_QUOTES) ?>', 
                            '<?= (int)($appUser['intara_id'] ?? 0) ?>'
                        )">Hindura</button>
                            <button type="submit" name="delete_user" class="delete" onclick="confirmDeleteUser(<?= $appUser['id'] ?>, '<?= htmlspecialchars($appUser['username'], ENT_QUOTES) ?>')">Siba</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($usersList)): ?>
                <tr><td colspan="5" style="text-align:center;">Nta user uhari</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Intara Section -->
    <div class="section">
        <h2><?= mi('add_location', 22) ?> Ongeramo Intara</h2>
        <form method="POST" class="form-row">
            <input type="text" name="intara_name" placeholder="Izina ry'Intara" required>
            <button type="submit" name="add_intara">Ongeramo</button>
        </form>
        <p style="text-align: center; margin-top: 15px;">
            <a href="create-intara.php" style="color: #006400; text-decoration: none;">
                <?= mi('add', 18) ?> <strong>Create New Intara</strong> (INTARA y'indi)
            </a>
        </p>
    </div>

    <!-- Add Itorero Section -->
    <div class="section">
        <h2><?= mi('church', 22) ?> Ongeramo Itorero</h2>
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
        <h2><?= mi('map', 22) ?> List y'Intara</h2>
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
        <h2><?= mi('church', 22) ?> List y'Itorero</h2>
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

<!-- Edit User Modal -->
<div id="updateUserModal" class="modal">
    <div class="modal-content">
        <h3>Hindura User</h3>
        <form method="POST">
            <input type="hidden" name="user_id" id="edit_user_id">
            <label>Username:</label>
            <input type="text" name="user_username" id="edit_user_username" required style="width:100%;margin-bottom:15px;">
            <label>Email:</label>
            <input type="email" name="user_email" id="edit_user_email" required style="width:100%;margin-bottom:15px;">
            <label>Role:</label>
            <select name="user_role" id="edit_user_role" required style="width:100%;margin-bottom:15px;" onchange="toggleEditIntaraField()">
                <option value="guest">Pastor</option>
                <option value="admin">Admin</option>
            </select>
            <div id="edit_intara_label" style="margin-bottom:15px;">
                <label>Intara:</label>
                <select name="user_intara_id" id="edit_user_intara_id" style="width:100%;margin-bottom:15px;">
                    <option value="">-- Hitamo Intara --</option>
                    <?php foreach ($intaraList as $intara): ?>
                        <option value="<?= $intara['id'] ?>"><?= htmlspecialchars($intara['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-buttons">
                <button type="button" class="cancel" onclick="closeModal('updateUserModal')">Funga</button>
                <button type="submit" name="update_user">Bika</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Modal -->
<div id="deleteUserModal" class="modal">
    <div class="modal-content">
        <h3>Siba User</h3>
        <p>Urashaka gusiba user <strong id="delete_user_name"></strong>?</p>
        <form method="POST">
            <input type="hidden" name="user_id" id="delete_user_id">
            <div class="modal-buttons">
                <button type="button" class="cancel" onclick="closeModal('deleteUserModal')">Funga</button>
                <button type="submit" name="delete_user" class="delete">Siba</button>
            </div>
        </form>
    </div>
</div>

<script>

function toggleEditIntaraField() {
    const role = document.getElementById('edit_user_role').value;
    const intaraSelect = document.getElementById('edit_user_intara_id');
    const intaraLabel = document.getElementById('edit_intara_label');
    const isGuest = role === 'guest';
    intaraSelect.style.display = isGuest ? '' : 'none';
    intaraLabel.style.display = isGuest ? '' : 'none';
    intaraSelect.required = isGuest;
    if (!isGuest) intaraSelect.value = '';
}

function toggleGuestIntaraField() {
    const role = document.getElementById('new_role').value;
    const intaraSelect = document.getElementById('new_intara_id');
    const isGuest = role === 'guest';
    intaraSelect.style.display = isGuest ? '' : 'none';
    intaraSelect.required = isGuest;
    if (!isGuest) {
        intaraSelect.value = '';
    }
}
toggleGuestIntaraField();

function updateUser(id, username, email, role, intaraId) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_user_username').value = username;
    document.getElementById('edit_user_email').value = email;
    document.getElementById('edit_user_role').value = role;
    document.getElementById('edit_user_intara_id').value = intaraId;
    document.getElementById('updateUserModal').style.display = 'block';
    toggleEditIntaraField();
}

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

function confirmDeleteUser(id, username) {
    document.getElementById('delete_user_id').value = id;
    document.getElementById('delete_user_name').textContent = username;
    document.getElementById('deleteUserModal').style.display = 'block';
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

document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const input = btn.closest('.password-wrapper').querySelector('input');
        const shown = input.type === 'text';
        input.type = shown ? 'password' : 'text';
        btn.setAttribute('data-shown', shown ? 'false' : 'true');
        const label = shown ? 'Show password' : 'Hide password';
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
    });
});
</script>

</body>
</html>