<?php
require_once 'includes/asset_helper.php';
// Include the PDO database connection
include('./conn/db_connect_pdo.php');
include('./includes/attendance_grade_helper.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// We already have PDO connections from db_connect_pdo.php
// $conn_qr_pdo and $conn_login_pdo are available

// Get filter parameters
$selectedCourse = isset($_GET['course']) ? $_GET['course'] : '';
$selectedSection = isset($_GET['section']) ? $_GET['section'] : '';
$selectedTerm = isset($_GET['term']) ? $_GET['term'] : (isset($_SESSION['semester']) ? $_SESSION['semester'] : '');

// Get all unique courses and sections for filter dropdowns
try {
    // Get courses
    $coursesQuery = "SELECT DISTINCT course_section FROM tbl_student ORDER BY course_section";
    $coursesStmt = $conn_qr_pdo->query($coursesQuery);
    $courses = [];
    
    while ($row = $coursesStmt->fetch(PDO::FETCH_ASSOC)) {
        $parts = explode('-', $row['course_section']);
        $course = $parts[0] ?? '';
        $section = $parts[1] ?? '';
        
        if (!in_array($course, $courses) && !empty($course)) {
            $courses[] = $course;
        }
    }
    
    // Get distinct sections for the selected course
    $sectionsQuery = "SELECT DISTINCT SUBSTRING_INDEX(course_section, '-', -1) AS section 
                     FROM tbl_student 
                     WHERE course_section LIKE :course
                     ORDER BY section";
    $sectionsStmt = $conn_qr_pdo->prepare($sectionsQuery);
    $courseFilter = $selectedCourse . '-%';
    $sectionsStmt->bindParam(':course', $courseFilter);
    $sectionsStmt->execute();
    $sections = [];
    
    while ($row = $sectionsStmt->fetch(PDO::FETCH_ASSOC)) {
        $sections[] = $row['section'];
    }
    
    // Get terms
    $termsQuery = "SELECT DISTINCT term FROM attendance_sessions ORDER BY term";
    $termsStmt = $conn_qr_pdo->query($termsQuery);
    $terms = [];
    
    while ($row = $termsStmt->fetch(PDO::FETCH_ASSOC)) {
        $terms[] = $row['term'];
    }
    
    // If no terms found, use defaults
    if (empty($terms)) {
        $terms = ['1st Semester', '2nd Semester', 'Summer'];
    }
    
    // Get student attendance grades data
    $gradesQuery = "
        SELECT 
            s.tbl_student_id, 
            s.student_name, 
            s.course_section,
            subj.subject_name,
            IFNULL(g.attendance_rate, 0) AS attendance_rate,
            IFNULL(g.attendance_grade, 5.00) AS attendance_grade
        FROM 
            tbl_student s
        LEFT JOIN 
            attendance_grades g ON s.tbl_student_id = g.student_id
        LEFT JOIN
            tbl_subjects subj ON g.course_id = subj.subject_id
        WHERE 1=1
    ";
    
    $gradesParams = [];
    
    if (!empty($selectedCourse)) {
        $gradesQuery .= " AND s.course_section LIKE :course";
        $gradesParams[':course'] = $selectedCourse . '-%';
    }
    
    if (!empty($selectedSection)) {
        $gradesQuery .= " AND s.course_section LIKE :section";
        $gradesParams[':section'] = '%-' . $selectedSection;
    }
    
    if (!empty($selectedTerm)) {
        $gradesQuery .= " AND g.term = :term";
        $gradesParams[':term'] = $selectedTerm;
    }
    
    $gradesQuery .= " ORDER BY s.course_section, s.student_name";
    
    $gradesStmt = $conn_qr_pdo->prepare($gradesQuery);
    
    if (!empty($gradesParams)) {
        foreach ($gradesParams as $key => $value) {
            $gradesStmt->bindValue($key, $value);
        }
    }
    
    $gradesStmt->execute();
    
    $attendanceGrades = [];
    while ($row = $gradesStmt->fetch(PDO::FETCH_ASSOC)) {
        $attendanceGrades[] = $row;
    }
    
} catch (Exception $e) {
    $error = "Error fetching data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Attendance System - Attendance Grades (PDO Version)</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        /* Grades container styles */
        .grades-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            margin: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            width: calc(100% - 40px);
            transition: all 0.3s ease;
        }

        /* Grades scale info */
        .grades-scale {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .grade-range {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }

        /* Grade colors */
        .grade-1-00 { color: #28a745; }  /* Excellent - Green */
        .grade-1-25 { color: #20c997; }  /* Very Good - Teal */
        .grade-1-50 { color: #17a2b8; }  /* Good - Blue */
        .grade-1-75 { color: #007bff; }  /* Satisfactory - Primary Blue */
        .grade-2-00 { color: #6f42c1; }  /* Above Average - Purple */
        .grade-2-50 { color: #fd7e14; }  /* Average - Orange */
        .grade-2-75 { color: #ffc107; }  /* Below Average - Yellow */
        .grade-3-00 { color: #e83e8c; }  /* Fair - Pink */
        .grade-4-00 { color: #dc3545; }  /* Poor - Red */
        .grade-5-00 { color: #dc3545; font-weight: bold; }  /* Failed - Bold Red */

        /* Title styles */
        .title {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 15px;
            margin-bottom: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
            border-radius: 20px 20px 0 0;
        }

        .title h4 {
            margin: 0;
            color: #098744;
        }

        /* Responsive styles */
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
            
            .grades-container {
                margin: 10px;
                min-height: calc(100vh - 100px);
            }
        }
    </style>
</head>
<body>
    <!-- Custom Alert Box -->
    <div id="customAlert" class="custom-alert"></div>

    <?php include('./includes/sidebar.php'); ?>
    
    <!-- Main Content -->
    <div class="main">
        <div class="grades-container">
            <!-- Title -->
            <div class="title">
                <h4><i class="fas fa-graduation-cap me-2"></i> Attendance Grades</h4>
            </div>
            
            <!-- Grades scale info -->
            <div class="grades-scale mt-4">
                <h5 class="mb-3">Attendance Grade Scale</h5>
                <div class="grade-ranges">
                    <span class="grade-range grade-1-00">100% → 1.00</span>
                    <span class="grade-range grade-1-25">95-99% → 1.25</span>
                    <span class="grade-range grade-1-50">90-94% → 1.50</span>
                    <span class="grade-range grade-1-75">85-89% → 1.75</span>
                    <span class="grade-range grade-2-00">80-84% → 2.00</span>
                    <span class="grade-range grade-2-50">75-79% → 2.50</span>
                    <span class="grade-range grade-2-75">70-74% → 2.75</span>
                    <span class="grade-range grade-3-00">65-69% → 3.00</span>
                    <span class="grade-range grade-4-00">60-64% → 4.00</span>
                    <span class="grade-range grade-5-00">&lt; 60% → 5.00</span>
                </div>
            </div>
            
            <!-- Filter Controls -->
            <div class="row justify-content-center mb-4">
                <div class="col-md-3 mb-2">
                    <label>Course:</label>
                    <select class="form-control" id="courseFilter">
                        <option value="">All Courses</option>
                        <?php 
                        foreach ($courses as $course) {
                            $selected = ($course == $selectedCourse) ? 'selected' : '';
                            echo "<option value=\"$course\" $selected>$course</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Section:</label>
                    <select class="form-control" id="sectionFilter">
                        <option value="">All Sections</option>
                        <?php 
                        foreach ($sections as $section) {
                            $selected = ($section == $selectedSection) ? 'selected' : '';
                            echo "<option value=\"$section\" $selected>$section</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Term:</label>
                    <select class="form-control" id="termFilter">
                        <option value="">All Terms</option>
                        <?php 
                        foreach ($terms as $term) {
                            $selected = ($term == $selectedTerm) ? 'selected' : '';
                            echo "<option value=\"$term\" $selected>$term</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button class="btn btn-success" id="filterBtn">Filter</button>
                </div>
            </div>
            
            <!-- Grades Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-success text-white">
                        <tr>
                            <th class="text-center">Student ID</th>
                            <th class="text-center">Student Name</th>
                            <th class="text-center">Course-Section</th>
                            <th class="text-center">Subject</th>
                            <th class="text-center">Attendance Rate (%)</th>
                            <th class="text-center">Attendance Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendanceGrades)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No attendance grade data available</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendanceGrades as $grade): ?>
                                <tr>
                                    <td class="text-center"><?= $grade['tbl_student_id'] ?></td>
                                    <td><?= $grade['student_name'] ?></td>
                                    <td class="text-center"><?= $grade['course_section'] ?></td>
                                    <td class="text-center"><?= $grade['subject_name'] ?? 'N/A' ?></td>
                                    <td class="text-center"><?= number_format($grade['attendance_rate'], 2) ?>%</td>
                                    <td class="text-center grade-<?= str_replace('.', '-', $grade['attendance_grade']) ?>"><?= number_format($grade['attendance_grade'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Export Controls -->
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <button class="btn btn-success me-2" id="exportCsv">
                        <i class="fas fa-file-csv me-2"></i> Export CSV
                    </button>
                    <button class="btn btn-success me-2" id="exportExcel">
                        <i class="fas fa-file-excel me-2"></i> Export Excel
                    </button>
                    <button class="btn btn-success" id="printReport">
                        <i class="fas fa-print me-2"></i> Print Report
                    </button>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger mt-4">
                    <?= $error ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="<?php echo asset_url('js/jquery-3.6.0.min.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Filter button click handler
            $('#filterBtn').on('click', function() {
                const course = $('#courseFilter').val();
                const section = $('#sectionFilter').val();
                const term = $('#termFilter').val();
                
                window.location.href = `attendance-grades-pdo.php?course=${course}&section=${section}&term=${term}`;
            });
            
            // Section filter should update based on selected course
            $('#courseFilter').on('change', function() {
                const course = $(this).val();
                
                if (course) {
                    // AJAX call to get sections for the selected course
                    $.ajax({
                        url: 'get_sections.php',
                        type: 'GET',
                        data: { course: course },
                        dataType: 'json',
                        success: function(data) {
                            let options = '<option value="">All Sections</option>';
                            
                            data.forEach(function(section) {
                                options += `<option value="${section}">${section}</option>`;
                            });
                            
                            $('#sectionFilter').html(options);
                        }
                    });
                }
            });
            
            // Export buttons
            $('#exportCsv').on('click', function() {
                const course = $('#courseFilter').val();
                const section = $('#sectionFilter').val();
                const term = $('#termFilter').val();
                
                window.location.href = `export.php?type=csv&course=${course}&section=${section}&term=${term}`;
            });
            
            $('#exportExcel').on('click', function() {
                const course = $('#courseFilter').val();
                const section = $('#sectionFilter').val();
                const term = $('#termFilter').val();
                
                window.location.href = `export.php?type=excel&course=${course}&section=${section}&term=${term}`;
            });
            
            $('#printReport').on('click', function() {
                window.print();
            });
        });
    </script>

    <!-- PDO debugging info -->
    <div style="display:none">
        <?php
        // Output connection status for debugging
        echo "PDO Connection Status:<br>";
        echo "login_register: " . ($conn_login_pdo ? "Connected" : "Failed") . "<br>";
        echo "qr_attendance_db: " . ($conn_qr_pdo ? "Connected" : "Failed") . "<br>";
        
        // Output PDO driver information
        echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers());
        ?>
    </div>
</body>
</html> 