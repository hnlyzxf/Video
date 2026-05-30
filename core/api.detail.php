<?php
require_once __DIR__ . '/bootstrap.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// 处理测试请求
if (isset($_GET['test'])) {
    echo json_encode([
        'success' => true,
        'message' => 'PHP 剧集解析服务器运行正常',
        'time' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 目标URL
$url = isset($_GET['url']) && !empty($_GET['url']) ? $_GET['url'] : 'https://tv.contentchina.com/detail/66294.html';

// 检查cURL是否可用
if (!function_exists('curl_init')) {
    echo json_encode([
        'success' => false,
        'error' => 'cURL扩展未安装'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 设置cURL选项
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_USERAGENT => APP_REQUEST_USER_AGENT,
    CURLOPT_HTTPHEADER => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1'
    ],
    CURLOPT_ENCODING => 'gzip,deflate'
]);

// 执行请求
$content = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

// 检查请求是否成功
if ($content === false || !empty($error)) {
    echo json_encode([
        'success' => false,
        'error' => '网络请求失败: ' . $error,
        'url' => $url
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode([
        'success' => false,
        'error' => "HTTP错误码: $httpCode",
        'url' => $url
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 处理编码问题
if (strpos($info['content_type'], 'gbk') !== false || strpos($info['content_type'], 'gb2312') !== false) {
    $content = mb_convert_encoding($content, 'UTF-8', 'GBK');
}

// 解析播放源和集数信息
function parseSeriesData($htmlContent) {
    $result = [];
    $sourceEpisodes = [];
    
    // 匹配所有播放源的集数信息
    if (preg_match_all(
        '/<div class="series-con-a">.*?<a href="([^"]+)"[^>]*?data-ajax25source="([^"]+)"[^>]*?>(\d+)<\/a>.*?<\/div>/s',
        $htmlContent,
        $linkMatches,
        PREG_SET_ORDER
    )) {
        foreach ($linkMatches as $match) {
            $url = $match[1];
            $sourceName = $match[2];
            $episodeNum = intval($match[3]);
            
            // 确保URL是完整的
            if (strpos($url, '//') === 0) {
                $url = 'http:' . $url;
            }
            
            // 初始化播放源数组（如果不存在）
            if (!isset($sourceEpisodes[$sourceName])) {
                $sourceEpisodes[$sourceName] = [];
            }
            
            // 添加集数信息
            $sourceEpisodes[$sourceName][] = [
                'episode' => $episodeNum,
                'url' => $url
            ];
        }
    }
    
    // 处理单集播放源
    if (preg_match_all('/<div class="series-con-i[^"]*"[^>]*>(.*?)<\/div>/s', $htmlContent, $containers)) {
        foreach ($containers[1] as $container) {
            if (preg_match('/data-ajax25source="([^"]+)"/', $container, $sourceMatch)) {
                $sourceName = $sourceMatch[1];
                if (preg_match('/<a href=\'([^\']+)\'[^>]*class="series-con-search series-con-play"[^>]*>/', $container, $playMatch)) {
                    $url = $playMatch[1];
                    
                    // 确保URL是完整的
                    if (strpos($url, '//') === 0) {
                        $url = 'http:' . $url;
                    }
                    
                    // 初始化播放源数组（如果不存在）
                    if (!isset($sourceEpisodes[$sourceName])) {
                        $sourceEpisodes[$sourceName] = [];
                    }
                    
                    // 添加集数信息
                    $sourceEpisodes[$sourceName][] = [
                        'episode' => 1,
                        'url' => $url
                    ];
                }
            }
        }
    }
    
    // 将收集的播放源和集数信息转换为结果数组
    foreach ($sourceEpisodes as $sourceName => $episodes) {
        // 按集数升序排序
        usort($episodes, function($a, $b) {
            return $a['episode'] - $b['episode'];
        });
        
        $result[] = [
            'source' => $sourceName,
            'episodes' => $episodes
        ];
    }
    
    return $result;
}

function displaySeriesData($seriesData) {
    foreach ($seriesData as $sourceData) {
        echo "播放源：" . $sourceData['source'] . "\n\n";
        
        foreach ($sourceData['episodes'] as $episode) {
            echo "集数：" . $episode['episode'] . "，播放地址：" . $episode['url'] . "\n";
        }
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
}

// 解析电影详情数据
function parseMovieDetail($content) {
    $movieDetail = [
        'name' => '',
        'rating' => '',
        'cover' => '',
        'actors' => '',
        'director' => '',
        'type' => '',
        'region' => '',
        'year' => '',
        'watchType' => '',
        'description' => ''
    ];
    
    // 解析名称 - 从海报区域的data-ajax25属性中获取
    if (preg_match('/class="aPlayBtn_show"[^>]*data-ajax25="([^"]+)"/', $content, $nameMatch)) {
        $movieDetail['name'] = trim($nameMatch[1]);
    }
    // 备用方法: 从标题中获取
    else if (preg_match('/<title>(.*?)[-_《]/', $content, $nameMatch)) {
        $movieDetail['name'] = trim($nameMatch[1]);
    }

    // 解析封面图片 - 匹配poster-img的background-image
    if (preg_match('/class="poster-img"[^>]*style="background-image:url\([\'"]?([^\'"]+)[\'"]?\)/', $content, $coverMatch)) {
        $movieDetail['cover'] = buildCoverProxyUrl($coverMatch[1]);
    }
    
    // 解析评分 - 从emScore标签中提取
    if (preg_match('/<em class="emScore"[^>]*>([^<]+)<\/em>/', $content, $ratingMatch)) {
        $movieDetail['rating'] = trim($ratingMatch[1]);
    }
    
    // 解析导演信息 - 查找包含"导演"的li标签和emTit-l类
    if (preg_match('/<em class="emTit"[^>]*>导&nbsp;&nbsp;演：<\/em>.*?<div class="emTit-l">(.*?)<\/div>/s', $content, $directorMatch)) {
        $directorContent = $directorMatch[1];
        $directors = [];
        if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/', $directorContent, $directorMatches)) {
            $directors = array_map('trim', $directorMatches[1]);
        }
        $movieDetail['director'] = implode(' / ', $directors);
    }
    
    // 解析演员信息 - 查找包含"演员"的li标签和emTit-l类
    if (preg_match('/<em class="emTit"[^>]*>演&nbsp;&nbsp;员：<\/em>.*?<div class="emTit-l">(.*?)<\/div>/s', $content, $actorMatch)) {
        $actorContent = $actorMatch[1];
        $actors = [];
        if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/', $actorContent, $actorMatches)) {
            $actors = array_map('trim', $actorMatches[1]);
        }
        $movieDetail['actors'] = implode(' / ', $actors);
    }
    
    // 从data-ajax25tab属性解析类型、地区、年份、标签等信息
    if (preg_match('/data-ajax25tab="([^"]+)"/', $content, $tabMatch)) {
        $tabData = $tabMatch[1];
        $parts = explode('|', $tabData);
        
        if (count($parts) >= 4) {
            // 第一部分是类型
            $movieDetail['type'] = trim($parts[0]);
            // 第二部分是地区
            $movieDetail['region'] = trim($parts[1]);
            // 第三部分是年份
            $movieDetail['year'] = trim($parts[2]);
            // 第四部分是观看类型
            $movieDetail['watchType'] = trim($parts[3]);
        }
    }
    
    // 解析简介 - 查找pIntro pHide类
    if (preg_match('/<p class="pIntro pHide"[^>]*>(.*?)<\/p>/s', $content, $descMatch)) {
        $movieDetail['description'] = trim(strip_tags($descMatch[1]));
    }
    
    return $movieDetail;
}

try {
    // 解析HTML内容获取剧集数据
    $seriesData = parseSeriesData($content);
    
    // 解析电影详情数据
    $movieDetail = parseMovieDetail($content);
    
    // 输出JSON格式的结果
    echo json_encode([
        'success' => true,
        'name' => $movieDetail['name'],
        'rating' => $movieDetail['rating'],
        'cover' => $movieDetail['cover'],
        'actors' => $movieDetail['actors'],
        'director' => $movieDetail['director'],
        'type' => $movieDetail['type'],
        'region' => $movieDetail['region'],
        'year' => $movieDetail['year'],
        'watchType' => $movieDetail['watchType'],
        'description' => $movieDetail['description'],
        'play_links' => $seriesData,
        'info' => [
            'url' => $url,
            'http_code' => $httpCode,
            'content_length' => strlen($content),
            'content_type' => $info['content_type']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '解析错误: ' . $e->getMessage(),
        'url' => $url
    ], JSON_UNESCAPED_UNICODE);
}
?>
