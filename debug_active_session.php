<?php
// Debug script to check why active session is not detected
session_start();
require_once('conn/db_connect.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Active Session</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>🔍 Debug Active Session Detection</h2>
        
        <div class='alert alert-danger'>
            <h5>🚨 URGENT: Active Session Not Detected</h5>
            <p>The system shows 'No Active Session' even when data exists. Let's debug this step by step.</p>
        </div>
        
        <div class='row'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Test Session Detection</h5>
                    </div>
                    <div class='card-body'>
                        <form method='post'>
                            <div class='form-group mb-3'>
                                <label>Select School ID to debug:</label>
                                <select name='debug_school_id' class='form-control'>
                                    <option value='1'>School ID 1</option>
                                    <option value='2'>School ID 2</option>
                                    <option value='3'>School ID 3</option>
                                </select>
                            </div>
                            <button type='submit' name='debug_active' class='btn btn-danger'>
                                <i class='fas fa-bug'></i> Debug Active Session
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
                        <p><strong>Class Start Time Formatted:</strong> " . ($_SESSION['class_start_time_formatted'] ?? 'Not set') . "</p>
                    </div>
                </div>
            </div>
        </div>";

if (isset($_POST['debug_active']) && $_POST['debug_school_id']) {
    $school_id = intval($_POST['debug_school_id']);
    $_SESSION['school_id'] = $school_id;
    
    echo "<div class='card mt-4'>
        <div class='card-header'>
            <h5>Debugging Active Session for School ID: $school_id</h5>
        </div>
        <div class='card-body'>";
    
    // Step 1: Check database data
    echo "<h6>📊 Step 1: Check Database Data</h6>";
    $query = "SELECT * FROM class_time_settings WHERE school_id = ? ORDER BY updated_at DESC LIMIT 1";
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Database has data:</p>";
        echo "<p>• School ID: " . $row['school_id'] . "</p>";
        echo "<p>• Start Time: " . $row['start_time'] . "</p>";
        echo "<p>• Updated: " . $row['updated_at'] . "</p>";
        
        // Step 2: Simulate the exact logic from index.php
        echo "<h6>🧠 Step 2: Simulate index.php Logic</h6>";
        
        $active_class_time = null;
        $class_time_source = '';
        
        if (isset($_SESSION['school_id'])) {
            try {
                if (isset($conn_qr)) {
                    $query = "SELECT start_time, updated_at FROM class_time_settings WHERE school_id = ? ORDER BY updated_at DESC LIMIT 1";
                    $stmt = $conn_qr->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param("i", $_SESSION['school_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            $active_class_time = $row['start_time'];
                            $class_time_source = 'database (saved at ' . date('h:i A', strtotime($row['updated_at'])) . ')';
                            
                            echo "<p style='color: green;'>✅ Logic executed successfully:</p>";
                            echo "<p>• \$active_class_time = '$active_class_time'</p>";
                            echo "<p>• \$class_time_source = '$class_time_source'</p>";
                            
                            // Set session variables
                            $_SESSION['class_start_time'] = $row['start_time'];
                            $_SESSION['class_start_time_formatted'] = date('h:i A', strtotime($row['start_time']));
                            
                            echo "<p style='color: green;'>✅ Session variables set:</p>";
                            echo "<p>• \$_SESSION['class_start_time'] = '" . $_SESSION['class_start_time'] . "'</p>";
                            echo "<p>• \$_SESSION['class_start_time_formatted'] = '" . $_SESSION['class_start_time_formatted'] . "'</p>";
                        } else {
                            echo "<p style='color: red;'>❌ No data found in result</p>";
                        }
                        $stmt->close();
                    } else {
                        echo "<p style='color: red;'>❌ Statement preparation failed</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ Database connection not available</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ \$_SESSION['school_id'] not set</p>";
        }
        
        // Step 3: Check the condition that determines display
        echo "<h6>🎯 Step 3: Check Display Condition</h6>";
        
        if ($active_class_time) {
            echo "<p style='color: green;'>✅ \$active_class_time is truthy: '$active_class_time'</p>";
            echo "<p style='color: green;'>✅ Should show: <strong>ACTIVE SESSION</strong></p>";
            echo "<p style='color: green;'>✅ Should NOT show: 'No Active Session'</p>";
        } else {
            echo "<p style='color: red;'>❌ \$active_class_time is falsy</p>";
            echo "<p style='color: red;'>❌ Will show: 'No Active Session'</p>";
        }
        
        // Step 4: Check session variables
        echo "<h6>🔐 Step 4: Check Session Variables</h6>";
        echo "<p>• \$_SESSION['school_id'] = '" . ($_SESSION['school_id'] ?? 'NOT SET') . "'</p>";
        echo "<p>• \$_SESSION['class_start_time'] = '" . ($_SESSION['class_start_time'] ?? 'NOT SET') . "'</p>";
        echo "<p>• \$_SESSION['class_start_time_formatted'] = '" . ($_SESSION['class_start_time_formatted'] ?? 'NOT SET') . "'</p>";
        
        // Step 5: Test the exact condition from the template
        echo "<h6>📋 Step 5: Test Template Condition</h6>";
        $template_condition = $active_class_time ? 'TRUE' : 'FALSE';
        echo "<p>• Template condition: <strong>$template_condition</strong></p>";
        
        if ($template_condition === 'TRUE') {
            echo "<p style='color: green;'>✅ Template will show ACTIVE SESSION</p>";
        } else {
            echo "<p style='color: red;'>❌ Template will show NO ACTIVE SESSION</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ No data found in database for School ID $school_id</p>";
        echo "<p><strong>Solution:</strong> Add sample data first.</p>";
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
        <a href='fix_session_loading.php' class='btn btn-warning me-2'>
            <i class='fas fa-tools'></i> Fix Session Loading
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