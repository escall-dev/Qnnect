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
    <title>Test Termination</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test Termination Button</h2>
        
        <div class="alert alert-info">
            <strong>Current Session:</strong><br>
            Class Time: <?= $_SESSION['class_start_time'] ?? 'Not set' ?><br>
            Instructor: <?= $_SESSION['current_instructor_name'] ?? 'Not set' ?><br>
            Subject: <?= $_SESSION['current_subject_name'] ?? 'Not set' ?>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h4>Header Termination Button</h4>
                <button type="button" id="headerTerminateBtn" class="btn btn-danger btn-lg" onclick="testTermination()">
                    <i class="fas fa-stop-circle"></i> Terminate Session
                </button>
            </div>
            <div class="col-md-6">
                <h4>Regular Termination Button</h4>
                <button type="button" id="terminateClassSession" class="btn btn-danger btn-block" onclick="testTermination()">
                    <i class="fas fa-stop-circle"></i> Terminate Current Session
                </button>
            </div>
        </div>
        
        <div class="mt-4">
            <div id="testResults" class="alert alert-secondary">
                Click a button to test termination...
            </div>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Go to Main Page</a>
            <a href="logout.php" class="btn btn-outline-danger">Test Logout</a>
        </div>
    </div>

    <script>
        function testTermination() {
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Testing termination...</div>';
            
            if (!confirm('Are you sure you want to terminate the current class session?')) {
                resultsDiv.innerHTML = '<div class="alert alert-info">Termination cancelled</div>';
                return;
            }
            
            // Show loading state
            const buttons = document.querySelectorAll('#headerTerminateBtn, #terminateClassSession');
            buttons.forEach(btn => {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Terminating...';
                btn.disabled = true;
            });
            
            // Simulate API call
            setTimeout(() => {
                resultsDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Termination test successful!</div>';
                
                // Restore buttons
                buttons.forEach(btn => {
                    btn.innerHTML = '<i class="fas fa-stop-circle"></i> Terminate Session';
                    btn.disabled = false;
                });
            }, 2000);
        }
    </script>
</body>
</html> 