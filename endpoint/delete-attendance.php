<?php
include ('../conn/conn.php');

if (isset($_GET['attendance'])) {
    $attendance = $_GET['attendance'];

    // Validate that the attendance ID is a number
    if (!is_numeric($attendance)) {
        echo "<script>
            alert('Invalid attendance ID!');
            window.location.href = 'http://localhost/personal-proj/Qnnect/index.php';
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
                window.location.href = 'http://localhost/personal-proj/Qnnect/index.php';
            </script>";
            exit;
        }

        // Proceed with deletion using proper parameter binding
        $query = "DELETE FROM tbl_attendance WHERE tbl_attendance_id = ?";
        $stmt = $conn->prepare($query);
        $query_execute = $stmt->execute([$attendance]);

        if ($query_execute && $stmt->rowCount() > 0) {
            echo "<script>
                alert('Attendance deleted successfully!');
                window.location.href = 'http://localhost/personal-proj/Qnnect/index.php';
            </script>";
        } else {
            echo "<script>
                alert('Failed to delete attendance! No rows were affected.');
                window.location.href = 'http://localhost/personal-proj/Qnnect/index.php';
            </script>";
        }

    } catch (PDOException $e) {
        echo "<script>
            alert('Database Error: " . addslashes($e->getMessage()) . "');
            window.location.href = 'http://localhost/personal-proj/Qnnect/index.php';
        </script>";
    } catch (Exception $e) {
        echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
            window.location.href = 'http://localhost/personal-proj/Qnnect/index.php';
        </script>";
    }
} else {
    echo "<script>
        alert('No attendance ID provided!');
        window.location.href = 'http://localhost/personal-proj/Qnnect/index.php';
    </script>";
}
?>