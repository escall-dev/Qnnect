<?php
require_once(__DIR__ . '/../includes/session_config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$value = isset($_POST['value']) ? $_POST['value'] : '';
$allowed = ['1m','5m','15m','1d','no_expiry'];
if (!in_array($value, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid value']);
    exit;
}

$_SESSION['qr_expiry_option'] = $value;
echo json_encode(['success' => true, 'message' => 'Default QR expiry saved', 'value' => $value]);
exit;
?>


