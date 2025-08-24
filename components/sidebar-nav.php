<?php
// Check if we're in the admin folder first
$isInAdminFolder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$baseUrl = $isInAdminFolder ? '../' : '';

// Use consistent session handling
if (session_status() === PHP_SESSION_NONE) {
    require_once($isInAdminFolder ? "../includes/session_config.php" : "includes/session_config.php");
}

$email = $_SESSION['email'] ?? '';
$userData = null;

if (!empty($email)) {
    try {
        // Use direct mysqli connection to login database
        $conn_login = mysqli_connect("localhost", "root", "", "login_register");
        if ($conn_login) {
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = mysqli_prepare($conn_login, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $userData = mysqli_fetch_assoc($result);
            // Safely close the connection
            if (isset($conn_login) && $conn_login instanceof mysqli) {
                try {
                    if ($conn_login->ping()) {
                        $conn_login->close();
                    }
                } catch (Throwable $e) {
                    // Connection is already closed or invalid, do nothing
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error in sidebar-nav: " . $e->getMessage());
        $userData = null;
    }
}

// Debugging: Log session data
error_log("Session data in sidebar-nav: " . print_r($_SESSION, true));
?>
<!DOCTYPE html>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- Fix CSS paths -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>styles/main.css">
    <!-- Boxicons CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: 260px;
        background: #098744;
        z-index: 100;
        transition: all 0.5s ease;
    }

    .sidebar.close {
        width: 78px;
    }

    .sidebar .logo-details {
        height: 60px;
        width: 100%;
        display: flex;
        align-items: center;
    }

    .sidebar .logo-details i.bx-menu {
        font-size: 30px;
        color: #fff;
        height: 50px;
        min-width: 78px;
        text-align: center;
        line-height: 50px;
        cursor: pointer;
        z-index: 9999;
        position: relative;
        user-select: none;
        -webkit-user-select: none;
        pointer-events: auto !important;
    }

    .sidebar .nav-links li .sub-menu {
        display: none;
        background: #0a6e37; /* Slightly darker than sidebar background */
    }
    
    .sidebar .nav-links li.showMenu .sub-menu {
        display: block;
    }
    
    /* Remove the black background for active items */
    .sidebar .nav-links li.active > .iocn-link {
        background-color: #0a6e37; /* Match with submenu background */
    }
    
    .sidebar .nav-links li.active > .iocn-link a,
    .sidebar .nav-links li.active > .iocn-link i {
        color: #fff;
    }

    /* Add hover effect for menu items */
    .sidebar .nav-links li:hover > .iocn-link {
        background-color: #0a6e37;
    }

    .sidebar .nav-links li:hover > .iocn-link a,
    .sidebar .nav-links li:hover > .iocn-link i {
        color: #fff;
    }

    /* Style for submenu items */
    .sidebar .nav-links li .sub-menu a {
        padding: 12px 20px;
        white-space: nowrap;
        opacity: 0.6;
        transition: all 0.3s ease;
    }

    .sidebar .nav-links li .sub-menu a:hover {
        opacity: 1;
    }

    /* Active submenu item */
    .sidebar .nav-links li .sub-menu a.active {
        opacity: 1;
        background-color: #0a6e37;
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Setup sidebar toggle with multiple approaches
        const sidebar = document.querySelector(".sidebar");
        const sidebarBtn = document.querySelector(".bx-menu");
        console.log('Sidebar element:', sidebar);
        console.log('Sidebar button:', sidebarBtn);
        
        // Global click handler for the menu icon
        document.addEventListener('click', function(e) {
            const clickedElement = e.target;
            if (clickedElement.classList.contains('bx-menu') || clickedElement.closest('.bx-menu')) {
                console.log('Menu icon clicked through global handler');
                e.preventDefault();
                e.stopPropagation();
                if (sidebar) {
                    sidebar.classList.toggle("close");
                }
            }
        }, true);

        // Direct click handler on the button
        if (sidebarBtn && sidebar) {
            sidebarBtn.onclick = function(e) {
                console.log('Direct click on menu button');
                e.preventDefault();
                e.stopPropagation();
                sidebar.classList.toggle("close");
            };
        } else {
            console.warn('Sidebar elements not found properly');
        }

        // Setup arrow toggles for submenus with direct event listeners
        const arrowElements = document.querySelectorAll('.arrow');
        arrowElements.forEach(arrow => {
            arrow.addEventListener('click', function(e) {
                console.log('Arrow clicked');
                e.preventDefault();
                e.stopPropagation();
                const parentLi = this.closest('li');
                if (parentLi) {
                    parentLi.classList.toggle('showMenu');
                }
            });
        });

        // Mark current page as active
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-links li');
        navLinks.forEach(li => {
            const links = li.querySelectorAll('a');
            links.forEach(link => {
                if (link.getAttribute('href') === currentPath || 
                    currentPath.endsWith(link.getAttribute('href'))) {
                    li.classList.add('showMenu');
                    const parentLi = link.closest('li');
                    if (parentLi) {
                        parentLi.classList.add('active');
                    }
                }
            });
        });
    });
    </script>
   </head>
<body>
<div class="sidebar close">
    <div class="logo-details">
        <i class='bx bx-menu' role="button" title="Toggle Sidebar"></i>
        <span class="logo_name">📚 QR Code Attendance</span>
    </div>
    <ul class="nav-links">
      <li>
        <a href="<?php echo $baseUrl; ?>dashboard.php">
          <i class='fas fa-tachometer-alt'></i>
          <span class="link_name">Dashboard</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="<?php echo $baseUrl; ?>dashboard.php">Dashboard</a></li>
        </ul>
      </li>
      <li>
        <a href="<?php echo $baseUrl; ?>index.php">
          <i class='fas fa-home' ></i>
          <span class="link_name">Home</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="<?php echo $baseUrl; ?>index.php">Home</a></li>
        </ul>
      </li>
      <li>
        <a href="<?php echo $baseUrl; ?>masterlist.php">
          <i class='fas fa-user-shield' ></i>
          <span class="link_name">Classlist</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="<?php echo $baseUrl; ?>masterlist.php">Classlist</a></li>
        </ul>
      </li>
      <li>
        <a href="<?php echo $baseUrl; ?>teacher-schedule.php">
          <i class='fas fa-calendar-alt' ></i>
          <span class="link_name">Class Schedule</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="<?php echo $baseUrl; ?>teacher-schedule.php">Class Schedule</a></li>
        </ul>
      </li>
      <li>
        <div class="iocn-link">
          <a href="<?php echo $baseUrl; ?>leaderboard.php">
            <i class='fas fa-trophy'></i>
            <span class="link_name">Leaderboards</span>
          </a>
          <i class='bx bxs-chevron-down arrow'></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="#">Leaderboards</a></li>
          <li><a href="<?php echo $baseUrl; ?>leaderboard.php">Attendance Leaders</a></li>
          <li><a href="<?php echo $baseUrl; ?>leaderboard-monthly.php">Monthly Rankings</a></li>
        </ul>
      </li>
     
      <li>
        <div class="iocn-link">
          <a href="<?php echo $baseUrl; ?>analytics.php">
            <i class='bx bx-pie-chart-alt-2' ></i>
            <span class="link_name">Data Reports</span>
          </a>
          <i class='bx bxs-chevron-down arrow'></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="#">Data Reports</a></li>
          <li><a href="<?php echo $baseUrl; ?>analytics.php">Attendance Analytics</a></li>
          <li><a href="<?php echo $baseUrl; ?>attendance_status.php">Attendance Status</a></li>
          <!-- <li><a href="<?php echo $baseUrl; ?>attendance-grades.php">Attendance Grades</a></li> -->
        </ul>
      </li>
      <li>
        <div class="iocn-link">
          <a href="<?php echo $baseUrl; ?>admin/history.php">
            <i class='bx bx-history'></i>
            <span class="link_name">History</span>
          </a>
          <i class='bx bxs-chevron-down arrow'></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="#">History</a></li>
          <li><a href="<?php echo $baseUrl; ?>admin/history.php">Users History</a></li>
          <li><a href="<?php echo $baseUrl; ?>verification-logs.php">Face Verification History</a></li>
        </ul>
      </li>
      <li>
        <a href="<?php echo $baseUrl; ?>settings.php">
          <i class='bx bx-cog'></i>
          <span class="link_name">Settings</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="<?php echo $baseUrl; ?>settings.php">Settings</a></li>
        </ul>
      </li>
      <li>
        <a href="<?php echo $baseUrl; ?>admin/users.php">
          <i class='bx bx-user'></i>
          <span class="link_name">User Profile</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="#">User Profile</a></li>
        </ul>
      </li>
      <li>
    <div class="profile-details">
      <div class="profile-content">
          <img src="<?php 
                    if ($userData && !empty($userData['profile_image'])) {
                        echo $baseUrl . $userData['profile_image'];
                    } else {
                        echo $baseUrl . 'admin/image/SPCPC-logo-trans.png';
                    }
                ?>" alt="profileImg" class="profile-img">
      </div>
      <div class="name-job">
        <div class="profile_name">
          <?php echo htmlspecialchars($userData['username'] ?? 'User'); ?>
        </div>
        <div class="job">
          <?php echo htmlspecialchars('admin'); ?>
        </div>
      </div>
      <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'logout.php' : 'admin/logout.php'; ?>" class="logout-icon">
        <i class='bx bx-log-out'></i>
      </a>
    </div>
  </li>
  
</ul>
  </div>
</body>
</html>

<style>

/* Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
*{
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', sans-serif;
}
.sidebar{
  position: fixed;
  top: 0;
  left: 0;
  height: 100%;
  width: 260px;
  background: #098744;
  z-index: 100;
  transition: all 0.5s ease;
}
.sidebar.close{
  width: 78px;
}
.sidebar .logo-details{
  height: 60px;
  width: 100%;
  display: flex;
  align-items: center;
  position: relative;
  z-index: 9998;
  pointer-events: auto !important;
}
.sidebar .logo-details i{
  font-size: 30px;
  color: #fff;
  height: 50px;
  min-width: 78px;
  text-align: center;
  line-height: 50px;
}
.sidebar .logo-details .logo_name{
  font-size: 22px;
  color: #fff;
  font-weight: 600;
  transition: 0.3s ease;
  transition-delay: 0.1s;
}
.sidebar.close .logo-details .logo_name{
  transition-delay: 0s;
  opacity: 0;
  pointer-events: none;
}
.sidebar .nav-links{
  height: 100%;
  padding: 30px 0 150px 0;
  overflow: auto;
}
.sidebar.close .nav-links{
  overflow: visible;
}
.sidebar .nav-links::-webkit-scrollbar{
  display: none;
}
.sidebar .nav-links li{
  position: relative;
  list-style: none;
  transition: all 0.4s ease;
}
.sidebar .nav-links li:hover{
  background:rgb(66, 184, 121);
}
.sidebar .nav-links li .iocn-link{
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.sidebar.close .nav-links li .iocn-link{
  display: block
}
.sidebar .nav-links li i{
  height: 50px;
  min-width: 78px;
  text-align: center;
  line-height: 50px;
  color: #fff;
  font-size: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  z-index: 1;
}

.sidebar .nav-links li i.arrow {
  cursor: pointer;
  position: relative;
  z-index: 2;
  pointer-events: all !important;
}
.sidebar .nav-links li.showMenu i.arrow{
  transform: rotate(-180deg);
}
.sidebar.close .nav-links i.arrow{
  display: none;
}
.sidebar .nav-links li a{
  display: flex;
  align-items: center;
  text-decoration: none;
}
.sidebar .nav-links li a .link_name{
  font-size: 18px;
  font-weight: 400;
  color: #fff;
  transition: all 0.4s ease;
}
.sidebar.close .nav-links li a .link_name{
  opacity: 0;
  pointer-events: none;
}
.sidebar .nav-links li .sub-menu{
  padding: 6px 6px 14px 80px;
  margin-top: -10px;
  background: #098744;
  display: none;
  position: relative;
  z-index: 1;
}
.sidebar .nav-links li.showMenu .sub-menu{
  display: block !important;
  pointer-events: all !important;
}
.sidebar .nav-links li .sub-menu a{
  color: #fff;
  font-size: 15px;
  padding: 5px 0;
  white-space: nowrap;
  opacity: 0.6;
  transition: all 0.3s ease;
}
.sidebar .nav-links li .sub-menu a:hover{
  opacity: 1;
}
.sidebar.close .nav-links li .sub-menu{
  position: absolute;
  left: 100%;
  top: -10px;
  margin-top: 0;
  padding: 10px 20px;
  border-radius: 0 6px 6px 0;
  opacity: 0;
  display: block;
  pointer-events: none;
  transition: 0s;
}
.sidebar.close .nav-links li:hover .sub-menu{
  top: 0;
  opacity: 1;
  pointer-events: auto;
  transition: all 0.4s ease;
}
.sidebar .nav-links li .sub-menu .link_name{
  display: none;
}
.sidebar.close .nav-links li .sub-menu .link_name{
  font-size: 18px;
  opacity: 1;
  display: block;
}
.sidebar .nav-links li .sub-menu.blank{
  opacity: 1;
  pointer-events: auto;
  padding: 3px 20px 6px 16px;
  opacity: 0;
  pointer-events: none;
}
.sidebar .nav-links li:hover .sub-menu.blank{
  top: 50%;
  transform: translateY(-50%);
}
.sidebar .profile-details{
  position: fixed;
  bottom: 0;
  width: 260px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #098744;
  padding: 12px 0;
  transition: all 0.5s ease;
}
.sidebar.close .profile-details{
  background: none;
}
.sidebar.close .profile-details{
  width: 78px;
}
.sidebar .profile-details .profile-content{
  display: flex;
  align-items: center;
  justify-content: center;  
}
.sidebar .profile-details img{
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 50% !important;
  margin: 0 14px 0 12px;
}
.sidebar.close .profile-details img{
  width: 50px;
  height: 50px;
  margin: 0 19px;
}
.sidebar .profile-details .profile_name,
.sidebar .profile-details .job{
  color: #fff;
  font-size: 14px;
  font-weight: 500;
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sidebar.close .profile-details i,
.sidebar.close .profile-details .profile_name,
.sidebar.close .profile-details .job{
  display: none;
}
.sidebar .profile-details .job{
  font-size: 12px;
  color: #fff;
}
.home-section{
  position: relative;
  background: #E4E9F7;
  height: 100vh;
  left: 260px;
  width: calc(100% - 260px);
  transition: all 0.5s ease;
}
.sidebar.close ~ .home-section{
  left: 78px;
  width: calc(100% - 78px);
}
.home-section .home-content{
  height: 60px;
  display: flex;
  align-items: center;
}
.home-section .home-content .bx-menu,
.home-section .home-content .text{
  color: #11101d;
  font-size: 35px;
}
.home-section .home-content .bx-menu{
  margin: 0 15px;
  cursor: pointer;
}
.home-section .home-content .text{
  font-size: 26px;
  font-weight: 600;
}
@media (max-width: 400px) {
  .sidebar.close .nav-links li .sub-menu{
    display: none;
  }
  .sidebar{
    width: 78px;
  }
  .sidebar.close{
    width: 0;
  }
  .home-section{
    left: 78px;
    width: calc(100% - 78px);
    z-index: 100;
  }
  .sidebar.close ~ .home-section{
    width: 100%;
    left: 0;
  }
}

.logout-icon {
  color: #fff;
  font-size: 20px;
  text-decoration: none;
  margin-right: 10px;
}

.logout-icon:hover {
  color: #e74c3c;
}

.profile-details img {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 50% !important;
    margin: 0 14px 0 12px;
}

.name_job {
    margin-left: 10px;
}

.name {
    font-weight: bold;
    font-size: 15px;
}

.email {
    font-size: 12px;
    color: #777;
}

.profile-img {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 50% !important;
    margin: 0 14px 0 12px;
}

.profile-details {
    position: fixed;
    bottom: 0;
    width: 260px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #098744;
    padding: 12px 0;
    transition: all 0.5s ease;
}

.profile-details img {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 50% !important;
    margin: 0 14px 0 12px;
}

</style>
   