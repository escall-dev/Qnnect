<?php
// Use consistent session handling
require_once 'includes/session_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    header("Location: admin/login.php");
    exit;
}

// Include database connections
include('./conn/db_connect.php');

// Get user's school_id from session (already set during login)
$school_id = $_SESSION['school_id'];
$user_id = $_SESSION['user_id'];

// Debug: Log session contents
error_log('Masterlist session contents: ' . print_r($_SESSION, true));

// Check if school_id column exists in tbl_student, if not add it
$check_column_query = "SHOW COLUMNS FROM tbl_student LIKE 'school_id'";
$column_result = $conn_qr->query($check_column_query);

if ($column_result->num_rows == 0) {
    // Add school_id column to tbl_student table
    $add_column_query = "ALTER TABLE tbl_student ADD COLUMN school_id INT DEFAULT 1";
    $conn_qr->query($add_column_query);
    
    // Update existing records to have school_id = 1
    $update_query = "UPDATE tbl_student SET school_id = 1 WHERE school_id IS NULL";
    $conn_qr->query($update_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Attendance System</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./styles/masterlist.css">
    <link rel="stylesheet" href="./styles/pagination.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* Main content styles */
        .main {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease;
            width: calc(100% - 260px);
            z-index: 1;
        }

        /* When sidebar is closed */
        .sidebar.close ~ .main {
            margin-left: 78px;
            width: calc(100% - 78px);
        }

        /* Hamburger menu rotation */
        .sidebar-toggle {
            transition: transform 0.3s ease;
            z-index: 101;
        }

        .sidebar-toggle.rotate {
            transform: rotate(180deg);
        }

        /* Responsive styles */
        @media (max-width: 1200px) {
            .main.collapsed {
                margin-left: 200px;
                width: calc(100% - 200px);
            }
            .main.active {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
        }

        @media (max-width: 992px) {
            .main.collapsed {
                margin-left: 180px;
                width: calc(100% - 180px);
            }
            .main.active {
                margin-left: 50px;
                width: calc(100% - 50px);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 260px;
                transform: translateX(0);
            }
            
            .sidebar.close {
                transform: translateX(-100%) !important;
                width: 260px !important;
            }
            
            .main {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .main.collapsed {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }

        /* Student container styles */
        .student-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            transition: all 0.3s ease;
            width: 85%;
            margin-left: 80px;
            margin-bottom: 20px;
        }

        .student-list {
            background-color: white;
            border-radius: 20px;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        /* Adjust the title and button spacing */
        .student-list .title {
            margin: 0 0 15px 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Adjust table container to use full width */
        .table-container {
            width: 100%;
            overflow-x: auto;
            transition: all 0.3s ease;
            margin: 15px 0; /* Add some vertical spacing */
        }

        /* Table styles */
        .table {
            width: 100%;
            min-width: 100%;
            background-color: #098744;
            color: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th,
        .table tbody td {
            border-color: rgba(255, 255, 255, 0.2) !important;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #076a34;
        }

        /* Action buttons styling */
        .action-button {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .action-button .btn {
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.2s ease;
        }

        .action-button .btn:hover {
            transform: translateY(-1px);
        }

        .action-button .btn-success {
            background-color: #ffffff;
            border-color: #ffffff;
            color: #098744;
        }

        .action-button .btn-success:hover {
            background-color: #f0f0f0;
            border-color: #f0f0f0;
            color: #076a34;
        }

        .action-button .btn-secondary {
            background-color: #ffffff;
            border-color: #ffffff;
            color: #6c757d;
        }

        .action-button .btn-danger {
            background-color: #ffffff;
            border-color: #ffffff;
            color: #dc3545;
        }

        .action-button .btn-secondary:hover,
        .action-button .btn-danger:hover {
            background-color: #f0f0f0;
            border-color: #f0f0f0;
        }

        /* Adjust the hr spacing */
        .student-list hr {
            margin: 15px 0;
        }

        /* Adjust pagination container spacing */
        .pagination-container {
            width: 100%;
            margin-top: 20px;
        }

        /* Custom pagination styling to match leaderboard */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px !important; /* Force 5px gap */
            margin: 20px 0;
        }
        
        .pagination .page-item .page-link {
            color: #212529;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 0.5rem 0.75rem;
            min-width: 40px;
            text-align: center;
            transition: all 0.2s ease;
            margin: 0 !important; /* Remove any default margins */
        }
        
        /* Override any other styles that might add spacing */
        .pagination .page-item {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .pagination.justify-content-center {
            gap: 5px !important;
        }
        
        /* Ensure links don't have extra spacing */
        .page-link {
            margin: 0 !important;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #098744;
            border-color: #098744;
            color: white;
            z-index: 3;
        }
        
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            cursor: auto;
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        
        .pagination .page-item:not(.active) .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #076a34;
            z-index: 2;
        }

        /* When sidebar is closed - maintain full width usage */
        .sidebar.close ~ .main .student-container {
            width: 98%;
            margin-left: 20px;
        }

        .sidebar.close ~ .main .student-list,
        .sidebar.close ~ .main .table-container {
            width: 100%;
        }

        /* Remove any conflicting styles */
        .main.collapsed {
            margin-left: 78px;
            width: calc(100% - 78px);
        }

        /* Face Capture Styles */
        .camera-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            border: 3px solid #098744;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .camera-container.captured {
            border-color: #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        }
        
        .face-outline {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px dashed #fff;
            border-radius: 50%;
            box-sizing: border-box;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .face-capture-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }
        
        #recaptureFace {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
            animation: pulse 1.5s infinite;
        }
        
        #recaptureFace:hover {
            background-color: #e0a800;
            border-color: #e0a800;
            animation: none;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        #capturePreview {
            transition: all 0.3s ease;
        }
        
        .capture-feedback {
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            text-align: center;
        }

        /* Add these styles to your existing CSS */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 260px;
            background: #098744;
            z-index: 100;
            transition: all 0.3s ease !important;
        }

        .sidebar.close {
            width: 78px !important;
        }

        .main {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease !important;
            width: calc(100% - 260px);
            z-index: 1;
        }

        .main.collapsed {
            margin-left: 78px !important;
            width: calc(100% - 78px) !important;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 260px;
                transform: translateX(0);
            }
            
            .sidebar.close {
                transform: translateX(-100%) !important;
                width: 260px !important;
            }
            
            .main {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .main.collapsed {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }

        /* Update the sidebar toggle button styles */
        .sidebar-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 101;
            background-color: #098744;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background-color: #076a34; /* Slightly darker shade for hover */
            color: white;
        }

        .sidebar-toggle:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(9, 135, 68, 0.3);
        }

        /* Table styles */
        .table {
            width: 100%;
            min-width: 100%;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .student-container {
                padding: 15px;
            }
            
            .table-container {
                font-size: 14px;
            }
        }

        /* Add smooth transitions to all elements */
        .main, .student-container, .table-container, .table {
            transition: all 0.3s ease !important;
        }

        .pagination-container .pagination {
            gap: 10px !important;
        }

        .pagination-container .page-item {
            margin: 0 10px !important;
        }

        /* Print button hover effect */
        .print-btn:hover {
            background-color: #076a34 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            cursor: pointer;
        }
    </style>
    
    

   
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading-spinner">
        <div class="spinner"></div>
    </div>

    <!-- Overlay -->
    <div class="overlay"></div>

    <!-- Custom Alert Box -->
    <div id="customAlert" class="custom-alert"></div>

    <?php include('./components/sidebar-nav.php');?>
  
    <div class="main collapsed" id="main">
        <div class="student-container">
            <div class="student-list">
                <div class="title" style="justify-content: center; align-items: center; gap: 20px; display: flex;">
                    <span class="text-center w-100"><h4><i class="fas fa-chalkboard-teacher"></i> List of Students</h4></span>
                </div>
                <hr>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <!-- Search bar on the left -->
                        <div class="input-group" style="max-width: 300px;">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="search" class="form-control" placeholder="Search students..." id="studentSearch">
                        </div>
                        <!-- Filter, Sort, and Apply button on the right -->
                        <div class="d-flex align-items-center">
                            <span class="mr-2" style="white-space: nowrap;">Filter By:</span>
                            <select class="form-control mx-2" id="filterBy" style="width: 150px;">
                                <option value="">All Courses</option>
                                <option value="BSIS-301">BSIS-301</option>
                                <option value="BSIS-302">BSIS-302</option>
                                <option value="BSIT-301">BSIT-301</option>
                                <option value="BSIT-302">BSIT-302</option>
                                <option value="BSIT-401">BSIT-401</option>
                                <option value="BSIT-402">BSIT-402</option>
                            </select>
                            <select class="form-control mx-2" id="sortBy" style="width: 150px;">
                                <option value="">Sort By:</option>
                                <option value="name_asc">Name (A-Z)</option>
                                <option value="name_desc">Name (Z-A)</option>
                                <option value="course_asc">Course (A-Z)</option>
                                <option value="course_desc">Course (Z-A)</option>
                            </select>
                            <button class="btn mx-1" style="background-color: #098744; color: white;" id="applyFilters">
                                <i class="fas fa-check"></i> Apply
                            </button>
                            <button class="btn btn-secondary mx-1" id="resetFilters">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                    <!-- Export/Print and Add Student Buttons Row -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <button class="btn" style="background-color: #098744; color: white; margin-right: 8px;" onclick="exportToExcel()">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button class="btn" style="background-color: #098744; color: white; margin-right: 8px;" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button class="btn" style="background-color: #098744; color: white; margin-right: 8px;" onclick="printTable()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                        <button class="btn" style="background-color: #098744; color: white; min-width: 140px; font-weight: 500;" data-toggle="modal" data-target="#addStudentModal">
                            <i class="fas fa-user-plus"></i> Add Student
                        </button>
                    </div>
                </div>
                <div class="table-container table-responsive">
                    <table class="table text-center table-sm table-bordered" id="studentTable">
                        <thead style="background-color: #098744; color: white; position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Course & Section</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody style="background-color: #098744; color: white;">
                        <?php 
include ('./conn/conn.php');




// Add session check for user isolation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('User session expired or not logged in. Please log in again.'); window.location.href = 'login.php';</script>";
    exit();
}
$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'] ?? 1;

// Get all students for client-side search (filtered by school_id AND user_id)
$allStudentsStmt = $conn->prepare("SELECT * FROM tbl_student WHERE school_id = :school_id AND user_id = :user_id ORDER BY tbl_student_id DESC");
$allStudentsStmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
$allStudentsStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$allStudentsStmt->execute();
$allStudents = $allStudentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Store all students in a hidden input for JavaScript
echo '<input type="hidden" id="allStudentsData" value="' . htmlspecialchars(json_encode($allStudents)) . '">';

// Regular pagination for initial display (filtered by school_id AND user_id)
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("SELECT * FROM tbl_student WHERE school_id = :school_id AND user_id = :user_id ORDER BY tbl_student_id DESC LIMIT :limit OFFSET :offset");
$stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student WHERE school_id = :school_id AND user_id = :user_id");
$totalStmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
$totalStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$totalStmt->execute();
$totalRecords = $totalStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

foreach ($result as $row) {
                            outputStudentRow($row);
                        }

                        function outputStudentRow($row) {
    $studentID = $row["tbl_student_id"];
    $studentName = $row["student_name"];
    $studentCourse = $row["course_section"];
    $qrCode = $row["generated_code"];
?>
                            <tr class="student-row">
    <th scope="row" id="studentID-<?= $studentID ?>"><?= $studentID ?></th>
    <td id="studentName-<?= $studentID ?>"><?= $studentName ?></td>
    <td id="studentCourse-<?= $studentID ?>"><?= $studentCourse ?></td>
    <td>
        <div class="action-button">
            <button class="btn btn-success btn-sm qr-button" data-id="<?= $studentID ?>" data-name="<?= $studentName ?>" data-qr="<?= $qrCode ?>">
                <i class="fas fa-qrcode"></i>
            </button>
            <button class="btn btn-secondary btn-sm" onclick="updateStudent(<?= $studentID ?>)">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-danger btn-sm" onclick="deleteStudent(<?= $studentID ?>)">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </td>
</tr>
<?php
}
?>
                        </tbody>
                    </table>
                </div>
                <!-- Centered Add Student Button below the table -->
              

                    <!-- Update the pagination section -->
                    <div class="pagination-container" id="paginationContainer">
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center" style="gap: 5px !important; margin-top: 20px;">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>" style="margin: 0 !important;">
                <a class="page-link" href="?page=1" style="color: #212529; border-radius: 0.25rem;">First</a>
            </li>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>" style="margin: 0 !important;">
                <a class="page-link" href="?page=<?= $page - 1 ?>" style="color: #212529; border-radius: 0.25rem;">Previous</a>
            </li>
            <?php 
                                $maxVisiblePages = 5;
            $startPage = max(1, min($page - floor($maxVisiblePages / 2), $totalPages - $maxVisiblePages + 1));
            $endPage = min($startPage + $maxVisiblePages - 1, $totalPages);
            
            for ($i = $startPage; $i <= $endPage; $i++): 
            ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>" style="margin: 0 !important;">
                    <a class="page-link" href="?page=<?= $i ?>" style="<?= $i === $page ? 'background-color: #098744; border-color: #098744; color: white;' : 'color: #212529;' ?> border-radius: 0.25rem;"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>" style="margin: 0 !important;">
                <a class="page-link" href="?page=<?= $page + 1 ?>" style="color: #212529; border-radius: 0.25rem;">Next</a>
            </li>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>" style="margin: 0 !important;">
                <a class="page-link" href="?page=<?= $totalPages ?>" style="color: #212529; border-radius: 0.25rem;">Last</a>
            </li>
        </ul>
    </nav>
    </div>
</div>
</div>
            </div>
    </div>

    <!-- Check if course and section tables exist, if not create them -->
    <?php
    // Check if both tbl_courses and tbl_sections tables exist
    $check_courses_table_query = "SHOW TABLES LIKE 'tbl_courses'";
    $courses_table_result = $conn_qr->query($check_courses_table_query);
    
    $check_sections_table_query = "SHOW TABLES LIKE 'tbl_sections'";
    $sections_table_result = $conn_qr->query($check_sections_table_query);
    
    if ($courses_table_result->num_rows == 0 || $sections_table_result->num_rows == 0) {
        // Include the setup file to create the tables
        include('./db_setup/create_course_section_tables.php');
        // Also update section-course relationships if needed
        include('./update_section_courses.php');
    }
    
    // Get all courses from database - filter by user_id and school_id
    $courses_query = "SELECT DISTINCT c.course_id, c.course_name, c.user_id, c.school_id FROM tbl_courses c 
                    WHERE (c.user_id = :user_id AND c.school_id = :school_id) OR c.user_id = 1
                    ORDER BY c.course_name";
    $courses_stmt = $conn->prepare($courses_query);
    $courses_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $courses_stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all sections from database with their associated courses - filter by user_id and school_id
    $sections_query = "SELECT s.section_id, s.section_name, s.course_id, c.course_name, s.user_id, s.school_id
                      FROM tbl_sections s 
                      LEFT JOIN tbl_courses c ON s.course_id = c.course_id
                      WHERE ((s.user_id = :user_id AND s.school_id = :school_id) OR s.user_id = 1)
                      ORDER BY c.course_name, s.section_name";
    $sections_stmt = $conn->prepare($sections_query);
    $sections_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $sections_stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
    $sections_stmt->execute();
    $sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For debugging purposes
    error_log("Sections data: " . print_r($sections, true));
    ?>

    <!-- Add Modal -->
    <div class="modal fade" id="addStudentModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="addStudent" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudent"><i class="fas fa-user-plus"></i> Add Student</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="./endpoint/add-student.php" method="POST" id="addStudentForm">

                        <div class="form-group">
                            <label for="studentName"><i class="fas fa-user"></i> Full Name:</label>
                            <input type="text" class="form-control" id="studentName" name="student_name" required>
                        </div>
                        <div class="form-group">
                            <label for="studentCourse"><i class="fas fa-book"></i> Course</label>
                            <div class="input-group">
                                <select class="form-control" id="studentCourse" name="course" onchange="updateSections(); toggleCustomCourse();">
                                    <option value="" disabled selected>Select Course</option>
                                    <?php 
                                    // List all available courses
                                    foreach ($courses as $course) {
                                        echo "<option value=\"{$course['course_name']}\">{$course['course_name']}</option>";
                                    }
                                    ?>
                                    <option value="custom">+ Add Custom Course</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Custom Course Input (initially hidden) -->
                        <div class="form-group" id="customCourseGroup" style="display: none;">
                            <label for="customCourse"><i class="fas fa-edit"></i> Custom Course Name</label>
                            <input type="text" class="form-control" id="customCourse" name="custom_course" 
                                placeholder="Enter custom course/grade level" 
                                title="Enter any custom course name">
                            <small class="form-text text-muted">Enter any course name you want</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="studentSection"><i class="fas fa-users"></i> Section</label>
                            <div class="input-group">
                                <select class="form-control" id="studentSection" name="section" onchange="toggleCustomSection();">
                                    <option value="" disabled selected>Select a course first</option>
                                    <option value="custom" style="display: none;" id="customSectionOption">+ Add Custom Section</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Custom Section Input (initially hidden) -->
                        <div class="form-group" id="customSectionGroup" style="display: none;">
                            <label for="customSection"><i class="fas fa-edit"></i> Custom Section Name</label>
                            <input type="text" class="form-control" id="customSection" name="custom_section" 
                                placeholder="Enter custom section" 
                                title="Enter any custom section name">
                            <small class="form-text text-muted">Enter any section name you want</small>
                        </div>
                        
                        <!-- Complete Custom Course & Section input -->
                        <div class="form-group">
                            <label for="completeCourseSection"><i class="fas fa-graduation-cap"></i> Custom Course or Grade Level & Section</label>
                            <input type="text" class="form-control" id="completeCourseSection" name="complete_course_section" 
                                placeholder="Enter Course-Section directly (e.g. 11 - ICT LAPU)">
                            <small class="form-text text-muted">Format for custom: Course-Section (e.g. BSCS-101)</small>
                        </div>
                        
                        <!-- Hidden field to store the combined course-section value -->
                        <input type="hidden" id="courseSectionCombined" name="course_section" value="">
                        
                        <!-- Add JavaScript for course-section relationship -->
                        <script>
                        // JavaScript to handle dynamic section loading based on course selection
                        function updateSections() {
                            // Get the selected course
                            const courseSelect = document.getElementById('studentCourse');
                            const sectionSelect = document.getElementById('studentSection');
                            const courseValue = courseSelect.value;
                            const customSectionOption = document.getElementById('customSectionOption');
                            
                            // Clear current section options but keep the custom option
                            sectionSelect.innerHTML = '';
                            
                            // If no course is selected, show default option
                            if (!courseValue) {
                                const defaultOption = document.createElement('option');
                                defaultOption.value = '';
                                defaultOption.disabled = true;
                                defaultOption.selected = true;
                                defaultOption.textContent = 'Select a course first';
                                sectionSelect.appendChild(defaultOption);
                                
                                // Add the custom section option (hidden initially)
                                const customOption = document.createElement('option');
                                customOption.value = 'custom';
                                customOption.textContent = '+ Add Custom Section';
                                customOption.id = 'customSectionOption';
                                customOption.style.display = 'none';
                                sectionSelect.appendChild(customOption);
                                return;
                            }
                            
                            // If "custom" course is selected, only show custom section option
                            if (courseValue === 'custom') {
                                const defaultOption = document.createElement('option');
                                defaultOption.value = '';
                                defaultOption.disabled = true;
                                defaultOption.textContent = 'Select Section or Add Custom';
                                sectionSelect.appendChild(defaultOption);
                                
                                // Show custom section option
                                const customOption = document.createElement('option');
                                customOption.value = 'custom';
                                customOption.textContent = '+ Add Custom Section';
                                customOption.id = 'customSectionOption';
                                customOption.selected = true;
                                sectionSelect.appendChild(customOption);
                                
                                // Trigger the custom section display
                                toggleCustomSection();
                                return;
                            }
                            
                            // For regular courses, get sections from PHP-provided data
                            const sections = <?php echo json_encode($sections); ?>;
                            
                            // Find sections associated with the selected course
                            const filteredSections = sections.filter(section => {
                                console.log("Checking section:", section, "looking for course:", courseValue);
                                return section.course_name === courseValue;
                            });
                            
                            console.log("All sections:", sections);
                            console.log("Filtered sections for " + courseValue + ":", filteredSections);
                            
                            // Add default option
                            const defaultOption = document.createElement('option');
                            defaultOption.value = '';
                            defaultOption.disabled = true;
                            defaultOption.selected = true;
                            defaultOption.textContent = filteredSections.length === 0 ? 'No sections available - select custom' : 'Select Section';
                            sectionSelect.appendChild(defaultOption);
                            
                            // Add sections to dropdown if there are any
                            if (filteredSections.length > 0) {
                                filteredSections.forEach(section => {
                                    const option = document.createElement('option');
                                    option.value = section.section_name;
                                    option.textContent = section.section_name;
                                    sectionSelect.appendChild(option);
                                });
                            }
                            
                            // Always add custom section option
                            const customOption = document.createElement('option');
                            customOption.value = 'custom';
                            customOption.textContent = '+ Add Custom Section';
                            customOption.id = 'customSectionOption';
                            sectionSelect.appendChild(customOption);
                            
                            // Update the hidden combined field
                            updateCombinedField();
                        }
                        
                        // Toggle custom course input visibility
                        function toggleCustomCourse() {
                            const courseSelect = document.getElementById('studentCourse');
                            const customCourseGroup = document.getElementById('customCourseGroup');
                            const customCourseInput = document.getElementById('customCourse');
                            
                            if (courseSelect.value === 'custom') {
                                customCourseGroup.style.display = 'block';
                                customCourseInput.required = true;
                                customCourseInput.focus();
                            } else {
                                customCourseGroup.style.display = 'none';
                                customCourseInput.required = false;
                            }
                            
                            updateCombinedField();
                        }
                        
                        // Toggle custom section input visibility
                        function toggleCustomSection() {
                            const sectionSelect = document.getElementById('studentSection');
                            const customSectionGroup = document.getElementById('customSectionGroup');
                            const customSectionInput = document.getElementById('customSection');
                            
                            if (sectionSelect.value === 'custom') {
                                customSectionGroup.style.display = 'block';
                                customSectionInput.required = true;
                                customSectionInput.focus();
                            } else {
                                customSectionGroup.style.display = 'none';
                                customSectionInput.required = false;
                            }
                            
                            updateCombinedField();
                        }

                        // Update the hidden field with combined course-section value
                        function updateCombinedField() {
                            const courseSelect = document.getElementById('studentCourse');
                            const sectionSelect = document.getElementById('studentSection');
                            const customCourseInput = document.getElementById('customCourse');
                            const customSectionInput = document.getElementById('customSection');
                            const completeCourseSection = document.getElementById('completeCourseSection');
                            const combinedField = document.getElementById('courseSectionCombined');
                            
                            // First check if we have a direct entry in the complete course section field
                            if (completeCourseSection && completeCourseSection.value.trim()) {
                                const completeValue = completeCourseSection.value.trim();
                                if (completeValue && completeValue.includes('-')) {
                                    combinedField.value = completeValue;
                                    
                                    // Disable individual field validations since we're using the combined field
                                    if (customCourseInput) customCourseInput.setCustomValidity("");
                                    if (customSectionInput) customSectionInput.setCustomValidity("");
                                    
                                    // Mark as valid
                                    completeCourseSection.setCustomValidity("");
                                    completeCourseSection.classList.remove('is-invalid');
                                    completeCourseSection.classList.add('is-valid');
                                    
                                    return;
                                } else if (!completeValue) {
                                    completeCourseSection.setCustomValidity("Course-section cannot be empty");
                                    completeCourseSection.classList.add('is-invalid');
                                    completeCourseSection.classList.remove('is-valid');
                                } else if (!completeValue.includes('-')) {
                                    completeCourseSection.setCustomValidity("Format must be Course-Section (e.g. BSCS-101)");
                                    completeCourseSection.classList.add('is-invalid');
                                    completeCourseSection.classList.remove('is-valid');
                                }
                            }
                            
                            // If no valid complete value, proceed with individual fields
                            // Determine course value (dropdown or custom input)
                            let courseValue = courseSelect.value;
                            if (courseValue === 'custom' && customCourseInput.value) {
                                // Validate that custom course is not empty
                                if (!customCourseInput.value.trim()) {
                                    customCourseInput.setCustomValidity("Custom course cannot be empty");
                                    customCourseInput.classList.add('is-invalid');
                                    customCourseInput.classList.remove('is-valid');
                                } else {
                                    customCourseInput.setCustomValidity("");
                                    customCourseInput.classList.remove('is-invalid');
                                    customCourseInput.classList.add('is-valid');
                                    courseValue = customCourseInput.value.trim();
                                }
                            }
                            
                            // Determine section value (dropdown or custom input)
                            let sectionValue = sectionSelect.value;
                            if (sectionValue === 'custom' && customSectionInput.value) {
                                // Validate that custom section is not empty
                                if (!customSectionInput.value.trim()) {
                                    customSectionInput.setCustomValidity("Custom section cannot be empty");
                                    customSectionInput.classList.add('is-invalid');
                                    customSectionInput.classList.remove('is-valid');
                                } else {
                                    customSectionInput.setCustomValidity("");
                                    customSectionInput.classList.remove('is-invalid');
                                    customSectionInput.classList.add('is-valid');
                                    sectionValue = customSectionInput.value.trim();
                                }
                            }
                            
                            // Only set combined value if both are valid
                            if (courseValue && courseValue !== 'custom' && 
                                sectionValue && sectionValue !== 'custom') {
                                combinedField.value = courseValue + '-' + sectionValue;
                            } else {
                                combinedField.value = '';
                            }
                        }
                        
                        // Add event listeners for real-time validation
                        document.addEventListener('DOMContentLoaded', function() {
                            const customCourseInput = document.getElementById('customCourse');
                            const customSectionInput = document.getElementById('customSection');
                            const completeCourseSection = document.getElementById('completeCourseSection');
                            
                            customCourseInput.addEventListener('input', function() {
                                // Clear the direct entry field when using individual fields
                                if (completeCourseSection && this.value.trim()) {
                                    completeCourseSection.value = '';
                                }
                                updateCombinedField();
                            });
                            
                            customSectionInput.addEventListener('input', function() {
                                // Clear the direct entry field when using individual fields
                                if (completeCourseSection && this.value.trim()) {
                                    completeCourseSection.value = '';
                                }
                                updateCombinedField();
                            });
                            
                            // Add event listener for complete course section field
                            if (completeCourseSection) {
                                completeCourseSection.addEventListener('input', function() {
                                    // Clear the individual fields when using direct entry
                                    if (this.value.trim()) {
                                        if (customCourseInput) customCourseInput.value = '';
                                        if (customSectionInput) customSectionInput.value = '';
                                    }
                                    updateCombinedField();
                                });
                            }
                        });

                        // Add event listeners
                        document.addEventListener('DOMContentLoaded', function() {
                            const courseSelect = document.getElementById('studentCourse');
                            const sectionSelect = document.getElementById('studentSection');
                            const customCourseInput = document.getElementById('customCourse');
                            const customSectionInput = document.getElementById('customSection');
                            
                            // Course and section select events
                            courseSelect.addEventListener('change', updateSections);
                            sectionSelect.addEventListener('change', updateCombinedField);
                            sectionSelect.addEventListener('change', toggleCustomSection);
                            
                            // Custom input fields events
                            customCourseInput.addEventListener('input', updateCombinedField);
                            customSectionInput.addEventListener('input', updateCombinedField);
                            
                            // Initialize the sections dropdown
                            updateSections();
                            toggleCustomCourse();
                        });
                        </script>
                                
                        </div>
                        
                        <!-- Face Capture Section -->
                        <div class="face-capture-section">
                            <h5><i class="fas fa-camera"></i> Face Capture</h5>
                            <p class="text-muted">Please capture your face for verification purposes</p>
                            
                            <div class="camera-container" id="cameraContainer">
                                <video id="faceVideo" autoplay playsinline style="width: 100%; height: auto; display: block; transform: scaleX(1);"></video>
                                <canvas id="faceCanvas" style="display: none;"></canvas>
                                <div class="camera-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; box-sizing: border-box; pointer-events: none;">
                                    <div class="face-outline" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 200px; height: 200px; border: 2px dashed #fff; border-radius: 50%; box-sizing: border-box; pointer-events: none;"></div>
                                </div>
                                
                                <!-- Countdown Overlay -->
                                <div id="countdownOverlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 10; justify-content: center; align-items: center;">
                                    <div style="font-size: 80px; color: white; font-weight: bold; text-shadow: 2px 2px 4px #000;">3</div>
                                </div>
                            </div>
                            
                            <!-- Capture Preview Section -->
                            <div id="capturePreview" class="mt-3 text-center" style="display: none;">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> Face captured successfully!
                                    <p class="mb-0 mt-1">If you're not satisfied with this image, click "Recapture".</p>
                                </div>
                            </div>
                            
                            <div class="face-capture-buttons">
                                <button type="button" class="btn" id="startCamera" style="background-color: #098744; color: white;">
                                    <i class="fas fa-video"></i> Start Camera
                                </button>
                                <button type="button" class="btn btn-primary" id="captureFace" disabled>
                                    <i class="fas fa-camera"></i> Capture Face
                                </button>
                                <button type="button" class="btn btn-warning" id="recaptureFace" style="display: none;">
                                    <i class="fas fa-redo"></i> Recapture
                                </button>
                            </div>
                            
                            <div class="face-status mt-3 text-center">
                                <p id="faceStatusMessage">Please start the camera and position your face within the circle</p>
                            </div>
                            
                            <input type="hidden" name="face_image_data" id="faceImageData">
                            <input type="hidden" name="face_verified" id="faceVerified" value="0">
                        </div>
                        
                        <button type="button" class="btn mt-3" style="background-color: #098744; color: white;" class="form-control qr-generator" id="generateQrBtn" onclick="generateQrCode()" disabled>
                            <i class="fas fa-qrcode"></i> Generate QR Code
                        </button>

                        <div class="qr-con text-center" style="display: none;">
                            <input type="hidden" class="form-control" id="generatedCode" name="generated_code">
                            <p>Take a pic with your qr code.</p>
                            <img class="mb-4" src="" id="qrImg" alt="">
                        </div>
                        <div class="modal-footer modal-close" style="display: none;">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-dark">Add List</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" id="updateStudentModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="updateStudent" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStudent"><i class="fas fa-user-edit"></i> Update Student</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="./endpoint/update-student.php" method="POST" id="updateStudentForm">
                        <input type="hidden" class="form-control" id="updateStudentId" name="tbl_student_id">
                        <div class="form-group">
                            <label for="updateStudentName"><i class="fas fa-user"></i> Full Name:</label>
                            <input type="text" class="form-control" id="updateStudentName" name="student_name">
                        </div>
                        <div class="form-group">
                            <label for="updateStudentCourse"><i class="fas fa-book"></i> Course & Section:</label>
                            <select class="form-control" id="updateStudentCourse" name="course_section" required>
                                <option value="" disabled selected>Select Course and Section</option>
                                <?php 
                                // Dynamic course-section combinations for update form
                                // Track unique course-section combinations to avoid duplicates
                                $seen = array();
                                
                                foreach ($courses as $course) {
                                    foreach ($sections as $section) {
                                        if ($section['course_id'] == $course['course_id']) {
                                            $combo = $course['course_name'] . '-' . $section['section_name'];
                                            if (!in_array($combo, $seen)) {
                                                $seen[] = $combo;
                                                echo "<option value=\"{$combo}\">{$combo}</option>";
                                            }
                                        }
                                    }
                                }
                                ?>
                                <option value="custom">+ Add Custom Course & Section</option>
                            </select>
                            <input type="text" class="form-control mt-2" id="updateCustomStudentCourse" name="update_custom_course_section" placeholder="Enter custom Course-Section (e.g. BSCS-101)" style="display: none;">
                            <small class="form-text text-muted">Format for custom: Course-Section (e.g. BSCS-101)</small>
                            <input type="hidden" id="updateFinalCourseSection" name="update_final_course_section">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-dark">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

    <!-- Face API JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface"></script>
    
    <script src="./functions/mstrlst.js"></script>

    <script> 
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.querySelector('.fa-bars'); // or your menu icon class
            const sidebar = document.querySelector('.sidebar');
            const main = document.querySelector('.main');

            // Check initial state
            console.log('Initial sidebar classes:', sidebar?.classList);
            console.log('Initial main classes:', main?.classList);

            function toggleSidebar() {
                if (sidebar && main) {
                    sidebar.classList.toggle('close');
                    main.classList.toggle('expanded');
                    
                    if (sidebar.classList.contains('close')) {
                        // When sidebar is closed - expand content
                        document.querySelector('.student-container').style.width = '98%';
                        document.querySelector('.table-container').style.width = '100%';
                        document.querySelector('.student-list').style.width = '100%';
                        document.querySelector('.student-container').style.marginLeft = '20px';
                    } else {
                        // When sidebar is open - shrink content more
                        document.querySelector('.student-container').style.width = '85%';
                        document.querySelector('.table-container').style.width = '100%';
                        document.querySelector('.student-list').style.width = '100%';
                        document.querySelector('.student-container').style.marginLeft = '80px'; // Increased from 40px to 80px
                    }
                    
                    // Force table redraw (no DataTables anymore)
                }
            }

            if (toggleButton) {
                toggleButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            }

            // Keep your existing submenu code
            function toggleSubmenu(event) {
                event.preventDefault();
                const toggle = event.currentTarget;
                const submenu = toggle.nextElementSibling;
                const arrow = toggle.querySelector('.arrow');

                // Close all other submenus
                const allSubmenus = document.querySelectorAll('.submenu');
                allSubmenus.forEach(menu => {
                    if (menu !== submenu && menu.style.display === 'block') {
                        menu.style.display = 'none';
                        const otherArrow = menu.previousElementSibling.querySelector('.arrow');
                        if (otherArrow) otherArrow.style.transform = '';
                    }
                });

                // Toggle current submenu
                if (submenu) {
                    submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
                    if (arrow) {
                        arrow.style.transform = submenu.style.display === 'block' ? 'rotate(180deg)' : '';
                    }
                }
            }

            // Add click event listeners for submenus
            const submenuToggles = document.querySelectorAll('.submenu-toggle');
            submenuToggles.forEach(toggle => {
                toggle.addEventListener('click', toggleSubmenu);
            });
        });
    </script>

    <!-- Face Recognition Script -->
    <script>
        let video = document.getElementById('faceVideo');
        let canvas = document.getElementById('faceCanvas');
        let ctx = canvas.getContext('2d');
        let startCameraBtn = document.getElementById('startCamera');
        let captureFaceBtn = document.getElementById('captureFace');
        let generateQrBtn = document.getElementById('generateQrBtn');
        let faceStatusMessage = document.getElementById('faceStatusMessage');
        let faceVerified = document.getElementById('faceVerified');
        let stream = null;
        let model = null;
        
        // Load BlazeFace model
        async function loadFaceDetectionModel() {
            try {
                model = await blazeface.load();
                console.log('Face detection model loaded');
            } catch (error) {
                console.error('Error loading face detection model:', error);
                faceStatusMessage.textContent = 'Error loading face detection model. Please try again.';
                faceStatusMessage.style.color = 'red';
            }
        }
        
        // Initialize face detection
        loadFaceDetectionModel();
        
        // Start camera
        startCameraBtn.addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    } 
                });
                video.srcObject = stream;
                
             
                video.style.transform = 'scaleX(-1)';
                
                // Set canvas dimensions to match video
                video.onloadedmetadata = () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                };
                
                startCameraBtn.disabled = true;
                captureFaceBtn.disabled = false;
                faceStatusMessage.textContent = 'Camera started. Position your face within the circle and click Capture.';
                
                // Start face detection
                detectFace();
                
            } catch (error) {
                console.error('Error accessing camera:', error);
                faceStatusMessage.textContent = 'Error accessing camera. Please check permissions and try again.';
                faceStatusMessage.style.color = 'red';
            }
        });
        
        // Detect face in video stream
        async function detectFace() {
            if (!model || !video.srcObject) return;
            
            try {
                const predictions = await model.estimateFaces(video, false);
                
                if (predictions.length > 0) {
                    faceStatusMessage.textContent = 'Face detected! You can now capture your face.';
                    faceStatusMessage.style.color = 'green';
                } else {
                    faceStatusMessage.textContent = 'No face detected. Please position your face within the circle.';
                    faceStatusMessage.style.color = 'red';
                }
                
                // Continue detection if stream is active
                if (video.srcObject) {
                    requestAnimationFrame(detectFace);
                }
                
            } catch (error) {
                console.error('Error during face detection:', error);
            }
        }
        
        // Capture face with countdown
        captureFaceBtn.addEventListener('click', () => {
            if (!video.srcObject) return;
            
            // Start countdown
            const countdownOverlay = document.getElementById('countdownOverlay');
            const countdownDisplay = countdownOverlay.querySelector('div');
            countdownOverlay.style.display = 'flex';
            
            let count = 3;
            countdownDisplay.textContent = count;
            
            const countdownInterval = setInterval(() => {
                count--;
                if (count > 0) {
                    countdownDisplay.textContent = count;
                } else {
                    // Clear interval and hide overlay
                    clearInterval(countdownInterval);
                    countdownOverlay.style.display = 'none';
                    
                    // Capture the image
                    captureImage();
                }
            }, 1000);
        });
        
        // Function to capture the image
        function captureImage() {
            // Set canvas size to match video dimensions
            const videoWidth = video.videoWidth;
            const videoHeight = video.videoHeight;
            canvas.width = videoWidth;
            canvas.height = videoHeight;
            
            // Calculate the center and dimensions for cropping
            const size = Math.min(videoWidth, videoHeight);
            const centerX = (videoWidth - size) / 2;
            const centerY = (videoHeight - size) / 2;
            
            // First, draw the mirrored video frame to match the preview
            ctx.save();
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, videoWidth, videoHeight);
            ctx.restore();
            
            // Create a temporary canvas for cropping
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = size;
            tempCanvas.height = size;
            const tempCtx = tempCanvas.getContext('2d');
            
            // Draw the cropped, centered portion
            tempCtx.drawImage(canvas,
                centerX, centerY, size, size,  // Source rectangle
                0, 0, size, size               // Destination rectangle
            );
            
            // Get the final image data
            const imageData = tempCanvas.toDataURL('image/jpeg', 0.9);
            document.getElementById('faceImageData').value = imageData;
            
            // Display the captured image
            const capturedImage = new Image();
            capturedImage.src = imageData;
            capturedImage.onload = () => {
                // Clear the canvas and draw the final image
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                canvas.width = size;
                canvas.height = size;
                ctx.drawImage(capturedImage, 0, 0, size, size);
            };
            
            // Stop camera stream
            stream.getTracks().forEach(track => track.stop());
            video.srcObject = null;
            
            // Update UI
            faceStatusMessage.textContent = 'Face captured successfully! You can now generate your QR code or recapture if needed.';
            faceStatusMessage.style.color = 'green';
            captureFaceBtn.disabled = true;
            startCameraBtn.disabled = true;
            recaptureFaceBtn.style.display = 'inline-block';
            generateQrBtn.disabled = false;
            faceVerified.value = '1';
            
            // Show captured image and preview section
            video.style.display = 'none';
            canvas.style.display = 'block';
            document.getElementById('capturePreview').style.display = 'block';
            document.getElementById('cameraContainer').classList.add('captured');
            
            // Log the successful face verification
            logVerification(true);
        }
        
        // Function to log verification attempts
        function logVerification(success) {
            const studentName = document.getElementById('studentName').value;
            const status = success ? 'Success' : 'Failed';
            const notes = success ? 'Face captured during registration' : 'Failed to capture face during registration';
            
            // Send log data to server
            fetch('./endpoint/log-verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `student_name=${encodeURIComponent(studentName)}&status=${status}&notes=${encodeURIComponent(notes)}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Verification logged:', data);
            })
            .catch(error => {
                console.error('Error logging verification:', error);
            });
        }
        
        // Add recapture functionality
        const recaptureFaceBtn = document.getElementById('recaptureFace');
        recaptureFaceBtn.addEventListener('click', async () => {
            // Reset the canvas and video elements
            canvas.style.display = 'none';
            video.style.display = 'block';
            document.getElementById('capturePreview').style.display = 'none';
            document.getElementById('cameraContainer').classList.remove('captured');
            
            // Log the recapture attempt
            logVerification(false);
            
            try {
                // Start the camera again
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    } 
                });
                video.srcObject = stream;
                
                // Update UI
                faceStatusMessage.textContent = 'Camera restarted. Position your face within the circle and click Capture.';
                faceStatusMessage.style.color = 'initial';
                captureFaceBtn.disabled = false;
                recaptureFaceBtn.style.display = 'none';
                generateQrBtn.disabled = true;
                faceVerified.value = '0';
                
                // Restart face detection
                detectFace();
                
            } catch (error) {
                console.error('Error accessing camera for recapture:', error);
                faceStatusMessage.textContent = 'Error restarting camera. Please try again.';
                faceStatusMessage.style.color = 'red';
            }
        });
        
        // Clean up when modal is closed
        $('#addStudentModal').on('hidden.bs.modal', function () {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            video.srcObject = null;
            video.style.display = 'block';
            canvas.style.display = 'none';
            document.getElementById('capturePreview').style.display = 'none';
            startCameraBtn.disabled = false;
            captureFaceBtn.disabled = true;
            recaptureFaceBtn.style.display = 'none';
            generateQrBtn.disabled = true;
            faceVerified.value = '0';
            faceStatusMessage.textContent = 'Please start the camera and position your face within the circle';
            faceStatusMessage.style.color = 'initial';
            
            // Reset form
            document.getElementById('addStudentForm').reset();
            document.querySelector('.qr-con').style.display = 'none';
            document.querySelector('.modal-close').style.display = 'none';
            document.querySelector('.qr-generator').style.display = '';
            document.getElementById('studentName').style.pointerEvents = '';
            document.getElementById('studentCourse').style.pointerEvents = '';
        });
        
        // Override the original generateQrCode function
        function generateQrCode() {
            // Check if face is verified
            if (faceVerified.value !== '1') {
                alert('Please capture your face before generating a QR code.');
                return;
            }
            
            const qrImg = document.getElementById('qrImg');
            
            // Get course and section data from the form
            const course_code = $("#studentCourse").val() || "BSIT";
            const section = $("#studentSection").val() || "A";
            const instructor_id = $("#instructor_id").val() || "1";
            const studentName = $("#studentName").val() || "";
            
            // Get session values from PHP
            const user_id = <?php echo $_SESSION['user_id'] ?? 1; ?>;
            const school_id = <?php echo $_SESSION['school_id'] ?? 1; ?>;
            
            // Generate components for unique code
            const randomString = Math.random().toString(36).substring(2, 18);
            const studentHash = btoa(studentName + course_code + section).replace(/[^a-zA-Z0-9]/g, '').substring(0, 8);
            
            // Create code in format matching backend: STU-{user_id}-{school_id}-{hash}-{random}
            const qrText = `STU-${user_id}-${school_id}-${studentHash}-${randomString}`;
            $("#generatedCode").val(qrText);
            
            if (qrText === "") {
                alert("Please enter text to generate a QR code.");
                return;
            } else {
                const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(qrText)}`;
                
                qrImg.src = apiUrl;
                document.getElementById('studentName').style.pointerEvents = 'none';
                document.getElementById('studentCourse').style.pointerEvents = 'none';
                document.querySelector('.modal-close').style.display = '';
                document.querySelector('.qr-con').style.display = '';
                document.querySelector('.qr-generator').style.display = 'none';
            }
        }
    </script>

    <!-- QR Modal Fix Script -->
    <script>
        // Custom QR code modal implementation
        $(document).ready(function() {
            // Add global QR modal to the page
            $('body').append(`
                <div id="globalQrModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 400px; width: 90%;">
                        <div style="background: white; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25); border-radius: 12px; overflow: hidden; position: relative;">
                            <!-- Green border -->
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; border: 4px solid #098744; border-radius: 12px; pointer-events: none;"></div>
                            
                            <!-- Header -->
                            <div style="background: linear-gradient(135deg, #098744 0%, #054d24 100%); color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                <h5 id="qrModalTitle" style="margin: 0; font-weight: 600;">QR Code</h5>
                                <button type="button" id="closeQrModal" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0;">&times;</button>
                            </div>
                            
                            <!-- Body -->
                            <div style="padding: 20px; text-align: center;">
                                <img id="qrModalImage" src="" alt="QR Code" style="width: 100%; max-width: 240px; display: block; margin: 0 auto;">
                            </div>
                            
                            <!-- Footer -->
                            <div style="padding: 1rem; text-align: center; background-color: #f8f9fa;">
                                <button id="closeQrButton" class="btn" style="background-color: #098744; color: white; border: none;">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            // Handle QR button clicks
            $('.qr-button').on('click', function() {
                const studentName = $(this).data('name');
                const qrCode = $(this).data('qr');
                
                // Update modal content
                $('#qrModalTitle').text(studentName + "'s QR Code");
                $('#qrModalImage').attr('src', 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + qrCode);
                
                // Show modal
                $('#globalQrModal').show();
            });
            
            // Close modal handlers
            $('#closeQrModal, #closeQrButton').on('click', function() {
                $('#globalQrModal').hide();
            });
            
            // Close when clicking outside
            $('#globalQrModal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
            
            // Handle custom course & section in add form
            $('#studentCourse').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#customStudentCourse').show().focus(); // Focus on the custom input
                } else {
                    $('#customStudentCourse').hide();
                    // Set the selected value to the hidden field for regular selections
                    $('#finalCourseSection').val($(this).val());
                }
            });
            
            // Handle custom course & section in update form
            $('#updateStudentCourse').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#updateCustomStudentCourse').show().focus();
                } else {
                    $('#updateCustomStudentCourse').hide();
                    // Set the selected value to the hidden field for regular selections
                    $('#updateFinalCourseSection').val($(this).val());
                }
            });
            
            // Clean up modals on hide to prevent stale data
            $('#updateStudentModal').on('hidden.bs.modal', function() {
                $('#updateStudentForm')[0].reset();
                $('#updateCustomStudentCourse').hide();
            });
            
            // Handle custom input field changes in add form
            $('#customStudentCourse').on('input', function() {
                $('#finalCourseSection').val($(this).val());
            });
            
            // Handle custom input field changes in update form
            $('#updateCustomStudentCourse').on('input', function() {
                $('#updateFinalCourseSection').val($(this).val());
            });
            
            // Form submission for add student
            $('#addStudentForm').on('submit', function(e) {
                // Validate student name
                const studentName = $('#studentName').val().trim();
                if (!studentName) {
                    e.preventDefault();
                    alert('Please enter a student name');
                    $('#studentName').focus();
                    return false;
                }

                // Validate course section
                const courseSelect = $('#studentCourse');
                const sectionSelect = $('#studentSection');
                const customCourseInput = $('#customCourse');
                const customSectionInput = $('#customSection');
                const combinedField = $('#courseSectionCombined');
                
                // Check if complete course-section direct entry is filled
                const completeCourseSection = $('#completeCourseSection').val().trim();
                
                if (completeCourseSection) {
                    // If direct entry is used, validate its format
                    if (!completeCourseSection.trim()) {
                        e.preventDefault();
                        alert('Course-section cannot be empty');
                        $('#completeCourseSection').focus();
                        return false;
                    }
                    
                    // Set the combined field directly
                    combinedField.val(completeCourseSection);
                } else {
                    // If direct entry is not used, validate the individual fields
                    
                    // Validate course selection
                    if (courseSelect.val() === '') {
                        e.preventDefault();
                        alert('Please select a course or enter a complete course-section value');
                        courseSelect.focus();
                        return false;
                    }
    
                    // Validate course if custom selected
                    if (courseSelect.val() === 'custom') {
                        const customCourseValue = customCourseInput.val().trim();
                        if (!customCourseValue) {
                            e.preventDefault();
                            alert('Custom course cannot be empty');
                            customCourseInput.focus();
                            return false;
                        }
                    }
                    
                    // Validate section selection
                    if (sectionSelect.val() === '') {
                        e.preventDefault();
                        alert('Please select a section or enter a complete course-section value');
                        sectionSelect.focus();
                        return false;
                    }
                    
                    // Validate section if custom selected
                    if (sectionSelect.val() === 'custom') {
                        const customSectionValue = customSectionInput.val().trim();
                        if (!customSectionValue) {
                            e.preventDefault();
                            alert('Custom section cannot be empty');
                            customSectionInput.focus();
                            return false;
                        }
                    }
                }
                
                // Update combined field one last time
                updateCombinedField();
                
                // Check if we have a valid combined course-section value
                if (!combinedField.val()) {
                    e.preventDefault();
                    alert('Please select or enter both a valid course and section');
                    return false;
                }

                // Validate QR code generation
                const generatedCode = $('#generatedCode').val().trim();
                if (!generatedCode) {
                    e.preventDefault();
                    alert('Please generate a QR code before submitting');
                    return false;
                }
                
                // Verify QR code format matches the expected pattern (STU-user_id-school_id-hash-random)
                const qrPattern = new RegExp(`^STU-\\d+-\\d+-[a-zA-Z0-9]+-[a-zA-Z0-9]+$`);
                if (!qrPattern.test(generatedCode)) {
                    e.preventDefault();
                    alert('QR code format is invalid. Please regenerate the QR code.');
                    console.error('Invalid QR format:', generatedCode);
                    return false;
                }

                // Validate face verification
                const faceVerified = $('#faceVerified').val();
                if (faceVerified !== '1') {
                    e.preventDefault();
                    alert('Please complete face verification');
                    return false;
                }

                // Validate face image data
                const faceImageData = $('#faceImageData').val().trim();
                if (!faceImageData) {
                    e.preventDefault();
                    alert('Face image data is missing. Please recapture your face.');
                    return false;
                }

                // If all validations pass, form will submit
                return true;
            });
            
            // Form submission for update student
            $('#updateStudentForm').on('submit', function(e) {
                const updateCourseSelect = $('#updateStudentCourse');
                const updateCustomCourseInput = $('#updateCustomStudentCourse');
                
                if (updateCourseSelect.val() === 'custom') {
                    // If custom option selected, validate and set the course_section value
                    const customValue = updateCustomCourseInput.val().trim();
                    if (!customValue) {
                        e.preventDefault();
                        alert('Please enter a valid custom course & section');
                        updateCustomCourseInput.focus();
                        return false;
                    }
                    updateCourseSelect.val(customValue);
                    $('#updateFinalCourseSection').val(customValue);
                } else {
                    // For predefined options, set the value to the hidden field as well
                    $('#updateFinalCourseSection').val(updateCourseSelect.val());
                }
            });
        });
    </script>

    <script>
        // Enhanced search and filter function
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('studentSearch');
            const filterSelect = document.getElementById('filterBy');
            const sortSelect = document.getElementById('sortBy');
            const applyButton = document.getElementById('applyFilters');
            const resetButton = document.getElementById('resetFilters');
            const tbody = document.querySelector('#studentTable tbody');
            const paginationContainer = document.getElementById('paginationContainer');
            
            // Get all students data from hidden input
            const allStudentsData = JSON.parse(document.getElementById('allStudentsData').value);
            
            function applyFiltersAndSort() {
                const searchTerm = searchInput.value.toLowerCase();
                const filterValue = filterSelect.value;
                const sortValue = sortSelect.value;

                // Clear current table content
                tbody.innerHTML = '';
                
                // Filter all students
                let filteredStudents = allStudentsData.filter(student => {
                    const name = student.student_name.toLowerCase();
                    const course = student.course_section;
                    let show = true;

                    // Apply search term filter
                    if (searchTerm) {
                        show = name.includes(searchTerm);
                    }

                    // Apply course filter
                    if (filterValue && show) {
                        show = course === filterValue;
                    }

                    return show;
                });

                // Sort filtered results
                if (sortValue) {
                    filteredStudents.sort((a, b) => {
                        const aValue = sortValue.includes('name') 
                            ? a.student_name
                            : a.course_section;
                        const bValue = sortValue.includes('name')
                            ? b.student_name
                            : b.course_section;
                        
                        return sortValue.includes('desc') 
                            ? bValue.localeCompare(aValue)
                            : aValue.localeCompare(bValue);
                    });
                }

                // Display filtered and sorted results
                filteredStudents.forEach(student => {
                    const row = document.createElement('tr');
                    row.className = 'student-row';
                    row.innerHTML = `
                        <th scope="row" id="studentID-${student.tbl_student_id}">${student.tbl_student_id}</th>
                        <td id="studentName-${student.tbl_student_id}">${student.student_name}</td>
                        <td id="studentCourse-${student.tbl_student_id}">${student.course_section}</td>
                        <td>
                            <div class="action-button">
                                <button class="btn btn-success btn-sm qr-button" data-id="${student.tbl_student_id}" data-name="${student.student_name}" data-qr="${student.generated_code}">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm" onclick="updateStudent(${student.tbl_student_id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteStudent(${student.tbl_student_id})">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Hide pagination if searching/filtering
                paginationContainer.style.display = (searchTerm || filterValue) ? 'none' : 'block';
            }

            function resetAllFilters() {
                // Reset all inputs to default values
                searchInput.value = '';
                filterSelect.value = '';
                sortSelect.value = '';

                // Reload the page to restore original pagination
                window.location.reload();
            }

            // Apply filters when button is clicked
            applyButton.addEventListener('click', applyFiltersAndSort);

            // Reset filters when reset button is clicked
            resetButton.addEventListener('click', resetAllFilters);

            // Also apply when Enter is pressed in search input
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    applyFiltersAndSort();
                }
            });

            // Reattach QR button event listeners after table updates
            function reattachQRListeners() {
                document.querySelectorAll('.qr-button').forEach(button => {
                    button.addEventListener('click', function() {
                        const studentName = this.dataset.name;
                        const qrCode = this.dataset.qr;
                        
                        $('#qrModalTitle').text(studentName + "'s QR Code");
                        $('#qrModalImage').attr('src', 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + qrCode);
                        $('#globalQrModal').show();
                    });
                });
            }

            // Call reattachQRListeners after table updates
            const observer = new MutationObserver(reattachQRListeners);
            observer.observe(tbody, { childList: true });
        });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        // Export to Excel function
        function exportToExcel() {
            const table = document.getElementById('studentTable');
            const ws = XLSX.utils.table_to_sheet(table);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Students');
            
            // Generate timestamp for filename
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const filename = `students_list_${timestamp}.xlsx`;
            
            XLSX.writeFile(wb, filename);
        }

        // Export to PDF function
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(16);
            doc.text('Students List', 14, 15);
            
            // Add timestamp
            doc.setFontSize(10);
            const timestamp = new Date().toLocaleString();
            doc.text(`Generated on: ${timestamp}`, 14, 22);

            // Create the PDF using autotable plugin
            doc.autoTable({
                html: '#studentTable',
                startY: 25,
                styles: { fontSize: 8 },
                columnStyles: { 0: { cellWidth: 20 } },
                headStyles: { 
                    fillColor: [9, 135, 68],
                    textColor: [255, 255, 255],
                    fontSize: 8,
                    fontStyle: 'bold'
                }
            });

            // Generate timestamp for filename
            const fileTimestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const filename = `students_list_${fileTimestamp}.pdf`;
            
            doc.save(filename);
        }

        // Print function
        function printTable() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Students List</title>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                            th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                            th { background-color: #098744; color: white; }
                            .header { text-align: center; margin-bottom: 20px; }
                            .timestamp { font-size: 12px; color: #666; margin-bottom: 10px; }
                            @media print {
                                .no-print { display: none; }
                                table { page-break-inside: auto; }
                                tr { page-break-inside: avoid; page-break-after: auto; }
                                thead { display: table-header-group; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>Students List</h2>
                            <div class="timestamp">Generated on: ${new Date().toLocaleString()}</div>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Course & Section</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${Array.from(document.querySelectorAll('#studentTable tbody tr')).map(row => `
                                    <tr>
                                        <td>${row.cells[0].textContent}</td>
                                        <td>${row.cells[1].textContent}</td>
                                        <td>${row.cells[2].textContent}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        <div class="no-print" style="margin-top: 20px; text-align: center;">
                            <button onclick="window.print();window.close()">Print</button>
                        </div>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>

    <!-- Student Delete Success Modal -->
    <div class="modal fade" id="deleteSuccessModal" tabindex="-1" role="dialog" aria-labelledby="deleteSuccessLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #098744; color: white;">
                    <h5 class="modal-title" id="deleteSuccessLabel"><i class="fas fa-trash-alt"></i> Student Deleted</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div class="trash-animation">
                        <div class="trash-lid"></div>
                        <div class="trash-container"></div>
                        <div class="trash-paper"></div>
                    </div>
                    <p class="mt-4" id="deleteSuccessMessage" style="font-size: 18px;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-dismiss="modal" style="background-color: #098744; color: white;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #dc3545; color: white;">
                    <h5 class="modal-title" id="errorModalLabel"><i class="fas fa-exclamation-triangle"></i> Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div class="error-animation">
                        <div class="error-circle">
                            <div class="error-line1"></div>
                            <div class="error-line2"></div>
                        </div>
                    </div>
                    <p class="mt-4" id="errorMessage" style="font-size: 18px;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Error animation styles */
        .error-animation {
            margin: 20px auto;
            width: 80px;
            height: 80px;
            position: relative;
        }
        .error-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid #dc3545;
            position: absolute;
            top: 0;
            left: 0;
            animation: error-circle-animation 0.5s ease-out;
        }
        .error-line1, .error-line2 {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 40px;
            height: 4px;
            background-color: #dc3545;
            transform: translate(-50%, -50%) rotate(45deg);
            animation: error-line-animation 0.5s ease-out 0.2s both;
        }
        .error-line2 {
            transform: translate(-50%, -50%) rotate(-45deg);
            animation-delay: 0.3s;
        }
        
        @keyframes error-circle-animation {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        @keyframes error-line-animation {
            0% { width: 0; opacity: 0; }
            100% { width: 40px; opacity: 1; }
        }
    </style>

    <style>
        /* Trash Can Animation Styles */
        .trash-animation {
            margin: 20px auto;
            width: 100px;
            height: 120px;
            position: relative;
        }

        .trash-container {
            width: 70px;
            height: 80px;
            background-color: #098744;
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
            animation: container-animation 0.5s ease-in-out 0.4s forwards;
        }

        .trash-lid {
            width: 80px;
            height: 12px;
            background-color: #098744;
            position: absolute;
            left: 50%;
            top: 10px;
            transform: translateX(-50%);
            border-radius: 5px;
            animation: lid-animation 0.8s ease-in-out;
        }

        .trash-lid:before {
            content: '';
            width: 15px;
            height: 10px;
            background-color: #098744;
            position: absolute;
            left: 50%;
            top: -10px;
            transform: translateX(-50%);
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }

        .trash-paper {
            width: 12px;
            height: 20px;
            background-color: white;
            position: absolute;
            left: 50%;
            top: 15%;
            transform: translateX(-50%) rotate(-15deg);
            animation: paper-animation 1s ease-in-out;
        }

        @keyframes lid-animation {
            0% { top: 10px; transform: translateX(-50%) rotate(0); }
            40% { top: -20px; transform: translateX(-50%) rotate(-5deg); }
            60% { top: -20px; transform: translateX(-50%) rotate(5deg); }
            80% { top: -20px; transform: translateX(-50%) rotate(-5deg); }
            100% { top: 10px; transform: translateX(-50%) rotate(0); }
        }

        @keyframes paper-animation {
            0% { top: 15%; transform: translateX(-50%) rotate(-15deg); opacity: 1; }
            30% { top: 15%; transform: translateX(-50%) rotate(15deg); opacity: 1; }
            60% { top: 75%; transform: translateX(-50%) rotate(-15deg); opacity: 0.7; }
            100% { top: 90%; transform: translateX(-50%) rotate(0deg); opacity: 0; }
        }

        @keyframes container-animation {
            0% { transform: translateX(-50%) scale(1); }
            30% { transform: translateX(-50%) scale(1.05); }
            60% { transform: translateX(-50%) scale(0.95); }
            100% { transform: translateX(-50%) scale(1); }
        }
    </style>

    <!-- Student Add Success Modal -->
    <div class="modal fade" id="addSuccessModal" tabindex="-1" role="dialog" aria-labelledby="addSuccessLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #098744; color: white;">
                    <h5 class="modal-title" id="addSuccessLabel"><i class="fas fa-user-plus"></i> Student Added</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center" style="padding-top: 20px; padding-bottom: 20px;">
                    <div id="successAnimation" class="success-checkmark">
                        <div class="check-icon">
                            <span class="icon-line line-tip"></span>
                            <span class="icon-line line-long"></span>
                            <div class="icon-circle"></div>
                            <div class="icon-fix"></div>
                        </div>
                    </div>
                    <div style="height: 20px; clear: both;"></div>
                    <p class="mt-5" id="addSuccessMessage" style="font-size: 18px; margin-top: 30px !important;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-dismiss="modal" style="background-color: #098744; color: white;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Success Checkmark Animation - Proven, Reliable Animation */
        .success-checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .success-checkmark .check-icon {
            width: 80px;
            height: 80px;
            position: relative;
            border-radius: 50%;
            box-sizing: content-box;
            border: 4px solid #098744;
        }
        
        .success-checkmark .check-icon::before {
            top: 3px;
            left: -2px;
            width: 30px;
            transform-origin: 100% 50%;
            border-radius: 100px 0 0 100px;
        }
        
        .success-checkmark .check-icon::after {
            top: 0;
            left: 30px;
            width: 60px;
            transform-origin: 0 50%;
            border-radius: 0 100px 100px 0;
            animation: rotate-circle 4.25s ease-in;
        }
        
        .success-checkmark .check-icon::before,
        .success-checkmark .check-icon::after {
            content: '';
            height: 100px;
            position: absolute;
            background: #FFFFFF;
            transform: rotate(-45deg);
        }
        
        .success-checkmark .check-icon .icon-line {
            height: 5px;
            background-color: #098744;
            display: block;
            border-radius: 2px;
            position: absolute;
            z-index: 10;
        }
        
        .success-checkmark .check-icon .icon-line.line-tip {
            top: 46px;
            left: 14px;
            width: 25px;
            transform: rotate(45deg);
            animation: icon-line-tip 0.75s;
        }
        
        .success-checkmark .check-icon .icon-line.line-long {
            top: 38px;
            right: 8px;
            width: 47px;
            transform: rotate(-45deg);
            animation: icon-line-long 0.75s;
        }
        
        .success-checkmark .check-icon .icon-circle {
            top: -4px;
            left: -4px;
            z-index: 10;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            position: absolute;
            box-sizing: content-box;
            border: 4px solid #098744;
            animation: icon-circle-scale 0.3s;
        }
        
        .success-checkmark .check-icon .icon-fix {
            top: 8px;
            width: 5px;
            left: 26px;
            z-index: 1;
            height: 85px;
            position: absolute;
            transform: rotate(-45deg);
            background-color: #FFFFFF;
        }
        
        @keyframes icon-line-tip {
            0% {
                width: 0;
                left: 1px;
                top: 19px;
            }
            54% {
                width: 0;
                left: 1px;
                top: 19px;
            }
            70% {
                width: 50px;
                left: -8px;
                top: 37px;
            }
            84% {
                width: 17px;
                left: 21px;
                top: 48px;
            }
            100% {
                width: 25px;
                left: 14px;
                top: 45px;
            }
        }
        
        @keyframes icon-line-long {
            0% {
                width: 0;
                right: 46px;
                top: 54px;
            }
            65% {
                width: 0;
                right: 46px;
                top: 54px;
            }
            84% {
                width: 55px;
                right: 0px;
                top: 35px;
            }
            100% {
                width: 47px;
                right: 8px;
                top: 38px;
            }
        }
        
        @keyframes icon-circle-scale {
            0% {
                transform: scale(0);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>

    <script>
        // Check for URL parameters on page load and show modals
        $(document).ready(function() {
            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            
            // Check for delete success
            if (urlParams.get('delete_success') === '1') {
                const studentName = urlParams.get('student_name');
                $('#deleteSuccessMessage').text('Student "' + studentName + '" deleted successfully!');
                $('#deleteSuccessModal').modal('show');
                
                // Remove the parameters from the URL without reloading the page
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            // Check for add success
            if (urlParams.get('add_success') === '1') {
                const studentName = urlParams.get('student_name');
                const studentId = urlParams.get('student_id');
                $('#addSuccessMessage').text('Student "' + studentName + '" added successfully!');
                $('#addSuccessModal').modal('show');
                
                // Remove the parameters from the URL without reloading the page
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            // Check for error (both delete and add errors)
            if (urlParams.get('delete_error') === '1' || urlParams.get('add_error') === '1') {
                const errorMsg = urlParams.get('message');
                $('#errorMessage').text(errorMsg);
                $('#errorModal').modal('show');
                
                // Remove the parameters from the URL without reloading the page
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>
</html>
