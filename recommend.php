<?php
// 读取分页和分类参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$url = isset($_GET['url']) ? trim($_GET['url']) : '';

// 初始化片单数据
$catalogData = [
    'success' => false,
    'categories' => [],
    'data' => []
];

// 复用本地列表接口
$_GET['page'] = $page;
if ($url !== '') {
    $_GET['url'] = $url;
}

ob_start();
include __DIR__ . '/core/api.php';
$response = ob_get_clean();

if ($response) {
    $decoded = json_decode($response, true);
    if (is_array($decoded)) {
        $catalogData = $decoded;
    }
}

// 覆盖被接口文件设置的 JSON 响应头，确保页面按 HTML 渲染
header('Content-Type: text/html; charset=utf-8');

// 渲染片单卡片
function renderRecommendCards(array $items): void
{
    if (empty($items)) {
        echo '<li class="alert" role="alert">当前没有可展示的影片。</li>';
        return;
    }

    foreach ($items as $item) {
        $name = htmlspecialchars($item['name'] ?? '影片');
        $cover = htmlspecialchars($item['cover'] ?? '');
        $playUrl = urlencode($item['playUrl'] ?? '');
        $year = htmlspecialchars($item['year'] ?? '');
        $rating = htmlspecialchars($item['rating'] ?? '暂无评分');

        echo <<<HTML
<li>
    <a href="info.php?playUrl={$playUrl}">
        <div class="movie-poster-container">
            <img class="lazy" src="assets/images/load.gif" data-original="{$cover}" alt="{$name} 海报" width="200" height="280">
HTML;

        if ($year !== '') {
            echo '<div class="movie-remarks">' . $year . '</div>';
        }

        echo <<<HTML
            <div class="movie-rating">{$rating}</div>
        </div>
        <span>{$name}</span>
    </a>
</li>
HTML;
    }
}

// 渲染分类链接
function renderCategoryLinks(array $items): void
{
    foreach ($items as $item) {
        $name = htmlspecialchars($item['name'] ?? '');
        $itemUrl = urlencode($item['url'] ?? '');
        echo '<a class="category-item" href="?url=' . $itemUrl . '">' . $name . '</a>';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>片单浏览 - 木子白白白影视</title>
    <meta name="description" content="Browse movie catalog by category, region and sort options.">
    <link rel="shortcut icon" href="./assets/images/32.ico">
    <link rel="apple-touch-icon" href="./assets/images/64.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/claude-theme.css">
    <script src="./assets/js/jquery.min.js"></script>
    <script src="./assets/js/jquery.lazyload.min.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="./">
                    <i class="fas fa-film"></i>
                    <span>木子白白白</span>
                </a>
            </div>
            <nav class="nav_desktop_layout" aria-label="main-nav">
                <ul class="nav_links_wrap" role="list">
                    <li><a class="nav_links_link" href="./">影视搜索</a></li>
                    <li><a class="nav_links_link is-primary" href="./recommend.php">片单浏览</a></li>
                    <li><a class="nav_links_link" href="./core/">接口文档</a></li>
                    <li><a class="nav_links_link" href="./test/">接口中心</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="u-container-full hero-content">
                <div class="hero-eyebrow">片单浏览</div>
                <h1 class="hero-title">从整理好的分类里，顺着看下去</h1>
                <p class="hero-subtitle">片单页负责浏览发现。你可以按主分类、地区、类型与排序方式切换，再进入详情页查看播放源。</p>
            </div>
        </section>

        <section class="site-section">
            <div class="u-container-full">
                <?php if (!empty($catalogData['success']) && !empty($catalogData['categories'])): ?>
                <div class="categories-section">
                    <?php if (!empty($catalogData['categories']['main'])): ?>
                    <div class="category-group">
                        <h3>主分类</h3>
                        <div class="category-items"><?php renderCategoryLinks($catalogData['categories']['main']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($catalogData['categories']['type'])): ?>
                    <div class="category-group">
                        <h3>类型</h3>
                        <div class="category-items"><?php renderCategoryLinks($catalogData['categories']['type']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($catalogData['categories']['region'])): ?>
                    <div class="category-group">
                        <h3>地区</h3>
                        <div class="category-items"><?php renderCategoryLinks($catalogData['categories']['region']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($catalogData['categories']['sort'])): ?>
                    <div class="category-group">
                        <h3>排序</h3>
                        <div class="category-items"><?php renderCategoryLinks($catalogData['categories']['sort']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="editorial-panel" style="margin-top: 24px;">
                    <div class="section-eyebrow">结果列表</div>
                    <h2 class="section-title">当前片单</h2>
                    <p class="section-copy">共展示 <?php echo intval($catalogData['count'] ?? count($catalogData['data'] ?? [])); ?> 部影片。</p>
                </div>

                <ul id="list">
                    <?php
                    if (!empty($catalogData['success']) && !empty($catalogData['data'])) {
                        renderRecommendCards($catalogData['data']);
                    } else {
                        echo '<li class="alert" role="alert">当前暂时无法加载片单数据。</li>';
                    }
                    ?>
                </ul>

                <?php if (!empty($catalogData['success']) && !empty($catalogData['data'])): ?>
                <div class="page">
                    <?php $prevPage = max(1, $page - 1); ?>
                    <a href="?page=<?php echo $prevPage; ?><?php echo $url !== '' ? '&url=' . urlencode($url) : ''; ?>">上一页</a>
                    <span class="current-page">第 <?php echo $page; ?> 页</span>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $url !== '' ? '&url=' . urlencode($url) : ''; ?>">下一页</a>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="footer_wrap">
        <div class="footer_bottom_wrap">
            <div class="footer_bottom_contain">
                <div class="footer_bottom_text">© 2026 木子白白白影视</div>
                <ul class="footer_bottom_list" role="list">
                    <li><a class="footer_bottom_link_wrap" href="./"><i class="fas fa-house"></i></a></li>
                    <li><a class="footer_bottom_link_wrap" href="./core/"><i class="fas fa-book"></i></a></li>
                    <li><a class="footer_bottom_link_wrap" href="./test/"><i class="fas fa-flask"></i></a></li>
                </ul>
            </div>
        </div>
    </footer>

    <script>
        // 初始化懒加载，减少列表图片压力
        $(function () {
            if ($.fn.lazyload) {
                $("img.lazy").lazyload({
                    effect: "fadeIn",
                    threshold: 200
                });
            }
        });
    </script>
</body>
</html>
