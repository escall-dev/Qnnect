<?php
include ('../conn/conn.php');
include("../includes/data_isolation_helper.php");

// Start session to get user context
session_start();

if (isset($_GET['student'])) {
    $student = $_GET['student'];
    
    // Get user context for data isolation
    $context = getCurrentUserContext();

    // Validate that the student ID is a number
    if (!is_numeric($student)) {
        echo "<script>
            alert('Invalid student ID!');
            window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
        </script>";
        exit;
    }

    try {
        // First check if the student record exists and belongs to the user's context
        $checkQuery = "SELECT * FROM tbl_student WHERE tbl_student_id = ? AND school_id = ? " . ($context['user_id'] ? "AND (user_id = ? OR user_id IS NULL)" : "");
        $checkStmt = $conn->prepare($checkQuery);
        
        if ($context['user_id']) {
            $checkStmt->execute([$student, $context['school_id'], $context['user_id']]);
        } else {
            $checkStmt->execute([$student, $context['school_id']]);
        }
        
        $result = $checkStmt->fetch();
        
        if (!$result) {
            echo "<script>
                alert('Student record not found or access denied!');
                window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
            </script>";
            exit;
        }

        // Get student info for the success message
        $studentName = $result['student_name'];

        // Proceed with deletion using proper parameter binding with data isolation
        $query = "DELETE FROM tbl_student WHERE tbl_student_id = ? AND school_id = ? " . ($context['user_id'] ? "AND (user_id = ? OR user_id IS NULL)" : "");
        $stmt = $conn->prepare($query);
        
        if ($context['user_id']) {
            $query_execute = $stmt->execute([$student, $context['school_id'], $context['user_id']]);
        } else {
            $query_execute = $stmt->execute([$student, $context['school_id']]);
        }

        if ($query_execute && $stmt->rowCount() > 0) {
            // Redirect back to masterlist with delete success parameter
            header("Location: http://localhost/personal-proj/Qnnect/masterlist.php?delete_success=1&student_name=" . urlencode($studentName));
            exit();
        } else {
            echo "<script>
                alert('Failed to delete student! No rows were affected.');
                window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
            </script>";
        }

    } catch (PDOException $e) {
        echo "<script>
            alert('Database Error: " . addslashes($e->getMessage()) . "');
            window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
        </script>";
    } catch (Exception $e) {
        echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
            window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
        </script>";
    }
} else {
    echo "<script>
        alert('No student ID provided!');
        window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
    </script>";
}
?>