<?php
// Use consistent session handling
require_once '../includes/session_config.php';
require_once "database.php";
require_once "functions/log_functions.php";

// Clear cache to ensure fresh data
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Store the login database connection in $sidebarConn for the sidebar
$sidebarConn = $conn;

// AUTOMATIC SESSION MANAGEMENT - NO DUPLICATES
// Only run session management if we have a user logged in
if (isset($_SESSION['username'])) {
    $current_username = $_SESSION['username'];
    
    // Step 1: Check if user has any active sessions (filtered by user_id and school_id)
    $current_user_id = $_SESSION['user_id'] ?? 0;
    $current_school_id = $_SESSION['school_id'] ?? 0;
    
    $active_check = "SELECT COUNT(*) as active_count FROM tbl_user_logs 
                     WHERE username = ? AND log_out_time IS NULL AND user_id = ? AND school_id = ?";
    $stmt = mysqli_prepare($conn, $active_check);
    mysqli_stmt_bind_param($stmt, "sii", $current_username, $current_user_id, $current_school_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $active_count = mysqli_fetch_assoc($result)['active_count'];
    
    // Step 2: If user has MORE than 1 active session, clean up automatically
    if ($active_count > 1) {
        // Close all but the most recent active session (filtered by user_id and school_id)
        $cleanup_query = "UPDATE tbl_user_logs 
                         SET log_out_time = NOW() 
                         WHERE username = ? 
                         AND log_out_time IS NULL 
                         AND user_id = ? 
                         AND school_id = ? 
                         AND log_id NOT IN (
                             SELECT max_id FROM (
                                 SELECT MAX(log_id) as max_id 
                                 FROM tbl_user_logs 
                                 WHERE username = ? 
                                 AND log_out_time IS NULL 
                                 AND user_id = ? 
                                 AND school_id = ?
                             ) as latest
                         )";
        
        $cleanup_stmt = mysqli_prepare($conn, $cleanup_query);
        mysqli_stmt_bind_param($cleanup_stmt, "siisii", $current_username, $current_user_id, $current_school_id, $current_username, $current_user_id, $current_school_id);
        mysqli_stmt_execute($cleanup_stmt);
        
        error_log("Auto-cleaned duplicate sessions for user: $current_username");
    }
    
    // Step 3: If user has NO active sessions, create one (they just logged in)
    if ($active_count == 0 && !isset($_SESSION['session_created_this_login'])) {
        $user_type = $_SESSION['user_type'] ?? 'User';
        $log_id = recordUserLogin($conn, $current_username, '', $user_type);
        
        if ($log_id) {
            $_SESSION['log_id'] = $log_id;
            $_SESSION['session_created_this_login'] = true;
            error_log("Auto-created session for user: $current_username (ID: $log_id)");
        }
    }
}

// Make sure the tbl_user_logs table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_user_logs'");
if (mysqli_num_rows($check_table) == 0) {
    // Create the table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS tbl_user_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        user_type VARCHAR(20) DEFAULT 'User',
        log_in_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        log_out_time DATETIME NULL,
        ip_address VARCHAR(45),
        user_id INT NOT NULL,
        school_id INT NOT NULL
    )";
    mysqli_query($conn, $create_table);
}

