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

        try {
            $stmt = $conn->prepare("UPDATE tbl_student SET student_name = :student_name, course_section = :course_section WHERE tbl_student_id = :tbl_student_id");
            
            $stmt->bindParam(":tbl_student_id", $studentId, PDO::PARAM_STR); 
            $stmt->bindParam(":student_name", $studentName, PDO::PARAM_STR); 
            $stmt->bindParam(":course_section", $studentCourse, PDO::PARAM_STR);

            $stmt->execute();

            header("Location: http://localhost/personal-proj/Qnnect/masterlist.php");

            exit();
        } catch (PDOException $e) {
            echo "Error:" . $e->getMessage();
        }

    } else {
        echo "
            <script>
                alert('Please fill in all fields!');
                window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
            </script>
        ";
    }
}
?>
