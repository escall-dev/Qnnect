<?php
require_once 'conn/db_connect.php';
require_once 'includes/session_config.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    die("Access denied. Please log in first.");
}

$school_id = $_SESSION['school_id'];

try {
    // List of old instructors to remove
    $oldInstructors = ['josefa', 'alex', 'abrney', 'Josefa', 'Alex', 'Abrney', 'JOSEFA', 'ALEX', 'ABRNEY'];
    
    echo "<h3>Cleaning up old instructors...</h3>";
    
    foreach ($oldInstructors as $instructorName) {
        // Check if instructor exists
        $checkQuery = "SELECT instructor_id, instructor_name FROM tbl_instructors WHERE instructor_name LIKE ?";
        $checkStmt = $conn_qr->prepare($checkQuery);
        $searchName = "%$instructorName%";
        $checkStmt->bind_param("s", $searchName);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $instructorId = $row['instructor_id'];
                $foundName = $row['instructor_name'];
                
                echo "<p>Found instructor: <strong>$foundName</strong> (ID: $instructorId)</p>";
                
                // First, remove all instructor-subject relationships
                $deleteRelQuery = "DELETE FROM tbl_instructor_subjects WHERE instructor_id = ?";
                $deleteRelStmt = $conn_qr->prepare($deleteRelQuery);
                $deleteRelStmt->bind_param("i", $instructorId);
                $deleteRelStmt->execute();
                echo "<p>- Removed subject relationships</p>";
                
                // Then, remove the instructor
                $deleteInstQuery = "DELETE FROM tbl_instructors WHERE instructor_id = ?";
                $deleteInstStmt = $conn_qr->prepare($deleteInstQuery);
                $deleteInstStmt->bind_param("i", $instructorId);
                $deleteInstStmt->execute();
                echo "<p>- Removed instructor: <strong>$foundName</strong></p>";
                
                echo "<hr>";
            }
        } else {
            echo "<p>No instructor found matching: <strong>$instructorName</strong></p>";
        }
    }
    
    echo "<h3>✅ Cleanup completed!</h3>";
    echo "<p><a href='index.php' class='btn btn-primary'>Go back to main page</a></p>";
    
    // Display remaining instructors
    echo "<h4>Remaining Instructors:</h4>";
    $remainingQuery = "SELECT instructor_id, instructor_name FROM tbl_instructors ORDER BY instructor_name";
    $remainingResult = $conn_qr->query($remainingQuery);
    
    if ($remainingResult->num_rows > 0) {
        echo "<ul>";
        while ($row = $remainingResult->fetch_assoc()) {
            echo "<li>{$row['instructor_name']} (ID: {$row['instructor_id']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No instructors found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Instructor Cleanup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <!-- Cleanup results will be displayed above -->
    </div>
</body>
</html>
