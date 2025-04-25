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
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>白名单代理下载</title>
    <!-- 引入Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width: 430px; margin-top: 80px;">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">白名单代理下载</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="url" class="form-label">下载链接：</label>
                    <input type="url" class="form-control" id="url" name="url" placeholder="请输入下载链接" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">下载</button>
            </form>
            <div class="alert alert-secondary mt-4 p-2 small" role="alert">
                仅支持以下白名单域名：<br><?php echo implode(', ', $whitelist); ?>
            </div>
        </div>
    </div>
</div>
<!-- 引入Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>