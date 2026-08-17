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

// Fetch all classes for the dropdown
$classes_result = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
if (!$classes_result) {
    die("Database error: " . $conn->error);
}
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
}

// Fetch all subjects for the dropdown
$subjects_result = $conn->query("SELECT * FROM subjects ORDER BY id");
if (!$subjects_result) {
    die("Database error: " . $conn->error);
}
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row;
}

// Fetch all chapters with subject and textbook info
$chapters_result = $conn->query("SELECT * FROM chapters");
$chapters = [];
$textbooks = [];
while ($row = $chapters_result->fetch_assoc()) {
    $chapters[] = $row;
    // Collect unique textbooks per subject
    $textbooks[$row['subject_id']][] = $row['textbook'];
}
foreach ($textbooks as $subject_id => $books) {
    $textbooks[$subject_id] = array_unique($books);
}

// Fetch recent teaching records for Quick Fill (Maximum 3 records)
$records_sql = "SELECT * FROM teaching_records ORDER BY date DESC, start_time DESC LIMIT 3";
$records_result = $conn->query($records_sql);
$records = [];
while ($row = $records_result->fetch_assoc()) {
    $records[] = $row;
}

// Function to get week text from date
function getWeekText($date) {
    $day = (int)date('j', strtotime($date));
    $weekNum = ceil($day / 7);
    switch ($weekNum) {
        case 1: return '1st Week';
        case 2: return '2nd Week';
        case 3: return '3rd Week';
        case 4: return '4th Week';
        case 5: return '5th Week';
        default: return '';
    }
}

// Placeholder data for teaching records
$total_records = 125;
$total_teachers = 8;
$total_classes = 12;

