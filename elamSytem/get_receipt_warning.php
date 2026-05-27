<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'auth.php';

requireLogin();

$itoreroId = (int) ($_GET['itorero_id'] ?? 0);
if (!$itoreroId) {
    echo json_encode(['warning' => null]);
    exit;
}

if (!guestCanAccessItorero($pdo, $itoreroId) && isGuestUser()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

ensureReceiptTables($pdo);
$warning = getItoreroReceiptWarning($pdo, $itoreroId);
echo json_encode(['warning' => $warning]);
