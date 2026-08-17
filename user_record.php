<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

// Get teacher info
$teacher_id = $_SESSION['user_id'];
$teacher_query = $conn->query("SELECT * FROM users WHERE id = $teacher_id AND role = 'teacher'");
$teacher_info = $teacher_query->fetch_assoc();

// Fetch unique classes and subjects for filter dropdowns (only for this teacher's records)
$class_options = [];
$subject_options = [];
$class_sql = "SELECT DISTINCT class_name FROM teaching_records WHERE teacher_id = ? ORDER BY class_name ASC";
$class_stmt = $conn->prepare($class_sql);
$class_stmt->bind_param('i', $teacher_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
while ($row = $class_result->fetch_assoc()) {
    $class_options[] = $row['class_name'];
}
$subject_sql = "SELECT DISTINCT subject FROM teaching_records WHERE teacher_id = ? ORDER BY subject ASC";
$subject_stmt = $conn->prepare($subject_sql);
$subject_stmt->bind_param('i', $teacher_id);
$subject_stmt->execute();
$subject_result = $subject_stmt->get_result();
while ($row = $subject_result->fetch_assoc()) {
    $subject_options[] = $row['subject'];
}

// Handle filters
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : '';
$week_filter = isset($_GET['week']) ? $_GET['week'] : '';
$start_date_filter = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date_filter = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';

// Build SQL query for records (only for this teacher)
$sql = "SELECT * FROM teaching_records WHERE teacher_id = ?";
$params = [$teacher_id];
$types = 'i';
if ($class_filter && $class_filter !== 'All Classes') {
    $sql .= " AND class_name = ?";
    $params[] = $class_filter;
    $types .= 's';
}
if ($subject_filter && $subject_filter !== 'All Subjects') {
    $sql .= " AND subject = ?";
    $params[] = $subject_filter;
    $types .= 's';
}
if ($week_filter && $week_filter !== 'All Weeks') {
    $sql .= " AND week = ?";
    $params[] = $week_filter;
    $types .= 's';
}
if ($start_date_filter) {
    $sql .= " AND date >= ?";
    $params[] = $start_date_filter;
    $types .= 's';
}
if ($end_date_filter) {
    $sql .= " AND date <= ?";
    $params[] = $end_date_filter;
    $types .= 's';
}
if ($search_filter) {
    $sql .= " AND (topic LIKE ? OR textbook LIKE ? OR chapter LIKE ? OR notes LIKE ? OR week LIKE ?)";
    for ($i = 0; $i < 5; $i++) {
        $params[] = "%$search_filter%";
        $types .= 's';
    }
}
$sql .= " ORDER BY date DESC, start_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$records = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Teaching Records - e-RPH Mobile</title>
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

        .filter-section {
            background-color: var(--card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .filter-input {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.9rem;
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .filter-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .table th {
            background-color: var(--border-color);
            font-weight: 600;
            color: var(--text-color);
        }

        .table tbody tr:hover {
            background-color: var(--border-color);
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .text-muted {
            color: #6c757d !important;
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

            .filter-row {
                grid-template-columns: 1fr;
                gap: 0.75rem;
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
                <h4>e-RPH <?php echo ucfirst($teacher_info['role']); ?></h4>
            </div>
            <div></div> <!-- Spacer for centering -->
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div class="mobile-nav-menu" id="mobileNavMenu">
        <div class="mobile-nav-header">
            <img src="1234.png" alt="School Logo">
            <h5>e-RPH <?php echo ucfirst($teacher_info['role']); ?></h5>
        </div>
        <div class="mobile-nav-links">
        <li style="color:rgb(0, 255, 149) !important;">༺☆HOME PAGE☆༻</li>
            <a href="dashboard_user.php" class="mobile-nav-link">
                <i class="fas fa-book"></i>
                <span>Dashboard</span>
            </a>
            <li style="color:rgb(0, 255, 149) !important;">༺☆ADD & VIEW REPORT☆༻</li>
            <a href="user_record.php" class="mobile-nav-link active">
                <i class="fas fa-book"></i>
                <span>Teaching Records</span>
            </a>
            <a href="teacher_report.php" class="mobile-nav-link">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <li style="color:rgb(0, 255, 149) !important;">༺☆OWN PROFILE☆༻</li>
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
        <h1 class="page-title">My Teaching Records</h1>
        <p class="page-subtitle">View and manage your teaching records</p>

        <!-- Filter Section -->
        <div class="filter-section fade-in-content">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="filter-label">Class:</label>
                        <select name="class" class="filter-input">
                            <option value="">All Classes</option>
                            <?php foreach ($class_options as $class): ?>
                                <option value="<?php echo htmlspecialchars($class); ?>" 
                                        <?php echo $class_filter === $class ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Subject:</label>
                        <select name="subject" class="filter-input">
                            <option value="">All Subjects</option>
                            <?php foreach ($subject_options as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject); ?>" 
                                        <?php echo $subject_filter === $subject ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Week:</label>
                        <select name="week" class="filter-input">
                            <option value="">All Weeks</option>
                            <option value="Week 1" <?php echo $week_filter === 'Week 1' ? 'selected' : ''; ?>>Week 1</option>
                            <option value="Week 2" <?php echo $week_filter === 'Week 2' ? 'selected' : ''; ?>>Week 2</option>
                            <option value="Week 3" <?php echo $week_filter === 'Week 3' ? 'selected' : ''; ?>>Week 3</option>
                            <option value="Week 4" <?php echo $week_filter === 'Week 4' ? 'selected' : ''; ?>>Week 4</option>
                            <option value="Week 5" <?php echo $week_filter === 'Week 5' ? 'selected' : ''; ?>>Week 5</option>
                            <option value="Week 6" <?php echo $week_filter === 'Week 6' ? 'selected' : ''; ?>>Week 6</option>
                            <option value="Week 7" <?php echo $week_filter === 'Week 7' ? 'selected' : ''; ?>>Week 7</option>
                            <option value="Week 8" <?php echo $week_filter === 'Week 8' ? 'selected' : ''; ?>>Week 8</option>
                            <option value="Week 9" <?php echo $week_filter === 'Week 9' ? 'selected' : ''; ?>>Week 9</option>
                            <option value="Week 10" <?php echo $week_filter === 'Week 10' ? 'selected' : ''; ?>>Week 10</option>
                        </select>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="filter-label">Start Date:</label>
                        <input type="date" name="start_date" class="filter-input" value="<?php echo $start_date_filter; ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">End Date:</label>
                        <input type="date" name="end_date" class="filter-input" value="<?php echo $end_date_filter; ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Search:</label>
                        <input type="text" name="search" class="filter-input" placeholder="Search topics, textbooks, chapters..." value="<?php echo htmlspecialchars($search_filter); ?>">
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>
                    <a href="user_record.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Records Table -->
        <div class="card fade-in-content">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>
                Teaching Records
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table fade-in-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Topic</th>
                                <th>Time</th>
                                <th>Week</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($records->num_rows > 0): ?>
                                <?php while ($record = $records->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($record['topic']); ?></td>
                                        <td><?php echo $record['start_time'] . ' - ' . $record['end_time']; ?></td>
                                        <td>
                                            <span class="badge badge-success"><?php echo htmlspecialchars($record['week']); ?></span>
                                        </td>
                                        <td>
                                            <a href="../view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No teaching records found
                                    </td>
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