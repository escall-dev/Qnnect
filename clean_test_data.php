<?php
// Include database connection
include('./conn/conn.php');

try {
    // Delete test data
    $stmt = $conn->prepare("DELETE FROM tbl_student WHERE student_name = 'atom nucleus'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Test data removed successfully.";
    } else {
        echo "No test data found to remove.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 