<?php
include ('../conn/conn.php');

if (isset($_GET['student'])) {
    $student = $_GET['student'];

    // Validate that the student ID is a number
    if (!is_numeric($student)) {
        // Redirect with parameters for error modal
        header("Location: http://localhost/Qnnect/masterlist.php?delete_error=1&message=" . urlencode("Invalid student ID!"));
        exit();
    }

    try {
        // First check if the student record exists
        $checkQuery = "SELECT * FROM tbl_student WHERE tbl_student_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$student]);
        $result = $checkStmt->fetch();
        
        if (!$result) {
            // Redirect with parameters for error modal
            header("Location: http://localhost/Qnnect/masterlist.php?delete_error=1&message=" . urlencode("Student record not found!"));
            exit();
        }

        // Get student info for the success message
        $studentName = $result['student_name'];

        // Proceed with deletion using proper parameter binding
        $query = "DELETE FROM tbl_student WHERE tbl_student_id = ?";
        $stmt = $conn->prepare($query);
        $query_execute = $stmt->execute([$student]);

        if ($query_execute && $stmt->rowCount() > 0) {
            // Redirect with parameters for the success modal
            header("Location: http://localhost/Qnnect/masterlist.php?delete_success=1&student_name=" . urlencode($studentName));
            exit();
        } else {
            // Redirect with parameters for error modal
            header("Location: http://localhost/Qnnect/masterlist.php?delete_error=1&message=" . urlencode("Failed to delete student! No rows were affected."));
            exit();
        }

    } catch (PDOException $e) {
        // Redirect with parameters for database error modal
        header("Location: http://localhost//Qnnect/masterlist.php?delete_error=1&message=" . urlencode("Database Error: " . $e->getMessage()));
        exit();
    } catch (Exception $e) {
        // Redirect with parameters for general error modal
        header("Location: http://localhost/Qnnect/masterlist.php?delete_error=1&message=" . urlencode("Error: " . $e->getMessage()));
        exit();
    }
} else {
    // Redirect with parameters for error modal
    header("Location: http://localhost/Qnnect/masterlist.php?delete_error=1&message=" . urlencode("No student ID provided!"));
    exit();
}
?>