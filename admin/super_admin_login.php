<?php
define('SUPER_ADMIN_CONTEXT', true);
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once 'database.php';
require_once 'functions/log_functions.php';

// Enforce PIN verification gate with TTL and force override
$force_pin = isset($_GET['force_pin']) && $_GET['force_pin'] == '1';
$pin_ok = !empty($_SESSION['superadmin_pin_verified']);
$pin_age_ok = true;
if (isset($_SESSION['superadmin_pin_verified_at'])) {
    // Re-prompt after 15 minutes
    $pin_age_ok = (time() - (int)$_SESSION['superadmin_pin_verified_at']) <= (15 * 60);
}
if ($force_pin || !$pin_ok || !$pin_age_ok) {
    // Clear stale flag to be safe and redirect
    unset($_SESSION['superadmin_pin_verified']);
    unset($_SESSION['superadmin_pin_verified_at']);
    header('Location: super_admin_pin.php');
    exit();
}

// Helpers
function sanitize_input($data) { return htmlspecialchars(stripslashes(trim($data))); }

// Check if at least one super admin exists
$super_admin_exists = false;
$check_sql = "SELECT COUNT(*) AS cnt FROM users WHERE role = 'super_admin'";
$check_res = mysqli_query($conn, $check_sql);
if ($check_res) { $row = mysqli_fetch_assoc($check_res); $super_admin_exists = ((int)($row['cnt'] ?? 0)) > 0; }

$error_message = null; $success_message = null;

// AJAX: return super admin profiles
if (isset($_POST['action']) && $_POST['action'] === 'get_super_admin_users') {
    header('Content-Type: application/json');
    $sql = "SELECT u.id, u.username,  u.profile_image, rl.last_login
            FROM users u
            LEFT JOIN recent_logins rl ON u.username = rl.username
            WHERE u.role = 'super_admin' AND u.username IS NOT NULL AND u.username != ''
            ORDER BY rl.last_login DESC, u.username ASC
            LIMIT 20";
    $result = mysqli_query($conn, $sql);
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) { $users[] = $row; }
    echo json_encode(['success' => true, 'users' => $users]);
    exit();
}

// Bootstrap: Create initial Super Admin if none exists
if (isset($_POST['create_super_admin']) && !$super_admin_exists) {
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $errs = [];
    if ($username === '' || !preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username)) { $errs[] = 'Valid username is required (3-20 chars, letters/numbers/_/-).'; }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errs[] = 'Valid email is required.'; }
    if ($password === '' || !preg_match('/^[a-zA-Z0-9]+$/', $password)) { $errs[] = 'Password should contain only letters and numbers.'; }
    if ($password !== $confirm) { $errs[] = 'Passwords do not match.'; }
    if (empty($errs)) {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $dup = mysqli_stmt_get_result($stmt);
        if ($dup && mysqli_num_rows($dup) > 0) { $errs[] = 'Email already exists.'; }
        mysqli_stmt_close($stmt);
    }
    if (empty($errs)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $profile_image = 'image/SPCPC-logo-trans.png';
        $role = 'super_admin';
        $stmt = mysqli_prepare($conn, 'INSERT INTO users (email, password, username, profile_image, role, school_id) VALUES (?, ?, ?, ?, ?, NULL)');
        mysqli_stmt_bind_param($stmt, 'sssss', $email, $hash, $username, $profile_image, $role);
        if (mysqli_stmt_execute($stmt)) { $super_admin_exists = true; $success_message = 'Super Admin account created. You can now log in.'; }
        else { $error_message = 'Failed to create Super Admin. ' . mysqli_error($conn); }
        mysqli_stmt_close($stmt);
    } else { $error_message = '<ul><li>' . implode('</li><li>', $errs) . '</li></ul>'; }
}

