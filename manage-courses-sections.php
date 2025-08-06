<?php
include("./conn/conn.php");
include("./includes/session_config.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href = './admin/login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'] ?? 1;

// Process form submission to add custom course/section
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_course' && !empty($_POST['course_name'])) {
            $courseName = trim($_POST['course_name']);
            
            try {
                // Check if course already exists
                $check = $conn->prepare("SELECT course_id FROM tbl_courses WHERE course_name = :course_name AND user_id = :user_id AND school_id = :school_id");
                $check->bindParam(':course_name', $courseName);
                $check->bindParam(':user_id', $user_id);
                $check->bindParam(':school_id', $school_id);
                $check->execute();
                
                if ($check->rowCount() === 0) {
                    // Add new course
                    $insert = $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (:course_name, :user_id, :school_id)");
                    $insert->bindParam(':course_name', $courseName);
                    $insert->bindParam(':user_id', $user_id);
                    $insert->bindParam(':school_id', $school_id);
                    $insert->execute();
                    
                    $message = "Course '{$courseName}' added successfully!";
                } else {
                    $message = "Course '{$courseName}' already exists!";
                }
            } catch (Exception $e) {
                $message = "Error adding course: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'add_section' && !empty($_POST['section_name']) && !empty($_POST['course_id'])) {
            $sectionName = trim($_POST['section_name']);
            $courseId = (int) $_POST['course_id'];
            
            try {
                // Check if section already exists
                $check = $conn->prepare("SELECT section_id FROM tbl_sections WHERE section_name = :section_name AND user_id = :user_id AND school_id = :school_id");
                $check->bindParam(':section_name', $sectionName);
                $check->bindParam(':user_id', $user_id);
                $check->bindParam(':school_id', $school_id);
                $check->execute();
                
                if ($check->rowCount() === 0) {
                    // Add new section
                    $insert = $conn->prepare("INSERT INTO tbl_sections (section_name, course_id, user_id, school_id) VALUES (:section_name, :course_id, :user_id, :school_id)");
                    $insert->bindParam(':section_name', $sectionName);
                    $insert->bindParam(':course_id', $courseId);
                    $insert->bindParam(':user_id', $user_id);
                    $insert->bindParam(':school_id', $school_id);
                    $insert->execute();
                    
                    $message = "Section '{$sectionName}' added successfully!";
                } else {
                    $message = "Section '{$sectionName}' already exists!";
                }
            } catch (Exception $e) {
                $message = "Error adding section: " . $e->getMessage();
            }
        }
    }
}

// Get existing courses for this user
$courses_query = "SELECT c.course_id, c.course_name, c.user_id 
                 FROM tbl_courses c 
                 WHERE (c.user_id = :user_id AND c.school_id = :school_id) OR c.user_id = 1
                 ORDER BY c.course_name";
$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bindParam(':user_id', $user_id);
$courses_stmt->bindParam(':school_id', $school_id);
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get existing sections for this user
$sections_query = "SELECT s.section_id, s.section_name, s.course_id, c.course_name, s.user_id 
                 FROM tbl_sections s
                 JOIN tbl_courses c ON s.course_id = c.course_id
                 WHERE ((s.user_id = :user_id AND s.school_id = :school_id) OR s.user_id = 1)
                 ORDER BY c.course_name, s.section_name";
$sections_stmt = $conn->prepare($sections_query);
$sections_stmt->bindParam(':user_id', $user_id);
$sections_stmt->bindParam(':school_id', $school_id);
$sections_stmt->execute();
$sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses & Sections</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        .container {
            padding-top: 30px;
        }
        .card {
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .custom-badge {
            font-size: 85%;
            padding: 0.25em 0.6em;
            margin-left: 5px;
        }
        .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-action {
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h1><i class="fas fa-cogs"></i> Manage Courses & Sections</h1>
                <p class="lead">Add and manage custom courses and sections for your account.</p>
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <a href="masterlist.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Masterlist</a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Add Course Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-plus-circle"></i> Add New Course/Grade Level
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_course">
                            <div class="form-group">
                                <label for="course_name">Course/Grade Level Name:</label>
                                <input type="text" class="form-control" id="course_name" name="course_name" required placeholder="e.g. BSCS, Grade 7, etc.">
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Add Course</button>
                        </form>
                    </div>
                </div>
                
                <!-- Course List -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-book"></i> Your Courses/Grade Levels
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($courses as $course): ?>
                            <li class="list-group-item">
                                <span>
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                    <?php if ($course['user_id'] == 1): ?>
                                        <span class="badge badge-info custom-badge">System Default</span>
                                    <?php elseif ($course['user_id'] == $user_id): ?>
                                        <span class="badge badge-success custom-badge">Custom</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Add Section Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-plus-circle"></i> Add New Section
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_section">
                            <div class="form-group">
                                <label for="course_id">Course/Grade Level:</label>
                                <select class="form-control" id="course_id" name="course_id" required>
                                    <option value="" selected disabled>Select Course/Grade Level</option>
                                    <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="section_name">Section Name:</label>
                                <input type="text" class="form-control" id="section_name" name="section_name" required placeholder="e.g. A, B, 1, 2, etc.">
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Add Section</button>
                        </form>
                    </div>
                </div>
                
                <!-- Section List -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users"></i> Your Sections
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($sections as $section): ?>
                            <li class="list-group-item">
                                <span>
                                    <?php echo htmlspecialchars($section['section_name']); ?>
                                    <span class="badge badge-secondary custom-badge"><?php echo htmlspecialchars($section['course_name']); ?></span>
                                    <?php if ($section['user_id'] == 1): ?>
                                        <span class="badge badge-info custom-badge">System Default</span>
                                    <?php elseif ($section['user_id'] == $user_id): ?>
                                        <span class="badge badge-success custom-badge">Custom</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
