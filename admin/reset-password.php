<?php

if (isset($_POST["email"])) {
    $email = $_POST["email"];
}

$token = isset($_GET["token"]) ? trim($_GET["token"]) : null;
if (!$token) {
    die("Token is missing. Please check the link you received.");
}

$token_hash = hash("sha256", $token);

// Database connection
$hostName = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "login_register";
$conn = mysqli_connect($hostName, $dbUser, $dbPassword, $dbName);

if (!$conn) {
    die("Something went wrong with the database connection");
}

$sql = "SELECT id, full_name, email, reset_token_hash_expires_at FROM users WHERE reset_token_hash = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Failed to prepare statement: " . $conn->error);
}
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$stmt->bind_result($user_id, $full_name, $email, $expiration);

$token_valid = false;
$error_message = "";

if ($stmt->fetch()) {
    $current_time = new DateTime();  // Current time
    $expiration_time = new DateTime($expiration);  // Expiration time from the database

    if ($expiration_time > $current_time) {
        $token_valid = true;
    } else {
        $error_message = "This token has expired. Please request a new password reset.";
    }
} else {
    $error_message = "Invalid token. Please request a new password reset.";
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
        
        .reset-password-container {
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
        
        .form-control {
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            border: none;
            font-size: 16px;
            width: 100%;
        }
        
        .password-container {
            position: relative;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        .btn-reset {
            background-color: #4285F4;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            font-size: 16px;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        
        .login-link {
            color: #098744;
            text-decoration: none;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
        
        .alert {
            margin-bottom: 20px;
            width: 100%;
        }
        
        .title {
            color: #098744;
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .image-container {
                display: none;
            }
            
            .reset-password-container {
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
            <img src="../admin/image/fgot-pass.png" alt="School Logo" class="img-fluid">
        </div>
        
        <!-- Reset Password container on the right -->
        <div class="reset-password-container">
            <?php if (!$token_valid): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="reset-pass.php" class="btn btn-primary">Request New Reset Link</a>
                    <a href="login.php" class="login-link d-block mt-3"></a>`
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            <?php else: ?>
                <div class="logo-container">
                    <img src="../admin/image/SPCPC-logo-trans.png" alt="School Logo">
                </div>
                
                <div class="title">Reset Password</div>
                
                <!-- Password Requirements Reminder -->
                <div class="alert alert-info" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> <strong>Password Requirements:</strong><br>
                    • Must be at least 8 characters long<br>
                    • Must contain letters and numbers only<br>
                    • No special characters allowed
                </div>
                
                <!-- Form to reset the password - preserving the original form action -->
                <form action="reset-password-process.php" method="post" style="width: 100%;">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required>
                        <span class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                            <i class="fa fa-eye" id="toggleIcon1"></i>
                        </span>
                    </div>
                    
                    <div class="password-container">
                        <input type="password" class="form-control" id="repeat-password" name="repeat-password" placeholder="Confirm New Password" required>
                        <span class="password-toggle" onclick="togglePassword('repeat-password', 'toggleIcon2')">
                            <i class="fa fa-eye" id="toggleIcon2"></i>
                        </span>
                    </div>
                    
                    <button type="submit" class="btn btn-reset" name="submit">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </form>
                
                <div class="text-center mt-2">
                    <a href="login.php" class="login-link">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, iconId) {
            const passwordField = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
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
