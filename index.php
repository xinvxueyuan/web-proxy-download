<?php

class ProxyDownloader {
    private $targetUrl;
    private $downloadPath;
    
    public function __construct($url) {
        $this->parseUrl($url);
    }
    
    private function parseUrl($url) {
        // 移除开头的域名部分，获取目标URL
        $parts = explode('/', $_SERVER['REQUEST_URI'], 4);
        if (count($parts) >= 4) {
            $this->targetUrl = 'https://' . $parts[3];
        } else {
            throw new Exception('Invalid URL format');
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
    $downloader = new ProxyDownloader($_SERVER['REQUEST_URI']);
    $downloader->download();
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Error: ' . $e->getMessage();
}