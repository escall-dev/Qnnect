<?php
require_once 'includes/asset_helper.php';
require_once 'includes/session_config.php';
require_once './conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: admin/login.php");
    exit;
}

// Store the login database connection for the sidebar
$sidebarConn = $conn_login;

// Check QR database connection
if (!$conn_qr) {
    die("Connection failed: " . mysqli_connect_error());
}

// Debug connection
error_log("Attempting to connect to database with username: $dbUser and database: $qrDb");

// Get user's school_id and user_id from session
$school_id = $_SESSION['school_id'] ?? 1;
$user_id = $_SESSION['user_id'] ?? 1;

// Get verification logs
try {
    // Debug query execution
    error_log("Executing query for students filter");
    
    // Get distinct students for filter (filtered by school and user)
    $students_query = "SELECT DISTINCT student_name FROM tbl_face_verification_logs 
                       WHERE school_id = ? AND user_id = ? ORDER BY student_name";
    $stmt = $conn_qr->prepare($students_query);
    $stmt->bind_param("ii", $school_id, $user_id);
    $stmt->execute();
    $students_result = $stmt->get_result();
    
    if (!$students_result) {
        error_log("Students query failed: " . mysqli_error($conn_qr));
    }
    
    $students = [];
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row['student_name'];
    }
    
    error_log("Found " . count($students) . " unique students");
    
    // Debug logs query
    error_log("Executing query for all logs");
    
    // Get all logs (filtered by school and user)
    $logs_query = "SELECT * FROM tbl_face_verification_logs 
                    WHERE school_id = ? AND user_id = ? 
                    ORDER BY verification_time DESC";
    $stmt = $conn_qr->prepare($logs_query);
    $stmt->bind_param("ii", $school_id, $user_id);
    $stmt->execute();
    $logs_result = $stmt->get_result();
    
    if (!$logs_result) {
        error_log("Logs query failed: " . mysqli_error($conn_qr));
    }
    
    $logs = [];
    while ($row = mysqli_fetch_assoc($logs_result)) {
        $logs[] = $row;
    }
    
    error_log("Found " . count($logs) . " verification logs");
    
} catch(Exception $e) {
    error_log("Error in verification logs: " . $e->getMessage());
    $logs = [];
    $students = [];
}

// Test query (filtered by school and user)
$test_query = "SELECT COUNT(*) as count FROM tbl_face_verification_logs 
               WHERE school_id = ? AND user_id = ?";
