<?php
/**
 * Role-Based System Setup Script
 * Run this file once to set up the complete role-based system
 */

// Include database connections
require_once 'conn/conn.php';
require_once 'admin/database.php';

// Function to execute SQL file
function executeSQLFile($conn, $filename) {
    if (!file_exists($filename)) {
        return ['success' => false, 'message' => "SQL file not found: $filename"];
    }
    
    $sql = file_get_contents($filename);
    $statements = explode(';', $sql);
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            if (mysqli_query($conn, $statement)) {
                $executed++;
            } else {
                $error = mysqli_error($conn);
                if (!empty($error)) {
                    $errors[] = $error;
                }
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'executed' => $executed,
        'errors' => $errors
    ];
}

// Check if setup has already been run
function isSetupComplete($conn) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
    return mysqli_num_rows($result) > 0;
}

$setup_complete = false;
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {
    // Run the setup
    $result = executeSQLFile($conn, 'db_setup/role_based_system_schema.sql');
    
    if ($result['success']) {
        $messages[] = [
            'type' => 'success',
            'text' => "Setup completed successfully! Executed {$result['executed']} SQL statements."
        ];
        $setup_complete = true;
    } else {
        $messages[] = [
            'type' => 'danger',
            'text' => "Setup encountered errors: " . implode(', ', $result['errors'])
        ];
    }
} else {
    // Check if already set up
    $setup_complete = isSetupComplete($conn);
    if ($setup_complete) {
        $messages[] = [
            'type' => 'info',
            'text' => 'Role-based system is already set up!'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role-Based System Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #098744 0%, #0a5c2e 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .setup-header {
            background: linear-gradient(135deg, #098744, #0a5c2e);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .setup-body {
            padding: 40px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list i {
            color: #098744;
            margin-right: 15px;
            width: 20px;
        }
        
        .btn-setup {
            background: linear-gradient(135deg, #098744, #0a5c2e);
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-setup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(9, 135, 68, 0.3);
            color: white;
        }
        
        .btn-setup:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .credentials-box {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .credential-item:last-child {
            border-bottom: none;
        }
        
        .credential-value {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1><i class="fas fa-cogs"></i> Role-Based System Setup</h1>
            <p class="mb-0">Set up the complete role-based login and management system</p>
        </div>
        
        <div class="setup-body">
            <?php foreach ($messages as $message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>" role="alert">
                <?php echo $message['text']; ?>
            </div>
            <?php endforeach; ?>
            
            <h3>System Features</h3>
            <p>This setup will configure the following features:</p>
            
            <ul class="feature-list">
                <li><i class="fas fa-users"></i> Role-based user management (Admin & Super Admin)</li>
                <li><i class="fas fa-school"></i> Multi-school support with theme customization</li>
                <li><i class="fas fa-sign-in-alt"></i> Enhanced login with school selection and profile filtering</li>
                <li><i class="fas fa-calendar-alt"></i> Automatic schedule generation with conflict detection</li>
                <li><i class="fas fa-key"></i> Secure passkey system for theme changes</li>
                <li><i class="fas fa-tachometer-alt"></i> Comprehensive admin panel with role-based access</li>
                <li><i class="fas fa-list-alt"></i> System activity logging and monitoring</li>
                <li><i class="fas fa-database"></i> Database schema updates and sample data</li>
            </ul>
            
            <?php if ($setup_complete): ?>
            <div class="credentials-box">
                <h5><i class="fas fa-user-shield"></i> Default Super Admin Credentials</h5>
                <p class="text-muted">Use these credentials to access the admin panel:</p>
                
                <div class="credential-item">
                    <span><strong>Username:</strong></span>
                    <span class="credential-value">superadmin</span>
                </div>
                <div class="credential-item">
                    <span><strong>Email:</strong></span>
                    <span class="credential-value">admin@system.local</span>
                </div>
                <div class="credential-item">
                    <span><strong>Password:</strong></span>
                    <span class="credential-value">admin123</span>
                </div>
            </div>
            
            <div class="text-center">
                <a href="admin/login.php" class="btn btn-setup">
                    <i class="fas fa-sign-in-alt"></i> Go to New Login System
                </a>
                <a href="admin/admin_panel.php" class="btn btn-outline-primary ms-3">
                    <i class="fas fa-tachometer-alt"></i> Access Admin Panel
                </a>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Important:</strong> This will modify your database structure. Make sure to backup your database before proceeding.
            </div>
            
            <form method="post" class="text-center">
                <button type="submit" name="run_setup" class="btn btn-setup">
                    <i class="fas fa-play"></i> Run Setup Now
                </button>
            </form>
            <?php endif; ?>
            
            <hr class="my-4">
            
            <h5>What happens during setup:</h5>
            <ol>
                <li>Updates the <code>users</code> table with role and school_id columns</li>
                <li>Creates <code>schools</code> table with initial entries (SPCPC, Computer Site Inc.)</li>
                <li>Creates <code>theme_passkeys</code> table for secure theme management</li>
                <li>Creates <code>schedules</code> and <code>rooms</code> tables for schedule generation</li>
                <li>Creates <code>system_logs</code> table for activity tracking</li>
                <li>Adds sample rooms for each school</li>
                <li>Creates a default Super Admin account</li>
                <li>Updates existing tables with foreign key relationships</li>
            </ol>
            
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>