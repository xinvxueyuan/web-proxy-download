<?php
require_once __DIR__ . '/functions.php';

function handleRequest() {
    $whiteList = file(__DIR__ . '/whitelist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $error = '';
    $limitConfigFile = __DIR__ . '/limit_config.txt';
    $limit = getLimitConfig($limitConfigFile);
    $period = 60;
    $now = time();
    $cookieName = 'download_limit';
    $limitData = isset($_COOKIE[$cookieName]) ? json_decode($_COOKIE[$cookieName], true) : null;
    if (!$limitData || !isset($limitData['time']) || !isset($limitData['count']) || $now - $limitData['time'] > $period) {
        $limitData = ['time' => $now, 'count' => 0];
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!checkAndUpdateLimit($limitData, $limit, $period, $now, $cookieName)) {
            $error = '请求过于频繁，请稍后再试。';
        } else {
            $url = trim($_POST['url'] ?? '');
            if (empty($url)) {
                $error = '请输入下载链接。';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $error = '链接格式不正确。';
            } elseif (!isUrlInWhitelist($url, $whiteList) && basename(parse_url($url, PHP_URL_PATH)) !== 'whitelist.txt') {
                $error = '该链接不在白名单内，无法下载。';
            } else {
                if (handleDownload($url) === false) {
                    $error = '文件下载失败，请检查链接。';
                }
            }
        }
        setcookie($cookieName, json_encode($limitData), $now + $period, '/');
    }
    return [
        'whitelist' => $whiteList,
        'error' => $error,
        'limit' => $limit,
        'period' => $period,
        'now' => $now,
        'cookie_name' => $cookieName,
        'limit_data' => $limitData
    ];
}