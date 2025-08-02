<?php
include ('../conn/conn.php');

if (isset($_GET['student'])) {
    $student = $_GET['student'];

    // Validate that the student ID is a number
    if (!is_numeric($student)) {
        echo "<script>
            alert('Invalid student ID!');
            window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
        </script>";
        exit;
    }

    try {
        // First check if the student record exists
        $checkQuery = "SELECT * FROM tbl_student WHERE tbl_student_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$student]);
        $result = $checkStmt->fetch();
        
        if (!$result) {
            echo "<script>
                alert('Student record not found!');
                window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
            </script>";
            exit;
        }

        // Get student info for the success message
        $studentName = $result['student_name'];

        // Proceed with deletion using proper parameter binding
        $query = "DELETE FROM tbl_student WHERE tbl_student_id = ?";
        $stmt = $conn->prepare($query);
        $query_execute = $stmt->execute([$student]);

        if ($query_execute && $stmt->rowCount() > 0) {
            echo "<script>
                alert('Student \"" . addslashes($studentName) . "\" deleted successfully!');
                window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
            </script>";
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