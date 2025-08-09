<?php
// Test script for termination button functionality
session_start();

// Set test session data
$_SESSION['class_start_time'] = '14:30';
$_SESSION['class_start_time_formatted'] = '14:30:00';
$_SESSION['current_instructor_name'] = 'Test Instructor';
$_SESSION['current_subject_name'] = 'Test Subject';
$_SESSION['school_id'] = 1;
$_SESSION['user_id'] = 1;
$_SESSION['email'] = 'test@example.com';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Termination Button</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .status-indicator {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status-active {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="text-center mb-4">
            <i class="fas fa-stop-circle text-danger"></i> 
            Termination Button Test
        </h1>
        
        <div class="test-section">
            <h3>Current Session Status</h3>
            <div class="status-indicator <?= isset($_SESSION['class_start_time']) ? 'status-active' : 'status-inactive' ?>">
                <strong>Class Start Time:</strong> <?= $_SESSION['class_start_time'] ?? 'Not set' ?><br>
                <strong>Instructor:</strong> <?= $_SESSION['current_instructor_name'] ?? 'Not set' ?><br>
                <strong>Subject:</strong> <?= $_SESSION['current_subject_name'] ?? 'Not set' ?><br>
                <strong>School ID:</strong> <?= $_SESSION['school_id'] ?? 'Not set' ?>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Test Termination Buttons</h3>
            
            <!-- Header-style termination button -->
            <div class="mb-3">
                <h5>Header Termination Button (Large)</h5>
                <button type="button" id="headerTerminateBtn" class="btn btn-danger btn-lg" onclick="testTermination()">
                    <i class="fas fa-stop-circle"></i> Terminate Session
                </button>
            </div>
            
            <!-- Regular termination button -->
            <div class="mb-3">
                <h5>Regular Termination Button</h5>
                <button type="button" id="terminateClassSession" class="btn btn-danger btn-block" onclick="testTermination()">
                    <i class="fas fa-stop-circle"></i> Terminate Current Session
                </button>
                <small class="text-muted mt-2 d-block">
                    <i class="fas fa-info-circle"></i> This will end the current class session and clear all session data
                </small>
            </div>
            
            <!-- Test API directly -->
            <div class="mb-3">
                <h5>Direct API Test</h5>
                <button type="button" class="btn btn-warning" onclick="testDirectAPI()">
                    <i class="fas fa-code"></i> Test API Directly
                </button>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Test Results</h3>
            <div id="testResults" class="alert alert-info">
                Click a button above to test the termination functionality...
            </div>
        </div>
        
        <div class="test-section">
            <h3>Session Management</h3>
            <div class="row">
                <div class="col-md-6">
                    <button type="button" class="btn btn-success btn-block" onclick="setTestSession()">
                        <i class="fas fa-play"></i> Set Test Session
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-secondary btn-block" onclick="clearTestSession()">
                        <i class="fas fa-trash"></i> Clear Test Session
                    </button>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Navigation</h3>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Go to Main Page
            </a>
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Test Logout
            </a>
        </div>
    </div>

    <script>
        function testTermination() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing termination...</div>';
            
            if (!confirm('Are you sure you want to terminate the current class session? This will end attendance tracking for the current session.')) {
                resultsDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Termination cancelled by user</div>';
                return;
            }
            
            // Show loading state on buttons
            const buttons = document.querySelectorAll('#headerTerminateBtn, #terminateClassSession');
            buttons.forEach(btn => {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Terminating...';
                btn.disabled = true;
                
                // Restore button after 3 seconds
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 3000);
            });
            
            // Simulate API call
            setTimeout(() => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Termination Test Successful!</h5>
                        <p><strong>What happened:</strong></p>
                        <ul>
                            <li>Session variables would be cleared</li>
                            <li>Database records would be updated</li>
                            <li>UI would be reset to no-active-session state</li>
                            <li>Header termination button would be hidden</li>
                        </ul>
                        <p><strong>Note:</strong> This is a simulation. In the real system, the API would be called.</p>
                    </div>
                `;
            }, 2000);
        }
        
        function testDirectAPI() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing API directly...</div>';
            
            fetch('api/terminate-class-session.php', {
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
                            <h5><i class="fas fa-check-circle"></i> API Test Successful!</h5>
                            <p><strong>Response:</strong></p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> API Test Failed!</h5>
                            <p><strong>Error:</strong> ${data.message}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> API Test Error!</h5>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            });
        }
        
        function setTestSession() {
            // This would set session data via AJAX in a real scenario
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = `
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Test Session Set</h5>
                    <p>In a real scenario, this would set the class time and show the termination buttons.</p>
                    <p>Current test session is already active.</p>
                </div>
            `;
        }
        
        function clearTestSession() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = `
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Test Session Cleared</h5>
                    <p>In a real scenario, this would clear the session data.</p>
                    <p>For this test, you can refresh the page to reset.</p>
                </div>
            `;
        }
    </script>
</body>
</html> 