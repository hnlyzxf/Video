<?php
require_once __DIR__ . '/../auth.php';
requireAccess('详情接口验证');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Validation page for the detail endpoint.">
    <title>详情接口验证 - 木子白白白影视</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                <div class="hero-eyebrow">详情接口验证</div>
                <h1 class="hero-title">验证影片详情与播放源结构</h1>
                <p class="hero-subtitle">输入详情页地址后，查看海报、基础信息、播放源与原始 JSON。</p>
            </div>
        </section>

        <section class="site-section">
            <div class="u-container-full">
                <div class="search-form">
                    <h2>验证参数</h2>
                    <form id="detailForm">
                        <div class="form-group">
                            <label for="urlInput">详情页地址</label>
                            <input type="text" id="urlInput" value="https://tv.contentchina.com/detail/66294.html" placeholder="Movie detail URL">
                        </div>
                        <button type="button" class="btn" onclick="testAPI()">发起请求</button>
                        <button type="button" class="btn btn-secondary" onclick="testDefault()">使用默认地址</button>
                    </form>
                </div>

                <div id="apiUrl" class="json-output" style="display:block;"></div>
                <div class="loading" id="loading" style="display:none;">正在加载...</div>
                <div id="result" class="results-container"></div>
            </div>
        </section>
    </main>

    <script>
        async function testAPI() {
            const url = document.getElementById('urlInput').value.trim();
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            const apiUrlDisplay = document.getElementById('apiUrl');

            if (!url) {
                alert('请输入详情页地址');
                return;
            }

            const apiUrl = `../core/api.detail.php?url=${encodeURIComponent(url)}`;
            apiUrlDisplay.textContent = `API URL: ${apiUrl}`;
            loading.style.display = 'block';
            result.innerHTML = '';

            try {
                const response = await fetch(apiUrl);
                const data = await response.json();
                loading.style.display = 'none';

                if (!data.success) {
                    result.innerHTML = `<div class="error">请求失败：${data.error || '未知错误'}</div>`;
                    return;
                }

                displayMovieDetail(data);
                displayRawData(data);
            } catch (error) {
                loading.style.display = 'none';
                result.innerHTML = `<div class="error">请求失败：${error.message}</div>`;
            }
        }

        function testDefault() {
            document.getElementById('urlInput').value = 'https://dianying.contentchina.com/detail/222802.html';
            testAPI();
        }

        function displayMovieDetail(movie) {
            const resultDiv = document.getElementById('result');
            let linksHtml = '<p>暂无播放源</p>';

            if (Array.isArray(movie.play_links) && movie.play_links.length > 0) {
                linksHtml = movie.play_links.map(function (source) {
                    const episodes = Array.isArray(source.episodes)
                        ? source.episodes.map(function (episode) {
                            return `<a class="play-link" target="_blank" href="${episode.url}">第 ${episode.episode} 集</a>`;
                        }).join('')
                        : '';

                    return `
                        <div class="content-section" style="margin-top:16px;">
                            <h4>${source.source || '默认源'}</h4>
                            <div>${episodes || '暂无剧集'}</div>
                        </div>
                    `;
                }).join('');
            }

            resultDiv.innerHTML = `
                <div class="content-section">
                    <h3>${movie.name || '未命名影片'}</h3>
                    <div class="movie-detail">
                        <div class="movie-poster">
                            ${movie.cover ? `<img src="${movie.cover}" alt="${movie.name || '影片'}">` : '<div class="plot-content">暂无海报</div>'}
                        </div>
                        <div class="video-info">
                            <div class="info-item"><span class="info-label">评分</span><span class="info-value">${movie.rating || '暂无评分'}</span></div>
                            <div class="info-item"><span class="info-label">类型</span><span class="info-value">${movie.type || '暂无'}</span></div>
                            <div class="info-item"><span class="info-label">地区</span><span class="info-value">${movie.region || '暂无'}</span></div>
                            <div class="info-item"><span class="info-label">年份</span><span class="info-value">${movie.year || '暂无'}</span></div>
                            <div class="info-item"><span class="info-label">导演</span><span class="info-value">${movie.director || '暂无'}</span></div>
                            <div class="info-item"><span class="info-label">主演</span><span class="info-value">${movie.actors || '暂无'}</span></div>
                        </div>
                    </div>
                    <div class="content-section" style="margin-top:16px;">
                        <h3>剧情简介</h3>
                        <div class="plot-content">${movie.description || '暂无简介'}</div>
                    </div>
                    <div class="content-section" style="margin-top:16px;">
                        <h3>播放源</h3>
                        ${linksHtml}
                    </div>
                </div>
            `;
        }

        function displayRawData(data) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML += `
                <div class="content-section">
                    <h3>原始 JSON</h3>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                </div>
            `;
        }

        window.onload = function () {
            testDefault();
        };
    </script>
</body>
</html>
