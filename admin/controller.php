<?php
// Determine if we're handling a super admin session or regular admin session
// Check for URL parameters that would indicate super admin context
$from_super_admin = isset($_POST['from_super_admin']) || 
                   (isset($_POST['redirect']) && strpos($_POST['redirect'], 'admin_panel.php') !== false);

// Flag this script as super admin context if needed
if ($from_super_admin) {
    define('SUPER_ADMIN_CONTEXT', true);
}

if ($from_super_admin) {
    // Include super admin session configuration
    require_once '../includes/session_config_superadmin.php';
} else {
    // Use regular session handling
    require_once '../includes/session_config.php';
}

require_once '../includes/auth_functions.php';

Check if user is logged in
if (!isset($_SESSION['email'])) {
    // Redirect to appropriate login page
    if ($from_super_admin) {
        header("Location: super_admin_login.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

// Log session data for debugging
error_log("Session data in controller: " . print_r($_SESSION, true));

require_once "database.php";

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'edit':
        handleEdit();
        break;
    default:
        header("Location: users.php");
        exit;
}

function handleEdit() {
    global $conn;
    
    try {
        $email = $_SESSION['email'];
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Start with base query
        $updates = array();
        $params = array();
        
        // Add username update
        if (!empty($username)) {
            $updates[] = "username = ?";
            $params[] = $username;
        }
        
        // Add password update if provided
        if (!empty($password)) {
            $updates[] = "password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                // Create uploads directory if it doesn't exist
                if (!file_exists('../uploads/profile_images')) {
                    mkdir('../uploads/profile_images', 0777, true);
                }
                
                $new_filename = "profile_" . time() . "." . $filetype;
                $upload_path = "../uploads/profile_images/" . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $profile_image = "uploads/profile_images/" . $new_filename;
                    $updates[] = "profile_image = ?";
                    $params[] = $profile_image;
                }
            }
        }
        
        // Get current username before making any updates (for related table updates)
        $current_user_query = "SELECT username FROM users WHERE email = ?";
        $current_stmt = $conn->prepare($current_user_query);
        $current_stmt->bind_param("s", $email);
        $current_stmt->execute();
        $current_result = $current_stmt->get_result();
        $current_user = $current_result->fetch_assoc();
        $old_username = $current_user['username'] ?? null;
        $current_stmt->close();
        
        if (empty($updates)) {
            $_SESSION['error'] = "No changes to update.";
            header("Location: users.php");
            exit;
        }

        // Add email to parameters for WHERE clause
        $params[] = $email;
        
        // Construct final query
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE email = ?";
        
        // Debug information
        error_log("SQL Query: " . $sql);
        error_log("Parameters: " . print_r($params, true));
        
        // Prepare and execute statement
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // Create type string (s for each parameter)
            $types = str_repeat("s", count($params));
            
            // Bind parameters
            $stmt->bind_param($types, ...$params);
            
            // Execute the statement
            if ($stmt->execute()) {
                // If username was updated, we need to update related tables
                if (!empty($username) && $old_username && $old_username !== $username) {
                    require_once '../conn/db_connect.php';
                    
                    // Update teacher_schedules table with new username
                    $update_schedules_query = "UPDATE teacher_schedules SET teacher_username = ? WHERE teacher_username = ?";
                    $schedule_stmt = $conn_qr->prepare($update_schedules_query);
                    $schedule_stmt->bind_param("ss", $username, $old_username);
                    
                    if ($schedule_stmt->execute()) {
                        $affected_rows = $schedule_stmt->affected_rows;
                        error_log("Updated teacher_schedules: changed username from '$old_username' to '$username' ($affected_rows rows affected)");
                    } else {
                        error_log("Failed to update teacher_schedules: " . $schedule_stmt->error);
                    }
                    $schedule_stmt->close();
                }                // Update session variables
                if (!empty($username)) {
                    $_SESSION['username'] = $username;
                    // Also update userData session if it exists
                    if (isset($_SESSION['userData'])) {
                        $_SESSION['userData']['username'] = $username;
                    }
                }
                if (isset($profile_image)) {
                    $_SESSION['profile_image'] = $profile_image;
                }
                
                $_SESSION['success'] = "Profile updated successfully!";
            } else {
                $_SESSION['error'] = "Error updating profile: " . $stmt->error;
            }
            
            $stmt->close();
        } else {
            $_SESSION['error'] = "Error preparing statement: " . $conn->error;
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    }
    
    // Redirect back to source if provided
    $redirect = $_POST['redirect'] ?? null;
    if ($redirect) {
        // If redirecting to admin_panel.php, this is a super admin operation
        if (strpos($redirect, 'admin_panel.php') !== false) {
            header("Location: " . $redirect);
        } else {
            header("Location: " . $redirect);
        }
    } else {
        header("Location: users.php");
    }
    exit;
}
?> 