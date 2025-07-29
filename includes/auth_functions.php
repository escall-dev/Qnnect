<?php
// Authentication and Authorization Functions

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    // Check for either username or email (email is more consistently used across the app)
    return (isset($_SESSION['username']) && !empty($_SESSION['username'])) || 
           (isset($_SESSION['email']) && !empty($_SESSION['email']));
}

/**
 * Check if user has specific role
 */
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['role'] ?? 'admin';
    
    if ($required_role === 'super_admin') {
        return $user_role === 'super_admin';
    }
    
    if ($required_role === 'admin') {
        return in_array($user_role, ['admin', 'super_admin']);
    }
    
    return false;
}

/**
 * Check if user belongs to specific school or is super admin
 */
function canAccessSchool($school_id) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Super admins can access all schools
    if (hasRole('super_admin')) {
        return true;
    }
    
    // Regular admins can only access their assigned school
    $user_school_id = $_SESSION['school_id'] ?? null;
    return $user_school_id == $school_id;
}

/**
 * Require login - redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Determine the correct path to login
        $login_path = 'admin/login.php';
        if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
            $login_path = 'login.php';
        }
        header("Location: $login_path");
        exit();
    }
}

/**
 * Require specific role - show error if insufficient permissions
 */
function requireRole($required_role) {
    requireLogin();
    
    if (!hasRole($required_role)) {
        http_response_code(403);
        die('Access denied. Insufficient permissions.');
    }
}

/**
 * Require super admin access
 */
function requireSuperAdmin() {
    requireRole('super_admin');
}

/**
 * Get user's school information
 */
function getUserSchool($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $school_id = $_SESSION['school_id'] ?? null;
    if (!$school_id) {
        return null;
    }
    
    $sql = "SELECT * FROM schools WHERE id = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

/**
 * Get all schools (super admin only)
 */
function getAllSchools($conn) {
    if (!hasRole('super_admin')) {
        return [];
    }
    
    $sql = "SELECT * FROM schools WHERE status = 'active' ORDER BY name";
    $result = mysqli_query($conn, $sql);
    
    $schools = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $schools[] = $row;
    }
    
    return $schools;
}

/**
 * Log system activity
 */
function logActivity($conn, $action, $details = null, $school_id = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    if (!$school_id) {
        $school_id = $_SESSION['school_id'] ?? null;
    }
    
    // Ensure system_logs table exists
    $check_table = "CREATE TABLE IF NOT EXISTS system_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NULL,
        school_id INT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_action (user_id, action),
        INDEX idx_school_action (school_id, action),
        INDEX idx_created_at (created_at)
    )";
    mysqli_query($conn, $check_table);
    
    $sql = "INSERT INTO system_logs (user_id, school_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iissss", $user_id, $school_id, $action, $details, $ip_address, $user_agent);
        return mysqli_stmt_execute($stmt);
    }
    
    return false;
}

/**
 * Generate secure passkey for theme changes
 */
function generateThemePasskey($conn, $school_id = null, $expires_hours = 24) {
    requireSuperAdmin();
    
    $passkey = bin2hex(random_bytes(16)); // 32 character hex string
    $key_hash = password_hash($passkey, PASSWORD_DEFAULT);
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_hours} hours"));
    $created_by = $_SESSION['user_id'] ?? null;
    
    $sql = "INSERT INTO theme_passkeys (key_hash, created_by, school_id, expires_at) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "siis", $key_hash, $created_by, $school_id, $expires_at);
    
    if (mysqli_stmt_execute($stmt)) {
        logActivity($conn, 'THEME_PASSKEY_GENERATED', "School ID: {$school_id}, Expires: {$expires_at}");
        return $passkey;
    }
    
    return false;
}

/**
 * Verify theme passkey
 */
function verifyThemePasskey($conn, $passkey) {
    $sql = "SELECT * FROM theme_passkeys WHERE used = FALSE AND (expires_at IS NULL OR expires_at > NOW())";
    $result = mysqli_query($conn, $sql);
    
    while ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($passkey, $row['key_hash'])) {
            // Mark as used
            $update_sql = "UPDATE theme_passkeys SET used = TRUE, used_at = NOW(), used_by = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            $user_id = $_SESSION['user_id'] ?? null;
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $row['id']);
            mysqli_stmt_execute($stmt);
            
            logActivity($conn, 'THEME_PASSKEY_USED', "Passkey ID: {$row['id']}");
            return $row;
        }
    }
    
    return false;
}

/**
 * Update school theme
 */
function updateSchoolTheme($conn, $school_id, $theme_color, $passkey) {
    $passkey_data = verifyThemePasskey($conn, $passkey);
    
    if (!$passkey_data) {
        return ['success' => false, 'message' => 'Invalid or expired passkey'];
    }
    
    // Validate hex color
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $theme_color)) {
        return ['success' => false, 'message' => 'Invalid color format'];
    }
    
    $sql = "UPDATE schools SET theme_color = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $theme_color, $school_id);
    
    if (mysqli_stmt_execute($stmt)) {
        logActivity($conn, 'THEME_UPDATED', "School ID: {$school_id}, Color: {$theme_color}");
        return ['success' => true, 'message' => 'Theme updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to update theme'];
}

/**
 * Get filtered users based on role and school access
 */
function getFilteredUsers($conn, $school_id = null) {
    if (hasRole('super_admin')) {
        // Super admins see all users
        $sql = "SELECT u.*, s.name as school_name FROM users u 
                LEFT JOIN schools s ON u.school_id = s.id 
                ORDER BY u.username";
        $result = mysqli_query($conn, $sql);
    } else {
        // Regular admins only see users from their school
        $user_school_id = $_SESSION['school_id'] ?? null;
        if (!$user_school_id) {
            return [];
        }
        
        $sql = "SELECT u.*, s.name as school_name FROM users u 
                LEFT JOIN schools s ON u.school_id = s.id 
                WHERE u.school_id = ? 
                ORDER BY u.username";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_school_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    return $users;
}
?>