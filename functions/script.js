function printAttendance() {
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page') || 1;
    window.open('print-attendance.php?page=' + page, '_blank');
}
   
// Function to toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('main');
    const toggleButton = document.querySelector('.sidebar-toggle');
    
    // Add animation class before toggling
    document.body.classList.add('sidebar-transition');
    
    // Toggle classes
    sidebar.classList.toggle('active');
    main.classList.toggle('active');
    toggleButton.classList.toggle('rotate');
    
    // If needed, adjust column widths when sidebar is toggled
    const qrContainer = document.querySelector('.qr-container');
    const attendanceList = document.querySelector('.attendance-list');
    
    if (qrContainer && attendanceList) {
        if (sidebar.classList.contains('active')) {
            // Sidebar is minimized - give more space to attendance list
            setTimeout(() => {
                qrContainer.classList.remove('col-4');
                qrContainer.classList.add('col-3');
                attendanceList.classList.remove('col-7');
                attendanceList.classList.add('col-8');
            }, 150); // Slight delay to make animation smoother
        } else {
            // Sidebar is expanded - revert to original layout
            setTimeout(() => {
                qrContainer.classList.remove('col-3');
                qrContainer.classList.add('col-4');
                attendanceList.classList.remove('col-8');
                attendanceList.classList.add('col-7');
            }, 150); // Slight delay to make animation smoother
        }
    }
    
    // Remove animation class after transition completes
    setTimeout(() => {
        document.body.classList.remove('sidebar-transition');
    }, 300);
}

// Function to show custom alert
function showCustomAlert(message, type) {
    const alertBox = document.getElementById('customAlert');
    alertBox.textContent = message;
    alertBox.className = `custom-alert ${type}`; // Add class based on type
    alertBox.style.display = 'block';

    // Hide the alert after 3 seconds
    setTimeout(() => {
        alertBox.style.display = 'none';
    }, 3000);
}

// Start the scanner
let scanner;
let lastScannedQR = null;

function startScanner() {
    scanner = new Instascan.Scanner({ 
        video: document.getElementById('interactive'),
        mirror: true, // Enable mirroring
        scanPeriod: 5 // Reduce scan frequency to improve performance
    });

    scanner.addListener('scan', function (content) {
        if (lastScannedQR === content) {
            alert("This QR code has already been scanned for Time In. Please scan again for Time Out.");
            return;
        }

        lastScannedQR = content;
        $("#detected-qr-code").val(content);
        console.log("QR Code Detected: ", content);
        document.querySelector(".qr-detected-container form").submit();
        scanner.stop();
        document.querySelector(".scanner-con").style.display = "none";
        document.querySelector(".qr-detected-container").style.display = "block";
    });

    Instascan.Camera.getCameras().then(function (cameras) {
        if (cameras.length > 0) {
            // Try to use front camera first for better mirroring experience
            const frontCamera = cameras.find(camera => camera.name.toLowerCase().includes('front') || camera.name.toLowerCase().includes('user'));
            const selectedCamera = frontCamera || cameras[0];
            scanner.start(selectedCamera);
        } else {
            console.error("No cameras found.");
            alert("No cameras found. Please connect a camera and try again.");
        }
    }).catch(function (e) {
        console.error(e);
        alert("An error occurred while accessing the camera: " + e.message);
    });
}

// Initialize sidebar toggle when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add click event to sidebar toggle button
    const toggleButton = document.querySelector('.sidebar-toggle');
    if (toggleButton) {
        toggleButton.addEventListener('click', toggleSidebar);
        console.log('Toggle button click event attached');
    } else {
        console.error('Toggle button not found');
    }
});

// Start the scanner when the page loads
window.onload = function () {
    startScanner();
};

