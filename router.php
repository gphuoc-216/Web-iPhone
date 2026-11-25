<?php
// router.php — Bộ định tuyến cho PHP Built-in Server (tương đương .htaccess / web.config)
$duongDanUri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$thuMucGoc   = __DIR__;
$tepThucTe   = $thuMucGoc . str_replace('/', DIRECTORY_SEPARATOR, $duongDanUri);

// 1. Phục vụ trực tiếp các file tĩnh tồn tại (CSS, JS, ảnh, HTML...)
if ($duongDanUri !== '/' && is_file($tepThucTe)) {
    return false;
}

// 2. Fallback ảnh cục bộ (/storage/, /shop-assets/, /img/, /uploads/)
if (preg_match('#^/(storage|shop-assets|img|uploads)/(.*)$#', $duongDanUri)) {
    $_GET['path'] = ltrim($duongDanUri, '/');
    require $thuMucGoc . '/api/storage.php';
    return true;
}

// 3. Định tuyến /api/... vào bộ định tuyến BCE api/index.php
if (preg_match('#^/api/(.*)$#', $duongDanUri, $khop)) {
    $_GET['duongDan'] = $khop[1];
    require $thuMucGoc . '/api/index.php';
    return true;
}

// 4. Định tuyến /admin về cổng quản trị BCE admin/index.html
if (preg_match('#^/admin(/.*)?$#', $duongDanUri)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($thuMucGoc . '/admin/index.html');
    return true;
}

// 5. Trang chủ khách hàng index.html
header('Content-Type: text/html; charset=utf-8');
readfile($thuMucGoc . '/index.html');
return true;
