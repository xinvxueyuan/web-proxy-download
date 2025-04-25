<?php
// 白名单域名列表
$whitelist = file(__DIR__ . '/whitelist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

function is_url_in_whitelist($url, $whitelist) {
    $parsed = parse_url($url);
    if (!isset($parsed['host'])) return false;
    $host = $parsed['host'];
    foreach ($whitelist as $allowed) {
        if (stripos($host, $allowed) !== false) {
            return true;
        }
    }
    return false;
}

$error = '';
// 限流逻辑：每分钟最多5次
// 限流逻辑：每分钟请求上限由配置文件决定
$limit_config_file = __DIR__ . '/limit_config.txt';
$limit = 5;
if (file_exists($limit_config_file)) {
    $limit_content = trim(file_get_contents($limit_config_file));
    if (is_numeric($limit_content) && (int)$limit_content > 0) {
        $limit = (int)$limit_content;
    }
}
$period = 60; // 秒
$now = time();
$cookie_name = 'download_limit';
$limit_data = isset($_COOKIE[$cookie_name]) ? json_decode($_COOKIE[$cookie_name], true) : null;
if (!$limit_data || !isset($limit_data['time']) || !isset($limit_data['count']) || $now - $limit_data['time'] > $period) {
    $limit_data = ['time' => $now, 'count' => 0];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($limit_data['count'] >= $limit && $now - $limit_data['time'] < $period) {
        $error = '请求过于频繁，请稍后再试。';
    } else {
        if ($now - $limit_data['time'] >= $period) {
            $limit_data = ['time' => $now, 'count' => 1];
        } else {
            $limit_data['count']++;
        }
        setcookie($cookie_name, json_encode($limit_data), $now + $period, '/');
        if (empty($error)) {
            $url = trim($_POST['url'] ?? '');
            if (empty($url)) {
                $error = '请输入下载链接。';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $error = '链接格式不正确。';
            } elseif (!is_url_in_whitelist($url, $whitelist) && basename(parse_url($url, PHP_URL_PATH)) !== 'whitelist.txt') {
                $error = '该链接不在白名单内，无法下载。';
            } else {
                // 代理下载
                $filename = basename(parse_url($url, PHP_URL_PATH));
                if (!$filename) $filename = 'downloaded_file';
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                $fp = fopen($url, 'rb');
                if ($fp) {
                    while (!feof($fp)) {
                        echo fread($fp, 8192);
                        flush();
                    }
                    fclose($fp);
                } else {
                    $error = '文件下载失败，请检查链接。';
                }
                exit;
            }
        }
    }
    setcookie($cookie_name, json_encode($limit_data), $now + $period, '/');
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>简单代理下载</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
        }
        .center-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .main-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 2rem;
        }
        .download-form {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body class="bg-light">
<div class="center-container">
    <div class="main-title text-primary text-center">简单代理下载</div>
    <form method="post" class="download-form">
        <div class="mb-3">
            <input type="url" class="form-control" id="url" name="url" placeholder="请输入文件下载链接" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100">下载</button>
    </form>
    <?php if ($error): ?>
        <div class="alert alert-danger mt-3 w-100 text-center" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div class="alert alert-secondary mt-4 p-2 small text-center w-100" role="alert">
        仅支持以下白名单域名：<br><?php echo implode(', ', $whitelist); ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>