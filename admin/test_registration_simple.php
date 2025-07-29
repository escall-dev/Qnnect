<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Registration Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #098744;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #076633;
        }
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <h1>🧪 Simple Registration Test</h1>
    
    <?php
    if ($_POST && isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $repeat_password = $_POST['repeat_password'];
        $school_id = $_POST['school_id'] ?? 1;
        
        $errors = [];
        
        // Basic validation
        if (empty($username)) $errors[] = "Username required";
        if (empty($email)) $errors[] = "Email required";
        if (empty($password)) $errors[] = "Password required";
        if ($password !== $repeat_password) $errors[] = "Passwords don't match";
        
        if (empty($errors)) {
            // Try to register
            require_once "database.php";
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'admin';
            $profile_image = 'image/SPCPC-logo-trans.png';
            
            // Check if we need to handle missing columns
            $sql = "INSERT INTO users (email, password, username, profile_image";
            $values = "VALUES (?, ?, ?, ?";
            $types = "ssss";
            $params = [$email, $passwordHash, $username, $profile_image];
            
            // Check if role column exists
            $check_role = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
            if (mysqli_num_rows($check_role) > 0) {
                $sql .= ", role";
                $values .= ", ?";
                $types .= "s";
                $params[] = $role;
            }
            
            // Check if school_id column exists
            $check_school = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'school_id'");
            if (mysqli_num_rows($check_school) > 0) {
                $sql .= ", school_id";
                $values .= ", ?";
                $types .= "i";
                $params[] = $school_id;
            }
            
            $sql .= ") " . $values . ")";
            
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                if (mysqli_stmt_execute($stmt)) {
                    echo "<div class='alert alert-success'>✅ Registration successful! <a href='login.php'>Login here</a></div>";
                } else {
                    echo "<div class='alert alert-danger'>❌ Registration failed: " . mysqli_error($conn) . "</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>❌ Database error: " . mysqli_error($conn) . "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>❌ Errors: " . implode(", ", $errors) . "</div>";
        }
    }
    ?>
    
    <form method="post">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>School:</label>
            <select name="school_id">
                <option value="1">SPCPC</option>
                <option value="2">Computer Site Inc.</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
            <small>Letters and numbers only</small>
        </div>
        
        <div class="form-group">
            <label>Repeat Password:</label>
            <input type="password" name="repeat_password" required>
        </div>
        
        <button type="submit" name="register">Register</button>
    </form>
    
    <hr>
    <p><a href="registration.php">← Back to Full Registration Form</a></p>
    <p><a href="login.php">Go to Login</a></p>
    <p><a href="../setup_role_system.php">Run Setup Script</a></p>
</body>
</html>