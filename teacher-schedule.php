
<?php
// teacher-schedule.php
// Teacher Schedule Management System
require_once 'includes/session_config.php';

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

// Get user data
$user_email = $_SESSION['email'];
$user_school_id = $_SESSION['school_id'] ?? 1;

// Database connection
require_once 'conn/db_connect_pdo.php';
$pdo = $conn_qr_pdo; // For master_schedule table
$login_pdo = $conn_login_pdo; // For users table

// Get teacher's username from users table (login_register DB)
$teacher_username = '';
try {
    $stmt = $login_pdo->prepare("SELECT username FROM users WHERE email = ?");
    $stmt->execute([$user_email]);
    $teacher_username = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Error fetching teacher username: " . $e->getMessage());
}

// Remove week navigation and week selection
// Get current month and year
$current_month = $_GET['month'] ?? date('Y-m');
$current_year = date('Y', strtotime($current_month));
$current_month_num = date('n', strtotime($current_month));

// Helper: Get all days in the current month
function getMonthDays($year, $month) {
    $days = [];
    $date = strtotime("$year-$month-01");
    $lastDay = date('t', $date);
    for ($d = 1; $d <= $lastDay; $d++) {
        $days[] = date('Y-m-d', strtotime("$year-$month-$d"));
    }
    return $days;
}
$monthDays = getMonthDays($current_year, str_pad($current_month_num, 2, '0', STR_PAD_LEFT));

// Fetch holidays for this month from database
$holidays = [];
$holiday_names = [];
try {
    $stmt = $pdo->prepare("
        SELECT holiday_date, holiday_name 
        FROM holidays 
        WHERE school_id = ? AND YEAR(holiday_date) = ? AND MONTH(holiday_date) = ?
        ORDER BY holiday_date
    ");
    $stmt->execute([$user_school_id, $current_year, $current_month_num]);
    $holiday_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($holiday_data as $holiday) {
        $holidays[] = $holiday['holiday_date'];
        $holiday_names[$holiday['holiday_date']] = $holiday['holiday_name'];
    }
} catch (Exception $e) {
    error_log("Error fetching holidays: " . $e->getMessage());
}

// Fetch national holidays for the current year
$nationalHolidays = [];
$nationalHolidayNames = [];
try {
    // Philippine National Holidays (Fixed dates)
    $nationalHolidaysData = [
        // Fixed Date Holidays
        $current_year . '-01-01' => 'New Year\'s Day',
        $current_year . '-04-09' => 'Day of Valor (Araw ng Kagitingan)',
        $current_year . '-05-01' => 'Labor Day',
        $current_year . '-06-12' => 'Independence Day',
        $current_year . '-08-21' => 'Ninoy Aquino Day',
        $current_year . '-08-30' => 'National Heroes Day',
        $current_year . '-11-01' => 'All Saints\' Day',
        $current_year . '-11-02' => 'All Souls\' Day',
        $current_year . '-11-30' => 'Bonifacio Day',
        $current_year . '-12-24' => 'Christmas Eve',
        $current_year . '-12-25' => 'Christmas Day',
        $current_year . '-12-30' => 'Rizal Day',
        $current_year . '-12-31' => 'New Year\'s Eve',
    ];
    
    // Add Easter Sunday and related holidays
    $easter = date('Y-m-d', easter_date($current_year));
    $nationalHolidaysData[$easter] = 'Easter Sunday';
    
    // Add Good Friday (2 days before Easter)
    $goodFriday = date('Y-m-d', strtotime($easter . ' -2 days'));
    $nationalHolidaysData[$goodFriday] = 'Good Friday';
    
    // Add Maundy Thursday (3 days before Easter)
    $maundyThursday = date('Y-m-d', strtotime($easter . ' -3 days'));
    $nationalHolidaysData[$maundyThursday] = 'Maundy Thursday';
    
    // Add Black Saturday (1 day before Easter)
    $blackSaturday = date('Y-m-d', strtotime($easter . ' -1 day'));
    $nationalHolidaysData[$blackSaturday] = 'Black Saturday';
    
    // Filter holidays for current month only
    foreach ($nationalHolidaysData as $date => $name) {
        if (date('n', strtotime($date)) == $current_month_num) {
            $nationalHolidays[] = $date;
            $nationalHolidayNames[$date] = $name;
        }
    }
    
    // Debug: Log holidays for current month
    error_log("Current month: " . $current_month_num);
    error_log("National holidays for this month: " . implode(', ', $nationalHolidays));
} catch (Exception $e) {
    error_log("Error processing national holidays: " . $e->getMessage());
}

// Get teacher's schedules (fixed weekly schedule) - filtered by user
$teacher_schedules = [];
try {
        $stmt = $pdo->prepare("
        SELECT * FROM teacher_schedules 
        WHERE ((teacher_username = ? AND school_id = ?) OR (user_id = ? AND user_id IS NOT NULL))
        AND status = 'active' 
        ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), start_time
    ");
    $stmt->execute([$teacher_username, $school_id, $user_id]);
    $teacher_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching teacher schedules: " . $e->getMessage());
}

// Get available subjects for this teacher - filtered by user
$teacher_subjects = [];
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT subject FROM teacher_schedules 
        WHERE ((teacher_username = ? AND school_id = ?) OR (user_id = ? AND user_id IS NOT NULL))
        AND status = 'active'
    ");
    $stmt->execute([$teacher_username, $school_id, $user_id]);
    $teacher_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("Error fetching teacher subjects: " . $e->getMessage());
}

