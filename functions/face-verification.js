// Global variables
let video = document.getElementById('faceVideo');
let canvas = document.getElementById('faceCanvas');
let ctx = canvas.getContext('2d');
let startCameraBtn = document.getElementById('startCamera');
let verifyFaceBtn = document.getElementById('verifyFace');
let scanQRBtn = document.getElementById('scanQR');
let studentSelect = document.getElementById('studentSelect');
let statusMessage = document.getElementById('statusMessage');
let verificationResult = document.getElementById('verificationResult');
let successMessage = document.getElementById('successMessage');
let errorMessage = document.getElementById('errorMessage');
let stream = null;
let model = null;
let selectedStudentId = null;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Load BlazeFace model
    loadFaceDetectionModel();
    
    // Event listeners
    startCameraBtn.addEventListener('click', startCamera);
    verifyFaceBtn.addEventListener('click', verifyFace);
    scanQRBtn.addEventListener('click', proceedToQRScan);
    studentSelect.addEventListener('change', handleStudentSelection);
    
    // Clean up when page is unloaded
    window.addEventListener('beforeunload', stopCamera);
});

// Load face detection model
async function loadFaceDetectionModel() {
    try {
        showLoading();
        model = await blazeface.load();
        console.log('Face detection model loaded successfully');
        hideLoading();
    } catch (error) {
        console.error('Error loading face detection model:', error);
        statusMessage.textContent = 'Error loading face detection model. Please refresh the page.';
        statusMessage.style.color = 'red';
        hideLoading();
    }
}

// Handle student selection
function handleStudentSelection() {
    selectedStudentId = studentSelect.value;
    if (selectedStudentId) {
        startCameraBtn.disabled = false;
        statusMessage.textContent = 'Student selected. Click "Start Camera" to begin.';
    } else {
        startCameraBtn.disabled = true;
        statusMessage.textContent = 'Please select a student first.';
    }
}

// Start camera
async function startCamera() {
    if (!selectedStudentId) {
        statusMessage.textContent = 'Please select a student first.';
        return;
    }
    
    try {
        showLoading();
        
        console.log('Attempting to access camera...');
        
        // Check if browser supports getUserMedia
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Browser does not support camera access. Please use a modern browser like Chrome, Firefox, or Edge.');
        }
        
        // List available devices for debugging
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');
        console.log('Available video devices:', videoDevices);
        
        if (videoDevices.length === 0) {
            throw new Error('No video devices (cameras) found. Please connect a camera and try again.');
        }
        
        // Request camera access with specific constraints for better performance
        // Try with simpler constraints first
        stream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: false
        });
        
        console.log('Camera access granted:', stream);
        
        // Set video source
        video.srcObject = stream;
        
        // Set canvas dimensions to match video
        video.onloadedmetadata = () => {
            console.log('Video metadata loaded. Dimensions:', video.videoWidth, 'x', video.videoHeight);
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
        };
        
        // Make sure video plays
        video.onloadeddata = () => {
            console.log('Video data loaded. Playing video...');
            video.play().catch(e => console.error('Error playing video:', e));
        };
        
        // Update UI
        startCameraBtn.disabled = true;
        verifyFaceBtn.disabled = false;
        statusMessage.textContent = 'Camera started. Position your face within the circle and click "Verify Face".';
        
        // Start face detection
        detectFace();
        
        hideLoading();
    } catch (error) {
        console.error('Error accessing camera:', error);
        
        // Provide more specific error messages based on the error
        let errorMessage = 'Error accessing camera. ';
        
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            errorMessage += 'Camera access was denied. Please allow camera access in your browser settings.';
        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            errorMessage += 'No camera found. Please connect a camera and try again.';
        } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
            errorMessage += 'Camera is in use by another application. Please close other applications using the camera.';
        } else if (error.name === 'OverconstrainedError') {
            errorMessage += 'Camera constraints not satisfied. Please try with a different camera.';
        } else if (error.name === 'TypeError') {
            errorMessage += 'Invalid constraints or parameters. Please refresh the page and try again.';
        } else {
            errorMessage += error.message || 'Please check permissions and try again.';
        }
        
        statusMessage.textContent = errorMessage;
        statusMessage.style.color = 'red';
        hideLoading();
        
        // Add a button to retry
        const retryButton = document.createElement('button');
        retryButton.className = 'btn btn-warning mt-2';
        retryButton.innerHTML = '<i class="fas fa-redo"></i> Retry Camera Access';
        retryButton.onclick = () => {
            retryButton.remove();
            startCamera();
        };
        
        // Add the retry button after the status message
        statusMessage.parentNode.appendChild(retryButton);
    }
}

