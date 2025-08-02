<?php
include("../conn/conn.php");
include("../includes/data_isolation_helper.php");

// Start session to get user context
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user context for data isolation
    $context = getCurrentUserContext();
    
    // Check if all required fields are present
    if (isset($_POST['student_name'], $_POST['final_course_section'], $_POST['generated_code'], $_POST['face_verified'], $_POST['face_image_data'])) {
        $studentName = trim($_POST['student_name']);
        $studentCourse = trim($_POST['final_course_section']);
        $generatedCode = trim($_POST['generated_code']);
        $faceVerified = $_POST['face_verified'];
        $faceImageData = $_POST['face_image_data'];
        
        // Validate that all fields are not empty
        if (empty($studentName) || empty($studentCourse) || empty($generatedCode)) {
            echo "
                <script>
                    alert('Please fill in all required fields!');
                    window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
                </script>
            ";
            exit();
        }
        
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
        
        // Validate face image data
        if (empty($faceImageData)) {
            echo "
                <script>
                    alert('Face image data is missing. Please recapture your face.');
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
            
            // Insert student data into the database with data isolation
            $stmt = $conn->prepare("INSERT INTO tbl_student (student_name, course_section, generated_code, face_image_path, school_id, user_id) VALUES (:student_name, :course_section, :generated_code, :face_image_path, :school_id, :user_id)");
            
            $stmt->bindParam(":student_name", $studentName, PDO::PARAM_STR); 
            $stmt->bindParam(":course_section", $studentCourse, PDO::PARAM_STR);
            $stmt->bindParam(":generated_code", $generatedCode, PDO::PARAM_STR);
            $stmt->bindParam(":face_image_path", $faceImageFilename, PDO::PARAM_STR);
            $stmt->bindParam(":school_id", $context['school_id'], PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $context['user_id'], PDO::PARAM_INT);

            $stmt->execute();

            // Redirect back to masterlist with success parameter
            header("Location: http://localhost/personal-proj/Qnnect/masterlist.php?success=1&student_name=" . urlencode($studentName) . "&course_section=" . urlencode($studentCourse));
            exit();
            exit();
        } catch (PDOException $e) {
            echo "
                <script>
                    alert('Error adding student: " . addslashes($e->getMessage()) . "');
                    window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
                </script>
            ";
            exit();
        }

    } else {
        echo "
            <script>
                alert('Please fill in all required fields (name, course/section, QR code, and face verification)!');
                window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
            </script>
        ";
    }
}
?>
