<?php
require_once 'config.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/mapato-pastor-fields.php';

header('Content-Type: application/json; charset=utf-8');

requireAdmin();

$intaraId = isset($_GET['intara_id']) ? (int) $_GET['intara_id'] : 0;
$month = isset($_GET['month']) ? (int) $_GET['month'] : 0;

if ($intaraId < 1 || $month < 1 || $month > 12) {
    echo json_encode(['fields' => []]);
    exit;
}

$defs = getMapatoPastorFieldDefs($pdo, $intaraId, $month);
echo json_encode(['fields' => $defs], JSON_UNESCAPED_UNICODE);
