<?php
require_once __DIR__ . '/core/bootstrap.php';
$playUrl = isset($_GET['playUrl']) ? $_GET['playUrl'] : '';

// 请求详情接口
function fetchDetailData(string $apiUrl): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => APP_REQUEST_USER_AGENT
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if (!$response || $httpCode != 200 || !empty($error)) {
        return [
            'success' => false,
            'error' => $error,
            'http_code' => $httpCode
        ];
    }

    $detailData = json_decode($response, true);
    if (!is_array($detailData) || empty($detailData['success'])) {
        return [
            'success' => false,
            'error' => 'Invalid API response',
            'http_code' => $httpCode
        ];
    }

    return [
        'success' => true,
        'data' => $detailData
    ];
}

// 读取影片详情
$infoData = [];
if (!empty($playUrl) && !empty($_SERVER['HTTP_HOST'])) {
    $requestDir = dirname($_SERVER['REQUEST_URI']);
    if ($requestDir === '/' || $requestDir === '\\') {
        $requestDir = '';
    }

    $scheme = detectRequestScheme();
    $schemesToTry = array_values(array_unique([
        $scheme,
        $scheme === 'https' ? 'http' : 'https'
    ]));

    foreach ($schemesToTry as $schemeToTry) {
        $apiUrl = $schemeToTry . '://' . $_SERVER['HTTP_HOST'] . $requestDir . '/core/api.detail.php?url=' . urlencode($playUrl);
        $result = fetchDetailData($apiUrl);

        if (!empty($result['success'])) {
            $infoData = $result['data'];
            break;
        }

        error_log("详情 API 调用失败: HTTP {$result['http_code']}, Error: {$result['error']}, URL: {$apiUrl}");
    }
}

