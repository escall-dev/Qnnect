<?php
require_once '../includes/session_config.php';
require_once '../includes/auth_functions.php';
require_once 'database.php';

// Require login
requireLogin();

// Get user's role and school info
$user_role = $_SESSION['role'] ?? 'admin';
$user_school_id = $_SESSION['school_id'] ?? null;
$is_super_admin = hasRole('super_admin');

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_users':
            $users = getFilteredUsers($conn, $user_school_id);
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
            
            $sql = "INSERT INTO schools (name, code, theme_color) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $name, $code, $theme);
            
            if (mysqli_stmt_execute($stmt)) {
                logActivity($conn, 'SCHOOL_ADDED', "Name: {$name}, Code: {$code}");
                echo json_encode(['success' => true, 'message' => 'School added successfully']);
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
    }
}

// Get data for display
$user_school = getUserSchool($conn);
$all_schools = $is_super_admin ? getAllSchools($conn) : [];
$users = getFilteredUsers($conn, $user_school_id);

// Get system logs
$logs_sql = $is_super_admin 
    ? "SELECT sl.*, u.username, s.name as school_name FROM system_logs sl 
       LEFT JOIN users u ON sl.user_id = u.id 
       LEFT JOIN schools s ON sl.school_id = s.id 
       ORDER BY sl.created_at DESC LIMIT 50"
    : "SELECT sl.*, u.username, s.name as school_name FROM system_logs sl 
       LEFT JOIN users u ON sl.user_id = u.id 
       LEFT JOIN schools s ON sl.school_id = s.id 
       WHERE sl.school_id = ? 
       ORDER BY sl.created_at DESC LIMIT 50";

if ($is_super_admin) {
    $logs_result = mysqli_query($conn, $logs_sql);
} else {
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

    <div class="settings-outer-container">
        <div class="settings-container">
            <div class="settings-title">
                <h2><i class="fas fa-cog"></i> Admin Controls</h2>
                <div class="ms-auto d-none d-md-flex" style="gap:8px;">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Add School Form
        document.getElementById('add_school_form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'add_school');
            formData.append('school_name', document.getElementById('school_name').value);
            formData.append('school_code', document.getElementById('school_code').value);
            formData.append('theme_color', document.getElementById('school_theme').value);
            
            fetch('admin_panel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

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
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
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
            .then(data => {
                if (data.success) {
                    document.getElementById('passkey_display').textContent = data.passkey;
                    document.getElementById('passkey_result').style.display = 'block';
                } else {
                    alert('Error: ' + data.message);
                }
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

        function deleteUser(userId) {
            if (!confirm('Delete this user? This cannot be undone.')) return;
            const fd = new URLSearchParams();
            fd.append('action','delete_user');
            fd.append('user_id', userId);
            fetch('admin_panel.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: fd.toString() })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { alert('Error: ' + (data.message || 'Delete failed')); return; }
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

        function deleteSchool(id) {
            if (!confirm('Delete this school? If it has linked records, it will be marked inactive.')) return;
            const fd = new URLSearchParams();
            fd.append('action','delete_school');
            fd.append('school_id', id);
            fetch('admin_panel.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: fd.toString() })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { alert('Error: ' + (data.message || 'Delete failed')); return; }
                location.reload();
            });
        }
    </script>
</body>
</html>