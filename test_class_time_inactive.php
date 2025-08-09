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
    <title>Test Class Time Inactive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
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
        <h2 class="text-center mb-4">
            <i class="fas fa-clock text-warning"></i> 
            Test Class Time Inactive Functionality
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
                        <h5>Test Termination Buttons</h5>
                    </div>
                    <div class="card-body">
                        <button type="button" id="headerTerminateBtn" class="btn btn-danger btn-lg btn-block mb-3" onclick="testTermination()">
                            <i class="fas fa-stop-circle"></i> Terminate & Set Inactive
                        </button>
                        
                        <button type="button" id="terminateClassSession" class="btn btn-danger btn-block mb-3" onclick="testTermination()">
                            <i class="fas fa-stop-circle"></i> Terminate Session & Set Class Time Inactive
                        </button>
                        
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> These buttons will terminate the session and set class time settings to inactive
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>API Tests</h5>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-warning btn-block mb-2" onclick="testTerminateAPI()">
                            <i class="fas fa-code"></i> Test Terminate API
                        </button>
                        
                        <button type="button" class="btn btn-warning btn-block mb-2" onclick="testInactiveAPI()">
                            <i class="fas fa-code"></i> Test Set Inactive API
                        </button>
                        
                        <button type="button" class="btn btn-info btn-block" onclick="testBothAPIs()">
                            <i class="fas fa-code"></i> Test Both APIs
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <div id="testResults" class="alert alert-secondary">
                Click a button above to test the functionality...
            </div>
        </div>
        
        <div class="mt-4 text-center">
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
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing termination and setting class time inactive...</div>';
            
            if (!confirm('Are you sure you want to terminate the current class session and set class time to inactive?')) {
                resultsDiv.innerHTML = '<div class="alert alert-info">Termination cancelled by user</div>';
                return;
            }
            
            // Show loading state
            const buttons = document.querySelectorAll('#headerTerminateBtn, #terminateClassSession');
            buttons.forEach(btn => {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Terminating...';
                btn.disabled = true;
            });
            
            // Simulate API calls
            setTimeout(() => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Termination Test Successful!</h5>
                        <p><strong>What happened:</strong></p>
                        <ul>
                            <li>Session variables cleared</li>
                            <li>Class time settings set to inactive</li>
                            <li>Database records updated</li>
                            <li>UI reset to no-active-session state</li>
                        </ul>
                        <p><strong>Note:</strong> This is a simulation. In the real system, both APIs would be called.</p>
                    </div>
                `;
                
                // Restore buttons
                buttons.forEach(btn => {
                    btn.innerHTML = '<i class="fas fa-stop-circle"></i> Terminate & Set Inactive';
                    btn.disabled = false;
                });
            }, 3000);
        }
        
        function testTerminateAPI() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing terminate-class-session API...</div>';
            
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
                            <h5><i class="fas fa-check-circle"></i> Terminate API Test Successful!</h5>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Terminate API Test Failed!</h5>
                            <p><strong>Error:</strong> ${data.message}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Terminate API Test Error!</h5>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            });
        }
        
        function testInactiveAPI() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing set-class-time-inactive API...</div>';
            
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
                            <h5><i class="fas fa-check-circle"></i> Set Inactive API Test Successful!</h5>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Set Inactive API Test Failed!</h5>
                            <p><strong>Error:</strong> ${data.message}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Set Inactive API Test Error!</h5>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            });
        }
        
        function testBothAPIs() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing both APIs simultaneously...</div>';
            
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
                            <h5><i class="fas fa-check-circle"></i> Both APIs Test Successful!</h5>
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
                            <h5><i class="fas fa-exclamation-triangle"></i> API Test Failed!</h5>
                            <p><strong>Error:</strong> ${errorMessage}</p>
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
    </script>
</body>
</html> 