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

// Fetch unique classes and subjects for filter dropdowns
$class_options = [];
$subject_options = [];
$class_sql = "SELECT DISTINCT class_name FROM teaching_records ORDER BY class_name ASC";
$class_result = $conn->query($class_sql);
while ($row = $class_result->fetch_assoc()) {
    $class_options[] = $row['class_name'];
}
$subject_sql = "SELECT DISTINCT subject FROM teaching_records ORDER BY subject ASC";
$subject_result = $conn->query($subject_sql);
while ($row = $subject_result->fetch_assoc()) {
    $subject_options[] = $row['subject'];
}
// Fetch all teachers for filter dropdown
$teacher_options = [];
$teacher_sql = "SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name ASC";
$teacher_result = $conn->query($teacher_sql);
while ($row = $teacher_result->fetch_assoc()) {
    $teacher_options[] = $row;
}

// Handle filters
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : '';
$week_filter = isset($_GET['week']) ? $_GET['week'] : '';
$start_date_filter = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date_filter = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';
$teacher_filter = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : '';

// Build SQL query for records
$sql = "SELECT tr.*, u.full_name AS teacher_name FROM teaching_records tr LEFT JOIN users u ON tr.teacher_id = u.id WHERE 1=1";
$params = [];
if ($class_filter && $class_filter !== 'All Classes') {
    $sql .= " AND tr.class_name = ?";
    $params[] = $class_filter;
}
if ($subject_filter && $subject_filter !== 'All Subjects') {
    $sql .= " AND tr.subject = ?";
    $params[] = $subject_filter;
}
if ($week_filter && $week_filter !== 'All Weeks') {
    $sql .= " AND tr.week = ?";
    $params[] = $week_filter;
}
if ($start_date_filter) {
    $sql .= " AND tr.date >= ?";
    $params[] = $start_date_filter;
}
if ($end_date_filter) {
    $sql .= " AND tr.date <= ?";
    $params[] = $end_date_filter;
}
if ($search_filter) {
    $sql .= " AND (tr.topic LIKE ? OR tr.textbook LIKE ? OR tr.chapter LIKE ? OR tr.notes LIKE ? OR tr.week LIKE ?)";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
}
if ($teacher_filter && $teacher_filter !== 'All Teachers') {
    $sql .= " AND tr.teacher_id = ?";
    $params[] = $teacher_filter;
}
$sql .= " ORDER BY tr.date DESC, tr.start_time DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records = $stmt->get_result();

// Placeholder data for teaching records
$total_records = 125;
$total_teachers = 8;
$total_classes = 12;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teaching Records - e-RPH Mobile</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 600;
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

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .stat-title {
            color: var(--text-color);
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .chart-container {
            height: 300px;
            margin-bottom: 2rem;
        }

        /* Table Styling */
        .table {
            --bs-table-bg: var(--card-bg);
            --bs-table-color: var(--text-color);
        }

        .table thead th {
            background-color: var(--primary-color) !important;
            color: white !important;
            border-color: var(--border-color);
        }

        .table tbody td {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: var(--border-color);
        }

        .table tbody tr:hover td {
            background-color: var(--primary-color);
            color: white;
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

        /* Form controls styling for dark mode */
        .form-label {
            color: var(--text-color);
        }

        .form-select, .form-control {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .form-select:focus, .form-control:focus {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        /* Mobile Responsive Adjustments */
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }

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
            <a href="dashboard_admin.php" class="mobile-nav-link">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <li style="color:#0dff00 !important;">ADD & VIEW REPORT</li>
            <a href="teach_records.php" class="mobile-nav-link active">
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
        <h1 class="page-title">Teaching Records</h1>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form class="row g-3 align-items-end" method="GET">
                    <div class="col-md-2">
                        <label class="form-label">Class:</label>
                        <select class="form-select" name="class">
                            <option>All Classes</option>
                            <?php foreach ($class_options as $class): ?>
                                <option value="<?php echo htmlspecialchars($class); ?>" <?php if ($class_filter == $class) echo 'selected'; ?>><?php echo htmlspecialchars($class); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Subject:</label>
                        <select class="form-select" name="subject">
                            <option>All Subjects</option>
                            <?php foreach ($subject_options as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject); ?>" <?php if ($subject_filter == $subject) echo 'selected'; ?>><?php echo htmlspecialchars($subject); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Week:</label>
                        <select class="form-select" name="week">
                            <option>All Weeks</option>
                            <option value="1st Week" <?php if ($week_filter == '1st Week') echo 'selected'; ?>>1st Week</option>
                            <option value="2nd Week" <?php if ($week_filter == '2nd Week') echo 'selected'; ?>>2nd Week</option>
                            <option value="3rd Week" <?php if ($week_filter == '3rd Week') echo 'selected'; ?>>3rd Week</option>
                            <option value="4th Week" <?php if ($week_filter == '4th Week') echo 'selected'; ?>>4th Week</option>
                            <option value="5th Week" <?php if ($week_filter == '5th Week') echo 'selected'; ?>>5th Week</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date:</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date_filter); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date:</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date_filter); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Search:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search topics, ..." value="<?php echo htmlspecialchars($search_filter); ?>">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Teacher:</label>
                        <select class="form-select" name="teacher_id">
                            <option>All Teachers</option>
                            <?php foreach ($teacher_options as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" <?php if ($teacher_filter == $teacher['id']) echo 'selected'; ?>><?php echo htmlspecialchars($teacher['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-2"></i>Filter</button>
                    </div>
                    <div class="col-md-12 text-end mt-2">
                        <a href="teach_records.php" class="btn btn-secondary"><i class="fas fa-sync-alt me-2"></i>Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Records Table -->
        <div class="card">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>DATE/TIME</th>
                            <th>CLASS</th>
                            <th>SUBJECT</th>
                            <th>TEXTBOOK/CHAPTER</th>
                            <th>TOPIC</th>
                            <th>WEEK</th>
                            <th>TEACHER</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($records->num_rows > 0): ?>
                        <?php while ($row = $records->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['date']); ?><br><span><?php echo htmlspecialchars(substr($row['start_time'],0,5)); ?> - <?php echo htmlspecialchars(substr($row['end_time'],0,5)); ?></span></td>
                            <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo htmlspecialchars($row['textbook']); ?><br><?php echo htmlspecialchars($row['chapter']); ?></td>
                            <td><?php echo htmlspecialchars($row['topic']); ?><br><span class="text-muted small"><?php echo htmlspecialchars($row['notes']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['week']); ?></td>
                            <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                            <td>
                                <a href="../view_record.php?id=<?php echo $row['id']; ?>" class="text-primary me-2" title="View"><i class="fas fa-eye"></i></a>
                                <a href="#" class="text-danger" title="Delete"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted">No teaching records found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
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