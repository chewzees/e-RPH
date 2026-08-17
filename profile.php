<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = clean_input($_POST['full_name']);

        $update_sql = "UPDATE users SET full_name = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $full_name, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_msg'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();
        } else {
            $_SESSION['error_msg'] = "Error updating profile.";
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $verify_sql = "SELECT password FROM users WHERE id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("i", $user_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $user_data = $verify_result->fetch_assoc();
        
        if ($new_password !== $confirm_password) {
            $_SESSION['error_msg'] = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $_SESSION['error_msg'] = "Password must be at least 6 characters long.";
        } elseif ($current_password !== $user_data['password']) {
            $_SESSION['error_msg'] = "Current password is incorrect.";
        } else {
            $password_sql = "UPDATE users SET password = ? WHERE id = ?";
            $password_stmt = $conn->prepare($password_sql);
            $password_stmt->bind_param("si", $new_password, $user_id);
            
            if ($password_stmt->execute()) {
                $_SESSION['success_msg'] = "Password updated successfully!";
                header("Location: profile.php");
                exit();
            } else {
                $_SESSION['error_msg'] = "Error updating password.";
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
    <title>Profile - e-RPH Mobile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Fade In Animation CSS -->
    <link rel="stylesheet" href="../fade_in_animation.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --background-color: #f5f5f5;
            --text-color: #333;
            --card-bg: #ffffff;
            --border-color: #eee;
            --sidebar-bg: #ffffff;
            --header-bg: #ffffff;
        }

        [data-theme="dark"] {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --background-color: #000000;
            --text-color: #ffffff;
            --card-bg: #404040;
            --border-color: #555555;
            --sidebar-bg: #404040;
            --header-bg: #4CAF50;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        /* Mobile Top Navigation */
        .mobile-header {
            background-color: var(--header-bg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        .mobile-nav-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mobile-menu-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-color);
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mobile-logo img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }

        .mobile-logo h4 {
            margin: 0;
            font-size: 1rem;
            color: var(--text-color);
        }

        .mobile-nav-menu {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background-color: var(--sidebar-bg);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1001;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            transform: translateX(0);
            opacity: 1;
        }

        .mobile-nav-menu.active {
            left: 0;
            transform: translateX(0);
            opacity: 1;
        }

        .mobile-nav-menu:not(.active) {
            transform: translateX(-100%);
            opacity: 0.8;
        }

        .mobile-nav-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }

        .mobile-nav-header img {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }

        .mobile-nav-links {
            padding: 1rem;
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .mobile-nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .mobile-nav-link:hover::before {
            left: 100%;
        }

        .mobile-nav-link:hover, .mobile-nav-link.active {
            background-color: var(--primary-color);
            color: white;
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }

        .mobile-nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 8px;
        }

        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(0px);
        }

        .mobile-overlay.active {
            opacity: 1;
            visibility: visible;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(2px);
        }

        .main-content {
            margin-top: 80px;
            padding: 1rem;
            background-color: var(--background-color);
        }

        .page-title {
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .page-subtitle {
            color: var(--text-color);
            margin-bottom: 2rem;
        }

        .card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: var(--primary-color) !important;
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 500;
            border-bottom: none;
        }

        .card-body {
            padding: 1.5rem;
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .info-row {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            width: 180px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            margin-right: 0.5rem;
        }

        .info-label i {
            width: 20px;
            margin-right: 8px;
        }

        .info-value {
            flex: 1;
        }

        .badge-teacher {
            background-color: var(--primary-color);
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: normal;
        }

        .form-control {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .form-text {
            font-size: 0.8rem;
            margin-top: 0.2rem;
        }

        /* Comprehensive Dark Mode - Force ALL text white and ALL columns gray */
        [data-theme="dark"] {
            color-scheme: dark;
        }

        [data-theme="dark"] *,
        [data-theme="dark"] *::before,
        [data-theme="dark"] *::after {
            color: #ffffff !important;
        }

        [data-theme="dark"] .card,
        [data-theme="dark"] .card-body,
        [data-theme="dark"] .card-header,
        [data-theme="dark"] .table,
        [data-theme="dark"] .table th,
        [data-theme="dark"] .table td,
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select,
        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .recent-activity,
        [data-theme="dark"] .mobile-nav-menu,
        [data-theme="dark"] .mobile-header,
        [data-theme="dark"] .info-row {
            background-color: #404040 !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] .table th {
            background-color: #555555 !important;
        }

        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: #404040 !important;
            color: #ffffff !important;
            border-color: var(--primary-color) !important;
        }

        [data-theme="dark"] .btn {
            color: #ffffff !important;
        }

        [data-theme="dark"] .text-danger {
            color: #ff6b6b !important;
        }

        [data-theme="dark"] .text-success {
            color: #51cf66 !important;
        }

        [data-theme="dark"] .text-warning {
            color: #ffd43b !important;
        }

        [data-theme="dark"] .text-info {
            color: #74c0fc !important;
        }

        /* Mobile Responsive Adjustments */
        @media (max-width: 768px) {
            .mobile-header {
                padding: 0.75rem;
            }

            .mobile-logo h4 {
                font-size: 0.9rem;
            }

            .main-content {
                margin-top: 70px;
                padding: 0.75rem;
            }

            .card-body {
                padding: 1rem;
            }

            .info-label {
                width: 140px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Top Navigation -->
    <div class="mobile-header">
        <div class="mobile-nav-top">
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="mobile-logo">
                <img src="1234.png" alt="School Logo">
                <h4>e-RPH <?php echo ucfirst($user['role']); ?></h4>
            </div>
            <div></div> <!-- Spacer for centering -->
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div class="mobile-nav-menu" id="mobileNavMenu">
        <div class="mobile-nav-header">
            <img src="1234.png" alt="School Logo">
            <h5>e-RPH <?php echo ucfirst($user['role']); ?></h5>
        </div>
        <div class="mobile-nav-links">
        <li style="color:rgb(0, 255, 149) !important;">༺☆HOME PAGE☆༻</li>
            <a href="dashboard_user.php" class="mobile-nav-link">
                <i class="fas fa-book"></i>
                <span>Dashboard</span>
            </a>
            <li style="color:rgb(0, 255, 149) !important;">༺☆ADD & VIEW REPORT☆༻</li>
            <a href="user_record.php" class="mobile-nav-link">
                <i class="fas fa-book"></i>
                <span>Teaching Records</span>
            </a>
            <a href="teacher_report.php" class="mobile-nav-link">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <li style="color:rgb(0, 255, 149) !important active;">༺☆OWN PROFILE☆༻</li>
            <a href="profile.php" class="mobile-nav-link">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
            <li style="color:rgb(0, 255, 149) !important;">༺☆LOG OUT☆༻</li>
            <a href="logout.php" class="mobile-nav-link" style="color: #dc3545;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay" onclick="toggleMobileMenu()"></div>

    <!-- Main Content -->
    <div class="main-content fade-in-content">
        <h1 class="page-title">Profile</h1>
        <p class="page-subtitle">Manage your account information</p>

        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success_msg'];
                unset($_SESSION['success_msg']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error_msg'];
                unset($_SESSION['error_msg']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Account Information -->
        <div class="card fade-in-content">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i>
                Account Information
            </div>
            <div class="card-body">
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-user"></i>
                        Username:
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-id-card"></i>
                        Full Name:
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-shield-alt"></i>
                        Role:
                    </div>
                    <div class="info-value">
                        <span class="badge-teacher">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-calendar-alt"></i>
                        Registration Date:
                    </div>
                    <div class="info-value">
                        <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile -->
        <div class="card fade-in-content">
            <div class="card-header">
                <i class="fas fa-edit me-2"></i>
                Edit Profile
            </div>
            <div class="card-body">
                <form method="POST" class="fade-in-form">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-2"></i>
                            Username
                        </label>
                        <input type="text" class="form-control bg-light" id="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <div class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Username cannot be changed
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">
                            <i class="fas fa-id-card me-2"></i>
                            Full Name
                        </label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card fade-in-content">
            <div class="card-header">
                <i class="fas fa-lock me-2"></i>
                Change Password
            </div>
            <div class="card-body">
                <form method="POST" class="fade-in-form">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">
                            <i class="fas fa-key me-2"></i>
                            Current Password
                        </label>
                        <input type="password" class="form-control" id="current_password" 
                               name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            <i class="fas fa-lock me-2"></i>
                            New Password
                        </label>
                        <input type="password" class="form-control" id="new_password" 
                               name="new_password" required>
                        <div class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Password must be at least 6 characters long
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock me-2"></i>
                            Confirm New Password
                        </label>
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key me-2"></i>
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Check for saved theme preference and apply it
        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : null;
            
            if (currentTheme) {
                document.documentElement.setAttribute('data-theme', currentTheme);
            }
        });

        // Mobile menu toggle function with enhanced animations
        function toggleMobileMenu() {
            const navMenu = document.getElementById('mobileNavMenu');
            const overlay = document.getElementById('mobileOverlay');
            const menuBtn = document.querySelector('.mobile-menu-btn i');
            
            navMenu.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Animate menu button
            if (navMenu.classList.contains('active')) {
                menuBtn.style.transform = 'rotate(90deg)';
                menuBtn.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            } else {
                menuBtn.style.transform = 'rotate(0deg)';
            }
        }

        // Close menu when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const navMenu = document.getElementById('mobileNavMenu');
                if (navMenu && navMenu.classList.contains('active')) {
                    toggleMobileMenu();
                }
            }
        });

        // Add active class to current nav item with animation
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.mobile-nav-link');
            const currentPage = window.location.pathname.split('/').pop();
            
            navLinks.forEach((link, index) => {
                // Add staggered animation
                link.style.opacity = '0';
                link.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    link.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                    link.style.opacity = '1';
                    link.style.transform = 'translateX(0)';
                }, index * 50);
                
                // Check if this is the current page
                const href = link.getAttribute('href');
                if (href && href.includes(currentPage)) {
                    link.classList.add('active');
                }
            });
        });

        // Add touch/swipe support for mobile
        let touchStartX = 0;
        let touchEndX = 0;

        document.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });

        document.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {
            const navMenu = document.getElementById('mobileNavMenu');
            const swipeThreshold = 50;
            
            if (touchEndX < touchStartX - swipeThreshold) {
                // Swipe left - close menu
                if (navMenu && navMenu.classList.contains('active')) {
                    toggleMobileMenu();
                }
            } else if (touchEndX > touchStartX + swipeThreshold) {
                // Swipe right - open menu
                if (navMenu && !navMenu.classList.contains('active')) {
                    toggleMobileMenu();
                }
            }
        }
    </script>

    <!-- Fade In Animation JavaScript -->
    <script src="../fade_in_animation.js"></script>
</body>
</html> 