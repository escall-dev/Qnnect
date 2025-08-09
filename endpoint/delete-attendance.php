<?php
include ('../conn/conn.php');

if (isset($_GET['attendance'])) {
    $attendance = $_GET['attendance'];

    // Validate that the attendance ID is a number
    if (!is_numeric($attendance)) {
        echo "<script>
            alert('Invalid attendance ID!');
            window.location.href = 'http://localhost/Qnnect/index.php';
        </script>";
        exit;
    }

    try {
        // First check if the attendance record exists
        $checkQuery = "SELECT * FROM tbl_attendance WHERE tbl_attendance_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$attendance]);
        $result = $checkStmt->fetch();
        
        if (!$result) {
            echo "<script>
                alert('Attendance record not found!');
                window.location.href = 'http://localhost/Qnnect/index.php';
            </script>";
            exit;
        }

        // Proceed with deletion using proper parameter binding
        $query = "DELETE FROM tbl_attendance WHERE tbl_attendance_id = ?";
        $stmt = $conn->prepare($query);
        $query_execute = $stmt->execute([$attendance]);

        if ($query_execute && $stmt->rowCount() > 0) {
            // Use the success params to trigger our enhanced modal
            $successParams = http_build_query([
                'success' => 'attendance_deleted',
                'student' => $result['student_name'] ?? 'Student',
                'id' => $attendance
            ]);
            header("Location: http://localhost/Qnnect/index.php?$successParams");
            exit();
        } else {
            $errorParams = http_build_query([
                'error' => 'delete_failed',
                'message' => 'Failed to delete attendance! No rows were affected.',
                'details' => 'Database reported successful execution but no rows were modified.'
            ]);
            header("Location: http://localhost/Qnnect/index.php?$errorParams");
            exit();
        }

    } catch (PDOException $e) {
        $errorParams = http_build_query([
            'error' => 'db_error',
            'message' => 'Database Error',
            'details' => $e->getMessage()
        ]);
        header("Location: http://localhost/Qnnect/index.php?$errorParams");
        exit();
    } catch (Exception $e) {
        $errorParams = http_build_query([
            'error' => 'general_error',
            'message' => 'Error during deletion',
            'details' => $e->getMessage()
        ]);
        header("Location: http://localhost/Qnnect/index.php?$errorParams");
        exit();
    }
} else {
    $errorParams = http_build_query([
        'error' => 'missing_id',
        'message' => 'No attendance ID provided!',
        'details' => 'The attendance ID parameter was missing from the request.'
    ]);
    header("Location: http://localhost/Qnnect/index.php?$errorParams");
    exit();
}
?>