<?php
// Comprehensive debug script for terminate button
session_start();

// Force set an active session
$_SESSION['class_start_time'] = '14:30';
$_SESSION['class_start_time_formatted'] = '14:30:00';
$_SESSION['current_instructor_name'] = 'John Doe';
$_SESSION['current_subject_name'] = 'Web Development';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Terminate Button</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>🔍 Debug Terminate Button</h2>
        
        <div class='alert alert-info'>
            <h4>Current Session State:</h4>
            <p><strong>Class Start Time:</strong> " . ($_SESSION['class_start_time'] ?? 'Not set') . "</p>
            <p><strong>Instructor:</strong> " . ($_SESSION['current_instructor_name'] ?? 'Not set') . "</p>
            <p><strong>Subject:</strong> " . ($_SESSION['current_subject_name'] ?? 'Not set') . "</p>
        </div>

        <div class='card'>
            <div class='card-header'>
                <h5>Active Class Session (Simulated)</h5>
            </div>
            <div class='card-body'>
                <div class='alert alert-success'>
                    <h6><i class='fas fa-clock'></i> Active Class Session</h6>
                    <div class='row'>
                        <div class='col-md-6'>
                            <strong>Start Time:</strong> " . date('h:i A', strtotime($_SESSION['class_start_time'])) . "
                        </div>
                        <div class='col-md-6'>
                            <strong>Status:</strong> <span class='badge badge-success'>Active</span>
                        </div>
                    </div>
                    <div class='row mt-2'>
                        <div class='col-md-6'>
                            <strong>Instructor:</strong> " . htmlspecialchars($_SESSION['current_instructor_name']) . "
                        </div>
                        <div class='col-md-6'>
                            <strong>Subject:</strong> " . htmlspecialchars($_SESSION['current_subject_name']) . "
                        </div>
                    </div>
                    <div class='row mt-3'>
                        <div class='col-12'>
                            <button type='button' id='terminateClassSession' class='btn btn-danger btn-sm'>
                                <i class='fas fa-stop-circle'></i> Terminate Session
                            </button>
                            <button type='button' onclick='testDirectCall()' class='btn btn-warning btn-sm ml-2'>
                                <i class='fas fa-bug'></i> Test Direct Call
                            </button>
                            <button type='button' onclick='testEventListeners()' class='btn btn-info btn-sm ml-2'>
                                <i class='fas fa-list'></i> Test Event Listeners
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class='card mt-3'>
            <div class='card-header'>
                <h5>Debug Console</h5>
            </div>
            <div class='card-body'>
                <div id='debugOutput' class='bg-dark text-light p-3' style='height: 200px; overflow-y: scroll; font-family: monospace;'>
                    <div>Debug output will appear here...</div>
                </div>
            </div>
        </div>

        <div class='card mt-3'>
            <div class='card-header'>
                <h5>Test Results</h5>
            </div>
            <div class='card-body'>
                <div id='testResults'></div>
            </div>
        </div>
    </div>

    <script>
        // Debug logging function
        function debugLog(message) {
            const debugOutput = document.getElementById('debugOutput');
            const timestamp = new Date().toLocaleTimeString();
            debugOutput.innerHTML += '<div>[' + timestamp + '] ' + message + '</div>';
            debugOutput.scrollTop = debugOutput.scrollHeight;
            console.log(message);
        }

        // Test direct function call
        function testDirectCall() {
            debugLog('=== Testing Direct Function Call ===');
            debugLog('Checking if terminateClassSession function exists...');
            
            if (typeof terminateClassSession === 'function') {
                debugLog('✅ terminateClassSession function exists');
                debugLog('Calling terminateClassSession() directly...');
                terminateClassSession();
            } else {
                debugLog('❌ terminateClassSession function NOT found');
                debugLog('Available global functions: ' + Object.keys(window).filter(key => typeof window[key] === 'function').slice(0, 10).join(', '));
            }
        }

        // Test event listeners
        function testEventListeners() {
            debugLog('=== Testing Event Listeners ===');
            
            const button = document.getElementById('terminateClassSession');
            debugLog('Looking for button with ID: terminateClassSession');
            debugLog('Button found: ' + (button ? 'YES' : 'NO'));
            
            if (button) {
                debugLog('Button text: ' + button.textContent);
                debugLog('Button classes: ' + button.className);
                debugLog('Button onclick: ' + button.onclick);
                
                // Check if event listener is attached
                const events = getEventListeners(button);
                debugLog('Event listeners on button: ' + JSON.stringify(events));
                
                // Try to manually trigger click
                debugLog('Manually triggering button click...');
                button.click();
            }
        }

        // Function to get event listeners (simplified)
        function getEventListeners(element) {
            const events = {};
            // This is a simplified version - in real browsers you'd need dev tools
            events.click = 'Unknown (use browser dev tools)';
            return events;
        }

        // Define the terminate function here for testing
        function terminateClassSession() {
            debugLog('=== terminateClassSession() called ===');
            
            if (!confirm('Are you sure you want to terminate the current class session? This will end attendance tracking for the current session.')) {
                debugLog('User cancelled termination');
                return;
            }
            
            debugLog('User confirmed termination');
            
            // Show loading state
            const terminateBtn = document.getElementById('terminateClassSession');
            if (terminateBtn) {
                const originalText = terminateBtn.innerHTML;
                terminateBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Terminating...';
                terminateBtn.disabled = true;
                
                debugLog('Button state updated to loading');
                
                // Simulate API call
                setTimeout(() => {
                    debugLog('Simulating API call to terminate session...');
                    
                    // For testing, just show success
                    const testResults = document.getElementById('testResults');
                    testResults.innerHTML = '<div class=\"alert alert-success\">✅ Session terminated successfully (simulated)</div>';
                    
                    // Reset button
                    terminateBtn.innerHTML = originalText;
                    terminateBtn.disabled = false;
                    
                    debugLog('Termination completed (simulated)');
                }, 2000);
            } else {
                debugLog('❌ Terminate button not found during execution');
            }
        }

        // Auto-run tests when page loads
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('=== Page Loaded ===');
            debugLog('DOM ready, running initial tests...');
            
            // Test 1: Check if button exists
            const button = document.getElementById('terminateClassSession');
            debugLog('Button exists: ' + (button ? 'YES' : 'NO'));
            
            // Test 2: Check if function exists
            debugLog('Function exists: ' + (typeof terminateClassSession === 'function' ? 'YES' : 'NO'));
            
            // Test 3: Try to attach event listener
            if (button) {
                try {
                    button.addEventListener('click', function() {
                        debugLog('🎉 Event listener triggered!');
                        terminateClassSession();
                    });
                    debugLog('✅ Event listener attached successfully');
                } catch (error) {
                    debugLog('❌ Error attaching event listener: ' + error.message);
                }
            }
            
            debugLog('Initial tests completed');
        });
    </script>
</body>
</html>";
?> 