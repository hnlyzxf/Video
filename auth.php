<?php
// 统一的访问口令验证
const ACCESS_PASSWORD = '672149402';

// 启动会话
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 受保护页面验证
function requireAccess(string $pageTitle = '访问验证'): void
{
    if (!empty($_SESSION['protected_access_granted'])) {
        return;
    }

    $errorMessage = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = isset($_POST['access_password']) ? trim((string) $_POST['access_password']) : '';
        if ($password === ACCESS_PASSWORD) {
            $_SESSION['protected_access_granted'] = true;
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
        $errorMessage = '访问密码不正确';
    }

    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($pageTitle); ?> - 访问验证</title>
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f5f4ed;
                color: #141413;
                font-family: "Inter", "PingFang SC", "Microsoft YaHei", sans-serif;
            }
            .auth-card {
                width: min(420px, calc(100% - 32px));
                padding: 32px;
                border-radius: 24px;
                background: #faf9f5;
                box-shadow: 0 12px 40px rgba(20, 20, 19, 0.08);
                border: 1px solid #f0eee6;
            }
            h1 {
                margin: 0 0 12px;
                font-family: Georgia, "Times New Roman", serif;
                font-size: 2rem;
                font-weight: 500;
            }
            p {
                margin: 0 0 18px;
                color: #5e5d59;
                line-height: 1.7;
            }
            input {
                width: 100%;
                height: 48px;
                padding: 0 14px;
                box-sizing: border-box;
                border: 1px solid #e8e6dc;
                border-radius: 14px;
                background: #fff;
                font-size: 1rem;
            }
            button {
                margin-top: 16px;
                width: 100%;
                height: 48px;
                border: 0;
                border-radius: 14px;
                background: #c96442;
                color: #faf9f5;
                font-size: 1rem;
                cursor: pointer;
            }
            .error {
                margin-top: 12px;
                color: #b53333;
            }
        </style>
    </head>
    <body>
        <form class="auth-card" method="post">
            <h1>访问验证</h1>
            <p>此页面需要输入访问密码后才能进入。</p>
            <input type="password" name="access_password" placeholder="请输入访问密码" autocomplete="current-password" autofocus>
            <button type="submit">进入页面</button>
            <?php if ($errorMessage !== ''): ?>
            <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>
        </form>
    </body>
    </html>
    <?php
    exit;
}
