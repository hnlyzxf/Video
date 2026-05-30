<?php
require_once __DIR__ . '/core/bootstrap.php';

function sendImageError(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

function createImageRequestHeaders(string $targetUrl, ?string $referer = null): array
{
    $headers = [
        'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
        'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Sec-Fetch-Dest: image',
        'Sec-Fetch-Mode: no-cors',
        'Sec-Fetch-Site: cross-site',
    ];

    if ($referer !== null && $referer !== '') {
        $headers[] = 'Referer: ' . $referer;

        $refererScheme = parse_url($referer, PHP_URL_SCHEME);
        $refererHost = parse_url($referer, PHP_URL_HOST);
        if (is_string($refererScheme) && is_string($refererHost) && $refererScheme !== '' && $refererHost !== '') {
            $headers[] = 'Origin: ' . $refererScheme . '://' . $refererHost;
        }
    }

    $host = parse_url($targetUrl, PHP_URL_HOST);
    if (is_string($host) && $host !== '') {
        $headers[] = 'Host: ' . $host;
    }

    return $headers;
}

function getRefererCandidates(string $targetUrl): array
{
    $host = strtolower((string) parse_url($targetUrl, PHP_URL_HOST));
    $scheme = (string) parse_url($targetUrl, PHP_URL_SCHEME);
    $origin = ($scheme !== '' && $host !== '') ? ($scheme . '://' . $host . '/') : '';
    $referers = $origin !== '' ? [$origin] : [];

    if (strpos($host, '2345cdn.net') !== false || strpos($host, '2345.com') !== false) {
        array_unshift($referers, 'https://dianying.2345.com/', 'https://tv.2345.com/');
    }

    return array_values(array_unique(array_filter($referers)));
}

function fetchImageContent(string $targetUrl): array
{
    $attemptReferers = array_merge([null], getRefererCandidates($targetUrl));

    foreach ($attemptReferers as $referer) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $targetUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT => APP_REQUEST_USER_AGENT,
            CURLOPT_HTTPHEADER => createImageRequestHeaders($targetUrl, $referer),
            CURLOPT_ENCODING => '',
            CURLOPT_HEADER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($response === false || $error !== '') {
            continue;
        }

        $body = substr($response, $headerSize);
        if ($statusCode === 200 && strpos(strtolower($contentType), 'image/') === 0) {
            return [
                'success' => true,
                'content' => $body,
                'content_type' => $contentType,
            ];
        }
    }

    return ['success' => false];
}

if (!function_exists('curl_init')) {
    sendImageError(500, 'cURL extension is not available.');
}

$targetUrl = normalizeExternalUrl($_GET['url'] ?? '');
if ($targetUrl === '') {
    sendImageError(400, 'Missing image URL.');
}

$parsedUrl = parse_url($targetUrl);
if (
    !is_array($parsedUrl) ||
    empty($parsedUrl['scheme']) ||
    empty($parsedUrl['host']) ||
    !in_array(strtolower($parsedUrl['scheme']), ['http', 'https'], true)
) {
    sendImageError(400, 'Invalid image URL.');
}

$result = fetchImageContent($targetUrl);
if (empty($result['success'])) {
    sendImageError(502, 'Failed to fetch remote image.');
}

header('Content-Type: ' . $result['content_type']);
header('Cache-Control: public, max-age=86400');
header('Access-Control-Allow-Origin: *');
echo $result['content'];
