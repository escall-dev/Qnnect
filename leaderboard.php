<?php
require_once 'includes/asset_helper.php';
require_once 'includes/session_config.php';
require_once 'includes/data_isolation_helper.php';
include('./conn/db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: admin/login.php");
    exit;
}

// Get user context for data isolation
$context = getCurrentUserContext();

// Get the top students with most attendance records
try {
    $query = "
        SELECT s.student_name, s.course_section, s.tbl_student_id, COUNT(a.tbl_attendance_id) as attendance_count 
        FROM tbl_student s
        LEFT JOIN tbl_attendance a ON s.tbl_student_id = a.tbl_student_id
        WHERE s.school_id = ? 
        " . ($context['user_id'] ? "AND (s.user_id = ? OR s.user_id IS NULL)" : "") . "
        AND (a.school_id = ? OR a.school_id IS NULL)
        " . ($context['user_id'] ? "AND (a.user_id = ? OR a.user_id IS NULL)" : "") . "
        GROUP BY s.tbl_student_id
        ORDER BY attendance_count DESC
        LIMIT 50
    ";
    
    $stmt = $conn_qr->prepare($query);
    
    if ($context['user_id']) {
        $stmt->bind_param("iiii", $context['school_id'], $context['user_id'], $context['school_id'], $context['user_id']);
    } else {
        $stmt->bind_param("ii", $context['school_id'], $context['school_id']);
    }
    
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

// Get total attendance days this semester
$query = "
    SELECT COUNT(DISTINCT DATE(time_in)) as total_days 
    FROM tbl_attendance 
    WHERE time_in >= CURDATE() - INTERVAL 6 MONTH
    AND school_id = ?
    " . ($context['user_id'] ? "AND (user_id = ? OR user_id IS NULL)" : "");
$stmt = $conn_qr->prepare($query);

if ($context['user_id']) {
    $stmt->bind_param("ii", $context['school_id'], $context['user_id']);
} else {
    $stmt->bind_param("i", $context['school_id']);
}

$stmt->execute();
$total_days_result = $stmt->get_result();
$total_days = $total_days_result->fetch_assoc()['total_days'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Leaderboard - QR Code Attendance System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/dataTables.bootstrap4.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/buttons.bootstrap4.min.css'); ?>">
    
    <style>
        body {
            background-color: #808080; /* Match the gray background of analytics.php */
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Prevent horizontal scrolling */
        }
        
        /* FORCE CENTERED PAGINATION - DO NOT CHANGE THIS */
        div.dataTables_wrapper div.dataTables_paginate {
            margin: 15px 0 !important;
            white-space: nowrap !important;
            text-align: center !important;
            display: flex !important;
            justify-content: center !important;
            float: none !important;
            width: 100% !important;
        }
        
        /* FORCE CENTERED INFO TEXT - DO NOT CHANGE THIS */
        div.dataTables_wrapper div.dataTables_info {
            padding-top: 10px !important;
            white-space: nowrap !important;
            text-align: center !important;
            float: none !important;
            width: 100% !important;
        }
        
        /* FORCE pagination container to be full width and centered */
        .dataTables_wrapper .row:last-child {
            display: flex !important;
            justify-content: center !important;
            width: 100% !important;
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
        
        /* Scrollbar styling for webkit browsers */
        .leaderboard-content::-webkit-scrollbar {
            width: 8px;
        }

        .leaderboard-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .leaderboard-content::-webkit-scrollbar-thumb {
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

        /* Leaderboard table styles */
        .leaderboard-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .leaderboard-table th {
            background-color: #098744;
            color: white;
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
            margin-right: 10px !important;
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
            margin-right: 5px;
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

        /* Custom pagination styling to match image */
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .page-item .page-link {
            color: #212529;
            background-color: #fff;
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
        }
        
        .page-item.active .page-link {
            background-color: #098744;
            border-color: #098744;
            color: white;
        }
        
        .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #fff;
            border-color: #dee2e6;
        }
        
        .page-item:not(.active) .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #098744;
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
                        <h4><i class="fas fa-trophy"></i> Attendance Leaderboard</h4>
                    </div>

                    <div class="content-wrapper">
                        <!-- Stats Cards -->
                        <div class="stats-container">
                            <div class="stat-card">
                                <h5><i class="fas fa-calendar-check"></i> Total Attendance Days</h5>
                                <div class="value"><?php echo $total_days; ?></div>
                                <small>This semester</small>
                            </div>
                            <div class="stat-card">
                                <h5><i class="fas fa-users"></i> Total Students</h5>
                                <div class="value"><?php echo count($students); ?></div>
                                <small>Ranked by attendance</small>
                            </div>
                            <div class="stat-card">
                                <h5><i class="fas fa-star"></i> Overall Top Attendance Student</h5>
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
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="courseFilter"><i class="fas fa-filter"></i> Filter by Course:</label>
                                        <select class="form-control" id="courseFilter">
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
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="searchFilter"><i class="fas fa-search"></i> Search Student:</label>
                                        <input type="text" class="form-control" id="searchFilter" placeholder="Enter student name...">
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button class="btn btn-primary" style="background-color: #098744; border-color: #098744;" onclick="applyFilters()">
                                        <i class="fas fa-filter"></i> Apply Filters
                                    </button>
                                    &nbsp;
                                    <button class="btn btn-secondary" onclick="resetFilters()">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                    <div id="export-buttons" class="ml-2"></div>
                                </div>
                            </div>
                        </div>

                       
                        <!-- Leaderboard Table -->
                        <div class="table-responsive">
                            <table class="leaderboard-table" id="leaderboardTable">
                                <thead>
                                    <tr>
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
                                            <td colspan="6" class="text-center">No attendance records found</td>
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

    <script>
        // Initialize DataTable with export buttons
        $(document).ready(function() {
            // Initialize DataTable with minimal features
            const table = $('#leaderboardTable').DataTable({
                paging: true,
                pageLength: 8,
                ordering: true,
                info: true,
                searching: false,
                responsive: true,
                // Use custom Bootstrap pagination
                dom: "<'row'<'col-sm-12 mb-3'B>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 d-flex justify-content-center'i>>" +
                     "<'row'<'col-sm-12 d-flex justify-content-center'p>>",
                language: {
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: '',
                        previous: '',
                        next: '',
                        last: ''
                    }
                },
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
                    styleDataTablePagination();
                },
                initComplete: function() {
                    styleDataTablePagination();
                }
            });
            
            // Additional styling for buttons
            $('.dt-buttons').css({
                'text-align': 'center',
                'margin-bottom': '15px',
                'display': 'flex',
                'justify-content': 'center'
            });
            
            // Make sure buttons use the green color
            $('.dt-buttons .btn-success').css({
                'background-color': '#098744',
                'border-color': '#098744',
                'margin-right': '5px'
            });
            
         
        
            
            // Handle Print button directly
            $(document).on('click', '.buttons-print', function() {
                // Create a printable version of the table
                const printContent = document.createElement('div');
                printContent.innerHTML = '<h1 style="text-align:center; margin-bottom: 20px;">Attendance Leaderboard</h1>';
                
                // Get filtered table data
                const tableClone = $('#leaderboardTable').clone();
                tableClone.find('tr').each(function() {
                    // Show only visible rows
                    if ($(this).is(':hidden') && !$(this).is('thead tr')) {
                        $(this).remove();
                    }
                });
                
                // Add print styling
                const style = document.createElement('style');
                style.innerHTML = `
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
                    th { background-color: #098744; color: white; }
                    .medal { display: inline-block; width: 30px; height: 30px; line-height: 30px; 
                             text-align: center; border-radius: 50%; color: white; font-weight: bold; }
                    .gold { background: linear-gradient(45deg, #FFD700, #FFA500); }
                    .silver { background: linear-gradient(45deg, #C0C0C0, #A9A9A9); }
                    .bronze { background: linear-gradient(45deg, #CD7F32, #A0522D); }
                    .normal { background: #098744; }
                    .progress { height: 20px; border-radius: 10px; background-color: #e9ecef; }
                    .progress-bar { background-color: #098744; }
                    @media print {
                        body { font-size: 12pt; }
                        h1 { font-size: 18pt; }
                        table { page-break-inside: auto; }
                        tr { page-break-inside: avoid; page-break-after: auto; }
                    }
                `;
                
                printContent.appendChild(style);
                printContent.appendChild(tableClone[0]);
                
                // Create and append filters info if filters are applied
                const course = $('#courseFilter').val();
                const search = $('#searchFilter').val();
                
                if (course || search) {
                    const filtersInfo = document.createElement('div');
                    filtersInfo.style.marginBottom = '20px';
                    
                    let filterText = '<strong>Filters applied:</strong> ';
                    
                    if (course) {
                        filterText += 'Course: ' + course + ' ';
                    }
                    
                    if (search) {
                        filterText += 'Search: ' + search + ' ';
                    }
                    
                    filtersInfo.innerHTML = filterText;
                    printContent.insertBefore(filtersInfo, printContent.firstChild.nextSibling);
                }
                
                // Create a new window for printing
                const printWindow = window.open('', '_blank');
                printWindow.document.open();
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Attendance Leaderboard - Print</title>
                    </head>
                    <body>
                        ${printContent.innerHTML}
                    </body>
                    </html>
                `);
                printWindow.document.close();
                
                // Print the window
                printWindow.onload = function() {
                    printWindow.print();
                    printWindow.onafterprint = function() {
                        printWindow.close();
                    };
                };
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

        // Update pagination style to match the image
        function styleDataTablePagination() {
            // Clear any existing pagination
            $('.dataTables_wrapper .dataTables_paginate').empty();
            
            // Get pagination info from DataTable
            const table = $('.dataTable').DataTable();
            const info = table.page.info();
            const currentPage = info.page + 1; // DataTables is 0-indexed
            const totalPages = info.pages;
            
            // Create custom pagination
            let paginationHtml = `
            <ul class="pagination">
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="first">First</a>
                </li>
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="previous">Previous</a>
                </li>
            `;
            
            // Add page numbers
            const maxVisiblePages = 3;
            const startPage = Math.max(1, Math.min(currentPage - Math.floor(maxVisiblePages / 2), totalPages - maxVisiblePages + 1));
            const endPage = Math.min(startPage + maxVisiblePages - 1, totalPages);
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i-1}">${i}</a>
                    </li>
                `;
            }
            
            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="next">Next</a>
                </li>
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="last">Last</a>
                </li>
            </ul>
            `;
            
            // Insert the custom pagination
            $('.dataTables_wrapper .dataTables_paginate').html(paginationHtml);
            
            // Add event listeners
            $('.dataTables_wrapper .dataTables_paginate .page-link').on('click', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                
                if (page === 'first') {
                    table.page('first').draw('page');
                } else if (page === 'previous') {
                    table.page('previous').draw('page');
                } else if (page === 'next') {
                    table.page('next').draw('page');
                } else if (page === 'last') {
                    table.page('last').draw('page');
                } else {
                    table.page(page).draw('page');
                }
            });
        }
    </script>
</body>
</html> 