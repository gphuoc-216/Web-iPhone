<?php
// Phục vụ file ảnh trực tiếp từ thư mục cục bộ trên máy chủ Localhost
// Thay thế hoàn toàn MinIO Object Storage, tự động ánh xạ các đường dẫn cũ (/storage/, /shop-assets/, /img/, /uploads/)

$path = $_GET['path'] ?? '';
if (!$path) {
    http_response_code(404);
    echo "Missing path";
    exit;
}

// Chuẩn hóa đường dẫn và loại bỏ tiền tố URL cũ (nếu có)
$cleanPath = preg_replace('#^https?://[^/]+/#i', '', trim($path));
$cleanPath = ltrim(str_replace('\\', '/', $cleanPath), '/');

// Chống tấn công Path Traversal (../)
if (strpos($cleanPath, '..') !== false) {
    http_response_code(400);
    echo "Invalid path";
    exit;
}

$strippedStorage = preg_replace('#^storage/#', '', $cleanPath);
$strippedBucket  = preg_replace('#^shop-assets/#', '', $strippedStorage);

$rootDir = realpath(dirname(__DIR__));
$candidates = [
    $rootDir . '/' . $cleanPath,
    $rootDir . '/uploads/' . preg_replace('#^uploads/#', '', $strippedBucket),
    $rootDir . '/storage/' . $strippedStorage,
    $rootDir . '/storage/shop-assets/' . $strippedBucket,
    $rootDir . '/storage/shop-assets/img/' . basename($strippedBucket),
    $rootDir . '/img/' . preg_replace('#^img/#', '', $strippedBucket),
];

$mimeMap = [
    'webp' => 'image/webp',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'css'  => 'text/css; charset=utf-8',
    'mp4'  => 'video/mp4',
];

foreach ($candidates as $file) {
    $realFile = realpath($file);
    if ($realFile !== false && strpos($realFile, $rootDir) === 0 && is_file($realFile)) {
        $ext  = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';
        header("Content-Type: $mime");
        header("Cache-Control: public, max-age=86400");
        readfile($realFile);
        exit;
    }
}

http_response_code(404);
echo "File not found in local storage";
exit;
