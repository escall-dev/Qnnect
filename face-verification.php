<?php
require_once 'includes/asset_helper.php';
session_start();

// Clear any existing verification status when starting fresh
unset($_SESSION['face_verified']);
unset($_SESSION['verified_student_id']);
unset($_SESSION['verification_time']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Verification - QR Code Attendance System</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/masterlist.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?php echo asset_url('css/all.min.css'); ?>">
    
    <style>
        /* Main content styles - matching analytics.css */
        .main {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease;
            width: calc(100% - 260px);
            display: block !important; /* Override masterlist.css */
            height: auto !important; /* Override masterlist.css */
            justify-content: flex-start !important; /* Override masterlist.css */
            align-items: flex-start !important; /* Override masterlist.css */
        }

        /* When sidebar is closed */
        .sidebar.close ~ .main {
            margin-left: 78px;
            width: calc(100% - 78px);
        }

        /* Hamburger menu rotation */
        .sidebar-toggle {
            transition: transform 0.3s ease;
        }

        .sidebar-toggle.rotate {
            transform: rotate(180deg);
        }

        /* Face verification container styles - matching masterlist.php */
        .face-verification-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            margin: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            width: calc(100% - 40px);
            transition: all 0.3s ease;
        }

        .main.active .face-verification-container {
            width: calc(100% - 40px);
            margin: 20px;
        }

        .face-verification-content {
            background-color: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        .title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .title h4 {
            margin: 0;
            color: #098744;
        }
        
        /* Card styles */
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px;
            overflow: hidden;
            background-color: #fff;
        }
        
        .card-header {
            background-color: #098744;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
            border-bottom: none;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Camera container styles */
        .camera-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            border: 3px solid #098744;
            border-radius: 10px;
            overflow: hidden;
            padding-top: 40px; /* Add padding on top for the heading */
        }
        
        .camera-container h5 {
            position: absolute;
            top: 10px;
            left: 0;
            width: 100%;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.7);
            padding: 5px 0;
            margin: 0;
            z-index: 5;
        }
        
        .camera-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 2px dashed #fff;
            box-sizing: border-box;
            pointer-events: none;
        }
        
        .face-outline {
            position: absolute;
            top: 55%; /* Move down slightly from center */
            left: 50%;
            transform: translate(-50%, -50%);
            width: 180px; /* Slightly smaller */
            height: 180px; /* Slightly smaller */
            border: 2px dashed #fff;
            border-radius: 50%;
            box-sizing: border-box;
            pointer-events: none;
            z-index: 4;
        }
        
        #faceVideo {
            width: 100%;
            height: auto;
            display: block;
        }
        
        #faceCanvas {
            display: none;
        }
        
        .status-container {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            background-color: #f8f9fa;
            text-align: center;
        }
        
        .verification-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .verification-result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            display: none;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .student-search {
            margin-bottom: 20px;
        }
        
        /* Button styles */
        .btn-primary {
            background-color: #098744;
            border-color: #098744;
        }
        
        .btn-primary:hover {
            background-color: #076a34;
            border-color: #076a34;
        }
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        
        /* Loading spinner styles */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #098744;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive styles */
        @media (max-width: 1200px) {
            .main.collapsed {
                margin-left: 200px;
                width: calc(100% - 200px);
            }
            .main.active {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
        }

        @media (max-width: 992px) {
            .main.collapsed {
                margin-left: 180px;
                width: calc(100% - 180px);
            }
            .main.active {
                margin-left: 50px;
                width: calc(100% - 50px);
            }
        }

        @media (max-width: 768px) {
            .main, .main.active {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .face-verification-container {
                margin: 10px;
                width: calc(100% - 20px);
            }
            
            .face-verification-content {
                padding: 15px;
            }
            
            .card {
                padding: 15px;
                margin: 10px;
            }
        }

        @media (max-width: 576px) {
            .main {
                padding: 10px;
            }
            
            .face-verification-container {
                margin: 10px;
            }
            
            .face-verification-content {
                padding: 10px;
            }
        }

        .attendance-container {
            padding: 20px;
            margin: 0;
            width: 100%;
        }

        .qr-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        .camera-container {
            position: relative;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            border: 3px solid #098744;
            border-radius: 10px;
            overflow: hidden;
        }

        .verification-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .verification-buttons button {
            width: 100%;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .attendance-container {
                flex-direction: column;
            }
            
            .qr-container, .col-8 {
                width: 100%;
                max-width: 100%;
                flex: 0 0 100%;
            }
        }

        #successMessage h4 {
            color: #098744;
        }
        #successMessage i.fa-check-circle {
            color: #098744;
        }
        .verification-result .card-body {
            padding: 20px;
        }
        #successMessage h4 i {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading-spinner">
        <div class="spinner"></div>
    </div>

    <!-- Overlay -->
    <div class="overlay"></div>

    <!-- Custom Alert Box -->
    <div id="customAlert" class="custom-alert"></div>

  
    <?php include('./components/sidebar-nav.php'); ?>
    
    
    <div class="main" id="main">
        <div class="face-verification-container">
            <div class="face-verification-content">
                <div class="title">
                    <h4><i class="fas fa-user-check"></i> Face Verification</h4>
                </div>
                <div class="attendance-container row">
                    <!-- Left Side - Camera Section -->
                    <div class="qr-container col-4">
                        <div class="camera-container">
                            <h5 class="text-center">Position your face within the circle</h5>
                            <video id="faceVideo" autoplay playsinline></video>
                            <canvas id="faceCanvas"></canvas>
                            <div class="camera-overlay">
                                <div class="face-outline"></div>
                            </div>
                        </div>
                        
                        <div class="verification-buttons mt-3">
                            <button id="startCamera" class="btn btn-primary">
                                <i class="fas fa-video"></i> Start Camera
                            </button>
                            <button id="verifyFace" class="btn btn-success" disabled>
                                <i class="fas fa-check-circle"></i> Verify Face
                            </button>
                            <button id="scanQR" class="btn btn-info" disabled onclick="window.location.href=' <?php echo $baseUrl; ?>index.php'">
                                <i class="fas fa-qrcode"></i> Proceed to QR Scan
                            </button>
                        </div>

                        <div class="status-container">
                            <p id="statusMessage">Please select a student and position your face within the circle</p>
                        </div>
                    </div>

                    <!-- Right Side - Student Selection and Instructions -->
                    <div class="col-8">
                        <!-- Student Selection Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-user"></i> Student Selection
                            </div>
                            <div class="card-body">
                                <div class="student-search">
                                    <div class="form-group">
                                        <label for="studentSelect"><i class="fas fa-search"></i> Select Student:</label>
                                        <select class="form-control" id="studentSelect">
                                            <option value="">-- Select a student --</option>
                                            <?php
                                            include('./conn/conn.php');
                                            try {
                                                // Get user's school_id and user_id from session
                                                $school_id = $_SESSION['school_id'] ?? 1;
                                                $user_id = $_SESSION['user_id'] ?? 1;
                                                
                                                $stmt = $conn->prepare("SELECT tbl_student_id, student_name FROM tbl_student 
                                                                        WHERE school_id = :school_id AND user_id = :user_id 
                                                                        ORDER BY student_name");
                                                $stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
                                                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                                                $stmt->execute();
                                                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                foreach ($students as $student) {
                                                    echo "<option value='" . $student['tbl_student_id'] . "'>" . $student['student_name'] . "</option>";
                                                }
                                            } catch(PDOException $e) {
                                                echo "<option value=''>Error loading students</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Result Card -->
                        <div id="verificationResult" class="card mb-4 verification-result">
                            <div class="card-body">
                                <div id="successMessage" style="display: none;">
                                    <h4 style="color: #098744;"><i class="fas fa-check-circle" style="color: #098744;"></i> Face Verified Successfully!</h4>
                                    <p>You can now proceed to scan your QR code for attendance.</p>
                                </div>
                                <div id="errorMessage" style="display: none;">
                                    <h4><i class="fas fa-times-circle"></i> Face Verification Failed!</h4>
                                    <p>We couldn't verify your identity. Please try again or contact an administrator.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Instructions Card -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-info-circle"></i> Instructions
                            </div>
                            <div class="card-body">
                                <ol>
                                    <li>Select your name from the dropdown menu above</li>
                                    <li>Click "Start Camera" to activate your webcam</li>
                                    <li>Position your face within the circle</li>
                                    <li>Click "Verify Face" to authenticate your identity</li>
                                    <li>After successful verification, click "Proceed to QR Scan" to record your attendance</li>
                                </ol>
                                <div class="alert alert-info">
                                    <i class="fas fa-lightbulb"></i> Tip: Make sure you are in a well-lit area and looking directly at the camera for best results.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?php echo asset_url('js/jquery-3.6.0.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/popper.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.min.js'); ?>"></script>
    
    <!-- Face API JS -->
    <script src="<?php echo asset_url('js/tfjs'); ?>"></script>
    <script src="<?php echo asset_url('js/blazeface'); ?>"></script>
    
    <script src="./functions/face-verification.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.querySelector('.bx-menu');
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const sidebar = document.querySelector('.sidebar');
                    sidebar.classList.toggle('close');
                });
            }
        });

        let video = document.getElementById('faceVideo');
        let canvas = document.getElementById('faceCanvas');
        let startButton = document.getElementById('startCamera');
        let verifyButton = document.getElementById('verifyFace');
        let stream = null;
        let model = null;

        // Load BlazeFace model
        async function loadFaceDetectionModel() {
            try {
                model = await blazeface.load();
                console.log('Face detection model loaded successfully');
            } catch (error) {
                console.error('Error loading face detection model:', error);
                showMessage('Error loading face detection model. Please refresh the page.', 'error');
            }
        }

        // Initialize face detection
        loadFaceDetectionModel();

        // Start camera function
        async function startCamera() {
            try {
                // Request camera access with specific constraints
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    }
                });

                // Set video source and play
                video.srcObject = stream;
                await video.play();

                // Mirror the video display
                video.style.transform = 'scaleX(-1)';

                // Enable verify button and start face detection
                verifyButton.disabled = false;
                startButton.disabled = true;
                
                // Start continuous face detection
                detectFace();

                showMessage('Camera started successfully. Position your face within the circle.', 'success');
            } catch (error) {
                console.error('Error accessing camera:', error);
                showMessage('Error accessing camera. Please check camera permissions.', 'error');
                startButton.disabled = false;
            }
        }

        // Add click event listener to start camera button
        startButton.addEventListener('click', startCamera);

        // Function to detect face
        async function detectFace() {
            if (!model || !video.srcObject) return;

            try {
                const predictions = await model.estimateFaces(video, false);
                
                if (predictions.length > 0) {
                    showMessage('Face detected! You can now verify.', 'success');
                    verifyButton.disabled = false;
                } else {
                    showMessage('No face detected. Please position your face within the circle.', 'warning');
                    verifyButton.disabled = true;
                }

                // Continue detection if stream is active
                if (video.srcObject) {
                    requestAnimationFrame(detectFace);
                }
            } catch (error) {
                console.error('Error during face detection:', error);
                showMessage('Error during face detection. Please try again.', 'error');
            }
        }

        // Helper function to show messages
        function showMessage(message, type) {
            const statusElement = document.getElementById('statusMessage');
            if (!statusElement) return;

            statusElement.textContent = message;
            statusElement.className = 'alert alert-' + (type || 'info');
        }

        // Clean up resources when page is unloaded
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html> 