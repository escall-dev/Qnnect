<?php
include("../conn/conn.php");
include("../includes/data_isolation_helper.php");

// Start session to get user context
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user context for data isolation
    $context = getCurrentUserContext();
    
    // Check if all required fields are present
    if (isset($_POST['tbl_student_id'], $_POST['student_name'], $_POST['final_course_section'])) {
        $studentId = trim($_POST['tbl_student_id']);
        $studentName = trim($_POST['student_name']);
        $studentCourse = trim($_POST['final_course_section']);
        
        // Validate that all fields are not empty
        if (empty($studentId) || empty($studentName) || empty($studentCourse)) {
            echo "
                <script>
                    alert('Please fill in all required fields!');
                    window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
                </script>
            ";
            exit();
        }

        try {
            // Update student data with data isolation
            $stmt = $conn->prepare("UPDATE tbl_student SET student_name = :student_name, course_section = :course_section 
                                   WHERE tbl_student_id = :tbl_student_id 
                                   AND school_id = :school_id 
                                   " . ($context['user_id'] ? "AND (user_id = :user_id OR user_id IS NULL)" : ""));
            
            $stmt->bindParam(":tbl_student_id", $studentId, PDO::PARAM_INT); 
            $stmt->bindParam(":student_name", $studentName, PDO::PARAM_STR); 
            $stmt->bindParam(":course_section", $studentCourse, PDO::PARAM_STR);
            $stmt->bindParam(":school_id", $context['school_id'], PDO::PARAM_INT);
            if ($context['user_id']) {
                $stmt->bindParam(":user_id", $context['user_id'], PDO::PARAM_INT);
            }

            $stmt->execute();

            // Redirect back to masterlist with success parameter
            header("Location: http://localhost/personal-proj/Qnnect/masterlist.php?update_success=1&student_name=" . urlencode($studentName) . "&course_section=" . urlencode($studentCourse));
            exit();
        } catch (PDOException $e) {
            echo "
                <script>
                    alert('Error updating student: " . addslashes($e->getMessage()) . "');
                    window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
                </script>
            ";
            exit();
        }

    } else {
        echo "
            <script>
                alert('Please fill in all required fields (student ID, name, and course/section)!');
                window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
            </script>
        ";
    }
}
?>