// Get school info for theming
$school_info = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM school_info WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $school_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching school info: " . $e->getMessage());
}

$theme_color = $school_info['theme_color'] ?? '#098744';

// Helper function to format time
function formatTime($time) {
    // Handle different time formats
    if (strpos($time, 'PM') !== false || strpos($time, 'AM') !== false) {
        // Already in 12-hour format
        return $time;
    } else {
        // Convert from 24-hour format to 12-hour format
        return date('g:i A', strtotime($time));
    }
}

// Helper function to convert 12-hour format to 24-hour format
function convertTo24Hour($time12h) {
    if (strpos($time12h, 'PM') !== false || strpos($time12h, 'AM') !== false) {
        return date('H:i', strtotime($time12h));
    }
    return $time12h;
}

// Group schedules by day of week
$schedulesByDay = [];
foreach ($teacher_schedules as $schedule) {
    $schedulesByDay[$schedule['day_of_week']][] = $schedule;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Schedule - QR Attendance System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./styles/main.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- DataTables CSS for table view -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    

    
    <style>
        :root {
            --primary-color: <?php echo $theme_color; ?>;
        }
        
        body {
            background-color: #f4f6f8 !important;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main {
            position: relative !important;
            min-height: 100vh !important;
            margin-left: 260px !important;
            padding: 20px !important;
            transition: all 0.3s ease !important;
            width: calc(100% - 260px) !important;
            z-index: 1 !important;
        }

        .sidebar.close ~ .main {
            margin-left: 78px !important;
            width: calc(100% - 78px) !important;
        }
        
        .schedule-container {
            background: white !important;
            border-radius: 20px !important;
            padding: 30px !important;
            margin: 0 !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        
        .calendar-header {
            background: linear-gradient(135deg, var(--primary-color), #0a5c2e);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .week-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .week-nav-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .week-nav-btn:hover {
            background: #0a5c2e;
            transform: translateY(-1px);
        }
        
        .week-nav-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .current-week {
            font-size: 1.5em;
            font-weight: bold;
            color: white;
        }
        
        .week-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-bottom: 30px;
            width: 100%;
        }
        
        .day-column {
            background: white;
            border-radius: 10px;
            padding: 15px;
            min-height: 200px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
            cursor: pointer;
            width: 100%;
        }
        
        .day-column:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .day-header {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .schedule-item {
            background: var(--primary-color);
            color: white;
            padding: 8px;
            border-radius: 5px;
            margin-bottom: 8px;
            font-size: 0.9em;
        }
        
        .schedule-item:hover {
            background: #0a5c2e;
        }
        
        .schedule-actions {
            margin-top: 5px;
        }
        
        .btn-xs {
            padding: 2px 6px;
            font-size: 0.75em;
        }
        
        .toggle-view-btns {
            margin-bottom: 20px;
            text-align: right;
        }
        
        .export-btns {
            margin-bottom: 20px;
        }
        
        .modal-header {
            background: var(--primary-color);
            color: white;
        }
        
        .modal-footer {
            border-top: 1px solid #dee2e6;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        /* Ensure form inputs are interactive */
        .modal input[type="text"],
        .modal input[type="time"],
        .modal select,
        .modal textarea {
            pointer-events: auto !important;
            user-select: auto !important;
            -webkit-user-select: auto !important;
            -moz-user-select: auto !important;
            -ms-user-select: auto !important;
            opacity: 1 !important;
            background-color: white !important;
            color: #333 !important;
            border: 1px solid #ced4da !important;
            position: relative !important;
            z-index: 1070 !important;
        }
        
        .modal input[type="text"]:focus,
        .modal input[type="time"]:focus,
        .modal select:focus,
        .modal textarea:focus {
            border-color: #80bdff !important;
            outline: 0 !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }
        
        /* Fix modal interaction issues */
        .modal {
            z-index: 1050 !important;
        }
        
        .modal-dialog {
            z-index: 1055 !important;
            pointer-events: auto !important;
        }
        
        .modal-content {
            z-index: 1060 !important;
            pointer-events: auto !important;
        }
        
        .modal-backdrop {
            z-index: 1040 !important;
            background-color: rgba(0, 0, 0, 0.3) !important;
        }
        
        .modal-backdrop.show {
            opacity: 0.3 !important;
        }
        
        /* Ensure modal form is interactive */
        #scheduleModal {
            pointer-events: auto !important;
        }
        
        #scheduleModal .modal-content {
            pointer-events: auto !important;
        }
        
        #scheduleModal form {
            pointer-events: auto !important;
        }
        
        #scheduleModal input,
        #scheduleModal select,
        #scheduleModal textarea,
        #scheduleModal button {
            pointer-events: auto !important;
            position: relative !important;
            z-index: 1070 !important;
        }
        
        .calendar-section {
            position: relative;
            display: inline-block;
            z-index: 1000;
        }
        
        .calendar-container {
            position: relative;
            display: inline-block;
        }
        
        .holiday-date {
            background-color: #ffebee !important;
            color: #d32f2f !important;
            font-weight: bold !important;
        }
        
        .holiday-date:hover {
            background-color: #ffcdd2 !important;
        }
        
        .holiday-tooltip {
            display: none;
            position: absolute;
            background: #333;
            color: white;
            padding: 5px;
            border-radius: 3px;
            font-size: 0.7rem;
            z-index: 1000;
            white-space: nowrap;
        }
        
        .holiday-date:hover .holiday-tooltip {
            display: block;
        }
        
        .mini-month-calendar {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 5px;
            z-index: 9999;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            width: 280px;
            display: none;
        }
        
        .holiday-date {
            background-color: #fff3e0 !important;
            color: #f57c00 !important;
            font-weight: bold !important;
            cursor: pointer;
            position: relative;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .holiday-date:hover {
            background-color: #ffe0b2 !important;
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .national-holiday {
            background-color: #ffebee !important;
            color: #d32f2f !important;
            border: 2px solid #d32f2f !important;
        }
        
        .national-holiday:hover {
            background-color: #ffcdd2 !important;
            border-color: #b71c1c !important;
        }
        
        .holiday-tooltip {
            display: none;
            position: absolute;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 11px;
            white-space: nowrap;
            z-index: 1001;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border: 1px solid #555;
        }
        
        .holiday-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        
        .holiday-date:hover .holiday-tooltip {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-50%) translateY(5px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        
        /* Add holiday indicator icon */
        .holiday-date::before {
            content: '🎉';
            font-size: 8px;
            position: absolute;
            top: -2px;
            right: -2px;
            opacity: 0.8;
        }
        
        .national-holiday::before {
            content: '🇵🇭';
            font-size: 8px;
            position: absolute;
            top: -2px;
            right: -2px;
            opacity: 0.9;
        }
        
        /* Today styling */
        .today {
            background-color: #e3f2fd !important;
            border: 2px solid #2196f3 !important;
            font-weight: bold !important;
        }
        
        .today:hover {
            background-color: #bbdefb !important;
            transform: scale(1.05);
        }
        
        /* Calendar date hover effect */
        .calendar-date:hover {
            background-color: #f5f5f5 !important;
            transform: scale(1.05);
            transition: all 0.2s ease;
        }
        
        /* Holiday legend */
        .holiday-legend {
            margin-top: 10px;
            font-size: 0.7rem;
            color: #666;
        }
        
        .legend-item {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 5px;
        }
        
        .legend-color {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-right: 5px;
            border: 1px solid #ccc;
        }

        .main.collapsed {
            margin-left: 78px;
            width: calc(100% - 78px);
        }
    </style>
</head>
<body>
<?php include('./components/sidebar-nav.php'); ?>
<div class="main collapsed" id="main">
    <div class="schedule-container">
        <div class="calendar-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4><i class="fas fa-chalkboard-teacher"></i> Teaching Schedule</h4>
                    <p class="mb-0">Manage your weekly class schedules</p>
                </div>
                <div class="toggle-view-btns">
                    <button class="btn btn-outline-primary active" id="calendarViewBtn"><i class="fas fa-calendar"></i> Week View</button>
                    <button class="btn btn-outline-primary" id="tableViewBtn"><i class="fas fa-table"></i> Table</button>
                </div>
            </div>
        </div>
        
        <!-- Fixed Weekly Schedule - No Navigation Needed -->
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="left-buttons">
                <button class="btn btn-primary" id="addScheduleBtn"><i class="fas fa-plus"></i> Add New Schedule</button>
                <button class="btn btn-success" id="exportExcel"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="btn btn-primary" id="exportWord"><i class="fas fa-file-word"></i> Word</button>
                <button class="btn btn-secondary" id="printSchedule"><i class="fas fa-print"></i> Print</button>
            </div>
            
        </div>
        
        <!-- Week Grid View -->
        <div id="calendarViewSection">
            <div class="week-grid">
                <?php
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                foreach ($days as $day):
                    $daySchedules = $schedulesByDay[$day] ?? [];
                ?>
                <div class="day-column" data-day="<?= $day ?>" style="cursor: pointer;">
                    <div class="day-header"><?= $day ?></div>
                    <?php if (!empty($daySchedules)): ?>
                        <?php foreach ($daySchedules as $sched): ?>
                            <div class="schedule-item">
                                <div><b><?= htmlspecialchars($sched['subject']) ?></b></div>
                                <div><?= htmlspecialchars($sched['section']) ?></div>
                                <div><?= formatTime($sched['start_time']) ?> - <?= formatTime($sched['end_time']) ?></div>
                                <?php if (!empty($sched['room'])): ?>
                                    <div>Room: <?= htmlspecialchars($sched['room']) ?></div>
                                <?php endif; ?>
                                <div class="schedule-actions">
                                    <button class="btn btn-xs btn-outline-light edit-btn" data-id="<?= $sched['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-xs btn-outline-light delete-btn" data-id="<?= $sched['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted text-center add-schedule-placeholder" style="padding: 20px;" title="Click to add schedule">
                            <i class="fas fa-plus"></i><br>
                            Click to add schedule
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Table View -->
        <div id="tableViewSection" style="display:none;">
            <table id="scheduleTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Course & Section</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Week</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($teacher_schedules as $schedule): ?>
                    <tr>
                        <td><?= htmlspecialchars($schedule['subject']) ?></td>
                        <td><?= htmlspecialchars($schedule['section']) ?></td>
                        <td><?= htmlspecialchars($schedule['day_of_week']) ?></td>
                        <td><?= formatTime($schedule['start_time']) . ' - ' . formatTime($schedule['end_time']) ?></td>
                        <td><?= htmlspecialchars($schedule['room'] ?? 'TBA') ?></td>
                        <td>Fixed Schedule</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-btn" data-id="<?= $schedule['id'] ?>"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $schedule['id'] ?>"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal for Add/Edit Schedule -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add/Edit Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="scheduleForm">
                    <input type="hidden" name="schedule_id" id="modal_schedule_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Subject</label>
                                    <input type="text" class="form-control" name="subject" id="modal_subject" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Course & Section</label>
                                    <select class="form-control" name="course_section" id="modal_course_section" required>
                                        <option value="">Select Course & Section</option>
                                    </select>
                                    <small class="form-text text-muted">Select from available courses and sections</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Day of Week</label>
                                    <select class="form-control" name="day_of_week" id="modal_day_of_week" required>
                                        <option value="">Select Day</option>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Start Time</label>
                                    <input type="time" class="form-control" name="start_time" id="modal_start_time" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>End Time</label>
                                    <input type="time" class="form-control" name="end_time" id="modal_end_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Room (Optional)</label>
                                    <input type="text" class="form-control" name="room" id="modal_room" placeholder="Enter room number or location">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<!-- Add Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Event</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="eventForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="eventDate">Event Date</label>
                        <input type="date" class="form-control" id="eventDate" name="eventDate" min="<?= $current_year ?>-<?= str_pad($current_month_num,2,'0',STR_PAD_LEFT) ?>-01" max="<?= $current_year ?>-<?= str_pad($current_month_num,2,'0',STR_PAD_LEFT) ?>-<?= date('t', strtotime($current_month)) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="eventName">Event Name</label>
                        <input type="text" class="form-control" id="eventName" name="eventName" required>
                    </div>
                    <div class="form-group">
                        <label for="eventType">Event Type</label>
                        <select class="form-control" id="eventType" name="eventType" required>
                            <option value="holiday">Holiday</option>
                            <option value="event">Event</option>
                            <option value="meeting">Meeting</option>
                            <option value="exam">Exam</option>
                            <option value="deadline">Deadline</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Event Details</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-calendar-day text-danger" id="eventIcon" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h4 id="eventDateDisplay"></h4>
                    <h3 id="eventNameDisplay" class="text-danger"></h3>
                    <p id="eventTypeDisplay" class="text-muted"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- jQuery first (required by other libraries) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS for table view (after jQuery) -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<!-- SheetJS for Excel/Word export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<!-- jsPDF for Print -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.7.0/jspdf.plugin.autotable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    let sidebarBtn = document.querySelector('.bx-menu');
    let sidebar = document.querySelector('.sidebar');
    let main = document.querySelector('.main');

    if (sidebarBtn) {
        sidebarBtn.addEventListener('click', function() {
            // Toggle sidebar
            sidebar.classList.toggle('close');
            
            // Adjust main content width
            if (sidebar.classList.contains('close')) {
                main.style.marginLeft = '78px';
                main.style.width = 'calc(100% - 78px)';
            } else {
                main.style.marginLeft = '260px';
                main.style.width = 'calc(100% - 260px)';
            }
        });
    }

    // Arrow toggle functionality
    let arrows = document.querySelectorAll('.arrow');
    arrows.forEach(arrow => {
        arrow.addEventListener('click', (e) => {
            let parent = e.target.parentElement.parentElement;
            parent.classList.toggle('showMenu');
        });
    });

    // Mark current page as active
    let currentPath = window.location.pathname;
    let navLinks = document.querySelectorAll('.nav-links li');
    navLinks.forEach(li => {
        let links = li.querySelectorAll('a');
        links.forEach(link => {
            if (link.getAttribute('href') === currentPath || 
                currentPath.endsWith(link.getAttribute('href'))) {
                li.classList.add('showMenu');
                let parentLi = link.closest('li');
                if (parentLi) {
                    parentLi.classList.add('active');
                }
            }
        });
    });
});

$(document).ready(function() {
    console.log('Document ready!');
    
    // Populate course & section dropdown on page load
    populateCourseSectionDropdown();
    
    // Ensure modal is properly initialized
    $('#scheduleModal').on('shown.bs.modal', function () {
        console.log('Modal shown');
        
        // Force focus on first input
        $('#modal_subject').focus();
        
        // Ensure all inputs are enabled and interactive
        $('#scheduleModal input, #scheduleModal select, #scheduleModal textarea').prop('disabled', false);
        
        // Force enable time inputs specifically
        $('#modal_start_time, #modal_end_time').prop('disabled', false).attr('readonly', false);
        
        // Remove any potential overlays
        $('.modal-backdrop').css('pointer-events', 'none');
        
        // Ensure modal is on top
        $('#scheduleModal').css('z-index', '1070');
    });
    
    // Fixed weekly schedule - no navigation needed
    
    // Toggle views
    $('#calendarViewBtn').on('click', function() {
        $('#calendarViewSection').show();
        $('#tableViewSection').hide();
        $(this).addClass('active');
        $('#tableViewBtn').removeClass('active');
    });
    
    $('#tableViewBtn').on('click', function() {
        $('#calendarViewSection').hide();
        $('#tableViewSection').show();
        $(this).addClass('active');
        $('#calendarViewBtn').removeClass('active');
    });

    // Initialize DataTable
    if ($.fn.DataTable) {
        $('#scheduleTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[2, 'asc'], [3, 'asc']] // Sort by Day, then Time
        });
        console.log('DataTable initialized');
    } else {
        console.error('DataTable not loaded');
    }

    // Export to Excel
    $('#exportExcel').on('click', function() {
        exportToExcel();
    });
    
    // Export to Word (PDF)
    $('#exportWord').on('click', function() {
        exportToPDF();
    });
    
    // Print Schedule
    $('#printSchedule').on('click', function() {
        printSchedule();
    });

    // Add New Schedule button
    $('#addScheduleBtn').on('click', function() {
        openScheduleModal({});
    });

    // Make day columns clickable
    $('.day-column').on('click', function(e) {
        // Prevent click if clicking on a button inside the column
        if ($(e.target).closest('button').length) return;
        var day = $(this).data('day');
        if (day) openScheduleModal({day: day});
    });

    // Edit button click
    $(document).on('click', '.edit-btn', function() {
        var scheduleId = $(this).data('id');
        openScheduleModal({id: scheduleId});
    });

    // Delete button click
    $(document).on('click', '.delete-btn', function() {
        var scheduleId = $(this).data('id');
        if (confirm('Are you sure you want to delete this schedule?')) {
            deleteSchedule(scheduleId);
        }
    });



    // Form submission
    $('#scheduleForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', $('#modal_schedule_id').val() ? 'update' : 'add');
        
        $.ajax({
            url: 'api/manage-teacher-schedule.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Schedule save response:', response);
                console.log('Response type:', typeof response);
                console.log('Response length:', response.length);
                
                try {
                    var data = JSON.parse(response);
                    if (data.success) {
                        $('#scheduleModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (e) {
                    console.error('Error parsing schedule response:', e);
                    console.error('Raw response:', response);
                    alert('Error processing response: ' + response.substring(0, 200));
                }
            },
            error: function(xhr, status, error) {
                console.error('Schedule save error:', status, error);
                console.error('Response text:', xhr.responseText);
                alert('Error saving schedule: ' + xhr.responseText.substring(0, 200));
            }
        });
    });

    // Calendar UI removed

    // Calendar date click functionality


    // Holiday/event UI removed

    // Event modal UI removed

    // Event submission removed
    
    // Calendar date handler removed
    
    // Holiday date handler removed
    
    // Holiday hover effects removed
    
    // Holiday creation UI removed
});

