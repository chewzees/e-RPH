<?php
// Enhanced Mobile Navigation Template
// Include this in all mobile pages for consistent navigation

// Generate CSRF token for secure forms
if (!isset($csrf_token)) {
    $csrf_token = generate_csrf_token();
}

// Determine current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Header with Improved Design -->
<header class="mobile-header">
    <div class="mobile-nav-top">
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Toggle Navigation Menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="mobile-logo">
            <img src="../1234.png" alt="School Logo">
            <h4><?php echo ucfirst($_SESSION['role'] ?? 'User'); ?> Portal</h4>
        </div>
        <div class="mobile-user-info">
            <span class="mobile-username"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
        </div>
    </div>
</header>

<!-- Mobile Navigation Menu with Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>
<nav class="mobile-nav-menu" id="mobileNavMenu">
    <div class="mobile-nav-header">
        <img src="../1234.png" alt="School Logo">
        <h5>e-RPH System</h5>
        <p><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></p>
    </div>
    
    <div class="mobile-nav-links">
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <!-- Admin Navigation -->
            <a href="dashboard_admin.php" class="mobile-nav-link <?php echo $current_page == 'dashboard_admin.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="manage_users.php" class="mobile-nav-link <?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
            </a>
            <a href="teach_records.php" class="mobile-nav-link <?php echo $current_page == 'teach_records.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Teaching Records</span>
            </a>
            <a href="admin_report.php" class="mobile-nav-link <?php echo $current_page == 'admin_report.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="manage_class.php" class="mobile-nav-link <?php echo $current_page == 'manage_class.php' ? 'active' : ''; ?>">
                <i class="fas fa-cogs"></i>
                <span>Manage Classes</span>
            </a>
            <a href="manage_subject.php" class="mobile-nav-link <?php echo $current_page == 'manage_subject.php' ? 'active' : ''; ?>">
                <i class="fas fa-book-open"></i>
                <span>Manage Subjects</span>
            </a>
            <a href="manage_chapter.php" class="mobile-nav-link <?php echo $current_page == 'manage_chapter.php' ? 'active' : ''; ?>">
                <i class="fas fa-list-ol"></i>
                <span>Manage Chapters</span>
            </a>
            <a href="admin_profile.php" class="mobile-nav-link <?php echo $current_page == 'admin_profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
        <?php else: ?>
            <!-- Teacher Navigation -->
            <a href="dashboard_user.php" class="mobile-nav-link <?php echo $current_page == 'dashboard_user.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="user_record.php" class="mobile-nav-link <?php echo $current_page == 'user_record.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Teaching Records</span>
            </a>
            <a href="teacher_report.php" class="mobile-nav-link <?php echo $current_page == 'teacher_report.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="profile.php" class="mobile-nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
        <?php endif; ?>
        
        <!-- Common Navigation Items -->
        <div class="mobile-nav-divider"></div>
        <div class="mobile-nav-section">
            <h6>Settings</h6>
            <div class="mobile-theme-toggle">
                <span>Dark Mode</span>
                <label class="theme-toggle">
                    <input type="checkbox" id="darkModeToggleMobile">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        
        <div class="mobile-nav-divider"></div>
        <a href="../logout.php" class="mobile-nav-link logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>

<!-- Enhanced Mobile Navigation Styles -->
<style>
/* Mobile Header */
.mobile-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    padding: 1rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobile-nav-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mobile-menu-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.75rem;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}

.mobile-menu-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.05);
}

.mobile-menu-btn:active {
    transform: scale(0.95);
}

.mobile-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
    text-decoration: none;
}

.mobile-logo img {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    border: 2px solid rgba(255,255,255,0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobile-logo h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.mobile-user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    color: white;
}

.mobile-username {
    font-size: 0.85rem;
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

/* Mobile Navigation Menu */
.mobile-nav-menu {
    position: fixed;
    top: 0;
    left: -100%;
    width: 320px;
    height: 100vh;
    background: var(--card-bg);
    box-shadow: 4px 0 20px rgba(0,0,0,0.15);
    z-index: 1001;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    backdrop-filter: blur(20px);
}

.mobile-nav-menu.active {
    left: 0;
}

.mobile-nav-header {
    padding: 2rem 1.5rem;
    text-align: center;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
    position: relative;
    overflow: hidden;
}

.mobile-nav-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(180deg); }
}

