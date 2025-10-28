<?php
define('SUPER_ADMIN_CONTEXT', true);
require_once '../includes/session_config_superadmin.php';
require_once 'database.php';

// Utilities
function sa_sanitize($v){ return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8'); }

// Brute force protection constants
define('MAX_FAILED_ATTEMPTS', 5);
define('LOCKOUT_TIME', 170); // 2:50 minutes in seconds

// Ensure system_settings table exists
$createTable = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(191) UNIQUE NOT NULL,
    setting_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
@mysqli_query($conn, $createTable);

// Helper to get setting by key
function get_setting($conn, $key){
    $stmt = mysqli_prepare($conn, 'SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1');
    if(!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row['setting_value'] ?? null;
}

// Helper to upsert setting
function upsert_setting($conn, $key, $value){
    $stmt = mysqli_prepare($conn, 'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP');
    if(!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'ss', $key, $value);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}   

$PIN_KEY = 'super_admin_pin_hash';
$FAILED_ATTEMPTS_KEY = 'super_admin_failed_attempts';
$LOCKOUT_TIME_KEY = 'super_admin_lockout_until';

// If user still has a super admin session with role set but PIN not validated, force full cleanup
if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin' && empty($_SESSION['superadmin_pin_verified'])) {
    // Inconsistent state -> logout
    header('Location: super_admin_logout.php?fix_state=1');
    exit();
}
$pin_hash = get_setting($conn, $PIN_KEY);

$error_message = null; $success_message = null;

// Check if account is locked out
$lockout_until = (int)get_setting($conn, $LOCKOUT_TIME_KEY);
$failed_attempts = (int)get_setting($conn, $FAILED_ATTEMPTS_KEY);
$now = time();
$is_locked = ($lockout_until > $now);

if ($is_locked) {
    $remaining_time = $lockout_until - $now;
    $minutes = floor($remaining_time / 60);
    $seconds = $remaining_time % 60;
    $error_message = "Too many failed attempts. Please wait {$minutes}:{$seconds} before trying again.";
}

// Handle set PIN (bootstrap) when none exists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_pin'])) {
    if ($pin_hash) {
        $error_message = 'PIN is already set.'; // Should not happen normally
    } else {
        $pin = $_POST['pin'] ?? '';
        $confirm = $_POST['confirm_pin'] ?? '';
        $pin = preg_replace('/\D+/', '', $pin); // keep digits only
        $confirm = preg_replace('/\D+/', '', $confirm);
        $errs = [];
        if ($pin === '' || $confirm === '') { $errs[] = 'PIN and confirmation are required.'; }
        if ($pin !== $confirm) { $errs[] = 'PINs do not match.'; }
        if (!preg_match('/^\d{6}$/', $pin)) { $errs[] = 'PIN must be exactly 6 digits.'; }
        if (empty($errs)) {
            $hash = password_hash($pin, PASSWORD_DEFAULT);
            if (upsert_setting($conn, $PIN_KEY, $hash)) {
                $success_message = 'PIN set successfully. You can now enter the PIN to proceed.';
                $pin_hash = $hash;
            } else {
                $error_message = 'Failed to save PIN. Please try again.';
            }
        } else {
            $error_message = '<ul><li>' . implode('</li><li>', $errs) . '</li></ul>';
        }
    }
}

