<?php
// Start a session if not already started
session_start();

// Process form submission
if(isset($_POST["submit"])){
    $email = $_POST["email"];
    
    $errors = array();
    
    // Validate email
    if(empty($email)) {
        array_push($errors, "Email is required");
    }
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        array_push($errors, "Email is not valid");
    }
    
    // If no errors, proceed with password reset
    if(count($errors) > 0) {
        foreach($errors as $error) {
            $error_message = $error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        
        .forgot-password-container {
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
            margin-bottom: 20px;
            border: none;
            font-size: 16px;
            width: 100%;
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
        
        .back-link {
            color: #098744;
            text-decoration: none;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .back-link:hover {
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
            
            .forgot-password-container {
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
        
        <!-- Forgot Password container on the right -->
        <div class="forgot-password-container">
            <?php if(isset($error_message)): ?>
                <div class='alert alert-danger'><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($success_message)): ?>
                <div class='alert alert-success'><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <div class="logo-container">
                <img src="../admin/image/SPCPC-logo-trans.png" alt="School Logo">
            </div>
            
            <div class="title">Forgot Password</div>
            
            <form action="send-reset.php" method="post" style="width: 100%;">
                <div class="mb-3" style="width: 100%;">
                    <input type="email" class="form-control" name="email" placeholder="Enter your email" autocomplete="off" required>
                </div>
                
                <button type="submit" class="btn btn-reset" name="submit">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
            
            <div class="back-links" style="text-align: center; margin-top: 10px;">
                <a href="./login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
  </div>
</body>
</html>