function openScheduleModal(data) {
    // Populate course & section dropdown first
    populateCourseSectionDropdown();
    
    if (data.id) {
        // Edit mode - fetch schedule data
        $.get('api/get-teacher-schedule.php?id=' + data.id)
            .done(function(response) {
                try {
                    var schedule = JSON.parse(response).data;
                    $('#modal_schedule_id').val(schedule.id);
                    $('#modal_subject').val(schedule.subject);
                    $('#modal_course_section').val(schedule.section);
                    $('#modal_day_of_week').val(schedule.day_of_week);
                    $('#modal_start_time').val(convertTo24Hour(schedule.start_time));
                    $('#modal_end_time').val(convertTo24Hour(schedule.end_time));
                    $('#modal_room').val(schedule.room || '');
                    $('#modalTitle').text('Edit Schedule');
                } catch (e) {
                    alert('Error loading schedule data');
                }
            });
    } else {
        // Add mode - clear form
        $('#scheduleForm')[0].reset();
        $('#modal_schedule_id').val('');
        if (data.day) {
            $('#modal_day_of_week').val(data.day);
        }
        $('#modalTitle').text('Add New Schedule');
    }
    $('#scheduleModal').modal('show');
}

function convertTo24Hour(time12h) {
    if (time12h.includes('PM') || time12h.includes('AM')) {
        const [time, modifier] = time12h.split(' ');
        let [hours, minutes] = time.split(':');
        hours = parseInt(hours);
        
        if (modifier === 'PM' && hours !== 12) {
            hours += 12;
        } else if (modifier === 'AM' && hours === 12) {
            hours = 0;
        }
        
        return `${hours.toString().padStart(2, '0')}:${minutes}`;
    }
    return time12h;
}

