<?php
// Include your database connection file
include('./conn/conn.php');
// Include database connections file to access instructor data
include('./conn/db_connect.php');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Start session if not started to access academic settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the selected dates, course_section, and school year from the form submission
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-1 week'));
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');
$selected_course_section = isset($_POST['course_section']) ? $_POST['course_section'] : '';
$selected_day = isset($_POST['selected_day']) ? $_POST['selected_day'] : '';

// Display text for course section (show "All Courses and Sections" when none selected)
$display_course_section = !empty($selected_course_section) ? $selected_course_section : "All Courses and Sections";
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

// Debug - Log input parameters
error_log("Date Range: $start_date to $end_date, Course: $selected_course_section, Day: $selected_day");

// Prepare the SQL statement with filtering
$sql = "
    SELECT *, 
    DATE_FORMAT(time_in, '%r') AS formatted_time_in,
    DATE_FORMAT(time_in, '%Y-%m-%d') AS attendance_date,
    DATE_FORMAT(time_in, '%W') AS day_of_week
    FROM tbl_attendance 
    LEFT JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id 
    WHERE time_in IS NOT NULL
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

// Add day of week filter
if (!empty($selected_day)) {
    $sql .= " AND DATE_FORMAT(time_in, '%W') = :selected_day";
}

$sql .= " ORDER BY time_in DESC";

// Debug - Log SQL
error_log("SQL Query: $sql");

$stmt = $conn->prepare($sql);

// Bind parameters
if (!empty($start_date) && !empty($end_date)) {
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    error_log("Binding dates: $start_date to $end_date");
}

if (!empty($selected_course_section)) {
    $stmt->bindParam(':course_section', $selected_course_section);
    error_log("Binding course section: $selected_course_section");
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
            const selectedDay = document.getElementById('selected_day').value;
            
            // Build URL with parameters
            let url = `export_attendance.php?format=${format}`;
            
            if (startDate) url += `&start_date=${startDate}`;
            if (endDate) url += `&end_date=${endDate}`;
            if (courseSection) url += `&course_section=${courseSection}`;
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
                <p class="card-text"><strong>School Year:</strong> <?= htmlspecialchars($school_year) ?></p>
                <p class="card-text"><strong>Semester:</strong> <?= htmlspecialchars($semester) ?></p>
                <p class="card-text"><strong>Instructor:</strong> <?= htmlspecialchars($instructor_name) ?></p>
                <p class="card-text"><strong>Subject:</strong> <?= htmlspecialchars($subject_name) ?></p>
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
                                <option value="BSIS-201" <?= ($selected_course_section == 'BSIS-201') ? 'selected' : '' ?>>BSIS-201</option>
                                <option value="BSIS-202" <?= ($selected_course_section == 'BSIS-202') ? 'selected' : '' ?>>BSIS-202</option>
                                <option value="BSIT-301" <?= ($selected_course_section == 'BSIT-301') ? 'selected' : '' ?>>BSIT-301</option>
                                <option value="BSIT-302" <?= ($selected_course_section == 'BSIT-302') ? 'selected' : '' ?>>BSIT-302</option>
                                <option value="BSIT-401" <?= ($selected_course_section == 'BSIT-401') ? 'selected' : '' ?>>BSIT-401</option>
                                <option value="BSBA-301" <?= ($selected_course_section == 'BSBA-301') ? 'selected' : '' ?>>BSBA-301</option>
                                <option value="BSBA-302" <?= ($selected_course_section == 'BSBA-302') ? 'selected' : '' ?>>BSBA-302</option>
                                <option value="BSTM-101" <?= ($selected_course_section == 'BSTM-101') ? 'selected' : '' ?>>BSTM-101</option>
                                <option value="BSTM-102" <?= ($selected_course_section == 'BSTM-102') ? 'selected' : '' ?>>BSTM-102</option>
                                <option value="BSE-101" <?= ($selected_course_section == 'BSE-101') ? 'selected' : '' ?>>BSE-101</option>
                                <option value="BSE-102" <?= ($selected_course_section == 'BSE-102') ? 'selected' : '' ?>>BSE-102</option>
                                <option value="BSE-201" <?= ($selected_course_section == 'BSE-201') ? 'selected' : '' ?>>BSE-201</option>
                                <option value="BSE-202" <?= ($selected_course_section == 'BSE-202') ? 'selected' : '' ?>>BSE-202</option>
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
                                <option value="Sunday" <?= ($selected_day == 'Sunday') ? 'selected' : '' ?>>Sunday</option>
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

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Course & Section</th>
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
                        <td colspan="6" class="text-center">No attendance records found for the selected criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
