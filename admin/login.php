<?php
require_once '../includes/session_config.php';
require_once '../includes/auth_functions.php';
require_once '../includes/school_branding.php';
require_once "functions/log_functions.php";

// Input sanitization function
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get schools for dropdown with logos
function getSchools($conn) {
    $sql = "SELECT s.id, s.name, s.code, s.theme_color, u.profile_image as logo_path 
            FROM schools s 
            LEFT JOIN users u ON s.id = u.school_id AND u.role = 'admin' 
            WHERE s.status = 'active' 
            ORDER BY s.name";
    $result = mysqli_query($conn, $sql);
    $schools = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $schools[] = $row;
    }
    return $schools;
}

// Get users by school for profile display
function getUsersBySchool($conn, $school_id) {
    $sql = "SELECT u.id, u.username, u.email, u.profile_image, u.role, rl.last_login 
            FROM users u 
            LEFT JOIN recent_logins rl ON u.username = rl.username 
            WHERE u.school_id = ? AND u.username IS NOT NULL AND u.username != ''
            ORDER BY rl.last_login DESC, u.username ASC 
            LIMIT 10";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    return $users;
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    require_once "database.php";
    
    if ($_POST['action'] === 'get_users_by_school') {
        $school_id = (int)$_POST['school_id'];
        $users = getUsersBySchool($conn, $school_id);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'users' => $users]);
        exit();
    }
    
    if ($_POST['action'] === 'get_school_theme') {
        $school_id = (int)$_POST['school_id'];
        $sql = "SELECT theme_color FROM schools WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $school_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $school = mysqli_fetch_assoc($result);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'theme_color' => $school['theme_color'] ?? '#098744']);
        exit();
    }
}

