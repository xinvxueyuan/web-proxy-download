<?php
require_once __DIR__ . '/functions.php';

function handle_request() {
    $whitelist = file(__DIR__ . '/whitelist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $error = '';
    $limit_config_file = __DIR__ . '/limit_config.txt';
    $limit = get_limit_config($limit_config_file);
    $period = 60;
    $now = time();
    $cookie_name = 'download_limit';
    $limit_data = isset($_COOKIE[$cookie_name]) ? json_decode($_COOKIE[$cookie_name], true) : null;
    if (!$limit_data || !isset($limit_data['time']) || !isset($limit_data['count']) || $now - $limit_data['time'] > $period) {
        $limit_data = ['time' => $now, 'count' => 0];
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!check_and_update_limit($limit_data, $limit, $period, $now, $cookie_name)) {
            $error = '请求过于频繁，请稍后再试。';
        } else {
            $url = trim($_POST['url'] ?? '');
            if (empty($url)) {
                $error = '请输入下载链接。';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $error = '链接格式不正确。';
            } elseif (!is_url_in_whitelist($url, $whitelist) && basename(parse_url($url, PHP_URL_PATH)) !== 'whitelist.txt') {
                $error = '该链接不在白名单内，无法下载。';
            } else {
                if (handle_download($url) === false) {
                    $error = '文件下载失败，请检查链接。';
                }
            }
        }
        setcookie($cookie_name, json_encode($limit_data), $now + $period, '/');
    }
    return [
        'whitelist' => $whitelist,
        'error' => $error,
        'limit' => $limit,
        'period' => $period,
        'now' => $now,
        'cookie_name' => $cookie_name,
        'limit_data' => $limit_data
    ];
}