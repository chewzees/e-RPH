<?php
require_once 'db_connect.php';
secure_session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT * FROM users WHERE id = $admin_id AND role = 'admin'");
$admin_info = $admin_query->fetch_assoc();

// Handle user addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    $full_name = clean_input($_POST['full_name']);
    $role = clean_input($_POST['role']);
    
    // Check if username already exists
    $check_sql = "SELECT id FROM users WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error_msg'] = "Username already exists. Please choose a different username.";
    } else {
        // Insert new user
        $insert_sql = "INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, ?, 'active')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssss", $username, $password, $full_name, $role);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success_msg'] = "User added successfully!";
        } else {
            $_SESSION['error_msg'] = "Error adding user: " . $conn->error;
        }
    }
}

// Handle user deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    // Don't allow deleting self
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error_msg'] = "You cannot delete your own account.";
    } else {
        $delete_sql = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success_msg'] = "User deleted successfully!";
        } else {
            $_SESSION['error_msg'] = "Error deleting user: " . $conn->error;
        }
    }
}

// Get all users
$users_sql = "SELECT * FROM users ORDER BY id";
$users_result = $conn->query($users_sql);
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - e-RPH Mobile</title>
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
            --card-bg: #f8f9fa;
            --border-color: #e9ecef;
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

        /* Mobile Top Navigation (from manage_class.php) */
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
        /* Main Content - Same as Desktop */
        .main-content {
            margin-top: 80px;
            padding: 1rem;
            background-color: var(--background-color);
            min-height: 100vh;
        }

        .page-title {
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-size: 24px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 600;
        }

        .page-subtitle {
            color: var(--text-color);
            margin-bottom: 2rem;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card-header {
            background-color: #4CAF50 !important;
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 500;
            border-bottom: none;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .form-control {
            border-radius: 6px;
            padding: 0.6rem 1rem;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 6px;
            padding: 0.6rem 1.5rem;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .table {
            background-color: var(--card-bg) !important;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            color: var(--text-color) !important;
            transition: all 0.3s ease;
        }

        .table th {
            background-color: var(--border-color) !important;
            border-bottom: 2px solid var(--border-color) !important;
            font-weight: 600;
            color: var(--text-color) !important;
        }

        .table td {
            background-color: var(--card-bg) !important;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color) !important;
            color: var(--text-color) !important;
        }

        .table tr {
            background-color: var(--card-bg) !important;
        }

        .table tbody tr {
            background-color: var(--card-bg) !important;
        }

        .table tbody tr:hover {
            background-color: var(--border-color) !important;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 4px;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
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

        /* Mobile Responsive Adjustments */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .page-title {
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
                <h4>e-RPH <?php echo ucfirst($admin_info['role']); ?></h4>
            </div>
            <button class="mobile-menu-btn" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <i class="fas fa-moon" id="darkModeIcon"></i>
            </button>
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
            <a href="dashboard_admin.php" class="mobile-nav-link">
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
            <a href="manage_users.php" class="mobile-nav-link active">
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
    <!-- Main Content - Same as Desktop -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">User Management</h1>
                <p class="page-subtitle">Manage system users and their permissions</p>
            </div>

        </div>

        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_msg']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_msg']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

        <!-- Add User Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-plus"></i> Add New User</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="teacher">Teacher</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </form>
            </div>
        </div>

        <!-- Users List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users"></i> All Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role'] == 'admin' ? 'badge-danger' : 'badge-success'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-primary btn-sm me-1"
                                           title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this user?')"
                                           title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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
                updateDarkModeIcon(currentTheme);
            }
        });

        // Dark mode toggle function
        function toggleDarkMode() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateDarkModeIcon(newTheme);
        }

        // Update dark mode icon
        function updateDarkModeIcon(theme) {
            const icon = document.getElementById('darkModeIcon');
            if (icon) {
                if (theme === 'dark') {
                    icon.className = 'fas fa-sun';
                } else {
                    icon.className = 'fas fa-moon';
                }
            }
        }

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