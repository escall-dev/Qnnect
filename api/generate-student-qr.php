<?php
// Generate a dynamic, expiring QR token for a student (valid for 60 seconds)
require_once(__DIR__ . '/../conn/db_connect.php');
require_once(__DIR__ . '/../includes/session_config.php');

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

// Ensure session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized', 'message' => 'Session expired']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$school_id = (int)$_SESSION['school_id'];

// Validate input
$student_id = isset($_REQUEST['student_id']) ? (int)$_REQUEST['student_id'] : 0;
if ($student_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'bad_request', 'message' => 'Missing student_id']);
    exit;
}

// Ensure DB connection
$conn = $conn_qr ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'db_error', 'message' => 'Database connection unavailable']);
    exit;
}

// Ensure table exists
$createSql = "CREATE TABLE IF NOT EXISTS student_qr_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    token VARCHAR(191) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL,
    school_id INT NOT NULL,
    INDEX idx_student_current (student_id, used_at, expires_at),
    INDEX idx_expires (expires_at),
    INDEX idx_user_school (user_id, school_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
@$conn->query($createSql);

// Verify the student belongs to this user/school
$stmt = $conn->prepare("SELECT tbl_student_id FROM tbl_student WHERE tbl_student_id = ? AND user_id = ? AND school_id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'db_error', 'message' => 'Failed to prepare statement']);
    exit;
}
$stmt->bind_param('iii', $student_id, $user_id, $school_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'not_found', 'message' => 'Student not found']);
    exit;
}

// Get expiry option from request or session default
$expiry_option = $_REQUEST['expiry_option'] ?? $_SESSION['qr_expiry_option'] ?? '1m';

// Generate secure random token (URL-safe)
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Handle no_expiry as a special case
if ($expiry_option === 'no_expiry') {
    // For no expiry, check if a permanent token already exists
    $check_perm = $conn->prepare("SELECT token FROM student_qr_tokens WHERE student_id = ? AND user_id = ? AND school_id = ? AND expires_at = '2099-12-31 23:59:59' LIMIT 1");
    if ($check_perm) {
        $check_perm->bind_param('iii', $student_id, $user_id, $school_id);
        $check_perm->execute();
        $perm_result = $check_perm->get_result();
        
        if ($perm_result && $perm_result->num_rows > 0) {
            // Use existing permanent token
            $row = $perm_result->fetch_assoc();
            $token = $row['token'];
            $expires_at = '2099-12-31 23:59:59';
        } else {
            // Create new permanent token
            $random = random_bytes(16);
            $token = base64url_encode($random) . '-PERM';
            $expires_at = '2099-12-31 23:59:59';
            
            // Insert permanent token
            $ins = $conn->prepare("INSERT INTO student_qr_tokens (student_id, token, expires_at, user_id, school_id) VALUES (?, ?, ?, ?, ?)");
            if (!$ins) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'db_error', 'message' => 'Failed to prepare insert']);
                exit;
            }
            $ins->bind_param('issii', $student_id, $token, $expires_at, $user_id, $school_id);
            $ok = $ins->execute();
            if (!$ok) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'db_error', 'message' => 'Failed to save token']);
                exit;
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'db_error', 'message' => 'Failed to check permanent token']);
        exit;
    }
} else {
    // Calculate expiry based on selected option for timed options
    $expiry_seconds = 60; // default 1 minute
    switch ($expiry_option) {
        case '1m':
            $expiry_seconds = 60;
            break;
        case '5m':
            $expiry_seconds = 300;
            break;
        case '15m':
            $expiry_seconds = 900;
            break;
        case '1d':
            $expiry_seconds = 86400;
            break;
        default:
            $expiry_seconds = 60; // fallback to 1 minute
    }
    
    $expires_at = date('Y-m-d H:i:s', time() + $expiry_seconds);
    
    // Generate secure random token for timed options
    $random = random_bytes(16);
    $token = base64url_encode($random) . '-' . base64url_encode(pack('N', time()));
    
    // Revoke all previous un-used tokens for this student (so only latest is valid)
    $revoke = $conn->prepare("UPDATE student_qr_tokens SET revoked_at = NOW() WHERE student_id = ? AND user_id = ? AND school_id = ? AND used_at IS NULL AND (expires_at > NOW())");
    if ($revoke) {
        $revoke->bind_param('iii', $student_id, $user_id, $school_id);
        $revoke->execute();
    }
    
    // Insert new token for timed options
    $ins = $conn->prepare("INSERT INTO student_qr_tokens (student_id, token, expires_at, user_id, school_id) VALUES (?, ?, ?, ?, ?)");
    if (!$ins) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'db_error', 'message' => 'Failed to prepare insert']);
        exit;
    }
    $ins->bind_param('issii', $student_id, $token, $expires_at, $user_id, $school_id);
    $ok = $ins->execute();
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'db_error', 'message' => 'Failed to save token']);
        exit;
    }
}



// Build QR image URL (frontend can also render client-side if preferred)
$qr_image_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($token);

// Log the response for debugging
error_log("API Response - student_id: $student_id, expiry_option: $expiry_option, token: $token, expires_at: $expires_at");

echo json_encode([
    'success' => true,
    'data' => [
        'student_id' => $student_id,
        'token' => $token,
        'expires_at' => $expires_at,
        'qr_image_url' => $qr_image_url
    ]
]);
exit;
?>
