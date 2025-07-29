<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role-Based System - Navigation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #098744 0%, #0a5c2e 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .container {
            padding-top: 50px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, #098744, #0a5c2e);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, #098744, #0a5c2e);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(9, 135, 68, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .btn-outline-custom {
            border: 2px solid #098744;
            color: #098744;
            background: transparent;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-custom:hover {
            background: #098744;
            color: white;
            text-decoration: none;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            color: white;
            text-align: center;
        }
        
        .feature-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .main-title {
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .status-ready {
            background: #28a745;
            color: white;
        }
        
        .status-setup {
            background: #ffc107;
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-title">
            <h1><i class="fas fa-shield-alt"></i> Role-Based Login & Management System</h1>
            <p class="lead">Secure, multi-school access control for educational institutions</p>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-rocket"></i> Quick Actions</h3>
            </div>
            <div class="card-body text-center">
                <a href="setup_role_system.php" class="btn-custom">
                    <i class="fas fa-cogs"></i> Setup System
                </a>
                <a href="test_role_system.php" class="btn-outline-custom">
                    <i class="fas fa-check-circle"></i> Test System
                </a>
                <a href="admin/login.php" class="btn-custom">
                    <i class="fas fa-sign-in-alt"></i> New Login
                </a>
                <a href="admin/registration.php" class="btn-outline-custom">
                    <i class="fas fa-user-plus"></i> Register
                </a>
                <a href="admin/admin_panel.php" class="btn-custom">
                    <i class="fas fa-tachometer-alt"></i> Admin Panel
                </a>
            </div>
        </div>

        <!-- System Components -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-puzzle-piece"></i> System Components</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>🔐 Authentication System</h5>
                        <ul>
                            <li><a href="admin/login.php">3-Step Login Process</a> <span class="status-badge status-ready">Ready</span></li>
                            <li><a href="admin/registration.php">School-Based Registration</a> <span class="status-badge status-ready">Ready</span></li>
                            <li>Role-Based Access Control <span class="status-badge status-ready">Ready</span></li>
                        </ul>
                        
                        <h5>🏫 School Management</h5>
                        <ul>
                            <li>Multi-School Support <span class="status-badge status-ready">Ready</span></li>
                            <li>Theme Customization <span class="status-badge status-ready">Ready</span></li>
                            <li>Secure Passkey System <span class="status-badge status-ready">Ready</span></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>📊 Management Tools</h5>
                        <ul>
                            <li><a href="admin/admin_panel.php">Comprehensive Admin Panel</a> <span class="status-badge status-ready">Ready</span></li>
                            <li><a href="admin/schedule_generator.php">Auto Schedule Generator</a> <span class="status-badge status-ready">Ready</span></li>
                            <li>System Activity Logging <span class="status-badge status-ready">Ready</span></li>
                        </ul>
                        
                        <h5>🛡️ Security Features</h5>
                        <ul>
                            <li>SQL Injection Prevention <span class="status-badge status-ready">Ready</span></li>
                            <li>XSS Protection <span class="status-badge status-ready">Ready</span></li>
                            <li>Session Security <span class="status-badge status-ready">Ready</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Roles -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> User Roles & Permissions</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>👤 Admin (Default Role)</h5>
                        <ul>
                            <li>Limited to assigned school</li>
                            <li>Manage class schedules</li>
                            <li>View school-specific data</li>
                            <li>Access reports and logs</li>
                            <li>Use saved profiles for login</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>👑 Super Admin (Developer Only)</h5>
                        <ul>
                            <li>Global access across all schools</li>
                            <li>Add/remove/edit schools</li>
                            <li>Promote/demote user roles</li>
                            <li>Generate theme passkeys</li>
                            <li>View all system logs</li>
                            <li>Access developer tools</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Default Credentials -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-key"></i> Default Super Admin Credentials</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>⚠️ Important:</strong> Change these credentials after first login!
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Username:</strong><br>
                        <code>superadmin</code>
                    </div>
                    <div class="col-md-4">
                        <strong>Email:</strong><br>
                        <code>admin@system.local</code>
                    </div>
                    <div class="col-md-4">
                        <strong>Password:</strong><br>
                        <code>admin123</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Setup Instructions -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list-ol"></i> Setup Instructions</h3>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Run Setup:</strong> Click <a href="setup_role_system.php">"Setup System"</a> to initialize the database</li>
                    <li><strong>Test System:</strong> Use <a href="test_role_system.php">"Test System"</a> to verify everything works</li>
                    <li><strong>Login:</strong> Access the <a href="admin/login.php">new login system</a> with default credentials</li>
                    <li><strong>Configure:</strong> Add schools, users, and customize themes via the admin panel</li>
                    <li><strong>Generate Schedules:</strong> Use the auto-schedule generator for classes</li>
                </ol>
            </div>
        </div>

        <!-- Documentation -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-book"></i> Documentation & Support</h3>
            </div>
            <div class="card-body text-center">
                <a href="ROLE_BASED_SYSTEM_GUIDE.md" class="btn-outline-custom">
                    <i class="fas fa-file-alt"></i> Complete Guide
                </a>
                <a href="dashboard.php" class="btn-outline-custom">
                    <i class="fas fa-home"></i> Main Dashboard
                </a>
                <a href="README.md" class="btn-outline-custom">
                    <i class="fas fa-info-circle"></i> System README
                </a>
            </div>
        </div>

        <!-- Features Overview -->
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-school"></i>
                </div>
                <h5>Multi-School Support</h5>
                <p>Separate data and themes for each educational institution</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h5>Smart Scheduling</h5>
                <p>Auto-generate schedules with conflict detection</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h5>Advanced Security</h5>
                <p>Role-based access with comprehensive protection</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-palette"></i>
                </div>
                <h5>Theme Management</h5>
                <p>School-specific themes with secure passkey control</p>
            </div>
        </div>

        <div class="text-center mt-4 mb-4">
            <p style="color: rgba(255,255,255,0.8);">
                <i class="fas fa-code"></i> Built with security, scalability, and user experience in mind
            </p>
        </div>
    </div>
</body>
</html>