// Handle form submission to save a new teaching record
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'];
    $subject_id = $_POST['subject_id'];
    $textbook = $_POST['textbook'];
    $chapter = $_POST['chapter'];
    $topic = $_POST['topic'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $notes = $_POST['notes'];

    // Calculate week from date
    $week = getWeekText($date);

    // Get class_name and subject name from their IDs
    $class_name = '';
    $subject_name = '';
    foreach ($classes as $c) {
        if ($c['id'] == $class_id) {
            $class_name = $c['class_name'];
            break;
        }
    }
    foreach ($subjects as $s) {
        if ($s['id'] == $subject_id) {
            $subject_name = isset($s['name']) ? $s['name'] : 'Subject ' . $s['id'];
            break;
        }
    }

    $stmt = $conn->prepare("INSERT INTO teaching_records (class_name, subject, textbook, chapter, topic, week, date, start_time, end_time, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $class_name, $subject_name, $textbook, $chapter, $topic, $week, $date, $start_time, $end_time, $notes);
    if ($stmt->execute()) {
        $success_msg = 'Teaching record saved successfully!';
        echo '<script>window.location = window.location.pathname + "?success=1";</script>';
        exit();
    } else {
        $success_msg = 'Error saving record: ' . $stmt->error;
    }
}
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
            overflow: hidden;
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
        .btn {
            border-radius: 6px;
            padding: 0.6rem 1.5rem;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        /* Table styling for dark mode */
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

        .table tbody tr:hover td {
            background-color: var(--border-color);
        }

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

        /* Enhanced Quick Fill Styling */
        .quick-fill-section {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.05), rgba(76, 175, 80, 0.1));
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(76, 175, 80, 0.2);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .quick-fill-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(76, 175, 80, 0.2);
        }

        .quick-fill-title small {
            font-weight: 400;
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .quick-fill-item {
            background-color: var(--card-bg);
            padding: 0;
            border-radius: 10px;
            margin-bottom: 1rem;
            cursor: pointer;
            border: 2px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--text-color);
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .quick-fill-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), #45a049);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .quick-fill-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(76, 175, 80, 0.2);
        }

        .quick-fill-item:hover::before {
            opacity: 1;
        }

        .quick-fill-item:active {
            transform: translateY(-1px);
        }

        .quick-fill-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem 0.75rem;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.05), transparent);
        }

        .class-name {
            font-size: 1.1rem;
            color: var(--primary-color);
            font-weight: 700;
        }

        .date-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
        }

        .quick-fill-details {
            padding: 0.5rem 1.25rem;
        }

        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-row strong {
            margin-right: 0.5rem;
            min-width: 70px;
            color: var(--text-color);
        }

        .topic-row {
            background: rgba(76, 175, 80, 0.05);
            padding: 0.5rem;
            border-radius: 6px;
            margin-top: 0.5rem;
            border-left: 3px solid var(--primary-color);
        }

        .quick-fill-action {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.05));
            padding: 0.75rem 1.25rem;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary-color);
            border-top: 1px solid rgba(76, 175, 80, 0.2);
            transition: all 0.3s ease;
        }

        .quick-fill-item:hover .quick-fill-action {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }

        /* Quick Fill Feedback Animation */
        .quick-fill-feedback {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: center;
            font-weight: 600;
            animation: slideInFromTop 0.5s ease-out;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        @keyframes slideInFromTop {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Empty state styling */
        .quick-fill-empty {
            text-align: center;
            padding: 2rem;
        }

        .empty-state {
            padding: 2rem;
        }

        .empty-state i {
            opacity: 0.3;
        }

        .empty-state p {
            margin: 0;
            font-size: 1rem;
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
        [data-theme="dark"] .quick-fill-section {
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
            .card-body {
                padding: 1rem;
            }
            .page-title {
                font-size: 20px;
            }
            .main-content {
                margin-top: 70px;
                padding: 0.75rem;
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
            <a href="teach_records.php" class="mobile-nav-link">
                <i class="fas fa-book"></i>
                <span>Teaching Records</span>
            </a>
            <a href="admin_report.php" class="mobile-nav-link active">
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
        <h2 class="page-title">Add Teaching Record</h2>

        <!-- Quick Fill Section -->
        <?php if (!empty($records)): ?>
            <div class="quick-fill-section fade-in-content">
                <div class="quick-fill-title">
                    <i class="fas fa-clock me-2"></i>
                    Quick Use from Recent Records (Latest 3)
                    <small class="d-block mt-1 opacity-75">Tap any record to auto-fill the form</small>
                </div>
                <?php foreach ($records as $record): ?>
                    <div class="quick-fill-item" onclick="useForm('<?php echo htmlspecialchars($record['class_name']); ?>', '<?php echo htmlspecialchars($record['subject']); ?>', '<?php echo htmlspecialchars($record['textbook']); ?>', '<?php echo htmlspecialchars($record['chapter']); ?>', '<?php echo htmlspecialchars($record['topic']); ?>', '<?php echo htmlspecialchars($record['date']); ?>', '<?php echo htmlspecialchars($record['start_time']); ?>', '<?php echo htmlspecialchars($record['end_time']); ?>')">
                        <div class="quick-fill-header">
                            <strong class="class-name"><?php echo htmlspecialchars($record['class_name']); ?></strong>
                            <span class="date-badge"><?php echo date('M d, Y', strtotime($record['date'])); ?></span>
                        </div>
                        <div class="quick-fill-details">
                            <div class="detail-row">
                                <i class="fas fa-book text-primary me-2"></i>
                                <strong>Subject:</strong> <?php echo htmlspecialchars($record['subject']); ?>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-book-open text-success me-2"></i>
                                <strong>TextBook:</strong> <?php echo htmlspecialchars($record['textbook']); ?>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-list-ol text-warning me-2"></i>
                                <strong>Chapter:</strong> <?php echo htmlspecialchars($record['chapter']); ?>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-clock text-info me-2"></i>
                                <strong>Time:</strong> <?php echo date('h:i A', strtotime($record['start_time'])) . ' - ' . date('h:i A', strtotime($record['end_time'])); ?>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-calendar-week text-secondary me-2"></i>
                                <strong>Week:</strong> <?php echo getWeekText($record['date']); ?>
                            </div>
                            <div class="detail-row topic-row">
                                <i class="fas fa-lightbulb text-secondary me-2"></i>
                                <strong>Topic:</strong> <?php echo htmlspecialchars($record['topic']); ?>
                            </div>
                        </div>
                        <div class="quick-fill-action">
                            <i class="fas fa-hand-pointer me-1"></i>
                            Tap to use this record
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="quick-fill-section quick-fill-empty fade-in-content">
                <div class="quick-fill-title">
                    <i class="fas fa-clock me-2"></i>
                    Quick Use from Recent Records
                </div>
                <div class="empty-state">
                    <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                    <p class="text-muted">No recent records found. Create your first teaching record below.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Add Teaching Record Form -->
        <form class="card p-4" method="POST">
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select class="form-select" name="class_id" id="class_id" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <select class="form-select" name="subject_id" id="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Textbook <span class="text-danger">*</span></label>
                    <select class="form-select" name="textbook" id="textbook" required>
                        <option value="">Please select subject first</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Chapter <span class="text-danger">*</span></label>
                    <select class="form-select" name="chapter" id="chapter" required>
                        <option value="">Please select textbook first</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Topic <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="topic" name="topic" placeholder="Example: Quadratic Functions and Their Properties" required>
            </div>
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="dateInput" name="date" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" id="startTimeInput" name="start_time" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">End Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" id="endTimeInput" name="end_time" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Week</label>
                    <input type="text" class="form-control" id="weekDisplay" placeholder="Auto-generated" readonly>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes"></textarea>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-success">Save Record</button>
            </div>
        </form>
        <?php if ($success_msg): ?>
            <div class="alert alert-success mt-3"><?php echo $success_msg; ?></div>
        <?php endif; ?>
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

            // Week auto-generation logic
            const dateInput = document.getElementById('dateInput');
            const weekDisplay = document.getElementById('weekDisplay');
            if (dateInput && weekDisplay) {
                dateInput.addEventListener('change', function() {
                    const dateValue = this.value;
                    if (dateValue) {
                        const dateObj = new Date(dateValue);
                        const day = dateObj.getDate();
                        // Get week number (1-4, or 5 if needed)
                        const weekNum = Math.ceil(day / 7);
                        let weekText = '';
                        switch (weekNum) {
                            case 1: weekText = '1st Week'; break;
                            case 2: weekText = '2nd Week'; break;
                            case 3: weekText = '3rd Week'; break;
                            case 4: weekText = '4th Week'; break;
                            case 5: weekText = '5th Week'; break;
                            default: weekText = '';
                        }
                        weekDisplay.value = weekText;
                    } else {
                        weekDisplay.value = '';
                    }
                });
            }
        });

        // Dynamic textbook and chapter dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            const textbooks = <?php echo json_encode($textbooks); ?>;
            const chapters = <?php echo json_encode($chapters); ?>;
            const subjectSelect = document.querySelector('select[name="subject_id"]');
            const textbookSelect = document.querySelector('select[name="textbook"]');
            const chapterSelect = document.querySelector('select[name="chapter"]');

            if (subjectSelect && textbookSelect && chapterSelect) {
                subjectSelect.addEventListener('change', function() {
                    const subjectId = this.value;
                    textbookSelect.innerHTML = '<option value="">Select Textbook</option>';
                    chapterSelect.innerHTML = '<option value="">Please select textbook first</option>';
                    if (textbooks[subjectId]) {
                        textbooks[subjectId].forEach(function(book) {
                            textbookSelect.innerHTML += `<option value="${book}">${book}</option>`;
                        });
                    }
                });

                textbookSelect.addEventListener('change', function() {
                    const subjectId = subjectSelect.value;
                    const textbook = this.value;
                    chapterSelect.innerHTML = '<option value="">Select Chapter</option>';
                    chapters.forEach(function(chap) {
                        if (chap.subject_id == subjectId && chap.textbook == textbook) {
                            chapterSelect.innerHTML += `<option value="${chap.chapter_number}">${chap.chapter_number} ${chap.chapter_title}</option>`;
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
            }
        });

        // Enhanced useForm function for mobile admin
        function useForm(class_name, subject, textbook, chapter, topic, date, start_time, end_time) {
            // Find and set the class ID
            const classSelect = document.getElementById('class_id');
            for (let i = 0; i < classSelect.options.length; i++) {
                if (classSelect.options[i].text === class_name) {
                    classSelect.value = classSelect.options[i].value;
                    break;
                }
            }
            
            // Find and set the subject ID
            const subjectSelect = document.getElementById('subject_id');
            for (let i = 0; i < subjectSelect.options.length; i++) {
                if (subjectSelect.options[i].text === subject) {
                    subjectSelect.value = subjectSelect.options[i].value;
                    break;
                }
            }
            
            // Trigger change events to populate dependent dropdowns
            subjectSelect.dispatchEvent(new Event('change'));
            
            // Use setTimeout to ensure dropdowns are populated before setting values
            setTimeout(() => {
                document.getElementById('textbook').value = textbook;
                document.getElementById('textbook').dispatchEvent(new Event('change'));
                
                setTimeout(() => {
                    document.getElementById('chapter').value = chapter;
                }, 100);
            }, 100);
            
            // Populate other fields
            document.getElementById('topic').value = topic;
            document.getElementById('dateInput').value = date;
            document.getElementById('startTimeInput').value = start_time;
            document.getElementById('endTimeInput').value = end_time;
            
            // Show success feedback
            showQuickFillFeedback(class_name);
            
            // Scroll to form
            document.querySelector('.card').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Show feedback when Quick Fill is used
        function showQuickFillFeedback(className) {
            // Create temporary success message
            const feedback = document.createElement('div');
            feedback.className = 'quick-fill-feedback';
            feedback.innerHTML = `<i class="fas fa-check-circle me-2"></i>Form filled with data from "${className}"`;
            
            // Insert after Quick Fill section
            const quickFillSection = document.querySelector('.quick-fill-section');
            quickFillSection.parentNode.insertBefore(feedback, quickFillSection.nextSibling);
            
            // Remove feedback after 3 seconds
            setTimeout(() => {
                if (feedback.parentNode) {
                    feedback.parentNode.removeChild(feedback);
                }
            }, 3000);
        }
    </script>
    <!-- Fade In Animation JavaScript -->
    <script src="../fade_in_animation.js"></script>

</body>
</html> 