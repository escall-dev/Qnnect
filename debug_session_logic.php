<?php
// Debug script to check session logic for different school IDs
session_start();
require_once('conn/db_connect.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Session Logic Debug</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>🔍 Session Logic Debug</h2>
        
        <div class='alert alert-warning'>
            <h5>Problem: School ID 2 shows 'No Active Session' even with data</h5>
            <p>Let's debug the session checking logic to find the issue.</p>
        </div>
        
        <div class='row'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Test Session Logic</h5>
                    </div>
                    <div class='card-body'>
                        <form method='post'>
                            <div class='form-group mb-3'>
                                <label>Select School ID to test:</label>
                                <select name='test_school_id' class='form-control'>
                                    <option value='1'>School ID 1</option>
                                    <option value='2'>School ID 2</option>
                                    <option value='3'>School ID 3</option>
                                </select>
                            </div>
                            <button type='submit' name='test_logic' class='btn btn-primary'>
                                <i class='fas fa-search'></i> Test Session Logic
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Current Session</h5>
                    </div>
                    <div class='card-body'>
                        <p><strong>School ID:</strong> " . ($_SESSION['school_id'] ?? 'Not set') . "</p>
                        <p><strong>Class Start Time:</strong> " . ($_SESSION['class_start_time'] ?? 'Not set') . "</p>
                        <p><strong>Current Instructor:</strong> " . ($_SESSION['current_instructor_id'] ?? 'Not set') . "</p>
                        <p><strong>Current Subject:</strong> " . ($_SESSION['current_subject'] ?? 'Not set') . "</p>
                    </div>
                </div>
            </div>
        </div>";

if (isset($_POST['test_logic']) && $_POST['test_school_id']) {
    $school_id = intval($_POST['test_school_id']);
    $_SESSION['school_id'] = $school_id;
    
    echo "<div class='card mt-4'>
        <div class='card-header'>
            <h5>Session Logic Analysis for School ID: $school_id</h5>
        </div>
        <div class='card-body'>";
    
    // 1. Check class_time_settings table
    echo "<h6>📅 Step 1: Check class_time_settings table</h6>";
    $class_time_query = "SELECT * FROM class_time_settings WHERE school_id = ?";
    $stmt = $conn_qr->prepare($class_time_query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $class_time_result = $stmt->get_result();
    $class_time_count = $class_time_result->num_rows;
    
    echo "<p>Records found: <strong>$class_time_count</strong></p>";
    if ($class_time_count > 0) {
        while ($row = $class_time_result->fetch_assoc()) {
            echo "<p>• School ID: " . $row['school_id'] . " | Start Time: " . $row['start_time'] . " | Updated: " . $row['updated_at'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No class time settings found!</p>";
    }
    
    // 2. Check if session variables are set
    echo "<h6>🔐 Step 2: Check Session Variables</h6>";
    $session_vars = [
        'school_id' => $_SESSION['school_id'] ?? 'NOT SET',
        'class_start_time' => $_SESSION['class_start_time'] ?? 'NOT SET',
        'current_instructor_id' => $_SESSION['current_instructor_id'] ?? 'NOT SET',
        'current_subject' => $_SESSION['current_subject'] ?? 'NOT SET',
        'current_section' => $_SESSION['current_section'] ?? 'NOT SET'
    ];
    
    foreach ($session_vars as $var => $value) {
        $color = ($value === 'NOT SET') ? 'red' : 'green';
        echo "<p style='color: $color;'>• \$_SESSION['$var'] = <strong>$value</strong></p>";
    }
    
    // 3. Simulate the session checking logic from index.php
    echo "<h6>🧠 Step 3: Simulate Session Check Logic</h6>";
    
    // This is the logic from index.php that determines if there's an active session
    $has_active_session = false;
    $session_info = [];
    
    if (isset($_SESSION['class_start_time']) && !empty($_SESSION['class_start_time'])) {
        $has_active_session = true;
        $session_info['start_time'] = $_SESSION['class_start_time'];
        $session_info['instructor'] = $_SESSION['current_instructor_id'] ?? 'Not set';
        $session_info['subject'] = $_SESSION['current_subject'] ?? 'Not set';
        $session_info['section'] = $_SESSION['current_section'] ?? 'Not set';
        
        echo "<p style='color: green;'>✅ Session check: class_start_time is set</p>";
        echo "<p>• Start Time: " . $session_info['start_time'] . "</p>";
        echo "<p>• Instructor: " . $session_info['instructor'] . "</p>";
        echo "<p>• Subject: " . $session_info['subject'] . "</p>";
        echo "<p>• Section: " . $session_info['section'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Session check: class_start_time is NOT set</p>";
    }
    
    // 4. Check if we need to load session from database
    echo "<h6>💾 Step 4: Check Database for Active Session</h6>";
    
    if (!$has_active_session && $class_time_count > 0) {
        echo "<p style='color: orange;'>⚠️ Session variables not set but database has data!</p>";
        echo "<p>This means the session loading logic might be broken.</p>";
        
        // Try to load from database
        $load_query = "SELECT * FROM class_time_settings WHERE school_id = ? AND start_time IS NOT NULL ORDER BY updated_at DESC LIMIT 1";
        $stmt = $conn_qr->prepare($load_query);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $load_result = $stmt->get_result();
        
        if ($load_result->num_rows > 0) {
            $db_session = $load_result->fetch_assoc();
            echo "<p style='color: green;'>✅ Found database session:</p>";
            echo "<p>• Start Time: " . $db_session['start_time'] . "</p>";
            echo "<p>• Updated: " . $db_session['updated_at'] . "</p>";
            
            // Simulate setting session variables
            echo "<p style='color: blue;'>🔧 Would set session variables:</p>";
            echo "<p>• \$_SESSION['class_start_time'] = '" . $db_session['start_time'] . "'</p>";
            echo "<p>• \$_SESSION['school_id'] = '$school_id'</p>";
        }
    }
    
    // 5. Check teacher_schedules for this school
    echo "<h6>👨‍🏫 Step 5: Check Teacher Schedules</h6>";
    $teacher_query = "SELECT COUNT(*) as count FROM teacher_schedules WHERE school_id = ? AND status = 'active'";
    $stmt = $conn_qr->prepare($teacher_query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $teacher_result = $stmt->get_result();
    $teacher_count = $teacher_result->fetch_assoc()['count'];
    
    echo "<p>Active teacher schedules: <strong>$teacher_count</strong></p>";
    if ($teacher_count == 0) {
        echo "<p style='color: orange;'>⚠️ No teacher schedules found - this might affect dropdowns</p>";
    }
    
    // 6. Summary and recommendations
    echo "<h6>📋 Step 6: Summary & Recommendations</h6>";
    
    if ($has_active_session) {
        echo "<p style='color: green;'>✅ Session logic working correctly</p>";
    } else {
        echo "<p style='color: red;'>❌ Session logic issue detected</p>";
        
        if ($class_time_count > 0) {
            echo "<p style='color: orange;'>⚠️ Database has data but session variables not set</p>";
            echo "<p><strong>Recommendation:</strong> The session loading logic needs to be fixed.</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No data in database</p>";
            echo "<p><strong>Recommendation:</strong> Add sample data first.</p>";
        }
    }
    
    echo "</div></div>";
}

echo "<div class='card mt-4'>
    <div class='card-header'>
        <h5>Quick Actions</h5>
    </div>
    <div class='card-body'>
        <a href='fix_session_loading.php' class='btn btn-warning me-2'>
            <i class='fas fa-tools'></i> Fix Session Loading
        </a>
        <a href='populate_school_data.php' class='btn btn-success me-2'>
            <i class='fas fa-plus'></i> Add Sample Data
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