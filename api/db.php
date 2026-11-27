<?php
// ============================================================================
// api/db.php
// Kết nối PDO MySQL cục bộ (Localhost - XAMPP / Laragon / IIS)
// Tuân thủ quy ước đặt tên tiếng Việt không dấu (camelCase / PascalCase)
// ============================================================================

$mayChuCsdl  = getenv('DB_HOST') ?: '127.0.0.1';
$congCsdl    = getenv('DB_PORT') ?: '3306';
$tenCsdl     = getenv('DB_NAME') ?: 'quan_ly_ban_dien_thoai';
$taiKhoanDb  = getenv('DB_USER') ?: 'root';
$matKhauEnv  = getenv('DB_PASS');
$matKhauDb   = ($matKhauEnv !== false) ? $matKhauEnv : ''; // Khi chạy Docker sẽ tự lấy DB_PASS; chạy local XAMPP/Laragon mặc định là '' (đổi nếu MySQL có password)

$ketNoiPdo   = null;
$loiCsdl     = null;
$daThuKetNoi = false;

@ini_set('default_socket_timeout', '5');
@ini_set('mysqlnd.net_read_timeout', '5');

/**
 * Trả về đối tượng kết nối PDO tới MySQL cục bộ.
 * Nếu lỗi kết nối và $tuDongBaoLoi = true, lập tức phản hồi JSON chuẩn HTTP 500.
 */
function layKetNoiCsdl(bool $tuDongBaoLoi = false): ?PDO
{
    global $ketNoiPdo, $loiCsdl, $daThuKetNoi;
    global $mayChuCsdl, $congCsdl, $tenCsdl, $taiKhoanDb, $matKhauDb;

    if (!$daThuKetNoi) {
        $daThuKetNoi = true;
        try {
            $chuoiKetNoi = "mysql:host={$mayChuCsdl};port={$congCsdl};dbname={$tenCsdl};charset=utf8mb4";
            $ketNoiPdo = new PDO($chuoiKetNoi, $taiKhoanDb, $matKhauDb, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 3,
            ]);
        } catch (Throwable $ngoaiLe) {
            $ketNoiPdo = null;
            $loiCsdl   = 'Không thể kết nối CSDL';
            error_log('Ket noi CSDL that bai: ' . $ngoaiLe->getMessage());
        }
    }

    if ($ketNoiPdo === null && $tuDongBaoLoi) {
        phanHoiJson(false, 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau', null, 500);
    }

    return $ketNoiPdo;
}

/**
 * Lấy thông tin trạng thái kết nối CSDL (dùng cho endpoint kiểm tra hệ thống).
 */
function layTrangThaiCsdl(): array
{
    global $mayChuCsdl, $congCsdl, $tenCsdl, $loiCsdl;
    $pdo = layKetNoiCsdl(false);

    if ($pdo !== null) {
        return [
            'daKetNoi' => true,
            'cheDo'    => 'MySQL Localhost',
            'mayChu'   => "{$mayChuCsdl}:{$congCsdl}",
            'tenCsdl'  => $tenCsdl,
            'thongBao' => "Đã kết nối thành công cơ sở dữ liệu {$tenCsdl} trên {$mayChuCsdl}:{$congCsdl}",
        ];
    }

    return [
        'daKetNoi' => false,
        'cheDo'    => 'Mất kết nối MySQL',
        'mayChu'   => "{$mayChuCsdl}:{$congCsdl}",
        'tenCsdl'  => $tenCsdl,
        'thongBao' => 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau',
        'chiTiet'  => $loiCsdl,
    ];
}

/**
 * Phản hồi JSON chuẩn với toàn bộ key tiếng Việt không dấu (camelCase).
 */
function phanHoiJson(bool $thanhCong, string $thongBao, $duLieu = null, int $maHttp = 200): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($maHttp);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Ma-Phien');

    $ketQua = [
        'thanhCong' => $thanhCong,
        'trangThai' => $thanhCong ? 'thanhCong' : 'loi',
        'thongBao'  => $thongBao,
        'duLieu'    => $duLieu,
    ];

    echo json_encode($ketQua, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Đọc dữ liệu đầu vào JSON hoặc form-data từ request HTTP.
 */
function layDuLieuDauVao(): array
{
    $loaiNoiDung = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($loaiNoiDung, 'application/json') !== false) {
        $noiDungTho = file_get_contents('php://input');
        $giaiMa = json_decode($noiDungTho ?: '{}', true);
        return is_array($giaiMa) ? $giaiMa : [];
    }
    if (!empty($_POST)) {
        return $_POST;
    }
    $noiDungTho = file_get_contents('php://input');
    $giaiMa = json_decode($noiDungTho ?: '{}', true);
    return is_array($giaiMa) ? $giaiMa : [];
}

/**
 * Chuẩn hóa đường dẫn ảnh sản phẩm về lưu trữ cục bộ (tương thích dữ liệu cũ MinIO / /storage/).
 */
function chuanHoaDuongDanAnh(?string $duongDan): string
{
    $chuoi = trim((string)$duongDan);
    if ($chuoi === '') {
        return '/storage/shop-assets/img/iphone17.webp';
    }
    if (preg_match('#^https?://#i', $chuoi)) {
        $phanTich = parse_url($chuoi);
        $tenMien  = strtolower($phanTich['host'] ?? '');
        $cong     = (int)($phanTich['port'] ?? 0);
        if (strpos($tenMien, '100.') === 0 || $tenMien === 'shop-ecommerce.local' || $cong === 9000) {
            $chuoi = ltrim($phanTich['path'] ?? '', '/');
        } else {
            return $chuoi;
        }
    }
    $sach = ltrim(str_replace('\\', '/', $chuoi), '/');
    if (strpos($sach, 'uploads/') === 0 || strpos($sach, 'storage/shop-assets/') === 0) {
        return '/' . $sach;
    }
    if (strpos($sach, 'storage/') === 0) {
        $phanCon = preg_replace('#^shop-assets/#', '', substr($sach, 8));
        return '/storage/shop-assets/' . $phanCon;
    }
    if (strpos($sach, 'shop-assets/') === 0) {
        return '/storage/' . $sach;
    }
    if (strpos($sach, 'img/') === 0) {
        return '/storage/shop-assets/' . $sach;
    }
    return '/storage/shop-assets/img/' . $sach;
}

/**
 * Các hàm hỗ trợ quản lý Transaction CSDL để tầng Controller không gọi trực tiếp hàm SQL/PDO.
 */
function batDauGiaoDich(PDO $pdo): void
{
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }
}

function xacNhanGiaoDich(PDO $pdo): void
{
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
}

function hoanTacGiaoDich(PDO $pdo): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
