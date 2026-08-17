// Mobile Navigation Smooth Transitions
// Include this JavaScript file in all mobile pages for consistent smooth interactions

// Mobile Navigation Functions
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
function setupOverlayClick() {
    const overlay = document.getElementById('mobileOverlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            toggleMobileMenu();
        });
    }
}

// Close menu when pressing Escape key
function setupEscapeKey() {
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const navMenu = document.getElementById('mobileNavMenu');
            if (navMenu && navMenu.classList.contains('active')) {
                toggleMobileMenu();
            }
        }
    });
}

// Add smooth scroll to nav links
function setupNavLinks() {
    document.querySelectorAll('.mobile-nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            // Add a small delay for smooth transition
            setTimeout(() => {
                // The actual navigation will happen naturally
            }, 300);
        });
    });
}

// Add active class to current nav item with animation
function setupActiveNavItem() {
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
}

// Add touch/swipe support for mobile
function setupSwipeSupport() {
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

// Add page load animations
function setupPageAnimations() {
    // Add fade-in animation to main content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.classList.add('fade-in-up');
    }
    
    // Add staggered animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Add hover effects to interactive elements
function setupHoverEffects() {
    // Add hover effect to buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Add hover effect to form inputs
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

// Add loading states
function setupLoadingStates() {
    // Add loading state to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                submitBtn.disabled = true;
                
                // Reset button after 3 seconds (fallback)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
}

// Add smooth scrolling for anchor links
function setupSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Add performance optimizations
function setupPerformanceOptimizations() {
    // Debounce scroll events
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            // Handle scroll events here if needed
        }, 100);
    });
    
    // Optimize animations for reduced motion preference
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.style.setProperty('--transition-duration', '0.1s');
    }
}

// Initialize all mobile navigation features
function initMobileNavigation() {
    setupOverlayClick();
    setupEscapeKey();
    setupNavLinks();
    setupActiveNavItem();
    setupSwipeSupport();
    setupPageAnimations();
    setupHoverEffects();
    setupLoadingStates();
    setupSmoothScrolling();
    setupPerformanceOptimizations();
}

// Run initialization when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initMobileNavigation();
});

// Export functions for use in other scripts
window.MobileNavigation = {
    toggleMobileMenu,
    setupOverlayClick,
    setupEscapeKey,
    setupNavLinks,
    setupActiveNavItem,
    setupSwipeSupport,
    setupPageAnimations,
    setupHoverEffects,
    setupLoadingStates,
    setupSmoothScrolling,
    setupPerformanceOptimizations,
    initMobileNavigation
}; 