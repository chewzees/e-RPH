<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT * FROM users WHERE id = $admin_id AND role = 'admin'");
$admin_info = $admin_query->fetch_assoc();

// Create subjects table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// Handle subject addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject_name = clean_input($_POST['subject_name']);
    
    // Check if subject already exists
    $check_sql = "SELECT id FROM subjects WHERE name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $subject_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error_msg'] = "Subject already exists with this name.";
    } else {
        // Insert new subject
        $insert_sql = "INSERT INTO subjects (name) VALUES (?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("s", $subject_name);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success_msg'] = "Subject added successfully!";
        } else {
            $_SESSION['error_msg'] = "Error adding subject: " . $conn->error;
        }
    }
}

// Handle subject deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $subject_id = intval($_GET['id']);
    
    $delete_sql = "DELETE FROM subjects WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $subject_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success_msg'] = "Subject deleted successfully!";
    } else {
        $_SESSION['error_msg'] = "Error deleting subject: " . $conn->error;
    }
}

// Get all subjects
$subjects_sql = "SELECT * FROM subjects ORDER BY id";
$subjects_result = $conn->query($subjects_sql);
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - e-RPH Mobile</title>
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
            --blue-color: #1976D2;
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mobile-menu-btn:hover {
            background-color: rgba(76, 175, 80, 0.1);
            transform: scale(1.1);
        }

        .mobile-menu-btn:active {
            transform: scale(0.95);
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

        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }

        .action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }

        .edit-btn {
            color: #1976D2;
        }

        .delete-btn {
            color: #DC3545;
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
        [data-theme="dark"] .filter-section {
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

        [data-theme="dark"] .edit-btn,
        [data-theme="dark"] .delete-btn {
            color: #ffffff !important;
        }

        /* Form controls and table styling for dark mode */
        .form-control, .form-select {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        .form-label {
            color: var(--text-color);
        }

        .table {
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .table th {
            background-color: var(--border-color);
            color: var(--text-color);
            border-bottom: 1px solid var(--border-color);
        }

        .table td {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr:hover {
            background-color: var(--border-color);
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
                <h4>e-RPH <?php echo ucfirst($admin_info['role']); ?></h4>
            </div>
            <div></div> <!-- Spacer for centering -->
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div class="mobile-nav-menu" id="mobileNavMenu">
        <div class="mobile-nav-header">
            <img src="1234.png" alt="School Logo">
            <h5>e-RPH <?php echo ucfirst($admin_info['role']); ?></h5>
        </div>
        <div class="mobile-nav-links">
         <li style="color:#0dff00 !important;">HOME PAGE</li>
            <a href="dashboard_admin.php" class="mobile-nav-link active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <li style="color:#0dff00 !important;">ADD & VIEW REPORT</li>
            <a href="teach_records.php" class="mobile-nav-link">
                <i class="fas fa-book"></i>
                <span>Teaching Records</span>
            </a>
            <a href="admin_report.php" class="mobile-nav-link">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <li style="color:#0dff00 !important;">MANAGE TEACHER & ADMIN</li>
            <a href="manage_users.php" class="mobile-nav-link">
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
            </a>
            <li style="color:#0dff00 !important;">MANAGEMENT</li>
            <a href="manage_class.php" class="mobile-nav-link">
                <i class="fas fa-cogs"></i>
                <span>Manage Class</span>
            </a>
            <a href="manage_subject.php" class="mobile-nav-link">
                <i class="fas fa-book-open"></i>
                <span>Manage Subject</span>
            </a>
            <a href="manage_chapter.php" class="mobile-nav-link">
                <i class="fas fa-list-ol"></i>
                <span>Manage Chapter</span>
            </a>
            <li style="color:#0dff00 !important;">PROFILE</li>
            <a href="admin_profile.php" class="mobile-nav-link">
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
        <h1 class="page-title">Subject Management</h1>
        <p class="page-subtitle">Manage subjects in the system</p>

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

        <!-- Add New Subject -->
        <div class="card">
            <div class="card-header bg-primary">
                <i class="fas fa-plus me-2"></i>
                Add New Subject
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="subject_name" class="form-label required-field">Subject Name</label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" placeholder="Example: Mathematics" required>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Subject List -->
        <div class="card">
            <div class="card-header bg-primary">
                <i class="fas fa-list me-2"></i>
                Subject List
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>SUBJECT NAME</th>
                                <th>CREATED ON</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($subjects) > 0): ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($subject['created_at'])); ?></td>
                                        <td>
                                            <a href="../edit_subject.php?id=<?php echo $subject['id']; ?>" class="btn btn-link p-0 action-btn edit-btn">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_subject.php?action=delete&id=<?php echo $subject['id']; ?>" class="btn btn-link p-0 action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this subject?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No subjects found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

        // Close menu when clicking overlay
        document.getElementById('mobileOverlay').addEventListener('click', function() {
            toggleMobileMenu();
        });

        // Close menu when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const navMenu = document.getElementById('mobileNavMenu');
                if (navMenu.classList.contains('active')) {
                    toggleMobileMenu();
                }
            }
        });

        // Add smooth scroll to nav links
        document.querySelectorAll('.mobile-nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Add a small delay for smooth transition
                setTimeout(() => {
                    // The actual navigation will happen naturally
                }, 300);
            });
        });

        // Add active class to current nav item with animation
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.mobile-nav-link');
            navLinks.forEach((link, index) => {
                // Add staggered animation
                link.style.opacity = '0';
                link.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    link.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                    link.style.opacity = '1';
                    link.style.transform = 'translateX(0)';
                }, index * 50);
                
                if (link.getAttribute('href') === 'manage_subject.php') {
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
                if (navMenu.classList.contains('active')) {
                    toggleMobileMenu();
                }
            } else if (touchEndX > touchStartX + swipeThreshold) {
                // Swipe right - open menu
                if (!navMenu.classList.contains('active')) {
                    toggleMobileMenu();
                }
            }
        }
    </script>
    <!-- Fade In Animation JavaScript -->
    <script src="../fade_in_animation.js"></script>

</body>
</html> 