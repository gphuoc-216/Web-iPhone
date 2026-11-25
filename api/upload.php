<?php
// API upload ảnh sản phẩm lưu trữ cục bộ (thay thế MinIO Object Storage)
if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['image'] ?? $_FILES['img'] ?? $_FILES['file'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy file ảnh hợp lệ để tải lên'], JSON_UNESCAPED_UNICODE);
    exit;
}

$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Định dạng ảnh không được hỗ trợ (chỉ nhận jpg, jpeg, png, webp, gif, svg)'], JSON_UNESCAPED_UNICODE);
    exit;
}

$maxSize = 10 * 1024 * 1024; // 10MB
if (($file['size'] ?? 0) > $maxSize) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Kích thước file vượt quá giới hạn 10MB'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rootDir   = dirname(__DIR__);
$uploadDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

$baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
$baseName = trim($baseName, '_') ?: 'product';
$fileName = $baseName . '_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
$destPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Không thể lưu file ảnh vào thư mục uploads/'], JSON_UNESCAPED_UNICODE);
    exit;
}

$relativePath = 'uploads/' . $fileName;

echo json_encode([
    'status'  => 'success',
    'message' => 'Tải ảnh lên cục bộ thành công',
    'path'    => $relativePath,
    'url'     => '/' . $relativePath
], JSON_UNESCAPED_UNICODE);
exit;