.mobile-nav-header img {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    border: 3px solid rgba(255,255,255,0.3);
    margin-bottom: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobile-nav-header h5 {
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    font-size: 1.3rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.mobile-nav-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.mobile-nav-links {
    padding: 1rem;
}

.mobile-nav-link {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    color: var(--text-color);
    text-decoration: none;
    border-radius: 12px;
    margin-bottom: 0.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.mobile-nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.6s;
}

.mobile-nav-link:hover::before {
    left: 100%;
}

.mobile-nav-link:hover,
.mobile-nav-link.active {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
    transform: translateX(8px);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
}

.mobile-nav-link i {
    width: 28px;
    text-align: center;
    margin-right: 1rem;
    font-size: 1.1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobile-nav-link:hover i,
.mobile-nav-link.active i {
    transform: scale(1.2);
}

.mobile-nav-link span {
    font-weight: 500;
    font-size: 0.95rem;
}

.mobile-nav-divider {
    height: 1px;
    background: var(--border-color);
    margin: 1rem 0.5rem;
    opacity: 0.3;
}

.mobile-nav-section h6 {
    color: var(--text-color);
    opacity: 0.7;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 1rem 1.25rem 0.5rem;
}

.mobile-theme-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    color: var(--text-color);
    font-weight: 500;
}

.logout-link {
    color: #dc3545 !important;
    margin-top: 1rem;
}

.logout-link:hover {
    background: #dc3545 !important;
    color: white !important;
}

/* Mobile Overlay */
.mobile-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(0px);
}

.mobile-overlay.active {
    opacity: 1;
    visibility: visible;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
}

/* Main Content Adjustment */
.main-content {
    margin-top: 90px;
    padding: 1.5rem;
    background: var(--background-color);
    min-height: calc(100vh - 90px);
}

/* Responsive Adjustments */
@media (max-width: 480px) {
    .mobile-nav-menu {
        width: 100%;
        left: -100%;
    }
    
    .mobile-logo h4 {
        font-size: 1rem;
    }
    
    .mobile-nav-header {
        padding: 1.5rem 1rem;
    }
}
</style>

<!-- Enhanced Mobile Navigation JavaScript -->
<script>
// Enhanced mobile navigation with improved animations and gestures
function toggleMobileMenu() {
    const navMenu = document.getElementById('mobileNavMenu');
    const overlay = document.getElementById('mobileOverlay');
    const menuBtn = document.querySelector('.mobile-menu-btn i');
    
    navMenu.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Animate menu button
    if (navMenu.classList.contains('active')) {
        menuBtn.style.transform = 'rotate(90deg)';
        menuBtn.style.transition = 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    } else {
        menuBtn.style.transform = 'rotate(0deg)';
        document.body.style.overflow = ''; // Restore scrolling
    }
}

// Close menu when clicking overlay
document.getElementById('mobileOverlay').addEventListener('click', toggleMobileMenu);

// Close menu with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const navMenu = document.getElementById('mobileNavMenu');
        if (navMenu.classList.contains('active')) {
            toggleMobileMenu();
        }
    }
});

// Touch gesture support for mobile menu
let touchStartX = 0;
let touchEndX = 0;

document.addEventListener('touchstart', e => {
    touchStartX = e.changedTouches[0].screenX;
});

document.addEventListener('touchend', e => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipeGesture();
});

function handleSwipeGesture() {
    const navMenu = document.getElementById('mobileNavMenu');
    const swipeThreshold = 50;
    
    if (touchEndX < touchStartX - swipeThreshold) {
        // Swipe left - close menu
        if (navMenu.classList.contains('active')) {
            toggleMobileMenu();
        }
    } else if (touchEndX > touchStartX + swipeThreshold) {
        // Swipe right - open menu
        if (!navMenu.classList.contains('active') && touchStartX < 50) {
            toggleMobileMenu();
        }
    }
}

// Dark mode synchronization with main app
const darkModeToggleMobile = document.getElementById('darkModeToggleMobile');
if (darkModeToggleMobile) {
    // Check current theme
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        darkModeToggleMobile.checked = true;
        document.documentElement.setAttribute('data-theme', 'dark');
    }
    
    // Toggle dark mode
    darkModeToggleMobile.addEventListener('change', function() {
        const newTheme = this.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });
}
</script>