// Get history logs
try {
    // Get session variables for filtering
    $current_user_id = $_SESSION['user_id'] ?? 0;
    $current_school_id = $_SESSION['school_id'] ?? 0;
    
    // Get distinct users for filter (filtered by school_id and user_id - show only current user)
    // Get current user's username from session instead of database variations
    $current_username = $_SESSION['username'] ?? '';
    $users = [];
    if (!empty($current_username)) {
        $users[] = $current_username;
    }
    
    // Debug: Log what users are being fetched
    error_log("Current username from session: $current_username");
    
    // Add HTML comment for debugging
    echo "<!-- Debug: Current username from session: $current_username -->";

    // Get all logs - MAKE SURE TO USE FRESH DATA (filtered by school_id and user_id - show only current user)
    $logs_query = "SELECT * FROM tbl_user_logs WHERE school_id = ? AND user_id = ? ORDER BY log_in_time DESC";
    $logs_stmt = mysqli_prepare($conn, $logs_query);
    mysqli_stmt_bind_param($logs_stmt, "ii", $current_school_id, $current_user_id);
    mysqli_stmt_execute($logs_stmt);
    $logs_result = mysqli_stmt_get_result($logs_stmt);
    
    if (!$logs_result) {
        throw new Exception("Error fetching logs: " . mysqli_error($conn));
    }
    
    $logs = [];
    while ($row = mysqli_fetch_assoc($logs_result)) {
        $logs[] = $row;
    }

    // Debug output - log what data we have
    error_log("History page loaded - Records found: " . count($logs));
    error_log("Session info: user_id=$current_user_id, school_id=$current_school_id");
    if (!empty($logs)) {
        error_log("Sample log entry: " . print_r($logs[0], true));
    }

} catch(Exception $e) {
    error_log("Error in user logs: " . $e->getMessage());
    $logs = [];
    $users = [];
}

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Test query (filtered by school_id and user_id - show only current user)
$test_query = "SELECT COUNT(*) as count FROM tbl_user_logs WHERE school_id = ? AND user_id = ?";
$test_stmt = mysqli_prepare($conn, $test_query);
mysqli_stmt_bind_param($test_stmt, "ii", $current_school_id, $current_user_id);
mysqli_stmt_execute($test_stmt);
$test_result = mysqli_stmt_get_result($test_stmt);
if ($test_result) {
    $row = mysqli_fetch_assoc($test_result);
    error_log("Number of records for user_id=$current_user_id, school_id=$current_school_id: " . $row['count']);
} else {
    error_log("Query failed: " . mysqli_error($conn));
}

// Add this after your database connection check
echo "<!-- Debug info: -->";
$check_logs = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM tbl_user_logs WHERE school_id = ? AND user_id = ?");
mysqli_stmt_bind_param($check_logs, "ii", $current_school_id, $current_user_id);
mysqli_stmt_execute($check_logs);
$check_logs_result = mysqli_stmt_get_result($check_logs);
$logs_count = mysqli_fetch_assoc($check_logs_result)['count'];
echo "<!-- Logs count for user_id=$current_user_id, school_id=$current_school_id: " . $logs_count . " -->";

$check_users = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$users_count = mysqli_fetch_assoc($check_users)['count'];
echo "<!-- Users count: " . $users_count . " -->";