$movieName = htmlspecialchars($infoData['name'] ?? '影片详情');
$cover = htmlspecialchars($infoData['cover'] ?? '');
$firstPlayUrl = '';
if (!empty($infoData['play_links'][0]['episodes'][0]['url'])) {
    $firstPlayUrl = $infoData['play_links'][0]['episodes'][0]['url'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $movieName; ?> - 木子白白白影视</title>
    <meta name="description" content="Movie detail page with sources, episodes and basic metadata.">
    <link rel="shortcut icon" href="./assets/images/32.ico">
    <link rel="apple-touch-icon" href="./assets/images/64.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/claude-theme.css">
    <script src="./assets/js/jquery.min.js"></script>
    <script src="./assets/js/config.js"></script>
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
                    <li><a class="nav_links_link" href="./recommend.php">片单浏览</a></li>
                    <li><a class="nav_links_link" href="./core/">接口文档</a></li>
                    <li><a class="nav_links_link" href="./test/">接口中心</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="u-container-full hero-content">
                <div class="hero-eyebrow">影片详情</div>
                <h1 class="hero-title"><?php echo $movieName; ?></h1>
                <p class="hero-subtitle">在同一页里查看播放源、剧集列表、基础信息与剧情简介。</p>
                <div class="hero-search">
                    <form action="./" method="GET">
                        <div class="search-input-group">
                            <input type="text" name="v" id="wd" placeholder="搜索其他影片" autocomplete="off">
                            <button type="submit" class="search-btn btn">返回搜索</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="site-section">
            <div class="u-container-full">
                <?php if ($firstPlayUrl !== ''): ?>
                <div class="content-section">
                    <h3>播放窗口</h3>
                    <iframe id="videoPlayer" src="https://bd.jx.cn/?url=<?php echo urlencode($firstPlayUrl); ?>" width="100%" height="520" frameborder="0" allowfullscreen></iframe>
                </div>

                <div class="content-section">
                    <h3>解析线路</h3>
                    <div class="parser-buttons"></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($infoData['play_links']) && is_array($infoData['play_links'])): ?>
                <div class="content-section">
                    <h3>播放源与剧集</h3>
                    <?php foreach ($infoData['play_links'] as $sourceIndex => $source): ?>
                        <h4><?php echo htmlspecialchars($source['source'] ?? '播放源'); ?></h4>
                        <ul class="playlist-list">
                            <?php if (!empty($source['episodes']) && is_array($source['episodes'])): ?>
                                <?php foreach ($source['episodes'] as $episodeIndex => $episode): ?>
                                <li class="playlist-item">
                                    <a
                                        href="javascript:void(0);"
                                        data-url="<?php echo htmlspecialchars($episode['url'] ?? ''); ?>"
                                        class="playlist-link<?php echo ($sourceIndex === 0 && $episodeIndex === 0) ? ' active' : ''; ?>"
                                    >
                                        第<?php echo htmlspecialchars((string) ($episode['episode'] ?? '1')); ?>集
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="content-section">
                    <h3>影片信息</h3>
                    <div class="video-details-container">
                        <div class="video-poster">
                            <?php if ($cover !== ''): ?>
                            <img src="<?php echo $cover; ?>" alt="<?php echo $movieName; ?> 海报" width="280" height="380">
                            <?php else: ?>
                            <div class="plot-content">暂无海报</div>
                            <?php endif; ?>
                        </div>
                        <div class="video-info">
                            <div class="info-item">
                                <span class="info-label">评分</span>
                                <span class="info-value"><?php echo htmlspecialchars($infoData['rating'] ?? '暂无评分'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">类型</span>
                                <span class="info-value"><?php echo htmlspecialchars($infoData['type'] ?? '暂无信息'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">主演</span>
                                <span class="info-value"><?php echo htmlspecialchars($infoData['actors'] ?? '暂无信息'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">导演</span>
                                <span class="info-value"><?php echo htmlspecialchars($infoData['director'] ?? '暂无信息'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">年份</span>
                                <span class="info-value"><?php echo htmlspecialchars($infoData['year'] ?? '暂无信息'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">地区</span>
                                <span class="info-value"><?php echo htmlspecialchars($infoData['region'] ?? '暂无信息'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">观看方式</span>
                                <span class="info-value"><?php echo htmlspecialchars($infoData['watchType'] ?? '暂无信息'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-section">
                    <h3>剧情简介</h3>
                    <div class="plot-content"><?php echo nl2br(htmlspecialchars($infoData['description'] ?? '暂无简介')); ?></div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer_wrap">
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
        // 维护当前解析线路与当前播放地址
        let currentParser = "";
        let currentVideoUrl = <?php echo json_encode($firstPlayUrl, JSON_UNESCAPED_UNICODE); ?>;

        document.addEventListener("DOMContentLoaded", function () {
            const player = document.getElementById("videoPlayer");
            const parserButtons = document.querySelector(".parser-buttons");

            // 初始化解析线路按钮
            if (typeof PARSER_CONFIG !== "undefined" && parserButtons) {
                currentParser = PARSER_CONFIG[DEFAULT_PARSER_INDEX].url;
                parserButtons.innerHTML = "";

                PARSER_CONFIG.forEach(function (parser, index) {
                    const button = document.createElement("button");
                    button.type = "button";
                    button.className = "parser-btn" + (index === DEFAULT_PARSER_INDEX ? " active" : "");
                    button.dataset.url = parser.url;
                    button.textContent = parser.name || ("线路" + (index + 1));
                    parserButtons.appendChild(button);
                });
            }

            // 点击解析线路时切换当前播放器地址
            document.addEventListener("click", function (event) {
                if (event.target.classList.contains("parser-btn")) {
                    document.querySelectorAll(".parser-btn").forEach(function (button) {
                        button.classList.remove("active");
                    });
                    event.target.classList.add("active");
                    currentParser = event.target.dataset.url || "";

                    if (player && currentParser && currentVideoUrl) {
                        player.src = currentParser + encodeURIComponent(currentVideoUrl);
                    }
                }

                // 点击剧集时切换当前播放地址
                if (event.target.classList.contains("playlist-link")) {
                    document.querySelectorAll(".playlist-link").forEach(function (link) {
                        link.classList.remove("active");
                    });
                    event.target.classList.add("active");
                    currentVideoUrl = event.target.dataset.url || "";

                    if (player && currentParser && currentVideoUrl) {
                        player.src = currentParser + encodeURIComponent(currentVideoUrl);
                    }
                }
            });
        });
    </script>
</body>
</html>
