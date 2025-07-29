<?php
// Start the session to store the instructor setting
session_start();

// Include database connection
include('../conn/db_connect.php');

// Set headers for JSON response
header('Content-Type: application/json');

// Debug logging for troubleshooting
error_log("save-instructor.php accessed with " . $_SERVER['REQUEST_METHOD'] . " method");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . print_r($_POST, true));
}

// Create required tables if they don't exist
// Instructors table - stores basic instructor information
$create_instructors_table = "CREATE TABLE IF NOT EXISTS tbl_instructors (
    instructor_id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn_qr->query($create_instructors_table);

// Subjects table - stores all available subjects
$create_subjects_table = "CREATE TABLE IF NOT EXISTS tbl_subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn_qr->query($create_subjects_table);

// Instructor-Subject relationship table - links instructors to their subjects
$create_instructor_subjects_table = "CREATE TABLE IF NOT EXISTS tbl_instructor_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_instructor_subject (instructor_id, subject_id),
    FOREIGN KEY (instructor_id) REFERENCES tbl_instructors(instructor_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES tbl_subjects(subject_id) ON DELETE CASCADE
)";
$conn_qr->query($create_instructor_subjects_table);

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Action parameter determines what we're doing
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    error_log("Current action: $action");
    
    switch ($action) {
        case 'set':
            // Get the instructor ID and subject ID from POST data
            $instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
            $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
            
            if ($instructor_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid instructor ID'
                ]);
                exit;
            }
            
            // Get instructor details
            $stmt = $conn_qr->prepare("SELECT instructor_name FROM tbl_instructors WHERE instructor_id = ?");
            $stmt->bind_param("i", $instructor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Instructor not found'
                ]);
                exit;
            }
            
            $instructor_data = $result->fetch_assoc();
            
            // Get subject details if subject_id is provided
            $subject_name = '';
            if ($subject_id > 0) {
                $subject_stmt = $conn_qr->prepare("
                    SELECT s.subject_name 
                    FROM tbl_subjects s
                    JOIN tbl_instructor_subjects is_rel ON s.subject_id = is_rel.subject_id
                    WHERE is_rel.instructor_id = ? AND s.subject_id = ?
                ");
                $subject_stmt->bind_param("ii", $instructor_id, $subject_id);
                $subject_stmt->execute();
                $subject_result = $subject_stmt->get_result();
                
                if ($subject_result->num_rows > 0) {
                    $subject_data = $subject_result->fetch_assoc();
                    $subject_name = $subject_data['subject_name'];
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Subject not found for this instructor'
                    ]);
                    exit;
                }
            } else {
                // If no specific subject is provided, get the first subject for this instructor
                $subject_stmt = $conn_qr->prepare("
                    SELECT s.subject_id, s.subject_name 
                    FROM tbl_subjects s
                    JOIN tbl_instructor_subjects is_rel ON s.subject_id = is_rel.subject_id
                    WHERE is_rel.instructor_id = ?
                    LIMIT 1
                ");
                $subject_stmt->bind_param("i", $instructor_id);
                $subject_stmt->execute();
                $subject_result = $subject_stmt->get_result();
                
                if ($subject_result->num_rows > 0) {
                    $subject_data = $subject_result->fetch_assoc();
                    $subject_id = $subject_data['subject_id'];
                    $subject_name = $subject_data['subject_name'];
                }
            }
            
            // Save instructor and subject to session
            $_SESSION['current_instructor_id'] = $instructor_id;
            $_SESSION['current_instructor_name'] = $instructor_data['instructor_name'];
            $_SESSION['current_subject_id'] = $subject_id;
            $_SESSION['current_subject_name'] = $subject_name;
            
            error_log("Set current instructor to ID: $instructor_id, Name: {$instructor_data['instructor_name']}, Subject ID: $subject_id, Subject: $subject_name");
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Instructor set successfully',
                'data' => [
                    'instructor_id' => $instructor_id,
                    'instructor_name' => $instructor_data['instructor_name'],
                    'subject_id' => $subject_id,
                    'subject_name' => $subject_name
                ]
            ]);
            break;
            
        case 'add':
            // Get the new instructor name and subject
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : '';
            
            // Convert subjects to array if it's a string
            if (!is_array($subjects)) {
                $subjects = explode(',', $subjects);
                // Trim each subject
                $subjects = array_map('trim', $subjects);
                // Remove empty subjects
                $subjects = array_filter($subjects, function($subject) {
                    return !empty($subject);
                });
            }
            
            error_log("Attempting to add instructor: $name, Subjects: " . implode(', ', $subjects));
            
            if (empty($name) || empty($subjects)) {
                error_log("Error: Instructor name or subjects are empty");
                echo json_encode([
                    'success' => false,
                    'message' => 'Instructor name and at least one subject are required'
                ]);
                exit;
            }
            
            // Start transaction
            $conn_qr->begin_transaction();
            
            try {
            // Check if instructor already exists
                $check_stmt = $conn_qr->prepare("SELECT instructor_id FROM tbl_instructors WHERE instructor_name = ?");
                $check_stmt->bind_param("s", $name);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Instructor exists, get their ID
                    $instructor_data = $check_result->fetch_assoc();
                    $instructor_id = $instructor_data['instructor_id'];
                    error_log("Instructor already exists with ID: $instructor_id, adding new subjects");
                } else {
                    // Add new instructor to the database
                    $insert_stmt = $conn_qr->prepare("INSERT INTO tbl_instructors (instructor_name) VALUES (?)");
                    $insert_stmt->bind_param("s", $name);
                    
                    if (!$insert_stmt->execute()) {
                        throw new Exception("Error adding instructor: " . $conn_qr->error);
                    }
                    
                    $instructor_id = $conn_qr->insert_id;
                    error_log("Added new instructor with ID: $instructor_id");
                }
                
                // Process each subject
                $added_subjects = [];
                foreach ($subjects as $subject_name) {
                    if (empty($subject_name)) continue;
                    
                    // Check if subject exists
                    $subject_check_stmt = $conn_qr->prepare("SELECT subject_id FROM tbl_subjects WHERE subject_name = ?");
                    $subject_check_stmt->bind_param("s", $subject_name);
                    $subject_check_stmt->execute();
                    $subject_check_result = $subject_check_stmt->get_result();
                    
                    if ($subject_check_result->num_rows > 0) {
                        // Subject exists, get its ID
                        $subject_data = $subject_check_result->fetch_assoc();
                        $subject_id = $subject_data['subject_id'];
                        error_log("Subject already exists with ID: $subject_id");
                    } else {
                        // Add new subject
                        $subject_insert_stmt = $conn_qr->prepare("INSERT INTO tbl_subjects (subject_name) VALUES (?)");
                        $subject_insert_stmt->bind_param("s", $subject_name);
                        
                        if (!$subject_insert_stmt->execute()) {
                            throw new Exception("Error adding subject: " . $conn_qr->error);
                        }
                        
                        $subject_id = $conn_qr->insert_id;
                        error_log("Added new subject with ID: $subject_id");
                    }
                    
                    // Check if relationship already exists
                    $rel_check_stmt = $conn_qr->prepare("SELECT id FROM tbl_instructor_subjects WHERE instructor_id = ? AND subject_id = ?");
                    $rel_check_stmt->bind_param("ii", $instructor_id, $subject_id);
                    $rel_check_stmt->execute();
                    $rel_check_result = $rel_check_stmt->get_result();
                    
                    if ($rel_check_result->num_rows === 0) {
                        // Add relationship between instructor and subject
                        $rel_insert_stmt = $conn_qr->prepare("INSERT INTO tbl_instructor_subjects (instructor_id, subject_id) VALUES (?, ?)");
                        $rel_insert_stmt->bind_param("ii", $instructor_id, $subject_id);
                        
                        if (!$rel_insert_stmt->execute()) {
                            throw new Exception("Error adding instructor-subject relationship: " . $conn_qr->error);
                        }
                        
                        error_log("Added instructor-subject relationship for Instructor ID: $instructor_id, Subject ID: $subject_id");
                    } else {
                        error_log("Instructor-subject relationship already exists");
                    }
                    
                    $added_subjects[] = $subject_name;
                }
                
                // Commit transaction
                $conn_qr->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Instructor and subjects added successfully',
                    'data' => [
                        'instructor_id' => $instructor_id,
                        'instructor_name' => $name,
                        'subjects' => $added_subjects
                    ]
                ]);
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn_qr->rollback();
                
                error_log("Error in add instructor transaction: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'edit':
            // Get the instructor details
            $instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : '';
            
            // Convert subjects to array if it's a string
            if (!is_array($subjects)) {
                $subjects = explode(',', $subjects);
                // Trim each subject
                $subjects = array_map('trim', $subjects);
                // Remove empty subjects
                $subjects = array_filter($subjects, function($subject) {
                    return !empty($subject);
                });
            }
            
            error_log("Attempting to edit instructor ID: $instructor_id, New Name: $name, New Subjects: " . implode(', ', $subjects));
            
            if ($instructor_id <= 0 || empty($name) || empty($subjects)) {
                error_log("Error: Missing required fields for editing");
                echo json_encode([
                    'success' => false,
                    'message' => 'All fields are required for editing'
                ]);
                exit;
            }
            
            // Start transaction
            $conn_qr->begin_transaction();
            
            try {
                // Check if instructor exists
                $check_stmt = $conn_qr->prepare("SELECT instructor_id FROM tbl_instructors WHERE instructor_id = ?");
                $check_stmt->bind_param("i", $instructor_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    throw new Exception("Instructor not found for editing");
                }
                
                // Check if the new name already exists for another instructor
                $name_check_stmt = $conn_qr->prepare("SELECT instructor_id FROM tbl_instructors WHERE instructor_name = ? AND instructor_id != ?");
                $name_check_stmt->bind_param("si", $name, $instructor_id);
                $name_check_stmt->execute();
                $name_check_result = $name_check_stmt->get_result();
                
                if ($name_check_result->num_rows > 0) {
                    throw new Exception("Another instructor with this name already exists");
                }
                
                // Update the instructor name
                $update_stmt = $conn_qr->prepare("UPDATE tbl_instructors SET instructor_name = ? WHERE instructor_id = ?");
                $update_stmt->bind_param("si", $name, $instructor_id);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Error updating instructor: " . $conn_qr->error);
                }
                
                error_log("Updated instructor with ID: $instructor_id");
                
                // Process subjects
                $updated_subjects = [];
                foreach ($subjects as $subject_name) {
                    if (empty($subject_name)) continue;
                    
                    // Check if subject exists
                    $subject_check_stmt = $conn_qr->prepare("SELECT subject_id FROM tbl_subjects WHERE subject_name = ?");
                    $subject_check_stmt->bind_param("s", $subject_name);
                    $subject_check_stmt->execute();
                    $subject_check_result = $subject_check_stmt->get_result();
                    
                    if ($subject_check_result->num_rows > 0) {
                        // Subject exists, get its ID
                        $subject_data = $subject_check_result->fetch_assoc();
                        $subject_id = $subject_data['subject_id'];
                        error_log("Subject already exists with ID: $subject_id");
                    } else {
                        // Add new subject
                        $subject_insert_stmt = $conn_qr->prepare("INSERT INTO tbl_subjects (subject_name) VALUES (?)");
                        $subject_insert_stmt->bind_param("s", $subject_name);
                        
                        if (!$subject_insert_stmt->execute()) {
                            throw new Exception("Error adding subject: " . $conn_qr->error);
                        }
                        
                        $subject_id = $conn_qr->insert_id;
                        error_log("Added new subject with ID: $subject_id");
                    }
                    
                    // Check if relationship already exists
                    $rel_check_stmt = $conn_qr->prepare("SELECT id FROM tbl_instructor_subjects WHERE instructor_id = ? AND subject_id = ?");
                    $rel_check_stmt->bind_param("ii", $instructor_id, $subject_id);
                    $rel_check_stmt->execute();
                    $rel_check_result = $rel_check_stmt->get_result();
                    
                    if ($rel_check_result->num_rows === 0) {
                        // Add relationship between instructor and subject
                        $rel_insert_stmt = $conn_qr->prepare("INSERT INTO tbl_instructor_subjects (instructor_id, subject_id) VALUES (?, ?)");
                        $rel_insert_stmt->bind_param("ii", $instructor_id, $subject_id);
                        
                        if (!$rel_insert_stmt->execute()) {
                            throw new Exception("Error adding instructor-subject relationship: " . $conn_qr->error);
                        }
                        
                        error_log("Added instructor-subject relationship for Instructor ID: $instructor_id, Subject ID: $subject_id");
                    } else {
                        error_log("Instructor-subject relationship already exists");
                    }
                    
                    $updated_subjects[] = $subject_name;
                }
                
                // If this was the current instructor, update the session
                if (isset($_SESSION['current_instructor_id']) && $_SESSION['current_instructor_id'] == $instructor_id) {
                    $_SESSION['current_instructor_name'] = $name;
                    // Only update subject if it still exists for this instructor
                    if (isset($_SESSION['current_subject_id'])) {
                        $current_subject_id = $_SESSION['current_subject_id'];
                        $subject_exists_stmt = $conn_qr->prepare("
                            SELECT s.subject_name 
                            FROM tbl_subjects s
                            JOIN tbl_instructor_subjects is_rel ON s.subject_id = is_rel.subject_id
                            WHERE is_rel.instructor_id = ? AND s.subject_id = ?
                        ");
                        $subject_exists_stmt->bind_param("ii", $instructor_id, $current_subject_id);
                        $subject_exists_stmt->execute();
                        $subject_exists_result = $subject_exists_stmt->get_result();
                        
                        if ($subject_exists_result->num_rows === 0) {
                            // Subject no longer exists for this instructor, reset subject info
                            unset($_SESSION['current_subject_id']);
                            unset($_SESSION['current_subject_name']);
                        }
                    }
                    error_log("Updated current instructor session data");
                }
                
                // Commit transaction
                $conn_qr->commit();
            
            echo json_encode([
                'success' => true,
                    'message' => 'Instructor updated successfully',
                'data' => [
                        'instructor_id' => $instructor_id,
                        'instructor_name' => $name,
                        'subjects' => $updated_subjects
                    ]
                ]);
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn_qr->rollback();
                
                error_log("Error in edit instructor transaction: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'delete':
            // Get the instructor ID to delete
            $instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
            
            error_log("Attempting to delete instructor ID: $instructor_id");
            
            if ($instructor_id <= 0) {
                error_log("Error: Invalid instructor ID for deletion");
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid instructor ID'
                ]);
                exit;
            }
            
            // Check if instructor exists
            $check_stmt = $conn_qr->prepare("SELECT instructor_name FROM tbl_instructors WHERE instructor_id = ?");
            $check_stmt->bind_param("i", $instructor_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                error_log("Error: Instructor not found for deletion");
                echo json_encode([
                    'success' => false,
                    'message' => 'Instructor not found'
                ]);
                exit;
            }
            
            $instructor_data = $check_result->fetch_assoc();
            $instructor_name = $instructor_data['instructor_name'];
            
            // Delete the instructor (relationships will be deleted due to CASCADE constraint)
            $delete_stmt = $conn_qr->prepare("DELETE FROM tbl_instructors WHERE instructor_id = ?");
            $delete_stmt->bind_param("i", $instructor_id);
            
            if ($delete_stmt->execute()) {
                error_log("Deleted instructor with ID: $instructor_id");
                
                // If this was the current instructor, remove from session
                if (isset($_SESSION['current_instructor_id']) && $_SESSION['current_instructor_id'] == $instructor_id) {
                    unset($_SESSION['current_instructor_id']);
                    unset($_SESSION['current_instructor_name']);
                    unset($_SESSION['current_subject_id']);
                    unset($_SESSION['current_subject_name']);
                    error_log("Removed current instructor from session");
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Instructor deleted successfully',
                    'data' => [
                        'instructor_id' => $instructor_id,
                        'instructor_name' => $instructor_name
                    ]
                ]);
            } else {
                error_log("Error deleting instructor: " . $conn_qr->error);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error deleting instructor: ' . $conn_qr->error
                ]);
            }
            break;
            
        case 'list':
            // Return the list of instructors with their subjects from the database
            $query = "
                SELECT i.instructor_id, i.instructor_name, 
                       GROUP_CONCAT(s.subject_id) as subject_ids,
                       GROUP_CONCAT(s.subject_name SEPARATOR ', ') as subjects
                FROM tbl_instructors i
                LEFT JOIN tbl_instructor_subjects is_rel ON i.instructor_id = is_rel.instructor_id
                LEFT JOIN tbl_subjects s ON is_rel.subject_id = s.subject_id
                GROUP BY i.instructor_id
                ORDER BY i.instructor_name ASC
            ";
            $result = $conn_qr->query($query);
            
            $instructors = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Convert comma-separated values to arrays
                    $row['subject_ids'] = $row['subject_ids'] ? explode(',', $row['subject_ids']) : [];
                    $row['subjects_list'] = $row['subjects'] ? explode(', ', $row['subjects']) : [];
                    $instructors[] = $row;
                }
            }
            
            error_log("Listing instructors. Found " . count($instructors) . " instructors");
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'instructors' => $instructors,
                    'current_instructor' => [
                        'id' => isset($_SESSION['current_instructor_id']) ? $_SESSION['current_instructor_id'] : null,
                        'name' => isset($_SESSION['current_instructor_name']) ? $_SESSION['current_instructor_name'] : null,
                        'subject_id' => isset($_SESSION['current_subject_id']) ? $_SESSION['current_subject_id'] : null,
                        'subject_name' => isset($_SESSION['current_subject_name']) ? $_SESSION['current_subject_name'] : null
                    ]
                ]
            ]);
            break;
            
        case 'get_subjects':
            // Get subjects for a specific instructor
            $instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
            
            if ($instructor_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid instructor ID'
                ]);
                exit;
            }
            
            $query = "
                SELECT s.subject_id, s.subject_name
                FROM tbl_subjects s
                JOIN tbl_instructor_subjects is_rel ON s.subject_id = is_rel.subject_id
                WHERE is_rel.instructor_id = ?
                ORDER BY s.subject_name ASC
            ";
            
            $stmt = $conn_qr->prepare($query);
            $stmt->bind_param("i", $instructor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $subjects = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $subjects[] = $row;
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'instructor_id' => $instructor_id,
                    'subjects' => $subjects
                ]
            ]);
            break;
            
        case 'remove_subject':
            // Get the instructor ID and subject ID
            $instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
            $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
            
            error_log("Attempting to remove subject ID: $subject_id from instructor ID: $instructor_id");
            
            if ($instructor_id <= 0 || $subject_id <= 0) {
                error_log("Error: Invalid instructor ID or subject ID for removal");
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid instructor ID or subject ID'
                ]);
                exit;
            }
            
            // Check if the relationship exists
            $check_stmt = $conn_qr->prepare("
                SELECT is_rel.id, i.instructor_name, s.subject_name 
                FROM tbl_instructor_subjects is_rel
                JOIN tbl_instructors i ON i.instructor_id = is_rel.instructor_id
                JOIN tbl_subjects s ON s.subject_id = is_rel.subject_id
                WHERE is_rel.instructor_id = ? AND is_rel.subject_id = ?
            ");
            $check_stmt->bind_param("ii", $instructor_id, $subject_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                error_log("Error: Relationship not found for removal");
                echo json_encode([
                    'success' => false,
                    'message' => 'Subject not found for this instructor'
                ]);
                exit;
            }
            
            $relationship_data = $check_result->fetch_assoc();
            $instructor_name = $relationship_data['instructor_name'];
            $subject_name = $relationship_data['subject_name'];
            
            // Count how many subjects this instructor has
            $count_stmt = $conn_qr->prepare("
                SELECT COUNT(*) as subject_count 
                FROM tbl_instructor_subjects 
                WHERE instructor_id = ?
            ");
            $count_stmt->bind_param("i", $instructor_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_data = $count_result->fetch_assoc();
            $subject_count = $count_data['subject_count'];
            
            // Don't allow removing the last subject from an instructor
            if ($subject_count <= 1) {
                error_log("Error: Cannot remove the only subject for this instructor");
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot remove the only subject. An instructor must have at least one subject. Delete the instructor instead.'
                ]);
                exit;
            }
            
            // Delete the relationship
            $delete_stmt = $conn_qr->prepare("
                DELETE FROM tbl_instructor_subjects 
                WHERE instructor_id = ? AND subject_id = ?
            ");
            $delete_stmt->bind_param("ii", $instructor_id, $subject_id);
            
            if ($delete_stmt->execute()) {
                error_log("Removed subject ID: $subject_id from instructor ID: $instructor_id");
                
                // If this was the current instructor/subject, update the session
                if (isset($_SESSION['current_instructor_id']) && 
                    $_SESSION['current_instructor_id'] == $instructor_id &&
                    isset($_SESSION['current_subject_id']) && 
                    $_SESSION['current_subject_id'] == $subject_id) {
                    
                    // Keep the instructor but reset the subject
                    unset($_SESSION['current_subject_id']);
                    unset($_SESSION['current_subject_name']);
                    error_log("Reset current subject in session");
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => "Subject '$subject_name' removed from instructor '$instructor_name'",
                    'data' => [
                        'instructor_id' => $instructor_id,
                        'instructor_name' => $instructor_name,
                        'subject_id' => $subject_id,
                        'subject_name' => $subject_name
                    ]
                ]);
            } else {
                error_log("Error removing subject: " . $conn_qr->error);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error removing subject: ' . $conn_qr->error
                ]);
            }
            break;
            
        default:
            error_log("Error: Invalid action '$action'");
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
} else {
    // Return error for non-POST requests
    error_log("Error: Invalid request method " . $_SERVER['REQUEST_METHOD']);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST is allowed.'
    ]);
}
?> 