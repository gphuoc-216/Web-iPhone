<?php
// ============================================================================
// api/upload.php
// API tải ảnh sản phẩm lưu trữ cục bộ (yêu cầu phân quyền Ban Quản lý, chống XSS)
// ============================================================================

if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Ma-Phien');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/autoload.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    phanHoiJson(false, 'Phương thức HTTP không được hỗ trợ', null, 405);
}

$pdo = layKetNoiCsdl(true);

$maPhien = trim((string)($_SERVER['HTTP_X_MA_PHIEN'] ?? ''));
if ($maPhien === '') {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (stripos($auth, 'Bearer ') === 0) {
        $maPhien = trim(substr($auth, 7));
    }
}
if ($maPhien === '') {
    $maPhien = trim((string)($_POST['maPhien'] ?? $_GET['maPhien'] ?? ''));
}

$phienHienTai = (new XacThucCtrl($pdo))->kiemTraPhien($maPhien);
if ($phienHienTai === null) {
    phanHoiJson(false, 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại', null, 401);
}
if ($phienHienTai['vaiTro'] !== 'Ban Quản lý') {
    phanHoiJson(false, 'Chỉ Ban Quản lý mới có quyền tải ảnh sản phẩm lên hệ thống', null, 403);
}

$tepAnh = $_FILES['hinhAnh'] ?? $_FILES['anh'] ?? $_FILES['tepAnh'] ?? $_FILES['image'] ?? $_FILES['file'] ?? null;
if (!$tepAnh || ($tepAnh['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    phanHoiJson(false, 'Không tìm thấy tệp ảnh hợp lệ để tải lên', null, 400);
}

// Tuyệt đối không cho phép .svg để chống Stored XSS
$duoiHopLe = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$duoiTep = strtolower(pathinfo($tepAnh['name'], PATHINFO_EXTENSION));
if (!in_array($duoiTep, $duoiHopLe, true)) {
    phanHoiJson(false, 'Định dạng ảnh không được hỗ trợ (chỉ nhận jpg, jpeg, png, webp, gif)', null, 400);
}

$kichThuocToiDa = 10 * 1024 * 1024; // 10MB
if (($tepAnh['size'] ?? 0) > $kichThuocToiDa) {
    phanHoiJson(false, 'Kích thước tệp ảnh vượt quá giới hạn 10MB', null, 400);
}

if (@getimagesize($tepAnh['tmp_name']) === false) {
    phanHoiJson(false, 'Tệp tải lên không phải là hình ảnh hợp lệ', null, 400);
}

$thuMucGoc = dirname(__DIR__);
$thuMucUpload = $thuMucGoc . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($thuMucUpload)) {
    @mkdir($thuMucUpload, 0775, true);
}

$tenCoSo = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($tepAnh['name'], PATHINFO_FILENAME));
$tenCoSo = trim($tenCoSo, '_') ?: 'dienthoai';
$tenTep = $tenCoSo . '_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $duoiTep;
$duongDanLuu = $thuMucUpload . DIRECTORY_SEPARATOR . $tenTep;

if (!move_uploaded_file($tepAnh['tmp_name'], $duongDanLuu)) {
    phanHoiJson(false, 'Không thể lưu tệp ảnh vào thư mục uploads/', null, 500);
}

$duongDanTuongDoi = 'uploads/' . $tenTep;
phanHoiJson(true, 'Tải ảnh sản phẩm lên thành công', [
    'duongDan' => $duongDanTuongDoi,
    'lienKet'  => '/' . $duongDanTuongDoi,
]);
