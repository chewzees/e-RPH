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

// Fetch all classes for the dropdown
$classes_result = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
}

// Fetch all subjects for the dropdown
$subjects_result = $conn->query("SELECT * FROM subjects ORDER BY id");
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

// Fetch recent teaching records for Quick Fill (only for this teacher)
$records_sql = "SELECT * FROM teaching_records WHERE teacher_id = $teacher_id ORDER BY date DESC, start_time DESC LIMIT 3";
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

    // Calculate Awk from date
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

    $stmt = $conn->prepare("INSERT INTO teaching_records (class_name, subject, textbook, chapter, topic, week, date, start_time, end_time, notes, teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssi", $class_name, $subject_name, $textbook, $chapter, $topic, $week, $date, $start_time, $end_time, $notes, $teacher_id);
    if ($stmt->execute()) {
        $success_msg = 'Teaching record saved successfully!';
        echo '<script>window.location = window.location.pathname + "?success=1";</script>';
    } else {
        $success_msg = 'Error saving record: ' . $conn->error;
    }
}

// Check for success message
if (isset($_GET['success'])) {
    $success_msg = 'Teaching record saved successfully!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Teaching Record - e-RPH Mobile</title>
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

        .form-group {
            margin-bottom: 1rem;
        }

        /* Form Label Styling - 和admin页面一样 */
        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        /* Form Control Styling - 和admin页面一样 */
        .form-control,
        .form-select {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: var(--border-color);
            transition: all 0.3s ease;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-color);
            opacity: 0.6;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
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
        .quick-fill-section:empty::after {
            content: "No recent records found. Create your first teaching record below.";
            display: block;
            text-align: center;
            color: var(--text-color);
            opacity: 0.6;
            font-style: italic;
            padding: 2rem;
        }

        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
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
            <a href="user_record.php" class="mobile-nav-link">
                <i class="fas fa-book"></i>
                <span>Teaching Records</span>
            </a>
            <a href="teacher_report.php" class="mobile-nav-link active">
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
        <h1 class="page-title">Add Teaching Record</h1>
        <p class="page-subtitle">Create a new teaching record</p>

        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_msg; ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

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
        <?php endif; ?>

        <!-- Add Record Form -->
        <div class="card fade-in-content">
            <div class="card-header">
                <i class="fas fa-plus me-2"></i>
                Add New Teaching Record
            </div>
            <div class="card-body">
                <form method="POST" action="" class="fade-in-form">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="class_id" class="form-label required-field">Class</label>
                            <select class="form-control" id="class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="subject_id" class="form-label required-field">Subject</label>
                            <select class="form-control" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="textbook" class="form-label required-field">Textbook</label>
                            <select class="form-control" id="textbook" name="textbook" required>
                                <option value="">Please select subject first</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="chapter" class="form-label required-field">Chapter</label>
                            <select class="form-control" id="chapter" name="chapter" required>
                                <option value="">Please select textbook first</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="topic" class="form-label required-field">Topic</label>
                            <input type="text" class="form-control" id="topic" name="topic" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="date" class="form-label required-field">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <div class="col-md-4">
                            <label for="start_time" class="form-label required-field">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_time" class="form-label required-field">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Save Record
                        </button>
                    </div>
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

        function useForm(class_name, subject, textbook, chapter, topic, date, start_time, end_time) {
            // For mobile version, we need to find the correct IDs for class and subject
            // since the dropdowns expect IDs, not names
            const classSelect = document.getElementById('class_id');
            const subjectSelect = document.getElementById('subject_id');
            
            // Find and set the class ID
            for (let i = 0; i < classSelect.options.length; i++) {
                if (classSelect.options[i].text === class_name) {
                    classSelect.value = classSelect.options[i].value;
                    break;
                }
            }
            
            // Find and set the subject ID
            for (let i = 0; i < subjectSelect.options.length; i++) {
                if (subjectSelect.options[i].text === subject) {
                    subjectSelect.value = subjectSelect.options[i].value;
                    break;
                }
            }
            
            // Populate all form fields
            document.getElementById('textbook').value = textbook;
            document.getElementById('chapter').value = chapter;
            document.getElementById('topic').value = topic;
            
            // Populate date and time fields if they exist
            if (document.getElementById('date')) {
                document.getElementById('date').value = date;
            }
            if (document.getElementById('start_time')) {
                document.getElementById('start_time').value = start_time;
            }
            if (document.getElementById('end_time')) {
                document.getElementById('end_time').value = end_time;
            }
            
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

        // Dynamic textbook and chapter dropdowns - 和桌面版一样的功能
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
            }
        });
    </script>
    <!-- Fade In Animation JavaScript -->
    <script src="../fade_in_animation.js"></script>

</body>
</html> 