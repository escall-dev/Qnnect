<?php
// Use isolated session for the Super Admin portal so it doesn't conflict with regular admin session
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once 'database.php';
require_once '../conn/db_connect.php';

// Require super admin access only
requireLogin();
if (!hasRole('super_admin')) {
    $redirect = 'admin_panel.php';
    header('Location: super_admin_login.php?force_pin=1&redirect=' . urlencode($redirect));
    exit();
}

// Get user's role and school info
$user_role = $_SESSION['role'] ?? 'admin';
$user_school_id = $_SESSION['school_id'] ?? null;
$is_super_admin = hasRole('super_admin');

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_users':
            $users = getFilteredUsers($conn, getEffectiveScopeSchoolId());
            echo json_encode(['success' => true, 'users' => $users]);
            exit();
        case 'get_user':
            $uid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            if ($uid <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid user id']); exit(); }
            $stmt = mysqli_prepare($conn, "SELECT id, username, email, role, school_id FROM users WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'i', $uid);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $user = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);
            if (!$user) { echo json_encode(['success' => false, 'message' => 'User not found']); exit(); }
            // Access control: non-SA can only access within their school
            if (!$is_super_admin) {
                if (!$user_school_id || (int)$user['school_id'] !== (int)$user_school_id) {
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    exit();
                }
            }
            echo json_encode(['success' => true, 'user' => $user]);
            exit();
            
        case 'promote_user':
            if (!$is_super_admin) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit();
            }
            
            $user_id = (int)$_POST['user_id'];
            $new_role = $_POST['new_role'];
            
            if (!in_array($new_role, ['admin', 'super_admin'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid role']);
                exit();
            }
            
            $sql = "UPDATE users SET role = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                logActivity($conn, 'USER_ROLE_CHANGED', "User ID: {$user_id}, New Role: {$new_role}");
                echo json_encode(['success' => true, 'message' => 'User role updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user role']);
            }
            exit();
        case 'create_user':
            // Create new user (admins limited to their school as admin role)
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'admin';
            $school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : null;

            if ($username === '' || $email === '' || $password === '') {
                echo json_encode(['success' => false, 'message' => 'Username, email and password are required']);
                exit();
            }
            if (!$is_super_admin) {
                // Non-SA can only create admin in their own school
                $role = 'admin';
                $school_id = $user_school_id;
                if (!$school_id) {
                    echo json_encode(['success' => false, 'message' => 'No school context']);
                    exit();
                }
            } else {
                // Validate role value for SA
                if (!in_array($role, ['admin','super_admin'], true)) $role = 'admin';
            }
            // Unique email check
            $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res && mysqli_fetch_assoc($res)) { mysqli_stmt_close($stmt); echo json_encode(['success' => false, 'message' => 'Email already exists']); exit(); }
            mysqli_stmt_close($stmt);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($school_id) {
                $stmt = mysqli_prepare($conn, 'INSERT INTO users (username, email, password, role, school_id) VALUES (?,?,?,?,?)');
                mysqli_stmt_bind_param($stmt, 'ssssi', $username, $email, $hash, $role, $school_id);
            } else {
                $stmt = mysqli_prepare($conn, 'INSERT INTO users (username, email, password, role) VALUES (?,?,?,?)');
                mysqli_stmt_bind_param($stmt, 'ssss', $username, $email, $hash, $role);
            }
            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($conn);
                logActivity($conn, 'USER_CREATED', "User ID: {$new_id}, Role: {$role}");
                echo json_encode(['success' => true, 'message' => 'User created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create user']);
            }
            mysqli_stmt_close($stmt);
            exit();
        case 'update_user':
            // Update user fields. Non-SA limited to username within their school.
            $uid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $username = isset($_POST['username']) ? trim($_POST['username']) : null;
            $new_role = $_POST['role'] ?? null;
            $school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : null;
            $password = $_POST['password'] ?? null;
            if ($uid <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid user id']); exit(); }

            // Load existing
            $stmt0 = mysqli_prepare($conn, 'SELECT id, role, school_id FROM users WHERE id = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt0, 'i', $uid);
            mysqli_stmt_execute($stmt0);
            $res0 = mysqli_stmt_get_result($stmt0);
            $target = $res0 ? mysqli_fetch_assoc($res0) : null;
            mysqli_stmt_close($stmt0);
            if (!$target) { echo json_encode(['success' => false, 'message' => 'User not found']); exit(); }
            if (!$is_super_admin) {
                // Must be same school
                if ((int)$target['school_id'] !== (int)$user_school_id) { echo json_encode(['success' => false, 'message' => 'Access denied']); exit(); }
            }
            $fields = [];
            $params = [];
            $types = '';
            if ($username !== null && $username !== '') { $fields[] = 'username = ?'; $params[] = $username; $types .= 's'; }
            if ($is_super_admin && $new_role !== null) {
                if (in_array($new_role, ['admin','super_admin'], true)) { $fields[] = 'role = ?'; $params[] = $new_role; $types .= 's'; }
            }
            if ($is_super_admin) {
                // SA can reassign school (optional)
                if ($school_id !== null) { $fields[] = 'school_id = ?'; $params[] = $school_id; $types .= 'i'; }
            }
            if ($password !== null && $password !== '') { $fields[] = 'password = ?'; $params[] = password_hash($password, PASSWORD_DEFAULT); $types .= 's'; }
            if (!$fields) { echo json_encode(['success' => false, 'message' => 'No changes to apply']); exit(); }
            $types .= 'i';
            $params[] = $uid;
            $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (mysqli_stmt_execute($stmt)) {
                logActivity($conn, 'USER_UPDATED', "User ID: {$uid}");
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user']);
            }
            mysqli_stmt_close($stmt);
            exit();
        case 'delete_user':
            // Only super admin can delete users
            if (!$is_super_admin) { echo json_encode(['success' => false, 'message' => 'Access denied']); exit(); }
            $uid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            if ($uid <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid user id']); exit(); }
            // Prevent deleting self
            if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $uid) { echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']); exit(); }
            $stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $uid);
            if (mysqli_stmt_execute($stmt)) {
                logActivity($conn, 'USER_DELETED', "User ID: {$uid}");
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            }
            mysqli_stmt_close($stmt);
            exit();
            
        case 'add_school':
            if (!$is_super_admin) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit();
            }
            
            $name = trim($_POST['school_name']);
            $code = trim($_POST['school_code']);
            $theme = $_POST['theme_color'] ?? '#098744';
            
            if (empty($name) || empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Name and code are required']);
                exit();
            }
            
            // Uniqueness check (friendly error before attempting insert)
            $stmtU = mysqli_prepare($conn, 'SELECT id, name, code FROM schools WHERE name = ? OR code = ? LIMIT 1');
            mysqli_stmt_bind_param($stmtU, 'ss', $name, $code);
            mysqli_stmt_execute($stmtU);
            $resU = mysqli_stmt_get_result($stmtU);
            $dup = $resU ? mysqli_fetch_assoc($resU) : null;
            mysqli_stmt_close($stmtU);
            if ($dup) {
                $which = ($dup['name'] === $name) ? 'name' : 'code';
                echo json_encode(['success' => false, 'message' => 'A school with this ' . $which . ' already exists']);
                exit();
            }

            $sql = "INSERT INTO schools (name, code, theme_color) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $name, $code, $theme);
            $ok = @mysqli_stmt_execute($stmt);
            $errno = mysqli_errno($conn);
            mysqli_stmt_close($stmt);
            if ($ok) {
                logActivity($conn, 'SCHOOL_ADDED', "Name: {$name}, Code: {$code}");
                echo json_encode(['success' => true, 'message' => 'School added successfully']);
            } else if ($errno === 1062) {
                echo json_encode(['success' => false, 'message' => 'A school with this name or code already exists']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add school']);
            }
            exit();
        case 'get_school':
            if (!$is_super_admin) { echo json_encode(['success' => false, 'message' => 'Access denied']); exit(); }
            $sid = isset($_POST['school_id']) ? (int)$_POST['school_id'] : 0;
            if ($sid <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid school id']); exit(); }
            $stmt = mysqli_prepare($conn, 'SELECT id, name, code, theme_color, status, created_at FROM schools WHERE id = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'i', $sid);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);
            if (!$row) { echo json_encode(['success' => false, 'message' => 'School not found']); exit(); }
            echo json_encode(['success' => true, 'school' => $row]);
            exit();
        case 'update_school':
            if (!$is_super_admin) { echo json_encode(['success' => false, 'message' => 'Access denied']); exit(); }
            $sid = isset($_POST['school_id']) ? (int)$_POST['school_id'] : 0;
            $name = isset($_POST['school_name']) ? trim($_POST['school_name']) : '';
            $code = isset($_POST['school_code']) ? trim($_POST['school_code']) : '';
            $theme = $_POST['theme_color'] ?? null;
            $status = $_POST['status'] ?? null;
            if ($sid <= 0 || $name === '' || $code === '') { echo json_encode(['success' => false, 'message' => 'Invalid fields']); exit(); }
            $fields = ['name = ?','code = ?'];
            $types = 'ss';
            $params = [$name, $code];
            if ($theme !== null && $theme !== '') { $fields[] = 'theme_color = ?'; $types .= 's'; $params[] = $theme; }
            if ($status !== null && in_array($status, ['active','inactive'], true)) { $fields[] = 'status = ?'; $types .= 's'; $params[] = $status; }
            $types .= 'i';
            $params[] = $sid;
            $sql = 'UPDATE schools SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            if ($ok) { logActivity($conn, 'SCHOOL_UPDATED', 'School ID: ' . $sid); echo json_encode(['success' => true, 'message' => 'School updated']); }
            else { echo json_encode(['success' => false, 'message' => 'Update failed']); }
            exit();
        case 'delete_school':
            if (!$is_super_admin) { echo json_encode(['success' => false, 'message' => 'Access denied']); exit(); }
            $sid = isset($_POST['school_id']) ? (int)$_POST['school_id'] : 0;
            if ($sid <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid school id']); exit(); }
            $stmt = mysqli_prepare($conn, 'DELETE FROM schools WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $sid);
            $ok = @mysqli_stmt_execute($stmt);
            $errno = mysqli_errno($conn);
            mysqli_stmt_close($stmt);
            if ($ok) { logActivity($conn, 'SCHOOL_DELETED', 'School ID: ' . $sid); echo json_encode(['success' => true, 'message' => 'School deleted']); exit(); }
            if ($errno === 1451) { // FK constraint -> soft delete
                $stmt2 = mysqli_prepare($conn, "UPDATE schools SET status = 'inactive' WHERE id = ?");
                mysqli_stmt_bind_param($stmt2, 'i', $sid);
                $ok2 = mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
                if ($ok2) { logActivity($conn, 'SCHOOL_SOFT_DELETED', 'School ID: ' . $sid); echo json_encode(['success' => true, 'message' => 'School has linked records. Marked as inactive instead.']); }
                else { echo json_encode(['success' => false, 'message' => 'Delete failed']); }
            } else {
                echo json_encode(['success' => false, 'message' => 'Delete failed']);
            }
            exit();
            
        case 'generate_passkey':
            if (!$is_super_admin) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit();
            }
            
            $school_id = isset($_POST['school_id']) ? (int)$_POST['school_id'] : null;
            $expires_hours = (int)($_POST['expires_hours'] ?? 24);
            
            $passkey = generateThemePasskey($conn, $school_id, $expires_hours);
            
            if ($passkey) {
                echo json_encode(['success' => true, 'passkey' => $passkey, 'message' => 'Passkey generated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to generate passkey']);
            }
            exit();
            
        case 'update_theme':
            $school_id = (int)$_POST['school_id'];
            $theme_color = $_POST['theme_color'];
            $passkey = $_POST['passkey'];
            
            if (!$is_super_admin && !canAccessSchool($school_id)) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit();
            }
            
            $result = updateSchoolTheme($conn, $school_id, $theme_color, $passkey);
            echo json_encode($result);
            exit();
        case 'set_scope':
            if (!$is_super_admin) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit();
            }
            $raw = $_POST['scope_school_id'] ?? '';
            if ($raw === '' || $raw === 'all') {
                unset($_SESSION['scope_school_id']);
                echo json_encode(['success' => true]);
                exit();
            }
            $sid = (int)$raw;
            $stmt = mysqli_prepare($conn, 'SELECT id FROM schools WHERE id = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'i', $sid);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $exists = $res && mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
            if (!$exists) {
                echo json_encode(['success' => false, 'message' => 'Invalid school id']);
                exit();
            }
            $_SESSION['scope_school_id'] = $sid;
            echo json_encode(['success' => true]);
            exit();
    }
}

// Get data for display
$user_school = getUserSchool($conn);
$all_schools = $is_super_admin ? getAllSchools($conn) : [];

// Effective scope: SA may select a school; Admin fixed to own
$effective_scope_id = getEffectiveScopeSchoolId();
$users = getFilteredUsers($conn, $effective_scope_id);

// Get system logs respecting scope
if ($is_super_admin) {
    if ($effective_scope_id) {
        $logs_sql = "SELECT sl.*, u.username, s.name as school_name FROM system_logs sl 
       LEFT JOIN users u ON sl.user_id = u.id 
       LEFT JOIN schools s ON sl.school_id = s.id 
       WHERE sl.school_id = ? 
       ORDER BY sl.created_at DESC LIMIT 50";
        $stmt = mysqli_prepare($conn, $logs_sql);
        mysqli_stmt_bind_param($stmt, "i", $effective_scope_id);
        mysqli_stmt_execute($stmt);
        $logs_result = mysqli_stmt_get_result($stmt);
    } else {
        $logs_sql = "SELECT sl.*, u.username, s.name as school_name FROM system_logs sl 
       LEFT JOIN users u ON sl.user_id = u.id 
       LEFT JOIN schools s ON sl.school_id = s.id 
       ORDER BY sl.created_at DESC LIMIT 50";
    $logs_result = mysqli_query($conn, $logs_sql);
    }
} else {
    $logs_sql = "SELECT sl.*, u.username, s.name as school_name FROM system_logs sl 
       LEFT JOIN users u ON sl.user_id = u.id 
       LEFT JOIN schools s ON sl.school_id = s.id 
       WHERE sl.school_id = ? 
       ORDER BY sl.created_at DESC LIMIT 50";
    $stmt = mysqli_prepare($conn, $logs_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_school_id);
    mysqli_stmt_execute($stmt);
    $logs_result = mysqli_stmt_get_result($stmt);
}

$logs = [];
while ($row = mysqli_fetch_assoc($logs_result)) {
    $logs[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - QR Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        :root { --primary-color: <?php echo $user_school['theme_color'] ?? '#098744'; ?>; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, var(--primary-color) 0%, #0a5c2e 100%); min-height: 100vh; margin: 0; padding: 20px; overflow-x: hidden; }
        .header { background: #fff; padding: 20px; border-radius: 16px; box-shadow: 0 6px 24px rgba(0,0,0,0.08); margin: 0 auto 16px auto; max-width: 1200px; display:flex; justify-content: space-between; align-items: center; }
        .user-info { display:flex; align-items:center; }
        .user-avatar { width:40px; height:40px; border-radius:50%; background: var(--primary-color); display:flex; align-items:center; justify-content:center; color:#fff; margin-right:12px; }
        .settings-outer-container { max-width: 1200px; margin: 0 auto; }
    .settings-container { background: rgba(255,255,255,0.95); border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); padding: 0; overflow: hidden; border: 2px solid #098744; }
        .settings-content { padding: 20px; background: #fff; }
        .settings-title { display:flex; align-items:center; gap:12px; padding: 18px 20px; border-bottom: 1px solid #e9ecef; background: #fff; }
        .settings-title h2 { font-size: 22px; margin: 0; font-weight: 800; color: #1f2937; }
        .settings-nav { display:flex; gap: 12px; flex-wrap: wrap; padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e9ecef; }
        .settings-nav a { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius: 10px; color:#1f2937; text-decoration:none; background:#fff; border:1px solid #e5e7eb; transition: all .2s ease; font-weight:600; }
        .settings-nav a:hover { background: #f1f5f9; }
        .settings-nav a.active { background: var(--primary-color); color:#fff; border-color: var(--primary-color); }
        .content-section { display:none; }
        .content-section.active { display:block; }
    .card { background:#fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border: 1px solid #0ca557; margin-bottom: 20px; }
        .card-header { background: none; border-bottom: 1px solid #eee; padding: 20px; font-weight: 600; }
        .card-body { padding: 20px; }
        .btn-primary { background: var(--primary-color); border-color: var(--primary-color); }
        .btn-primary:hover { filter: brightness(0.95); }
        .table th { border-top: none; font-weight: 600; color: #666; }
    /* System-like table and filters */
    .styled-green-table { border-collapse: separate !important; border-spacing: 0 !important; border-radius: 12px; overflow: hidden; }
    .styled-green-table th, .styled-green-table td { border: 1px solid #0ca557 !important; padding: 12px 8px !important; vertical-align: middle !important; }
    .styled-green-table thead th { text-align: center !important; background: #e9f9f0; }
    .filters-bar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:12px; }
    .filters-bar .form-control, .filters-bar .form-select { min-width: 180px; }
        .role-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .role-admin { background: #e3f2fd; color:#1976d2; }
        .role-super_admin { background: #fff3e0; color:#f57c00; }
        .passkey-display { background:#f8f9fa; border:2px dashed #dee2e6; border-radius:8px; padding:15px; text-align:center; font-family: 'Courier New', monospace; font-size:18px; font-weight:bold; color: var(--primary-color); margin: 15px 0; }
        /* Hierarchical table styling */
        .course-row { background: #f9fafb; font-weight: 600; }
        .course-row .badge-school { font-weight: 600; }
        .course-row .toggle-btn { cursor: pointer; color: #495057; }
        .section-row { background: #ffffff; }
        .section-row td { border-top: none; }
        .section-indent { padding-left: 28px; position: relative; }
        .section-indent:before { content: ''; position: absolute; left: 12px; top: 50%; width: 10px; height: 1px; background: rgba(0,0,0,0.15); }
        .muted-actions .btn { padding: .15rem .4rem; }
        .school-name-colored { font-weight: 600; }
        .table thead th { position: sticky; top: 0; background: #ffffff; z-index: 1; }
        .course-row:hover { filter: brightness(0.98); }
    </style>
</head>
<body>
    <div class="header">
        <div style="display:flex; align-items:center; gap:10px;">
            <h1 style="margin:0;">Admin Panel</h1>
        </div>
        <div class="user-info">
            <div class="user-avatar"><i class="fas fa-user"></i></div>
            <div>
                <div style="font-weight:600;"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div style="font-size:14px;color:#666;"><?php echo $is_super_admin ? 'Super Administrator' : 'Administrator'; ?></div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal (reusable) -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalTitle">Please Confirm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmModalBody">Are you sure?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmModalOkBtn">Yes, Continue</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal (reusable) -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalTitle">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="successModalBody">Operation completed successfully.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal (reusable) -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="errorModalTitle">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="errorModalBody">Something went wrong.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-outer-container">
        <div class="settings-container">
            <div class="settings-title">
                <h2><i class="fas fa-cog"></i> Admin Controls</h2>
                <div class="ms-auto d-none d-md-flex" style="gap:8px;">
                    <?php if ($is_super_admin): ?>
                    <div class="d-flex align-items-center" style="gap:8px;">
                        <label for="scope_school_id" class="form-label mb-0">School Scope:</label>
                        <select id="scope_school_id" class="form-select form-select-sm" style="width:auto;">
                            <option value="" <?php echo empty($effective_scope_id) ? 'selected' : ''; ?>>All Schools</option>
                            <?php foreach ($all_schools as $school): ?>
                                <option value="<?php echo (int)$school['id']; ?>" <?php echo ($effective_scope_id == $school['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($school['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-outline-danger" href="super_admin_login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            <div class="settings-nav" id="ap_nav">
                <a href="#dashboard" data-section="dashboard" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="#users" data-section="users"><i class="fas fa-users"></i> User Management</a>
                <?php if ($is_super_admin): ?>
                <a href="#schools" data-section="schools"><i class="fas fa-school"></i> School Management</a>
                <?php endif; ?>
               
                <a href="#themes" data-section="themes"><i class="fas fa-palette"></i> Theme Management</a>
                <?php if ($is_super_admin): ?>
                <a href="#passkeys" data-section="passkeys"><i class="fas fa-key"></i> Passkey Generator</a>
                <?php endif; ?>
                <a href="#logs" data-section="logs"><i class="fas fa-list-alt"></i> System Logs</a>
                <a href="#profile" data-section="profile"><i class="fas fa-user-cog"></i> Profile Settings</a>
                <?php if ($is_super_admin): ?>
                <a href="#students" data-section="students"><i class="fas fa-user-graduate"></i> Students</a>
                <a href="#attendance" data-section="attendance"><i class="fas fa-check-square"></i> Attendance</a>
                <a href="#schedules" data-section="schedules"><i class="fas fa-calendar"></i> Schedules</a>
                <a href="#courses" data-section="courses"><i class="fas fa-book"></i> Courses & Sections</a>
                <a href="#backup-restore" data-section="backup-restore" class="text-danger"><i class="fas fa-database"></i> Backup & Restore</a>
                <a href="#delete-data" data-section="delete-data" class="text-danger"><i class="fas fa-trash-alt"></i> Delete Data</a>
                <?php endif; ?>
            </div>
            <div class="settings-content">

        <!-- Dashboard Section -->
        <div id="dashboard" class="content-section active">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x text-primary mb-3"></i>
                            <h3><?php echo count($users); ?></h3>
                            <p class="text-muted">Total Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-school fa-2x text-success mb-3"></i>
                            <h3><?php echo $is_super_admin ? count($all_schools) : 1; ?></h3>
                            <p class="text-muted">Schools</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-2x text-warning mb-3"></i>
                            <h3>0</h3>
                            <p class="text-muted">Active Schedules</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-list-alt fa-2x text-info mb-3"></i>
                            <h3><?php echo count($logs); ?></h3>
                            <p class="text-muted">Recent Logs</p>
                        </div>
                    </div>
                </div>
            </div>
    </div>
        
    <!-- User Management Section -->
        <div id="users" class="content-section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>User Management</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="refreshUsers()" type="button">
                            <i class="fas fa-arrows-rotate"></i> Refresh
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="openCreateUser()" type="button">
                            <i class="fas fa-user-plus"></i> Add User
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>School</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="users_table_body">
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['school_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary" title="Edit" onclick="openEditUser(<?php echo (int)$user['id']; ?>)"><i class="fas fa-pen"></i></button>
                                            <?php if ($is_super_admin): ?>
                                            <button class="btn btn-outline-warning" title="Role" onclick="openRoleChange(<?php echo (int)$user['id']; ?>, '<?php echo $user['role']; ?>')"><i class="fas fa-user-shield"></i></button>
                                            <button class="btn btn-outline-danger" title="Delete" onclick="deleteUser(<?php echo (int)$user['id']; ?>)"><i class="fas fa-trash"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Create/Edit User Modal -->
            <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="userModalTitle">Add User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="userForm">
                                <input type="hidden" id="user_id">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" id="u_username" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" id="u_email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password <span class="text-muted" id="pwd_hint">(required)</span></label>
                                    <input type="password" class="form-control" id="u_password">
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <select class="form-select" id="u_role" <?php echo $is_super_admin ? '' : 'disabled'; ?>>
                                            <option value="admin">Admin</option>
                                            <?php if ($is_super_admin): ?><option value="super_admin">Super Admin</option><?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">School <?php if ($is_super_admin): ?><span class="text-muted">(optional)</span><?php endif; ?></label>
                                        <?php if ($is_super_admin): ?>
                                        <select class="form-select" id="u_school_id">
                                            <option value="">None</option>
                                            <?php foreach ($all_schools as $school): ?>
                                            <option value="<?php echo (int)$school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php else: ?>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_school['name'] ?? ''); ?>" disabled>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="userModalSave">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Change Role Modal (SA only) -->
            <?php if ($is_super_admin): ?>
            <div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Change Role</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="role_user_id">
                            <select class="form-select" id="role_select">
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="roleSaveBtn">Update</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
    </div>

        <!-- School Management Section (Super Admin Only) -->
    <?php if ($is_super_admin): ?>
    <div id="schools" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h5>School Management</h5>
                </div>
                <div class="card-body">
                    <form id="add_school_form" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="school_name" placeholder="School Name" required>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="school_code" placeholder="School Code" required>
                            </div>
                            <div class="col-md-3">
                                <input type="color" class="form-control" id="school_theme" value="#098744">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Add School</button>
                            </div>
                        </div>
                    </form>
                    
                                        <div class="table-responsive">
                                                <table class="table styled-green-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Theme</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                                                        <th style="width:140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_schools as $school): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($school['name']); ?></td>
                                    <td><?php echo htmlspecialchars($school['code']); ?></td>
                                    <td>
                                        <div style="width: 30px; height: 20px; background: <?php echo $school['theme_color']; ?>; border-radius: 4px; display: inline-block;"></div>
                                        <?php echo $school['theme_color']; ?>
                                    </td>
                                    <td>
                                                                                <span class="badge <?php echo ($school['status'] ?? 'active') === 'inactive' ? 'bg-secondary' : 'bg-success'; ?>"><?php echo ucfirst($school['status']); ?></span>
                                    </td>
                                                                        <td><?php echo date('M j, Y', strtotime($school['created_at'])); ?></td>
                                                                        <td>
                                                                                <div class="btn-group btn-group-sm" role="group">
                                                                                        <button class="btn btn-outline-primary" onclick="openEditSchool(<?php echo (int)$school['id']; ?>)"><i class="fas fa-pen"></i></button>
                                                                                        <button class="btn btn-outline-danger" onclick="deleteSchool(<?php echo (int)$school['id']; ?>)"><i class="fas fa-trash"></i></button>
                                                                                </div>
                                                                        </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
                        <!-- Edit School Modal -->
                        <div class="modal fade" id="schoolModal" tabindex="-1" aria-labelledby="schoolModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="schoolModalLabel">Edit School</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" id="ed_school_id">
                                        <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" class="form-control" id="ed_school_name" required>
                                        </div>
                                        <div class="mb-3">
                                                <label class="form-label">Code</label>
                                                <input type="text" class="form-control" id="ed_school_code" required>
                                        </div>
                                        <div class="mb-3">
                                                <label class="form-label">Theme Color</label>
                                                <input type="color" class="form-control" id="ed_theme_color">
                                        </div>
                                        <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" id="ed_status">
                                                        <option value="active">Active</option>
                                                        <option value="inactive">Inactive</option>
                                                </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" id="schoolModalSave">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
    </div>
    <?php endif; ?>

        <!-- Schedule Generator Section -->
       

        <!-- Theme Management Section -->
        <div id="themes" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h5>Theme Management</h5>
                </div>
                <div class="card-body">
                    <form id="theme_form">
                        <div class="row">
                            <?php if ($is_super_admin): ?>
                            <div class="col-md-4">
                                <label class="form-label">School</label>
                                <select class="form-select" id="theme_school_id" required>
                                    <option value="">Select School</option>
                                    <?php foreach ($all_schools as $school): ?>
                                    <option value="<?php echo $school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <input type="hidden" id="theme_school_id" value="<?php echo $user_school_id; ?>">
                            <?php endif; ?>
                            <div class="col-md-4">
                                <label class="form-label">Theme Color</label>
                                <input type="color" class="form-control" id="theme_color" value="<?php echo $user_school['theme_color'] ?? '#098744'; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Passkey</label>
                                <input type="text" class="form-control" id="theme_passkey" placeholder="Enter passkey" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Update Theme</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Passkey Generator Section (Super Admin Only) -->
        <?php if ($is_super_admin): ?>
        <div id="passkeys" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h5>Passkey Generator</h5>
                </div>
                <div class="card-body">
                    <form id="passkey_form">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">School (Optional)</label>
                                <select class="form-select" id="passkey_school_id">
                                    <option value="">All Schools</option>
                                    <?php foreach ($all_schools as $school): ?>
                                    <option value="<?php echo $school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Expires In (Hours)</label>
                                <input type="number" class="form-control" id="expires_hours" value="24" min="1" max="168" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">Generate Passkey</button>
                            </div>
                        </div>
                    </form>
                    
                    <div id="passkey_result" style="display: none;">
                        <h6 class="mt-4">Generated Passkey:</h6>
                        <div class="passkey-display" id="passkey_display"></div>
                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            This passkey can be used to change themes. Keep it secure and share only with authorized personnel.
                        </p>
                    </div>
                </div>
            </div>
    </div>
    <?php endif; ?>

    <!-- System Logs Section -->
    <div id="logs" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h5>System Logs</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <?php if ($is_super_admin): ?>
                                    <th>School</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo date('M j, H:i', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                                    <?php if ($is_super_admin): ?>
                                    <td><?php echo htmlspecialchars($log['school_name'] ?? 'N/A'); ?></td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div>

        <!-- Profile Settings (same as users.php) -->
    <div id="profile" class="content-section">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> Update User Account</h5>
                </div>
                <div class="card-body">
                    <?php 
                        // Load current user
                        $email_curr = $_SESSION['email'] ?? null;
                        $curr_user = null;
                        if ($email_curr) {
                            $stmt_u = mysqli_prepare($conn, 'SELECT * FROM users WHERE email = ? LIMIT 1');
                            mysqli_stmt_bind_param($stmt_u, 's', $email_curr);
                            mysqli_stmt_execute($stmt_u);
                            $res_u = mysqli_stmt_get_result($stmt_u);
                            $curr_user = $res_u ? mysqli_fetch_assoc($res_u) : null;
                            mysqli_stmt_close($stmt_u);
                        }
                    ?>
                    <form action="controller.php?action=edit" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="redirect" value="admin_panel.php#profile">
                        <div class="text-center mb-4">
                            <img id="ap_profile_preview" 
                                 src="<?php echo (!empty($curr_user['profile_image']) ? '../' . $curr_user['profile_image'] : 'image/SPCPC-logo-trans.png'); ?>" 
                                 alt="Profile Picture" 
                                 style="width:150px;height:150px;border-radius:50%;object-fit:cover;border:3px solid var(--primary-color);">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="ap_username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="ap_username" name="username" value="<?php echo htmlspecialchars($curr_user['username'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="ap_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="ap_email" name="email" value="<?php echo htmlspecialchars($curr_user['email'] ?? ''); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="ap_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="ap_password" name="password" placeholder="Leave blank to keep current">
                            </div>
                            <div class="col-md-6">
                                <label for="ap_profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="ap_profile_image" name="profile_image" accept="image/*">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>

    <?php if ($is_super_admin): ?>
    <div class="settings-outer-container">
        <div class="settings-container">
            <div class="settings-content">
    <div id="students" class="content-section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Students</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" id="student_add_btn" type="button"><i class="fas fa-plus"></i> Add</button>
                    <button class="btn btn-outline-secondary btn-sm" id="students_refresh" type="button"><i class="fas fa-arrows-rotate"></i> Refresh</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm"><thead><tr><th>Name</th><th>Course/Section</th><th>School</th><th>Actions</th></tr></thead><tbody id="students_table_body"></tbody></table>
                </div>
            </div>
        </div>
    </div>
    <div id="attendance" class="content-section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Attendance Logs</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" id="attendance_add_btn" type="button"><i class="fas fa-plus"></i> Add</button>
                    <button class="btn btn-outline-secondary btn-sm" id="attendance_refresh" type="button"><i class="fas fa-arrows-rotate"></i> Refresh</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm"><thead><tr><th>Time In</th><th>Status</th><th>Student</th><th>Course/Section</th><th>School</th><th>Actions</th></tr></thead><tbody id="attendance_table_body"></tbody></table>
                </div>
            </div>
        </div>
    </div>
    <div id="schedules" class="content-section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Class Schedules</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" id="schedule_add_btn" type="button"><i class="fas fa-plus"></i> Add</button>
                    <button class="btn btn-outline-secondary btn-sm" id="schedules_refresh" type="button"><i class="fas fa-arrows-rotate"></i> Refresh</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm"><thead><tr><th>Subject</th><th>Instructor</th><th>Section</th><th>Day</th><th>Start</th><th>End</th><th>Room</th><th>Actions</th></tr></thead><tbody id="schedules_table_body"></tbody></table>
                </div>
            </div>
        </div>
    </div>
    <div id="courses" class="content-section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Courses & Sections</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" id="course_add_btn" type="button"><i class="fas fa-plus"></i> Add Course</button>
                    <button class="btn btn-success btn-sm" id="section_add_btn" type="button"><i class="fas fa-plus"></i> Add Section</button>
                    <button class="btn btn-outline-secondary btn-sm" id="courses_refresh" type="button"><i class="fas fa-arrows-rotate"></i> Refresh</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="courses_hier_table">
                        <thead>
                            <tr>
                                <th style="width:36px;"></th>
                                <th>Name</th>
                                <th>School</th>
                                <th style="width:120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="courses_hier_tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Backup & Restore Section -->
    <div id="backup-restore" class="content-section">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-database"></i> Backup & Restore</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6><i class="fas fa-download"></i> Create Backup</h6>
                            </div>
                            <div class="card-body">
                                <p>Create a backup of your database.</p>
                                <div class="mb-3">
                                    <label class="form-label">Select Database:</label>
                                    <select class="form-select" id="backup_database">
                                        <option value="login_register">User Database</option>
                                        <option value="qr_attendance_db">Attendance Database</option>
                                    </select>
                                </div>
                                <button class="btn btn-success" id="create_backup_btn">
                                    <i class="fas fa-download"></i> Create Backup
                                </button>
                                <div id="backup_progress" class="mt-3" style="display: none;">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                                    </div>
                                    <small class="text-muted">Creating backup...</small>
                                </div>
                                <div id="backup_result" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6><i class="fas fa-upload"></i> Restore Backup</h6>
                            </div>
                            <div class="card-body">
                                <p>Restore from a backup file.</p>
                                <div class="mb-3">
                                    <label class="form-label">Select Backup File:</label>
                                    <input type="file" class="form-control" id="restore_file" accept=".sql">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Target Database:</label>
                                    <select class="form-select" id="restore_database">
                                        <option value="login_register">User Database</option>
                                        <option value="qr_attendance_db">Attendance Database</option>
                                    </select>
                                </div>
                                <button class="btn btn-warning" id="restore_backup_btn">
                                    <i class="fas fa-upload"></i> Restore Backup
                                </button>
                                <div id="restore_progress" class="mt-3" style="display: none;">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width: 100%"></div>
                                    </div>
                                    <small class="text-muted">Restoring backup...</small>
                                </div>
                                <div id="restore_result" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-history"></i> Available Backups</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>File Name</th>
                                                <th>Database</th>
                                                <th>Date Created</th>
                                                <th>Size</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="backup_files_list">
                                            <tr>
                                                <td colspan="5" class="text-center">Loading...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Data Section -->
    <div id="delete-data" class="content-section">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5><i class="fas fa-trash-alt"></i> Delete Data</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> These operations are irreversible. Make sure you have backups before proceeding.
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-users"></i> Student Data</h6>
                            </div>
                            <div class="card-body">
                                <p>Delete all student records and related data.</p>
                                <button class="btn btn-danger" id="delete_students_btn">
                                    <i class="fas fa-trash"></i> Delete All Students
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-check-square"></i> Attendance Data</h6>
                            </div>
                            <div class="card-body">
                                <p>Delete all attendance records.</p>
                                <button class="btn btn-danger" id="delete_attendance_btn">
                                    <i class="fas fa-trash"></i> Delete All Attendance
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-calendar"></i> Schedule Data</h6>
                            </div>
                            <div class="card-body">
                                <p>Delete all class schedules.</p>
                                <button class="btn btn-danger" id="delete_schedules_btn">
                                    <i class="fas fa-trash"></i> Delete All Schedules
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-database"></i> All Data</h6>
                            </div>
                            <div class="card-body">
                                <p>Delete ALL data from the system.</p>
                                <button class="btn btn-danger" id="delete_all_data_btn">
                                    <i class="fas fa-trash-alt"></i> DELETE EVERYTHING
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div id="delete_result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>
     <?php endif; ?>

    <!-- CRUD Modals: Students, Attendance, Schedules, Courses, Sections -->
    <?php if ($is_super_admin): ?>
    <!-- Student Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalTitle">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="studentForm">
                        <input type="hidden" id="student_id">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" id="student_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course/Section</label>
                            <input type="text" class="form-control" id="student_course_section" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">School</label>
                            <select class="form-select" id="student_school_id" required>
                                <option value="">Select School</option>
                                <?php foreach ($all_schools as $school): ?>
                                <option value="<?php echo (int)$school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="studentModalSave">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Modal -->
    <div class="modal fade" id="attendanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attendanceModalTitle">Add Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="attendanceForm">
                        <input type="hidden" id="attendance_id">
                        <div class="mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="number" class="form-control" id="attendance_student_id" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Time In</label>
                            <input type="datetime-local" class="form-control" id="attendance_time_in" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="attendance_status">
                                <option value="On Time">On Time</option>
                                <option value="Late">Late</option>
                                <option value="Absent">Absent</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">School</label>
                            <select class="form-select" id="attendance_school_id" required>
                                <option value="">Select School</option>
                                <?php foreach ($all_schools as $school): ?>
                                <option value="<?php echo (int)$school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="attendanceModalSave">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalTitle">Add Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="scheduleForm">
                        <input type="hidden" id="schedule_id">
                        <div class="mb-3"><label class="form-label">Subject</label><input type="text" class="form-control" id="schedule_subject" required></div>
                        <div class="mb-3"><label class="form-label">Instructor</label><input type="text" class="form-control" id="schedule_teacher" required></div>
                        <div class="mb-3"><label class="form-label">Section</label><input type="text" class="form-control" id="schedule_section" required></div>
                        <div class="mb-3"><label class="form-label">Day of Week</label>
                            <select class="form-select" id="schedule_day">
                                <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
                                <option>Thursday</option><option>Friday</option><option>Saturday</option><option>Sunday</option>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label">Start</label><input type="time" class="form-control" id="schedule_start" required></div>
                            <div class="col-6"><label class="form-label">End</label><input type="time" class="form-control" id="schedule_end" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Room</label><input type="text" class="form-control" id="schedule_room"></div>
                        <div class="mb-3">
                            <label class="form-label">School</label>
                            <select class="form-select" id="schedule_school_id" required>
                                <option value="">Select School</option>
                                <?php foreach ($all_schools as $school): ?>
                                <option value="<?php echo (int)$school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="scheduleModalSave">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Modal -->
    <div class="modal fade" id="courseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="courseModalTitle">Add Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="courseForm">
                        <input type="hidden" id="course_id">
                        <div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control" id="course_name" required></div>
                        <div class="mb-3">
                            <label class="form-label">School</label>
                            <select class="form-select" id="course_school_id" required>
                                <option value="">Select School</option>
                                <?php foreach ($all_schools as $school): ?>
                                <option value="<?php echo (int)$school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="courseModalSave">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Modal -->
    <div class="modal fade" id="sectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sectionModalTitle">Add Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="sectionForm">
                        <input type="hidden" id="section_id">
                        <div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control" id="section_name" required></div>
                        <div class="mb-3"><label class="form-label">Course</label>
                            <select class="form-select" id="section_course_id" required></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">School</label>
                            <select class="form-select" id="section_school_id" required>
                                <option value="">Select School</option>
                                <?php foreach ($all_schools as $school): ?>
                                <option value="<?php echo (int)$school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="sectionModalSave">Save</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Reusable confirm + success helpers
        let _confirmModal, _successModal, _confirmResolve;
        document.addEventListener('DOMContentLoaded', () => {
            const cmEl = document.getElementById('confirmModal');
            if (cmEl) _confirmModal = new bootstrap.Modal(cmEl);
            const smEl = document.getElementById('successModal');
            if (smEl) _successModal = new bootstrap.Modal(smEl);
            const emEl = document.getElementById('errorModal');
            if (emEl) window._errorModal = new bootstrap.Modal(emEl);
            const okBtn = document.getElementById('confirmModalOkBtn');
            if (okBtn) {
                okBtn.addEventListener('click', () => {
                    if (_confirmResolve) _confirmResolve(true);
                    _confirmResolve = null;
                    if (_confirmModal) _confirmModal.hide();
                });
            }
            cmEl?.addEventListener('hidden.bs.modal', () => {
                if (_confirmResolve) _confirmResolve(false);
                _confirmResolve = null;
            });
        });

        function showConfirm(options) {
            const { title = 'Please Confirm', message = 'Are you sure?', confirmText = 'Yes, Continue' } = options || {};
            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalBody').textContent = message;
            document.getElementById('confirmModalOkBtn').textContent = confirmText;
            if (_confirmModal) _confirmModal.show();
            return new Promise(resolve => { _confirmResolve = resolve; });
        }

        function showSuccess(message, title = 'Success') {
            document.getElementById('successModalTitle').textContent = title;
            document.getElementById('successModalBody').textContent = message || 'Operation completed successfully.';
            const el = document.getElementById('successModal');
            if (_successModal && el) {
                _successModal.show();
                return new Promise(resolve => {
                    el.addEventListener('hidden.bs.modal', () => resolve(true), { once: true });
                });
            }
            return Promise.resolve(true);
        }

        function showError(message, title = 'Error') {
            const t = document.getElementById('errorModalTitle'); if (t) t.textContent = title;
            const b = document.getElementById('errorModalBody'); if (b) b.textContent = message || 'Something went wrong.';
            const el = document.getElementById('errorModal');
            if (window._errorModal && el) {
                window._errorModal.show();
                return new Promise(resolve => {
                    el.addEventListener('hidden.bs.modal', () => resolve(true), { once: true });
                });
            }
            alert(message || 'Something went wrong.');
            return Promise.resolve(true);
        }

        async function postAndParseJson(url, body) {
            const response = await fetch(url, { method: 'POST', body });
            const raw = await response.text();
            try {
                return JSON.parse(raw);
            } catch (err) {
                console.error('Server returned non-JSON:', raw);
                throw new Error(raw || 'Invalid server response');
            }
        }

        function showSection(sectionId, el) {
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            const sec = document.getElementById(sectionId);
            if (sec) sec.classList.add('active');
            document.querySelectorAll('.settings-nav a').forEach(a => a.classList.remove('active'));
            if (el) el.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            try { location.hash = '#' + sectionId; } catch (_) {}
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Wire tab clicks
            document.querySelectorAll('.settings-nav a').forEach(a => {
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = a.getAttribute('data-section');
                    showSection(id, a);
                });
            });
            // Initial activation: ?tab= or hash
            const params = new URLSearchParams(location.search);
            const fromTab = params.get('tab');
            const hash = (location.hash || '').replace('#','');
            const target = fromTab || hash || 'dashboard';
            const link = document.querySelector(`.settings-nav a[data-section="${target}"]`) || document.querySelector('.settings-nav a');
            if (link) {
                showSection(link.getAttribute('data-section'), link);
            }
            const scopeSel = document.getElementById('scope_school_id');
            if (scopeSel) {
                scopeSel.addEventListener('change', async (e) => {
                    const val = e.target.value || 'all';
                    const fd = new URLSearchParams();
                    fd.append('action','set_scope');
                    fd.append('scope_school_id', val);
                    try {
                        const r = await fetch('admin_panel.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: fd.toString() });
                        const data = await r.json();
                        if (data && data.success) {
                            location.reload();
                        } else {
                            alert('Failed to update scope: ' + (data && data.message ? data.message : 'Unknown error'));
                        }
                    } catch (err) {
                        alert('Request failed: ' + (err && err.message ? err.message : 'Unknown error'));
                    }
                });
            }
        });

        function changeUserRole(userId, newRole) {
            if (!confirm('Are you sure you want to change this user\'s role?')) {
                return;
            }
            
            fetch('admin_panel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=promote_user&user_id=${userId}&new_role=${newRole}`
            })
            .then(response => response.json())
            .then(async data => {
                if (data.success) {
                    await showSuccess(data.message);
                    location.reload();
                } else {
                    await showError('Error: ' + data.message);
                }
            });
        }

        // Add School Form
        (function() {
            const addForm = document.getElementById('add_school_form');
            if (!addForm) return;
            addForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'add_school');
                formData.append('school_name', document.getElementById('school_name').value);
                formData.append('school_code', document.getElementById('school_code').value);
                formData.append('theme_color', document.getElementById('school_theme').value);
                try {
                    const data = await postAndParseJson('admin_panel.php', formData);
                    if (data && data.success) {
                        await showSuccess(data.message || 'School added successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + ((data && data.message) || 'Request failed'));
                    }
                } catch (err) {
                    alert('Error adding school: ' + (err && err.message ? err.message : 'Unknown error'));
                    console.error('Add school error:', err);
                }
            });
        })();

        // Theme Form
        document.getElementById('theme_form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'update_theme');
            formData.append('school_id', document.getElementById('theme_school_id').value);
            formData.append('theme_color', document.getElementById('theme_color').value);
            formData.append('passkey', document.getElementById('theme_passkey').value);
            
            fetch('admin_panel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(async data => {
                if (data.success) {
                    await showSuccess(data.message || 'Theme updated successfully.');
                    location.reload();
                } else {
                    await showError('Error: ' + (data.message || 'Invalid or expired passkey'));
                }
            })
            .catch(async err => {
                await showError('Request failed: ' + (err && err.message ? err.message : 'Unknown error'));
            });
        });

        // Passkey Form
        <?php if ($is_super_admin): ?>
        document.getElementById('passkey_form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'generate_passkey');
            formData.append('school_id', document.getElementById('passkey_school_id').value);
            formData.append('expires_hours', document.getElementById('expires_hours').value);
            
            fetch('admin_panel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(async data => {
                if (data.success) {
                    document.getElementById('passkey_display').textContent = data.passkey;
                    document.getElementById('passkey_result').style.display = 'block';
                } else {
                    await showError('Error: ' + (data.message || 'Failed to generate passkey'));
                }
            })
            .catch(async err => {
                await showError('Request failed: ' + (err && err.message ? err.message : 'Unknown error'));
            });
        });
        <?php endif; ?>

        // Profile preview
        const apImgInput = document.getElementById('ap_profile_image');
        if (apImgInput) {
            apImgInput.addEventListener('change', (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                const url = URL.createObjectURL(file);
                const img = document.getElementById('ap_profile_preview');
                if (img) img.src = url;
            });
        }

        // ----- User Management CRUD -----
        let userModal, roleModal;
        document.addEventListener('DOMContentLoaded', () => {
            const um = document.getElementById('userModal');
            if (um) userModal = new bootstrap.Modal(um);
            const rm = document.getElementById('roleModal');
            if (rm) roleModal = new bootstrap.Modal(rm);
            // New CRUD modals
            const stm = document.getElementById('studentModal'); if (stm) window._studentModal = new bootstrap.Modal(stm);
            const atm = document.getElementById('attendanceModal'); if (atm) window._attendanceModal = new bootstrap.Modal(atm);
            const scm = document.getElementById('scheduleModal'); if (scm) window._scheduleModal = new bootstrap.Modal(scm);
            const csm = document.getElementById('courseModal'); if (csm) window._courseModal = new bootstrap.Modal(csm);
            const sem = document.getElementById('sectionModal'); if (sem) window._sectionModal = new bootstrap.Modal(sem);
        });

        function refreshUsers() {
            fetch('admin_panel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_users'
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { alert('Failed to load users'); return; }
                const tbody = document.getElementById('users_table_body');
                if (!tbody) return;
                tbody.innerHTML = '';
                data.users.forEach(u => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${escapeHtml(u.username || '')}</td>
                        <td>${escapeHtml(u.email || '')}</td>
                        <td><span class="role-badge role-${u.role}">${escapeHtml((u.role || '').replace('_',' ').replace(/^./, c=>c.toUpperCase()))}</span></td>
                        <td>${escapeHtml(u.school_name || 'N/A')}</td>
                        <td>
                          <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-primary" title="Edit" onclick="openEditUser(${u.id})"><i class="fas fa-pen"></i></button>
                            ${u.can_delete === false ? '' : `<?php echo $is_super_admin ? '<button class=\'btn btn-outline-warning\' title=\'Role\' onclick=\'openRoleChange(__UID__, \'__ROLE__\')\'><i class=\'fas fa-user-shield\'></i></button><button class=\'btn btn-outline-danger\' title=\'Delete\' onclick=\'deleteUser(__UID__)\'><i class=\'fas fa-trash\'></i></button>' : ''; ?>`.replaceAll('__UID__', u.id).replaceAll('__ROLE__', u.role)}
                          </div>
                        </td>`;
                    tbody.appendChild(tr);
                });
            });
        }

        function openCreateUser() {
            const title = document.getElementById('userModalTitle');
            if (title) title.textContent = 'Add User';
            document.getElementById('user_id').value = '';
            document.getElementById('u_username').value = '';
            document.getElementById('u_email').value = '';
            const pwd = document.getElementById('u_password'); if (pwd) pwd.value = '';
            const hint = document.getElementById('pwd_hint'); if (hint) hint.textContent = '(required)';
            const roleSel = document.getElementById('u_role'); if (roleSel) roleSel.value = 'admin';
            const schoolSel = document.getElementById('u_school_id'); if (schoolSel) schoolSel.value = '';
            const emailInput = document.getElementById('u_email'); if (emailInput) emailInput.disabled = false;
            if (userModal) userModal.show();
            document.getElementById('userModalSave').onclick = saveUserCreateOrUpdate;
        }

        function openEditUser(userId) {
            fetch('admin_panel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_user&user_id=' + encodeURIComponent(userId)
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { alert(data.message || 'Failed to load user'); return; }
                const u = data.user;
                const title = document.getElementById('userModalTitle');
                if (title) title.textContent = 'Edit User';
                document.getElementById('user_id').value = u.id;
                document.getElementById('u_username').value = u.username || '';
                const emailInput = document.getElementById('u_email');
                emailInput.value = u.email || '';
                emailInput.disabled = true; // not editable for simplicity
                const pwd = document.getElementById('u_password'); if (pwd) pwd.value = '';
                const hint = document.getElementById('pwd_hint'); if (hint) hint.textContent = '(leave blank to keep)';
                const roleSel = document.getElementById('u_role'); if (roleSel) roleSel.value = u.role || 'admin';
                const schoolSel = document.getElementById('u_school_id'); if (schoolSel) schoolSel.value = u.school_id || '';
                if (userModal) userModal.show();
                document.getElementById('userModalSave').onclick = saveUserCreateOrUpdate;
            });
        }

        function saveUserCreateOrUpdate() {
            const id = document.getElementById('user_id').value;
            const username = document.getElementById('u_username').value.trim();
            const email = document.getElementById('u_email').value.trim();
            const password = document.getElementById('u_password').value;
            const role = document.getElementById('u_role') ? document.getElementById('u_role').value : 'admin';
            const school_id = document.getElementById('u_school_id') ? document.getElementById('u_school_id').value : '';
            if (!username || !email || (!id && !password)) { alert('Please fill in required fields'); return; }
            const fd = new URLSearchParams();
            if (id) {
                fd.append('action','update_user');
                fd.append('user_id', id);
                fd.append('username', username);
                if (password) fd.append('password', password);
                if (document.getElementById('u_role') && !document.getElementById('u_role').disabled) fd.append('role', role);
                if (document.getElementById('u_school_id')) fd.append('school_id', school_id);
            } else {
                fd.append('action','create_user');
                fd.append('username', username);
                fd.append('email', email);
                fd.append('password', password);
                if (document.getElementById('u_role') && !document.getElementById('u_role').disabled) fd.append('role', role);
                if (document.getElementById('u_school_id')) fd.append('school_id', school_id);
            }
            fetch('admin_panel.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: fd.toString() })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { alert('Error: ' + (data.message || 'Request failed')); return; }
                if (userModal) userModal.hide();
                refreshUsers();
            });
        }

        async function deleteUser(userId) {
            const confirmed = await showConfirm({
                title: 'Delete User',
                message: 'Are you sure you want to delete this user? This action cannot be undone.',
                confirmText: 'Yes, delete user'
            });
            if (!confirmed) return;
            const fd = new URLSearchParams();
            fd.append('action','delete_user');
            fd.append('user_id', userId);
            fetch('admin_panel.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: fd.toString() })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { alert('Error: ' + (data.message || 'Delete failed')); return; }
                showSuccess('User successfully deleted.');
                refreshUsers();
            });
        }

        function openRoleChange(userId, currentRole) {
            const input = document.getElementById('role_user_id');
            const select = document.getElementById('role_select');
            if (!input || !select) return;
            input.value = userId;
            select.value = currentRole;
            if (roleModal) roleModal.show();
        }
        const roleSaveBtn = document.getElementById('roleSaveBtn');
        if (roleSaveBtn) {
            roleSaveBtn.addEventListener('click', () => {
                const uid = document.getElementById('role_user_id').value;
                const newRole = document.getElementById('role_select').value;
                const fd = new URLSearchParams();
                fd.append('action','promote_user');
                fd.append('user_id', uid);
                fd.append('new_role', newRole);
                fetch('admin_panel.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: fd.toString() })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { alert('Error: ' + (data.message || 'Update failed')); return; }
                    if (roleModal) roleModal.hide();
                    refreshUsers();
                });
            });
        }

        function escapeHtml(str) {
            return String(str).replace(/[&<>"]+/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
        }

        // ---- School CRUD ----
        let schoolModal;
        document.addEventListener('DOMContentLoaded', () => {
            const sm = document.getElementById('schoolModal');
            if (sm) schoolModal = new bootstrap.Modal(sm);
        });

        function openEditSchool(id) {
            const fd = new URLSearchParams();
            fd.append('action','get_school');
            fd.append('school_id', id);
            fetch('admin_panel.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: fd.toString() })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { alert('Failed to load school'); return; }
                const s = data.school;
                document.getElementById('ed_school_id').value = s.id;
                document.getElementById('ed_school_name').value = s.name || '';
                document.getElementById('ed_school_code').value = s.code || '';
                document.getElementById('ed_theme_color').value = s.theme_color || '#098744';
                document.getElementById('ed_status').value = s.status || 'active';
                if (schoolModal) schoolModal.show();
            });
        }

        const schoolSaveBtn = document.getElementById('schoolModalSave');
        if (schoolSaveBtn) {
            schoolSaveBtn.addEventListener('click', () => {
                const id = document.getElementById('ed_school_id').value;
                const name = document.getElementById('ed_school_name').value.trim();
                const code = document.getElementById('ed_school_code').value.trim();
                const theme = document.getElementById('ed_theme_color').value;
                const status = document.getElementById('ed_status').value;
                if (!name || !code) { alert('Please fill in name and code'); return; }
                const fd = new URLSearchParams();
                fd.append('action','update_school');
                fd.append('school_id', id);
                fd.append('school_name', name);
                fd.append('school_code', code);
                fd.append('theme_color', theme);
                fd.append('status', status);
                fetch('admin_panel.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: fd.toString() })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { alert('Error: ' + (data.message || 'Update failed')); return; }
                    if (schoolModal) schoolModal.hide();
                    location.reload();
                });
            });
        }

        async function deleteSchool(id) {
            const confirmed = await showConfirm({
                title: 'Delete School',
                message: 'Are you sure you want to delete this school? If it has linked records, it will be marked inactive.',
                confirmText: 'Yes, delete school'
            });
            if (!confirmed) return;
            const fd = new URLSearchParams();
            fd.append('action','delete_school');
            fd.append('school_id', id);
            fetch('admin_panel.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: fd.toString() })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { alert('Error: ' + (data.message || 'Delete failed')); return; }
                showSuccess('School successfully deleted or marked inactive.');
                setTimeout(() => { location.reload(); }, 500);
            });
        }

        <?php if ($is_super_admin): ?>
        async function loadStudents() {
            const params = new URLSearchParams();
            params.append('action','get_students_scoped');
            const r = await fetch('admin_students_api.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params.toString() });
            const data = await r.json();
            const tbody = document.getElementById('students_table_body');
            if (!tbody) return;
            tbody.innerHTML = '';
            if (!data.success) { tbody.innerHTML = '<tr><td colspan="4">Failed to load students</td></tr>'; return; }
            data.students.forEach(s => {
                const tr = document.createElement('tr');
                const tdName = document.createElement('td'); tdName.textContent = s.student_name || '';
                const tdCourse = document.createElement('td'); tdCourse.textContent = s.course_section || '';
                const tdSchool = document.createElement('td'); tdSchool.textContent = s.school_name || '';
                const tdAct = document.createElement('td');
                const btnGrp = document.createElement('div'); btnGrp.className = 'btn-group btn-group-sm';
                const btnEdit = document.createElement('button'); btnEdit.className = 'btn btn-outline-primary'; btnEdit.innerHTML = '<i class="fas fa-pen"></i>';
                btnEdit.addEventListener('click', () => openStudentEdit(s.tbl_student_id, s.student_name || '', s.course_section || '', s.school_id || ''));
                const btnDel = document.createElement('button'); btnDel.className = 'btn btn-outline-danger'; btnDel.innerHTML = '<i class="fas fa-trash"></i>';
                btnDel.addEventListener('click', () => deleteStudent(s.tbl_student_id));
                btnGrp.appendChild(btnEdit); btnGrp.appendChild(btnDel); tdAct.appendChild(btnGrp);
                tr.appendChild(tdName); tr.appendChild(tdCourse); tr.appendChild(tdSchool); tr.appendChild(tdAct);
                tbody.appendChild(tr);
            });
        }
        document.getElementById('students_refresh')?.addEventListener('click', loadStudents);
        document.addEventListener('DOMContentLoaded', loadStudents);
        <?php endif; ?>

        <?php if ($is_super_admin): ?>
        // ---- Students CRUD ----
        function openStudentCreate() {
            document.getElementById('studentModalTitle').textContent = 'Add Student';
            document.getElementById('student_id').value = '';
            document.getElementById('student_name').value = '';
            document.getElementById('student_course_section').value = '';
            document.getElementById('student_school_id').value = '';
            _studentModal?.show();
            document.getElementById('studentModalSave').onclick = saveStudent;
        }
        function openStudentEdit(id, name, courseSection, schoolId) {
            document.getElementById('studentModalTitle').textContent = 'Edit Student';
            document.getElementById('student_id').value = id;
            document.getElementById('student_name').value = name || '';
            document.getElementById('student_course_section').value = courseSection || '';
            document.getElementById('student_school_id').value = schoolId || '';
            _studentModal?.show();
            document.getElementById('studentModalSave').onclick = saveStudent;
        }
        async function saveStudent() {
            const id = document.getElementById('student_id').value;
            const name = document.getElementById('student_name').value.trim();
            const courseSection = document.getElementById('student_course_section').value.trim();
            const schoolId = document.getElementById('student_school_id').value;
            if (!name || !courseSection || !schoolId) { await showError('Please fill all fields'); return; }
            const params = new URLSearchParams();
            params.append('op', id ? 'update' : 'create');
            if (id) params.append('tbl_student_id', id);
            params.append('student_name', name);
            params.append('course_section', courseSection);
            params.append('school_id', schoolId);
            const res = await fetch('admin_students_api.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params.toString() });
            const data = await res.json();
            if (!data.success) { await showError(data.message || 'Save failed'); return; }
            _studentModal?.hide();
            await showSuccess('Student saved successfully.');
            loadStudents();
        }
        async function deleteStudent(id) {
            const ok = await showConfirm({ title:'Delete Student', message:'Are you sure you want to delete this student?' });
            if (!ok) return;
            const params = new URLSearchParams(); params.append('op','delete'); params.append('tbl_student_id', id);
            const res = await fetch('admin_students_api.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params.toString() });
            const data = await res.json(); if (!data.success) { await showError(data.message || 'Delete failed'); return; }
            await showSuccess('Student deleted.'); loadStudents();
        }

        document.getElementById('student_add_btn')?.addEventListener('click', openStudentCreate);

        // ---- Attendance CRUD ----
        function openAttendanceCreate() {
            document.getElementById('attendanceModalTitle').textContent = 'Add Attendance';
            document.getElementById('attendance_id').value = '';
            document.getElementById('attendance_student_id').value = '';
            document.getElementById('attendance_time_in').value = '';
            document.getElementById('attendance_status').value = 'On Time';
            document.getElementById('attendance_school_id').value = '';
            _attendanceModal?.show();
            document.getElementById('attendanceModalSave').onclick = saveAttendance;
        }
        function openAttendanceEdit(id, studentId, timeIn, status, schoolId) {
            document.getElementById('attendanceModalTitle').textContent = 'Edit Attendance';
            document.getElementById('attendance_id').value = id;
            document.getElementById('attendance_student_id').value = studentId || '';
            document.getElementById('attendance_time_in').value = timeIn ? timeIn.replace(' ', 'T') : '';
            document.getElementById('attendance_status').value = status || 'On Time';
            document.getElementById('attendance_school_id').value = schoolId || '';
            _attendanceModal?.show();
            document.getElementById('attendanceModalSave').onclick = saveAttendance;
        }
        async function saveAttendance() {
            const id = document.getElementById('attendance_id').value;
            const studentId = document.getElementById('attendance_student_id').value;
            const timeIn = document.getElementById('attendance_time_in').value;
            const status = document.getElementById('attendance_status').value;
            const schoolId = document.getElementById('attendance_school_id').value;
            if (!studentId || !timeIn || !schoolId) { await showError('Please fill all fields'); return; }
            const params = new URLSearchParams(); params.append('op', id ? 'update' : 'create');
            if (id) params.append('id', id);
            params.append('tbl_student_id', studentId); params.append('time_in', timeIn.replace('T',' ')); params.append('status', status); params.append('school_id', schoolId);
            const res = await fetch('admin_attendance_api.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params.toString() });
            const data = await res.json(); if (!data.success) { await showError(data.message || 'Save failed'); return; }
            _attendanceModal?.hide(); await showSuccess('Attendance saved.'); loadAttendance();
        }
        async function deleteAttendance(id) {
            const ok = await showConfirm({ title:'Delete Attendance', message:'Are you sure you want to delete this entry?' }); if (!ok) return;
            const p = new URLSearchParams(); p.append('op','delete'); p.append('id', id);
            const res = await fetch('admin_attendance_api.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: p.toString() });
            const data = await res.json(); if (!data.success) { await showError(data.message || 'Delete failed'); return; }
            await showSuccess('Attendance deleted.'); loadAttendance();
        }
        document.getElementById('attendance_add_btn')?.addEventListener('click', openAttendanceCreate);

        // ---- Schedules CRUD ----
        function openScheduleCreate() {
            document.getElementById('scheduleModalTitle').textContent = 'Add Schedule';
            document.getElementById('schedule_id').value=''; document.getElementById('schedule_subject').value=''; document.getElementById('schedule_teacher').value=''; document.getElementById('schedule_section').value=''; document.getElementById('schedule_day').value='Monday'; document.getElementById('schedule_start').value=''; document.getElementById('schedule_end').value=''; document.getElementById('schedule_room').value=''; document.getElementById('schedule_school_id').value='';
            _scheduleModal?.show(); document.getElementById('scheduleModalSave').onclick = saveSchedule;
        }
        function openScheduleEdit(id) {
            // Minimal editor: ask user to retype fields for now (enhance later by fetching one row if needed)
            document.getElementById('schedule_id').value=id; _scheduleModal?.show(); document.getElementById('scheduleModalSave').onclick = saveSchedule; document.getElementById('scheduleModalTitle').textContent = 'Edit Schedule';
        }
        async function saveSchedule() {
            const id=document.getElementById('schedule_id').value; const subject=document.getElementById('schedule_subject').value.trim(); const teacher=document.getElementById('schedule_teacher').value.trim(); const section=document.getElementById('schedule_section').value.trim(); const day=document.getElementById('schedule_day').value; const start=document.getElementById('schedule_start').value; const end=document.getElementById('schedule_end').value; const room=document.getElementById('schedule_room').value.trim(); const schoolId=document.getElementById('schedule_school_id').value;
            if (!subject || !section || !schoolId) { await showError('Please fill required fields'); return; }
            const p=new URLSearchParams(); p.append('op', id ? 'update':'create'); if (id) p.append('id', id); p.append('subject', subject); p.append('teacher_username', teacher); p.append('section', section); p.append('day_of_week', day); p.append('start_time', start); p.append('end_time', end); p.append('room', room); p.append('school_id', schoolId);
            const res = await fetch('admin_schedules_api.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString() }); const data = await res.json(); if (!data.success) { await showError(data.message || 'Save failed'); return; }
            _scheduleModal?.hide(); await showSuccess('Schedule saved.'); loadSchedules();
        }
        async function deleteSchedule(id) { const ok = await showConfirm({ title:'Delete Schedule', message:'Proceed to delete schedule?' }); if (!ok) return; const p=new URLSearchParams(); p.append('op','delete'); p.append('id', id); const res=await fetch('admin_schedules_api.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString()}); const data=await res.json(); if(!data.success){ await showError(data.message||'Delete failed'); return;} await showSuccess('Schedule deleted.'); loadSchedules(); }
        document.getElementById('schedule_add_btn')?.addEventListener('click', openScheduleCreate);

        // ---- Courses CRUD ----
        function populateCourseSelectForSections(courses) {
            const sel = document.getElementById('section_course_id'); if (!sel) return; sel.innerHTML='';
            courses.forEach(c => { const opt=document.createElement('option'); opt.value=c.course_id; opt.textContent=c.course_name; sel.appendChild(opt); });
        }
        function openCourseCreate() { document.getElementById('courseModalTitle').textContent='Add Course'; document.getElementById('course_id').value=''; document.getElementById('course_name').value=''; document.getElementById('course_school_id').value=''; _courseModal?.show(); document.getElementById('courseModalSave').onclick = saveCourse; }
        function openCourseEdit(id, name, schoolId) { document.getElementById('courseModalTitle').textContent='Edit Course'; document.getElementById('course_id').value=id; document.getElementById('course_name').value=name||''; document.getElementById('course_school_id').value=schoolId||''; _courseModal?.show(); document.getElementById('courseModalSave').onclick = saveCourse; }
        async function saveCourse() { const id=document.getElementById('course_id').value; const name=document.getElementById('course_name').value.trim(); const schoolId=document.getElementById('course_school_id').value; if(!name||!schoolId){ await showError('Please fill all fields'); return;} const p=new URLSearchParams(); p.append('entity','course'); p.append('op', id?'update':'create'); if(id)p.append('course_id', id); p.append('course_name', name); p.append('school_id', schoolId); const res=await fetch('admin_courses_api.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString()}); const data=await res.json(); if(!data.success){ await showError(data.message||'Save failed'); return;} _courseModal?.hide(); await showSuccess('Course saved.'); loadCoursesSections(); }
        async function deleteCourse(id) { const ok=await showConfirm({title:'Delete Course', message:'Delete this course?' }); if(!ok) return; const p=new URLSearchParams(); p.append('entity','course'); p.append('op','delete'); p.append('course_id', id); const res=await fetch('admin_courses_api.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString()}); const data=await res.json(); if(!data.success){ await showError(data.message||'Delete failed'); return;} await showSuccess('Course deleted.'); loadCoursesSections(); }
        document.getElementById('course_add_btn')?.addEventListener('click', openCourseCreate);

        // ---- Sections CRUD ----
        function openSectionCreate() { document.getElementById('sectionModalTitle').textContent='Add Section'; document.getElementById('section_id').value=''; document.getElementById('section_name').value=''; document.getElementById('section_course_id').value=''; document.getElementById('section_school_id').value=''; _sectionModal?.show(); document.getElementById('sectionModalSave').onclick = saveSection; }
        function openSectionEdit(id, name, courseId, schoolId) { document.getElementById('sectionModalTitle').textContent='Edit Section'; document.getElementById('section_id').value=id; document.getElementById('section_name').value=name||''; document.getElementById('section_course_id').value=courseId||''; document.getElementById('section_school_id').value=schoolId||''; _sectionModal?.show(); document.getElementById('sectionModalSave').onclick = saveSection; }
        async function saveSection() { const id=document.getElementById('section_id').value; const name=document.getElementById('section_name').value.trim(); const courseId=document.getElementById('section_course_id').value; const schoolId=document.getElementById('section_school_id').value; if(!name||!courseId||!schoolId){ await showError('Please fill all fields'); return;} const p=new URLSearchParams(); p.append('entity','section'); p.append('op', id?'update':'create'); if(id)p.append('section_id', id); p.append('section_name', name); p.append('course_id', courseId); p.append('school_id', schoolId); const res=await fetch('admin_courses_api.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString()}); const data=await res.json(); if(!data.success){ await showError(data.message||'Save failed'); return;} _sectionModal?.hide(); await showSuccess('Section saved.'); loadCoursesSections(); }
        async function deleteSection(id) { const ok=await showConfirm({title:'Delete Section', message:'Delete this section?' }); if(!ok) return; const p=new URLSearchParams(); p.append('entity','section'); p.append('op','delete'); p.append('section_id', id); const res=await fetch('admin_courses_api.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString()}); const data=await res.json(); if(!data.success){ await showError(data.message||'Delete failed'); return;} await showSuccess('Section deleted.'); loadCoursesSections(); }
        document.getElementById('section_add_btn')?.addEventListener('click', openSectionCreate);
        async function loadAttendance() {
            const r = await fetch('admin_attendance_api.php', { method:'POST' });
            const data = await r.json();
            const tbody = document.getElementById('attendance_table_body');
            if (!tbody) return;
            tbody.innerHTML='';
            if (!data.success) { tbody.innerHTML = '<tr><td colspan="6">Failed to load</td></tr>'; return; }
            data.attendance.forEach(a => {
                const tr = document.createElement('tr');
                const c1 = document.createElement('td'); c1.textContent = a.time_in || '';
                const c2 = document.createElement('td'); c2.textContent = a.status || '';
                const c3 = document.createElement('td'); c3.textContent = a.student_name || '';
                const c4 = document.createElement('td'); c4.textContent = a.course_section || '';
                const c5 = document.createElement('td'); c5.textContent = a.school_name || '';
                const c6 = document.createElement('td');
                const grp = document.createElement('div'); grp.className = 'btn-group btn-group-sm';
                const eBtn = document.createElement('button'); eBtn.className = 'btn btn-outline-primary'; eBtn.innerHTML = '<i class="fas fa-pen"></i>';
                eBtn.addEventListener('click', () => openAttendanceEdit(a.id, a.student_id || 0, a.time_in || '', a.status || '', a.school_id || 0));
                const dBtn = document.createElement('button'); dBtn.className = 'btn btn-outline-danger'; dBtn.innerHTML = '<i class="fas fa-trash"></i>';
                dBtn.addEventListener('click', () => deleteAttendance(a.id));
                grp.appendChild(eBtn); grp.appendChild(dBtn); c6.appendChild(grp);
                tr.appendChild(c1); tr.appendChild(c2); tr.appendChild(c3); tr.appendChild(c4); tr.appendChild(c5); tr.appendChild(c6);
                tbody.appendChild(tr);
            });
        }
        async function loadSchedules() {
            const r = await fetch('admin_schedules_api.php', { method:'POST' });
            const data = await r.json();
            const tbody = document.getElementById('schedules_table_body');
            if (!tbody) return;
            tbody.innerHTML='';
            if (!data.success) { tbody.innerHTML = '<tr><td colspan="8">Failed to load</td></tr>'; return; }
            data.schedules.forEach(s => {
                const tr = document.createElement('tr');
                const tds = [s.subject||'', s.teacher_username||'', s.section||'', s.day_of_week||'', s.start_time||'', s.end_time||'', s.room||''];
                tds.forEach(v => { const td=document.createElement('td'); td.textContent=v; tr.appendChild(td); });
                const act = document.createElement('td'); const grp=document.createElement('div'); grp.className='btn-group btn-group-sm';
                const e=document.createElement('button'); e.className='btn btn-outline-primary'; e.innerHTML='<i class="fas fa-pen"></i>'; e.addEventListener('click',()=>openScheduleEdit(s.id));
                const d=document.createElement('button'); d.className='btn btn-outline-danger'; d.innerHTML='<i class="fas fa-trash"></i>'; d.addEventListener('click',()=>deleteSchedule(s.id));
                grp.appendChild(e); grp.appendChild(d); act.appendChild(grp); tr.appendChild(act);
                tbody.appendChild(tr);
            });
        }
        async function loadCoursesSections() {
            const r = await fetch('admin_courses_api.php', { method:'POST' });
            const data = await r.json();
            const tbody = document.getElementById('courses_hier_tbody');
            if (!tbody) return;
            tbody.innerHTML = '';
            if (!data.success) { tbody.innerHTML = '<tr><td colspan="4">Failed to load</td></tr>'; return; }

            // Build map courseId -> sections[] for quick grouping
            const sectionsByCourse = {};
            (data.sections||[]).forEach(sec => {
                if (!sectionsByCourse[sec.course_id]) sectionsByCourse[sec.course_id] = [];
                sectionsByCourse[sec.course_id].push(sec);
            });

            (data.courses||[]).forEach(c => {
                const courseRow = document.createElement('tr');
                courseRow.className = 'course-row';
                const toggleTd = document.createElement('td');
                const toggleBtn = document.createElement('button'); toggleBtn.className='btn btn-link p-0 toggle-btn'; toggleBtn.innerHTML='<i class="fas fa-chevron-right"></i>';
                toggleTd.appendChild(toggleBtn);
                const nameTd = document.createElement('td'); nameTd.textContent = c.course_name || '';
                const schoolTd = document.createElement('td');
                const schoolSpan = document.createElement('span'); schoolSpan.textContent = c.school_name || ''; schoolSpan.className='school-name-colored';
                if (c.school_theme_color) { schoolSpan.style.color = c.school_theme_color; }
                schoolTd.appendChild(schoolSpan);
                const actTd = document.createElement('td'); actTd.className='muted-actions';
                const grp=document.createElement('div'); grp.className='btn-group btn-group-sm';
                const editBtn=document.createElement('button'); editBtn.className='btn btn-outline-primary'; editBtn.innerHTML='<i class="fas fa-pen"></i>'; editBtn.addEventListener('click',()=>openCourseEdit(c.course_id, c.course_name||'', c.school_id||0));
                const delBtn=document.createElement('button'); delBtn.className='btn btn-outline-danger'; delBtn.innerHTML='<i class="fas fa-trash"></i>'; delBtn.addEventListener('click',()=>deleteCourse(c.course_id));
                grp.appendChild(editBtn); grp.appendChild(delBtn); actTd.appendChild(grp);
                courseRow.appendChild(toggleTd); courseRow.appendChild(nameTd); courseRow.appendChild(schoolTd); courseRow.appendChild(actTd);
                tbody.appendChild(courseRow);

                const secs = sectionsByCourse[c.course_id] || [];
                secs.forEach(s => {
                    const secRow = document.createElement('tr'); secRow.className='section-row'; secRow.style.display='none';
                    const iconTd = document.createElement('td'); iconTd.innerHTML='';
                    const nmTd = document.createElement('td'); nmTd.className='section-indent'; nmTd.textContent = s.section_name || '';
                    const schTd = document.createElement('td'); const inherSpan=document.createElement('span'); inherSpan.textContent = c.school_name || ''; inherSpan.className='school-name-colored'; if (c.school_theme_color) inherSpan.style.color = c.school_theme_color; schTd.appendChild(inherSpan);
                    const actS = document.createElement('td'); actS.className='muted-actions'; const g=document.createElement('div'); g.className='btn-group btn-group-sm';
                    const e=document.createElement('button'); e.className='btn btn-outline-primary'; e.innerHTML='<i class="fas fa-pen"></i>'; e.addEventListener('click',()=>openSectionEdit(s.section_id, s.section_name||'', s.course_id||0, s.school_id||0));
                    const d=document.createElement('button'); d.className='btn btn-outline-danger'; d.innerHTML='<i class="fas fa-trash"></i>'; d.addEventListener('click',()=>deleteSection(s.section_id));
                    g.appendChild(e); g.appendChild(d); actS.appendChild(g);
                    secRow.appendChild(iconTd); secRow.appendChild(nmTd); secRow.appendChild(schTd); secRow.appendChild(actS);
                    tbody.appendChild(secRow);

                    // Link toggle behavior
                    toggleBtn.addEventListener('click', () => {
                        const icon = toggleBtn.querySelector('i');
                        const isHidden = secRow.style.display === 'none';
                        document.querySelectorAll('#courses_hier_tbody tr.section-row').forEach(r => { if (r.previousSibling === courseRow || r.dataset.parent === String(c.course_id)) { /* placeholder */ } });
                        const related = [];
                        let cursor = courseRow.nextSibling;
                        while (cursor && cursor.classList && cursor.classList.contains('section-row')) { related.push(cursor); cursor = cursor.nextSibling; }
                        related.forEach(r => { r.style.display = isHidden ? '' : 'none'; });
                        if (icon) icon.className = isHidden ? 'fas fa-chevron-down' : 'fas fa-chevron-right';
                    }, { once: false });
                });
            });

            // Expand first course by default for hint
            const firstToggle = document.querySelector('#courses_hier_tbody .toggle-btn');
            if (firstToggle) firstToggle.click();
        }
        document.getElementById('attendance_refresh')?.addEventListener('click', loadAttendance);
        document.getElementById('schedules_refresh')?.addEventListener('click', loadSchedules);
        document.getElementById('courses_refresh')?.addEventListener('click', () => { loadCoursesSections(); });
        // Keep section course select in sync with courses list
        document.getElementById('courses_refresh')?.addEventListener('click', () => {
            // After refresh, repopulate section course select
            fetch('admin_courses_api.php', { method:'POST' })
                .then(r=>r.json())
                .then(d=>{ if (d && d.success) populateCourseSelectForSections(d.courses||[]); });
        });
        // Initial population for section course select
        fetch('admin_courses_api.php', { method:'POST' })
            .then(r=>r.json())
            .then(d=>{ if (d && d.success) populateCourseSelectForSections(d.courses||[]); });
        document.addEventListener('DOMContentLoaded', () => { loadAttendance(); loadSchedules(); loadCoursesSections(); });
        <?php endif; ?>
        
        // Backup & Restore functionality
        document.addEventListener('DOMContentLoaded', () => {
            // Create backup
            document.getElementById('create_backup_btn')?.addEventListener('click', async function() {
                const database = document.getElementById('backup_database').value;
                const btn = this;
                const progress = document.getElementById('backup_progress');
                const result = document.getElementById('backup_result');
                
                btn.disabled = true;
                progress.style.display = 'block';
                result.innerHTML = '';
                
                try {
                    const response = await fetch('backup-restore.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=backup&database=${database}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        result.innerHTML = `<div class="alert alert-success">Backup created successfully: ${data.filename}</div>`;
                        window.loadBackupFiles();
                    } else {
                        result.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                    }
                } catch (error) {
                    result.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
                } finally {
                    btn.disabled = false;
                    progress.style.display = 'none';
                }
            });
            
            // Restore backup
            document.getElementById('restore_backup_btn')?.addEventListener('click', async function() {
                const file = document.getElementById('restore_file').files[0];
                const database = document.getElementById('restore_database').value;
                
                if (!file) {
                    await showError('Please select a backup file');
                    return;
                }
                
                const confirmed = await showConfirm({
                    title: 'Restore Backup',
                    message: 'Are you sure you want to restore this backup? This will overwrite existing data.',
                    confirmText: 'Yes, restore backup'
                });
                
                if (!confirmed) return;
                
                const btn = this;
                const progress = document.getElementById('restore_progress');
                const result = document.getElementById('restore_result');
                
                btn.disabled = true;
                progress.style.display = 'block';
                result.innerHTML = '';
                
                const formData = new FormData();
                formData.append('action', 'restore');
                formData.append('database', database);
                formData.append('backup_file', file);
                
                try {
                    const response = await fetch('backup-restore.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        result.innerHTML = `<div class="alert alert-success">Backup restored successfully</div>`;
                        await showSuccess('Backup restored successfully');
                    } else {
                        result.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                    }
                } catch (error) {
                    result.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
                } finally {
                    btn.disabled = false;
                    progress.style.display = 'none';
                }
            });
            
            // Load backup files list
            window.loadBackupFiles = async function() {
                try {
                    const response = await fetch('backup-restore.php?action=list');
                    const data = await response.json();
                    const tbody = document.getElementById('backup_files_list');
                    
                    if (data.success && data.files) {
                        tbody.innerHTML = '';
                        data.files.forEach(file => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${file.name}</td>
                                <td>${file.database}</td>
                                <td>${file.date}</td>
                                <td>${file.size}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="backup-restore.php?action=download&file=${file.name}" class="btn btn-outline-primary">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                        <button class="btn btn-outline-danger" onclick="deleteBackupFile('${file.name}')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No backup files found</td></tr>';
                    }
                } catch (error) {
                    document.getElementById('backup_files_list').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading backup files</td></tr>';
                }
            }
            
            // Load backup files on page load
            if (document.getElementById('backup_files_list')) {
                window.loadBackupFiles();
            }
        });
        
        // Delete Data functionality
        document.addEventListener('DOMContentLoaded', () => {
            // Delete students
            document.getElementById('delete_students_btn')?.addEventListener('click', async function() {
                const confirmed = await showConfirm({
                    title: 'Delete All Students',
                    message: 'Are you sure you want to delete ALL student data? This action cannot be undone.',
                    confirmText: 'Yes, delete all students'
                });
                
                if (!confirmed) return;
                
                await executeDeleteOperation('students', this);
            });
            
            // Delete attendance
            document.getElementById('delete_attendance_btn')?.addEventListener('click', async function() {
                const confirmed = await showConfirm({
                    title: 'Delete All Attendance',
                    message: 'Are you sure you want to delete ALL attendance data? This action cannot be undone.',
                    confirmText: 'Yes, delete all attendance'
                });
                
                if (!confirmed) return;
                
                await executeDeleteOperation('attendance', this);
            });
            
            // Delete schedules
            document.getElementById('delete_schedules_btn')?.addEventListener('click', async function() {
                const confirmed = await showConfirm({
                    title: 'Delete All Schedules',
                    message: 'Are you sure you want to delete ALL schedule data? This action cannot be undone.',
                    confirmText: 'Yes, delete all schedules'
                });
                
                if (!confirmed) return;
                
                await executeDeleteOperation('schedules', this);
            });
            
            // Delete all data
            document.getElementById('delete_all_data_btn')?.addEventListener('click', async function() {
                const confirmed = await showConfirm({
                    title: 'DELETE EVERYTHING',
                    message: 'Are you sure you want to delete ALL DATA from the system? This action cannot be undone and will destroy everything.',
                    confirmText: 'YES, DELETE EVERYTHING'
                });
                
                if (!confirmed) return;
                
                // Double confirmation for delete all
                const doubleConfirmed = await showConfirm({
                    title: 'FINAL WARNING',
                    message: 'This is your final warning. This will permanently delete ALL data. Are you absolutely sure?',
                    confirmText: 'YES, I UNDERSTAND'
                });
                
                if (!doubleConfirmed) return;
                
                await executeDeleteOperation('all', this);
            });
            
            async function executeDeleteOperation(type, button) {
                const result = document.getElementById('delete_result');
                
                button.disabled = true;
                result.innerHTML = '<div class="alert alert-info">Processing deletion...</div>';
                
                try {
                    const response = await fetch('delete_data.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=delete&type=${type}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        result.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        await showSuccess(data.message);
                        
                        // Refresh relevant sections
                        if (typeof loadStudents === 'function') loadStudents();
                        if (typeof loadAttendance === 'function') loadAttendance();
                        if (typeof loadSchedules === 'function') loadSchedules();
                    } else {
                        result.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                    }
                } catch (error) {
                    result.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
                } finally {
                    button.disabled = false;
                }
            }
        });
        
        // Global function for deleting backup files
        async function deleteBackupFile(filename) {
            const confirmed = await showConfirm({
                title: 'Delete Backup File',
                message: `Are you sure you want to delete ${filename}?`,
                confirmText: 'Yes, delete file'
            });
            
            if (!confirmed) return;
            
            try {
                const response = await fetch('backup-restore.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&filename=${filename}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    await showSuccess('Backup file deleted successfully');
                    // Reload backup files list
                    window.loadBackupFiles();
                } else {
                    await showError(data.message || 'Failed to delete backup file');
                }
            } catch (error) {
                await showError('Error deleting backup file: ' + error.message);
            }
        }
    </script>
</body>
</html>