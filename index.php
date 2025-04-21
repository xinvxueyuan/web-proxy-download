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
        $normalizedRequestUri = str_replace('\\', '/', $requestUri); // Line 23 - Using single quotes
        $normalizedScriptDir = str_replace('\\', '/', $scriptDir);  // Line 24 - Using single quotes

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
            // Filter out headers that might cause issues or are handled by cURL
            $lowerKey = strtolower($key);
            if (!in_array($lowerKey, ['host', 'connection', 'content-length'])) { // Added content-length filter
                $curlHeaders[] = "$key: $value";
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
        $curlError = curl_error($ch); // Get cURL error message
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch); // Close cURL handle early

        if ($response === false) {
            throw new Exception("Download failed: cURL Error ($httpCode): $curlError");
        }

        // Separate header and body
        $responseHeaders = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Forward response headers from target server
        $headerLines = explode("\r\n", trim($responseHeaders));
        foreach ($headerLines as $headerLine) {
            // Don't forward transfer-encoding or content-length as we recalculate it
            if (stripos($headerLine, 'Transfer-Encoding:') === false && stripos($headerLine, 'Content-Length:') === false) {
                 // Also skip the HTTP status line
                 if (!preg_match('/^HTTP\/\d\.\d/', $headerLine)) {
                    header($headerLine, false); // Use false to allow multiple headers of the same type
                 }
            }
        }

        // Try to extract filename from Content-Disposition or URL
        $filename = 'download'; // Default filename
        foreach ($headerLines as $headerLine) {
            if (stripos($headerLine, 'Content-Disposition:') === 0) {
                if (preg_match('/filename="?([^"]+)"?/', $headerLine, $matches)) {
                    $filename = basename($matches[1]); // Use basename to prevent path traversal
                    break;
                }
            }
        }
        if ($filename === 'download') { // If not found in headers, use URL basename
             $pathInfo = pathinfo(parse_url($this->targetUrl, PHP_URL_PATH));
             if (isset($pathInfo['basename']) && !empty($pathInfo['basename'])) {
                 $filename = $pathInfo['basename'];
             }
        }


        // Set necessary headers for download
        header('Content-Description: File Transfer'); // Added description
        header('Content-Type: application/octet-stream'); // Force download
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0'); // Prevent caching
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($body)); // Set correct content length

        // Output file content
        echo $body;
        exit; // Ensure script termination after sending file
    }
}

try {
    // Basic routing: Ignore requests for favicon.ico
    if ($_SERVER['REQUEST_URI'] === '/favicon.ico') {
        header("HTTP/1.1 404 Not Found");
        exit;
    }

    // Only proceed if it's not the root path (/) which should show an info page or similar
    if ($_SERVER['REQUEST_URI'] !== '/') {
        $downloader = new ProxyDownloader();
        $downloader->download();
    } else {
        // Handle root path request - maybe show a simple info page or redirect to admin?
        // For now, just show a simple message.
        echo "Web Proxy Download Service. Use /<target_url> to download.";
        // Or redirect to admin login:
        // header('Location: /admin/login.php');
        // exit;
    }

} catch (Exception $e) {
    // Log the error instead of just echoing?
    error_log("Proxy Error: " . $e->getMessage()); // Log to PHP error log

    // Send a user-friendly error response
    if (!headers_sent()) { // Check if headers already sent
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/plain'); // Set content type for error message
    }
    echo 'Error: ' . htmlspecialchars($e->getMessage()); // Escape output
}