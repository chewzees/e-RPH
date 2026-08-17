<?php
require_once 'db_connect.php';
secure_session_start();

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();

// Enhanced error handling
$error_messages = [
    '1' => 'Invalid username or password. Please try again.',
    'csrf' => 'Security token invalid. Please refresh the page and try again.',
    'logged_out' => 'You have been successfully logged out.'
];

$error_message = '';
$success_message = '';

if (isset($_GET['error']) && isset($error_messages[$_GET['error']])) {
    $error_message = $error_messages[$_GET['error']];
}

if (isset($_GET['logged_out'])) {
    $success_message = $error_messages['logged_out'];
}

// Ensure demo accounts exist for quick fill
$demo_accounts = [
    ['admin', 'admin123', 'admin', 'Administrator'],
    ['teacher', 'teacher123', 'teacher', 'Demo Teacher'],
];
foreach ($demo_accounts as [$demo_user, $demo_pass, $demo_role, $demo_name]) {
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $check->bind_param("s", $demo_user);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $insert = $conn->prepare("INSERT INTO users (username, password, role, full_name, status) VALUES (?, ?, ?, ?, 'active')");
        $insert->bind_param("ssss", $demo_user, $demo_pass, $demo_role, $demo_name);
        $insert->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-RPH Login</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Fade In Animation CSS -->
    <link rel="stylesheet" href="fade_in_animation.css">
    <style>
        @keyframes slide {
            0% {
                background-position: 0% 0%;
            }
            100% {
                background-position: -200% -200%;
            }
        }

        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --background-color: #f4f7fb;
            --text-color: #1f2937;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --login-bg: #ffffff;
            --title-bg: linear-gradient(135deg, #2563eb 0%, #14b8a6 100%);
        }

        [data-theme="dark"] {
            --primary-color: #60a5fa;
            --primary-hover: #3b82f6;
            --background-color: #111827;
            --text-color: #f9fafb;
            --card-bg: #1f2937;
            --border-color: #374151;
            --login-bg: #1f2937;
            --title-bg: linear-gradient(135deg, #1d4ed8 0%, #0f766e 100%);
        }

        [data-theme="dark"] body::before {
            opacity: 0.1 !important;
        }

        /* Dark mode message styling */
        [data-theme="dark"] .error-message.desktop,
        [data-theme="dark"] .error-message.mobile {
            background-color: #8B0000 !important;
            color: #FFB3B3 !important;
            border: 1px solid #FF6B6B !important;
        }

        [data-theme="dark"] .success-message.desktop,
        [data-theme="dark"] .success-message.mobile {
            background-color: #006400 !important;
            color: #B3FFB3 !important;
            border: 1px solid #4CAF50 !important;
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
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--card-bg);
            padding: 8px 12px;
            border-radius: 25px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .theme-label {
            font-size: 14px;
            color: var(--text-color);
            font-weight: 500;
        }

        .manual-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid var(--primary-color);
            border-radius: 20px;
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            white-space: nowrap;
            animation: manual-glow 1.6s ease-in-out infinite;
        }

        .manual-toggle-btn:hover {
            border-color: var(--primary-color);
            color: #fff;
            background: var(--primary-color);
            animation: none;
            box-shadow: 0 0 12px rgba(37, 99, 235, 0.45);
        }

        @keyframes manual-glow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.15);
                transform: scale(1);
            }
            50% {
                box-shadow: 0 0 14px 4px rgba(37, 99, 235, 0.55);
                transform: scale(1.04);
            }
        }

        [data-theme="dark"] .manual-toggle-btn {
            animation-name: manual-glow-dark;
        }

        @keyframes manual-glow-dark {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(96, 165, 250, 0.15);
                transform: scale(1);
            }
            50% {
                box-shadow: 0 0 16px 5px rgba(96, 165, 250, 0.65);
                transform: scale(1.04);
            }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            position: relative;
            overflow-x: hidden;
            background: linear-gradient(135deg, var(--background-color) 0%, #eef4ff 100%);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        body::before {
            display: none;
        }

        /* View Toggle Buttons */
        .view-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .toggle-btn {
            padding: 8px 16px;
            border: 2px solid #4CAF50;
            background: white;
            color: #4CAF50;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .toggle-btn.active {
            background: #4CAF50;
            color: white;
        }

        .toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Dark Mode Toggle Button Styling */
        [data-theme="dark"] .toggle-btn {
            background: var(--card-bg);
            color: #000000; /* Black text in dark mode */
            border-color: var(--primary-color);
        }

        [data-theme="dark"] .toggle-btn.active {
            background: var(--primary-color);
            color: #000000; /* Black text even when active in dark mode */
        }

        [data-theme="dark"] .toggle-btn:hover {
            background: var(--primary-color);
            color: #000000; /* Black text on hover in dark mode */
        }

        /* Desktop split-screen layout */
        .desktop-view {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-shell {
            width: min(1100px, 100%);
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
        }

        .auth-hero {
            padding: 42px;
            background: linear-gradient(135deg, var(--primary-color), #14b8a6);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 16px;
        }

        .auth-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.2);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .auth-hero h1 {
            font-size: 2rem;
            margin: 0;
            font-weight: 700;
        }

        .auth-hero p {
            margin: 0;
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.95;
            max-width: 420px;
        }

        .auth-form-panel {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--login-bg);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .logo-container img {
            width: 56px;
            height: 56px;
            border-radius: 14px;
        }

        .logo-text {
            color: var(--text-color);
            font-weight: 700;
            font-size: 1.05rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 0.9rem;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 1rem;
            background-color: var(--card-bg);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .login-btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, var(--primary-color), #14b8a6);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .help-text {
            text-align: center;
            margin-top: 1.2rem;
            color: var(--text-color);
            opacity: 0.7;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .demo-fill {
            margin-top: 1.25rem;
            padding: 1rem;
            border: 1px dashed var(--border-color);
            border-radius: 12px;
            background: rgba(37, 99, 235, 0.04);
        }

        .demo-fill-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.6rem;
        }

        .demo-fill-actions {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .demo-fill-btn {
            flex: 1;
            min-width: 110px;
            padding: 0.65rem 0.9rem;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--card-bg);
            color: var(--text-color);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .demo-fill-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .demo-fill small {
            display: block;
            margin-top: 0.65rem;
            color: var(--text-color);
            opacity: 0.65;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .error-message,
        .success-message {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.95rem;
        }

        .error-message {
            background-color: #fef2f2;
            color: #b91c1c;
        }

        .success-message {
            background-color: #ecfdf5;
            color: #047857;
        }

        .manual-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .manual-overlay.active {
            display: flex;
        }

        .manual-panel {
            width: min(720px, 100%);
            max-height: min(88vh, 820px);
            overflow: auto;
            background: var(--card-bg);
            color: var(--text-color);
            border-radius: 18px;
            border: 1px solid var(--border-color);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
            padding: 24px;
        }

        .manual-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 12px;
        }

        .manual-header h2 {
            margin: 0 0 6px;
            font-size: 1.4rem;
        }

        .manual-intro {
            margin: 0;
            opacity: 0.75;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .manual-close {
            border: none;
            background: transparent;
            color: var(--text-color);
            font-size: 1.4rem;
            cursor: pointer;
            line-height: 1;
            padding: 4px 8px;
            border-radius: 8px;
        }

        .manual-close:hover {
            background: rgba(37, 99, 235, 0.08);
        }

        .manual-panel details {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 12px 14px;
            margin-top: 10px;
            background: rgba(37, 99, 235, 0.03);
        }

        .manual-panel summary {
            cursor: pointer;
            font-weight: 700;
            list-style: none;
        }

        .manual-panel summary::-webkit-details-marker {
            display: none;
        }

        .manual-panel summary::before {
            content: "▸";
            display: inline-block;
            margin-right: 8px;
            color: var(--primary-color);
        }

        .manual-panel details[open] summary::before {
            content: "▾";
        }

        .manual-panel ol,
        .manual-panel ul {
            margin: 10px 0 0;
            padding-left: 1.2rem;
            line-height: 1.55;
            font-size: 0.95rem;
        }

        .manual-panel p {
            margin: 10px 0 0;
            font-size: 0.95rem;
            line-height: 1.55;
        }

        @media (max-width: 900px) {
            .login-shell {
                grid-template-columns: 1fr;
            }

            .auth-hero {
                padding: 28px;
            }

            .auth-form-panel {
                padding: 28px;
            }
        }

        @media (max-width: 768px) {
            .desktop-view {
                padding: 12px;
            }

            .theme-toggle-container {
                top: 10px;
                left: 10px;
                flex-wrap: wrap;
                max-width: calc(100vw - 20px);
            }

            .view-toggle {
                top: 10px;
                right: 10px;
            }

            .toggle-btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }

        body::before {
            display: none !important;
        }
    </style>
</head>
<body class="view-desktop">
    <!-- Dark Mode + Manual -->
    <div class="theme-toggle-container">
        <span class="theme-label">Dark Mode</span>
        <label class="theme-toggle">
            <input type="checkbox" id="darkModeToggle">
            <span class="slider"></span>
        </label>
        <button type="button" class="manual-toggle-btn" id="openManualBtn" title="User Manual">
            <i class="fas fa-book-open"></i> Manual
        </button>
    </div>

    <div class="desktop-view">
        <div class="login-shell fade-in-container">
            <div class="auth-hero">
                <div class="auth-badge"><i class="fas fa-shield-alt"></i> Secure access</div>
                <h1>Welcome to e-RPH</h1>
                <p>Sign in to continue to your records, dashboards, and teaching tools in one simple place.</p>
            </div>
            <div class="auth-form-panel">
                <div class="logo-container">
                    <img src="1234.png" alt="School Logo">
                    <div class="logo-text">e-RPH System</div>
                </div>
                <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>
                <form action="login_process.php" method="POST" class="fade-in-form" id="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label for="username-desktop">Username</label>
                        <input type="text" id="username-desktop" name="username" required autocomplete="username" class="fade-in-input">
                    </div>
                    <div class="form-group">
                        <label for="password-desktop">Password</label>
                        <input type="password" id="password-desktop" name="password" required autocomplete="current-password" class="fade-in-input">
                    </div>
                    <button type="submit" class="login-btn fade-in-button">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                <div class="demo-fill">
                    <span class="demo-fill-label"><i class="fas fa-magic"></i> Quick fill</span>
                    <div class="demo-fill-actions">
                        <button type="button" class="demo-fill-btn" data-fill-user="admin" data-fill-pass="admin123">
                            <i class="fas fa-user-shield"></i> Admin
                        </button>
                        <button type="button" class="demo-fill-btn" data-fill-user="teacher" data-fill-pass="teacher123">
                            <i class="fas fa-chalkboard-teacher"></i> Teacher
                        </button>
                    </div>
                    <small>Admin: full system access · Teacher: teaching records &amp; reports</small>
                </div>
                <p class="help-text">First time user? Contact the system administrator to create an account.</p>
            </div>
        </div>
    </div>

    <div class="manual-overlay" id="manualOverlay" role="dialog" aria-modal="true" aria-labelledby="manualTitle">
        <div class="manual-panel" id="user-manual">
            <div class="manual-header">
                <div>
                    <h2 id="manualTitle">User Manual</h2>
                    <p class="manual-intro">Quick guide to signing in and using the e-RPH teaching records system.</p>
                </div>
                <button type="button" class="manual-close" id="closeManual" aria-label="Close manual">&times;</button>
            </div>

            <details open>
                <summary>1. Sign in</summary>
                <ol>
                    <li>Enter your username and password, then click <strong>Login</strong>.</li>
                    <li>Use <strong>Admin</strong> or <strong>Teacher</strong> quick fill for demo accounts.</li>
                    <li>Only active accounts can sign in. If login fails, check with an administrator.</li>
                </ol>
            </details>

            <details>
                <summary>2. Roles</summary>
                <ul>
                    <li><strong>Administrator</strong> — manage users, classes, subjects, chapters, view all teaching records, and create reports.</li>
                    <li><strong>Teacher</strong> — add teaching records, view own records, and update personal profile.</li>
                </ul>
                <p>New accounts are created by an Administrator under <em>Manage Users</em> (there is no public self-registration).</p>
            </details>

            <details>
                <summary>3. Admin workflow</summary>
                <ol>
                    <li><strong>Dashboard</strong> — review users and teacher update status.</li>
                    <li><strong>Manage Class / Subject / Chapter</strong> — set up school data used in reports.</li>
                    <li><strong>Manage Users</strong> — add teachers/admins and set roles or status.</li>
                    <li><strong>Reports</strong> — create teaching records for the system.</li>
                    <li><strong>Teaching Records</strong> — filter and review saved lesson records.</li>
                </ol>
            </details>

            <details>
                <summary>4. Teacher workflow</summary>
                <ol>
                    <li><strong>Dashboard</strong> — open your teaching tools.</li>
                    <li><strong>Reports</strong> — fill class, subject, textbook, chapter, topic, date, and time, then save.</li>
                    <li><strong>Quick Use</strong> — tap a recent record to auto-fill the form, then adjust as needed.</li>
                    <li><strong>Teaching Records</strong> — filter and review your own saved records.</li>
                    <li><strong>Profile</strong> — update your name or password.</li>
                </ol>
            </details>

            <details>
                <summary>5. Tips</summary>
                <ul>
                    <li>Use Dark Mode from the toggle on the login page and dashboards.</li>
                    <li>On report forms, select subject first so textbook and chapter lists load correctly.</li>
                    <li>Week is calculated automatically from the date you choose.</li>
                    <li>Logout from the menu when you finish your session.</li>
                </ul>
            </details>
        </div>
    </div>

    <script>
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;

        if (localStorage.getItem('theme') === 'dark') {
            body.setAttribute('data-theme', 'dark');
            darkModeToggle.checked = true;
        } else {
            body.setAttribute('data-theme', 'light');
            darkModeToggle.checked = false;
        }

        darkModeToggle.addEventListener('change', () => {
            if (darkModeToggle.checked) {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                body.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            }
        });

        document.querySelectorAll('[data-fill-user]').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('username-desktop').value = btn.dataset.fillUser || '';
                document.getElementById('password-desktop').value = btn.dataset.fillPass || '';
                document.getElementById('username-desktop').focus();
            });
        });

        const manualOverlay = document.getElementById('manualOverlay');
        const openManual = () => manualOverlay.classList.add('active');
        const closeManual = () => manualOverlay.classList.remove('active');

        document.getElementById('openManualBtn').addEventListener('click', openManual);
        document.getElementById('closeManual').addEventListener('click', closeManual);
        manualOverlay.addEventListener('click', (e) => {
            if (e.target === manualOverlay) closeManual();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeManual();
        });
    </script>
    <script src="fade_in_animation.js"></script>
</body>
</html> 