// Handle verify PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_pin'])) {
    if ($is_locked) {
        // Don't process the form if locked out - message already set above
    } else if (!$pin_hash) {
        $error_message = 'No PIN is configured yet. Please create one first.';
    } else {
        $pin = $_POST['pin'] ?? '';
        $pin = preg_replace('/\D+/', '', $pin);
        if ($pin === '' || !preg_match('/^\d{6}$/', $pin)) {
            $error_message = 'Enter a valid 6-digit PIN.';
        } else if (password_verify($pin, $pin_hash)) {
            // Successful login - reset failed attempts counter
            upsert_setting($conn, $FAILED_ATTEMPTS_KEY, '0');
            upsert_setting($conn, $LOCKOUT_TIME_KEY, '0');
            
            $_SESSION['superadmin_pin_verified'] = true;
            // Minor hardening: remember time
            $_SESSION['superadmin_pin_verified_at'] = time();
            header('Location: super_admin_login.php');
            exit();
        } else {
            // Increment failed attempts
            $failed_attempts++;
            upsert_setting($conn, $FAILED_ATTEMPTS_KEY, (string)$failed_attempts);
            
            // Check if we need to lock the account
            if ($failed_attempts >= MAX_FAILED_ATTEMPTS) {
                $lockout_until = time() + LOCKOUT_TIME;
                upsert_setting($conn, $LOCKOUT_TIME_KEY, (string)$lockout_until);
                
                $minutes = floor(LOCKOUT_TIME / 60);
                $seconds = LOCKOUT_TIME % 60;
                $error_message = "Too many failed attempts. Account locked for {$minutes}:{$seconds} minutes.";
            } else {
                $remaining_attempts = MAX_FAILED_ATTEMPTS - $failed_attempts;
                $error_message = "Invalid PIN. Access denied. {$remaining_attempts} attempt" . 
                                 ($remaining_attempts !== 1 ? 's' : '') . " remaining.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin PIN - Qnnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-color: #098744; --secondary-color: #0a5c2e; }
        *{ margin:0; padding:0; box-sizing:border-box; }
        body, html { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); height: 100vh; overflow: hidden; }
        .main-container { display:flex; height:100vh; }
        .branding-section { flex:1; display:flex; flex-direction:column; justify-content:center; align-items:flex-start; padding:20px 40px; color:#fff; position:relative; }
        .top-logo { position:absolute; top:20px; left:40px; }
        .spcpc-logo { width:185px; height:185px; object-fit:contain; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2)); }
        .brand-content { text-align:left; max-width:600px; margin-left:40px; }
        .brand-title { font-size:64px; font-weight:800; line-height:1.05; margin-bottom:15px; background: linear-gradient(135deg, #ffffff 0%, #e8f5e8 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .tagline { font-size: 24px; opacity:.9; }
        .login-section { flex:0 0 720px; background: var(--primary-color); display:flex; align-items:center; justify-content:center; padding:40px; position:relative; }
        .login-container { width:100%; max-width:480px; text-align:center; }
        .pin-input { letter-spacing: 8px; font-weight:700; text-align:center; }
        .btn-primary { width:100%; padding:14px; background: rgba(255,255,255,0.1); color:#fff; border:2px solid rgba(255,255,255,0.3); border-radius:50px; font-size:14px; font-weight:600; margin-top:12px; transition: all .2s ease; cursor:pointer; }
        .btn-primary:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.4); transform: translateY(-1px); }
        .form-label{ color:#fff; opacity:.9; font-weight:600; }
        .card{ background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color:#fff; }
        .card .form-control{ background: rgba(255,255,255,0.12); color:#fff; border:1px solid rgba(255,255,255,0.3); }
        .card .form-control::placeholder{ color: rgba(255,255,255,0.7); }
        a.link-white{ color:#fff; text-decoration:none; opacity:.9; }
        a.link-white:hover{ opacity:1; text-decoration:underline; }
        .countdown { font-weight: bold; }
    </style>
</head>
<body>
    <?php if ($is_locked): ?>
    <script>
        // Set up countdown timer if account is locked
        document.addEventListener('DOMContentLoaded', function() {
            const lockoutUntil = <?php echo $lockout_until; ?>;
            const countdownElement = document.getElementById('countdown-timer');
            
            function updateCountdown() {
                const now = Math.floor(Date.now() / 1000);
                let secondsLeft = lockoutUntil - now;
                
                if (secondsLeft <= 0) {
                    // Time's up, refresh the page
                    location.reload();
                    return;
                }
                
                const minutes = Math.floor(secondsLeft / 60);
                const seconds = secondsLeft % 60;
                countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                setTimeout(updateCountdown, 1000);
            }
            
            if (countdownElement) {
                updateCountdown();
            }
        });
    </script>
    <?php endif; ?>
    <div class="main-container">
        <div class="branding-section">
            <div class="top-logo"><img src="image/Qnnect-v1.2.png" alt="Qnnect Logo" class="spcpc-logo"></div>
            <div class="brand-content">
                <h1 class="brand-title">Authority Begins Here.</h1>
                <p class="tagline">Absolute Command, Qnnect</p>
            </div>
        </div>
        <div class="login-section">
            <div class="login-container">
                <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php if ($is_locked): ?>
                        Too many failed attempts. Please wait <span id="countdown-timer" class="countdown">
                            <?php echo floor($remaining_time / 60) . ':' . str_pad($remaining_time % 60, 2, '0', STR_PAD_LEFT); ?>
                        </span> before trying again.
                    <?php else: ?>
                        <?php echo $error_message; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>

                <?php if (!$pin_hash): ?>
                    <div class="card p-4">
                        <h5 class="mb-3">Set Super Admin PIN</h5>
                        <form method="post" autocomplete="off">
                            <div class="mb-3">
                                <label class="form-label">New PIN (6 digits)</label>
                                <input type="password" inputmode="numeric" pattern="\d{6}" maxlength="6" class="form-control pin-input" name="pin" placeholder="••••••" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm PIN</label>
                                <input type="password" inputmode="numeric" pattern="\d{6}" maxlength="6" class="form-control pin-input" name="confirm_pin" placeholder="••••••" required>
                            </div>
                            <button class="btn-primary" type="submit" name="set_pin">Save PIN</button>
                            <div class="mt-3"><a class="link-white" href="login.php">Back to Admin Login</a></div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="card p-4">
                        <h5 class="mb-3">Enter Super Admin PIN</h5>
                        <form method="post" autocomplete="off">
                            <div class="mb-3">
                                <label class="form-label">PIN</label>
                                <input type="password" inputmode="numeric" pattern="\d{6}" maxlength="6" class="form-control pin-input" 
                                       name="pin" placeholder="••••••" required autofocus <?php echo $is_locked ? 'disabled' : ''; ?>>
                            </div>
                            <button class="btn-primary" type="submit" name="verify_pin" <?php echo $is_locked ? 'disabled' : ''; ?>>
                                <?php echo $is_locked ? 'Account Locked' : 'Verify PIN'; ?>
                            </button>
                            <div class="mt-3"><a class="link-white" href="login.php">Back to Admin Login</a></div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
