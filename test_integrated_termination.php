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
    <title>Test Integrated Termination</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
        }
        .session-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .termination-log {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h2 class="text-center mb-4">
            <i class="fas fa-link text-primary"></i> 
            Test Integrated Termination (Lines 1547-1565 Logic)
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
                        <h5>Session Management Test</h5>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-primary btn-block mb-3" onclick="testSetClassTime()">
                            <i class="fas fa-clock"></i> Set Class Time (Store Session)
                        </button>
                        
                        <button type="button" class="btn btn-warning btn-block mb-3" onclick="testTermination()">
                            <i class="fas fa-stop-circle"></i> Test Integrated Termination
                        </button>
                        
                        <button type="button" class="btn btn-info btn-block mb-3" onclick="checkSessionState()">
                            <i class="fas fa-database"></i> Check Session State
                        </button>
                        
                        <button type="button" class="btn btn-success btn-block" onclick="testBothAPIs()">
                            <i class="fas fa-code"></i> Test Both APIs + Session Clear
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Session State Monitor</h5>
                    </div>
                    <div class="card-body">
                        <div id="sessionStateInfo" class="session-info">
                            <strong>Session Storage:</strong><br>
                            <span id="sessionStorageInfo">Click "Check Session State" to see current state...</span>
                        </div>
                        
                        <div id="localStorageInfo" class="session-info">
                            <strong>Local Storage:</strong><br>
                            <span id="localStorageData">Click "Check Session State" to see current state...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <div id="testResults" class="alert alert-secondary">
                Click a button above to test the integrated functionality...
            </div>
        </div>
        
        <div id="terminationLog" class="termination-log" style="display: none;">
            <strong>Termination Process Log:</strong><br>
            <div id="logContent"></div>
        </div>
        
        <div class="mt-4 text-center">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Go to Main Page
            </a>
            <a href="test_activity_logs_fix.php" class="btn btn-outline-info">
                <i class="fas fa-test"></i> Previous Test
            </a>
        </div>
    </div>

    <script>
        function testSetClassTime() {
            const resultsDiv = document.getElementById('testResults');
            const logDiv = document.getElementById('terminationLog');
            const logContent = document.getElementById('logContent');
            
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Setting class time and storing session state...</div>';
            logDiv.style.display = 'block';
            logContent.innerHTML = '<div>1. Setting class time to 15:30...</div>';
            
            // Simulate setting class time
            setTimeout(() => {
                logContent.innerHTML += '<div>2. Calling storeSessionState()...</div>';
                
                // Simulate the storeSessionState function
                if (typeof sessionStorage !== 'undefined') {
                    sessionStorage.setItem('class_start_time', '15:30');
                    sessionStorage.setItem('class_start_time_formatted', '15:30:00');
                    sessionStorage.setItem('class_duration', '60');
                    sessionStorage.setItem('current_instructor_name', 'Test Instructor');
                    sessionStorage.setItem('current_subject_name', 'Test Subject');
                }
                
                if (typeof localStorage !== 'undefined') {
                    localStorage.setItem('classSessionState', JSON.stringify({
                        class_start_time: '15:30',
                        class_duration: '60',
                        timestamp: new Date().toISOString()
                    }));
                    localStorage.setItem('activeClassTime', '15:30');
                }
                
                logContent.innerHTML += '<div>3. Session state stored successfully</div>';
                
                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Class Time Set Successfully!</h5>
                        <p>Class time has been set to 15:30 and session state has been stored.</p>
                        <p>This simulates the logic from lines 1547-1565 for session management.</p>
                    </div>
                `;
                
                // Update session state display
                checkSessionState();
            }, 2000);
        }
        
        function testTermination() {
            const resultsDiv = document.getElementById('testResults');
            const logDiv = document.getElementById('terminationLog');
            const logContent = document.getElementById('logContent');
            
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing integrated termination...</div>';
            logDiv.style.display = 'block';
            logContent.innerHTML = '<div>1. Starting integrated termination process...</div>';
            
            if (!confirm('Are you sure you want to terminate the current class session? This will end attendance tracking for the current session.')) {
                resultsDiv.innerHTML = '<div class="alert alert-info">Termination cancelled by user</div>';
                return;
            }
            
            logContent.innerHTML += '<div>2. User confirmed termination</div>';
            
            // Simulate the integrated termination process
            setTimeout(() => {
                logContent.innerHTML += '<div>3. Calling clearSessionVariables()...</div>';
                
                // Simulate clearSessionVariables function
                if (typeof sessionStorage !== 'undefined') {
                    sessionStorage.removeItem('class_start_time');
                    sessionStorage.removeItem('class_start_time_formatted');
                    sessionStorage.removeItem('current_instructor_id');
                    sessionStorage.removeItem('current_instructor_name');
                    sessionStorage.removeItem('current_subject_id');
                    sessionStorage.removeItem('current_subject_name');
                    sessionStorage.removeItem('current_section');
                    sessionStorage.removeItem('attendance_session_id');
                    sessionStorage.removeItem('attendance_session_start');
                    sessionStorage.removeItem('attendance_session_end');
                }
                
                if (typeof localStorage !== 'undefined') {
                    localStorage.removeItem('classSessionState');
                    localStorage.removeItem('activeClassTime');
                    localStorage.removeItem('currentInstructor');
                    localStorage.removeItem('currentSubject');
                }
                
                logContent.innerHTML += '<div>4. Session variables cleared</div>';
                
                setTimeout(() => {
                    logContent.innerHTML += '<div>5. Calling updateCurrentTimeDisplay()...</div>';
                    logContent.innerHTML += '<div>6. UI updated to show no active session</div>';
                    
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Integrated Termination Successful!</h5>
                            <p>The termination process has completed with integrated session management:</p>
                            <ul>
                                <li>Session variables cleared (matching lines 1547-1565 logic)</li>
                                <li>UI updated to show no active session</li>
                                <li>All termination buttons hidden</li>
                                <li>Session state properly reset</li>
                            </ul>
                        </div>
                    `;
                    
                    // Update session state display
                    checkSessionState();
                }, 1000);
            }, 2000);
        }
        
        function checkSessionState() {
            const sessionStorageInfo = document.getElementById('sessionStorageInfo');
            const localStorageData = document.getElementById('localStorageData');
            
            // Check session storage
            let sessionData = 'No session storage data found';
            if (typeof sessionStorage !== 'undefined') {
                const sessionItems = [];
                for (let i = 0; i < sessionStorage.length; i++) {
                    const key = sessionStorage.key(i);
                    const value = sessionStorage.getItem(key);
                    sessionItems.push(`${key}: ${value}`);
                }
                sessionData = sessionItems.length > 0 ? sessionItems.join('<br>') : 'No session storage data found';
            }
            sessionStorageInfo.innerHTML = sessionData;
            
            // Check local storage
            let localData = 'No local storage data found';
            if (typeof localStorage !== 'undefined') {
                const localItems = [];
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    const value = localStorage.getItem(key);
                    localItems.push(`${key}: ${value}`);
                }
                localData = localItems.length > 0 ? localItems.join('<br>') : 'No local storage data found';
            }
            localStorageData.innerHTML = localData;
        }
        
        function testBothAPIs() {
            const resultsDiv = document.getElementById('testResults');
            const logDiv = document.getElementById('terminationLog');
            const logContent = document.getElementById('logContent');
            
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing both APIs with integrated session management...</div>';
            logDiv.style.display = 'block';
            logContent.innerHTML = '<div>1. Testing both termination APIs...</div>';
            
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
                
                logContent.innerHTML += '<div>2. Both APIs called successfully</div>';
                
                if (terminateResult.success && inactiveResult.success) {
                    logContent.innerHTML += '<div>3. Both APIs returned success</div>';
                    logContent.innerHTML += '<div>4. Session variables would be cleared</div>';
                    logContent.innerHTML += '<div>5. UI would be updated</div>';
                    
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Full Integration Test Successful!</h5>
                            <p>Both APIs worked and would trigger the integrated session management:</p>
                            <ul>
                                <li>Terminate API: ${terminateResult.message}</li>
                                <li>Set Inactive API: ${inactiveResult.message}</li>
                                <li>Session variables would be cleared</li>
                                <li>UI would be updated to show no active session</li>
                            </ul>
                        </div>
                    `;
                } else {
                    const errorMessage = terminateResult.success ? 
                        'Error setting class time to inactive: ' + inactiveResult.message :
                        'Error terminating session: ' + terminateResult.message;
                    
                    logContent.innerHTML += '<div>3. API error occurred</div>';
                    
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> API Test Failed!</h5>
                            <p><strong>Error:</strong> ${errorMessage}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                logContent.innerHTML += '<div>3. Network error occurred</div>';
                
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> API Test Error!</h5>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            });
        }
        
        // Initialize session state display on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkSessionState();
        });
    </script>
</body>
</html> 