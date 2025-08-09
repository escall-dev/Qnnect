<?php
// Start a session if not already started
session_start();

$message = "";
$message_type = "";

if(isset($_POST["email"])) {
    $email = $_POST["email"];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "danger";
    } else {
    // generate a random byte for token and convert to string using bin2hex
    $token = bin2hex(random_bytes(64));
    
    $token_hash = hash("sha256", $token);

    // set the time zone
    date_default_timezone_set('Asia/Manila');
    // expiry
    $expiry = date("Y-m-d H:i:s", time() + 60 * 30);
    
    // $conn = include __DIR__ . '/database.php';
    $hostName = "localhost";
    $dbUser = "root";
    $dbPassword = "";
    $dbName = "login_register";
    $conn = mysqli_connect($hostName, $dbUser, $dbPassword, $dbName);

    if(!$conn){
            $message = "Something went wrong with the database connection.";
            $message_type = "danger";
        } else {
    $sql_check = "SELECT id FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Email exists, proceed with token update
        $sql = "UPDATE users
                SET reset_token_hash = ?, reset_token_hash_expires_at = ?
                WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $token_hash, $expiry, $email);
        $stmt->execute();
    
        // Send the email
    if ($conn->affected_rows) {
        $mail = require 'mailer.php';

        $mail->setFrom('your_email@gmail.com', 'SPCPC Password Reset');
        $mail->addAddress($email);
        $mail->Subject = "Password Reset";
        $mail->Body = <<<END

                    Click <a href="http://localhost/Qnnect/admin/reset-password.php?token=$token">Here</a> to reset your password.
                    
                    END;

                    try {
                        $mail->send();
                        $message = "Password reset link has been sent to your email. Please check your inbox.";
                        $message_type = "success";
                    } catch(Exception $e) {
                        $message = "Message could not be sent. Mailer error: {$mail->ErrorInfo}";
                        $message_type = "danger";
                    }
                } else {
                    $message = "Failed to update reset token. Please try again.";
                    $message_type = "danger";
                }
            } else {
                $message = "Email not found in our records.";
                $message_type = "danger";
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
    <title>Password Reset Request</title>
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
        
        .message-container {
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
        
        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .image-container {
                display: none;
            }
            
            .message-container {
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
            <img src="../admin/image/reset-pass.png" alt="School Logo" class="img-fluid">
        </div>
        
        <!-- Message container on the right -->
        <div class="message-container">
            <div class="logo-container">
                <img src="../admin/image/SPCPC-logo-trans.png" alt="School Logo">
            </div>
            
            <div class="title">Password Reset Request</div>
            
            <div class="message-box">
                <?php if($message_type == "success"): ?>
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
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
</body>
</html>