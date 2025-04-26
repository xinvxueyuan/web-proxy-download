<?php
// 白名单校验函数
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

// 限流处理函数
function get_limit_config($limit_config_file) {
    $limit = 5;
    $limits = [
        'per_minute' => 5
    ];
    if (file_exists($limit_config_file)) {
        $lines = file($limit_config_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (is_numeric($value) && (int)$value > 0) {
                    $limits[$key] = (int)$value;
                }
            }
        }
        if (isset($limits['per_minute'])) {
            $limit = $limits['per_minute'];
        }
    }
    return $limit;
}

function check_and_update_limit(&$limit_data, $limit, $period, $now, $cookie_name) {
    if ($limit_data['count'] >= $limit && $now - $limit_data['time'] < $period) {
        return false;
    } else {
        if ($now - $limit_data['time'] >= $period) {
            $limit_data = ['time' => $now, 'count' => 1];
        } else {
            $limit_data['count']++;
        }
        setcookie($cookie_name, json_encode($limit_data), $now + $period, '/');
        return true;
    }
}

// 下载处理函数
function handle_download($url) {
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
        exit;
    } else {
        return false;
    }
}