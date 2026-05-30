<?php
require_once __DIR__ . '/../auth.php';
requireAccess('列表接口验证');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Validation page for the list endpoint.">
    <title>列表接口验证 - 木子白白白影视</title>
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
                <div class="hero-eyebrow">列表接口验证</div>
                <h1 class="hero-title">验证片单与分类返回结构</h1>
                <p class="hero-subtitle">支持读取默认片单、切换分类地址，并展示结果卡片与原始 JSON。</p>
            </div>
        </section>

        <section class="site-section">
            <div class="u-container-full">
                <div class="search-form">
                    <h2>验证参数</h2>
                    <form id="listForm">
                        <div class="form-group">
                            <label for="categorySelect">分类地址</label>
                            <select id="categorySelect">
                                <option value="">选择分类</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="urlInput">自定义地址</label>
                            <input type="text" id="urlInput" placeholder="Custom list URL">
                        </div>
                        <button type="button" class="btn" onclick="loadData()">加载数据</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleJson()">查看 JSON</button>
                    </form>
                </div>

                <div id="status" class="status"></div>
                <div id="stats" class="stats" style="display: none;"></div>
                <div id="movieGrid" class="movie-grid"></div>
                <div id="jsonOutput" class="json-output" style="display: none;"></div>
            </div>
        </section>
    </main>

    <script>
        // 缓存当前响应数据，方便查看 JSON
        let movieData = null;
        let isJsonVisible = false;

        async function checkServerStatus() {
            try {
                const controller = new AbortController();
                setTimeout(() => controller.abort(), 3000);
                const response = await fetch('../core/api.php?test', { signal: controller.signal });
                return response.ok;
            } catch (error) {
                return false;
            }
        }

        async function loadData() {
            const statusDiv = document.getElementById('status');
            const movieGrid = document.getElementById('movieGrid');
            const statsDiv = document.getElementById('stats');
            const urlInput = document.getElementById('urlInput');
            const categorySelect = document.getElementById('categorySelect');

            statusDiv.innerHTML = '<div class="loading">正在加载片单数据...</div>';
            movieGrid.innerHTML = '';
            statsDiv.style.display = 'none';

            try {
                let apiUrl = '../core/api.php';

                // 优先使用下拉分类，其次使用手动地址
                if (categorySelect.value) {
                    apiUrl += '?url=' + encodeURIComponent(categorySelect.value);
                } else if (urlInput.value.trim()) {
                    apiUrl += '?url=' + encodeURIComponent(urlInput.value.trim());
                }

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 20000);
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal
                });
                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.error || '接口返回失败');
                }

                movieData = data;
                statusDiv.innerHTML = `<div class="success">已加载 ${data.count || 0} 条影片数据。</div>`;

                if (data.categories) {
                    populateCategories(data.categories);
                }

                displayMovies(data.data || []);
                showStats(data);
            } catch (error) {
                statusDiv.innerHTML = `<div class="error">请求失败：${error.message}</div>`;
            }
        }

        function populateCategories(categories) {
            const categorySelect = document.getElementById('categorySelect');
            categorySelect.innerHTML = '<option value="">选择分类</option>';

            const order = [
                { key: 'main', label: '主分类' },
                { key: 'sort', label: '排序' },
                { key: 'type', label: '类型' },
                { key: 'region', label: '地区' }
            ];

            order.forEach(function (group) {
                if (Array.isArray(categories[group.key]) && categories[group.key].length > 0) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = group.label;

                    categories[group.key].forEach(function (item) {
                        const option = document.createElement('option');
                        option.value = item.url || '';
                        option.textContent = item.name || '未命名分类';
                        optgroup.appendChild(option);
                    });

                    categorySelect.appendChild(optgroup);
                }
            });
        }

        function displayMovies(movies) {
            const movieGrid = document.getElementById('movieGrid');
            movieGrid.innerHTML = '';

            movies.forEach(function (movie) {
                const card = document.createElement('div');
                card.className = 'movie-card';
                const cover = movie.cover ? (movie.cover.startsWith('//') ? 'https:' + movie.cover : movie.cover) : '';
                const poster = cover
                    ? `<div class="test-movie-poster"><img src="${cover}" alt="${movie.name || '影片'}"></div>`
                    : '<div class="test-movie-poster"><div class="plot-content">暂无图片</div></div>';

                card.innerHTML = `
                    ${poster}
                    <div class="content-section" style="margin:0; border:none; box-shadow:none; padding:18px;">
                        <h3 style="margin-top:0;">${movie.name || '未命名影片'}</h3>
                        <p class="movie-meta">分类：${movie.category || '暂无'}</p>
                        <p class="movie-meta">地区：${movie.region || '暂无'} / 年份：${movie.year || '暂无'}</p>
                        <p class="movie-meta">评分：${movie.rating || '暂无评分'}</p>
                        <p class="movie-meta"><a href="../info.php?playUrl=${encodeURIComponent(movie.playUrl || '')}">查看详情页</a></p>
                    </div>
                `;
                movieGrid.appendChild(card);
            });
        }

        function showStats(data) {
            const statsDiv = document.getElementById('stats');
            const categories = [...new Set((data.data || []).map(item => item.category).filter(Boolean))];
            const regions = [...new Set((data.data || []).map(item => item.region).filter(Boolean))];

            statsDiv.innerHTML = `
                <div class="stat-item">
                    <div class="stat-number">${data.count || 0}</div>
                    <div class="stat-label">结果总数</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${categories.length}</div>
                    <div class="stat-label">分类数</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${regions.length}</div>
                    <div class="stat-label">地区数</div>
                </div>
            `;
            statsDiv.style.display = 'grid';
        }

        function toggleJson() {
            const jsonOutput = document.getElementById('jsonOutput');
            if (!movieData) {
                alert('请先加载数据');
                return;
            }

            isJsonVisible = !isJsonVisible;
            jsonOutput.style.display = isJsonVisible ? 'block' : 'none';
            if (isJsonVisible) {
                jsonOutput.textContent = JSON.stringify(movieData, null, 2);
            }
        }

        window.onload = async function () {
            const statusDiv = document.getElementById('status');
            statusDiv.innerHTML = '<div class="loading">正在检查服务状态...</div>';

            const serverOk = await checkServerStatus();
            if (!serverOk) {
                statusDiv.innerHTML = '<div class="error">无法连接列表接口，请确认 PHP 服务已启动。</div>';
                return;
            }

            loadData();
        };
    </script>
</body>
</html>
