<?php
require_once 'includes/asset_helper.php';
session_start();
include('conn/db_connect.php');
require_once('includes/ActivityLogger.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$activity_logger = new ActivityLogger($conn_qr, $_SESSION['user_id']);

// Handle export request
if (isset($_POST['export']) && isset($_POST['format'])) {
    $format = $_POST['format'];
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    try {
        $export_data = $activity_logger->exportActivityLogs($format, $start_date, $end_date);
        
        // Log the export action
        $activity_logger->log(
            'data_export',
            "Exported activity logs in $format format",
            'activity_logs',
            null,
            ['format' => $format, 'start_date' => $start_date, 'end_date' => $end_date]
        );

        // Set headers for file download
        header('Content-Type: ' . $export_data['type']);
        header('Content-Disposition: attachment; filename="' . $export_data['filename'] . '"');
        header('Content-Length: ' . strlen($export_data['content']));
        echo $export_data['content'];
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error exporting logs: " . $e->getMessage();
    }
}

// Get filter parameters
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$action_type = $_GET['action_type'] ?? null;
$user_id = $_GET['user_id'] ?? null;

// Build query
$where_conditions = [];
$params = [];
$types = "";

if ($start_date && $end_date) {
    $where_conditions[] = "al.created_at BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

if ($action_type) {
    $where_conditions[] = "al.action_type = ?";
    $params[] = $action_type;
    $types .= "s";
}

if ($user_id) {
    $where_conditions[] = "al.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get activity logs
$sql = "SELECT 
    al.*, 
    u.email as user_email,
    u.firstname as user_firstname,
    u.lastname as user_lastname
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    $where_clause
    ORDER BY al.created_at DESC
    LIMIT 1000";

$stmt = $conn_qr->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);

// Get unique action types for filter
$action_types_sql = "SELECT DISTINCT action_type FROM activity_logs ORDER BY action_type";
$action_types_result = $conn_qr->query($action_types_sql);
$action_types = $action_types_result->fetch_all(MYSQLI_ASSOC);

// Get users for filter
$users_sql = "SELECT id, email, firstname, lastname FROM users ORDER BY lastname, firstname";
$users_result = $conn_qr->query($users_sql);
$users = $users_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - QR Code Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo asset_url('css/daterangepicker.css'); ?>" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        /* Force table header colors */
        #logsTable thead tr,
        #logsTable thead th,
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
    </style>
</head>
<body>
    <?php include('includes/navbar.php'); ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Activity Logs</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="date_range" class="form-label">Date Range</label>
                                        <input type="text" class="form-control" id="date_range" name="date_range" 
                                               value="<?php echo $start_date && $end_date ? "$start_date - $end_date" : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="action_type" class="form-label">Action Type</label>
                                        <select class="form-select" id="action_type" name="action_type">
                                            <option value="">All Actions</option>
                                            <?php foreach ($action_types as $type): ?>
                                                <option value="<?php echo htmlspecialchars($type['action_type']); ?>"
                                                        <?php echo $action_type === $type['action_type'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($type['action_type']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">User</label>
                                        <select class="form-select" id="user_id" name="user_id">
                                            <option value="">All Users</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>"
                                                        <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['lastname'] . ', ' . $user['firstname']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary d-block">Apply Filters</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Export Options -->
                        <div class="mb-4">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                                
                            </form>
                        </div>

                        <!-- Activity Logs Table -->
                        <div class="table-responsive">
                            <table class="table table-striped" id="logsTable">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Action Type</th>
                                        <th>Description</th>
                                        <th>Attempts</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                            <td>
                                                <?php if ($log['user_id']): ?>
                                                    <?php echo htmlspecialchars($log['user_firstname'] . ' ' . $log['user_lastname']); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($log['user_email']); ?></small>
                                                <?php else: ?>
                                                    System
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                                            <td><?php echo htmlspecialchars($log['action_description']); ?></td>
                                            <td><?php echo htmlspecialchars($log['attempts']); ?></td>
                                            <td>
                                                <?php if ($log['additional_data']): ?>
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#detailsModal<?php echo $log['id']; ?>">
                                                        View Details
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modals -->
    <?php foreach ($logs as $log): ?>
        <?php if ($log['additional_data']): ?>
            <div class="modal fade" id="detailsModal<?php echo $log['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Activity Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <pre><?php echo json_encode(json_decode($log['additional_data']), JSON_PRETTY_PRINT); ?></pre>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="<?php echo asset_url('js/jquery-3.6.0.min.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo asset_url('js/moment.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/daterangepicker.min.js'); ?>"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#logsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10,
                paging: true,
                lengthChange: false,
                searching: false,
                // Use Bootstrap pagination styling
                pagingType: 'simple_numbers',
                dom: "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-5'i><'col-sm-7'p>>",

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

            // Initialize DateRangePicker
            $('#date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            $('#date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            });

            $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        });
    </script>
</body>
</html> 