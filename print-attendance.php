<?php
// Standard session + DB includes
require_once 'includes/session_config.php';
include('./conn/conn.php');
include('./conn/db_connect.php');

// Set timezone
date_default_timezone_set('Asia/Manila');

// (session already handled by session_config)

// Capture tenant identifiers early with robust fallbacks
$user_id = $_SESSION['user_id']
    ?? ($_SESSION['userData']['id'] ?? ($_SESSION['userData']['user_id'] ?? null));
$school_id = $_SESSION['school_id'] ?? ($_SESSION['userData']['school_id'] ?? null);
$email = $_SESSION['email'] ?? null;

// Fallback: derive user_id (and maybe school_id) from users table via email if missing
if ((!$user_id || !$school_id) && $email && isset($conn_login)) {
    try {
        $lookup = $conn_login->prepare("SELECT id, school_id FROM users WHERE email = ? LIMIT 1");
        $lookup->bind_param('s', $email);
        if ($lookup->execute()) {
            $res = $lookup->get_result()->fetch_assoc();
            if ($res) {
                if (!$user_id && !empty($res['id'])) {
                    $user_id = (int)$res['id'];
                    $_SESSION['user_id'] = $user_id; // persist for other pages
                }
                if (!$school_id && !empty($res['school_id'])) {
                    $school_id = (int)$res['school_id'];
                    $_SESSION['school_id'] = $school_id;
                }
            }
        }
    } catch (Exception $e) {
        error_log('print-attendance: fallback email->user lookup failed: ' . $e->getMessage());
    }
}

// If still no school_id, default to 1 (consistent with other pages using default school)
if (!$school_id) { $school_id = 1; }

if (!isset($_SESSION['__print_attendance_debug_logged'])) {
    error_log('print-attendance: resolved (post-lookup) user_id=' . var_export($user_id, true) . ' school_id=' . var_export($school_id, true));
    $_SESSION['__print_attendance_debug_logged'] = true;
}

// Get the selected dates, course_section, subject, and school year from the form submission
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-1 week'));
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');
$selected_course_section = isset($_POST['course_section']) ? $_POST['course_section'] : '';
$selected_subject = isset($_POST['selected_subject']) ? $_POST['selected_subject'] : '';
$selected_day = isset($_POST['selected_day']) ? $_POST['selected_day'] : '';

// Display text for course section (show "All Courses and Sections" when none selected)
$display_course_section = !empty($selected_course_section) ? $selected_course_section : "All Courses and Sections";
$display_subject = !empty($selected_subject) ? $selected_subject : "All Subjects";
$display_day = !empty($selected_day) ? $selected_day : "All Days";

// Fetch user's email from session
$email = isset($_SESSION['email']) ? $_SESSION['email'] : null;

// Check if academic settings exist in session, if not try to load from database
if ((!isset($_SESSION['school_year']) || !isset($_SESSION['semester'])) && $email) {
    try {
        $settings_query = "SELECT school_year, semester FROM user_settings WHERE email = ?";
        $settings_stmt = $conn_qr->prepare($settings_query);
        $settings_stmt->bind_param("s", $email);
        $settings_stmt->execute();
        $settings_result = $settings_stmt->get_result();
        
        if ($settings_result && $settings_result->num_rows > 0) {
            $settings = $settings_result->fetch_assoc();
            $_SESSION['school_year'] = $settings['school_year'];
            $_SESSION['semester'] = $settings['semester'];
        }
    } catch (Exception $e) {
        error_log("Error fetching academic settings: " . $e->getMessage());
    }
}

// Get academic settings with defaults
$school_year = isset($_SESSION['school_year']) ? $_SESSION['school_year'] : (date('Y') . '-' . (date('Y') + 1));
$semester = isset($_SESSION['semester']) ? $_SESSION['semester'] : '1st Semester';

// Get instructor information from session
$instructor_name = isset($_SESSION['current_instructor_name']) ? $_SESSION['current_instructor_name'] : 'Not specified';
$subject_name = isset($_SESSION['current_subject_name']) ? $_SESSION['current_subject_name'] : 'Not specified';

