<?php
/**
 * Authentication Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const GUEST_USERNAME = 'guest';
const GUEST_EMAIL = 'guest@elamsystem.local';
const GUEST_PASSWORD = 'Guest@123';

function ensureUsersRoleColumn($pdo) {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
        $hasRole = (bool) $stmt->fetch();
        if (!$hasRole) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin','guest') NOT NULL DEFAULT 'admin' AFTER password");
        }
    } catch (PDOException $e) {
        // Keep auth working even if schema update fails.
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function enforceNoCache() {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'] ?? 'admin'
    ];
}

// Require login - redirect to login page if not authenticated
function requireLogin() {
    enforceNoCache();
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function isGuestUser() {
    return isLoggedIn() && (($_SESSION['role'] ?? '') === 'guest');
}

function requireAdmin() {
    requireLogin();
    if (isGuestUser()) {
        header('Location: reports.php?readonly=1');
        exit;
    }
}

// Register new user
function registerUser($pdo, $username, $email, $password) {
    ensureUsersRoleColumn($pdo);

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
    
    if ($stmt->execute([$username, $email, $hashedPassword])) {
        return ['success' => true, 'message' => 'Registration successful'];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

// Login user
function loginUser($pdo, $username, $password) {
    ensureUsersRoleColumn($pdo);
    ensureGuestAccount($pdo);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Regenerate session id on successful login to prevent session fixation.
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'] ?? (($user['username'] === GUEST_USERNAME) ? 'guest' : 'admin');
    
    return ['success' => true, 'message' => 'Login successful'];
}

function ensureGuestAccount($pdo) {
    ensureUsersRoleColumn($pdo);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([GUEST_USERNAME]);
    $existingGuest = $stmt->fetch();
    if ($existingGuest) {
        $stmt = $pdo->prepare("UPDATE users SET role = 'guest' WHERE id = ?");
        $stmt->execute([$existingGuest['id']]);
        return;
    }

    $hashedPassword = password_hash(GUEST_PASSWORD, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'guest')");
    $stmt->execute([GUEST_USERNAME, GUEST_EMAIL, $hashedPassword]);
}

function createUserByAdmin($pdo, $username, $password, $role = 'guest', $email = '') {
    ensureUsersRoleColumn($pdo);

    $username = trim($username);
    $email = trim($email);
    $role = ($role === 'admin') ? 'admin' : 'guest';

    if ($username === '' || $password === '') {
        return ['success' => false, 'message' => 'Username na password birasabwa.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password igomba kugira nibura 6 characters.'];
    }

    if ($email === '') {
        $email = $username . '@elamsystem.local';
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username cyangwa email bisanzwe bihari.'];
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$username, $email, $hashedPassword, $role])) {
        return ['success' => true, 'message' => 'User yashyizweho neza.'];
    }

    return ['success' => false, 'message' => 'Habaye ikibazo mu gukora user.'];
}

function getAllUsers($pdo) {
    ensureUsersRoleColumn($pdo);
    $stmt = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
    return $stmt->fetchAll();
}

// Logout user
function logoutUser() {
    enforceNoCache();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
    header('Location: login.php');
    exit;
}