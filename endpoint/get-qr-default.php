<?php
require_once(__DIR__ . '/../includes/session_config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$value = $_SESSION['qr_expiry_option'] ?? '1m';
echo json_encode(['success' => true, 'value' => $value]);
exit;
?>


