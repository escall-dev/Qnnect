<?php
include("../conn/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['student_name'])) {
        $studentId = $_POST['tbl_student_id'];
        $studentName = $_POST['student_name'];
        // Use update_final_course_section if available, otherwise fall back to course_section
        $studentCourse = isset($_POST['update_final_course_section']) && !empty($_POST['update_final_course_section']) 
            ? $_POST['update_final_course_section'] 
            : $_POST['course_section'];
        
        // Check if this is a custom course section that needs to be saved
        if (isset($_POST['update_custom_course_section']) && !empty($_POST['update_custom_course_section'])) {
            $customCourseSection = $_POST['update_custom_course_section'];
            
            // Parse the custom input to extract course and section
            $parts = explode('-', $customCourseSection);
            
            if (count($parts) === 2) {
                $courseName = trim($parts[0]);
                $sectionName = trim($parts[1]);
                
                // Get session values
                session_start();
                $user_id = $_SESSION['user_id'] ?? 1;
                $school_id = $_SESSION['school_id'] ?? 1;
                
                try {
                    // Save course if it doesn't exist
                    $courseCheck = $conn->prepare("SELECT course_id FROM tbl_courses WHERE course_name = :course_name AND (user_id = :user_id OR user_id = 1) AND (school_id = :school_id OR school_id = 1)");
                    $courseCheck->bindParam(':course_name', $courseName);
                    $courseCheck->bindParam(':user_id', $user_id);
                    $courseCheck->bindParam(':school_id', $school_id);
                    $courseCheck->execute();
                    
                    if ($courseCheck->rowCount() === 0) {
                        $insertCourse = $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (:course_name, :user_id, :school_id)");
                        $insertCourse->bindParam(':course_name', $courseName);
                        $insertCourse->bindParam(':user_id', $user_id);
                        $insertCourse->bindParam(':school_id', $school_id);
                        $insertCourse->execute();
                    }
                    
                    // Save section if it doesn't exist
                    $sectionCheck = $conn->prepare("SELECT section_id FROM tbl_sections WHERE section_name = :section_name AND (user_id = :user_id OR user_id = 1) AND (school_id = :school_id OR school_id = 1)");
                    $sectionCheck->bindParam(':section_name', $sectionName);
                    $sectionCheck->bindParam(':user_id', $user_id);
                    $sectionCheck->bindParam(':school_id', $school_id);
                    $sectionCheck->execute();
                    
                    if ($sectionCheck->rowCount() === 0) {
                        $insertSection = $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id) VALUES (:section_name, :user_id, :school_id)");
                        $insertSection->bindParam(':section_name', $sectionName);
                        $insertSection->bindParam(':user_id', $user_id);
                        $insertSection->bindParam(':school_id', $school_id);
                        $insertSection->execute();
                    }
                } catch (Exception $e) {
                    error_log("Error saving course/section: " . $e->getMessage());
                }
            }
        }

        try {
            $stmt = $conn->prepare("UPDATE tbl_student SET student_name = :student_name, course_section = :course_section WHERE tbl_student_id = :tbl_student_id");
            
            $stmt->bindParam(":tbl_student_id", $studentId, PDO::PARAM_STR); 
            $stmt->bindParam(":student_name", $studentName, PDO::PARAM_STR); 
            $stmt->bindParam(":course_section", $studentCourse, PDO::PARAM_STR);

            $stmt->execute();

            header("Location: http://localhost/Qnnect/masterlist.php");

            exit();
        } catch (PDOException $e) {
            echo "Error:" . $e->getMessage();
        }

    } else {
        echo "
            <script>
                alert('Please fill in all fields!');
                window.location.href = 'http://localhost/Qnnect/masterlist.php';
            </script>
        ";
    }
}
?>
