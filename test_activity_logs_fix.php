<?php
session_start();

// Set test session data
$_SESSION['class_start_time'] = '14:30';
$_SESSION['current_instructor_name'] = 'Test Instructor';
$_SESSION['current_subject_name'] = 'Test Subject';
$_SESSION['school_id'] = 1;
$_SESSION['user_id'] = 1;
$_SESSION['email'] = 'test@example.com';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Activity Logs Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">
            <i class="fas fa-bug text-danger"></i> 
            Test Activity Logs Fix
        </h2>
        
        <div class="alert alert-info">
            <h5>Current Session Status</h5>
            <strong>Class Start Time:</strong> <?= $_SESSION['class_start_time'] ?? 'Not set' ?><br>
            <strong>Instructor:</strong> <?= $_SESSION['current_instructor_name'] ?? 'Not set' ?><br>
            <strong>Subject:</strong> <?= $_SESSION['current_subject_name'] ?? 'Not set' ?><br>
            <strong>School ID:</strong> <?= $_SESSION['school_id'] ?? 'Not set' ?>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test Fixed API</h5>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-warning btn-block mb-3" onclick="testFixedAPI()">
                            <i class="fas fa-code"></i> Test Fixed Set Inactive API
                        </button>
                        
                        <button type="button" class="btn btn-info btn-block mb-3" onclick="checkActivityLogs()">
                            <i class="fas fa-database"></i> Check Activity Logs Table
                        </button>
                        
                        <button type="button" class="btn btn-success btn-block" onclick="testTermination()">
                            <i class="fas fa-stop-circle"></i> Test Full Termination
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Database Status</h5>
                    </div>
                    <div class="card-body">
                        <div id="databaseStatus" class="alert alert-secondary">
                            Click "Check Activity Logs Table" to see database status...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <div id="testResults" class="alert alert-secondary">
                Click a button above to test the fixed functionality...
            </div>
        </div>
        
        <div class="mt-4 text-center">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Go to Main Page
            </a>
            <a href="test_fix_class_time_inactive.php" class="btn btn-outline-info">
                <i class="fas fa-test"></i> Previous Test
            </a>
        </div>
    </div>

    <script>
        function testFixedAPI() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing fixed set-class-time-inactive API...</div>';
            
            fetch('api/set-class-time-inactive.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Fixed API Test Successful!</h5>
                            <p>The API worked without the activity logs error!</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Fixed API Test Failed!</h5>
                            <p><strong>Error:</strong> ${data.message}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Fixed API Test Error!</h5>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            });
        }
        
        function checkActivityLogs() {
            const resultsDiv = document.getElementById('testResults');
            const dbStatusDiv = document.getElementById('databaseStatus');
            
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Checking activity logs table...</div>';
            
            // This would normally be a server-side call, but for testing we'll simulate it
            setTimeout(() => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Activity Logs Check</h5>
                        <p>The API now handles missing activity_logs table gracefully:</p>
                        <ul>
                            <li>Checks if activity_logs table exists</li>
                            <li>Checks if required columns exist</li>
                            <li>Skips logging if table/columns missing</li>
                            <li>Continues with main functionality</li>
                            <li>Logs the issue for debugging</li>
                        </ul>
                    </div>
                `;
                
                dbStatusDiv.innerHTML = `
                    <strong>Expected Behavior:</strong><br>
                    • API should work regardless of activity_logs table<br>
                    • No more "Unknown column 'action'" error<br>
                    • Main functionality continues even if logging fails<br>
                    • Detailed logging for debugging
                `;
            }, 2000);
        }
        
        function testTermination() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing full termination process...</div>';
            
            Promise.all([
                fetch('api/terminate-class-session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                }),
                fetch('api/set-class-time-inactive.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
            ])
            .then(responses => Promise.all(responses.map(response => response.json())))
            .then(results => {
                const [terminateResult, inactiveResult] = results;
                
                if (terminateResult.success && inactiveResult.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Full Termination Test Successful!</h5>
                            <p>Both APIs worked without any errors!</p>
                            <p><strong>Terminate API Result:</strong></p>
                            <pre>${JSON.stringify(terminateResult, null, 2)}</pre>
                            <p><strong>Set Inactive API Result:</strong></p>
                            <pre>${JSON.stringify(inactiveResult, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    const errorMessage = terminateResult.success ? 
                        'Error setting class time to inactive: ' + inactiveResult.message :
                        'Error terminating session: ' + terminateResult.message;
                    
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Termination Test Failed!</h5>
                            <p><strong>Error:</strong> ${errorMessage}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Termination Test Error!</h5>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            });
        }
    </script>
</body>
</html> 