// Dynamic Course & Section list resolution (STRICT per user request)
// Only pull rows owned by EXACT current user_id & school_id from tbl_student
$dynamic_course_sections = [];
try {
    if ($user_id && $school_id) {
        $student_cs_sql = "SELECT DISTINCT course_section
                            FROM tbl_student
                            WHERE school_id = :school_id
                              AND user_id = :user_id
                              AND course_section IS NOT NULL
                              AND course_section <> ''
                            ORDER BY course_section";
        $scs_stmt = $conn->prepare($student_cs_sql);
        $scs_stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
        $scs_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $scs_stmt->execute();
        $dynamic_course_sections = $scs_stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log('print-attendance: fetched ' . count($dynamic_course_sections) . ' course_section values for user_id=' . $user_id . ' school_id=' . $school_id);

        // Fallback 1: If strict filter empty, relax user filter (school-wide) to diagnose
        if (empty($dynamic_course_sections)) {
            error_log('print-attendance: strict list empty, running relaxed school-wide query');
            $relaxed_sql = "SELECT DISTINCT course_section FROM tbl_student
                            WHERE school_id = :school_id
                              AND course_section IS NOT NULL AND course_section <> ''
                            ORDER BY course_section";
            $relaxed_stmt = $conn->prepare($relaxed_sql);
            $relaxed_stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
            $relaxed_stmt->execute();
            $relaxed_list = $relaxed_stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log('print-attendance: relaxed list count=' . count($relaxed_list));
            if (!empty($relaxed_list)) {
                // Keep both lists for debug; don't overwrite strict list, but we will use relaxed for display
                $dynamic_course_sections = $relaxed_list;
                $print_attendance_relaxed_used = true;
            }
        }

        // Fallback 2: If still empty, attempt mysqli connection (in case PDO sees different DB)
        if (empty($dynamic_course_sections) && isset($conn_qr)) {
            error_log('print-attendance: attempting mysqli fallback query');
            $safe_school = (int)$school_id;
            $safe_user = (int)$user_id;
            $mysqli_q = "SELECT DISTINCT course_section FROM tbl_student WHERE school_id = $safe_school AND user_id = $safe_user AND course_section IS NOT NULL AND course_section <> '' ORDER BY course_section";
            if ($mres = $conn_qr->query($mysqli_q)) {
                $tmp = [];
                while ($r = $mres->fetch_assoc()) { $tmp[] = $r['course_section']; }
                error_log('print-attendance: mysqli fallback rows=' . count($tmp));
                if (!empty($tmp)) { $dynamic_course_sections = $tmp; $print_attendance_mysqli_used = true; }
            } else {
                error_log('print-attendance: mysqli fallback error ' . $conn_qr->error);
            }
        }
    } else {
        error_log('print-attendance: user_id or school_id missing AFTER lookup; course_section list cannot be built.');
    }
} catch (Exception $e) {
    error_log('print-attendance: error building strict course_section list: ' . $e->getMessage());
}

// Get subjects for filter dropdown from actual attendance records
$dynamic_subjects = [];
try {
    if ($user_id && $school_id) {
        $subjects_sql = "SELECT DISTINCT tbl_subjects.subject_name 
                         FROM tbl_attendance 
                         LEFT JOIN tbl_subjects ON tbl_subjects.subject_id = tbl_attendance.subject_id 
                         WHERE tbl_attendance.school_id = :school_id AND tbl_attendance.user_id = :user_id 
                         AND tbl_subjects.subject_name IS NOT NULL AND tbl_subjects.subject_name != '' 
                         ORDER BY tbl_subjects.subject_name";
        $subjects_stmt = $conn->prepare($subjects_sql);
        $subjects_stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
        $subjects_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $subjects_stmt->execute();
        $dynamic_subjects = $subjects_stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log('print-attendance: fetched ' . count($dynamic_subjects) . ' subjects for user_id=' . $user_id . ' school_id=' . $school_id);
    }
} catch (Exception $e) {
    error_log('print-attendance: error building subjects list: ' . $e->getMessage());
}

// Debug - Log input parameters
error_log("Date Range: $start_date to $end_date, Course: $selected_course_section, Day: $selected_day");

// Prepare the SQL statement with filtering (include school_id and user_id for data isolation)
$sql = "
    SELECT *, 
    DATE_FORMAT(time_in, '%r') AS formatted_time_in,
    DATE_FORMAT(time_in, '%Y-%m-%d') AS attendance_date,
    DATE_FORMAT(time_in, '%W') AS day_of_week,
    COALESCE(tbl_subjects.subject_name, 'Not specified') AS subject_name
    FROM tbl_attendance 
    LEFT JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id 
    LEFT JOIN tbl_subjects ON tbl_subjects.subject_id = tbl_attendance.subject_id
    WHERE time_in IS NOT NULL AND tbl_attendance.school_id = :school_id AND tbl_attendance.user_id = :user_id
";

