<?php
require_once __DIR__ . '/../auth.php';
requireAccess('接口中心');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="API hub for list, detail and search validation pages.">
    <title>接口中心 - 木子白白白影视</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet" type="text/css">
    <link href="../assets/css/claude-theme.css" rel="stylesheet" type="text/css">
</head>
<body>
    <header class="nav_wrap" role="banner">
        <div class="nav_contain u-container-full">
            <a class="nav_logo_wrap" href="../">木子白白白</a>
            <nav role="navigation" class="nav_desktop_layout" aria-label="main-nav">
                <ul class="nav_links_wrap" role="list">
                    <li><a href="../" class="nav_links_link">影视搜索</a></li>
                    <li><a href="../recommend.php" class="nav_links_link">片单浏览</a></li>
                    <li><a href="../core/" class="nav_links_link">接口文档</a></li>
                    <li><a href="./" class="nav_links_link is-primary">接口中心</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="main-content" class="page_wrap">
        <section class="hero-section">
            <div class="u-container-full hero-content">
                <div class="hero-eyebrow">接口中心</div>
                <h1 class="hero-title">把接口验证也纳入站点主路径</h1>
                <p class="hero-subtitle">列表、详情和搜索三类验证页共享统一的视觉与导航，不再作为孤立工具页存在。</p>
            </div>
        </section>

        <section class="site-section">
            <div class="u-container-full">
                <div class="api-info">
                    <h2>可用接口</h2>
                    <ul class="api-list">
                        <li><strong>列表接口：</strong><code>../core/api.php</code></li>
                        <li><strong>详情接口：</strong><code>../core/api.detail.php</code></li>
                        <li><strong>搜索接口：</strong><code>../core/api.search.php</code></li>
                    </ul>
                </div>

                <div class="test-grid">
                    <div class="test-card">
                        <h3>列表验证</h3>
                        <p>验证片单、分类切换和结果数据结构。</p>
                        <a href="test.php" class="test-btn">打开页面</a>
                    </div>
                    <div class="test-card">
                        <h3>详情验证</h3>
                        <p>验证详情结构、播放源和剧集列表解析。</p>
                        <a href="detailtest.php" class="test-btn secondary">打开页面</a>
                    </div>
                    <div class="test-card">
                        <h3>搜索验证</h3>
                        <p>验证关键词搜索、分页与原始 JSON 输出。</p>
                        <a href="searchtest.php" class="test-btn success">打开页面</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer_wrap">
        <div class="footer_bottom_wrap">
            <div class="footer_bottom_contain">
                <div class="footer_bottom_text">© 2026 木子白白白影视</div>
                <div class="footer_bottom_text">接口验证页已纳入站点统一设计系统</div>
            </div>
        </div>
    </footer>
</body>
</html>
