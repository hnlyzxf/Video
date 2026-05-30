<?php

const APP_REQUEST_USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET4.0C; .NET4.0E; rv:11.0) like Gecko';

function detectRequestScheme(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        if (in_array($forwardedProto, ['http', 'https'], true)) {
            return $forwardedProto;
        }
    }

    if (!empty($_SERVER['REQUEST_SCHEME']) && in_array($_SERVER['REQUEST_SCHEME'], ['http', 'https'], true)) {
        return $_SERVER['REQUEST_SCHEME'];
    }

    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
    ) {
        return 'https';
    }

    return 'http';
}

function getAppBasePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', dirname($scriptName));

    if ($scriptDir === '/' || $scriptDir === '.') {
        return '';
    }

    $lastSegment = basename($scriptDir);
    if (in_array($lastSegment, ['core', 'test'], true)) {
        $parentDir = str_replace('\\', '/', dirname($scriptDir));
        return ($parentDir === '/' || $parentDir === '.') ? '' : rtrim($parentDir, '/');
    }

    return rtrim($scriptDir, '/');
}

function buildAppUrl(string $path, array $query = []): string
{
    $normalizedPath = '/' . ltrim($path, '/');
    $basePath = getAppBasePath();
    $fullPath = ($basePath === '' ? '' : $basePath) . $normalizedPath;

    if (!empty($_SERVER['HTTP_HOST'])) {
        $url = detectRequestScheme() . '://' . $_SERVER['HTTP_HOST'] . $fullPath;
    } else {
        $url = $fullPath;
    }

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function normalizeExternalUrl(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (strpos($url, '//') === 0) {
        return 'https:' . $url;
    }

    return $url;
}

function buildCoverProxyUrl(string $url): string
{
    $url = normalizeExternalUrl($url);
    if ($url === '') {
        return '';
    }

    $parsedUrl = parse_url($url);
    if (!is_array($parsedUrl) || empty($parsedUrl['scheme']) || empty($parsedUrl['host'])) {
        return $url;
    }

    if (!in_array(strtolower($parsedUrl['scheme']), ['http', 'https'], true)) {
        return $url;
    }

    if (!empty($_SERVER['HTTP_HOST']) && strcasecmp($parsedUrl['host'], $_SERVER['HTTP_HOST']) === 0) {
        return $url;
    }

    return buildAppUrl('/image.php', ['url' => $url]);
}
