<?php
// Force fix session loading issue
session_start();
require_once('conn/db_connect.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Force Fix Session</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>🚀 Force Fix Session Loading</h2>
        
        <div class='alert alert-warning'>
            <h5>🚨 EMERGENCY FIX</h5>
            <p>This will force load session data from database and redirect to main page to fix the 'No Active Session' issue.</p>
        </div>
        
        <div class='row'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Force Fix for School ID</h5>
                    </div>
                    <div class='card-body'>
                        <form method='post'>
                            <div class='form-group mb-3'>
                                <label>Select School ID to force fix:</label>
                                <select name='force_school_id' class='form-control'>
                                    <option value='1'>School ID 1</option>
                                    <option value='2'>School ID 2</option>
                                    <option value='3'>School ID 3</option>
                                    <option value='4'>School ID 4</option>
                                    <option value='5'>School ID 5</option>
                                </select>
                            </div>
                            <button type='submit' name='force_fix' class='btn btn-danger'>
                                <i class='fas fa-bolt'></i> Force Fix & Redirect
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>What This Does</h5>
                    </div>
                    <div class='card-body'>
                        <ol>
                            <li>Loads data from database</li>
                            <li>Forces session variables to be set</li>
                            <li>Redirects to main page</li>
                            <li>Should show active session immediately</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>";

if (isset($_POST['force_fix']) && $_POST['force_school_id']) {
    $school_id = intval($_POST['force_school_id']);
    
    echo "<div class='card mt-4'>
        <div class='card-header'>
            <h5>Force Fixing Session for School ID: $school_id</h5>
        </div>
        <div class='card-body'>";
    
    try {
        // Step 1: Set school_id in session
        $_SESSION['school_id'] = $school_id;
        echo "<p style='color: green;'>✅ Set \$_SESSION['school_id'] = $school_id</p>";
        
        // Step 2: Load class time from database
        $query = "SELECT * FROM class_time_settings WHERE school_id = ? ORDER BY updated_at DESC LIMIT 1";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Step 3: Force set session variables
            $_SESSION['class_start_time'] = $row['start_time'];
            $_SESSION['class_start_time_formatted'] = date('h:i A', strtotime($row['start_time']));
            
            echo "<p style='color: green;'>✅ Loaded class time from database:</p>";
            echo "<p>• Start Time: " . $row['start_time'] . "</p>";
            echo "<p>• Formatted: " . $_SESSION['class_start_time_formatted'] . "</p>";
            
            // Step 4: Load teacher data
            $teacher_query = "SELECT teacher_username, subject, section FROM teacher_schedules WHERE school_id = ? AND status = 'active' ORDER BY updated_at DESC LIMIT 1";
            $teacher_stmt = $conn_qr->prepare($teacher_query);
            $teacher_stmt->bind_param("i", $school_id);
            $teacher_stmt->execute();
            $teacher_result = $teacher_stmt->get_result();
            
            if ($teacher_result->num_rows > 0) {
                $teacher_row = $teacher_result->fetch_assoc();
                $_SESSION['current_instructor_id'] = $teacher_row['teacher_username'];
                $_SESSION['current_subject'] = $teacher_row['subject'];
                $_SESSION['current_section'] = $teacher_row['section'];
                
                echo "<p style='color: green;'>✅ Loaded teacher data:</p>";
                echo "<p>• Instructor: " . $teacher_row['teacher_username'] . "</p>";
                echo "<p>• Subject: " . $teacher_row['subject'] . "</p>";
                echo "<p>• Section: " . $teacher_row['section'] . "</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ No teacher schedules found</p>";
            }
            
            // Step 5: Verify session variables are set
            echo "<h6>🔐 Step 5: Verify Session Variables</h6>";
            echo "<p>• \$_SESSION['school_id'] = '" . $_SESSION['school_id'] . "'</p>";
            echo "<p>• \$_SESSION['class_start_time'] = '" . $_SESSION['class_start_time'] . "'</p>";
            echo "<p>• \$_SESSION['class_start_time_formatted'] = '" . $_SESSION['class_start_time_formatted'] . "'</p>";
            
            // Step 6: Test the condition
            $active_class_time = $_SESSION['class_start_time'];
            if ($active_class_time) {
                echo "<p style='color: green;'>✅ \$active_class_time is truthy: '$active_class_time'</p>";
                echo "<p style='color: green;'>✅ Should show: <strong>ACTIVE SESSION</strong></p>";
            } else {
                echo "<p style='color: red;'>❌ \$active_class_time is falsy</p>";
            }
            
            echo "<div class='alert alert-success mt-3'>
                <h6>🎉 Force Fix Applied Successfully!</h6>
                <p>Session variables have been forced to load from database.</p>
                <p><strong>Redirecting to main page in 3 seconds...</strong></p>
            </div>";
            
            // Redirect to main page after 3 seconds
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 3000);
            </script>";
            
        } else {
            echo "<p style='color: red;'>❌ No class time settings found for School ID $school_id</p>";
            echo "<p><strong>Solution:</strong> Add sample data first using the populate script.</p>";
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>
            <h6>❌ Error occurred:</h6>
            <p>" . $e->getMessage() . "</p>
        </div>";
    }
    
    echo "</div></div>";
}

echo "<div class='card mt-4'>
    <div class='card-header'>
        <h5>Quick Actions</h5>
    </div>
    <div class='card-body'>
        <a href='populate_school_data.php' class='btn btn-success me-2'>
            <i class='fas fa-plus'></i> Add Sample Data
        </a>
        <a href='debug_active_session.php' class='btn btn-info me-2'>
            <i class='fas fa-search'></i> Debug Active Session
        </a>
        <a href='index.php' class='btn btn-primary'>
            <i class='fas fa-home'></i> Go to Main Page
        </a>
    </div>
</div>

</div>
</body>
</html>";
?> 