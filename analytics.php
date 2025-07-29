<?php
require_once 'includes/asset_helper.php';
require_once 'includes/session_config.php';
include('./conn/db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: admin/login.php");
    exit;
}

// Use mysqli prepared statement and get_result() instead of PDO
$query = "SELECT 
    tbl_student.course_section,
    COUNT(*) as attendance_count 
    FROM tbl_attendance 
    LEFT JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id 
    GROUP BY tbl_student.course_section";

try {
    // Initialize empty arrays in case of failure
    $labels = [];
    $data = [];
    $attendanceData = []; // Initialize the attendance data array
    
    // Prepare and execute the query using mysqli
    $stmt = $conn_qr->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all results using mysqli methods
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['course_section'];
        $data[] = $row['attendance_count'];
        $attendanceData[] = $row; // Store the complete row data
    }
    
    // Close the statement
    $stmt->close();
    
} catch(Exception $e) {
    // Log error and initialize empty arrays
    error_log("Error in analytics.php: " . $e->getMessage());
    $labels = [];
    $data = [];
    $attendanceData = []; // Initialize empty array in case of error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Attendance System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="./styles/analytics.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        /* Analytics container styles */
        .analytics-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            margin: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            width: calc(100% - 40px);
            transition: all 0.3s ease;
        }

        .main.active .analytics-container {
            width: calc(100% - 40px);
            margin: 20px;
        }

        /* Chart styles */
        .chart-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
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
            
            .analytics-container {
                margin: 10px;
                min-height: calc(100vh - 100px);
            }

            .chart-container {
                height: 250px;
            }
        }

        /* Content padding */
        .charts-content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Custom Alert Box -->
    <div id="customAlert" class="custom-alert"></div>

    <?php include('./components/sidebar-nav.php'); ?>
   


    <div class="main collapsed" id="main">
        <div class="analytics-container">
            <div class="analytics-content">
                <div class="title">
                    <h4><i class="fas fa-chart-pie"></i> Attendance Analytics</h4>
                </div>
                
                <div class="charts-content">
                    <!-- Charts Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-card">
                                <h5 class="text-center mb-4" style="color: #098744;">
                                    <i class="fas fa-chart-pie"></i>
                                    Attendance by Course (Pie Chart)
                                </h5>
                                <div class="chart-container">
                                    <canvas id="pieChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-card">
                                <h5 class="text-center mb-4" style="color: #098744;">
                                    <i class="fas fa-chart-bar"></i>
                                    Attendance by Course (Bar Graph)
                                </h5>
                                <div class="chart-container">
                                    <canvas id="barChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Table Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="chart-card">
                                <h5 class="text-center mb-4" style="color: #098744;">
                                    <i class="fas fa-table"></i>
                                    Attendance Summary
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered text-center">
                                        <thead style="background-color: #098744; color: white;">
                                            <tr>
                                                <th>Course</th>
                                                <th>Total Attendance</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $totalAttendance = array_sum($data);
                                            if (!empty($attendanceData)) {
                                                foreach ($attendanceData as $row) {
                                                    $percentage = $totalAttendance > 0 ? ($row['attendance_count'] / $totalAttendance) * 100 : 0;
                                                    echo "<tr>";
                                                    echo "<td>{$row['course_section']}</td>";
                                                    echo "<td>{$row['attendance_count']}</td>";
                                                    echo "<td>" . number_format($percentage, 2) . "%</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='3'>No attendance data available</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
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
    <script src="./functions/script.js"></script>

    <script>
        // Define consistent colors for both charts
        const chartColors = [
            '#098744',  // Primary green
            '#FFCE56',  // Yellow
            '#36A2EB',  // Blue
            '#FF6384',  // Red
            '#9966FF',  // Purple
            '#4BC0C0',  // Teal
            '#FF9F40',  // Orange
            '#C9CBCF'   // Gray
        ];

        // Charts JavaScript
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: chartColors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Number of Attendances',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: chartColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main');
            const toggleButton = document.querySelector('.sidebar-toggle');

            sidebar.classList.toggle('active');
            main.classList.toggle('active');
            toggleButton.classList.toggle('rotate');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.querySelector('.sidebar-toggle');
            if (toggleButton) {
                toggleButton.onclick = toggleSidebar;
            }
        });
    </script>
</body>
</html> 