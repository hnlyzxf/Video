<?php
require_once __DIR__ . '/../auth.php';
requireAccess('搜索接口验证');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Validation page for the search endpoint.">
    <title>搜索接口验证 - 木子白白白影视</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet" type="text/css">
    <link href="../assets/css/claude-theme.css" rel="stylesheet" type="text/css">
    <style>
        /* 搜索验证页使用紧凑结果行，避免继承详情页的大海报尺寸 */
        #resultsContainer .movie-item {
            align-items: flex-start;
        }

        #resultsContainer .movie-poster {
            width: 96px;
            min-width: 96px;
            height: 136px;
            object-fit: cover;
            border-radius: 12px;
            flex: 0 0 96px;
        }

        #resultsContainer .plot-content {
            width: 96px;
            min-width: 96px;
            min-height: 136px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            box-sizing: border-box;
            text-align: center;
        }
    </style>
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
                <div class="hero-eyebrow">搜索接口验证</div>
                <h1 class="hero-title">验证关键词搜索与分页结构</h1>
                <p class="hero-subtitle">输入关键词和页码后，检查搜索接口返回内容与影片结果卡片。</p>
            </div>
        </section>

        <section class="site-section">
            <div class="u-container-full">
                <div class="search-form">
                    <h2>验证参数</h2>
                    <form id="searchForm">
                        <div class="form-group">
                            <label for="keyword">搜索关键词</label>
                            <input type="text" id="keyword" name="keyword" placeholder="Movie keyword" value="复仇者联盟">
                        </div>
                        <div class="form-group">
                            <label for="page">页码</label>
                            <input type="number" id="page" name="page" min="1" value="1">
                        </div>
                        <button type="submit" class="btn">开始验证</button>
                        <button type="button" class="btn btn-secondary" onclick="clearResults()">清空结果</button>
                    </form>
                </div>

                <div id="apiUrl" class="json-output" style="display:block;"></div>
                <div id="resultsContainer" class="results-container"></div>
                <div id="jsonOutput" class="json-output" style="display:none;"></div>
            </div>
        </section>
    </main>

    <script src="../assets/js/jquery.min.js"></script>
    <script>
        $(document).ready(function () {
            // 提交表单时发起搜索验证
            $('#searchForm').on('submit', function (event) {
                event.preventDefault();
                performSearch();
            });

            function performSearch() {
                const keyword = $('#keyword').val().trim();
                const page = $('#page').val() || 1;

                if (!keyword) {
                    alert('请输入搜索关键词');
                    return;
                }

                const apiUrl = `../core/api.search.php?keyword=${encodeURIComponent(keyword)}&page=${page}`;
                $('#apiUrl').text(`API URL: ${apiUrl}`);
                $('#resultsContainer').html('<div class="loading">正在搜索...</div>');
                $('#jsonOutput').hide();

                $.ajax({
                    url: apiUrl,
                    method: 'GET',
                    dataType: 'json',
                    timeout: 30000,
                    success: function (data) {
                        displayResults(data);
                        $('#jsonOutput').text(JSON.stringify(data, null, 2)).show();
                    },
                    error: function (xhr, status, error) {
                        const message = status === 'timeout' ? '请求超时，请稍后重试。' : `请求失败：${error}`;
                        $('#resultsContainer').html(`<div class="error">${message}</div>`);
                    }
                });
            }

            function displayResults(data) {
                const movies = Array.isArray(data.data) ? data.data : [];
                if (movies.length === 0) {
                    $('#resultsContainer').html('<div class="error">没有匹配结果。</div>');
                    return;
                }

                let html = `
                    <div class="content-section">
                        <h3>搜索结果</h3>
                        <p class="section-copy">共返回 ${data.count || movies.length} 条结果。</p>
                    </div>
                `;

                movies.forEach(function (movie) {
                    html += `
                        <div class="movie-item">
                            ${movie.cover ? `<img src="${movie.cover}" alt="${movie.name || '影片'}" class="movie-poster">` : '<div class="plot-content">暂无海报</div>'}
                            <div class="movie-info">
                                <div class="movie-title">${movie.name || '未命名影片'}</div>
                                <div class="movie-meta">类型：${movie.category || movie.type || '暂无'}</div>
                                <div class="movie-meta">地区：${movie.region || '暂无'} / 年份：${movie.year || '暂无'}</div>
                                <div class="movie-meta">导演：${movie.director || '暂无'}</div>
                                <div class="movie-meta">评分：${movie.rating || '暂无评分'}</div>
                                <div class="movie-meta"><a href="../info.php?playUrl=${encodeURIComponent(movie.playUrl || '')}">查看详情页</a></div>
                            </div>
                        </div>
                    `;
                });

                $('#resultsContainer').html(html);
            }

            window.clearResults = function () {
                $('#resultsContainer').empty();
                $('#jsonOutput').hide();
                $('#apiUrl').empty();
            };

            performSearch();
        });
    </script>
</body>
</html>
