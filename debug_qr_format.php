<?php
// Debug utility to check QR code formats in the database
require_once 'includes/session_config.php';
include('./conn/db_connect.php');

// Basic security check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    header("Location: admin/login.php");
    exit;
}

$school_id = $_SESSION['school_id'];
$user_id = $_SESSION['user_id'];

// Check QR codes in database
$qr_check_query = "SELECT tbl_student_id, student_name, generated_code, course_section FROM tbl_student 
                   WHERE school_id = ? AND user_id = ?
                   ORDER BY tbl_student_id DESC LIMIT 10";
$qr_stmt = $conn_qr->prepare($qr_check_query);
$qr_stmt->bind_param("ii", $school_id, $user_id);
$qr_stmt->execute();
$qr_result = $qr_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Format Debugger</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container my-4">
        <h1 class="mb-4">QR Code Format Debugger</h1>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">QR Codes in Database</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Course/Section</th>
                            <th>QR Code Format</th>
                            <th>Format Valid?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $expected_format = '/^STU-\d+-\d+-[a-zA-Z0-9]+-[a-zA-Z0-9]+$/';
                        $all_valid = true;
                        
                        if ($qr_result->num_rows > 0) {
                            while ($row = $qr_result->fetch_assoc()) {
                                $is_valid = preg_match($expected_format, $row['generated_code']);
                                if (!$is_valid) $all_valid = false;
                                ?>
                                <tr>
                                    <td><?php echo $row['tbl_student_id']; ?></td>
                                    <td><?php echo $row['student_name']; ?></td>
                                    <td><?php echo $row['course_section']; ?></td>
                                    <td><?php echo $row['generated_code']; ?></td>
                                    <td>
                                        <?php if ($is_valid): ?>
                                            <span class="badge badge-success">Valid</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Invalid</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No students found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                
                <?php if (!$all_valid): ?>
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> Some QR codes in the database don't match the expected format.
                        You may need to regenerate QR codes for these students.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Test QR Generation</h5>
            </div>
            <div class="card-body">
                <p>Generate a test QR code using the updated format:</p>
                
                <div class="form-group">
                    <label for="testStudentName">Student Name:</label>
                    <input type="text" class="form-control" id="testStudentName" value="Test Student">
                </div>
                
                <div class="form-group">
                    <label for="testCourse">Course:</label>
                    <input type="text" class="form-control" id="testCourse" value="BSIT">
                </div>
                
                <div class="form-group">
                    <label for="testSection">Section:</label>
                    <input type="text" class="form-control" id="testSection" value="A">
                </div>
                
                <button id="generateTestQr" class="btn btn-primary">Generate Test QR</button>
                
                <div class="mt-3 text-center" id="qrTestResult" style="display:none;">
                    <h5>Generated QR Code:</h5>
                    <p id="qrTestText" class="mb-3 p-2 bg-light border"></p>
                    <img id="qrTestImg" src="" alt="Generated QR Code">
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">QR Scanner Test</h5>
            </div>
            <div class="card-body">
                <p>Test the QR scanner to ensure it's working correctly:</p>
                
                <div id="qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
                
                <div class="mt-3" id="scanResultContainer" style="display:none;">
                    <div class="alert alert-info">
                        <h5>Scan Result:</h5>
                        <p id="scanResult"></p>
                        <p><strong>Format Valid:</strong> <span id="scanFormatValid"></span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="masterlist.php" class="btn btn-secondary">Back to Masterlist</a>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    
    <script>
        $(document).ready(function() {
            // Generate test QR code
            $('#generateTestQr').click(function() {
                const studentName = $('#testStudentName').val() || "Test Student";
                const course = $('#testCourse').val() || "BSIT";
                const section = $('#testSection').val() || "A";
                
                // Get session values from PHP
                const user_id = <?php echo $_SESSION['user_id'] ?? 1; ?>;
                const school_id = <?php echo $_SESSION['school_id'] ?? 1; ?>;
                
                // Generate components for unique code
                const randomString = Math.random().toString(36).substring(2, 18);
                const studentHash = btoa(studentName + course + section).replace(/[^a-zA-Z0-9]/g, '').substring(0, 8);
                
                // Create code in format matching backend: STU-{user_id}-{school_id}-{hash}-{random}
                const qrText = `STU-${user_id}-${school_id}-${studentHash}-${randomString}`;
                
                // Display results
                $('#qrTestText').text(qrText);
                $('#qrTestImg').attr('src', `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrText)}`);
                $('#qrTestResult').show();
            });
            
            // Initialize QR scanner
            const html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader", { fps: 10, qrbox: 250 });
            
            function onScanSuccess(qrCodeMessage) {
                // Stop the scanner
                html5QrcodeScanner.clear();
                
                // Check if format is valid
                const expectedFormat = /^STU-\d+-\d+-[a-zA-Z0-9]+-[a-zA-Z0-9]+$/;
                const isValid = expectedFormat.test(qrCodeMessage);
                
                // Display results
                $('#scanResult').text(qrCodeMessage);
                $('#scanFormatValid').html(isValid ? 
                    '<span class="badge badge-success">Valid</span>' : 
                    '<span class="badge badge-danger">Invalid</span>');
                $('#scanResultContainer').show();
                
                // Restart scanner after a delay
                setTimeout(function() {
                    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                }, 3000);
            }
            
            function onScanFailure(error) {
                console.warn(`QR scan error: ${error}`);
            }
            
            // Render the scanner
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        });
    </script>
</body>
</html>
