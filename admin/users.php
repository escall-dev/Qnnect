<?php
// Use the same session handling as other pages
require_once '../includes/session_config.php';

// Check if user is logged in (consistent with other pages)
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once "database.php";

// Get user data
$email = $_SESSION['email'];
$sql = "SELECT * FROM users WHERE email = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Main content styles */
        .main {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease;
            width: calc(100% - 260px);
            z-index: 1;
            display: block !important;
            height: auto !important;
            justify-content: flex-start !important;
            align-items: flex-start !important;
            background-color:#808080 ; /* Light gray background for main content */
        }

        /* When sidebar is closed */
        .sidebar.close ~ .main {
            margin-left: 78px;
            width: calc(100% - 78px);
        }
        
        /* Add these styles for main.collapsed state */
        .main.collapsed {
            margin-left: 78px !important;
            width: calc(100% - 78px) !important;
        }

        /* Sidebar styles for proper toggling */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 260px;
            background: #098744;
            z-index: 100;
            transition: all 0.3s ease !important;
        }

        .sidebar.close {
            width: 78px !important;
        }

        /* User container styles */
        .user-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 0;
            margin: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            width: calc(100% - 40px);
            transition: all 0.3s ease;
            height: calc(100vh - 100px);
            overflow: hidden;
        }

        .user-content {
            background-color: white;
            border-radius: 20px;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow-y: auto;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            max-height: calc(100vh - 100px);
            scrollbar-width: thin;
            scrollbar-color: #098744 transparent;
        }

        /* Scrollbar styling for webkit browsers */
        .user-content::-webkit-scrollbar {
            width: 8px;
        }

        .user-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .user-content::-webkit-scrollbar-thumb {
            background-color: #098744;
            border-radius: 4px;
        }

        /* Title styles */
        .title {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 15px;
            margin: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
            border-radius: 20px 20px 0 0;
            height: 60px; /* Fixed height for the title */
        }

        .title h4 {
            margin: 0;
            color: #098744;
        }

        /* Form container styles */
        .form-container {
            padding: 20px;
            background-color: #f9f9f9;
            min-height: calc(100% - 60px); /* Allow for scrolling */
        }

        .form-card {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 20px auto;
            display: block;
            border: 3px solid #098744;
        }

        .btn-primary {
            background-color: #098744;
            border-color: #098744;
        }

        .btn-primary:hover {
            background-color: #076633;
            border-color: #076633;
        }

        /* Alert styles */
        .alert {
            margin: 20px 40px;
            border-radius: 10px;
        }

        /* Add background styling */
        body {
            background-color: #808080; /* Match the gray background of analytics.php */
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Prevent horizontal scrolling */
        }

        /* Add smooth transitions to all elements */
        .main, .user-container, .user-content {
            transition: all 0.3s ease !important;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .sidebar {
                width: 260px;
                transform: translateX(0);
            }
            
            .sidebar.close {
                transform: translateX(-100%) !important;
                width: 260px !important;
            }
            
            .main {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .main.collapsed {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .user-container {
                margin: 10px;
                min-height: calc(100vh - 100px);
            }
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar-nav.php'; ?>

    <div class="main" id="main">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="user-container">
            <div class="user-content">
                <div class="title">
                    <h4><i class="fas fa-user-edit"></i> Update User Account</h4>
                </div>
                
                <div class="form-container">
                    <div class="form-card">
                        <form action="controller.php?action=edit" method="POST" enctype="multipart/form-data">
                            <div class="text-center mb-4">
                                <img id="preview" 
                                     src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : 'image/SPCPC-logo-trans.png'; ?>" 
                                     alt="Profile Picture" 
                                     class="profile-image-preview">
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">Username:</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email:</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password:</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter new password (leave blank to keep current)">
                            </div>

                            <div class="mb-4">
                                <label for="profile_image" class="form-label">Profile Picture:</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" 
                                       accept="image/*" onchange="previewImage(this)">
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').setAttribute('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Add sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.querySelector('.fa-bars'); // Target the hamburger icon
            const sidebar = document.querySelector('.sidebar');
            const main = document.querySelector('.main');

            // Check if elements exist
            if (toggleButton && sidebar && main) {
                // Log initial state
                console.log('Initial sidebar classes:', sidebar.classList);
                console.log('Initial main classes:', main.classList);

                // Add click event to toggle button
                toggleButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidebar.classList.toggle('close');
                    main.classList.toggle('collapsed');
                    
                    console.log('Toggled sidebar classes:', sidebar.classList);
                    console.log('Toggled main classes:', main.classList);
                });
            } else {
                console.error('Sidebar toggle elements not found:', { 
                    toggleButton: !!toggleButton, 
                    sidebar: !!sidebar, 
                    main: !!main 
                });
            }

            // Submenu toggle functionality
            function toggleSubmenu(event) {
                event.preventDefault();
                const toggle = event.currentTarget;
                const submenu = toggle.nextElementSibling;
                const arrow = toggle.querySelector('.arrow');

                // Close all other submenus
                const allSubmenus = document.querySelectorAll('.submenu');
                allSubmenus.forEach(menu => {
                    if (menu !== submenu && menu.style.display === 'block') {
                        menu.style.display = 'none';
                        const otherArrow = menu.previousElementSibling.querySelector('.arrow');
                        if (otherArrow) otherArrow.style.transform = '';
                    }
                });

                // Toggle current submenu
                if (submenu) {
                    submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
                    if (arrow) {
                        arrow.style.transform = submenu.style.display === 'block' ? 'rotate(180deg)' : '';
                    }
                }
            }

            // Add click event listeners for submenus
            const submenuToggles = document.querySelectorAll('.submenu-toggle');
            submenuToggles.forEach(toggle => {
                toggle.addEventListener('click', toggleSubmenu);
            });
        });
    </script>
</body>
</html> 