function getDayOfWeek(dateString) {
    var date = new Date(dateString);
    var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return days[date.getDay()];
}

function showHolidayDetails(date) {
    console.log('Fetching holiday details for date:', date);
    $.ajax({
        url: 'api/manage-holidays.php?month=<?= $current_month ?>',
        type: 'GET',
        success: function(response) {
            console.log('Holiday API response:', response);
            try {
                var data = JSON.parse(response);
                if (data.success) {
                    var holiday = data.holidays.find(function(h) {
                        return h.holiday_date === date;
                    });
                    
                    if (holiday) {
                        var dateObj = new Date(date);
                        var formattedDate = dateObj.toLocaleDateString('en-US', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                        
                        $('#holidayDateDisplay').text(formattedDate);
                        $('#holidayNameDisplay').text(holiday.holiday_name);
                        $('#holidayDetailsModal').modal('show');
                    } else {
                        alert('Holiday details not found for ' + date);
                    }
                } else {
                    alert('Error loading holiday details: ' + data.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                alert('Error processing response');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            alert('Error loading holiday details');
        }
    });
}

// Function to populate course & section dropdown from masterlist
function populateCourseSectionDropdown() {
    console.log('populateCourseSectionDropdown called');
    $.ajax({
        url: 'api/get-teacher-course-sections.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('API Response:', response);
            if (response.success && response.course_sections) {
                var dropdown = $('#modal_course_section');
                // Clear existing options except the first one
                dropdown.find('option:not(:first)').remove();
                
                // Add new options from the API response
                response.course_sections.forEach(function(courseSection) {
                    var option = $('<option></option>')
                        .val(courseSection)
                        .text(courseSection);
                    dropdown.append(option);
                });
                
                console.log('Course & section dropdown populated with', response.course_sections.length, 'items');
            } else {
                console.error('Failed to populate dropdown:', response.error || 'Unknown error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching course sections:', error);
            console.error('Response:', xhr.responseText);
        }
    });
}

function deleteSchedule(scheduleId) {
    console.log('Attempting to delete schedule ID:', scheduleId);
    $.ajax({
        url: 'api/delete-teacher-schedule.php',
        type: 'POST',
        data: JSON.stringify({id: scheduleId}),
        contentType: 'application/json',
        success: function(response) {
            console.log('Raw response:', response);
            try {
                var data = JSON.parse(response);
                console.log('Parsed response:', data);
                if (data.success) {
                    alert('Schedule deleted successfully');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                alert('Error processing response: ' + response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            alert('Error deleting schedule: ' + error);
        }
    });
}

// Export to Excel function
function exportToExcel() {
    const table = document.getElementById('scheduleTable');
    const ws = XLSX.utils.table_to_sheet(table);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Schedule');
    
    // Generate timestamp for filename
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `teacher_schedule_${timestamp}.xlsx`;
    
    XLSX.writeFile(wb, filename);
}

// Export to PDF function
function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Add title
    doc.setFontSize(16);
    doc.text('Teacher Schedule', 14, 15);
    
    // Add timestamp
    doc.setFontSize(10);
    const timestamp = new Date().toLocaleString();
    doc.text(`Generated on: ${timestamp}`, 14, 22);

    // Create the PDF using autotable plugin
    doc.autoTable({
        html: '#scheduleTable',
        startY: 25,
        styles: { fontSize: 8 },
        headStyles: { 
            fillColor: [9, 135, 68],
            textColor: [255, 255, 255],
            fontSize: 8,
            fontStyle: 'bold'
        }
    });

    // Generate timestamp for filename
    const fileTimestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `teacher_schedule_${fileTimestamp}.pdf`;
    
    doc.save(filename);
}

// Print function
function printSchedule() {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Teacher Schedule</title>
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
                    <h2>Teacher Schedule</h2>
                    <div class="timestamp">Generated on: ${new Date().toLocaleString()}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Section</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Room</th>
                            <th>Schedule Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${Array.from(document.querySelectorAll('#scheduleTable tbody tr')).map(row => `
                            <tr>
                                <td>${row.cells[0].textContent}</td>
                                <td>${row.cells[1].textContent}</td>
                                <td>${row.cells[2].textContent}</td>
                                <td>${row.cells[3].textContent}</td>
                                <td>${row.cells[4].textContent}</td>
                                <td>${row.cells[5].textContent}</td>
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

// Helper function to get day of week from date
function getDayOfWeek(dateString) {
    const date = new Date(dateString);
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return days[date.getDay()];
}

// Function to show event details
function showEventDetails(date, eventName, eventType) {
    $('#eventDateDisplay').text(new Date(date).toLocaleDateString());
    $('#eventNameDisplay').text(eventName);
    $('#eventTypeDisplay').text('Type: ' + (eventType || 'Holiday'));
    
    // Set different icons for national holidays
    if (eventType === 'National Holiday') {
        $('#eventIcon').removeClass('fas fa-calendar-day').addClass('fas fa-flag');
        $('#eventIcon').removeClass('text-danger').addClass('text-danger');
    } else {
        $('#eventIcon').removeClass('fas fa-flag').addClass('fas fa-calendar-day');
        $('#eventIcon').removeClass('text-danger').addClass('text-danger');
    }
    
    $('#eventDetailsModal').modal('show');
}

// Function to show holiday details
function showHolidayDetails(date, holidayName, isNational) {
    var dateObj = new Date(date);
    var formattedDate = dateObj.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    $('#eventDateDisplay').text(formattedDate);
    $('#eventNameDisplay').text(holidayName);
    $('#eventTypeDisplay').text('Type: ' + (isNational ? '🇵🇭 National Holiday' : '🎉 School Holiday'));
    
    // Set appropriate icon
    if (isNational) {
        $('#eventIcon').removeClass('fas fa-calendar-day').addClass('fas fa-flag');
        $('#eventIcon').removeClass('text-danger').addClass('text-danger');
    } else {
        $('#eventIcon').removeClass('fas fa-flag').addClass('fas fa-calendar-day');
        $('#eventIcon').removeClass('text-danger').addClass('text-warning');
    }
    
    $('#eventDetailsModal').modal('show');
}



// Function to delete schedule
function deleteSchedule(scheduleId) {
    $.ajax({
        url: 'api/delete-teacher-schedule.php',
        type: 'POST',
        data: { id: scheduleId },
        success: function(response) {
            try {
                var data = JSON.parse(response);
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) {
                alert('Error processing response');
            }
        },
        error: function() {
            alert('Error deleting schedule');
        }
    });
}
</script>
</body>
</html> 