$stmt = $conn_qr->prepare($test_query);
$stmt->bind_param("ii", $school_id, $user_id);
$stmt->execute();
$test_result = $stmt->get_result();
if ($test_result) {
    $row = mysqli_fetch_assoc($test_result);
    error_log("Number of records: " . $row['count']);
} else {
    error_log("Query failed: " . mysqli_error($conn_qr));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Verification Logs - QR Code Attendance System</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/masterlist.css">

    <!-- Data Table -->
    <link rel="stylesheet" href="<?php echo asset_url('css/jquery.dataTables.css'); ?>" />
    <link rel="stylesheet" href="<?php echo asset_url('css/buttons.dataTables.min.css'); ?>" />
    <link rel="stylesheet" href="./styles/pagination.css" />

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?php echo asset_url('css/all.min.css'); ?>">
    
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

        /* Add background styling */
        body {
            background-color: #808080; /* Match the gray background of analytics.php */
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Prevent horizontal scrolling */
        }

        /* Logs container styles */
        .logs-container {
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

        .main.active .logs-container {
            width: calc(100% - 40px);
            margin: 20px;
        }

        .logs-content {
            background-color: white;
            border-radius: 20px;
            padding: 20px;
            height: 100%;
            overflow-y: auto;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        /* Title styles */
        .title {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            margin-bottom: 20px;
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 10;
            border-radius: 20px 20px 0 0;
        }

        .title h4 {
            margin: 0;
            color: #098744;
        }

        /* Filter styles */
        .filters-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            position: sticky;
            top: 70px;
            z-index: 9;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        /* Status badge styles */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .status-success {
            background-color: #d4edda;
            color: #155724;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Export button styling for consistency */
        .btn-success {
            background-color: #098744 !important;
            border-color: #098744 !important;
            margin-right: 5px;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: #076a34 !important;
            border-color: #076a34 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        form .btn-success {
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
        }

        /* Table container */
        .table-responsive {
            margin-top: 20px;
        }

        /* DataTables buttons styling */
        .dt-buttons {
            display: inline-flex !important;
            margin-bottom: 0 !important;
            margin-top: 15px;
        }
        
        .dt-button {
            background-color: #098744 !important;
            border-color: #098744 !important;
            color: white !important;
            border-radius: 4px !important;
            padding: 6px 12px !important;
            margin-right: 5px !important;
            border: none !important;
            transition: all 0.3s ease;
        }
        
        .dt-button:hover {
            background-color: #076a34 !important;
            border-color: #076a34 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Responsive styles */
        @media (max-width: 1200px) {
            .main.active {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
        }

        @media (max-width: 992px) {
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
            
            .logs-container {
                margin: 15px;
                height: calc(100vh - 30px);
            }
            
            .filters-container {
                flex-direction: column;
                gap: 10px;
                top: 60px;
            }
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

    <?php 
    // Include sidebar with the correct connection
    include('./components/sidebar-nav.php'); 
    ?>
   


    <div class="main collapsed" id="main">
        <div class="logs-container">
            <div class="logs-content">
                <div class="title">
                    <h4><i class="fas fa-history"></i> Face Registration Logs</h4>
                </div>
                
                <!-- Filters -->
                <div class="filters-container">
                    <div class="d-flex flex-wrap align-items-center w-100">
                        <div class="form-group mr-2 mb-0">
                            <select class="form-control form-control-sm" id="studentFilter">
                                <option value="">All Students</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo htmlspecialchars($student); ?>">
                                        <?php echo htmlspecialchars($student); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mr-2 mb-0">
                            <select class="form-control form-control-sm" id="statusFilter">
                                <option value="">All Statuses</option>
                                <option value="Success">Success</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>
                        
                        <div class="form-group mr-2 mb-0">
                            <input type="date" class="form-control form-control-sm" id="dateFilter">
                        </div>
                        
                        <button class="btn btn-sm btn-success mr-1" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                        
                        <button class="btn btn-sm btn-secondary mr-1" onclick="resetFilters()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                       
                    </div>
                </div>
                
                <!-- Export Options (hidden now that we're using the buttons above) -->
                <div class="mb-3" style="display:none;">
                    <form method="POST" class="d-inline" action="export_verification_logs.php">
                        <input type="hidden" id="export_student" name="student" value="">
                        <input type="hidden" id="export_status" name="status" value="">
                        <input type="hidden" id="export_date" name="date" value="">
                        
                        <button type="submit" name="format" value="print" class="btn btn-success">
                            <i class="fas fa-print"></i> Print
                        </button>
                       
                        <button type="submit" name="format" value="excel" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button type="submit" name="format" value="pdf" class="btn btn-success">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </form>
                </div>
                
                <!-- Logs Table -->
                <div class="table-responsive">
                    <table class="table table-striped" id="logsTable">
                        <thead style="background-color: #098744; color: white;">
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Date & Time</th>
                                <th>Attempt</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No verification logs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['student_name']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $log['status'] === 'Success' ? 'status-success' : 'status-failed'; ?>">
                                                <?php echo $log['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $log['verification_time']; ?></td>
                                        <td><?php echo $log['ip_address']; ?></td>
                                        <td><?php echo htmlspecialchars($log['notes']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?php echo asset_url('js/jquery-3.6.0.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/popper.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.min.js'); ?>"></script>
    
    <!-- DataTables JS -->
    <script src="<?php echo asset_url('js/jquery.dataTables.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/dataTables.bootstrap4.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/dataTables.buttons.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/buttons.bootstrap4.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/jszip.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/pdfmake.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/vfs_fonts.js'); ?>"></script>
    <script src="<?php echo asset_url('js/buttons.html5.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/buttons.print.min.js'); ?>"></script>
    <script src="./functions/pagination.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = initializeStandardDataTable('#logsTable', {
                responsive: true,
                order: [[2, 'desc']],
                dom: '<"row"<"col-md-6"B><"col-md-6"f>>rtip',
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
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
                ]
            });
            
            // Additional styling for buttons
            $('.dt-buttons .btn-success').css({
                'background-color': '#098744',
                'border-color': '#098744'
            });
            
            // Apply filters and update export form values
            $('#studentFilter').on('change', function() {
                table.column(1).search(this.value).draw();
                $('#export_student').val(this.value);
            });
            
            $('#statusFilter').on('change', function() {
                table.column(2).search(this.value).draw();
                $('#export_status').val(this.value);
            });
            
            $('#dateFilter').on('change', function() {
                const date = this.value ? new Date(this.value) : null;
                
                if (date) {
                    // Format date as YYYY-MM-DD for filtering and export
                    const formattedDate = date.toISOString().split('T')[0];
                    $('#export_date').val(formattedDate);
                    
                    // Remove any existing custom filtering function
                    $.fn.dataTable.ext.search.pop();
                    
                    // Custom filtering function
                    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                        if (data[3]) {
                            // Parse the date from the format "MMM DD, YYYY h:mm:ss A"
                            const dateStr = data[3];
                            const dateParts = dateStr.match(/(\w+)\s+(\d+),\s+(\d+)/);
                            
                            if (dateParts) {
                                const month = dateParts[1];
                                const day = parseInt(dateParts[2]);
                                const year = parseInt(dateParts[3]);
                                
                                // Convert month name to month number
                                const months = {
                                    'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5,
                                    'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11
                                };
                                
                                // Create a date object from the parsed components
                                const rowDate = new Date(year, months[month], day);
                                
                                // Compare only the date part (not time)
                                return rowDate.toISOString().split('T')[0] === formattedDate;
                            }
                        }
                        return false;
                    });
                    
                    // Apply the filter immediately
                    table.draw();
                } else {
                    // Remove custom filtering
                    $.fn.dataTable.ext.search.pop();
                    table.draw();
                }
            });
            
            // Handle Print button click
            $('.print-btn').on('click', function() {
                // Create a printable version of the table
                const printContent = document.createElement('div');
                printContent.innerHTML = '<h1 style="text-align:center; margin-bottom: 20px;">Face Registration Logs</h1>';
                
                // Get filtered table data
                const tableClone = $('#logsTable').clone();
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
                    .status-badge { padding: 5px 10px; border-radius: 20px; font-weight: bold; display: inline-block; min-width: 80px; text-align: center; }
                    .status-success { background-color: #d4edda; color: #155724; }
                    .status-failed { background-color: #f8d7da; color: #721c24; }
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
                const student = $('#studentFilter').val();
                const status = $('#statusFilter').val();
                const date = $('#dateFilter').val();
                
                if (student || status || date) {
                    const filtersInfo = document.createElement('div');
                    filtersInfo.style.marginBottom = '20px';
                    
                    let filterText = '<strong>Filters applied:</strong> ';
                    
                    if (student) {
                        filterText += 'Student: ' + student + ' ';
                    }
                    
                    if (status) {
                        filterText += 'Status: ' + status + ' ';
                    }
                    
                    if (date) {
                        filterText += 'Date: ' + date + ' ';
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
                        <title>Face Registration Logs - Print</title>
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
            
            // Handle Excel export button click
            $('.export-excel-btn').on('click', function() {
                // Create a temporary form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'export_verification_logs.php';
                
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = 'excel';
                
                const studentInput = document.createElement('input');
                studentInput.type = 'hidden';
                studentInput.name = 'student';
                studentInput.value = $('#studentFilter').val() || '';
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = $('#statusFilter').val() || '';
                
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'date';
                dateInput.value = $('#dateFilter').val() || '';
                
                form.appendChild(formatInput);
                form.appendChild(studentInput);
                form.appendChild(statusInput);
                form.appendChild(dateInput);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
            
            // Handle PDF export button click
            $('.export-pdf-btn').on('click', function() {
                // Create a temporary form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'export_verification_logs.php';
                
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = 'pdf';
                
                const studentInput = document.createElement('input');
                studentInput.type = 'hidden';
                studentInput.name = 'student';
                studentInput.value = $('#studentFilter').val() || '';
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = $('#statusFilter').val() || '';
                
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'date';
                dateInput.value = $('#dateFilter').val() || '';
                
                form.appendChild(formatInput);
                form.appendChild(studentInput);
                form.appendChild(statusInput);
                form.appendChild(dateInput);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
            
            // Show loading spinner
            function showLoading() {
                document.querySelector('.loading-spinner').style.display = 'flex';
                document.querySelector('.overlay').style.display = 'block';
            }
            
            // Hide loading spinner
            function hideLoading() {
                document.querySelector('.loading-spinner').style.display = 'none';
                document.querySelector('.overlay').style.display = 'none';
            }
        });
        
        // Apply filters function
        function applyFilters() {
            // Trigger change events on all filters to apply them
            $('#studentFilter').trigger('change');
            $('#statusFilter').trigger('change');
            $('#dateFilter').trigger('change');
        }
        
        // Reset filters function
        function resetFilters() {
            // Reset all filter values
            $('#studentFilter').val('');
            $('#statusFilter').val('');
            $('#dateFilter').val('');
            
            // Clear hidden export fields
            $('#export_student').val('');
            $('#export_status').val('');
            $('#export_date').val('');
            
            // Remove any custom filtering function
            while ($.fn.dataTable.ext.search.length > 0) {
                $.fn.dataTable.ext.search.pop();
            }
            
            // Redraw the table with no filters
            $('#logsTable').DataTable().search('').columns().search('').draw();
        }

        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main');
            const toggleButton = document.querySelector('.sidebar-toggle');

            sidebar.classList.toggle('active');
            main.classList.toggle('active');
            toggleButton.classList.toggle('rotate');
        }

        // Add event listener for sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.querySelector('.sidebar-toggle');
            if (toggleButton) {
                toggleButton.onclick = toggleSidebar;
            }
        });
    </script>
</body>
</html> 