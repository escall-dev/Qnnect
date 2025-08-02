<?php
require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo "Please log in first";
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

echo "<h3>Debugging Attendance Table Display</h3>";
echo "<p><strong>User ID:</strong> $user_id</p>";
echo "<p><strong>School ID:</strong> $school_id</p>";

// Check if tbl_attendance table exists
$tableCheck = "SHOW TABLES LIKE 'tbl_attendance'";
$tableResult = $conn_qr->query($tableCheck);
if ($tableResult->num_rows > 0) {
    echo "<p>✅ tbl_attendance table exists</p>";
} else {
    echo "<p>❌ tbl_attendance table does not exist</p>";
}

// Check if tbl_student table exists
$studentTableCheck = "SHOW TABLES LIKE 'tbl_student'";
$studentResult = $conn_qr->query($studentTableCheck);
if ($studentResult->num_rows > 0) {
    echo "<p>✅ tbl_student table exists</p>";
} else {
    echo "<p>❌ tbl_student table does not exist</p>";
}

// Count total attendance records
$totalQuery = "SELECT COUNT(*) as total FROM tbl_attendance";
$totalResult = $conn_qr->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
echo "<p><strong>Total attendance records in database:</strong> {$totalRow['total']}</p>";

// Count attendance records for this user/school
$userQuery = "SELECT COUNT(*) as total FROM tbl_attendance WHERE user_id = ? AND school_id = ?";
$userStmt = $conn_qr->prepare($userQuery);
$userStmt->bind_param("ii", $user_id, $school_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userRow = $userResult->fetch_assoc();
echo "<p><strong>Attendance records for your user/school:</strong> {$userRow['total']}</p>";

// Show recent attendance records
echo "<h4>Recent Attendance Records:</h4>";
$recentQuery = "SELECT a.*, s.student_name, s.course_section 
                FROM tbl_attendance a
                LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
                ORDER BY a.time_in DESC 
                LIMIT 5";
$recentResult = $conn_qr->query($recentQuery);

if ($recentResult->num_rows > 0) {
    echo "<table class='table table-bordered'>";
    echo "<tr><th>ID</th><th>Student Name</th><th>Course</th><th>Time In</th><th>User ID</th><th>School ID</th></tr>";
    while ($row = $recentResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['tbl_attendance_id']}</td>";
        echo "<td>" . ($row['student_name'] ?? 'N/A') . "</td>";
        echo "<td>" . ($row['course_section'] ?? 'N/A') . "</td>";
        echo "<td>{$row['time_in']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['school_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No attendance records found in database.</p>";
}

// Show table structure
echo "<h4>tbl_attendance Table Structure:</h4>";
$structureQuery = "DESCRIBE tbl_attendance";
$structureResult = $conn_qr->query($structureQuery);
echo "<table class='table table-bordered'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $structureResult->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='index.php' class='btn btn-primary'>Back to Main Page</a></p>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Debug</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <!-- Debug results will be displayed above -->
    </div>
</body>
</html>
