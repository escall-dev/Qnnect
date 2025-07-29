<?php
include("../conn/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['student_name'], $_POST['course_section'], $_POST['generated_code'], $_POST['face_verified'], $_POST['face_image_data'])) {
        $studentName = $_POST['student_name'];
        $studentCourse = $_POST['course_section'];
        $generatedCode = $_POST['generated_code'];
        $faceVerified = $_POST['face_verified'];
        $faceImageData = $_POST['face_image_data'];
        
        // Check if face verification was completed
        if ($faceVerified !== '1') {
            echo "
                <script>
                    alert('Face verification is required!');
                    window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
                </script>
            ";
            exit();
        }
        
        // Process the face image data (base64 string)
        // Remove the data URL prefix to get just the base64 data
        $faceImageData = str_replace('data:image/jpeg;base64,', '', $faceImageData);
        
        try {
            // Create a directory to store face images if it doesn't exist
            $uploadDir = '../face_images/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate a unique filename for the face image
            $faceImageFilename = 'face_' . time() . '_' . uniqid() . '.jpg';
            $faceImagePath = $uploadDir . $faceImageFilename;
            
            // Save the face image to the server
            file_put_contents($faceImagePath, base64_decode($faceImageData));
            
            // Insert student data into the database
            $stmt = $conn->prepare("INSERT INTO tbl_student (student_name, course_section, generated_code, face_image_path) VALUES (:student_name, :course_section, :generated_code, :face_image_path)");
            
            $stmt->bindParam(":student_name", $studentName, PDO::PARAM_STR); 
            $stmt->bindParam(":course_section", $studentCourse, PDO::PARAM_STR);
            $stmt->bindParam(":generated_code", $generatedCode, PDO::PARAM_STR);
            $stmt->bindParam(":face_image_path", $faceImageFilename, PDO::PARAM_STR);

            $stmt->execute();

            header("Location: http://localhost/personal-proj/Qnnect/masterlist.php");

            exit();
        } catch (PDOException $e) {
            echo "Error:" . $e->getMessage();
        }

    } else {
        echo "
            <script>
                alert('Please fill in all fields and complete face verification!');
            window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
            </script>
        ";
    }
}
?>
