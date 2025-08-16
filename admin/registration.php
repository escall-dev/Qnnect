<?php
// Start a session if not already started
session_start();

// Input sanitization function
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Username validation function
function validate_username($username) {
    return preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username);
}

// Email validation function
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Password validation function
function validate_password($password) {
    // Only letters and numbers
    return preg_match('/^[a-zA-Z0-9]+$/', $password);
}

// Process registration form submission
if(isset($_POST["register"])){
    $errors = array();
    
    // Sanitize and validate inputs
    $username = sanitize_input($_POST["name"]);
    $email = sanitize_input($_POST["email"]);
    $password = $_POST["password"]; // Don't sanitize password
    $repeat_password = $_POST["repeat_password"];
    $school_id = isset($_POST["school_id"]) ? (int)$_POST["school_id"] : null;
    
    // Validate username
    if(empty($username)) {
        $errors[] = "Username is required";
    } elseif(!validate_username($username)) {
        $errors[] = "Username must be 3-20 characters and contain only letters, numbers, underscore, or hyphen";
    }
    
    // Validate email
    if(empty($email)) {
        $errors[] = "Email is required";
    } elseif(!validate_email($email)) {
        $errors[] = "Email is not valid";
    }
    
    // Validate password
    if(empty($password)) {
        $errors[] = "Password is required";
    } elseif(!validate_password($password)) {
        $errors[] = "Password should only contain letters and numbers.";
    }
    
    // Validate password match
    if($password !== $repeat_password) {
        $errors[] = "Passwords do not match";
    }
    
    require_once "database.php";
    
    // Validate school selection (only if schools table exists)
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'schools'");
    if ($table_check && mysqli_num_rows($table_check) > 0) {
        if(empty($school_id)) {
            $errors[] = "Please select a school";
        }
    } else {
        // If schools table doesn't exist, set school_id to null for now
        $school_id = null;
    }

    // Handle profile image upload with security checks
    $profile_image = "image/SPCPC-logo-trans.png"; // Default image
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = sanitize_input($_FILES['profile_image']['name']);
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filesize = $_FILES['profile_image']['size'];
        $max_size = 5 * 1024 * 1024; // 5MB limit
        
        // Validate file
        if(!in_array($filetype, $allowed)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG & GIF allowed";
        } elseif($filesize > $max_size) {
            $errors[] = "File size must be less than 5MB";
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/profile_images';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $new_filename = "profile_" . time() . "_" . bin2hex(random_bytes(8)) . "." . $filetype;
            $upload_path = $upload_dir . "/" . $new_filename;
            
            // Verify file is a valid image
            if(!getimagesize($_FILES['profile_image']['tmp_name'])) {
                $errors[] = "Invalid image file";
            } elseif(!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
            } else {
                $profile_image = "uploads/profile_images/" . $new_filename;
            }
        }
    }
    
    require_once "database.php";
    
    // Check if email already exists using prepared statement
    $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if(mysqli_num_rows($result) > 0) {
            $errors[] = "Email already exists!";
        }
        mysqli_stmt_close($stmt);
    }
    
    // If there are errors, prepare the error message
    if(count($errors) > 0) {
        $error_message = "<ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
    } else {
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user using prepared statement with role and school_id
        $role = 'admin'; // Default role for new registrations
        $sql = "INSERT INTO users (email, password, username, profile_image, role, school_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssi", $email, $passwordHash, $username, $profile_image, $role, $school_id);
            if(mysqli_stmt_execute($stmt)) {
                $success_message = "Registration successful! You can now <a href='login.php'>log in here</a>.";
            } else {
                $error_message = "Registration failed. Please try again. Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database error. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
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
        
        .registration-container {
            background-color: rgba(144, 238, 144, 0.8);
            border-radius: 15px;
            padding: 30px;
            width: 450px;
            max-height: 90vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 auto;
        }
        
        .logo-container {
            margin: 20px 0 30px 0;
            text-align: center;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .school-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin: 0 auto;
            display: block;
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
        
        .btn-register {
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
        
        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .image-container {
                display: none;
            }
            
            .registration-container {
                width: 90%;
                max-height: 80vh;
                padding: 20px;
            }
        }

        /* Add custom scrollbar styling */
        .registration-container::-webkit-scrollbar {
            width: 8px;
        }

        .registration-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .registration-container::-webkit-scrollbar-thumb {
            background: #098744;
            border-radius: 4px;
        }

        .registration-container::-webkit-scrollbar-thumb:hover {
            background: #076633;
        }

        /* Form container to add some spacing */
        .form-wrapper {
            width: 100%;
            padding-top: 10px;
        }

        /* Update preview image container */
        .preview-container {
            text-align: center;
            margin: 15px 0;
        }

        #preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #098744;
            margin: 10px auto;
            display: block;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Image container on the left -->
        <div class="image-container">
            <img src="../admin/image/register-no-bg.png" alt="School Logo" class="img-fluid">
            </div>
           
        <!-- Registration container on the right -->
        <div class="registration-container">
            <?php if(isset($error_message)): ?>
                <div class='alert alert-danger'><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($success_message)): ?>
                <div class='alert alert-success'><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <div class="logo-container">
                <img src="../admin/image/SPCPC-logo-trans.png" alt="School Logo" class="school-logo">
            </div>
            
            <div class="form-wrapper">
                <form action="registration.php" method="post" enctype="multipart/form-data" autocomplete="off">
                    <div class="mb-3">
                        <input type="text" class="form-control" name="name" placeholder="Username" required>
                    </div>
                    
                    <div class="mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    
                    <div class="mb-3">
                        <select class="form-control" name="school_id">
                            <option value="">Select School (Optional)</option>
                            <?php
                            // Get schools from database
                            require_once "database.php";
                            
                            // Check if schools table exists first
                            $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'schools'");
                            if ($table_check && mysqli_num_rows($table_check) > 0) {
                                $schools_sql = "SELECT id, name FROM schools WHERE status = 'active' ORDER BY name";
                                $schools_result = mysqli_query($conn, $schools_sql);
                                if ($schools_result) {
                                    while ($school = mysqli_fetch_assoc($schools_result)) {
                                        echo "<option value='{$school['id']}'>{$school['name']}</option>";
                                    }
                                }
                            } else {
                                // If schools table doesn't exist, show default options
                                echo "<option value='1'>SPCPC</option>";
                                echo "<option value='2'>Computer Site Inc.</option>";
                            }
                            ?>
                        </select>
                        <?php 
                        $table_exists = $table_check && mysqli_num_rows($table_check) > 0;
                        if (!$table_exists): 
                        ?>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Using default schools. <a href="../role_system_index.php">Run setup for full features</a>
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profile Picture (Optional)</label>
                        <input type="file" class="form-control" name="profile_image" id="profile_image" accept="image/*" onchange="previewImage(this)">
                        <div class="preview-container">
                            <img id="preview" src="image/SPCPC-logo-trans.png" alt="Profile Preview">
                        </div>
                    </div>
                    
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <span class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                            <i class="fa fa-eye" id="toggleIcon1"></i>
                        </span>
                    </div>
                    
                    <div class="password-container">
                        <input type="password" class="form-control" id="repeat_password" name="repeat_password" placeholder="Repeat Password" required>
                        <span class="password-toggle" onclick="togglePassword('repeat_password', 'toggleIcon2')">
                            <i class="fa fa-eye" id="toggleIcon2"></i>
                        </span>
                    </div>
                    
                    <button type="submit" class="btn btn-register" name="register">Register</button>
                </form>
            </div>
            
            <div class="login-link-container" style="text-align: center; margin-top: 10px;">
                <p style="color: #333; margin-bottom: 5px; font-size: 14px;">Already have an account?</p>
                <a href="./login.php" class="login-link">Login here</a>
            </div>
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

        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').setAttribute('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>