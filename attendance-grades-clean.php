<?php
require_once 'includes/asset_helper.php';
include('./conn/db_connect.php');
include('./includes/attendance_grade_helper.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Convert MySQL connection to PDO for helper functions
$dsn = 'mysql:host=127.0.0.1;dbname=qr_attendance_db;charset=utf8mb4';
$username = 'root';
$password = '';
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Check if refresh was requested
$refreshRequested = isset($_GET['refresh']) && $_GET['refresh'] == 1;

// Get filter parameters
$selectedCourse = isset($_GET['course']) ? $_GET['course'] : '';
$selectedSection = isset($_GET['section']) ? $_GET['section'] : '';
$selectedTerm = isset($_GET['term']) ? $_GET['term'] : (isset($_SESSION['semester']) ? $_SESSION['semester'] : '');

// If refresh is requested, we need to recalculate grades for students
if ($refreshRequested) {
    // Get students who might not have grades yet
    try {
        $studentsQuery = "
            SELECT 
                s.tbl_student_id, 
                s.course_section
            FROM 
                tbl_student s
            LEFT JOIN 
                attendance_grades g ON s.tbl_student_id = g.student_id
            WHERE 
                g.id IS NULL
        ";
        
        // Apply filters if provided
        $studentsParams = [];
        
        if (!empty($selectedCourse)) {
            $studentsQuery .= " AND s.course_section LIKE ?";
            $studentsParams[] = $selectedCourse . '-%';
        }
        
        if (!empty($selectedSection)) {
            $studentsQuery .= " AND s.course_section LIKE ?";
            $studentsParams[] = '%-' . $selectedSection;
        }
        
        $studentsStmt = $conn_qr->prepare($studentsQuery);
        
        if (!empty($studentsParams)) {
            $types = str_repeat('s', count($studentsParams));
            $studentsStmt->bind_param($types, ...$studentsParams);
        }
        
        $studentsStmt->execute();
        $studentsResult = $studentsStmt->get_result();
        
        // Get course IDs (assuming we have a subjects table with course info)
        $coursesQuery = "SELECT subject_id FROM tbl_subjects LIMIT 1";
        $coursesStmt = $conn_qr->prepare($coursesQuery);
        $coursesStmt->execute();
        $courseResult = $coursesStmt->get_result();
        $courseId = $courseResult->fetch_assoc()['subject_id'] ?? 1;
        
        // Process each student
        while ($student = $studentsResult->fetch_assoc()) {
            $studentId = $student['tbl_student_id'];
            $courseSection = $student['course_section'];
            $parts = explode('-', $courseSection);
            $section = $parts[1] ?? '';
            
            // Use helper function to calculate and update
            $term = $selectedTerm ?: '1st Semester'; // Default to 1st semester if none selected
            calculateAndUpdateAttendanceGrade($pdo, $studentId, $courseId, $term, $section);
        }
        
        // Set success message
        $successMessage = "Student attendance data has been refreshed!";
    } catch (Exception $e) {
        $error = "Error refreshing data: " . $e->getMessage();
    }
}

// Get all unique courses and sections for filter dropdowns
try {
    // Get courses/sections
    $coursesQuery = "SELECT DISTINCT course_section FROM tbl_student ORDER BY course_section";
    $coursesStmt = $conn_qr->prepare($coursesQuery);
    $coursesStmt->execute();
    $coursesResult = $coursesStmt->get_result();
    $courses = [];
    
    while ($row = $coursesResult->fetch_assoc()) {
        $parts = explode('-', $row['course_section']);
        $course = $parts[0] ?? '';
        $section = $parts[1] ?? '';
        
        if (!in_array($course, $courses) && !empty($course)) {
            $courses[] = $course;
        }
    }
} catch (Exception $e) {
    // Swallow non-fatal errors while building filter lists
}
    
    // Get distinct sections for the selected course
    $sectionsQuery = "SELECT DISTINCT SUBSTRING_INDEX(course_section, '-', -1) AS section 
                     FROM tbl_student 
                     WHERE course_section LIKE ?
                     ORDER BY section";
    $sectionsStmt = $conn_qr->prepare($sectionsQuery);
    $courseFilter = $selectedCourse . '-%';
    $sectionsStmt->bind_param("s", $courseFilter);
    $sectionsStmt->execute();
    $sectionsResult = $sectionsStmt->get_result();
    $sections = [];
    
    while ($row = $sectionsResult->fetch_assoc()) {
        $sections[] = $row['section'];
    }
    
    // Get terms
    $termsQuery = "SELECT DISTINCT term FROM attendance_sessions ORDER BY term";
    $termStmt = $conn_qr->prepare($termsQuery);
    $termStmt->execute();
    $termsResult = $termStmt->get_result();
    $terms = [];
    
    while ($row = $termsResult->fetch_assoc()) {
        $terms[] = $row['term'];
    }
    
    // If no terms found, use defaults
    if (empty($terms)) {
        $terms = ['1st Semester', '2nd Semester', 'Summer'];
    }
    
    // Make sure 1st Semester is always included as an option
    if (!in_array('1st Semester', $terms)) {
        $terms[] = '1st Semester';
    }
    
    // Sort terms in logical order (1st Semester, 2nd Semester, Summer)
    usort($terms, function($a, $b) {
        $order = ['1st Semester' => 1, '2nd Semester' => 2, 'Summer' => 3];
        $aOrder = isset($order[$a]) ? $order[$a] : 999;
        $bOrder = isset($order[$b]) ? $order[$b] : 999;
        return $aOrder - $bOrder;
    });
    
    // Get student attendance grades data
    try {
    $gradesQuery = "
        SELECT 
            s.tbl_student_id, 
            s.student_name, 
            s.course_section,
            subj.subject_name,
            IFNULL(g.attendance_rate, 0) AS attendance_rate,
            IFNULL(g.attendance_grade, 5.00) AS raw_attendance_grade,
            s.created_at
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
        $gradesQuery .= " AND s.course_section LIKE ?";
        $gradesParams[] = $selectedCourse . '-%';
    }
    
    if (!empty($selectedSection)) {
        $gradesQuery .= " AND s.course_section LIKE ?";
        $gradesParams[] = '%-' . $selectedSection;
    }
    
    if (!empty($selectedTerm)) {
        $gradesQuery .= " AND (g.term = ? OR g.term IS NULL)";
        $gradesParams[] = $selectedTerm;
    }
    
    // Force refresh from tbl_student even if no grades exist yet
    $gradesQuery .= " ORDER BY s.course_section, s.student_name";
    
    $gradesStmt = $conn_qr->prepare($gradesQuery);
    
    if (!empty($gradesParams)) {
        $gradesTypes = str_repeat('s', count($gradesParams));
        $gradesStmt->bind_param($gradesTypes, ...$gradesParams);
    }
    
    $gradesStmt->execute();
    $gradesResult = $gradesStmt->get_result();
    
    $attendanceGrades = [];
    while ($row = $gradesResult->fetch_assoc()) {
        // The raw attendance grade is already in the row as 'raw_attendance_grade'
        // We'll calculate what the 80% portion means for the final grade
        // For simplicity, we'll assume the best possible grade for the remaining 20%
        // which would be 1.0 (the best possible grade)
        
        // Calculate 80% of the attendance grade
        $weightedAttendanceGrade = $row['raw_attendance_grade'] * 0.8;
        
        // For the remaining 20%, we'll use 1.0 (best grade) as default
        // This could be replaced with actual data if available
        $otherGradeComponent = 1.0 * 0.2;
        
        // Calculate final grade (weighted sum)
        $finalGrade = $weightedAttendanceGrade + $otherGradeComponent;
        
        // Round to 2 decimal places
        $finalGrade = round($finalGrade, 2);
        
        // Add the final grade to the row data
        $row['attendance_grade'] = $finalGrade;
        
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
    <title>Attendance Grades - QR Code Attendance System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/dataTables.bootstrap4.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/buttons.bootstrap4.min.css'); ?>">
    <link rel="stylesheet" href="./styles/pagination.css">
    
    <style>
        body {
            background-color: #808080; /* Match the gray background of analytics.php */
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Prevent horizontal scrolling */
        }
        
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

        /* Grades container styles - Outer container */
        .grades-outer-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            margin: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            width: calc(100% - 40px);
            height: calc(100vh - 60px);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        /* Grades content styles - Inner container */
        .grades-container {
            background-color: white;
            border-radius: 20px;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        .grades-content {
            height: 100%;
            width: 100%;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #098744 transparent;
            max-height: calc(100vh - 110px);
        }
        
        /* Scrollbar styling for webkit browsers */
        .grades-content::-webkit-scrollbar {
            width: 8px;
        }

        .grades-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .grades-content::-webkit-scrollbar-thumb {
            background-color: #098744;
            border-radius: 4px;
        }

        /* Title styles */
        .title {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 15px;
            margin: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
            border-radius: 20px 20px 0 0;
            height: 60px; /* Fixed height for the title */
        }

        .title h4 {
            margin: 0;
            color: #098744;
        }
        
        /* Content wrapper */
        .content-wrapper {
            padding: 20px;
            background-color: #f9f9f9;
            min-height: calc(100% - 60px);
        }

        /* Grades scale info */
        .grades-scale {
            background-color: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .grade-range {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }

        /* Grade colors */
        .grade-1-00 { color: #28a745; font-weight: bold; }  /* Excellent - Green */
        .grade-1-25 { color: #28a745; }  /* Excellent - Green */
        .grade-1-50 { color: #20c997; font-weight: bold; }  /* Very Good - Teal */
        .grade-1-75 { color: #20c997; }  /* Very Good - Teal */
        .grade-2-00 { color: #17a2b8; font-weight: bold; }  /* Good - Blue */
        .grade-2-25 { color: #17a2b8; }  /* Good - Blue */
        .grade-2-50 { color: #007bff; font-weight: bold; }  /* Satisfactory - Primary Blue */
        .grade-2-75 { color: #007bff; }  /* Satisfactory - Primary Blue */
        .grade-3-00 { color: #6c757d; font-weight: bold; }  /* Passing - Gray */
        .grade-4-00 { color: #dc3545; }  /* Poor - Red */
        .grade-5-00 { color: #dc3545; font-weight: bold; }  /* Failed - Bold Red */

        /* Filter container */
        .filter-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Table styles */
        .grades-table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .grades-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .grades-table th {
            background-color: #098744;
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: 600;
        }

        .grades-table th:first-child {
            border-top-left-radius: 10px;
        }

        .grades-table th:last-child {
            border-top-right-radius: 10px;
        }

        .grades-table td {
            padding: 12px;
            text-align: center;
        }

        .grades-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .grades-table tr:hover {
            background-color: #e9f7ef;
        }

        /* Export button styling */
        .export-buttons {
            margin-top: 20px;
            text-align: center;
        }
        
        .export-buttons .btn {
            margin: 0 5px;
            background-color: #098744;
            border-color: #098744;
        }
        
        .export-buttons .btn:hover {
            background-color: #076832;
            border-color: #076832;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .main {
                margin-left: 0 !important;
                width: 100% !important;
            }

            .grades-outer-container {
                margin: 10px;
                width: calc(100% - 20px);
                padding: 15px;
            }
        }

        /* Settings Navigation Styles (from settings.php) */
        .settings-nav {
            display: flex;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .settings-nav a {
            padding: 10px 15px;
            margin-right: 10px;
            border-radius: 4px;
            text-decoration: none;
            color: #343a40;
            background-color: #e9ecef;
            transition: all 0.3s ease;
        }

        .settings-nav a:hover {
            background-color: #dde1e4;
        }

        .settings-nav a.active {
            background-color: #098744;
            color: white;
        }

        .settings-nav a i {
            margin-right: 5px;
        }

        .tab-content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        /* Responsive layout for settings nav */
        @media (max-width: 768px) {
            .settings-nav {
                flex-direction: column;
            }
            
            .settings-nav a {
                margin-right: 0;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Custom Alert Box -->
    <div id="customAlert" class="custom-alert"></div>

    <?php include('./components/sidebar-nav.php'); ?>

    <div class="main" id="main">
        <div class="grades-outer-container">
            <div class="grades-container">
                <div class="grades-content">
                    <div class="title">
                        <h4><i class="fas fa-graduation-cap"></i> Attendance Grades</h4>
                    </div>

                    <div class="content-wrapper">
                        <!-- Simple Navigation (like settings.php) -->
                        <div class="settings-nav">
                            <a href="#scale" class="active" data-toggle="tab" role="tab" aria-controls="scale" aria-selected="true">
                                <i class="fas fa-chart-line"></i> Grading Scale
                            </a>
                            <a href="#grades" data-toggle="tab" role="tab" aria-controls="grades" aria-selected="false">
                                <i class="fas fa-list-alt"></i> Attendance Grades
                            </a>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content">
                            <!-- Grading Scale Tab -->
                            <div class="tab-pane fade show active" id="scale" role="tabpanel">
                                <!-- Grades Scale Information -->
                                <div class="grades-scale">
                                    <h5 class="text-center mb-3">College Grading System</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="bg-light">
                                                <tr class="text-center">
                                                    <th>Grades</th>
                                                    <th>Percentage</th>
                                                    <th>Equivalent</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="text-center grade-1-00">1.0</td>
                                                    <td class="text-center">97 - 100</td>
                                                    <td>Excellent</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center grade-1-25">1.25</td>
                                                    <td class="text-center">94 - 96</td>
                                                    <td>Excellent</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center grade-1-50">1.5</td>
                                                    <td class="text-center">91 - 93</td>
                                                    <td>Very Good</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center grade-1-75">1.75</td>
                                                    <td class="text-center">88 - 90</td>
                                                    <td>Very Good</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center grade-2-00">2.0</td>
                                                    <td class="text-center">85 - 87</td>
                                                    <td>Good</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center grade-2-25">2.25</td>
                                                    <td class="text-center">82 - 84</td>
                                                    <td>Good</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center grade-2-50">2.5</td>
                                                    <td class="text-center">79 - 81</td>
                                                    <td>Satisfactory</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center grade-2-75">2.75</td>
                                                    <td class="text-center">76 - 78</td>
                                                    <td>Satisfactory</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center grade-3-00">3.0</td>
                                                    <td class="text-center">75</td>
                                                    <td>Passing</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center grade-5-00">5.0</td>
                                                    <td class="text-center">65 - 74</td>
                                                    <td>Failure</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- How Attendance Grades are Calculated -->
                                <div class="grades-scale mt-4">
                                    <h5 class="text-center mb-3">How Attendance Grades are Calculated</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="alert alert-primary mb-3">
                                                <i class="fas fa-info-circle"></i> <strong>Important:</strong> Attendance accounts for 80% of the final course grade. The remaining 20% is determined by other assessment criteria.
                                            </div>
                                            
                                            <p>Attendance grades are calculated based on instructor-initiated sessions:</p>
                                            <ol>
                                                <li>Each time an instructor clicks "Set Time" in the attendance system, a new class session is created</li>
                                                <li>The system counts the total number of sessions created by instructors for each course/section</li>
                                                <li>For each student, the system counts how many of these sessions they attended (when their QR code was scanned)</li>
                                                <li>Attendance rate is calculated as: (Sessions Attended ÷ Total Sessions Created) × 100%</li>
                                                <li>The resulting percentage is then mapped to the corresponding grade according to the scale above</li>
                                                <li>This attendance grade contributes 80% to the final course grade</li>
                                            </ol>
                                            
                                            <div class="alert alert-warning mb-3">
                                                <i class="fas fa-exclamation-triangle"></i> <strong>Important note about absences:</strong> If a student is not present during a session when other students scan their QR codes, they are considered absent for that session. Each absence reduces the attendance rate and directly impacts the final grade.
                                            </div>
                                            
                                            <h6 class="mt-4">Example Calculation:</h6>
                                            <div class="example-calculation p-3 bg-light rounded">
                                                <p><strong>Scenario:</strong> An instructor created 45 attendance sessions during the semester, and a student attended 40 of these sessions.</p>
                                                <ul>
                                                    <li>Total sessions: 45 (instructor-initiated sessions)</li>
                                                    <li>Student's attended sessions: 40 (days when QR code was scanned)</li>
                                                    <li>Student's absences: 5 (sessions with no attendance recorded)</li>
                                                    <li>Attendance Rate = (40 ÷ 45) × 100% = 88.89%</li>
                                                    <li>According to the grading scale, 88.89% corresponds to a grade of 1.75 (Very Good)</li>
                                                    <li>This attendance grade (1.75) accounts for 80% of the final course grade: 1.75 × 0.8 = 1.4</li>
                                                    <li>Assuming a perfect score (1.0) for the remaining 20%: 1.0 × 0.2 = 0.2</li>
                                                    <li>Final grade: 1.4 + 0.2 = 1.6</li>
                                                </ul>
                                            </div>
                                            
                                            <div class="alert alert-info mt-3">
                                                <i class="fas fa-info-circle"></i> <strong>How sessions are tracked:</strong> There is no fixed class schedule in the system. Attendance is only tracked when an instructor initiates a session by clicking "Set Time" and students scan their QR codes during that session. Any session created by an instructor counts toward the total, and students who don't scan their QR codes during that session are counted as absent.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Attendance Grades Tab -->
                            <div class="tab-pane fade" id="grades" role="tabpanel">
                                <!-- Filter section -->
                                <div class="filter-container">
                                    <h5>Filter Options</h5>
                                    <form action="" method="GET" class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="course">Course:</label>
                                            <select name="course" id="course" class="form-control">
                                                <option value="">All Courses</option>
                                                <?php foreach ($courses as $course): ?>
                                                    <option value="<?php echo htmlspecialchars($course); ?>" <?php echo $selectedCourse === $course ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($course); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="section">Section:</label>
                                            <select name="section" id="section" class="form-control">
                                                <option value="">All Sections</option>
                                                <?php foreach ($sections as $section): ?>
                                                    <option value="<?php echo htmlspecialchars($section); ?>" <?php echo $selectedSection === $section ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($section); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="term">Term:</label>
                                            <select name="term" id="term" class="form-control">
                                                <option value="">All Terms</option>
                                                <?php foreach ($terms as $term): ?>
                                                    <option value="<?php echo htmlspecialchars($term); ?>" <?php echo $selectedTerm === $term ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($term); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary mr-2">
                                                <i class="fas fa-filter"></i> Apply Filters
                                            </button>
                                            <button type="submit" name="refresh" value="1" class="btn btn-success">
                                                <i class="fas fa-sync-alt"></i> Refresh Data
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <?php if(isset($successMessage)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <?php if(isset($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Grades Table -->
                                <div class="grades-table-container">
                                    <div class="mb-3">
                                        <div class="alert alert-info" role="alert">
                                            <i class="fas fa-info-circle"></i> If you don't see recently added students, please use the <strong>Refresh Data</strong> button above to update the list.
                                        </div>
                                    </div>
                                    
                                    <h5 class="mb-3"><i class="fas fa-list"></i> Student Attendance Grades</h5>
                                    
                                    <div class="table-responsive">
                                        <table class="grades-table" id="gradesTable">
                                            <thead>
                                                <tr>
                                                    <th>Student ID</th>
                                                    <th>Student Name</th>
                                                    <th>Course-Section</th>
                                                    <th>Subject</th>
                                                    <th>Attendance Rate (%)</th>
                                                    <th>Attendance Grade</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($attendanceGrades)): ?>
                                                    <?php foreach ($attendanceGrades as $grade): ?>
                                                        <?php 
                                                            // Determine CSS class for grade coloring
                                                            $gradeValue = floatval($grade['raw_attendance_grade']);
                                                            $gradeClass = 'grade-5-00'; // Default to failing
                                                            
                                                            if ($gradeValue <= 1.00) $gradeClass = 'grade-1-00';
                                                            else if ($gradeValue <= 1.25) $gradeClass = 'grade-1-25';
                                                            else if ($gradeValue <= 1.50) $gradeClass = 'grade-1-50';
                                                            else if ($gradeValue <= 1.75) $gradeClass = 'grade-1-75';
                                                            else if ($gradeValue <= 2.00) $gradeClass = 'grade-2-00';
                                                            else if ($gradeValue <= 2.25) $gradeClass = 'grade-2-25';
                                                            else if ($gradeValue <= 2.50) $gradeClass = 'grade-2-50';
                                                            else if ($gradeValue <= 2.75) $gradeClass = 'grade-2-75';
                                                            else if ($gradeValue <= 3.00) $gradeClass = 'grade-3-00';
                                                            else if ($gradeValue < 5.00) $gradeClass = 'grade-4-00';
                                                        ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($grade['tbl_student_id']) ?></td>
                                                            <td><?= htmlspecialchars($grade['student_name']) ?></td>
                                                            <td><?= htmlspecialchars($grade['course_section']) ?></td>
                                                            <td><?= htmlspecialchars($grade['subject_name'] ?? 'N/A') ?></td>
                                                            <td><?= number_format($grade['attendance_rate'], 2) ?>%</td>
                                                            <td class="<?= $gradeClass ?>"><?= number_format($grade['raw_attendance_grade'], 2) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">No attendance grade data available</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Export Buttons -->
                                <div class="export-buttons">
                                    <button type="button" class="btn btn-success" onclick="exportToCsv()">
                                        <i class="fas fa-file-csv"></i> Export CSV
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="printTable()">
                                        <i class="fas fa-print"></i> Print Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="<?php echo asset_url('js/popper.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.min.js'); ?>"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
    <script src="./functions/pagination.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable with standardized pagination
            const table = initializeStandardDataTable('#gradesTable', {
                pageLength: 10,
                dom: '<"top"lf>rt<"bottom"ip><"clear">',
                language: {
                    search: "Search students:"
                },
                buttons: []
            });
            
            // Correctly initialize Bootstrap tabs
            $('a[data-toggle="tab"]').on('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs and tab panes
                $('.settings-nav a').removeClass('active');
                $('.tab-pane').removeClass('show active');
                
                // Add active class to clicked tab
                $(this).addClass('active');
                
                // Show the corresponding tab pane
                const tabId = $(this).attr('href');
                $(tabId).addClass('show active');
                
                // Update URL hash
                history.pushState(null, null, tabId);
            });
            
            // Check if there's a tab specified in the URL hash
            if (window.location.hash) {
                const tabId = window.location.hash;
                $('.settings-nav a[href="' + tabId + '"]').addClass('active');
                $(tabId).addClass('show active');
                $('.tab-pane').not(tabId).removeClass('show active');
            } else {
                // Set grading scale as the default tab
                $('.settings-nav a[href="#scale"]').addClass('active');
                $('#scale').addClass('show active');
                $('#grades').removeClass('show active');
            }
        });
        
        // Filter Course-Section relationship
        document.getElementById('course').addEventListener('change', function() {
            const courseValue = this.value;
            const sectionSelect = document.getElementById('section');
            
            // Reset section when course changes
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            
            if (courseValue) {
                // Fetch sections for the selected course via AJAX
                fetch(`api/get-sections.php?course=${courseValue}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.sections) {
                            data.sections.forEach(section => {
                                const option = document.createElement('option');
<?php
require_once 'includes/asset_helper.php';
include('./conn/db_connect.php');
include('./includes/attendance_grade_helper.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Convert MySQL connection to PDO for helper functions
$dsn = 'mysql:host=127.0.0.1;dbname=qr_attendance_db;charset=utf8mb4';
$username = 'root';
$password = '';
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Check if refresh was requested
$refreshRequested = isset($_GET['refresh']) && $_GET['refresh'] == 1;

// Get filter parameters
$selectedCourse = isset($_GET['course']) ? $_GET['course'] : '';
$selectedSection = isset($_GET['section']) ? $_GET['section'] : '';
$selectedTerm = isset($_GET['term']) ? $_GET['term'] : (isset($_SESSION['semester']) ? $_SESSION['semester'] : '');

// If refresh is requested, we need to recalculate grades for students
if ($refreshRequested) {
    // Get students who might not have grades yet
    try {
        $studentsQuery = "
            SELECT 
                s.tbl_student_id, 
                s.course_section
            FROM 
                tbl_student s
            LEFT JOIN 
                attendance_grades g ON s.tbl_student_id = g.student_id
            WHERE 
                g.id IS NULL
        ";
        
        // Apply filters if provided
        $studentsParams = [];
        
        if (!empty($selectedCourse)) {
            $studentsQuery .= " AND s.course_section LIKE ?";
            $studentsParams[] = $selectedCourse . '-%';
        }
        
        if (!empty($selectedSection)) {
            $studentsQuery .= " AND s.course_section LIKE ?";
            $studentsParams[] = '%-' . $selectedSection;
        }
        
        $studentsStmt = $conn_qr->prepare($studentsQuery);
        
        if (!empty($studentsParams)) {
            $types = str_repeat('s', count($studentsParams));
            $studentsStmt->bind_param($types, ...$studentsParams);
        }
        
        $studentsStmt->execute();
        $studentsResult = $studentsStmt->get_result();
        
        // Get course IDs (assuming we have a subjects table with course info)
        $coursesQuery = "SELECT subject_id FROM tbl_subjects LIMIT 1";
        $coursesStmt = $conn_qr->prepare($coursesQuery);
        $coursesStmt->execute();
        $courseResult = $coursesStmt->get_result();
        $courseId = $courseResult->fetch_assoc()['subject_id'] ?? 1;
        
        // Process each student
        while ($student = $studentsResult->fetch_assoc()) {
            $studentId = $student['tbl_student_id'];
            $courseSection = $student['course_section'];
            $parts = explode('-', $courseSection);
            $section = $parts[1] ?? '';
            
            // Use helper function to calculate and update
            $term = $selectedTerm ?: '1st Semester'; // Default to 1st semester if none selected
            calculateAndUpdateAttendanceGrade($pdo, $studentId, $courseId, $term, $section);
        }
        
        // Set success message
        $successMessage = "Student attendance data has been refreshed!";
    } catch (Exception $e) {
        $error = "Error refreshing data: " . $e->getMessage();
    }
}

// Get all unique courses and sections for filter dropdowns
try {
    // Get courses/sections
    $coursesQuery = "SELECT DISTINCT course_section FROM tbl_student ORDER BY course_section";
    $coursesStmt = $conn_qr->prepare($coursesQuery);
    $coursesStmt->execute();
    $coursesResult = $coursesStmt->get_result();
    $courses = [];
    
    while ($row = $coursesResult->fetch_assoc()) {
        $parts = explode('-', $row['course_section']);
        $course = $parts[0] ?? '';
        $section = $parts[1] ?? '';
        
        if (!in_array($course, $courses) && !empty($course)) {
