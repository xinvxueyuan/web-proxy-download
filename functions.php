<?php
// 白名单校验函数
function isUrlInWhitelist($url, $whiteList) {
    $parsed = parse_url($url);
    if (!isset($parsed['host'])) return false;
    $host = $parsed['host'];
    foreach ($whiteList as $allowed) {
        if (stripos($host, $allowed) !== false) {
            return true;
        }
    }
    return false;
}

// 限流处理函数
function getLimitConfig($limitConfigFile) {
    $limit = 5;
    $limits = [
        'per_minute' => 5
    ];
    if (file_exists($limitConfigFile)) {
        $lines = file($limitConfigFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

function checkAndUpdateLimit(&$limitData, $limit, $period, $now, $cookieName) {
    if ($limitData['count'] >= $limit && $now - $limitData['time'] < $period) {
        return false;
    } else {
        if ($now - $limitData['time'] >= $period) {
            $limitData = ['time' => $now, 'count' => 1];
        } else {
            $limitData['count']++;
        }
        setcookie($cookieName, json_encode($limitData), $now + $period, '/');
        return true;
    }
}

// 下载处理函数
function handleDownload($url) {
    $fileName = basename(parse_url($url, PHP_URL_PATH));
    if (!$fileName) $fileName = 'downloaded_file';
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
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