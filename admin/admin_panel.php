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
    <style>
        :root {
            --primary-color: <?php echo $user_school['theme_color'] ?? '#098744'; ?>;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color), #0a5c2e);
            color: white;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .sidebar-subtitle {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 12px;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            margin-left: auto;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: white;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background: none;
            border-bottom: 1px solid #eee;
            padding: 20px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: #0a5c2e;
            border-color: #0a5c2e;
        }
        
        .badge {
            font-size: 12px;
            padding: 4px 8px;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #666;
        }
        
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-admin {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .role-super_admin {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .passkey-display {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 15px 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">Admin Panel</div>
            <div class="sidebar-subtitle">
                <?php echo $is_super_admin ? 'Super Admin' : 'Admin'; ?> • 
                <?php echo $user_school['name'] ?? 'All Schools'; ?>
            </div>
        </div>
        
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="#" class="nav-link active" onclick="showSection('dashboard')">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="showSection('users')">
                    <i class="fas fa-users"></i>
                    User Management
                </a>
            </div>
            <?php if ($is_super_admin): ?>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="showSection('schools')">
                    <i class="fas fa-school"></i>
                    School Management
                </a>
            </div>
            <?php endif; ?>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="showSection('schedules')">
                    <i class="fas fa-calendar-alt"></i>
                    Schedule Generator
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="showSection('themes')">
                    <i class="fas fa-palette"></i>
                    Theme Management
                </a>
            </div>
            <?php if ($is_super_admin): ?>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="showSection('passkeys')">
                    <i class="fas fa-key"></i>
                    Passkey Generator
                </a>
            </div>
            <?php endif; ?>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="showSection('logs')">
                    <i class="fas fa-list-alt"></i>
                    System Logs
                </a>
            </div>
            <div class="nav-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <a href="../dashboard.php" class="nav-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="../logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>Admin Panel</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    <div style="font-size: 14px; color: #666;">
                        <?php echo $is_super_admin ? 'Super Administrator' : 'Administrator'; ?>
                    </div>
                </div>
            </div>
        </div>

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
                    <?php if ($is_super_admin): ?>
                    <button class="btn btn-primary btn-sm" onclick="refreshUsers()">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                    <?php endif; ?>
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
                                    <?php if ($is_super_admin): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
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
                                    <?php if ($is_super_admin): ?>
                                    <td>
                                        <select class="form-select form-select-sm" onchange="changeUserRole(<?php echo $user['id']; ?>, this.value)">
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                        </select>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Theme</th>
                                    <th>Status</th>
                                    <th>Created</th>
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
                                        <span class="badge bg-success"><?php echo ucfirst($school['status']); ?></span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($school['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Schedule Generator Section -->
        <div id="schedules" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h5>Schedule Generator</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Schedule generation feature coming soon...</p>
                </div>
            </div>
        </div>

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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked nav link
            event.target.classList.add('active');
        }

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
    </script>
</body>
</html>