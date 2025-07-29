<?php
// Use the same session handling as other pages
require_once '../includes/session_config.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
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
                // Update session variables
                if (!empty($username)) {
                    $_SESSION['username'] = $username;
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
    
    // Correct the redirect path - remove 'admin/' since we're already in that directory
    header("Location: users.php");
    exit;
}
?> 