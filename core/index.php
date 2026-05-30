<?php
require_once __DIR__ . '/../auth.php';
requireAccess('接口文档');
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="API reference for list, detail and search endpoints.">
    <title>接口文档 - 木子白白白影视</title>
    <link rel="shortcut icon" href="../assets/images/32.ico">
    <link rel="apple-touch-icon" href="../assets/images/64.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/claude-theme.css">
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="../">
                    <i class="fas fa-film"></i>
                    <span>木子白白白</span>
                </a>
            </div>
            <nav class="nav_desktop_layout" aria-label="main-nav">
                <ul class="nav_links_wrap" role="list">
                    <li><a class="nav_links_link" href="../">影视搜索</a></li>
                    <li><a class="nav_links_link" href="../recommend.php">片单浏览</a></li>
                    <li><a class="nav_links_link is-primary" href="../core/">接口文档</a></li>
                    <li><a class="nav_links_link" href="../test/">接口中心</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="u-container-full hero-content">
                <div class="hero-eyebrow">接口文档</div>
                <h1 class="hero-title">列表、详情、搜索三组接口都在这里</h1>
                <p class="hero-subtitle">文档页用于说明参数、返回字段和使用方式；如果需要直接验证接口，请进入接口中心。</p>
                <div class="test-hero-links">
                    <a class="nav-chip" href="../test/">进入接口中心</a>
                    <a class="nav-chip" href="../">返回影视搜索</a>
                </div>
            </div>
        </section>

        <section class="site-section">
            <div class="u-container-full">
                <div class="api-info">
                    <h2>接口概览</h2>
                    <p class="section-copy">本项目提供三类接口：列表接口负责片单与分类，详情接口负责影片信息与播放源，搜索接口负责按关键词返回结果列表。所有接口统一返回 JSON 数据。</p>
                </div>

                <section class="usage-section">
                    <div class="usage-content">
                        <h3>1. 列表接口</h3>
                        <p><code>GET /core/api.php</code></p>
                        <table class="api-table">
                            <thead>
                                <tr><th>参数</th><th>类型</th><th>说明</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>url</td><td>string</td><td>可选，自定义片单地址。</td></tr>
                                <tr><td>test</td><td>any</td><td>可选，用于检查接口服务状态。</td></tr>
                            </tbody>
                        </table>
                        <p class="section-copy">返回字段包含 <code>success</code>、<code>count</code>、<code>data</code>、<code>categories</code> 和 <code>info</code>。</p>
                    </div>

                    <div class="usage-content" style="margin-top: 24px;">
                        <h3>2. 详情接口</h3>
                        <p><code>GET /core/api.detail.php</code></p>
                        <table class="api-table">
                            <thead>
                                <tr><th>参数</th><th>类型</th><th>说明</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>url</td><td>string</td><td>可选，影片详情页地址。</td></tr>
                                <tr><td>test</td><td>any</td><td>可选，用于检查接口服务状态。</td></tr>
                            </tbody>
                        </table>
                        <p class="section-copy">返回字段包含片名、评分、海报、导演、演员、地区、年份、简介以及 <code>play_links</code> 播放源数组。</p>
                    </div>

                    <div class="usage-content" style="margin-top: 24px;">
                        <h3>3. 搜索接口</h3>
                        <p><code>GET /core/api.search.php</code></p>
                        <table class="api-table">
                            <thead>
                                <tr><th>参数</th><th>类型</th><th>说明</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>keyword</td><td>string</td><td>可选，搜索关键词。</td></tr>
                                <tr><td>page</td><td>integer</td><td>可选，页码，默认 1。</td></tr>
                                <tr><td>test</td><td>any</td><td>可选，用于检查接口服务状态。</td></tr>
                            </tbody>
                        </table>
                        <p class="section-copy">返回字段包含关键词、结果数组、分页信息和抓取来源信息。</p>
                    </div>
                </section>

                <section class="site-section" style="padding-bottom: 0;">
                    <div class="test-grid">
                        <div class="feature-card feature-success">
                            <h4>列表验证</h4>
                            <p>验证片单、分类和结果结构。</p>
                            <a href="../test/test.php" class="test-btn">打开页面</a>
                        </div>
                        <div class="feature-card feature-info">
                            <h4>详情验证</h4>
                            <p>验证播放源、剧集和详情结构。</p>
                            <a href="../test/detailtest.php" class="test-btn secondary">打开页面</a>
                        </div>
                        <div class="feature-card feature-warning">
                            <h4>搜索验证</h4>
                            <p>验证关键词搜索与分页结果。</p>
                            <a href="../test/searchtest.php" class="test-btn success">打开页面</a>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </main>

    <footer class="footer_wrap">
        <div class="footer_bottom_wrap">
            <div class="footer_bottom_contain">
                <div class="footer_bottom_text">© 2026 木子白白白影视</div>
                <ul class="footer_bottom_list" role="list">
                    <li><a class="footer_bottom_link_wrap" href="../"><i class="fas fa-house"></i></a></li>
                    <li><a class="footer_bottom_link_wrap" href="../recommend.php"><i class="fas fa-compass"></i></a></li>
                    <li><a class="footer_bottom_link_wrap" href="../test/"><i class="fas fa-flask"></i></a></li>
                </ul>
            </div>
        </div>
    </footer>
</body>
</html>