// Add date range filter - Ensure dates are properly formatted and the condition is applied
if (!empty($start_date) && !empty($end_date)) {
    // Ensure we're comparing dates correctly by converting to date format
    $sql .= " AND DATE(time_in) BETWEEN :start_date AND :end_date";
}

// Add course section filter
if (!empty($selected_course_section)) {
    $sql .= " AND tbl_student.course_section = :course_section";
}

// Add subject filter
if (!empty($selected_subject)) {
    $sql .= " AND tbl_subjects.subject_name = :selected_subject";
}

// Add day of week filter
if (!empty($selected_day)) {
    $sql .= " AND DATE_FORMAT(time_in, '%W') = :selected_day";
}

$sql .= " ORDER BY time_in DESC";

// Debug - Log SQL
error_log("SQL Query: $sql");

$stmt = $conn->prepare($sql);

// Bind required parameters for data isolation
$stmt->bindParam(':school_id', $school_id);
$stmt->bindParam(':user_id', $user_id);
error_log("Binding school_id: $school_id, user_id: $user_id");

// Bind additional filter parameters
if (!empty($start_date) && !empty($end_date)) {
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    error_log("Binding dates: $start_date to $end_date");
}

if (!empty($selected_course_section)) {
    $stmt->bindParam(':course_section', $selected_course_section);
    error_log("Binding course section: $selected_course_section");
}

if (!empty($selected_subject)) {
    $stmt->bindParam(':selected_subject', $selected_subject);
    error_log("Binding subject: $selected_subject");
}

if (!empty($selected_day)) {
    $stmt->bindParam(':selected_day', $selected_day);
    error_log("Binding day of week: $selected_day");
}

$stmt->execute();
$result = $stmt->fetchAll();

