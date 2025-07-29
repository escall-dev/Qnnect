function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('main');
    sidebar.classList.toggle('active');
    main.classList.toggle('active');
}

function toggleSubMenu(event, element) {
    event.preventDefault();
    const submenu = element.nextElementSibling;
    const arrow = element.querySelector('.arrow');
    if (submenu) {
        submenu.classList.toggle('show');
        arrow.style.transform = submenu.classList.contains('show') ? 'rotate(180deg)' : '';
    }
}

function toggleCourses(year, element) {
    event.preventDefault();
    const courseSubmenu = document.getElementById(year + '-courses');
    const arrow = element.querySelector('.arrow');
    if (courseSubmenu) {
        // Toggle the clicked course submenu
        const isHidden = courseSubmenu.style.display === 'none';
        courseSubmenu.style.display = isHidden ? 'block' : 'none';
        arrow.style.transform = isHidden ? 'rotate(180deg)' : '';
    }
}

// Show loading spinner
function showLoading() {
    document.querySelector('.loading-spinner').style.display = 'block';
    document.querySelector('.overlay').style.display = 'block';
}

// Hide loading spinner
function hideLoading() {
    document.querySelector('.loading-spinner').style.display = 'none';
    document.querySelector('.overlay').style.display = 'none';
}

// Show custom alert
function showAlert(message, type) {
    const alertBox = document.getElementById('customAlert');
    alertBox.textContent = message;
    alertBox.className = 'custom-alert ' + type;
    alertBox.style.display = 'block';

    setTimeout(() => {
        alertBox.style.display = 'none';
    }, 3000);
}

// Add loading spinner to form submissions
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
        showLoading();
    });
});

function updateStudent(id) {
    $("#updateStudentModal").modal("show");

    let updateStudentId = $("#studentID-" + id).text();
    let updateStudentName = $("#studentName-" + id).text();
    let updateStudentCourse = $("#studentCourse-" + id).text();

    $("#updateStudentId").val(updateStudentId);
    $("#updateStudentName").val(updateStudentName);
    $("#updateStudentCourse").val(updateStudentCourse);
}

function deleteStudent(id) {
    const overlay = document.createElement("div");
    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";
    overlay.style.width = "100%";
    overlay.style.height = "100%";
    overlay.style.backgroundColor = "rgba(0, 0, 0, 0.6)";
    overlay.style.zIndex = "9998";
    overlay.style.display = "none";
    document.body.appendChild(overlay);

    const popup = document.createElement("div");
    popup.style.position = "fixed";
    popup.style.top = "50%";
    popup.style.left = "50%";
    popup.style.transform = "translate(-50%, -50%)";
    popup.style.backgroundColor = "#098744";
    popup.style.color = "#fff";
    popup.style.borderRadius = "12px";
    popup.style.padding = "20px";
    popup.style.boxShadow = "0 4px 10px rgba(0, 0, 0, 0.2)";
    popup.style.width = "300px";
    popup.style.textAlign = "center";
    popup.style.zIndex = "9999";
    popup.style.display = "none";
    popup.innerHTML = `
        <p>Are you sure you want to delete this Student from the list?</p>
        <button onclick="confirmDelete(${id})" style="background-color: #fff; color: #098744; border: none; border-radius: 6px; padding: 8px 16px; margin: 10px 5px; cursor: pointer; font-size: 14px;">Yes</button>
        <button onclick="closePopup()" style="background-color: #fff; color: #098744; border: none; border-radius: 6px; padding: 8px 16px; margin: 10px 5px; cursor: pointer; font-size: 14px;">Cancel</button>
    `;
    document.body.appendChild(popup);

    overlay.style.display = "block";
    popup.style.display = "block";

    setTimeout(() => {
        closePopup();
    }, 3000);
}

function confirmDelete(id) {
    window.location = "./endpoint/delete-student.php?student=" + id;
    showSuccessMessage();
}

function showSuccessMessage() {
    const overlay = document.createElement("div");
    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";
    overlay.style.width = "100%";
    overlay.style.height = "100%";
    overlay.style.backgroundColor = "rgba(0, 0, 0, 0.6)";
    overlay.style.zIndex = "9998";
    overlay.style.display = "none";
    document.body.appendChild(overlay);

    const successPopup = document.createElement("div");
    successPopup.style.position = "fixed";
    successPopup.style.top = "50%";
    successPopup.style.left = "50%";
    successPopup.style.transform = "translate(-50%, -50%)";
    successPopup.style.backgroundColor = "#4CAF50";
    successPopup.style.color = "#fff";
    successPopup.style.borderRadius = "12px";
    successPopup.style.padding = "20px";
    successPopup.style.boxShadow = "0 4px 10px rgba(0, 0, 0, 0.2)";
    successPopup.style.width = "300px";
    successPopup.style.textAlign = "center";
    successPopup.style.zIndex = "9999";
    successPopup.style.display = "none";
    document.body.appendChild(successPopup);

    overlay.style.display = "block";
    successPopup.style.display = "block";

    setTimeout(() => {
        successPopup.remove();
        overlay.remove();
    }, 3000);
}

function closePopup() {
    const popup = document.querySelector("div[style*='z-index: 9999']");
    const overlay = document.querySelector("div[style*='z-index: 9998']");
    if (popup) popup.remove();
    if (overlay) overlay.remove();
}

function generateRandomCode(length) {
    const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    let randomString = '';

    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * characters.length);
        randomString += characters.charAt(randomIndex);
    }

    return randomString;
}

function generateQrCode() {
    // Check if face is verified
    if (faceVerified.value !== '1') {
        alert('Please capture your face before generating a QR code.');
        return;
    }
    
    handleQRCodeGeneration();
}

function toggleSubMenu(event) {
    event.preventDefault();
    let submenu = event.target.nextElementSibling;
    let arrow = event.target.querySelector('.arrow');

    if (submenu.classList.contains('show')) {
        submenu.classList.remove('show');
        arrow.classList.remove('rotate');
    } else {
        submenu.classList.add('show');
        arrow.classList.add('rotate');
    }
}