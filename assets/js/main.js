// Main JavaScript functionality
$(document).ready(function() {
    // Theme toggle functionality
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Update theme toggle icon
        const themeIcon = document.querySelector('.theme-toggle i');
        if (themeIcon) {
            themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
    
    // Initialize theme from localStorage
    function initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        const themeIcon = document.querySelector('.theme-toggle i');
        if (themeIcon) {
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
    
    // Focus search input
    function focusSearch() {
        const searchInput = document.getElementById('wd');
        if (searchInput) {
            searchInput.focus();
            searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    // Back to top functionality
    function initBackToTop() {
        const backTopBtn = document.getElementById('backTopBtn');
        if (backTopBtn) {
            // Show/hide back to top button based on scroll position
            $(window).scroll(function() {
                if ($(this).scrollTop() > 300) {
                    $(backTopBtn).fadeIn();
                } else {
                    $(backTopBtn).fadeOut();
                }
            });
            
            // Smooth scroll to top
            $(backTopBtn).click(function(e) {
                e.preventDefault();
                $('html, body').animate({ scrollTop: 0 }, 600);
            });
        }
    }
    
    // Mobile navigation toggle
    function initMobileNav() {
        const mobileButton = document.querySelector('.nav_mobile_button');
        const mobileMenu = document.querySelector('.nav_mobile_menu');
        
        if (mobileButton && mobileMenu) {
            mobileButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('open');
                
                // Toggle hamburger icon animation
                const lines = mobileButton.querySelectorAll('.nav_mobile_icon_line');
                lines.forEach(line => line.classList.toggle('active'));
            });
            
            // Close mobile menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!mobileButton.contains(e.target) && !mobileMenu.contains(e.target)) {
                    mobileMenu.classList.remove('open');
                    const lines = mobileButton.querySelectorAll('.nav_mobile_icon_line');
                    lines.forEach(line => line.classList.remove('active'));
                }
            });
        }
    }
    
    // Search input enhancements
    function initSearchEnhancements() {
        const searchInput = document.getElementById('wd');
        if (searchInput) {
            // Add search suggestions (placeholder for future enhancement)
            searchInput.addEventListener('input', function() {
                // Future: Add search suggestions functionality
            });
            
            // Handle Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const form = this.closest('form');
                    if (form) {
                        form.submit();
                    }
                }
            });
        }
    }
    
    // Lazy loading for images (if any)
    function initLazyLoading() {
        if (typeof $.fn.lazyload !== 'undefined') {
            $('img[data-original]').lazyload({
                threshold: 200,
                effect: 'fadeIn'
            });
        }
    }
    
    // Initialize all functionality
    function init() {
        initTheme();
        initBackToTop();
        initMobileNav();
        initSearchEnhancements();
        initLazyLoading();
        
        // Make functions globally available
        window.toggleTheme = toggleTheme;
        window.focusSearch = focusSearch;
    }
    
    // Run initialization
    init();
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 600);
        }
    });
    
    // Add loading states for forms
    $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        if (submitBtn.length) {
            submitBtn.prop('disabled', true);
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> 搜索中...');
            
            // Re-enable after 3 seconds (fallback)
            setTimeout(function() {
                submitBtn.prop('disabled', false).html(originalText);
            }, 3000);
        }
    });
});

// Service Worker registration (if available)
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('./js/sw.js')
        .then(function(registration) {
            console.log('SW registered: ', registration);
        })
        .catch(function(registrationError) {
            console.log('SW registration failed: ', registrationError);
        });
}