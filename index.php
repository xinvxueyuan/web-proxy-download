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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['url'] ?? '');
    if (empty($url)) {
        $error = '请输入下载链接。';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error = '链接格式不正确。';
    } elseif (!is_url_in_whitelist($url, $whitelist)) {
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
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>白名单代理下载</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { max-width: 400px; margin: 80px auto; background: #fff; padding: 32px 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { text-align: center; }
        input[type=url] { width: 100%; padding: 8px; margin: 12px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #0078d7; color: #fff; border: none; border-radius: 4px; font-size: 16px; }
        .error { color: #d8000c; background: #ffd2d2; padding: 8px; border-radius: 4px; margin-bottom: 12px; }
        .tip { color: #555; font-size: 13px; margin-top: 16px; }
    </style>
</head>
<body>
<div class="container">
    <h2>白名单代理下载</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="url">下载链接：</label>
        <input type="url" id="url" name="url" placeholder="请输入下载链接" required>
        <button type="submit">下载</button>
    </form>
    <div class="tip">仅支持以下白名单域名：<br><?php echo implode(', ', $whitelist); ?></div>
</div>
</body>
</html>