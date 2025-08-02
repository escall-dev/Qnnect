<?php
// Fix session loading logic
session_start();
require_once('conn/db_connect.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Session Loading</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>🔧 Fix Session Loading Logic</h2>
        
        <div class='alert alert-info'>
            <h5>Problem Identified:</h5>
            <p>The session loading logic loads data from the database but doesn't set the session variables. This causes School ID 2 to show 'No Active Session' even when data exists.</p>
        </div>
        
        <div class='row'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Fix Session for School ID</h5>
                    </div>
                    <div class='card-body'>
                        <form method='post'>
                            <div class='form-group mb-3'>
                                <label>Select School ID to fix:</label>
                                <select name='fix_school_id' class='form-control'>
                                    <option value='2'>School ID 2</option>
                                    <option value='3'>School ID 3</option>
                                    <option value='4'>School ID 4</option>
                                    <option value='5'>School ID 5</option>
                                </select>
                            </div>
                            <button type='submit' name='fix_session' class='btn btn-warning'>
                                <i class='fas fa-tools'></i> Fix Session Loading
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>What This Fix Does</h5>
                    </div>
                    <div class='card-body'>
                        <ol>
                            <li>Loads class time data from database</li>
                            <li>Sets session variables properly</li>
                            <li>Enables active session display</li>
                            <li>Fixes 'No Active Session' issue</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>";

if (isset($_POST['fix_session']) && $_POST['fix_school_id']) {
    $school_id = intval($_POST['fix_school_id']);
    $_SESSION['school_id'] = $school_id;
    
    echo "<div class='card mt-4'>
        <div class='card-header'>
            <h5>Fixing Session for School ID: $school_id</h5>
        </div>
        <div class='card-body'>";
    
    try {
        // 1. Load class time settings from database
        echo "<h6>📅 Step 1: Loading class time settings from database</h6>";
        $class_time_query = "SELECT * FROM class_time_settings WHERE school_id = ? ORDER BY updated_at DESC LIMIT 1";
        $stmt = $conn_qr->prepare($class_time_query);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $class_time_data = $result->fetch_assoc();
            echo "<p style='color: green;'>✅ Found class time settings:</p>";
            echo "<p>• Start Time: " . $class_time_data['start_time'] . "</p>";
            echo "<p>• Updated: " . $class_time_data['updated_at'] . "</p>";
            
            // 2. Set session variables
            echo "<h6>🔐 Step 2: Setting session variables</h6>";
            $_SESSION['class_start_time'] = $class_time_data['start_time'];
            $_SESSION['school_id'] = $school_id;
            
            // Format the time for display
            $_SESSION['class_start_time_formatted'] = date('h:i A', strtotime($class_time_data['start_time']));
            
            echo "<p style='color: green;'>✅ Session variables set:</p>";
            echo "<p>• \$_SESSION['class_start_time'] = '" . $_SESSION['class_start_time'] . "'</p>";
            echo "<p>• \$_SESSION['school_id'] = '" . $_SESSION['school_id'] . "'</p>";
            echo "<p>• \$_SESSION['class_start_time_formatted'] = '" . $_SESSION['class_start_time_formatted'] . "'</p>";
            
            // 3. Load teacher schedules to set instructor and subject
            echo "<h6>👨‍🏫 Step 3: Loading teacher schedules</h6>";
            $teacher_query = "SELECT teacher_username, subject, section FROM teacher_schedules WHERE school_id = ? AND status = 'active' ORDER BY updated_at DESC LIMIT 1";
            $stmt = $conn_qr->prepare($teacher_query);
            $stmt->bind_param("i", $school_id);
            $stmt->execute();
            $teacher_result = $stmt->get_result();
            
            if ($teacher_result->num_rows > 0) {
                $teacher_data = $teacher_result->fetch_assoc();
                $_SESSION['current_instructor_id'] = $teacher_data['teacher_username'];
                $_SESSION['current_subject'] = $teacher_data['subject'];
                $_SESSION['current_section'] = $teacher_data['section'];
                
                echo "<p style='color: green;'>✅ Teacher data loaded:</p>";
                echo "<p>• Instructor: " . $teacher_data['teacher_username'] . "</p>";
                echo "<p>• Subject: " . $teacher_data['subject'] . "</p>";
                echo "<p>• Section: " . $teacher_data['section'] . "</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ No teacher schedules found</p>";
            }
            
            echo "<div class='alert alert-success mt-3'>
                <h6>🎉 Session fixed successfully!</h6>
                <p>School ID $school_id should now show an active session instead of 'No Active Session'.</p>
                <p><strong>Next:</strong> Go to the main page to see the fix in action.</p>
            </div>";
            
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
        <a href='debug_session_logic.php' class='btn btn-info me-2'>
            <i class='fas fa-search'></i> Debug Session Logic
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