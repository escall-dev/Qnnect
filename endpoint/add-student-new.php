The.// Reset statement for add-student.php
// This will help ensure proper formatting and structure
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
        
        // Process custom course and section first if they exist
        $courseName = '';
        $sectionName = '';
        
        // Process course (regular dropdown or custom)
        if (isset($_POST['course']) && $_POST['course'] === 'custom' && !empty($_POST['custom_course'])) {
            // Use the custom course name
            $courseName = trim($_POST['custom_course']);
            error_log("Using custom course: $courseName");
        } elseif (isset($_POST['course']) && !empty($_POST['course']) && $_POST['course'] !== 'custom') {
            // Use the selected course name
            $courseName = trim($_POST['course']);
            error_log("Using selected course: $courseName");
        }
        
        // Process section (regular dropdown or custom)
        if (isset($_POST['section']) && $_POST['section'] === 'custom' && !empty($_POST['custom_section'])) {
            // Use the custom section name
            $sectionName = trim($_POST['custom_section']);
            error_log("Using custom section: $sectionName");
        } elseif (isset($_POST['section']) && !empty($_POST['section']) && $_POST['section'] !== 'custom') {
            // Use the selected section name
            $sectionName = trim($_POST['section']);
            error_log("Using selected section: $sectionName");
        }
        
        // If we have both course and section, create the course-section string
        if (!empty($courseName) && !empty($sectionName)) {
            $studentCourse = $courseName . '-' . $sectionName;
            error_log("Created course-section: $studentCourse");
        } else {
            // Fall back to the course_section field if available
            $studentCourse = isset($_POST['course_section']) && !empty($_POST['course_section']) 
                ? $_POST['course_section'] 
                : '';
            error_log("Using fallback course-section: $studentCourse");
            
            // Parse the input to extract course and section if not already set
            if (!empty($studentCourse) && (empty($courseName) || empty($sectionName))) {
                $parts = explode('-', $studentCourse);
                if (count($parts) === 2) {
                    $courseName = empty($courseName) ? trim($parts[0]) : $courseName;
                    $sectionName = empty($sectionName) ? trim($parts[1]) : $sectionName;
                    error_log("Parsed from course_section: course=$courseName, section=$sectionName");
                }
            }
        }
        
        // Process the course and section if we have both
        if (!empty($courseName) && !empty($sectionName)) {
            error_log("Processing course: $courseName and section: $sectionName");
            
            try {
                // Save course if it doesn't exist - ALWAYS tie custom entries to the current user and school
                $courseCheck = $conn->prepare("SELECT course_id FROM tbl_courses WHERE course_name = :course_name AND ((user_id = :user_id AND school_id = :school_id) OR user_id = 1)");
                $courseCheck->bindParam(':course_name', $courseName);
                $courseCheck->bindParam(':user_id', $user_id);
                $courseCheck->bindParam(':school_id', $school_id);
                $courseCheck->execute();
                
                $courseId = 0;
                
                if ($courseCheck->rowCount() === 0) {
                    error_log("Course not found, inserting custom course: $courseName for user: $user_id, school: $school_id");
                    $insertCourse = $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (:course_name, :user_id, :school_id)");
                    $insertCourse->bindParam(':course_name', $courseName);
                    $insertCourse->bindParam(':user_id', $user_id);
                    $insertCourse->bindParam(':school_id', $school_id);
                    $insertCourse->execute();
                    $courseId = $conn->lastInsertId();
                    error_log("New custom course saved: $courseName with ID: " . $courseId);
                } else {
                    $courseId = $courseCheck->fetchColumn();
                    error_log("Course already exists: $courseName with ID: $courseId");
                }
                
                // If we have a valid course ID, let's handle the section
                if ($courseId > 0) {
                    error_log("Found/created course ID: $courseId for course: $courseName");
                    
                    // Look for an existing section with this name tied to THIS USER and SCHOOL specifically
                    // This ensures custom sections are isolated to the user who created them
                    $sectionCheck = $conn->prepare("SELECT section_id FROM tbl_sections 
                                                  WHERE section_name = :section_name 
                                                  AND ((user_id = :user_id AND school_id = :school_id) OR user_id = 1)");
                    $sectionCheck->bindParam(':section_name', $sectionName);
                    $sectionCheck->bindParam(':user_id', $user_id);
                    $sectionCheck->bindParam(':school_id', $school_id);
                    $sectionCheck->execute();
                    
                    if ($sectionCheck->rowCount() === 0) {
                        error_log("Section not found, inserting custom section: $sectionName with course ID: $courseId");
                        $insertSection = $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id, course_id) VALUES (:section_name, :user_id, :school_id, :course_id)");
                        $insertSection->bindParam(':section_name', $sectionName);
                        $insertSection->bindParam(':user_id', $user_id);
                        $insertSection->bindParam(':school_id', $school_id);
                        $insertSection->bindParam(':course_id', $courseId);
                        $insertSection->execute();
                        error_log("New custom section saved: $sectionName with ID: " . $conn->lastInsertId());
                    } else {
                        // Update the section to have the correct course_id
                        $sectionId = $sectionCheck->fetchColumn();
                        $updateSection = $conn->prepare("UPDATE tbl_sections SET course_id = :course_id WHERE section_id = :section_id");
                        $updateSection->bindParam(':course_id', $courseId);
                        $updateSection->bindParam(':section_id', $sectionId);
                        $updateSection->execute();
                        error_log("Updated existing section: $sectionName (ID: $sectionId) with course ID: $courseId");
                    }
                } else {
                    error_log("Error: Could not find or create course ID for course: $courseName");
                }
            } catch (Exception $e) {
                error_log("Error saving course/section: " . $e->getMessage());
            }
        } else {
            error_log("Invalid course-section format. Missing course or section name.");
        }
        
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
            // Redirect with error parameters
            header("Location: ../masterlist.php?add_error=1&message=" . urlencode("Face verification is required!"));
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
                mkdir($uploadDir, 0755, true);
                error_log("Created face images directory");
            }
            
            // Generate a unique filename for the image
            $faceImageFilename = 'face_' . $uniqueCode . '.jpg';
            $faceImagePath = $uploadDir . $faceImageFilename;
            
            // Save the face image to disk
            $faceImageBinary = base64_decode($faceImageData);
            if ($faceImageBinary === false) {
                throw new Exception("Failed to decode base64 image data");
            }
            
            if (file_put_contents($faceImagePath, $faceImageBinary) === false) {
                throw new Exception("Failed to save face image to disk");
            }
            
            error_log("Face image saved to: $faceImagePath");
            
            // Save student data to database
            $stmt = $conn->prepare("INSERT INTO tbl_students (student_name, course_section, qr_code, face_image, school_id, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bindParam(1, $studentName);
            $stmt->bindParam(2, $studentCourse);
            $stmt->bindParam(3, $uniqueCode);
            $stmt->bindParam(4, $faceImageFilename);
            $stmt->bindParam(5, $school_id);
            $stmt->bindParam(6, $user_id);
            
            error_log("About to execute insert with params: $studentName, $studentCourse, $uniqueCode, $faceImageFilename, $school_id, $user_id");
            
            $result = $stmt->execute();
            if (!$result) {
                error_log("Execute failed: " . implode(", ", $stmt->errorInfo()));
                throw new Exception("Execute failed");
            }
            
            $inserted_id = $conn->lastInsertId();
            error_log("Student inserted successfully with ID: $inserted_id");
            
            // Redirect back to masterlist with success message
            header("Location: ../masterlist.php?add_success=1&student_id=$inserted_id");
            exit();
            
        } catch (Exception $e) {
            error_log("Error saving student data: " . $e->getMessage());
            // Redirect with error
            header("Location: ../masterlist.php?add_error=1&message=" . urlencode("Error saving student: " . $e->getMessage()));
            exit();
        }

    } else {
        error_log("Missing required fields in POST data");
        // Redirect with error parameters
        header("Location: ../masterlist.php?add_error=1&message=" . urlencode("Please fill in all fields and complete face verification!"));
        exit();
    }
} else {
    error_log("Invalid request method");
    // Redirect with error
    header("Location: ../masterlist.php?add_error=1&message=" . urlencode("Invalid request method!"));
    exit();
}
