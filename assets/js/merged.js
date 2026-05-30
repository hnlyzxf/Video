$(document).ready(function() {
    // 懒加载图片
    $("img.lazy").lazyload({
        effect: "fadeIn",
        threshold: 200
    });
    
    // 搜索框焦点效果
    $('#wd').on('focus', function() {
        $(this).closest('.search-input-group').addClass('focused');
    }).on('blur', function() {
        $(this).closest('.search-input-group').removeClass('focused');
    });
    
    // 搜索建议功能（可选）
    let searchTimeout;
    $('#wd').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();
        
        if (query.length > 2) {
            searchTimeout = setTimeout(() => {
                // 这里可以添加搜索建议的AJAX请求
                console.log('搜索建议:', query);
            }, 300);
        }
    });
    
    // 平滑滚动到顶部
    $('#backTopBtn').click(function(e) {
        e.preventDefault();
        $('html, body').animate({
            scrollTop: 0
        }, 800, 'easeInOutCubic');
    });
    
    // 电影卡片点击效果
    $('.movie-card').on('click', function(e) {
        if (!$(e.target).closest('a').length) {
            const link = $(this).find('.movie-title a').attr('href');
            if (link) {
                window.location.href = link;
            }
        }
    });
    
    // 添加页面加载动画
    $('.movie-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(30px)'
        });
        
        setTimeout(() => {
            $(this).animate({
                'opacity': '1'
            }, 600).css({
                'transform': 'translateY(0)'
            });
        }, index * 100);
    });
    
    // 特性卡片悬停效果增强
    $('.feature-card').hover(
        function() {
            $(this).find('.feature-icon').css('transform', 'scale(1.1) rotate(5deg)');
        },
        function() {
            $(this).find('.feature-icon').css('transform', 'scale(1) rotate(0deg)');
        }
    );
    
    // 添加键盘快捷键支持
    $(document).keydown(function(e) {
        // Ctrl/Cmd + K 聚焦搜索框
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
            e.preventDefault();
            focusSearch(); // 使用 focusSearch 函数而不是直接 focus
        }
        
        // ESC 键清空搜索框
        if (e.keyCode === 27) {
            $('#wd').val('').blur();
        }
    });
    
    // 添加搜索框占位符动画
    const placeholders = [
        '搜索电影、电视剧、综艺...',
        '试试搜索"复仇者联盟"',
        '发现更多精彩内容',
        '输入关键词开始搜索'
    ];
    
    let placeholderIndex = 0;
    setInterval(() => {
        if (!$('#wd').is(':focus') && $('#wd').val() === '') {
            placeholderIndex = (placeholderIndex + 1) % placeholders.length;
            $('#wd').attr('placeholder', placeholders[placeholderIndex]);
        }
    }, 3000);
});

// 添加自定义缓动函数
$.easing.easeInOutCubic = function (x, t, b, c, d) {
    if ((t/=d/2) < 1) return c/2*t*t*t + b;
    return c/2*((t-=2)*t*t + 2) + b;
};

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

// Focus search functionality
function focusSearch(e) {
    // 阻止默认行为和事件冒泡
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    const searchInput = document.getElementById('wd');
    const searchSection = document.getElementById('searchSection');
    
    if (searchInput && searchSection) {
        // 停止当前所有动画，防止冲突
        $('html, body').stop(true, false);
        
        // 使用 jQuery 的 animate 方法进行更精确的滚动控制
        $('html, body').animate({
            scrollTop: $(searchSection).offset().top - 100
        }, 800, 'easeInOutCubic', function() {
            // 滚动完成后聚焦
            setTimeout(() => {
                searchInput.focus();
                // 防止页面自动跳到顶部
                $(window).scrollTop($(searchSection).offset().top - 100);
            }, 100);
        });
    } else if (searchInput) {
        // 如果没有搜索区域，滚动到输入框位置
        $('html, body').stop(true, false);
        
        $('html, body').animate({
            scrollTop: $(searchInput).offset().top - 100
        }, 800, 'easeInOutCubic', function() {
            setTimeout(() => {
                searchInput.focus();
                // 防止页面自动跳到顶部
                $(window).scrollTop($(searchInput).offset().top - 100);
            }, 100);
        });
    }
}

// Load saved theme
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        document.querySelector('.theme-toggle i').className = 'fas fa-sun';
    }
    
    // Header scroll effect
    let lastScrollTop = 0;
    const header = document.querySelector('.main-header');
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            header.style.transform = 'translateY(-100%)';
        } else {
            header.style.transform = 'translateY(0)';
        }
        
        lastScrollTop = scrollTop;
    });
});
