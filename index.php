<?php
// 读取搜索参数
$keyword = isset($_GET['v']) ? trim($_GET['v']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// 初始化搜索数据
$searchData = [
    'success' => false,
    'count' => 0,
    'data' => []
];

// 初始化推荐数据
$featuredData = [
    'success' => false,
    'count' => 0,
    'data' => []
];

// 搜索时优先复用本地接口文件，避免站内回调失败
if ($keyword !== '') {
    ob_start();
    $_GET['keyword'] = $keyword;
    $_GET['page'] = $page;
    define('INCLUDED_FROM_SEARCH', true);
    include __DIR__ . '/core/api.search.php';
    $response = ob_get_clean();

    if ($response) {
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            $searchData = $decoded;
        }
    }
} else {
    // 首页展示推荐片单
    ob_start();
    include __DIR__ . '/core/api.php';
    $response = ob_get_clean();

    if ($response) {
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            $featuredData = $decoded;
        }
    }
}

// 覆盖被接口文件设置的 JSON 响应头，确保页面按 HTML 渲染
header('Content-Type: text/html; charset=utf-8');

// 统一渲染影片卡片
function renderMovieCards(array $items): void
{
    if (empty($items)) {
        echo '<li class="alert" role="alert">当前没有可展示的影片数据。</li>';
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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $keyword !== '' ? htmlspecialchars($keyword) . ' - 搜索结果' : '木子白白白影视搜索'; ?></title>
    <meta name="description" content="Movie search, catalog browsing, detail parsing and API hub.">
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
<body class="home-page">
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
                    <li><a class="nav_links_link" href="./recommend.php">片单浏览</a></li>
                    <li><a class="nav_links_link" href="./core/">接口文档</a></li>
                    <li><a class="nav_links_link is-primary" href="./test/">接口中心</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="u-container-full hero-content">
                <div class="hero-eyebrow">影视搜索</div>
                <h1 class="hero-title">
                    <?php echo $keyword !== '' ? '搜索结果已为你展开' : '祝你找到想看的影片'; ?>
                </h1>
                <p class="hero-subtitle">
                    <?php echo $keyword !== '' ? '继续筛选结果，或直接进入详情页查看播放源与影片信息。' : ''; ?>
                </p>
                <div class="hero-search" id="search-panel">
                    <form action="./" method="GET">
                        <div class="search-input-group">
                            <input type="text" name="v" id="wd" placeholder="搜索电影、剧集、演员或关键词" value="<?php echo htmlspecialchars($keyword); ?>" autocomplete="off">
                            <button type="submit" class="search-btn btn">开始搜索</button>
                        </div>
                    </form>
                    <div class="test-hero-links">
                        <a class="nav-chip" href="./recommend.php">浏览片单</a>
                        <a class="nav-chip" href="./core/">查看接口文档</a>
                        <a class="nav-chip" href="./test/">进入接口中心</a>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($keyword === ''): ?>
        <section class="site-section site-section-dark">
            <div class="u-container-full">
                <div class="section-eyebrow">当日推荐</div>
                <h2 class="section-title">先看一批已经整理好的影片入口</h2>
                <p class="section-copy">如果你还没有明确目标，可以先从片单开始，之后再深入到详情解析页。</p>
                <ul id="list">
                    <?php
                    if (!empty($featuredData['success']) && !empty($featuredData['data'])) {
                        renderMovieCards(array_slice($featuredData['data'], 0, 10));
                    } else {
                        echo '<li class="alert" role="alert">当前暂时无法加载推荐片单。</li>';
                    }
                    ?>
                </ul>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($keyword !== ''): ?>
        <section class="site-section">
            <div class="u-container-full">
                <div class="editorial-panel">
                    <div class="section-eyebrow">搜索结果</div>
                    <h2 class="section-title"><?php echo htmlspecialchars($keyword); ?></h2>
                    <p class="section-copy">共检索到 <?php echo intval($searchData['count'] ?? 0); ?> 条结果。点击卡片可进入详情解析页。</p>
                </div>
                <ul id="list">
                    <?php
                    if (!empty($searchData['success']) && !empty($searchData['data'])) {
                        renderMovieCards($searchData['data']);
                    } else {
                        echo '<li class="alert" role="alert">当前没有匹配结果，请更换关键词后重试。</li>';
                    }
                    ?>
                </ul>
                <?php if (!empty($searchData['success']) && !empty($searchData['data'])): ?>
                <div class="page">
                    <?php $prevPage = max(1, $page - 1); ?>
                    <a href="?v=<?php echo urlencode($keyword); ?>&page=<?php echo $prevPage; ?>">上一页</a>
                    <span class="current-page">第 <?php echo $page; ?> 页</span>
                    <a href="?v=<?php echo urlencode($keyword); ?>&page=<?php echo $page + 1; ?>">下一页</a>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <footer class="footer_wrap">
        <div class="disclaimer_wrap">
            <div class="disclaimer_container">
                <div class="disclaimer">
                    <p>站点内容来自公开网络，仅用于学习、搜索展示与接口验证。</p>
                    <p>如涉及版权问题，请在提供必要证明后联系处理。</p>
                </div>
            </div>
        </div>
        <div class="footer_bottom_wrap">
            <div class="footer_bottom_contain">
                <div class="footer_bottom_text">© 2026 木子白白白影视</div>
                <ul class="footer_bottom_list" role="list">
                    <li><a class="footer_bottom_link_wrap" href="./"><i class="fas fa-house"></i></a></li>
                    <li><a class="footer_bottom_link_wrap" href="./recommend.php"><i class="fas fa-compass"></i></a></li>
                    <li><a class="footer_bottom_link_wrap" href="./test/"><i class="fas fa-flask"></i></a></li>
                </ul>
            </div>
        </div>
    </footer>

    <script>
        // 初始化懒加载，减少首屏图片压力
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