// Detect face in video stream
async function detectFace() {
    if (!model || !video.srcObject) return;
    
    try {
        // Get predictions from the model
        const predictions = await model.estimateFaces(video, false);
        
        if (predictions.length > 0) {
            // Face detected
            statusMessage.textContent = 'Face detected! You can now click "Verify Face".';
            statusMessage.style.color = 'green';
        } else {
            // No face detected
            statusMessage.textContent = 'No face detected. Please position your face within the circle.';
            statusMessage.style.color = 'red';
        }
        
        // Continue detection if stream is active
        if (video.srcObject) {
            requestAnimationFrame(detectFace);
        }
    } catch (error) {
        console.error('Error during face detection:', error);
    }
}

// Verify face
async function verifyFace() {
    if (!video.srcObject || !selectedStudentId) return;
    
    try {
        showLoading();
        
        // Draw current video frame to canvas
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Get image data as base64 string
        const imageData = canvas.toDataURL('image/jpeg');
        
        // Simulate verification with the server
        // In a real implementation, you would send the image to the server for comparison
        const verificationSuccess = await simulateVerification(selectedStudentId, imageData);
        
        // Show verification result
        verificationResult.style.display = 'block';
        
        if (verificationSuccess) {
            // Success
            successMessage.style.display = 'block';
            errorMessage.style.display = 'none';
            scanQRBtn.disabled = false;
            
            // Store verification in session
            storeVerificationInSession(selectedStudentId);
        } else {
            // Failure
            successMessage.style.display = 'none';
            errorMessage.style.display = 'block';
            scanQRBtn.disabled = true;
        }
        
        hideLoading();
    } catch (error) {
        console.error('Error during face verification:', error);
        statusMessage.textContent = 'Error during verification. Please try again.';
        statusMessage.style.color = 'red';
        hideLoading();
    }
}

// Simulate verification (replace with actual server verification)
async function simulateVerification(studentId, imageData) {
    // In a real implementation, you would send the image to the server
    // and compare it with the stored face image for the student
    
    // For demo purposes, we'll simulate a successful verification
    return new Promise((resolve) => {
        setTimeout(() => {
            // Simulate 80% success rate
            const success = Math.random() < 0.8;
            resolve(success);
        }, 1500); // Simulate server delay
    });
}

// Store verification in session
function storeVerificationInSession(studentId) {
    // Send verification data to server to store in session
    fetch('./endpoint/store-verification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `student_id=${studentId}&verified=1`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Verification stored:', data);
    })
    .catch(error => {
        console.error('Error storing verification:', error);
    });
}

// Proceed to QR scan
function proceedToQRScan() {
    // Redirect to index.php for QR scanning
    window.location.href = 'index.php';
}

// Stop camera
function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
    video.srcObject = null;
}

// Show loading spinner
function showLoading() {
    document.querySelector('.loading-spinner').style.display = 'flex';
}

// Hide loading spinner
function hideLoading() {
    document.querySelector('.loading-spinner').style.display = 'none';
}

// Toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('main');
    sidebar.classList.toggle('active');
    main.classList.toggle('active');
} 