// Check table structure
$check_structure = mysqli_query($conn, "DESCRIBE tbl_user_logs");
echo "<!-- tbl_user_logs structure: -->";
while ($row = mysqli_fetch_assoc($check_structure)) {
    echo "<!-- " . $row['Field'] . ": " . $row['Type'] . " -->";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Logs - QR Code Attendance System</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./styles/masterlist.css">

    <!-- Data Table -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" />
    <link rel="stylesheet" href="../styles/pagination.css" />

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
            display: block !important;
            height: auto !important;
            justify-content: flex-start !important;
            align-items: flex-start !important;
            background-color:#808080 ; /* Light gray background for main content */
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

        /* Logs container styles */
        .logs-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            margin: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            width: calc(100% - 40px);
            transition: all 0.3s ease;
        }

        .logs-content {
            background-color: #f8f9fa; /* Light gray background for content */
            border-radius: 20px;
            padding: 20px;
            height: 100%;
            overflow-y: auto;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        /* Title styles with white background */
        .title {
            background-color: white;
            border-radius: 20px 20px 0 0;
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

        /* Table container */
        .table-responsive {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }

        /* Pagination styling */
        .dataTables_paginate {
            margin-top: 15px !important;
        }
        
        .paginate_button {
            padding: 5px 10px !important;
            margin: 0 5px !important;
            border-radius: 5px !important;
            background-color: #f8f9fa !important;
            color: #098744 !important;
            cursor: pointer !important;
        }
        
        .paginate_button.current {
            background-color: #098744 !important;
            color: white !important;
        }
        
        .paginate_button:hover:not(.current) {
            background-color: #e9ecef !important;
        }

        /* Badge styling */
        .badge-admin {
            background-color: #098744;
            color: white;
        }
        
        .badge-user {
            background-color: #6c757d;
            color: white;
        }
        
        .badge-active {
            background-color: #28a745;
            color: white;
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

        /* Add background styling */
        body {
            background: linear-gradient(135deg, #098744 0%, #098744 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
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
    include('../components/sidebar-nav.php'); 
    ?>
   
    <div class="main collapsed" id="main">
        <div class="logs-container">
            <div class="logs-content">
                <div class="title" style="text-align: center;">
                    <h4 style="color: #098744;"><i class="fas fa-history"></i> User Logs</h4>
                </div>
                <hr>
                <!-- Filters -->
                <div class="filters-container">
                    <div class="d-flex flex-wrap align-items-center w-100">
                        <div class="form-group mr-2 mb-0">
                            <select class="form-control form-control-sm" id="userFilter">
                                <option value="">All Logs</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo htmlspecialchars($user); ?>" selected>
                                        <?php echo htmlspecialchars($user); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mr-2 mb-0">
                            <select class="form-control form-control-sm" id="usertypeFilter">
                                <option value="">All Types</option>
                                <option value="Admin">Admin</option>
                                <option value="User">User</option>
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
                        
                        <button class="btn btn-sm btn-primary mr-1" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh Now
                        </button>
                    </div>
                </div>
                
                <!-- Export Options (hidden now that we're using the buttons above) -->
                <div class="mb-3" style="display:none;">
                    <form method="POST" class="d-inline" action="export_history.php">
                        <input type="hidden" id="export_user" name="user" value="">
                        <input type="hidden" id="export_date" name="date" value="">
                        <input type="hidden" id="export_user_type" name="user_type" value="">
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
                                <th>Username</th>
                                <th>User Type</th>
                                <th>Log In Time</th>
                                <th>Log Out Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="alert alert-info">
                                            No user logs found. This could be because:
                                            <ul class="mb-0">
                                                <li>No users have logged in yet</li>
                                                <li>The logging system was recently implemented</li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                                        <td>
                                            <?php if ($log['user_type'] === 'Admin'): ?>
                                                <span class="badge badge-primary">
                                                    <?php echo htmlspecialchars($log['user_type']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y h:i:s A', strtotime($log['log_in_time'])); ?></td>
                                        <td>
                                            <?php if ($log['log_out_time']): ?>
                                                <?php echo date('M d, Y h:i:s A', strtotime($log['log_out_time'])); ?>
                                            <?php else: ?>
                                                <span class="badge badge-active">Currently Active</span>
                                            <?php endif; ?>
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

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    
    <!-- Inline CSS to fix pagination spacing -->
    <style>
        /* Fix for pagination spacing in history.php */
        .dataTables_paginate .paginate_button {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .dataTables_paginate .pagination .page-item {
            margin: 0 2px !important;
        }
        
        .dataTables_paginate .pagination .page-link {
            margin: 0 !important;
            padding: 0.5rem 0.75rem !important;
            min-width: 40px !important;
        }
        
        /* Remove DataTables default styling that might be causing issues */
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover,
        .dataTables_wrapper .dataTables_paginate .paginate_button:active,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: none !important;
            border: none !important;
        }
        
        /* Override Bootstrap spacing */
        .pagination {
            gap: 0 !important;
        }
    </style>
    
    <script>
        $(document).ready(function() {
            // Clear any existing DataTables
            if ($.fn.DataTable.isDataTable('#logsTable')) {
                $('#logsTable').DataTable().destroy();
            }
            
            // Add timestamp to prevent caching
            console.log('Initializing table at: ' + new Date().toISOString());
            
            // Initialize DataTable with fresh data
            const table = $('#logsTable').DataTable({
                destroy: true, // Force destroy any existing table
                responsive: true,
                order: [[2, 'desc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
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
                // Force no caching
                ajax: null,
                serverSide: false,
                processing: false
            });
            
            // Additional styling for buttons
            $('.dt-buttons .btn-success').css({
                'background-color': '#098744',
                'border-color': '#098744',
                'margin-right': '5px'
            });
            
            // Apply default username filter if a specific user is selected
            const defaultUser = $('#userFilter option:selected').val();
            if (defaultUser) {
                table.column(0).search(defaultUser).draw();
            }
            
            // Update export form values when filters change
            $('#userFilter').on('change', function() {
                console.log('User filter changed to:', this.value);
                table.column(0).search(this.value).draw();
                $('#export_user').val(this.value);
            });
            
            $('#usertypeFilter').on('change', function() {
                table.column(1).search(this.value).draw();
                $('#export_user_type').val(this.value);
            });
            
            $('#dateFilter').on('change', function() {
                // Clear any existing custom filter first
                while ($.fn.dataTable.ext.search.length > 0) {
                    $.fn.dataTable.ext.search.pop();
                }
                
                if (this.value) {
                    // Set the export_date value
                    $('#export_date').val(this.value);
                    
                    // Get the date value from the input
                    const inputDate = this.value;
                    
                    // Create a date object from the input value
                    const parts = inputDate.split('-');
                    if (parts.length === 3) {
                        const year = parseInt(parts[0]);
                        const month = parseInt(parts[1]) - 1; // JavaScript months are 0-based
                        const day = parseInt(parts[2]);
                        
                        // Map the month numbers to names for comparison
                        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        const selectedMonthName = monthNames[month];
                        
                        console.log(`Filtering for: ${selectedMonthName} ${day}, ${year}`);
                        
                        // Custom filtering function
                        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                            // Get the date string from the table cell (4th column, index 3)
                            const dateStr = data[3];
                            
                            if (!dateStr) {
                                return false;
                            }
                            
                            // Match date strings like "Apr 04, 2025 07:58:48 AM" or "Apr 4, 2025 7:58:48 AM"
                            // We only care about the date part, not the time
                            const datePartMatch = dateStr.match(/([A-Za-z]{3})\s+(\d{1,2}),\s+(\d{4})/);
                            
                            if (datePartMatch) {
                                const rowMonth = datePartMatch[1]; // Month name (Apr)
                                const rowDay = parseInt(datePartMatch[2], 10); // Day (4 or 04)
                                const rowYear = parseInt(datePartMatch[3], 10); // Year (2025)
                                
                                // Compare the row's date with the selected date
                                return (rowMonth === selectedMonthName && 
                                        rowDay === day && 
                                        rowYear === year);
                            }
                            
                            return false;
                        });
                    }
                }
                
                // Apply the filter immediately
                table.draw();
            });
            
            // Handle Print button click
            $('.print-btn').on('click', function() {
                // Create a temporary form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'export_history.php';
                
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = 'print';
                
                const userInput = document.createElement('input');
                userInput.type = 'hidden';
                userInput.name = 'user';
                userInput.value = $('#userFilter').val() || '';
                
                const userTypeInput = document.createElement('input');
                userTypeInput.type = 'hidden';
                userTypeInput.name = 'user_type';
                userTypeInput.value = $('#usertypeFilter').val() || '';
                
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'date';
                dateInput.value = $('#dateFilter').val() || '';
                
                form.appendChild(formatInput);
                form.appendChild(userInput);
                form.appendChild(userTypeInput);
                form.appendChild(dateInput);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
            
            // Handle Excel export button click
            $('.export-excel-btn').on('click', function() {
                // Create a temporary form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'export_history.php';
                
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = 'excel';
                
                const userInput = document.createElement('input');
                userInput.type = 'hidden';
                userInput.name = 'user';
                userInput.value = $('#userFilter').val() || '';
                
                const userTypeInput = document.createElement('input');
                userTypeInput.type = 'hidden';
                userTypeInput.name = 'user_type';
                userTypeInput.value = $('#usertypeFilter').val() || '';
                
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'date';
                dateInput.value = $('#dateFilter').val() || '';
                
                form.appendChild(formatInput);
                form.appendChild(userInput);
                form.appendChild(userTypeInput);
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
                form.action = 'export_history.php';
                
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = 'pdf';
                
                const userInput = document.createElement('input');
                userInput.type = 'hidden';
                userInput.name = 'user';
                userInput.value = $('#userFilter').val() || '';
                
                const userTypeInput = document.createElement('input');
                userTypeInput.type = 'hidden';
                userTypeInput.name = 'user_type';
                userTypeInput.value = $('#usertypeFilter').val() || '';
                
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'date';
                dateInput.value = $('#dateFilter').val() || '';
                
                form.appendChild(formatInput);
                form.appendChild(userInput);
                form.appendChild(userTypeInput);
                form.appendChild(dateInput);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
        });
        
        // Apply filters function
        function applyFilters() {
            // Trigger change events on all filters to apply them
            $('#userFilter').trigger('change');
            $('#usertypeFilter').trigger('change');
            $('#dateFilter').trigger('change');
        }
        
        // Reset filters function
        function resetFilters() {
            // Reset all filter values
            $('#userFilter').val('');
            $('#usertypeFilter').val('Admin');
            $('#dateFilter').val('');
            
            // Clear hidden export fields
            $('#export_user').val('');
            $('#export_user_type').val('');
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
            const sidebar = document.querySelector('.sidebar');
            const main = document.querySelector('.main');
            const toggleButton = document.querySelector('.bx-menu');

            if (sidebar) {
                sidebar.classList.toggle('close');
            }
            if (toggleButton) {
                toggleButton.classList.toggle('rotate');
            }
        }

        // Add event listener for sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.querySelector('.bx-menu');
            if (toggleButton) {
                toggleButton.onclick = toggleSidebar;
            }
        });
    </script>
</body>
</html>