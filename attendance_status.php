<?php
// Use consistent session handling
require_once 'includes/session_config.php';
require_once 'includes/asset_helper.php';
require_once 'includes/data_isolation_helper.php';
include('./conn/db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: admin/login.php");
    exit;
}

// Get user context for data isolation
$context = getCurrentUserContext();

// Get selected status filter (if any)
$selected_status = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$selected_date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
$selected_course = isset($_GET['course_filter']) ? $_GET['course_filter'] : '';

// Default date range values - show all data by default
$start_date = '2025-01-01';  // Start from beginning of year
$end_date = date('Y-12-31');  // End at end of current year

// Set date range based on selection
if ($selected_date_range == 'today') {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d');
} elseif ($selected_date_range == 'yesterday') {
    $start_date = date('Y-m-d', strtotime('-1 day'));
    $end_date = date('Y-m-d', strtotime('-1 day'));
} elseif ($selected_date_range == 'week') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = date('Y-m-d');
} elseif ($selected_date_range == 'month') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
} elseif ($selected_date_range == 'custom' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
} elseif ($selected_date_range == 'all') {
    // Show all attendance data regardless of date
    $start_date = '2025-01-01';
    $end_date = date('Y-12-31');
}

try {
    // Get all courses for filter dropdown with data isolation
    $coursesQuery = "SELECT DISTINCT course_section FROM tbl_student 
                     WHERE school_id = ? 
                     " . ($context['user_id'] ? "AND (user_id = ? OR user_id IS NULL)" : "") . "
                     ORDER BY course_section";
    $coursesStmt = $conn_qr->prepare($coursesQuery);
    
    if ($context['user_id']) {
        $coursesStmt->bind_param("ii", $context['school_id'], $context['user_id']);
    } else {
        $coursesStmt->bind_param("i", $context['school_id']);
    }
    
    $coursesStmt->execute();
    $coursesResult = $coursesStmt->get_result();
    $courses = [];
    
    while ($row = $coursesResult->fetch_assoc()) {
        $courses[] = $row['course_section'];
    }
    
    // Get attendance status records with filtering and data isolation
    $statusQuery = "SELECT 
        tbl_student.student_name,
        tbl_student.course_section,
        DATE_FORMAT(tbl_attendance.time_in, '%Y-%m-%d') AS attendance_date,
        DATE_FORMAT(tbl_attendance.time_in, '%r') AS time_in,
        tbl_attendance.status,
        tbl_subjects.subject_name,
        tbl_instructors.instructor_name
    FROM tbl_attendance 
    LEFT JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id
    LEFT JOIN tbl_subjects ON tbl_attendance.subject_id = tbl_subjects.subject_id
    LEFT JOIN tbl_instructors ON tbl_attendance.instructor_id = tbl_instructors.instructor_id
    WHERE DATE(tbl_attendance.time_in) BETWEEN ? AND ?
    AND tbl_student.school_id = ? 
    " . ($context['user_id'] ? "AND (tbl_student.user_id = ? OR tbl_student.user_id IS NULL)" : "") . "
    AND (tbl_attendance.school_id = ? OR tbl_attendance.school_id IS NULL)
    " . ($context['user_id'] ? "AND (tbl_attendance.user_id = ? OR tbl_attendance.user_id IS NULL)" : "");
    
    $params = [$start_date, $end_date, $context['school_id']];
    $types = "ssi";
    
    if ($context['user_id']) {
        $params[] = $context['user_id'];
        $types .= "i";
    }
    
    $params[] = $context['school_id'];
    $types .= "i";
    
    if ($context['user_id']) {
        $params[] = $context['user_id'];
        $types .= "i";
    }
    
    // Add status filter if selected
    if (!empty($selected_status)) {
        $statusQuery .= " AND tbl_attendance.status = ?";
        $params[] = $selected_status;
        $types .= "s";
    }
    
    // Add course filter if selected
    if (!empty($selected_course)) {
        $statusQuery .= " AND tbl_student.course_section = ?";
        $params[] = $selected_course;
        $types .= "s";
    }
    
    $statusQuery .= " ORDER BY tbl_attendance.time_in DESC LIMIT 500";
    
    $statusStmt = $conn_qr->prepare($statusQuery);
    
    if (!empty($params)) {
        $statusStmt->bind_param($types, ...$params);
    }
    
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    
    $attendanceStatusData = [];
    while ($row = $statusResult->fetch_assoc()) {
        $attendanceStatusData[] = $row;
    }
    
    // Get summary statistics
    $totalRecords = count($attendanceStatusData);
    $onTimeCount = 0;
    $lateCount = 0;
    
    foreach ($attendanceStatusData as $record) {
        if (strtolower($record['status']) === 'on time') {
            $onTimeCount++;
        } elseif (strtolower($record['status']) === 'late') {
            $lateCount++;
        }
    }
    
    $onTimePercentage = $totalRecords > 0 ? ($onTimeCount / $totalRecords) * 100 : 0;
    $latePercentage = $totalRecords > 0 ? ($lateCount / $totalRecords) * 100 : 0;
    
} catch(Exception $e) {
    error_log("Error in attendance_status.php: " . $e->getMessage());
    $courses = [];
    $attendanceStatusData = [];
    $totalRecords = 0;
    $onTimeCount = 0;
    $lateCount = 0;
    $onTimePercentage = 0;
    $latePercentage = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Status Report - QR Code Attendance System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Container styles */
        .status-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            margin: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            width: calc(100% - 40px);
            transition: all 0.3s ease;
        }

        .main.active .status-container {
            width: calc(100% - 40px);
            margin: 20px;
        }

        /* Card styles */
        .status-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

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

        /* Status styles */
        .late-status {
            color: #dc3545;
            font-weight: bold;
        }
        
        .ontime-status {
            color: #28a745;
            font-weight: bold;
        }

        /* Stats card styles */
        .stats-card {
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
            text-align: center;
        }

        .stats-card h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .stats-card p {
            margin-bottom: 0;
            font-size: 16px;
        }

        .ontime-card {
            background: linear-gradient(45deg, #28a745, #20c997);
        }

        .late-card {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
        }

        .total-card {
            background: linear-gradient(45deg, #098744, #0caa59);
        }

        /* Hide date inputs initially */
        .custom-date-container {
            display: none;
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
            
            .status-container {
                margin: 10px;
                width: calc(100% - 20px);
            }

            .filter-col {
                margin-bottom: 10px;
            }
        }

        /* Content padding */
        .status-content {
            padding: 20px;
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                width: 100%;
                max-width: 100%;
            }
            .status-card {
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
    </style>
</head>
<body>
    <!-- Custom Alert Box -->
    <div id="customAlert" class="custom-alert"></div>

    <?php include('./components/sidebar-nav.php'); ?>

    <div class="main collapsed" id="main">
        <div class="status-container">
            <div class="status-content">
                <div class="title">
                    <h4><i class="fas fa-clock"></i> Attendance Status Report</h4>
                </div>
                
                <div class="status-content">
                    <!-- Summary Statistics Cards -->
                    <div class="row mt-3 mb-4">
                        <div class="col-md-4">
                            <div class="stats-card total-card">
                                <h3><?php echo $totalRecords; ?></h3>
                                <p>Total Records</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card ontime-card">
                                <h3><?php echo $onTimeCount; ?> (<?php echo number_format($onTimePercentage, 1); ?>%)</h3>
                                <p>On Time</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card late-card">
                                <h3><?php echo $lateCount; ?> (<?php echo number_format($latePercentage, 1); ?>%)</h3>
                                <p>Late</p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter Form -->
                    <div class="status-card mb-4">
                        <h5 class="mb-3" style="color: #098744;">Filter Options</h5>
                        <form method="get" action="" class="no-print">
                            <div class="row">
                                <div class="col-md-3 filter-col">
                                    <div class="form-group">
                                        <label for="status_filter">Attendance Status:</label>
                                        <select name="status_filter" id="status_filter" class="form-control">
                                            <option value="">All Statuses</option>
                                            <option value="On Time" <?= ($selected_status == 'On Time') ? 'selected' : '' ?>>On Time</option>
                                            <option value="Late" <?= ($selected_status == 'Late') ? 'selected' : '' ?>>Late</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 filter-col">
                                    <div class="form-group">
                                        <label for="course_filter">Course & Section:</label>
                                        <select name="course_filter" id="course_filter" class="form-control">
                                            <option value="">All Courses</option>
                                            <?php foreach ($courses as $course): ?>
                                                <option value="<?= htmlspecialchars($course) ?>" <?= ($selected_course == $course) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($course) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 filter-col">
                                    <div class="form-group">
                                        <label for="date_range">Date Range:</label>
                                        <select name="date_range" id="date_range" class="form-control">
                                            <option value="all" <?= ($selected_date_range == 'all') ? 'selected' : '' ?>>All Time</option>
                                            <option value="today" <?= ($selected_date_range == 'today') ? 'selected' : '' ?>>Today</option>
                                            <option value="yesterday" <?= ($selected_date_range == 'yesterday') ? 'selected' : '' ?>>Yesterday</option>
                                            <option value="week" <?= ($selected_date_range == 'week') ? 'selected' : '' ?>>Last 7 Days</option>
                                            <option value="month" <?= ($selected_date_range == 'month') ? 'selected' : '' ?>>Last 30 Days</option>
                                            <option value="custom" <?= ($selected_date_range == 'custom') ? 'selected' : '' ?>>Custom Range</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 filter-col custom-date-container" id="custom_date_container">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label for="start_date">Start Date:</label>
                                                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $start_date ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label for="end_date">End Date:</label>
                                                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $end_date ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary" style="background-color: #098744;">
                                        <i class="fas fa-filter"></i> Apply Filters
                                    </button>
                                    <a href="attendance_status.php" class="btn btn-secondary">
                                        <i class="fas fa-sync"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Report Header (shown when printing) -->
                    <div class="d-none d-print-block mb-4">
                        <h4 class="text-center">Attendance Status Report</h4>
                        <p class="text-center">
                            <strong>Date Range:</strong> <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?><br>
                            <strong>Status:</strong> <?= !empty($selected_status) ? $selected_status : 'All' ?><br>
                            <strong>Course:</strong> <?= !empty($selected_course) ? $selected_course : 'All' ?><br>
                            <strong>Generated on:</strong> <?= date('M d, Y h:i A') ?>
                        </p>
                    </div>
                    
                    <!-- Attendance Status Table -->
                    <div class="status-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 style="color: #098744;">Attendance Records</h5>
                            
                            <!-- Export and Print buttons -->
                            <div class="no-print">
                                <button type="button" onclick="exportStatusData('csv')" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                                <button type="button" onclick="exportStatusData('excel')" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                                <button type="button" onclick="window.print()" class="btn btn-success btn-sm">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead style="background-color: #098744; color: white;">
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Course & Section</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Subject</th>
                                        <th>Instructor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($attendanceStatusData)): ?>
                                        <?php foreach ($attendanceStatusData as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                                <td><?= htmlspecialchars($row['course_section']) ?></td>
                                                <td><?= htmlspecialchars(date('M d, Y', strtotime($row['attendance_date']))) ?></td>
                                                <td><?= htmlspecialchars($row['time_in']) ?></td>
                                                <td><?= htmlspecialchars($row['subject_name'] ?? 'Not specified') ?></td>
                                                <td><?= htmlspecialchars($row['instructor_name'] ?? 'Not specified') ?></td>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="<?php echo asset_url('js/popper.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.min.js'); ?>"></script>
    <script src="./functions/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        // Toggle date range picker visibility based on selection
        document.addEventListener('DOMContentLoaded', function() {
            const dateRangeSelect = document.getElementById('date_range');
            const customDateContainer = document.getElementById('custom_date_container');
            
            // Initial state
            if (dateRangeSelect.value === 'custom') {
                customDateContainer.style.display = 'block';
            }
            
            // Change event
            dateRangeSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customDateContainer.style.display = 'block';
                } else {
                    customDateContainer.style.display = 'none';
                }
            });
            
            // Export function
            window.exportStatusData = function(format) {
                // Get current filters
                const statusFilter = document.getElementById('status_filter').value;
                const courseFilter = document.getElementById('course_filter').value;
                const dateRange = document.getElementById('date_range').value;
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                
                // Construct URL with parameters
                let url = 'export_status_report.php?format=' + format;
                
                if (statusFilter) {
                    url += '&status=' + encodeURIComponent(statusFilter);
                }
                
                if (courseFilter) {
                    url += '&course=' + encodeURIComponent(courseFilter);
                }
                
                if (dateRange) {
                    url += '&date_range=' + encodeURIComponent(dateRange);
                }
                
                if (dateRange === 'custom' && startDate && endDate) {
                    url += '&start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate);
                }
                
                // Navigate to the export URL
                window.location.href = url;
            };
        });
    </script>
</body>
</html> 