// Handle Super Admin login by selected profile
if (isset($_POST['login'])) {
    $selected_user_id = isset($_POST['selected_user_id']) ? (int)$_POST['selected_user_id'] : 0;
    $password = $_POST['password'] ?? '';
    if ($selected_user_id <= 0) { $error_message = 'Please select a profile.'; }
    elseif ($password === '') { $error_message = 'Password is required.'; }
    else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? AND role = 'super_admin' LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $selected_user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if ($user && password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            unset($_SESSION['logging_out']);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['email'] = htmlspecialchars($user['email']);
            $_SESSION['username'] = htmlspecialchars($user['username']);
            $_SESSION['profile_image'] = htmlspecialchars($user['profile_image']);
            $_SESSION['role'] = 'super_admin';
            $_SESSION['school_id'] = $user['school_id'] ?? null;
            $_SESSION['login_recorded'] = false;
            // Mark fresh super admin auth context
            session_regenerate_id(true);
            $_SESSION['super_admin_logged_in_at'] = time();
            $_SESSION['super_admin_last_activity'] = time();
            $_SESSION['super_admin_session_version'] = bin2hex(random_bytes(8));
            $log_id = recordUserLogin($conn, $_SESSION['username'], $_SESSION['email'], 'Super Admin');
            if ($log_id) { $_SESSION['log_id'] = $log_id; }
            logActivity($conn, 'SUPER_ADMIN_LOGIN', 'Super admin logged in');
            header('Location: admin_panel.php');
            exit();
        } else { $error_message = 'Invalid password. Please try again.'; }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login - Qnnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-color: #098744; --secondary-color: #0a5c2e; }
        *{ margin:0; padding:0; box-sizing:border-box; }
        body, html { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); height: 100vh; overflow: hidden; }
        .main-container { display: flex; height: 100vh; position: relative; overflow: hidden; }
        .branding-section { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: flex-start; padding: 20px 40px; color: white; position: relative; height: 100vh; overflow: visible; left: 0; margin-right: auto; }
    .top-logo { position: absolute; top: 20px; left: 40px; }
    .spcpc-logo { width: 185px; height: 185px; object-fit: contain; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2)); }
        .brand-content { text-align: left; max-width: 600px; margin-left: 40px; padding-bottom: 20px; }
    .brand-title { font-size: 85px; font-weight: 800; line-height: 1.05; margin-bottom: 15px; background: linear-gradient(135deg, #ffffff 0%, #e8f5e8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; letter-spacing: -1px; }
    .tagline { font-size: 28px; font-weight: 400; opacity: 0.9; color: white; text-align: left; margin-top: 5px; }
        .login-section { flex: 0 0 750px; background: var(--primary-color); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px; position: relative; height: 100vh; overflow: hidden; }
        .login-container { width: 100%; max-width: 520px; text-align: center; position: relative; }
        .step-content { display: none; animation: fadeIn 0.3s ease-in-out; }
        .step-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px);} to {opacity:1; transform: translateY(0);} }
        .profiles-header { display:flex; align-items:center; justify-content:center; gap:10px; color:#fff; opacity:.9; font-weight:600; }
    .profile-carousel { position: relative; margin:40px 0; min-height:260px; display:flex; align-items:center; justify-content:center; }
    /* Match admin/login.php profile UI */
    .profile-item { display:flex; flex-direction:column; align-items:center; cursor:pointer; transition: all .3s ease; opacity:.7; transform: scale(0.9); }
    .profile-item.active { opacity:1; transform: scale(1); }
    .profile-avatar { width:200px; height:200px; border-radius:50%; background: rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; margin-bottom:24px; border:4px solid rgba(255,255,255,0.3); transition: all .3s ease; overflow:hidden; }
    .profile-item.active .profile-avatar { border-color:#fff; box-shadow: 0 0 20px rgba(255,255,255,0.3); }
    .profile-avatar img { width:100%; height:100%; object-fit:cover; }
    .profile-avatar i { color:#fff; font-size:60px; }
    .profile-name { color:#fff; font-size:20px; font-weight:600; margin-top:10px; }
        .carousel-nav { position:absolute; top:50%; transform: translateY(-50%); width:48px; height:48px; background: rgba(255,255,255,0.25); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; cursor:pointer; transition: background .2s; }
        .carousel-nav:hover { background: rgba(255,255,255,0.35); }
        .carousel-nav.prev { left:0; }
        .carousel-nav.next { right:0; }
    .carousel-dots { display:flex; align-items:center; justify-content:center; gap:8px; margin: 30px 0; }
    .dot { width:8px; height:8px; border-radius:50%; background: rgba(255,255,255,0.3); cursor:pointer; transition: all .3s ease; }
    .dot.active { background:#4285F4; transform: scale(1.2); }
    /* Selected user (password step) parity */
    .selected-user { display:flex; flex-direction:column; align-items:center; margin-bottom:40px; }
    .selected-user .profile-avatar { width:100px; height:100px; border:3px solid #fff; margin-bottom:15px; }
    .selected-user .profile-name { font-size:18px; margin-bottom:30px; }
    /* Password + buttons parity with admin/login.php */
    .password-input-container { position: relative; width:100%; max-width:350px; margin: 0 auto 30px auto; }
    .password-input { width:100%; padding:16px 50px 16px 20px; border:2px solid rgba(255,255,255,0.3); border-radius:25px; background: rgba(255,255,255,0.1); color:#fff; font-size:16px; transition: all .3s ease; }
    .password-input::placeholder { color: rgba(255,255,255,0.6); }
    .password-input:focus { outline:none; border-color:#fff; background: rgba(255,255,255,0.15); }
    .password-toggle { position:absolute; right:20px; top:50%; transform: translateY(-50%); cursor:pointer; color: rgba(255,255,255,0.6); transition: color .3s ease; }
    .password-toggle:hover { color:#fff; }
    .btn-primary { width:100%; padding:14px; background: rgba(255,255,255,0.1); color:#fff; border:2px solid rgba(255,255,255,0.3); border-radius:50px; font-size:14px; font-weight:600; margin-bottom:12px; transition: all .2s ease; cursor:pointer; }
    .btn-primary:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.4); transform: translateY(-1px); }
    .btn-primary:disabled { opacity:.5; cursor:not-allowed; }
    .btn-secondary { width:100%; padding:14px; background: rgba(255,255,255,0.1); color:#fff; border:2px solid #42b883; border-radius:50px; font-size:14px; font-weight:500; cursor:pointer; transition: all .3s ease; margin:5px 0; min-width:200px; }
    .btn-secondary:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.4); transform: translateY(-1px); }
        .alert { text-align:left; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Debata:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body,html{ font-family: 'Poppins', sans-serif; } .brand-title{ font-family: 'Debata', sans-serif; }</style>
</head>
<body>
    <div class="main-container">
        <!-- Left branding -->
        <div class="branding-section">
            <div class="top-logo"><img src="image/Qnnect-v1.2.png" alt="Qnnect Logo" class="spcpc-logo"></div>
            <div class="brand-content">
                <h1 class="brand-title">Manage Monitor Qnnect.</h1>
                <p class="tagline">One Access. Total Control.</p>
            </div>
        </div>

        <!-- Right login flow -->
        <div class="login-section">
            <div class="login-container">
                <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
                <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>

                <form method="post" autocomplete="off" id="superAdminForm">
                    <!-- Step A: Profile selection -->
                    <div class="step-content active" id="sa_step_profiles">
                        <div class="profiles-header">
                            <i class="fas fa-crown"></i>
                            <span id="sa_profile_count">Saved Super Admin profiles</span>
                        </div>
                        <div class="profile-carousel" id="sa_profile_carousel"></div>
                        <div class="carousel-nav prev" id="sa_prev" style="display:none;"><i class="fas fa-chevron-left"></i></div>
                        <div class="carousel-nav next" id="sa_next" style="display:none;"><i class="fas fa-chevron-right"></i></div>
                        <div class="carousel-dots" id="sa_profile_dots"></div>
                        <button type="button" class="btn-primary" id="sa_btn_continue" disabled>Continue</button>
                        <button type="button" class="btn-secondary" onclick="window.location.href='login.php'">Back to Admin Login</button>
                        <input type="hidden" id="sa_selected_user_id" name="selected_user_id">
                    </div>

                    <!-- Step B: Password entry -->
                    <div class="step-content" id="sa_step_password">
                        <div class="selected-user" id="sa_selected_user_display"></div>
                        <div class="password-input-container">
                            <input type="password" class="password-input" id="sa_password" name="password" placeholder="Password" required>
                            <span class="password-toggle" onclick="saTogglePassword()"><i class="fas fa-eye" id="sa_toggleIcon"></i></span>
                        </div>
                        <button type="submit" class="btn-primary" name="login">Log in</button>
                        <button type="button" class="btn-secondary" id="sa_back_btn">Back to profiles</button>
                    </div>
                </form>

                <?php if (!$super_admin_exists): ?>
                <div class="alert alert-info mt-3">No Super Admin accounts found. Create one:</div>
                <form method="post" autocomplete="off">
                    <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
                    <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
                    <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
                    <input type="password" name="confirm_password" class="form-control mb-2" placeholder="Confirm Password" required>
                    <button type="submit" name="create_super_admin" class="btn-primary">Create Super Admin</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // State
    let saProfiles = [];
    let saCurrentIndex = 0;
    let saSelectedUser = null;

    // Resolve image path from DB (handles 'null', relative, absolute)
    function saResolveImg(src){
        if (!src) return '';
        const s = String(src).trim();
        if (!s || s.toLowerCase() === 'null') return '';
        if (/^https?:\/\//i.test(s)) return s; // absolute URL
        if (s.startsWith('../') || s.startsWith('/')) return s; // already relative to root
        return '../' + s; // DB stores relative to root like uploads/...
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Load super admin profiles
        fetch('super_admin_login.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ action:'get_super_admin_users' }) })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    saProfiles = data.users || [];
                    updateSaProfileCarousel();
                    updateSaDots();
                    document.getElementById('sa_profile_count').textContent = `${saProfiles.length} saved profiles`;
                    toggleSaNav();
                }
            });

        // Nav buttons
        document.getElementById('sa_prev').addEventListener('click', saPrevProfile);
        document.getElementById('sa_next').addEventListener('click', saNextProfile);
        document.getElementById('sa_btn_continue').addEventListener('click', () => saNextStep());
        document.getElementById('sa_back_btn').addEventListener('click', () => saPrevStep());
    });

    function toggleSaNav(){
        const show = saProfiles.length > 1;
        document.getElementById('sa_prev').style.display = show ? 'flex' : 'none';
        document.getElementById('sa_next').style.display = show ? 'flex' : 'none';
    }

    function updateSaProfileCarousel(){
        const container = document.getElementById('sa_profile_carousel');
        container.innerHTML = '';
        if (saProfiles.length === 0) {
            container.innerHTML = '<div style="color:#fff;opacity:.9">No saved super admin profiles.</div>';
            document.getElementById('sa_btn_continue').disabled = true;
            return;
        }
    const p = saProfiles[saCurrentIndex];
    const avatarSrc = saResolveImg(p.profile_image);
        container.innerHTML = `
            <div class="profile-item active" onclick="saSelectProfile(${saCurrentIndex})">
                <div class="profile-avatar">
                    ${avatarSrc ? `<img src="${avatarSrc}" alt="${p.username || p.email}">` : '<i class="fas fa-user"></i>'}
                </div>
                <div class="profile-name">${p.username || p.email}</div>
            </div>
        `;
        // ensure button state
        document.getElementById('sa_btn_continue').disabled = false;
        document.getElementById('sa_selected_user_id').value = p.id;
        saSelectedUser = p;
    }

    function updateSaDots(){
        const dots = document.getElementById('sa_profile_dots');
        dots.innerHTML = '';
        saProfiles.forEach((_, idx) => {
            const d = document.createElement('div');
            d.className = 'dot' + (idx === saCurrentIndex ? ' active' : '');
            d.onclick = () => { saCurrentIndex = idx; updateSaProfileCarousel(); updateSaDots(); };
            dots.appendChild(d);
        });
    }

    function saPrevProfile(){
        if (saProfiles.length === 0) return;
        saCurrentIndex = (saCurrentIndex - 1 + saProfiles.length) % saProfiles.length;
        updateSaProfileCarousel();
        updateSaDots();
    }
    function saNextProfile(){
        if (saProfiles.length === 0) return;
        saCurrentIndex = (saCurrentIndex + 1) % saProfiles.length;
        updateSaProfileCarousel();
        updateSaDots();
    }

    function saSelectProfile(idx){
        saCurrentIndex = idx;
        saSelectedUser = saProfiles[idx];
        document.getElementById('sa_selected_user_id').value = saSelectedUser.id;
        document.getElementById('sa_btn_continue').disabled = false;
        updateSaProfileCarousel();
        updateSaDots();
    }

    function saNextStep(){
        if (!saSelectedUser) return;
        // Display selected user
        const display = document.getElementById('sa_selected_user_display');
    const avatarSrc = saResolveImg(saSelectedUser.profile_image);
        display.innerHTML = `
            <div class="profile-avatar">
                ${avatarSrc ? `<img src="${avatarSrc}" alt="${saSelectedUser.username || saSelectedUser.email}">` : '<i class="fas fa-user"></i>'}
            </div>
            <div class="profile-name">${saSelectedUser.username || saSelectedUser.email}</div>
        `;
        document.getElementById('sa_step_profiles').classList.remove('active');
        document.getElementById('sa_step_password').classList.add('active');
        document.getElementById('sa_password').focus();
    }
    function saPrevStep(){
        document.getElementById('sa_step_password').classList.remove('active');
        document.getElementById('sa_step_profiles').classList.add('active');
    }
    function saTogglePassword(){
        const input = document.getElementById('sa_password');
        const icon = document.getElementById('sa_toggleIcon');
        if (input.type === 'password') { input.type = 'text'; icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
        else { input.type = 'password'; icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
    }
    </script>

    
</body>
</html>
