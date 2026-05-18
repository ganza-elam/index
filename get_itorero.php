<?php
/**
 * AJAX Endpoint: Get Itorero by Intara ID
 * Returns JSON list of itorero for the selected intara
 */

header('Content-Type: application/json');

require_once 'config.php';
require_once 'auth.php';

requireLogin();

$intara_id = $_GET['intara_id'] ?? null;

if (!$intara_id) {
    echo json_encode([]);
    exit;
}

if (!guestCanAccessIntara((int) $intara_id)) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$itoreroList = getItoreroByIntara($pdo, $intara_id);

echo json_encode($itoreroList);