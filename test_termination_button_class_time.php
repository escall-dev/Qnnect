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
    <title>Test Termination Button - Class Time</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .class-time-setting {
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            background-color: #f8fff9;
        }
        #classTimeTerminateBtn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
            border: 2px solid #dc3545;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: pulse-red 2s infinite;
        }
        
        #classTimeTerminateBtn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(220, 53, 69, 0.4);
            background-color: #c82333;
            border-color: #c82333;
            animation: none;
        }
        
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h2 class="text-center mb-4">
            <i class="fas fa-stop-circle text-danger"></i> 
            Test Termination Button - Class Time Set
        </h2>
        
        <div class="alert alert-info">
            <h5>Current Session Status</h5>
            <strong>Class Start Time:</strong> <?= $_SESSION['class_start_time'] ?? 'Not set' ?><br>
            <strong>Instructor:</strong> <?= $_SESSION['current_instructor_name'] ?? 'Not set' ?><br>
            <strong>Subject:</strong> <?= $_SESSION['current_subject_name'] ?? 'Not set' ?><br>
            <strong>School ID:</strong> <?= $_SESSION['school_id'] ?? 'Not set' ?>
        </div>
        
        <!-- Simulate Class Time Settings Section -->
        <div class="class-time-setting">
            <h5><i class="fas fa-clock"></i> Class Time Settings</h5>
            
            <!-- Current Time Settings Display -->
            <div id="currentTimeSettings" class="alert alert-info py-2 mb-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle text-success mr-2"></i>
                    <div>
                        <strong class="d-block">Current Class Time:</strong>
                        <span id="displayedStartTime" class="font-weight-bold">
                            <?= isset($_SESSION['class_start_time']) ? date('h:i A', strtotime($_SESSION['class_start_time'])) : '08:00 AM' ?>
                        </span>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> Attendance after this time will be marked as "Late"
                        </small>
                        <small class="text-success d-block mt-1 font-weight-bold">
                            <i class="fas fa-check"></i> Time is active and being used for attendance
                        </small>
                    </div>
                </div>
                
                <!-- Termination Button - Only shown when class time is set -->
                <div id="terminationButtonContainer" class="mt-3">
                    <button type="button" id="classTimeTerminateBtn" class="btn btn-danger btn-block" onclick="testTermination()">
                        <i class="fas fa-stop-circle"></i> Terminate Class Session
                    </button>
                    <small class="text-muted mt-2 d-block text-center">
                        <i class="fas fa-info-circle"></i> Click to end the current class session and clear all settings
                    </small>
                </div>
            </div>
            
            <!-- Class Time Form -->
            <form id="classTimeForm">
                <div class="form-group mb-3">
                    <label for="classStartTime" class="form-label">Start Time:</label>
                    <div class="input-group">
                        <input type="time" class="form-control" id="classStartTime" name="classStartTime" value="<?= $_SESSION['class_start_time'] ?? '08:00' ?>" required>
                        <div class="input-group-append">
                            <button type="button" id="setClassTime" class="btn btn-success" onclick="setClassTime()">
                                <i class="fas fa-save mr-1"></i> Set
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="mt-4">
            <div id="testResults" class="alert alert-secondary">
                Click the termination button above to test the functionality...
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
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing termination...</div>';
            
            if (!confirm('Are you sure you want to terminate the current class session? This will end attendance tracking for the current session.')) {
                resultsDiv.innerHTML = '<div class="alert alert-info">Termination cancelled by user</div>';
                return;
            }
            
            // Show loading state
            const terminateBtn = document.getElementById('classTimeTerminateBtn');
            const originalText = terminateBtn.innerHTML;
            terminateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Terminating...';
            terminateBtn.disabled = true;
            
            // Simulate API calls
            setTimeout(() => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Termination Test Successful!</h5>
                        <p><strong>What happened:</strong></p>
                        <ul>
                            <li>Class time termination button was clicked</li>
                            <li>Session variables would be cleared</li>
                            <li>Class time settings would be set to inactive</li>
                            <li>UI would be reset to no-active-session state</li>
                        </ul>
                        <p><strong>Note:</strong> This is a simulation. In the real system, the termination APIs would be called.</p>
                    </div>
                `;
                
                // Hide the termination button
                const terminationButtonContainer = document.getElementById('terminationButtonContainer');
                if (terminationButtonContainer) {
                    terminationButtonContainer.style.display = 'none';
                }
                
                // Restore button
                terminateBtn.innerHTML = originalText;
                terminateBtn.disabled = false;
            }, 3000);
        }
        
        function setClassTime() {
            const timeInput = document.getElementById('classStartTime');
            const time = timeInput.value;
            
            if (!time) {
                alert('Please select a time');
                return;
            }
            
            // Show loading state
            const setBtn = document.getElementById('setClassTime');
            const originalText = setBtn.innerHTML;
            setBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Setting...';
            setBtn.disabled = true;
            
            // Simulate setting class time
            setTimeout(() => {
                // Update displayed time
                const displayedTime = document.getElementById('displayedStartTime');
                if (displayedTime) {
                    const timeObj = new Date(`2024-01-01 ${time}`);
                    displayedTime.textContent = timeObj.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                }
                
                // Show termination button
                const terminationButtonContainer = document.getElementById('terminationButtonContainer');
                if (terminationButtonContainer) {
                    terminationButtonContainer.style.display = 'block';
                }
                
                // Show success message
                const resultsDiv = document.getElementById('testResults');
                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Class Time Set Successfully!</h5>
                        <p>Class time has been set to ${time}. The termination button should now be visible.</p>
                    </div>
                `;
                
                // Restore button
                setBtn.innerHTML = originalText;
                setBtn.disabled = false;
            }, 2000);
        }
        
        // Check if termination button should be shown on page load
        document.addEventListener('DOMContentLoaded', function() {
            const classTime = '<?= $_SESSION['class_start_time'] ?? '' ?>';
            if (classTime) {
                console.log('Class time is set, termination button should be visible');
                const terminationButtonContainer = document.getElementById('terminationButtonContainer');
                if (terminationButtonContainer) {
                    terminationButtonContainer.style.display = 'block';
                }
            } else {
                console.log('No class time set, termination button should be hidden');
                const terminationButtonContainer = document.getElementById('terminationButtonContainer');
                if (terminationButtonContainer) {
                    terminationButtonContainer.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html> 