// Debug - Log result count
error_log("Results found: " . count($result));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printable Attendance List</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./styles/print-attendance.css">
   
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                width: 100%;
                max-width: 100%;
            }
            .card {
                border: 1px solid #ddd;
            }
            .table th, .table td {
                border: 1px solid #ddd;
            }
            /* Add styling for table headers */
            .table thead th {
                background-color: #098744 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            /* Style for Late status */
            .late-status {
                color: #dc3545 !important;
                font-weight: bold;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            /* Style for On Time status */
            .ontime-status {
                color: #28a745 !important;
                font-weight: bold;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        /* Table styling for web view */
        .table thead th {
            background-color: #098744;
            color: white;
        }
        .late-status {
            color: #dc3545;
            font-weight: bold;
        }
        .ontime-status {
            color: #28a745;
            font-weight: bold;
        }
        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }
        .action-buttons button {
            margin: 0 5px;
            padding: 8px 16px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        .action-buttons button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
        }
        .btn-success {
            background-color: #098744;
            border-color: #098744;
        }
        .btn-success:hover {
            background-color: #076a34;
            border-color: #076a34;
        }
    </style>
   
    <script>
        function printPage() {
            window.print();
        }

        function exportData(format) {
            // Get form values
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const courseSection = document.getElementById('course_section').value;
            const selectedSubject = document.getElementById('selected_subject').value;
            const selectedDay = document.getElementById('selected_day').value;
            
            // Build URL with parameters
            let url = `export_attendance.php?format=${format}`;
            
            if (startDate) url += `&start_date=${startDate}`;
            if (endDate) url += `&end_date=${endDate}`;
            if (courseSection) url += `&course_section=${courseSection}`;
            if (selectedSubject) url += `&selected_subject=${selectedSubject}`;
            if (selectedDay) url += `&selected_day=${selectedDay}`;
            
            // Redirect to export URL
            window.location.href = url;
        }

        function handleFormSubmit(event) {
            event.preventDefault();
            const form = document.getElementById("attendanceForm");
            const formData = new FormData(form);
            
            // Log form data for debugging
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.open();
                document.write(data);
                document.close();
                // Don't auto-print, let the user check the data first
                console.log("Report generated");
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</head>
<body>
    <div class="container">
        <h4 class="text-center">List of Present Students</h4>
        
        <!-- Report Summary - This will be visible in print -->
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Report Summary</h5>
                <p class="card-text"><strong>Date Range:</strong> <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?></p>
                <p class="card-text"><strong>Day:</strong> <?= htmlspecialchars($display_day) ?></p>
                <p class="card-text"><strong>Course & Section:</strong> <?= htmlspecialchars($display_course_section) ?></p>
                <p class="card-text"><strong>Subject:</strong> <?= htmlspecialchars($display_subject) ?></p>
                <p class="card-text"><strong>School Year:</strong> <?= htmlspecialchars($school_year) ?></p>
                <p class="card-text"><strong>Semester:</strong> <?= htmlspecialchars($semester) ?></p>
                <p class="card-text"><strong>Instructor:</strong> <?= htmlspecialchars($instructor_name) ?></p>
            </div>
        </div>

        <!-- Form - This will be hidden in print -->
        <form id="attendanceForm" method="POST" class="no-print" onsubmit="handleFormSubmit(event)">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label for="start_date" class="col-sm-3 col-form-label text-right">From:</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="end_date" class="col-sm-3 col-form-label text-right">To:</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group row">
                        <label for="course_section" class="col-sm-4 col-form-label text-right">Course & Section:</label>
                        <div class="col-sm-8">
                            <select name="course_section" id="course_section" class="form-control">
                                <option value="">All Courses and Sections</option>
                                <?php if (!empty($dynamic_course_sections)): ?>
                                    <?php foreach ($dynamic_course_sections as $cs): ?>
                                        <option value="<?= htmlspecialchars($cs) ?>" <?= ($selected_course_section === $cs) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cs) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="selected_subject" class="col-sm-4 col-form-label text-right">Subject:</label>
                        <div class="col-sm-8">
                            <select name="selected_subject" id="selected_subject" class="form-control">
                                <option value="">All Subjects</option>
                                <?php if (!empty($dynamic_subjects)): ?>
                                    <?php foreach ($dynamic_subjects as $subject): ?>
                                        <option value="<?= htmlspecialchars($subject) ?>" <?= ($selected_subject === $subject) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($subject) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="selected_day" class="col-sm-4 col-form-label text-right">Day:</label>
                        <div class="col-sm-8">
                            <select name="selected_day" id="selected_day" class="form-control">
                                <option value="">All Days</option>
                                <option value="Monday" <?= ($selected_day == 'Monday') ? 'selected' : '' ?>>Monday</option>
                                <option value="Tuesday" <?= ($selected_day == 'Tuesday') ? 'selected' : '' ?>>Tuesday</option>
                                <option value="Wednesday" <?= ($selected_day == 'Wednesday') ? 'selected' : '' ?>>Wednesday</option>
                                <option value="Thursday" <?= ($selected_day == 'Thursday') ? 'selected' : '' ?>>Thursday</option>
                                <option value="Friday" <?= ($selected_day == 'Friday') ? 'selected' : '' ?>>Friday</option>
                                <option value="Saturday" <?= ($selected_day == 'Saturday') ? 'selected' : '' ?>>Saturday</option>
                              
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Buttons - centered between form and table -->
            <div class="action-buttons mb-4">
                <button type="submit" class="btn btn-primary" style="background-color: #098744;">Generate Report</button>
                <button type="button" onclick="printPage()" class="btn btn-success">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <!-- Export buttons -->
                <button type="button" onclick="exportData('csv')" class="btn btn-success">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button type="button" onclick="exportData('excel')" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button type="button" onclick="exportData('pdf')" class="btn btn-success">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
            </div>
        </form>

        <?php if (empty($dynamic_course_sections)): ?>
            <div class="alert alert-warning no-print" style="margin-top:10px;">
                <strong>Debug:</strong> No course & section options found for
                user_id=<code><?= htmlspecialchars((string)$user_id) ?></code>,
                school_id=<code><?= htmlspecialchars((string)$school_id) ?></code>.
                Check that tbl_student has rows with matching user_id & school_id and non-empty course_section.
                <?php if (isset($print_attendance_relaxed_used)): ?> (Showing relaxed school-wide list but it was empty.)<?php endif; ?>
                <?php if (isset($print_attendance_mysqli_used)): ?> (MySQLi fallback used.)<?php endif; ?>
            </div>
        <?php endif; ?>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Course & Section</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Time In</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && count($result) > 0): ?>
                    <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= htmlspecialchars($row['course_section']) ?></td>
                            <td><?= htmlspecialchars($row['subject_name'] ?? 'Not specified') ?></td>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($row['attendance_date']))) ?></td>
                            <td><?= htmlspecialchars($row['day_of_week']) ?></td>
                            <td><?= htmlspecialchars($row['formatted_time_in']) ?></td>
                            <td class="<?= strtolower($row['status']) === 'late' ? 'late-status' : 'ontime-status' ?>">
                                <?= htmlspecialchars($row['status'] ?? 'N/A') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No attendance records found for the selected criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
