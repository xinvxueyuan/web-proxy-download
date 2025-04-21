<?php
require_once __DIR__ . '/vendor/autoload.php';

use Admin\WhitelistManager;

class ProxyDownloader {
    private $targetUrl;
    private $downloadPath;
    
    public function __construct() {
        $this->parseUrl($_SERVER['REQUEST_URI']);
        $this->validateUrl();
    }

    private function parseUrl($requestUri) {
        // Get the script's directory path relative to the document root
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        // Ensure scriptDir ends with a slash if it's not the root
        if ($scriptDir !== '/' && $scriptDir !== '\\') { // Handle both / and \ separators
            $scriptDir .= '/';
        }
        // Normalize separators in requestUri for comparison
        $normalizedRequestUri = str_replace('\\', '/', $requestUri);
        $normalizedScriptDir = str_replace('\\', '/', $scriptDir);

        // Check if the request URI starts with the script directory
        if (strpos($normalizedRequestUri, $normalizedScriptDir) === 0) {
            // Remove the script directory path from the request URI
            $targetPath = substr($normalizedRequestUri, strlen($normalizedScriptDir));
            // Remove query string if present
            $targetPath = strtok($targetPath, '?');
            // Basic validation: ensure target path is not empty
            if (!empty($targetPath)) {
                $this->targetUrl = 'https://' . $targetPath;
            } else {
                throw new Exception('无效的目标URL路径');
            }
        } else {
            // This case should ideally not happen if routing is correct,
            // but handle it defensively.
            throw new Exception('无法解析请求URI以确定目标URL');
        }
    }

    private function validateUrl() {
        if (!WhitelistManager::getInstance()->isUrlAllowed($this->targetUrl)) {
            throw new Exception('该URL不在白名单中，禁止下载');
        }
    }
    
    public function download() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->targetUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        // 获取原始请求的headers并转发
        $headers = getallheaders();
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            if (!in_array(strtolower($key), ['host', 'connection'])) {
                $curlHeaders[] = "$key: $value";
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        
        // 执行请求
        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('Download failed: ' . curl_error($ch));
        }
        
        // 分离header和body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseHeaders = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        // 获取文件名
        $filename = basename($this->targetUrl);
        if (empty($filename)) {
            $filename = 'download';
        }
        
        // 设置响应头
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($body));
        
        // 输出文件内容
        echo $body;
        
        curl_close($ch);
    }
}

try {
    $downloader = new ProxyDownloader();
    $downloader->download();
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Error: ' . $e->getMessage();
}