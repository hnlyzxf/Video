<?php
require_once __DIR__ . '/bootstrap.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否是直接访问API（而不是被包含）
$isDirectAccess = !defined('INCLUDED_FROM_SEARCH');

if ($isDirectAccess) {
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
            'message' => 'PHP 搜索服务器运行正常',
            'time' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 获取搜索关键词和页码
$keyword = isset($_GET['keyword']) && !empty($_GET['keyword']) ? $_GET['keyword'] : '哪吒';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;

// 每次请求两页数据，计算实际的两个页码
$firstPage = ($page * 2) - 1;
$secondPage = $page * 2;

// 创建一个函数来获取指定页码的内容
function fetchPageContent($keyword, $pageNum) {
    // 构建搜索URL
    $url = 'https://so-kan.contentchina.com/search_' . urlencode($keyword) . '/?' . $pageNum;
    
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
    if ($content === false || !empty($error) || $httpCode !== 200) {
        return [
            'success' => false,
            'error' => !empty($error) ? '网络请求失败: ' . $error : "HTTP错误码: $httpCode",
            'url' => $url,
            'content' => null,
            'info' => $info
        ];
    }
    
    // 处理编码问题
    if (strpos($info['content_type'], 'gbk') !== false || strpos($info['content_type'], 'gb2312') !== false) {
        $content = mb_convert_encoding($content, 'UTF-8', 'GBK');
    }
    
    return [
        'success' => true,
        'content' => $content,
        'url' => $url,
        'info' => $info
    ];
}

// 检查cURL是否可用
if (!function_exists('curl_init')) {
    echo json_encode([
        'success' => false,
        'error' => 'cURL扩展未安装'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取第一页内容
$firstPageResult = fetchPageContent($keyword, $firstPage);
if (!$firstPageResult['success']) {
    echo json_encode([
        'success' => false,
        'error' => $firstPageResult['error'],
        'url' => $firstPageResult['url']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取第二页内容
$secondPageResult = fetchPageContent($keyword, $secondPage);
// 如果第二页获取失败，我们仍然可以继续处理第一页的内容
$hasSecondPage = $secondPageResult['success'];

// 使用第一页的内容作为主要内容
$content = $firstPageResult['content'];
$info = $firstPageResult['info'];

// 解析搜索结果
function parseSearchResults($content) {
    $results = [];
    
    // 匹配每个搜索结果项
    if (preg_match_all('/<div class="posterPlaceholder">(.*?)<div class="clear"><\/div>\s*<\/div>/s', $content, $matches)) {
        foreach ($matches[1] as $resultItem) {
            $item = [
                'name' => '',
                'cover' => '',
                'rating' => '',
                'year' => '',
                'category' => '',
                'remark' => '',
                'region' => '',
                'playUrl' => '',
                'director' => '',
                'genres' => [],
                'description' => ''
            ];
            
            // 提取名称
            if (preg_match('/data-ajax25="([^"]+)"/', $resultItem, $nameMatch)) {
                $item['name'] = trim($nameMatch[1]);
            }
            
            // 提取封面图片
            if (preg_match('/data-src="([^"]+)"/', $resultItem, $coverMatch)) {
                $item['cover'] = buildCoverProxyUrl($coverMatch[1]);
            }
            
            // 提取分类
            if (preg_match('/<span class="sStyle">([^<]+)<\/span>/', $resultItem, $categoryMatch)) {
                $item['category'] = trim($categoryMatch[1]);
            }
            
            // 提取评分 - 从sScore标签中获取
            if (preg_match('/<span class="sScore">([^<]+)<\/span>/', $resultItem, $ratingMatch)) {
                // 移除"分"字
                $rating = str_replace('分', '', trim($ratingMatch[1]));
                $item['rating'] = $rating;
            }
            
            // 如果没有找到评分，尝试从其他位置获取
            if (empty($item['rating'])) {
                // 尝试从pIntro标签中获取评分
                if (preg_match('/<p class="pIntro"><span[^>]*>(\d+(?:\.\d+)?)分<\/span>/', $resultItem, $ratingMatch)) {
                    $item['rating'] = trim($ratingMatch[1]);
                }
                // 尝试从右侧span标签获取评分
                else if (preg_match('/<span class="right">(\d+(?:\.\d+)?)分<\/span>/', $resultItem, $ratingMatch)) {
                    $item['rating'] = trim($ratingMatch[1]);
                }
            }
            
            // 提取年份 - 从sTime标签中获取
            if (preg_match('/<span class="sTime">(\d+)<\/span>/', $resultItem, $yearMatch)) {
                $item['year'] = trim($yearMatch[1]);
            }
            
            // 提取年份、类型、地区等信息
            if (preg_match('/data-ajax25tab="([^"]+)"/', $resultItem, $tabMatch)) {
                $tabData = $tabMatch[1];
                $parts = explode('|', $tabData);
                
                if (count($parts) >= 3) {
                    // 第一部分是类型
                    if (!empty(trim($parts[0]))) {
                        $item['genres'] = array_map('trim', explode(',', $parts[0]));
                    }
                    // 第二部分是地区
                    $item['region'] = trim($parts[1]);
                    // 第三部分是年份
                    $item['year'] = trim($parts[2]);
                }
            }
            
            // 提取播放链接
            if (preg_match('/<a href="([^"]+)"[^>]*class="aPlayBtn"/', $resultItem, $playUrlMatch)) {
                $playUrl = trim($playUrlMatch[1]);
                // 确保URL是完整的
                if (strpos($playUrl, '//') === 0) {
                    $playUrl = 'https:' . $playUrl;
                }
                $item['playUrl'] = $playUrl;
            }
            
            // 提取导演信息
            if (preg_match('/<li[^>]*><em class="emTit">导演：<\/em>(.*?)<\/li>/s', $resultItem, $directorMatch)) {
                $directorContent = $directorMatch[1];
                $directors = [];
                if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/', $directorContent, $directorMatches)) {
                    $directors = array_map('trim', $directorMatches[1]);
                }
                $item['director'] = implode(' / ', $directors);
            }
                        
            // 提取简介
            if (preg_match('/<span class="sAll">(.*?)<\/span>/', $resultItem, $descMatch)) {
                $item['description'] = trim(strip_tags($descMatch[1]));
            } elseif (preg_match('/<span class="sPart">(.*?)<\/span>/', $resultItem, $descMatch)) {
                $item['description'] = trim(strip_tags($descMatch[1]));
            }
            
            // 提取remark信息 - 从多个可能的位置获取
            $remarkFound = false;
            
            // 方法1: 从pRightBottom类中获取（优先级最高）
            if (preg_match('/<p class="pRightBottom"[^>]*>(.*?)<\/p>/s', $resultItem, $remarkMatch)) {
                $item['remark'] = trim(strip_tags($remarkMatch[1]));
                $remarkFound = true;
            }
            // 方法2: 从season类中获取
            elseif (preg_match('/<div class="season"[^>]*>(.*?)<\/div>/s', $resultItem, $remarkMatch)) {
                $item['remark'] = trim(strip_tags($remarkMatch[1]));
                $remarkFound = true;
            }
            // 方法3: 从sRemark类获取
            elseif (preg_match('/<span class="sRemark"[^>]*>(.*?)<\/span>/s', $resultItem, $remarkMatch)) {
                $item['remark'] = trim(strip_tags($remarkMatch[1]));
                $remarkFound = true;
            }
            // 方法4: 从sUpdate类获取
            elseif (preg_match('/<span class="sUpdate"[^>]*>(.*?)<\/span>/s', $resultItem, $remarkMatch)) {
                $item['remark'] = trim(strip_tags($remarkMatch[1]));
                $remarkFound = true;
            }
            // 方法5: 从其他可能包含集数信息的标签获取
            elseif (preg_match('/(\d+集全|\d+集|\d+话|完结|更新至第\d+集|更新至\d+集)/i', $resultItem, $remarkMatch)) {
                $item['remark'] = trim($remarkMatch[1]);
                $remarkFound = true;
            }
            // 方法6: 从任何包含"集"字的span标签获取
            elseif (preg_match('/<span[^>]*>([^<]*\d+集[^<]*)<\/span>/i', $resultItem, $remarkMatch)) {
                $item['remark'] = trim(strip_tags($remarkMatch[1]));
                $remarkFound = true;
            }
            
            // 调试信息 - 如果没有找到remark，记录原始HTML片段用于分析
            if (!$remarkFound && !empty($item['name'])) {
                // 可以在这里添加调试日志
                error_log("未找到remark信息，影片: " . $item['name']);
            }
            
            $results[] = $item;
        }
    }
    
    return $results;
}

// 解析分页信息
function parsePagination($content) {
    $pagination = [
        'current_page' => 1,
        'total_pages' => 1,
        'has_next' => false,
        'has_prev' => false
    ];
    
    // 提取当前页码
    if (preg_match('/<span class="cur">(\d+)<\/span>/', $content, $currentMatch)) {
        $pagination['current_page'] = intval($currentMatch[1]);
    }
    
    // 提取总页数
    if (preg_match_all('/<a[^>]*href="[^"]*\/\?(\d+)"[^>]*>\d+<\/a>/', $content, $pageMatches)) {
        $pages = array_map('intval', $pageMatches[1]);
        if (!empty($pages)) {
            $pagination['total_pages'] = max($pages);
        }
    }
    
    // 判断是否有下一页和上一页
    $pagination['has_next'] = $pagination['current_page'] < $pagination['total_pages'];
    $pagination['has_prev'] = $pagination['current_page'] > 1;
    
    return $pagination;
}

try {
    // 解析第一页的搜索结果
    $firstPageSearchResults = parseSearchResults($firstPageResult['content']);
    $pagination = parsePagination($firstPageResult['content']);
    
    // 合并结果
    $allSearchResults = $firstPageSearchResults;
    
    // 如果第二页获取成功，解析并合并结果
    if ($hasSecondPage) {
        $secondPageSearchResults = parseSearchResults($secondPageResult['content']);
        $allSearchResults = array_merge($allSearchResults, $secondPageSearchResults);
        
        // 更新分页信息
        $secondPagePagination = parsePagination($secondPageResult['content']);
        if ($secondPagePagination['total_pages'] > $pagination['total_pages']) {
            $pagination['total_pages'] = $secondPagePagination['total_pages'];
        }
    }
    
    // 调整分页信息以反映我们是两页两页地翻
    $pagination['current_page'] = $page;
    $pagination['total_pages'] = ceil($pagination['total_pages'] / 2);
    $pagination['has_next'] = $page < $pagination['total_pages'];
    $pagination['has_prev'] = $page > 1;
    
    echo json_encode([
        'success' => true,
        'keyword' => $keyword,
        'count' => count($allSearchResults),
        'data' => $allSearchResults,
        'pagination' => $pagination,
        'info' => [
            'urls' => [
                $firstPageResult['url'],
                $hasSecondPage ? $secondPageResult['url'] : null
            ],
            'page_requested' => $page,
            'actual_pages' => [$firstPage, $hasSecondPage ? $secondPage : null],
            'content_length' => strlen($firstPageResult['content']) + ($hasSecondPage ? strlen($secondPageResult['content']) : 0)
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '解析错误: ' . $e->getMessage(),
        'url' => $firstPageResult['url']
    ], JSON_UNESCAPED_UNICODE);
}
?>