// Process login form submission
if (isset($_POST["login"])) {
    $errors = [];
    
    $selected_user_id = isset($_POST["selected_user_id"]) ? (int)$_POST["selected_user_id"] : null;
    $school_id = isset($_POST["school_id"]) ? (int)$_POST["school_id"] : null;
    $password = $_POST["password"] ?? '';
    
    if (empty($school_id)) {
        $errors[] = "Please select a school";
    }
    
    if (empty($selected_user_id)) {
        $errors[] = "Please select a user profile";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    require_once "database.php";

    if (empty($errors)) {
        // Get user data and verify school association
        $sql = "SELECT * FROM users WHERE id = ? AND school_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $selected_user_id, $school_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_array($result, MYSQLI_ASSOC);
        
        if ($user && password_verify($password, $user["password"])) {
            // Ensure session is started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Clear any logout flags and ensure clean session state
            unset($_SESSION['logging_out']);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = htmlspecialchars($user['email']);
            $_SESSION['username'] = htmlspecialchars($user['username']);
            $_SESSION['profile_image'] = htmlspecialchars($user['profile_image']);
            $_SESSION['role'] = htmlspecialchars($user['role'] ?? 'admin');
            $_SESSION['school_id'] = $user['school_id'];
            $_SESSION['login_recorded'] = false;
            $_SESSION['session_created_this_login'] = false;
            
            // Debug: Log session variables after setting
            error_log('Login successful. Session variables set: ' . print_r($_SESSION, true));
            error_log('User ID: ' . $user['id'] . ', School ID: ' . $user['school_id']);
            
            // Update recent login tracking
            $update_sql = "INSERT INTO recent_logins (username, profile_image, school_id, last_login) 
                          VALUES (?, ?, ?, NOW()) 
                          ON DUPLICATE KEY UPDATE 
                          profile_image = VALUES(profile_image), 
                          school_id = VALUES(school_id),
                          last_login = NOW()";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "ssi", $user['username'], $user['profile_image'], $school_id);
            mysqli_stmt_execute($stmt);
            
            // Record the login
            $log_id = recordUserLogin(
                $conn, 
                htmlspecialchars($user['username']), 
                htmlspecialchars($user['email']), 
                htmlspecialchars($user['role'] ?? 'admin')
            );
            
            if ($log_id) {
                $_SESSION['log_id'] = $log_id;
                $_SESSION['session_created_this_login'] = true;
            }
            
            // Log the activity
            logActivity($conn, 'USER_LOGIN', "School: {$school_id}", $school_id);
            
            header("Location: ../dashboard.php");
            exit();
        } else {
            $errors[] = "Invalid credentials. Please try again.";
        }
    }
    
    $error_message = !empty($errors) ? implode('<br>', $errors) : null;
}

// Get schools for display
require_once "database.php";
$schools = getSchools($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qnnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #098744;
            --secondary-color: #0a5c2e;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            height: 100vh;
            overflow: hidden;
        }

        .main-container {
            display: flex;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Left Section - Branding */
        .branding-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 20px 40px;
            color: white;
            position: relative;
            height: 100vh;
            overflow: visible;
            left: 0;
            margin-right: auto;
        }

        /* Top Left Logo like Facebook */
        .top-logo {
            position: absolute;
            top: 20px;
            left: 40px; /* slight nudge for larger logo */
        }

        .spcpc-logo {
            width: 185px;  /* was 135px */
            height: 185px; /* was 135px */
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }

        /* Brand Title Section */
        .brand-content {
            text-align: left;
            max-width: 600px;
            margin-left: 40px;
            padding-bottom: 20px; /* Added padding to prevent cutoff */
        }

        .brand-title {
            font-size: 85px;
            font-weight: 800;
            line-height: 1.05; /* Slightly increased line height */
            margin-bottom: 15px;
            background: linear-gradient(135deg, #ffffff 0%, #e8f5e8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: left;
            letter-spacing: -1px;
            padding-bottom: 10px; /* Added padding for descenders */
            left: 0;
            margin-right: auto;
            font-family: 'Debata', sans-serif;
        }

        .tagline {
            font-size: 28px;
            font-weight: 400;
            opacity: 0.9;
            color: white;
            text-align: left;
            margin-top: 5px;
        }

        /* Right Section - Login Panel */
        .login-section {
            flex: 0 0 750px;
            background: var(--primary-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
            height: 100vh;
            overflow: hidden;
        }

        /* Top-left Back Arrow for steps 2/3 */
        .back-top-btn {
            position: absolute;
            top: 16px;
            left: 16px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.12);
            color: #fff;
            display: none; /* toggled via JS */
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s ease;
            z-index: 10;
        }

        .back-top-btn:hover { background: rgba(255,255,255,0.18); border-color: rgba(255,255,255,0.45); }
        .back-top-btn i { font-size: 18px; }

        .login-container {
            width: 100%;
            max-width: 450px;
            text-align: center;
            position: relative;
        }

        /* Settings Gear Icon */
        .settings-gear {
            position: absolute;
            top: -20px;
            right: 0;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .settings-gear:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .settings-gear i {
            color: white;
            font-size: 18px;
        }

        /* Step Content */
        .step-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .step-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Step 1: School Carousel */
        .school-carousel-container {
            margin: 60px 0;
            min-height: 350px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .school-carousel {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 40px 0;
            width: 100%;
            min-height: 250px;
            overflow: hidden;
        }

        .school-slides-wrapper {
            display: flex;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
        }

        .school-item {
            min-width: 100%;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 20px;
        }

        .school-item.active .school-logo {
            border-color: white;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .school-logo {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .school-name {
            color: white;
            font-size: 22px;
            font-weight: 600;
            margin-top: 10px;
            text-align: center;
        }

        /* Carousel dots for schools */
        .school-carousel-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .school-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .school-dot.active {
            background: white;
            transform: scale(1.2);
        }

        .school-dot:hover {
            background: rgba(255, 255, 255, 0.6);
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .carousel-nav:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .carousel-nav.prev {
            left: -60px;
        }

        .carousel-nav.next {
            right: -60px;
        }

        /* Step 2: Profile Selection */
        .profiles-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 40px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
        }

        .profiles-header i {
            margin-right: 8px;
            color: #4285F4;
        }

        .profile-carousel {
            position: relative;
            margin: 40px 0;
        }

        .profile-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.7;
            transform: scale(0.9);
        }

        .profile-item.active {
            opacity: 1;
            transform: scale(1);
        }

        .profile-avatar {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .profile-item.active .profile-avatar {
            border-color: white;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar i {
            color: white;
            font-size: 60px;
        }

        .profile-name {
            color: white;
            font-size: 20px;
            font-weight: 600;
            margin-top: 10px;
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 30px 0;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: #4285F4;
            transform: scale(1.2);
        }

        /* Step 3: Password Entry */
        .password-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 60px 0;
        }

        .selected-user {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 40px;
        }

        .selected-user .profile-avatar {
            width: 100px;
            height: 100px;
            border: 3px solid white;
            margin-bottom: 15px;
        }

        .selected-user .profile-name {
            font-size: 18px;
            margin-bottom: 30px;
        }

        .password-input-container {
            position: relative;
            width: 100%;
            max-width: 350px;
            margin-bottom: 30px;
        }

        .password-input {
            width: 100%;
            padding: 16px 50px 16px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .password-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .password-input:focus {
            outline: none;
            border-color: white;
            background: rgba(255, 255, 255, 0.15);
        }

        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.6);
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: white;
        }

        /* Buttons */
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            transition: all 0.2s ease;
            cursor: pointer;
            
        }

        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid #42b883;
            border-radius: 50px;
            font-size: 14px;    
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 5px 0 12px 0;
            min-width: 200px;
            display: block;            /* make anchors fill full width */
            text-align: center;        /* center text for anchors */
            text-decoration: none;     /* remove underline on anchors */
        }
        .btn-secondary:link, .btn-secondary:visited { text-decoration: none; color: white; }

    .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-1px);
        }

    /* Extra spacing for portal button to clear fixed footer */
    .btn-portal { margin-bottom: 32px; }

        .forgot-password {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            margin-top: 20px;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: white;
            text-decoration: underline;
        }

        .error-alert {
            background: rgba(220, 53, 69, 0.2);
            color: #fff;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(220, 53, 69, 0.3);
            font-size: 14px;
        }

        /* Add footer styles */
        
        .footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: var(--bg);
    color: #fff;
    
    display: flex;
    align-items: center;
                justify-content: flex-start;
    gap: 18px;
    z-index: 1000;
    box-shadow: 0 -4px 18px rgba(0,0,0,0.25);
  }

            .footer .flex-spacer { flex: 1 1 auto; }
            .footer .app-version { font-size: 12px; opacity: 0.85; margin-right: 14px; white-space: nowrap; }

  /* Each policy item must be position:relative for absolute child */
  .policy-item {
    position: relative;          /* important */
    display: inline-block;
    margin: 0 6px;
  }

  .footer a.footer-link {
    color: #dfe6ee;
    text-decoration: none;
    padding: 6px 8px;
    border-radius: 6px;
    display: inline-block;
    font-weight: 600;
  }
  .footer a.footer-link:hover,
  .footer a.footer-link:focus {
    color: #fff;
    background: rgba(255,255,255,0.04);
    outline: none;
  }

                /* Small hover modal (tooltip-like) - dark glass theme (readability enhanced) */
                .hover-modal {
            position: fixed; /* centered overlay panel */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(.97);
            width: 520px;
            max-width: min(90%, 640px);
                background:
                    linear-gradient(160deg, rgba(22,78,55,.90), rgba(14,55,38,.93)) padding-box;
                color: #f2fff9;
            padding: 28px 32px 30px;
            border-radius: 18px;
                border: 1px solid rgba(255,255,255,0.18);
                box-shadow: 0 4px 12px -2px rgba(0,0,0,0.45), 0 20px 44px -10px rgba(0,0,0,0.55);
            backdrop-filter: blur(12px) saturate(160%);
            -webkit-backdrop-filter: blur(12px) saturate(160%);
            z-index: 1000;
            text-align: left;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.55;
            letter-spacing: .25px;
            opacity: 0;               /* hidden by default */
            visibility: hidden;
            pointer-events: none;      /* don't intercept clicks when hidden */
            transition: opacity .14s ease, transform .22s cubic-bezier(.16,.84,.44,1);
        }

                .hover-modal:before {
                    content: "";
                    position: absolute;
                    inset: 0;
                    border-radius: inherit;
                    background:
                        radial-gradient(circle at 18% 22%, rgba(120,255,200,0.15), rgba(120,255,200,0) 62%),
                        radial-gradient(circle at 82% 78%, rgba(9,135,68,0.22), rgba(9,135,68,0) 70%);
                    pointer-events: none;
                    mix-blend-mode: screen;
                }

                .hover-modal .left strong,
                .hover-modal .right strong { color:#8dfed4; font-weight:700; letter-spacing:.45px; text-shadow:0 0 6px rgba(141,254,212,0.4); }
                .hover-modal p { margin:0 0 14px; font-size:15px; line-height:1.55; color:#e6f9f1; }
                .hover-modal .separator { background: linear-gradient(to bottom, rgba(141,254,212,0.65), rgba(141,254,212,0.08)); }
                .hover-modal a { color:#9bffe0; text-decoration:underline; }
                .hover-modal a:hover { text-decoration:none; color:#cffff0; }
                .hover-modal small, .hover-modal li { color:#d9f5eb; }
  

  /* show hover modal when the policy-item or the modal itself is hovered or focused */
    .policy-item:hover .hover-modal,
    .policy-item:focus-within .hover-modal,
    .hover-modal:hover {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translate(-50%, -50%) scale(1);
    }
  
  
  /* Overlay modal (large full content on click) */
  .overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    align-items: center;
    justify-content: center;
    z-index: 3000;
    padding: 20px;
    
  }
  .overlay.active { display: flex; }

  .overlay-panel {
    background: var(--panel);
    color: #111;
    max-width: 880px;
    width: 100%;
    border-radius: 10px;
    padding: 22px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.35);
    max-height: 86vh;
    overflow: auto;
    position: relative;
  }

  .overlay .close-btn {
    position: absolute;
    top: 14px;
    right: 14px;
    border: none;
    background: transparent;
    font-size: 22px;
    cursor: pointer;
  }

  .overlay h2 { margin-top: 0; margin-bottom: 8px; }
  .overlay .body-text { color:#333; line-height:1.6; font-size:14px; }

   .modal {
      display: none;
      position: fixed;  /* Fixed to viewport */
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%); /* Center horizontally & vertically */
      background: #fff;
      color: #333;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0px 4px 20px rgba(0,0,0,0.3);
      width: 400px;
      max-width: 90%;
      text-align: center;
      z-index: 1000;
    }
    /* Removed duplicate .hover-modal rule (was overriding earlier definition with display:none) */
    /* Override old paragraph styling inside hover modal (kept for legacy) */
    .hover-modal p{
        font-size:15px !important;
        color:#e6f9f1 !important;
        line-height:1.55 !important;
    }

    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 999;
    }
     footer a:hover + .modal-overlay,
    footer a:hover + .modal-overlay + .hover-modal {
      display: block;
    }


    /* Left column */
.hover-modal .left {
    text-align:center;
    flex: 1;
    font-size: 22px;
    line-height: 1.5;
    color:#e6f9f1; /* light text for dark glass */
}
.hover-modal .left strong {
    display:block;
    font-weight:700;
    margin-bottom:8px;
    color:#8dfed4; /* accent heading */
    letter-spacing:.4px;
}

/* Separator */
.hover-modal .separator {
  width: 1px;
  background: #cbd5e1;
  align-self: stretch;
}

/* Right column */
.hover-modal .right {
    margin-top:10px;
    font-size:18px;
    color:#d9f5eb;
    text-align:center;
}
.hover-modal .right strong {
    display:inline;
    color:#8dfed4;
    margin-bottom:4px;
    font-weight:600;
}
    
  /* Responsive */
  @media (max-width: 560px) {
    .hover-modal { width: 260px; }
    .footer { padding: 12px; gap: 10px; }
  } 


/* small screen adjustments */
@media (max-width: 600px) {
    .footer { font-size: 13px; padding: 10px; }
    .hover-tooltip { left: 50%; transform: translateX(-50%); min-width: 200px; max-width: 260px; }
    .overlay .overlay-content { padding: 16px; }
}


        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .branding-section {
                display: none;
            }
            
            .login-section {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Left Section - Branding -->
        <div class="branding-section">
            <div class="top-logo">
                <img src="image/Qnnect-v1.2.png" alt="Qnnect Logo" class="spcpc-logo">
            </div>
            
            <div class="brand-content">
                <div class="brand-title">
                    Track<br>
                    Attendance<br>
                    Seamlessly.
                </div>
                <div class="tagline">
                Scan in. Stay Synced.
                </div>
            </div>
        </div>

        <!-- Right Section - Login Panel -->
        <div class="login-section">
            <button type="button" id="backTopBtn" class="back-top-btn" aria-label="Back" onclick="backTopAction()">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="login-container">
                <?php if(isset($error_message)): ?>
                    <div class='error-alert'><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Settings Gear -->
                

                <form id="loginForm" method="post" action="">
                    <!-- Step 1: School Carousel Selection -->
                    <div class="step-content active" id="content1">
                        <div class="school-carousel-container">
                            <div class="school-carousel" id="school_carousel">
                                <div class="school-slides-wrapper" id="school_slides_wrapper">
                                    <?php if (!empty($schools)): ?>
                                        <?php foreach ($schools as $index => $school): ?>
                                        <div class="school-item" onclick="handleSchoolClick(<?php echo $school['id']; ?>)">
                                            <div class="school-logo">
                                                <?php 
                                                // Use database logo if available, otherwise fallback to default
                                                if (!empty($school['logo_path']) && file_exists($school['logo_path'])) {
                                                    $logoSrc = $school['logo_path'];
                                                } else {
                                                    // Fallback to hardcoded logic for existing schools
                                                    $logoFile = 'SPCPC-logo-trans.png'; // Default
                                                    if (stripos($school['name'], 'computer site') !== false || stripos($school['name'], 'comsite') !== false || $school['id'] == 2) {
                                                        $logoFile = 'comsite-logo-trans.png';
                                                    }
                                                    $logoSrc = 'image/' . $logoFile;
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="<?php echo htmlspecialchars($school['name']); ?>">
                                            </div>
                                            <div class="school-name"><?php echo htmlspecialchars($school['name']); ?></div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- Default fallback schools if database not set up -->
                                        <div class="school-item" onclick="handleSchoolClick(1)">
                                            <div class="school-logo">
                                                <img src="image/SPCPC-logo-trans.png" alt="SPCPC">
                                            </div>
                                            <div class="school-name">SPCPC</div>
                                        </div>
                                        <div class="school-item" onclick="handleSchoolClick(2)">
                                            <div class="school-logo">
                                                <img src="image/comsite-logo-trans.png" alt="Computer Site Inc.">
                                            </div>
                                            <div class="school-name">Computer Site Inc.</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php 
                            $schoolCount = !empty($schools) ? count($schools) : 2; // Default fallback count
                            if ($schoolCount > 1): 
                            ?>
                            <div class="carousel-nav prev" onclick="prevSchool()">
                                <i class="fas fa-chevron-left"></i>
                            </div>
                            <div class="carousel-nav next" onclick="nextSchool()">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                            
                            <!-- School carousel dots -->
                            <div class="school-carousel-dots" id="school_dots">
                                <?php for ($i = 0; $i < $schoolCount; $i++): ?>
                                <div class="school-dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="goToSchool(<?php echo $i; ?>)"></div>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" class="btn-primary" id="btn_step1" disabled onclick="nextStep(1)">
                            Continue
                        </button> <br>
                        
                        <button type="button" class="btn-secondary" onclick="window.location.href='registration.php'">
                            Create new account
                        </button>
                        <a href="super_admin_login.php?force_pin=1" class="btn-secondary btn-portal">Super Admin Portal</a>
                        
                        <input type="hidden" id="selected_school_id" name="school_id">
                    </div>

                    <!-- Step 2: Profile Selection -->
                    <div class="step-content" id="content2">
                        <div class="profiles-header">
                            <i class="fas fa-users"></i>
                            <span id="profile_count">4 saved profiles</span>
                        </div>
                        
                        <div class="profile-carousel" id="profile_carousel">
                            <!-- Profiles will be loaded here -->
                        </div>
                        
                        <div class="carousel-nav prev" onclick="prevProfile()" style="display: none;">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        <div class="carousel-nav next" onclick="nextProfile()" style="display: none;">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        
                        <div class="carousel-dots" id="profile_dots">
                            <!-- Dots will be generated here -->
                        </div>
                        
                        <button type="button" class="btn-primary" id="btn_step2" disabled onclick="nextStep(2)">
                            Continue
                        </button>
                        
                        <button type="button" id="backBtnStep2" class="btn-secondary" style="display:none;" onclick="prevStep(2)">
                            Back
                        </button>
                        
                        <button type="button" class="btn-secondary" onclick="window.location.href='registration.php'">
                            Create new account
                        </button>
                        <a href="super_admin_login.php?force_pin=1" class="btn-secondary btn-portal">Super Admin portal</a>
                        
                        <input type="hidden" id="selected_user_id" name="selected_user_id">
                    </div>

                    <!-- Step 3: Password Entry -->
                    <div class="step-content" id="content3">
                        <div class="password-step">
                            <div class="selected-user" id="selected_user_display">
                                <!-- Selected user info will be displayed here -->
                            </div>
                            
                            <div class="password-input-container">
                                <input type="password" class="password-input" id="password" name="password" placeholder="Password" required>
                                <span class="password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </span>
                            </div>
                            
                            <button type="submit" class="btn-primary" name="login">
                                Log in
                            </button>
                            
                            <button type="button" id="backBtnStep3" class="btn-secondary" style="display:none;" onclick="prevStep(3)">
                                Back to profiles
                            </button>
                            
                            <a href="forgotPassword.php" class="forgot-password">Forgot Password?</a>
                        </div>
                    </div>
                </form>

               
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let selectedSchoolId = null;
        let selectedUserId = null;
        let schools = <?php echo json_encode($schools); ?>;
        let currentSchoolIndex = 0;
        let currentProfileIndex = 0;
        let profiles = [];

        // Initialize the interface
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Schools loaded:', schools);
            console.log('DOM loaded, initializing...');
            
            // Initialize school carousel
            updateSchoolCarousel();
            
            // Add touch/swipe support for school carousel
            addSchoolSwipeSupport();
        });

        // Add swipe support for school carousel
        function addSchoolSwipeSupport() {
            const carousel = document.getElementById('school_carousel');
            if (!carousel) return;
            
            let startX, currentX, isDragging = false;
            
            carousel.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                isDragging = true;
            });
            
            carousel.addEventListener('touchmove', (e) => {
                if (!isDragging) return;
                currentX = e.touches[0].clientX;
            });
            
            carousel.addEventListener('touchend', (e) => {
                if (!isDragging) return;
                isDragging = false;
                
                const deltaX = startX - currentX;
                const threshold = 50;
                
                if (Math.abs(deltaX) > threshold) {
                    if (deltaX > 0) {
                        nextSchool();
                    } else {
                        prevSchool();
                    }
                }
            });
        }

        // Select school
        function selectSchool(schoolId) {
            console.log('School selected:', schoolId);
            selectedSchoolId = schoolId;
            document.getElementById('selected_school_id').value = schoolId;
            
            // Update visual selection
            document.querySelectorAll('.school-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Find and mark the current school as active
            const schoolItems = document.querySelectorAll('.school-item');
            schoolItems.forEach((item, index) => {
                if (index === currentSchoolIndex) {
                    item.classList.add('active');
                }
            });
            
            // Update theme colors
            const school = schools.find(s => s.id == schoolId);
            if (school && school.theme_color) {
                document.documentElement.style.setProperty('--primary-color', school.theme_color);
                const secondaryColor = adjustBrightness(school.theme_color, -20);
                document.documentElement.style.setProperty('--secondary-color', secondaryColor);
            }
            
            // Enable continue button
            document.getElementById('btn_step1').disabled = false;
            
            // Auto-proceed to step 2 after a short delay
            setTimeout(() => {
                nextStep(1);
            }, 500);
        }

        // Handle school item clicks
        function handleSchoolClick(schoolId) {
            // Find the index of the clicked school
            const schoolIndex = schools.findIndex(s => s.id == schoolId);
            if (schoolIndex !== -1) {
                currentSchoolIndex = schoolIndex;
                updateSchoolCarousel();
            } else {
                // Handle default schools (SPCPC = 1, Computer Site Inc. = 2)
                currentSchoolIndex = schoolId - 1;
                updateSchoolCarousel();
            }
            
            // Select the school
            selectSchool(schoolId);
        }

        // Navigate between schools - carousel style
        function prevSchool() {
            const schoolCount = schools.length > 0 ? schools.length : 2;
            if (currentSchoolIndex > 0) {
                currentSchoolIndex--;
                updateSchoolCarousel();
            }
        }

        function nextSchool() {
            const schoolCount = schools.length > 0 ? schools.length : 2;
            if (currentSchoolIndex < schoolCount - 1) {
                currentSchoolIndex++;
                updateSchoolCarousel();
            }
        }

        // Navigate to specific school (for dots)
        function goToSchool(index) {
            currentSchoolIndex = index;
            updateSchoolCarousel();
        }

        // Update school carousel display
        function updateSchoolCarousel() {
            const wrapper = document.getElementById('school_slides_wrapper');
            if (!wrapper) return;
            
            const translateX = -currentSchoolIndex * 100;
            wrapper.style.transform = `translateX(${translateX}%)`;
            
            // Update dots
            const dots = document.querySelectorAll('.school-dot');
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSchoolIndex);
            });
            
            // Update navigation arrows
            const prevArrow = document.querySelector('.carousel-nav.prev');
            const nextArrow = document.querySelector('.carousel-nav.next');
            const schoolCount = schools.length > 0 ? schools.length : 2;
            
            if (prevArrow) prevArrow.style.opacity = currentSchoolIndex === 0 ? '0.5' : '1';
            if (nextArrow) nextArrow.style.opacity = currentSchoolIndex === schoolCount - 1 ? '0.5' : '1';
            
            // Auto-select the current school
            const schoolItems = document.querySelectorAll('.school-item');
            if (schoolItems[currentSchoolIndex]) {
                const schoolId = schools.length > 0 ? schools[currentSchoolIndex].id : (currentSchoolIndex + 1);
                selectedSchoolId = schoolId;
                document.getElementById('selected_school_id').value = schoolId;
                document.getElementById('btn_step1').disabled = false;
            }
        }

        // Step navigation
        function nextStep(step) {
            if (step === 1 && selectedSchoolId) {
                loadUserProfiles(selectedSchoolId);
                showStep(2);
            } else if (step === 2 && selectedUserId) {
                showSelectedUser();
                showStep(3);
                document.getElementById('password').focus();
            }
        }

        function prevStep(step) {
            if (step === 2) {
                showStep(1);
            } else if (step === 3) {
                showStep(2);
            }
        }

        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.remove('active');
            });

            // Show current step
            document.getElementById(`content${step}`).classList.add('active');
            currentStep = step;
            // Toggle top-left back button visibility
            const backTopBtn = document.getElementById('backTopBtn');
            if (backTopBtn) backTopBtn.style.display = step > 1 ? 'flex' : 'none';
        }

        // Back arrow action
        function backTopAction() {
            if (currentStep === 3) {
                showStep(2);
            } else if (currentStep === 2) {
                showStep(1);
            }
        }

        // Load user profiles for selected school
        function loadUserProfiles(schoolId) {
            const container = document.getElementById('profile_carousel');
            container.innerHTML = '<div style="color: white; text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i><br>Loading profiles...</div>';

            fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_users_by_school&school_id=${schoolId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.users.length > 0) {
                    profiles = data.users;
                    currentProfileIndex = 0;
                    loadProfileCarousel();
                    updateProfileCount(data.users.length);
                } else {
                    container.innerHTML = `
                        <div style="color: white; text-align: center; padding: 40px;">
                            <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No profiles found for this school</p>
                            <small style="opacity: 0.7;">Contact your administrator to create an account</small>
                        </div>
                    `;
                    updateProfileCount(0);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                container.innerHTML = `
                    <div style="color: white; text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>Error loading profiles</p>
                        <small style="opacity: 0.7;">Please try again</small>
                    </div>
                `;
            });
        }

        // Load profile carousel
        function loadProfileCarousel() {
            const carousel = document.getElementById('profile_carousel');
            if (profiles.length > 0) {
                const profile = profiles[currentProfileIndex];
                const avatarSrc = profile.profile_image && profile.profile_image !== 'null' 
                    ? `../${profile.profile_image}` 
                    : '';
                
                carousel.innerHTML = `
                    <div class="profile-item active" onclick="selectProfile(${profile.id}, '${profile.username}')">
                        <div class="profile-avatar">
                            ${avatarSrc ? `<img src="${avatarSrc}" alt="${profile.username}">` : '<i class="fas fa-user"></i>'}
                        </div>
                        <div class="profile-name">${profile.username}</div>
                    </div>
                `;
                
                // Generate dots
                generateProfileDots();
                
                // Show navigation arrows if multiple profiles
                const prevNav = document.querySelector('#content2 .carousel-nav.prev');
                const nextNav = document.querySelector('#content2 .carousel-nav.next');
                
                if (profiles.length > 1) {
                    if (prevNav) prevNav.style.display = 'flex';
                    if (nextNav) nextNav.style.display = 'flex';
                } else {
                    if (prevNav) prevNav.style.display = 'none';
                    if (nextNav) nextNav.style.display = 'none';
                }
                
                // Auto-select the current profile
                selectProfile(profile.id, profile.username);
                
                // Add touch/swipe support for profiles
                addProfileSwipeSupport();
            }
        }

        // Add swipe support for profile carousel
        function addProfileSwipeSupport() {
            const carousel = document.getElementById('profile_carousel');
            if (!carousel) return;
            
            let startX, currentX, isDragging = false;
            
            carousel.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                isDragging = true;
            });
            
            carousel.addEventListener('touchmove', (e) => {
                if (!isDragging) return;
                currentX = e.touches[0].clientX;
            });
            
            carousel.addEventListener('touchend', (e) => {
                if (!isDragging) return;
                isDragging = false;
                
                const deltaX = startX - currentX;
                const threshold = 50;
                
                if (Math.abs(deltaX) > threshold) {
                    if (deltaX > 0) {
                        nextProfile();
                    } else {
                        prevProfile();
                    }
                }
            });
        }

        // Generate profile dots
        function generateProfileDots() {
            const dotsContainer = document.getElementById('profile_dots');
            let dotsHtml = '';
            
            for (let i = 0; i < profiles.length; i++) {
                dotsHtml += `<div class="dot ${i === currentProfileIndex ? 'active' : ''}" onclick="goToProfile(${i})"></div>`;
            }
            
            dotsContainer.innerHTML = dotsHtml;
        }

        // Navigate to specific profile
        function goToProfile(index) {
            currentProfileIndex = index;
            loadProfileCarousel();
        }

        // Navigate between profiles
        function prevProfile() {
            if (currentProfileIndex > 0) {
                currentProfileIndex--;
                loadProfileCarousel();
            }
        }

        function nextProfile() {
            if (currentProfileIndex < profiles.length - 1) {
                currentProfileIndex++;
                loadProfileCarousel();
            }
        }

        // Select profile
        function selectProfile(userId, username) {
            console.log('Profile selected:', userId, username);
            selectedUserId = userId;
            document.getElementById('selected_user_id').value = userId;
            document.getElementById('btn_step2').disabled = false;
            
            // Update visual selection
            document.querySelectorAll('.profile-item').forEach(item => {
                item.classList.remove('active');
            });
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }
        }

        // Show selected user in step 3
        function showSelectedUser() {
            const profile = profiles.find(p => p.id == selectedUserId);
            if (profile) {
                const avatarSrc = profile.profile_image && profile.profile_image !== 'null' 
                    ? `../${profile.profile_image}` 
                    : '';
                
                document.getElementById('selected_user_display').innerHTML = `
                    <div class="profile-avatar">
                        ${avatarSrc ? `<img src="${avatarSrc}" alt="${profile.username}">` : '<i class="fas fa-user"></i>'}
                    </div>
                    <div class="profile-name">${profile.username}</div>
                `;
            }
        }

        // Update profile count
        function updateProfileCount(count) {
            document.getElementById('profile_count').textContent = `${count} saved profile${count !== 1 ? 's' : ''}`;
        }

        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Adjust brightness for theme colors
        function adjustBrightness(hex, percent) {
            const num = parseInt(hex.replace("#", ""), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
        }

        // Navigate between schools - carousel style (updated)
        function prevSchool() {
            const schoolCount = schools.length > 0 ? schools.length : 2;
            if (currentSchoolIndex > 0) {
                currentSchoolIndex--;
                updateSchoolCarousel();
            }
        }

        function nextSchool() {
            const schoolCount = schools.length > 0 ? schools.length : 2;
            if (currentSchoolIndex < schoolCount - 1) {
                currentSchoolIndex++;
                updateSchoolCarousel();
            }
        }

        // Navigate to specific school (for dots)
        function goToSchool(index) {
            currentSchoolIndex = index;
            updateSchoolCarousel();
        }

        // Update school carousel display - like profile carousel
        function updateSchoolCarousel() {
            const wrapper = document.getElementById('school_slides_wrapper');
            if (!wrapper) return;
            
            const translateX = -currentSchoolIndex * 100;
            wrapper.style.transform = `translateX(${translateX}%)`;
            
            // Update dots
            const dots = document.querySelectorAll('.school-dot');
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSchoolIndex);
            });
            
            // Update navigation arrows
            const prevArrow = document.querySelector('.carousel-nav.prev');
            const nextArrow = document.querySelector('.carousel-nav.next');
            const schoolCount = schools.length > 0 ? schools.length : 2;
            
            if (prevArrow) prevArrow.style.opacity = currentSchoolIndex === 0 ? '0.5' : '1';
            if (nextArrow) nextArrow.style.opacity = currentSchoolIndex === schoolCount - 1 ? '0.5' : '1';
        }

        // Initialize school carousel on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the school carousel
            updateSchoolCarousel();
            
            // Add touch/swipe support for schools
            const carousel = document.getElementById('school_carousel');
            if (carousel) {
                let startX, currentX, isDragging = false;
                
                carousel.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                    isDragging = true;
                });
                
                carousel.addEventListener('touchmove', (e) => {
                    if (!isDragging) return;
                    currentX = e.touches[0].clientX;
                });
                
                carousel.addEventListener('touchend', (e) => {
                    if (!isDragging) return;
                    isDragging = false;
                    
                    const deltaX = startX - currentX;
                    const threshold = 50;
                    
                    if (Math.abs(deltaX) > threshold) {
                        if (deltaX > 0) {
                            nextSchool();
                        } else {
                            prevSchool();
                        }
                    }
                });
            }
        });

        
    </script>
    <!-- Add footer -->
        <!-- FOOTER HTML -->
           
 <footer class="footer" role="contentinfo" aria-label="Footer">
    <span class="policy-item" tabindex="0">
      <a href="#" class="footer-link" data-key="privacy">Privacy Policy</a>
      <div class="hover-modal" role="tooltip">
       <!-- Privacy Policy -->
        <p>The system collects only necessary information such as name, ID number, and attendance logs for the sole purpose of monitoring and recording attendance. All personal data is stored securely and will not be shared with third parties without consent, except when required by law. Users are assured that their information will be used responsibly and strictly for administrative purposes related to attendance tracking.</p>
      </div>
    </span>

    <span class="policy-item" tabindex="0">
      <a href="#" class="footer-link" data-key="terms">Terms &amp; Policies</a>
      <div class="hover-modal" role="tooltip">
       <!-- Terms &amp; Policies -->
        <p>Users agree to provide accurate information when registering or scanning their QR code. Any misuse of the system, such as attempting to scan on behalf of another individual or providing false details, is strictly prohibited. The administration reserves the right to review, suspend, or revoke access to the system in cases of policy violations. Continued use of the system signifies acceptance of these terms and conditions, which are subject to updates as needed to improve the service.</p>
      </div>
    </span>

    <span class="policy-item" tabindex="0">
      <a href="#" class="footer-link" data-key="community">Community Standards</a>
      <div class="hover-modal" role="tooltip">
        <!-- Community Standards -->
        <p>To maintain fairness and integrity, all users are expected to follow proper guidelines when using the QR Attendance Monitoring System. This includes scanning attendance honestly, respecting the privacy of others, and avoiding any actions that may disrupt the accuracy of records. The system is designed to promote accountability and transparency, and every member of the community is encouraged to uphold these values. Respectful use of the system ensures a reliable and trustworthy attendance record for everyone.</p>
      </div>
    </span>

    <span class="policy-item" tabindex="0">
        <a href="#" class="footer-link" data-key="community">About Us</a>
        <div class="hover-modal" role="tooltip">
            <div class="left">
            <strong>A Capstone Project Developed By:</strong>
            San Pedro City Polytechnic College <br>
            BSIT - 402 | GROUP 1<br>
           <br>
            <ul>
                Barcelona, Christian Danry<br>
                Bayot, David Joshua<br>
                Bismar, John Carl<br>
                Escallente, Alexander Joerenz<br>
                Lucenecio, Ma. Melissa<br>
                Pinedes, Redgine<br>
            </ul>
            </div> <br>
            <div class="separator"></div>
            <div class="right">
           Qnnect © 2025
            | To God be the Glory <br>
            </div>
        </div>
    </span>

    

    <span class="flex-spacer" aria-hidden="true"></span>
    <span class="app-version" title="Application Version">Current Version: 1.3.5</span>

  </footer>

  <!-- Overlay for click (pop-up full content) -->
  <div id="overlay" class="overlay" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="overlay-panel" role="document">
      <button class="close-btn" id="overlayClose" aria-label="Close">&times;</button>
      <h2 id="overlayTitle">Title</h2>
      <div id="overlayBody" class="body-text"></div>
    </div>
  </div>

</body>
</html>