<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT * FROM users WHERE id = $user_id AND role = 'teacher'");
$user_info = $user_query->fetch_assoc();

// Get total classes (placeholder - replace with actual query)
$total_classes = 2; // Example value

// Get total subjects (placeholder - replace with actual query)
$total_subjects = 4; // Example value

// Get total hours (placeholder - replace with actual query)
$total_hours = 12; // Example value
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - e-RPH Mobile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Fade In Animation CSS -->
    <link rel="stylesheet" href="../fade_in_animation.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --background-color: #f4f7fb;
            --text-color: #1f2937;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --sidebar-bg: #ffffff;
            --header-bg: #ffffff;
            --blue-color: #2563eb;
        }

        [data-theme="dark"] {
            --primary-color: #60a5fa;
            --primary-hover: #3b82f6;
            --background-color: #111827;
            --text-color: #f9fafb;
            --card-bg: #1f2937;
            --border-color: #374151;
            --sidebar-bg: #1f2937;
            --header-bg: #1f2937;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        /* Dark Mode Toggle Switch */
        .theme-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .theme-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary-color);
        }

        input:checked + .slider:before {
            transform: translateX(30px);
        }

        .theme-toggle-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .theme-label {
            font-size: 14px;
            color: var(--text-color);
            font-weight: 500;
        }

        /* Mobile Top Navigation (from manage_class.php) */
        .mobile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #14b8a6 100%);
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.18);
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
            background: rgba(255,255,255,0.2);
            border: none;
            font-size: 1.2rem;
            color: #ffffff;
            cursor: pointer;
            padding: 0.6rem 0.7rem;
            border-radius: 10px;
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
            color: #ffffff;
            font-weight: 600;
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
        .mobile-nav-header h5 {
            color: var(--text-color);
        }
        .mobile-nav-links {
            padding: 1rem;
        }
        .mobile-nav-links li { list-style: none; margin: 0; padding-left: 0; }
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
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        /* Main Content - Same as Desktop */
        .header {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.07), rgba(20, 184, 166, 0.08));
            padding: 1.25rem 1.3rem;
            border-radius: 18px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            margin-bottom: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(37, 99, 235, 0.12);
        }

        .welcome-text {
            font-size: 22px;
            color: var(--text-color);
            font-weight: 700;
        }

        .header-subtitle {
            color: var(--text-color);
            opacity: 0.72;
            font-size: 0.95rem;
            margin-top: 0.2rem;
        }

        .user-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .user-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }

        .logout-btn {
            background-color: #dc3545;
            color: white;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        /* Dashboard Stats - Same as Desktop */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            padding: 18px;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.15), rgba(20, 184, 166, 0.15));
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.4rem;
        }

        .stat-title {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }

        /* Schedule Section - Same as Desktop */
        .schedule-section {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }

        .schedule-table th,
        .schedule-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .schedule-table th {
            background-color: var(--background-color);
            font-weight: bold;
            color: var(--text-color);
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-primary {
            background-color: #007bff;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        /* Recent Activity Section - Same as Desktop */
        .recent-activity {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .section-title {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .activity-item:last-child {
            border-bottom: none;
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
        [data-theme="dark"] .mobile-header {
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
            .stats-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .welcome-text {
                font-size: 20px;
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
                <h4>e-RPH <?php echo ucfirst($user_info['role']); ?></h4>
            </div>
            <div></div> <!-- Spacer for centering -->
        </div>
    </div>
    <!-- Mobile Navigation Menu -->
    <div class="mobile-nav-menu" id="mobileNavMenu">
        <div class="mobile-nav-header">
            <img src="1234.png" alt="School Logo">
            <h5>e-RPH <?php echo ucfirst($user_info['role']); ?></h5>
        </div>
        <div class="mobile-nav-links">
            <li style="color:#0dff00 !important;">HOME PAGE</li>
            <a href="dashboard_user.php" class="mobile-nav-link active">
                <i class="fas fa-book"></i>
                <span>Dashboard</span>
            </a>
            <li style="color:#0dff00 !important;">ADD & VIEW REPORT</li>
            <a href="user_record.php" class="mobile-nav-link">
                <i class="fas fa-book"></i>
                <span>Teaching Records</span>
            </a>
            <a href="teacher_report.php" class="mobile-nav-link">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <li style="color:#0dff00 !important;">PROFILE</li>
            <a href="profile.php" class="mobile-nav-link">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
            <li style="color:#0dff00 !important;">LOG OUT</li>
            <a href="logout.php" class="mobile-nav-link" style="color: #dc3545;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay" onclick="toggleMobileMenu()"></div>
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="welcome-text">
                Welcome back, <?php echo htmlspecialchars($user_info['full_name']); ?>!
            </div>
            <div class="user-controls">
                <div class="theme-toggle-container">
                    <span class="theme-label">Dark Mode</span>
                    <label class="theme-toggle">
                        <input type="checkbox" id="darkModeToggle">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Stats Cards - Same as Desktop -->
        <div class="stats-container fade-in-content">
            <div class="stat-card fade-in-card">
                <div class="stat-title">My Classes</div>
                <div class="stat-value"><?php echo $total_classes; ?></div>
            </div>
            <div class="stat-card fade-in-card">
                <div class="stat-title">My Subjects</div>
                <div class="stat-value"><?php echo $total_subjects; ?></div>
            </div>
            <div class="stat-card fade-in-card">
                <div class="stat-title">Total Hours</div>
                <div class="stat-value"><?php echo $total_hours; ?></div>
            </div>
        </div>


    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode functionality
        const darkModeToggle = document.getElementById('darkModeToggle');
        
        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : null;
        
        if (currentTheme) {
            document.documentElement.setAttribute('data-theme', currentTheme);
            if (currentTheme === 'dark') {
                darkModeToggle.checked = true;
            }
        }
        
        // Function to toggle dark mode
        function toggleDarkMode() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Sync toggle
            darkModeToggle.checked = newTheme === 'dark';
        }
        
        // Add event listener
        darkModeToggle.addEventListener('change', toggleDarkMode);
        
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