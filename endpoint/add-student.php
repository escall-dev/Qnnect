<?php
include("../includes/session_config.php");
include("../conn/conn.php");
include("../conn/db_connect.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("No user_id in session - redirecting to login");
    echo "<script>alert('Please log in first!'); window.location.href = '../admin/login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'] ?? 1;

error_log("Using session user_id: $user_id, school_id: $school_id");
error_log("Session data: " . print_r($_SESSION, true));

/**
 * Generate a unique QR code for each student
 * @param string $studentName Student's name
 * @param string $courseSection Course and section
 * @param int $user_id User ID
 * @param int $school_id School ID
 * @return string Unique QR code
 */
function generateUniqueStudentCode($studentName, $courseSection, $user_id, $school_id) {
    // Create a unique identifier using multiple components
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8)); // 16 character random string
    $studentHash = substr(md5($studentName . $courseSection . $user_id . $school_id), 0, 8);
    
    // Combine all components to create a unique code
    $uniqueCode = sprintf(
        "STU-%s-%s-%s-%s",
        $user_id,
        $school_id,
        $studentHash,
        $randomString
    );
    
    return $uniqueCode;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received");
    error_log("POST data: " . print_r($_POST, true));
    
    if (isset($_POST['student_name'], $_POST['generated_code'], $_POST['face_verified'], $_POST['face_image_data'])) {
        error_log("All required fields present");
        
        $studentName = $_POST['student_name'];
        // Use final_course_section if available, otherwise fall back to course_section
        $studentCourse = isset($_POST['final_course_section']) && !empty($_POST['final_course_section']) 
            ? $_POST['final_course_section'] 
            : $_POST['course_section'];
        $generatedCode = $_POST['generated_code'];
        $faceVerified = $_POST['face_verified'];
        $faceImageData = $_POST['face_image_data'];
        
        // Generate a unique QR code for this student
        $uniqueCode = generateUniqueStudentCode($studentName, $studentCourse, $user_id, $school_id);
        
        error_log("Student data - Name: $studentName, Course: $studentCourse, Original Code: $generatedCode, Unique Code: $uniqueCode, Face verified: $faceVerified");
        
        // Debug: Log the school_id being used
        error_log("Adding student with school_id: " . $school_id);
        
        // Check if face verification was completed
        if ($faceVerified !== '1') {
            error_log("Face verification failed");
            echo "
                <script>
                    alert('Face verification is required!');
                    window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
                </script>
            ";
            exit();
        }
        
        error_log("Face verification passed");
        
        // Process the face image data (base64 string)
        // Remove the data URL prefix to get just the base64 data
        $faceImageData = str_replace('data:image/jpeg;base64,', '', $faceImageData);
        
        try {
            // Create a directory to store face images if it doesn't exist
            $uploadDir = '../face_images/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                error_log("Created upload directory: $uploadDir");
            }
            
            // Generate a unique filename for the face image
            $faceImageFilename = 'face_' . time() . '_' . uniqid() . '.jpg';
            $faceImagePath = $uploadDir . $faceImageFilename;
            
            error_log("Saving face image to: $faceImagePath");
            
            // Save the face image to the server
            $imageSaved = file_put_contents($faceImagePath, base64_decode($faceImageData));
            if ($imageSaved === false) {
                error_log("Failed to save face image");
                throw new Exception("Failed to save face image");
            }
            error_log("Face image saved successfully");
            
            // Insert student data into the database with school_id and user_id
            $insert_query = "INSERT INTO tbl_student (student_name, course_section, generated_code, face_image_path, school_id, user_id) VALUES (:student_name, :course_section, :generated_code, :face_image_path, :school_id, :user_id)";
            error_log("Insert query: $insert_query");
            
            $stmt = $conn->prepare($insert_query);
            if (!$stmt) {
                error_log("Prepare failed: " . implode(", ", $conn->errorInfo()));
                throw new Exception("Prepare failed");
            }
            
            $stmt->bindParam(":student_name", $studentName, PDO::PARAM_STR);
            $stmt->bindParam(":course_section", $studentCourse, PDO::PARAM_STR);
            $stmt->bindParam(":generated_code", $uniqueCode, PDO::PARAM_STR);
            $stmt->bindParam(":face_image_path", $faceImageFilename, PDO::PARAM_STR);
            $stmt->bindParam(":school_id", $school_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            
            error_log("About to execute insert with params: $studentName, $studentCourse, $uniqueCode, $faceImageFilename, $school_id, $user_id");
            
            $result = $stmt->execute();
            if (!$result) {
                error_log("Execute failed: " . implode(", ", $stmt->errorInfo()));
                throw new Exception("Execute failed");
            }
            
            $inserted_id = $conn->lastInsertId();
            error_log("Student inserted successfully with ID: $inserted_id");

            header("Location: http://localhost/personal-proj/Qnnect/masterlist.php");
            exit();
            
        } catch (Exception $e) {
            error_log("Error in student insertion: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }

    } else {
        error_log("Missing required fields in POST data");
        echo "
            <script>
                alert('Please fill in all fields and complete face verification!');
            window.location.href = 'http://localhost/personal-proj/Qnnect/masterlist.php';
            </script>
        ";
    }
} else {
    error_log("Not a POST request");
}
?>
