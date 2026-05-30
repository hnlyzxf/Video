<?php
require_once __DIR__ . '/bootstrap.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理测试请求
if (isset($_GET['test'])) {
    echo json_encode([
        'success' => true,
        'message' => 'PHP 服务器运行正常',
        'time' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 目标URL
$url = isset($_GET['url']) && !empty($_GET['url']) ? $_GET['url'] : 'https://dianying.contentchina.com/list/------.html';

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

// 解析分类链接
function parseCategories($content) {
    $categories = [
        'main' => [],      // 主分类 (selectList-wrap)
        'sort' => [],      // 排序
        'type' => [],      // 类型
        'region' => []     // 地区
    ];
    
    // 1. 解析主分类 (selectList-wrap)
    if (preg_match('/<div class="selectList-wrap"[^>]*>(.*?)<\/div>/s', $content, $selectMatch)) {
        $selectContent = $selectMatch[1];
        
        // 提取所有的 a 标签链接
        if (preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>([^<]+)<\/a>/', $selectContent, $linkMatches, PREG_SET_ORDER)) {
            foreach ($linkMatches as $linkMatch) {
                $href = trim($linkMatch[1]);
                $text = trim($linkMatch[2]);
                
                // 跳过空链接或无效链接
                if (empty($href) || empty($text) || $href === '#') {
                    continue;
                }
                
                // 确保URL是完整的
                if (strpos($href, '//') === 0) {
                    $href = 'https:' . $href;
                } elseif (strpos($href, 'http') !== 0) {
                    $href = 'http://' . $href;
                }
                
                $categories['main'][] = [
                    'url' => $href,
                    'name' => $text
                ];
            }
        }
    }
    
    // 2. 解析详细分类 (selectList clearfix)
    if (preg_match('/<ul class="selectList clearfix">(.*?)<\/ul>/s', $content, $selectMatch)) {
        $selectContent = $selectMatch[1];
        
        // 匹配每个 li 元素
        if (preg_match_all('/<li>(.*?)<\/li>/s', $selectContent, $liMatches, PREG_SET_ORDER)) {
            foreach ($liMatches as $liMatch) {
                $liContent = $liMatch[1];
                
                // 提取标题
                $title = '';
                if (preg_match('/<span class="sTit">([^<]+)<\/span>/', $liContent, $titleMatch)) {
                    $title = trim($titleMatch[1]);
                }
                
                // 提取链接
                if (preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>([^<]+)<\/a>/', $liContent, $linkMatches, PREG_SET_ORDER)) {
                    $links = [];
                    foreach ($linkMatches as $linkMatch) {
                        $href = $linkMatch[1];
                        $text = trim($linkMatch[2]);
                        
                        // 确保URL是完整的
                        if (strpos($href, '//') === 0) {
                            $href = 'https:' . $href;
                        }
                        
                        $links[] = [
                            'url' => $href,
                            'name' => $text
                        ];
                    }
                    
                    // 根据标题分类
                    switch ($title) {
                        case '排序':
                            $categories['sort'] = $links;
                            break;
                        case '类型':
                            $categories['type'] = $links;
                            break;
                        case '地区':
                            $categories['region'] = $links;
                            break;
                    }
                }
            }
        }
    }
    
    return $categories;
}

// 解析v_tb部分的数据
function parseVtbData($content) {
    $results = [];
    
    // 使用正则表达式匹配li标签及其内容
    if (preg_match_all('/<li media="(\d+)".*?>(.*?)<\/li>/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $id = $match[1];
            $liContent = $match[2];
            
            // 提取封面图片
            $cover = '';
            if (preg_match('/data-src="([^"]+)"/', $liContent, $imgMatch)) {
                $cover = buildCoverProxyUrl($imgMatch[1]);
            }

            // 提取播放链接
            $playUrl = '';
            if (preg_match('/href="([^"]+)"/', $liContent, $playUrlMatch)) {
                $playUrl = trim($playUrlMatch[1]);
                // 确保URL是完整的
                if (strpos($playUrl, '//') === 0) {
                    $playUrl = 'https:' . $playUrl;
                } elseif (strpos($playUrl, 'http') !== 0 && !empty($playUrl)) {
                    // 如果是相对路径，构建完整URL
                    $baseUrl = parse_url($url);
                    $playUrl = $baseUrl['scheme'] . '://' . $baseUrl['host'] . $playUrl;
                }
            }
            
            // 提取名称
            $name = '';
            if (preg_match('/alt="([^"]+)"/', $liContent, $nameMatch)) {
                $name = trim($nameMatch[1]);
            }
            
            // 提取播放源
            $sources = [];
            if (preg_match('/data-ajax25source="([^"]+)"/', $liContent, $sourceMatch)) {
                $sources = array_map('trim', explode(',', $sourceMatch[1]));
            }
            
            // 提取分类
            $category = '';
            if (preg_match('/data-ajax25form="([^"]+)"/', $liContent, $categoryMatch)) {
                $category = trim($categoryMatch[1]);
            }
            
            // 提取类型、地区、年份
            $genres = [];
            $region = '';
            $year = '';
            if (preg_match('/data-ajax25tab="([^"]+)"/', $liContent, $tabMatch)) {
                $tabData = $tabMatch[1];
                $parts = explode('|', $tabData);
                
                if (count($parts) >= 3) {
                    // 第一部分是类型
                    if (!empty(trim($parts[0]))) {
                        $genres = array_map('trim', explode(',', $parts[0]));
                    }
                    // 第二部分是地区
                    $region = trim($parts[1]);
                    // 第三部分是年份
                    $year = trim($parts[2]);
                }
            }
            
            // 提取评分
            $rating = '';
            if (preg_match('/<em>([^<]+)<\/em>/', $liContent, $ratingMatch)) {
                $rating = trim($ratingMatch[1]);
            }
            
            $results[] = [
                'id' => $id,
                'cover' => $cover,
                'name' => $name,
                'playUrl' => $playUrl,
                'sources' => $sources,
                'category' => $category,
                'genres' => $genres,
                'region' => $region,
                'year' => $year,
                'rating' => $rating
            ];
        }
    }
    
    return $results;
}

try {
    $movieData = parseVtbData($content);
    $categories = parseCategories($content);
    
    echo json_encode([
        'success' => true,
        'count' => count($movieData),
        'data' => $movieData,
        'categories' => $categories,
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
