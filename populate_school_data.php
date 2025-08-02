<?php
// Script to populate sample data for different school IDs
session_start();
require_once('conn/db_connect.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Populate School Data</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>📊 Populate School Data</h2>
        
        <div class='alert alert-info'>
            <h5>Why School ID 2 shows 'No Active Session':</h5>
            <p>School ID 2 doesn't have any data in the database tables. The features work correctly, but they need data to display. This script will add sample data to test the functionality.</p>
        </div>
        
        <div class='row'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Add Sample Data</h5>
                    </div>
                    <div class='card-body'>
                        <form method='post'>
                            <div class='form-group mb-3'>
                                <label>Select School ID to populate:</label>
                                <select name='populate_school_id' class='form-control'>
                                    <option value='2'>School ID 2</option>
                                    <option value='3'>School ID 3</option>
                                    <option value='4'>School ID 4</option>
                                    <option value='5'>School ID 5</option>
                                </select>
                            </div>
                            <button type='submit' name='populate' class='btn btn-success'>
                                <i class='fas fa-plus'></i> Add Sample Data
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Sample Data Preview</h5>
                    </div>
                    <div class='card-body'>
                        <h6>What will be added:</h6>
                        <ul>
                            <li>📅 Class time settings (2:30 PM)</li>
                            <li>👨‍🏫 Teacher schedules (Web Tech, Programming)</li>
                            <li>👥 Students (BSIT-301, BSIS-301)</li>
                            <li>👨‍💼 Instructors (John Doe, Jane Smith)</li>
                            <li>📊 Sample attendance sessions</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>";

if (isset($_POST['populate']) && $_POST['populate_school_id']) {
    $school_id = intval($_POST['populate_school_id']);
    
    echo "<div class='card mt-4'>
        <div class='card-header'>
            <h5>Adding Sample Data for School ID: $school_id</h5>
        </div>
        <div class='card-body'>";
    
    try {
        // 1. Add class time settings
        $class_time_query = "INSERT INTO class_time_settings (school_id, start_time, created_at, updated_at) 
                           VALUES (?, '14:30:00', NOW(), NOW()) 
                           ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), updated_at = NOW()";
        $stmt = $conn_qr->prepare($class_time_query);
        $stmt->bind_param("i", $school_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Added class time settings (2:30 PM)</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Class time settings already exist or error occurred</p>";
        }
        
        // 2. Add sample instructors
        $instructor_query = "INSERT INTO tbl_instructors (instructor_name, school_id) VALUES 
                           ('John Doe', ?), ('Jane Smith', ?) 
                           ON DUPLICATE KEY UPDATE instructor_name = VALUES(instructor_name)";
        $stmt = $conn_qr->prepare($instructor_query);
        $stmt->bind_param("ii", $school_id, $school_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Added sample instructors</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Instructors already exist or error occurred</p>";
        }
        
        // 3. Add sample students
        $student_query = "INSERT INTO tbl_student (tbl_student_id, first_name, last_name, course_section, school_id) VALUES 
                        (?, 'Student', 'One', 'BSIT-301', ?), 
                        (?, 'Student', 'Two', 'BSIS-301', ?) 
                        ON DUPLICATE KEY UPDATE course_section = VALUES(course_section)";
        $stmt = $conn_qr->prepare($student_query);
        $student_id1 = $school_id * 1000 + 1;
        $student_id2 = $school_id * 1000 + 2;
        $stmt->bind_param("iiii", $student_id1, $school_id, $student_id2, $school_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Added sample students</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Students already exist or error occurred</p>";
        }
        
        // 4. Add teacher schedules
        $schedule_query = "INSERT INTO teacher_schedules (teacher_username, subject, section, start_time, end_time, day_of_week, school_id, status) VALUES 
                         ('john.doe', 'Web Tech', 'BSIT-301', '14:30:00', '15:30:00', 'Monday', ?, 'active'),
                         ('jane.smith', 'Programming', 'BSIS-301', '16:00:00', '17:00:00', 'Tuesday', ?, 'active')";
        $stmt = $conn_qr->prepare($schedule_query);
        $stmt->bind_param("ii", $school_id, $school_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Added teacher schedules</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Schedules already exist or error occurred</p>";
        }
        
        echo "<div class='alert alert-success mt-3'>
            <h6>🎉 Sample data added successfully!</h6>
            <p>Now you can test the features for School ID $school_id. The system should show:</p>
            <ul>
                <li>Active class session with 2:30 PM start time</li>
                <li>Subject/Section dropdowns with data</li>
                <li>Terminate session button</li>
                <li>All multi-tenant features working</li>
            </ul>
        </div>";
        
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
        <h5>Next Steps</h5>
    </div>
    <div class='card-body'>
        <ol>
            <li>Use the form above to add sample data for School ID 2</li>
            <li>Go to <a href='index.php'>Main Page</a> and set your school_id to 2</li>
            <li>You should now see active sessions and working features</li>
            <li>Test the terminate button and subject/section filtering</li>
        </ol>
        
        <div class='mt-3'>
            <a href='debug_school_data.php' class='btn btn-info me-2'>
                <i class='fas fa-search'></i> Debug School Data
            </a>
            <a href='test_multi_tenant_features.php' class='btn btn-success me-2'>
                <i class='fas fa-cogs'></i> Test Multi-Tenant Features
            </a>
            <a href='index.php' class='btn btn-primary'>
                <i class='fas fa-home'></i> Go to Main Page
            </a>
        </div>
    </div>
</div>

</div>
</body>
</html>";
?> 