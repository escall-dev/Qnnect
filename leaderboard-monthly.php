<?php
require_once 'includes/asset_helper.php';
require_once 'includes/session_config.php';
include('./conn/db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: admin/login.php");
    exit;
}

// Get the current month or selected month
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Month names for dropdown
$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Get user's school_id and user_id from session
$school_id = $_SESSION['school_id'] ?? 1;
$user_id = $_SESSION['user_id'] ?? 1;

// Get the top students with most attendance records for the selected month (filtered by school and user)
try {
    $query = "
        SELECT s.student_name, s.course_section, s.tbl_student_id, COUNT(a.tbl_attendance_id) as attendance_count 
        FROM tbl_student s
        LEFT JOIN tbl_attendance a ON s.tbl_student_id = a.tbl_student_id 
            AND a.school_id = ? AND a.user_id = ?
        WHERE s.school_id = ? AND s.user_id = ? AND MONTH(a.time_in) = ? AND YEAR(a.time_in) = ?
        GROUP BY s.tbl_student_id
        ORDER BY attendance_count DESC
        LIMIT 50
    ";
    
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("iiiiii", $school_id, $user_id, $school_id, $user_id, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    $rank = 1;
    
    while ($row = $result->fetch_assoc()) {
        $row['rank'] = $rank++;
        $students[] = $row;
    }
} catch (Exception $e) {
    $error = "Error fetching leaderboard data: " . $e->getMessage();
}

// Get total attendance days for this month (filtered by school and user)
$query = "
    SELECT COUNT(DISTINCT DATE(time_in)) as total_days 
    FROM tbl_attendance 
    WHERE MONTH(time_in) = ? AND YEAR(time_in) = ? 
        AND school_id = ? AND user_id = ?
";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("iiii", $month, $year, $school_id, $user_id);
$stmt->execute();
$total_days_result = $stmt->get_result();
$total_days = $total_days_result->fetch_assoc()['total_days'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Attendance Leaderboard - QR Code Attendance System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/dataTables.bootstrap4.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/buttons.bootstrap4.min.css'); ?>">
    <link rel="stylesheet" href="./styles/pagination.css">
    
    <style>
        /* Body styles */
        body {
            background-color: #808080;
            overflow-x: hidden;
        }
        
        /* Main content styles */
        .main {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease;
            width: calc(100% - 260px);
        }

        /* When sidebar is closed */
        .sidebar.close ~ .main {
            margin-left: 78px;
            width: calc(100% - 78px);
        }

        /* Leaderboard container styles - Outer container */
        .leaderboard-outer-container {
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

        /* Leaderboard content styles - Inner container */
        .leaderboard-container {
            background-color: white;
            border-radius: 20px;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        .leaderboard-content {
            height: 100%;
            width: 100%;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #098744 transparent;
            max-height: calc(100vh - 110px);
        }
        
        /* Custom scrollbar */
        .leaderboard-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .leaderboard-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .leaderboard-content::-webkit-scrollbar-thumb {
            background: #098744;
            border-radius: 10px;
        }
        
        .leaderboard-content::-webkit-scrollbar-thumb:hover {
            background: #076633;
        }

        /* Title styles */
        .title {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            margin-bottom: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            background: white;
            z-index: 1000;
            height: 60px;
            border-radius: 20px 20px 0 0;
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

        /* Leaderboard table styles */
        .leaderboard-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .leaderboard-table th {
            background-color: #098744 !important;
            color: white !important;
            padding: 12px;
            text-align: center;
            font-weight: 600;
        }

        .leaderboard-table th:first-child {
            border-top-left-radius: 10px;
        }

        .leaderboard-table th:last-child {
            border-top-right-radius: 10px;
        }

        .leaderboard-table td {
            padding: 12px;
            text-align: center;
        }

        .leaderboard-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .leaderboard-table tr:hover {
            background-color: #e9f7ef;
        }

        /* Medal styles */
        .medal {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            color: white;
            font-weight: bold;
        }

        .gold {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .silver {
            background: linear-gradient(45deg, #C0C0C0, #A9A9A9);
            box-shadow: 0 0 10px rgba(192, 192, 192, 0.5);
        }

        .bronze {
            background: linear-gradient(45deg, #CD7F32, #A0522D);
            box-shadow: 0 0 10px rgba(205, 127, 50, 0.5);
        }

        .normal {
            background: #098744;
        }

        /* Progress bar styles */
        .progress {
            height: 20px;
            border-radius: 10px;
        }

        .progress-bar {
            background-color: #098744;
        }

        /* Stats card styles */
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            flex: 1;
            min-width: 200px;
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card h5 {
            color: #098744;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
        }

        /* Month selector styles */
        .month-selector {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .month-selector .form-group {
            margin-bottom: 0;
            margin-right: 15px;
        }

        .month-selector .btn-primary {
            background-color: #098744;
            border-color: #098744;
        }

        .month-selector .btn-primary:hover {
            background-color: #076633;
            border-color: #076633;
        }

        /* Filter styles */
        .filter-container {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .main {
                margin-left: 0 !important;
                width: 100% !important;
            }

            .leaderboard-outer-container {
                margin: 10px;
                width: calc(100% - 20px);
                padding: 15px;
            }

            .stats-container {
                flex-direction: column;
            }
        }

         /* DataTables buttons styling */
         .dt-buttons {
            display: inline-flex !important;
            margin-bottom: 20px !important;
            margin-top: 15px !important;
        }
        
        .dt-button {
            background-color: #098744 !important;
            border-color: #098744 !important;
            color: white !important;
            border-radius: 4px !important;
            padding: 6px 12px !important;
            margin-right: 15px !important;
            border: none !important;
            transition: all 0.3s ease;
        }
        
        .dt-button:hover {
            background-color: #076a34 !important;
            border-color: #076a34 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .dt-button.buttons-copy:before {
            content: "\f0c5";
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: px;
        }
        
        .dt-button.buttons-excel:before {
            content: "\f1c3";
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 5px;
        }
        
        .dt-button.buttons-pdf:before {
            content: "\f1c1";
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 5px;
        }
        
        .dt-button.buttons-print:before {
            content: "\f02f";
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 5px;
        }

        /* Force table header colors */
        #leaderboardTable thead tr,
        #leaderboardTable thead th,
        .leaderboard-table thead tr,
        .leaderboard-table thead th,
        table.dataTable thead tr,
        table.dataTable thead th {
            background-color: #098744 !important;
            color: white !important;
        }
        
        /* Custom pagination styling */
        .pagination {
            display: flex !important;
            justify-content: center !important;
            margin: 20px auto !important;
            width: 100% !important;
        }
        
        .pagination .page-item .page-link {
            color: #098744;
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
            margin: 0 2px;
            min-width: 40px;
            text-align: center;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #098744;
            border-color: #098744;
            color: white;
            z-index: 1;
        }
        
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
        }
        
        .pagination .page-item:not(.active) .page-link:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #098744;
        }
        
        .dataTables_wrapper .dataTables_paginate,
        .dataTables_wrapper .dataTables_info {
            float: none !important;
            text-align: center !important;
            width: 100% !important;
            display: flex !important;
            justify-content: center !important;
            padding: 10px 0 !important;
        }

        /* Force centered pagination */
        #leaderboardTable_wrapper .dataTables_paginate {
            display: flex !important;
            justify-content: center !important;
            width: 100% !important;
            float: none !important;
            text-align: center !important;
            margin: 20px auto !important;
        }
        
        /* Fix for dataTables_info centering */
        #leaderboardTable_wrapper .dataTables_info {
            display: none !important;
        }
    </style>
</head>
<body>
    <!-- Custom Alert Box -->
    <div id="customAlert" class="custom-alert"></div>

    <?php include('./components/sidebar-nav.php'); ?>

    <div class="main" id="main">
        <div class="leaderboard-outer-container">
            <div class="leaderboard-container">
                <div class="leaderboard-content">
                    <div class="title">
                        <h4><i class="fas fa-trophy"></i> Monthly Attendance Leaderboard</h4>
                    </div>

                    <div class="content-wrapper">
                        <!-- Month Selector -->
                        <div class="month-selector">
                            <form method="get" class="d-flex flex-wrap align-items-center justify-content-center w-100">
                                <div class="form-group">
                                    <label for="month"><i class="fas fa-calendar-alt"></i> Month:</label>
                                    <select class="form-control" id="month" name="month">
                                        <?php foreach($monthNames as $num => $name): ?>
                                            <option value="<?php echo $num; ?>" <?php echo $num == $month ? 'selected' : ''; ?>>
                                                <?php echo $name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="year"><i class="fas fa-calendar-day"></i> Year:</label>
                                    <select class="form-control" id="year" name="year">
                                        <?php for($y = date('Y'); $y >= date('Y')-3; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="form-control btn btn-primary">
                                        <i class="fas fa-search"></i> View
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Stats Cards -->
                        <div class="stats-container">
                            <div class="stat-card">
                                <h5><i class="fas fa-calendar-check"></i> Total School Days</h5>
                                <div class="value"><?php echo $total_days; ?></div>
                                <small>In <?php echo $monthNames[$month] . ' ' . $year; ?></small>
                            </div>
                            <div class="stat-card">
                                <h5><i class="fas fa-users"></i> Total Students</h5>
                                <div class="value"><?php echo count($students); ?></div>
                                <small>With attendance this month</small>
                            </div>
                            <div class="stat-card">
                                <h5><i class="fas fa-crown"></i> Top Student of the month</h5>
                                <div class="value">
                                    <?php 
                                    if(!empty($students)) {
                                        echo '<span style="font-size: 16px;">' . htmlspecialchars($students[0]['student_name']) . '</span>';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                                <small>
                                    <?php 
                                    if(!empty($students)) {
                                        echo $students[0]['attendance_count'] . ' attendance days';
                                    } else {
                                        echo 'No attendance records';
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="filter-container">
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex flex-wrap align-items-center">
                                        <div class="form-group mr-2 mb-0" style="min-width: 180px;">
                                            <select class="form-control form-control-sm" id="courseFilter">
                                                <option value="">All Courses</option>
                                                <?php
                                                $courses = [];
                                                foreach($students as $student) {
                                                    if(!in_array($student['course_section'], $courses)) {
                                                        $courses[] = $student['course_section'];
                                                        echo "<option value='{$student['course_section']}'>{$student['course_section']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group mr-2 mb-0" style="min-width: 220px;">
                                            <input type="text" class="form-control form-control-sm" id="searchFilter" placeholder="Enter name...">
                                        </div>
                                        
                                        <button class="btn btn-sm btn-success mr-1" onclick="applyFilters()">
                                            <i class="fas fa-filter"></i> Apply
                                        </button>
                                        
                                        <button class="btn btn-sm btn-secondary mr-1" onclick="resetFilters()">
                                            <i class="fas fa-redo"></i> Reset
                                        </button>
                                        
                                      
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export Buttons (Hidden) -->
                        <div class="export-buttons mb-3" style="display:none;">
                            <form method="POST" class="d-inline" action="#" onsubmit="return false;"> <!-- export endpoint not available -->
                                <input type="hidden" name="month" value="<?php echo $month; ?>">
                                <input type="hidden" name="year" value="<?php echo $year; ?>">
                                <input type="hidden" name="course" value="<?php echo isset($_GET['course']) ? htmlspecialchars($_GET['course']) : ''; ?>">
                                <input type="hidden" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                
                                <button type="submit" name="format" value="print" class="btn btn-success">
                                    <i class="fas fa-print"></i> Print
                                </button>
                                
                                <button type="submit" name="format" value="csv" class="btn btn-success">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                                <button type="submit" name="format" value="excel" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                                <button type="submit" name="format" value="pdf" class="btn btn-success">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </button>
                            </form>
                        </div>

                        <!-- Leaderboard Table -->
                        <div class="table-responsive">
                            <table class="leaderboard-table" id="leaderboardTable">
                                <thead>
                                    <tr style="background-color: #098744; color: white;">
                                        <th>Rank</th>
                                        <th>Student Name</th>
                                        <th>Course & Section</th>
                                        <th>Attendance Count</th>
                                        <th>Attendance Rate</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($students)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No attendance records found for <?php echo $monthNames[$month] . ' ' . $year; ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($students as $student): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    switch($student['rank']) {
                                                        case 1:
                                                            echo '<span class="medal gold">1</span>';
                                                            break;
                                                        case 2:
                                                            echo '<span class="medal silver">2</span>';
                                                            break;
                                                        case 3:
                                                            echo '<span class="medal bronze">3</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="medal normal">' . $student['rank'] . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['course_section']); ?></td>
                                                <td><?php echo $student['attendance_count']; ?></td>
                                                <td>
                                                    <?php 
                                                    $rate = $total_days > 0 ? round(($student['attendance_count'] / $total_days) * 100, 1) : 0;
                                                    echo $rate . '%';
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $rate; ?>%" 
                                                            aria-valuenow="<?php echo $rate; ?>" aria-valuemin="0" aria-valuemax="100">
                                                            <?php echo $rate; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="./functions/pagination.js"></script>

    <script>
        // Initialize DataTable and month selection
        $(document).ready(function() {
            // Initialize DataTable with standardized pagination
            const table = initializeStandardDataTable('#leaderboardTable', {
                paging: true,
                pageLength: 10,
                ordering: true,
                info: true,
                searching: false,
                lengthChange: false,
                // Use Bootstrap pagination styling
                pagingType: 'simple_numbers',
                dom: '<"row"<"col-md-6"B><"col-md-6"f>>rtip',
                buttons: [      
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn-success',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn-success',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Print',
                        className: 'btn-success',
                        exportOptions: {
                            columns: ':visible'
                        }
                    }
                ],
                drawCallback: function() {
                    $('.dataTables_paginate .paginate_button').addClass('btn btn-sm');
                    $('.dataTables_paginate .paginate_button.current').addClass('active');
                    
                    // Force pagination to be centered
                    $('.dataTables_paginate').css({
                        'display': 'flex',
                        'justify-content': 'center',
                        'width': '100%',
                        'float': 'none',
                        'text-align': 'center',
                        'margin': '20px auto'
                    });
                }
            });
            
            // Place buttons in the container beside reset button
            $('.dt-buttons').addClass('mt-3 mb-3 text-center');
            
            // Make sure buttons use the green color
            $('.dt-buttons .btn-success').css({
                'background-color': '#098744',
                'border-color': '#098744',
                    'margin-right': '5px'
            });
            
            // Add extra space between buttons
            $('.dt-button').css({
                'margin-right': '5px'
            });
            
            // Handle Print button click
            $('.print-btn').on('click', function() {
                // Create a temporary form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '#'; // Disabled: endpoint not implemented
                
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = 'print';
                
                const monthInput = document.createElement('input');
                monthInput.type = 'hidden';
                monthInput.name = 'month';
                monthInput.value = '<?php echo $month; ?>';
                
                const yearInput = document.createElement('input');
                yearInput.type = 'hidden';
                yearInput.name = 'year';
                yearInput.value = '<?php echo $year; ?>';
                
                const courseInput = document.createElement('input');
                courseInput.type = 'hidden';
                courseInput.name = 'course';
                courseInput.value = $('#courseFilter').val() || '';
                
                const searchInput = document.createElement('input');
                searchInput.type = 'hidden';
                searchInput.name = 'search';
                searchInput.value = $('#searchFilter').val() || '';
                
                form.appendChild(formatInput);
                form.appendChild(monthInput);
                form.appendChild(yearInput);
                form.appendChild(courseInput);
                form.appendChild(searchInput);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
            
            // Handle Excel export button click
            $('.export-excel-btn').on('click', function() {
                // Create a temporary form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '#'; // Disabled: endpoint not implemented
                
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = 'excel';
                
                const monthInput = document.createElement('input');
                monthInput.type = 'hidden';
                monthInput.name = 'month';
                monthInput.value = '<?php echo $month; ?>';
                
                const yearInput = document.createElement('input');
                yearInput.type = 'hidden';
                yearInput.name = 'year';
                yearInput.value = '<?php echo $year; ?>';
                
                const courseInput = document.createElement('input');
                courseInput.type = 'hidden';
                courseInput.name = 'course';
                courseInput.value = $('#courseFilter').val() || '';
                
                const searchInput = document.createElement('input');
                searchInput.type = 'hidden';
                searchInput.name = 'search';
                searchInput.value = $('#searchFilter').val() || '';
                
                form.appendChild(formatInput);
                form.appendChild(monthInput);
                form.appendChild(yearInput);
                form.appendChild(courseInput);
                form.appendChild(searchInput);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
            
            // Handle PDF export button click
            $('.export-pdf-btn').on('click', function() {
                // Create a temporary form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '#'; // Disabled: endpoint not implemented
                
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = 'pdf';
                
                const monthInput = document.createElement('input');
                monthInput.type = 'hidden';
                monthInput.name = 'month';
                monthInput.value = '<?php echo $month; ?>';
                
                const yearInput = document.createElement('input');
                yearInput.type = 'hidden';
                yearInput.name = 'year';
                yearInput.value = '<?php echo $year; ?>';
                
                const courseInput = document.createElement('input');
                courseInput.type = 'hidden';
                courseInput.name = 'course';
                courseInput.value = $('#courseFilter').val() || '';
                
                const searchInput = document.createElement('input');
                searchInput.type = 'hidden';
                searchInput.name = 'search';
                searchInput.value = $('#searchFilter').val() || '';
                
                form.appendChild(formatInput);
                form.appendChild(monthInput);
                form.appendChild(yearInput);
                form.appendChild(courseInput);
                form.appendChild(searchInput);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
        });
        
        // Filter functionality
        function applyFilters() {
            const courseValue = document.getElementById('courseFilter').value.toLowerCase();
            const searchValue = document.getElementById('searchFilter').value.toLowerCase();
            
            const table = document.getElementById('leaderboardTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const courseTd = rows[i].getElementsByTagName('td')[2];
                const nameTd = rows[i].getElementsByTagName('td')[1];
                
                if (courseTd && nameTd) {
                    const courseText = courseTd.textContent.toLowerCase();
                    const nameText = nameTd.textContent.toLowerCase();
                    
                    const courseMatch = courseValue === '' || courseText.includes(courseValue);
                    const nameMatch = searchValue === '' || nameText.includes(searchValue);
                    
                    if (courseMatch && nameMatch) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }
        
        function resetFilters() {
            document.getElementById('courseFilter').value = '';
            document.getElementById('searchFilter').value = '';
            
            const table = document.getElementById('leaderboardTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = '';
            }
        }
        
        // Sidebar functions
        document.addEventListener("DOMContentLoaded", function() {
            // #1 Fix for dropdown menus (arrows):
            // First remove any existing click handlers to avoid conflicts
            let arrows = document.querySelectorAll(".arrow");
            for (let i = 0; i < arrows.length; i++) {
                let oldElement = arrows[i];
                let newElement = oldElement.cloneNode(true);
                oldElement.parentNode.replaceChild(newElement, oldElement);
            }
            
            // Now add the click event handlers again
            arrows = document.querySelectorAll(".arrow");
            for (let i = 0; i < arrows.length; i++) {
                arrows[i].addEventListener("click", function(e) {
                    // Stop event from bubbling up
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle the showMenu class on the parent li element
                    let arrowParent = this.parentElement.parentElement;
                    console.log("Arrow clicked, toggling menu:", arrowParent);
                    arrowParent.classList.toggle("showMenu");
                });
            }
            
            // #2 Handle the sidebar toggle
            let sidebar = document.querySelector(".sidebar");
            
            // Add click handler to the menu button
            let menuButton = document.querySelector(".bx-menu");
            if (menuButton) {
                // Remove any existing click handlers to avoid conflicts
                let oldMenuBtn = menuButton;
                let newMenuBtn = oldMenuBtn.cloneNode(true);
                oldMenuBtn.parentNode.replaceChild(newMenuBtn, oldMenuBtn);
                menuButton = newMenuBtn;
                
                menuButton.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log("Menu button clicked, toggling sidebar");
                    sidebar.classList.toggle("close");
                });
            }
            
            // #3 Allow clicking on menu items themselves to toggle dropdown
            let menuItems = document.querySelectorAll(".iocn-link");
            for (let i = 0; i < menuItems.length; i++) {
                menuItems[i].addEventListener("click", function(e) {
                    // Only toggle if clicking the link itself or the arrow
                    let target = e.target;
                    let shouldToggle = 
                        target.classList.contains("arrow") || 
                        target.classList.contains("link_name") ||
                        target.parentElement.classList.contains("iocn-link");
                    
                    if (shouldToggle) {
                        let parentLi = this.parentElement;
                        console.log("Menu item clicked, toggling:", parentLi);
                        parentLi.classList.toggle("showMenu");
                    }
                });
            }
        });
    </script>
</body>
</html> 