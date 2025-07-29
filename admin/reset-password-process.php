<?php
session_start();

$message = "";
$message_type = "";
$user_name = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the password and repeat-password fields are set
    if (!isset($_POST["password"]) || !isset($_POST["repeat-password"])) {
        $message = "Password fields are missing.";
        $message_type = "danger";
    } else {
        $token = $_POST["token"];  // Token is now sent via POST from the form
        $token_hash = hash("sha256", $token); 

        // Database connection
        $hostName = "localhost";
        $dbUser = "root";
        $dbPassword = "";
        $dbName = "login_register";
        $conn = new mysqli($hostName, $dbUser, $dbPassword, $dbName);

        // Enable exception handling for MySQLi
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        if ($conn->connect_error) {
            $message = "Something went wrong with the database connection.";
            $message_type = "danger";
        } else {
            try {
                $sql = "SELECT id, full_name, email, reset_token_hash_expires_at FROM users WHERE reset_token_hash = ?";
                $stmt = $conn->prepare($sql); 
                $stmt->bind_param("s", $token_hash);
                $stmt->execute();
                $stmt->store_result(); // Store the result set

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($user_id, $full_name, $email, $expiration);
                    $stmt->fetch(); // Fetch the result

                    $user_name = $full_name;
                    $current_time = new DateTime();  // Current time
                    $expiration_time = new DateTime($expiration);  // Expiration time from the database

                    if ($expiration_time > $current_time) {
                        // Now check password validity
                        if (strlen($_POST["password"]) < 8 || preg_match('/[^a-zA-Z0-9]/', $_POST["password"])) {
                            if (strlen($_POST["password"]) < 8) {
                                $message = "Password must be at least 8 characters long and must contain letters and numbers only.";
                            } 
                            $message_type = "danger";
                        } else if ($_POST["password"] !== $_POST["repeat-password"]) {
                            $message = "Passwords do not match.";
                            $message_type = "danger";
                        } else if (empty($_POST["password"]) || empty($_POST["repeat-password"])) {
                            $message = "All fields are required.";
                            $message_type = "danger";
                        } else {
                            // Hash the password and update the database
                            $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

                            $stmt->free_result(); // Free the result set

                            $update_sql = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_hash_expires_at = NULL WHERE id = ?";
                            $update_stmt = $conn->prepare($update_sql);
                            $update_stmt->bind_param("si", $password_hash, $user_id);
                            $update_result = $update_stmt->execute();
                            
                            if ($update_result) {
                                $message = "Password has been reset successfully. You can now log in with your new password.";
                                $message_type = "success";
                            } else {
                                $message = "Failed to update password. Please try again.";
                                $message_type = "danger";
                            }
                            $update_stmt->close();
                        }
                    } else {
                        $message = "This token has expired. Please request a new password reset.";
                        $message_type = "danger";
                    }
                } else {
                    $message = "Invalid token. Please request a new password reset.";
                    $message_type = "danger";
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $message = "Database error: " . $e->getMessage();
                $message_type = "danger";
            }
            // Safely close the connection
            if (isset($conn) && $conn instanceof mysqli && @$conn->ping()) {
                $conn->close();
            }
        }
    }
} else {
    // Redirect if accessed directly without POST data
    header("Location: forgotPassword.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #098744;
        }
        
        .main-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 1200px;
            width: 90%;
            height: 90vh;
            align-items: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .image-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            max-height: 550px;
            overflow: hidden;
        }
        
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            max-height: 510px;
        }
        
        .result-container {
            background-color: rgba(144, 238, 144, 0.8);
            border-radius: 15px;
            padding: 30px;
            width: 400px;
            aspect-ratio: 1/1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
        }
        
        .logo-container {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .logo-container img {
            width: 100px;
            height: 100px;
        }
        
        .title {
            color: #098744;
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
        }
        
        .message-box {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            width: 100%;
            text-align: center;
        }
        
        .success-icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .error-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .user-name {
            font-weight: bold;
            color: #098744;
            margin-bottom: 10px;
        }
        
        .btn-action {
            background-color: #4285F4;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 16px;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .btn-action:hover {
            background-color: #3367d6;
            color: white;
        }
        
        .password-container {
            position: relative;
            width: 100%;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .image-container {
                display: none;
            }
            
            .result-container {
                width: 90%;
                aspect-ratio: auto;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Image container on the left -->
        <div class="image-container">
            <img src="../admin/image/reset-success.png" alt="School Logo" class="img-fluid">
        </div>
        
        <!-- Result container on the right -->
        <div class="result-container">
            <div class="logo-container">
                <img src="../admin/image/SPCPC-logo-trans.png" alt="School Logo">
            </div>
            
            <div class="title">Password Reset Result</div>
            
            <div class="message-box">
                <?php if($message_type == "success"): ?>
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <?php if(!empty($user_name)): ?>
                        <div class="user-name">Hello, <?php echo htmlspecialchars($user_name); ?>!</div>
                    <?php endif; ?>
                    <p><?php echo $message; ?></p>
                <?php else: ?>
                    <div class="error-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <p><?php echo $message; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <?php if($message_type == "success"): ?>
                    <a href="login.php" class="btn-action">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                <?php else: ?>
                    <a href="forgotPassword.php" class="btn-action">
                        <i class="fas fa-redo"></i> Try Again
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>
