<?php
// ============================================================================
// api/index.php
// Bộ định tuyến (Router) BCE + Phân quyền RBAC chặt chẽ theo 3 Actor nội bộ & Khách hàng
// ============================================================================

if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Ma-Phien');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/autoload.php';

function layMaPhienTuYeuCau(array $duLieu = []): string
{
    $headerPhien = $_SERVER['HTTP_X_MA_PHIEN'] ?? '';
    if ($headerPhien !== '') {
        return trim($headerPhien);
    }
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (stripos($auth, 'Bearer ') === 0) {
        return trim(substr($auth, 7));
    }
    if (!empty($_GET['maPhien'])) {
        return trim((string)$_GET['maPhien']);
    }
    if (!empty($duLieu['maPhien'])) {
        return trim((string)$duLieu['maPhien']);
    }
    return '';
}

function yeuCauPhanQuyen(PDO $pdo, array $cacVaiTroChoPhep = [], array $duLieu = []): array
{
    $maPhien = layMaPhienTuYeuCau($duLieu);
    $xacThucCtrl = new XacThucCtrl($pdo);
    $phienHienTai = $xacThucCtrl->kiemTraPhien($maPhien);

    if ($phienHienTai === null) {
        phanHoiJson(false, 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại', null, 401);
    }

    if (!empty($cacVaiTroChoPhep) && !in_array($phienHienTai['vaiTro'], $cacVaiTroChoPhep, true)) {
        phanHoiJson(
            false,
            "Vai trò '{$phienHienTai['vaiTro']}' không có quyền thực hiện chức năng này",
            ['vaiTroHienTai' => $phienHienTai['vaiTro'], 'vaiTroYeuCau' => $cacVaiTroChoPhep],
            403
        );
    }

    return $phienHienTai;
}

function traKetQuaController(array $ketQua): void
{
    $maHttp = (int)($ketQua['maHttp'] ?? ($ketQua['thanhCong'] ? 200 : 400));
    phanHoiJson(
        (bool)($ketQua['thanhCong'] ?? false),
        (string)($ketQua['thongBao'] ?? ''),
        $ketQua['duLieu'] ?? null,
        $maHttp
    );
}

$phuongThuc = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$duLieu = layDuLieuDauVao();

$duongDan = trim((string)($_GET['duongDan'] ?? ''), '/');
$cacPhan = $duongDan !== '' ? explode('/', $duongDan) : [];
$taiNguyen = $_GET['taiNguyen'] ?? ($cacPhan[0] ?? 'he-thong');
$hanhDong = $_GET['hanhDong'] ?? ($cacPhan[1] ?? '');

if ($taiNguyen === 'he-thong') {
    phanHoiJson(true, 'Hệ thống API BCE đang hoạt động', [
        'mayChu'   => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP Localhost',
        'tenMien'  => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'csdl'     => layTrangThaiCsdl(),
        'thoiGian' => date('Y-m-d H:i:s'),
    ]);
}

$pdo = layKetNoiCsdl(true);

try {
    switch ($taiNguyen) {
        // 1. QuanLyDanhMucCtrl (UC1 - UC3) — Ban Quản lý
        case 'danh-muc':
            $ctrl = new QuanLyDanhMucCtrl($pdo);
            if ($phuongThuc === 'GET') {
                if (!empty($_GET['maDanhMuc'])) {
                    traKetQuaController($ctrl->layThongTinDanhMuc((int)$_GET['maDanhMuc']));
                }
                traKetQuaController($ctrl->layDanhSachDanhMuc());
            }
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->luuDanhMuc($duLieu));
            }
            if ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH') {
                traKetQuaController($ctrl->capNhatDanhMuc((int)($duLieu['maDanhMuc'] ?? $_GET['maDanhMuc'] ?? 0), $duLieu));
            }
            if ($phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->xoaDanhMuc((int)($_GET['maDanhMuc'] ?? $duLieu['maDanhMuc'] ?? 0)));
            }
            break;

        // 2. QuanLyDienThoaiCtrl (UC4 - UC6, UC10) — Bảo vệ dữ liệu nội bộ (giaVonBinhQuan, Ngừng kinh doanh)
        case 'dien-thoai':
            $ctrl = new QuanLyDienThoaiCtrl($pdo);
            if ($phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Ban Quản lý', 'Nhân viên Kho', 'Nhân viên Bán hàng / CSKH'], $duLieu);
                if ($hanhDong === 'ngung-kinh-doanh') {
                    traKetQuaController($ctrl->layDanhSachNgungKinhDoanh());
                }
                if (!empty($_GET['maDienThoai'])) {
                    traKetQuaController($ctrl->layThongTinDienThoai((int)$_GET['maDienThoai']));
                }
                traKetQuaController($ctrl->layDanhSachDienThoai($_GET['trangThai'] ?? null));
            }
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            if ($hanhDong === 'khoi-phuc' && ($phuongThuc === 'POST' || $phuongThuc === 'PATCH')) {
                traKetQuaController($ctrl->khoiPhucDienThoai((int)($duLieu['maDienThoai'] ?? 0)));
            }
            $tepAnh = $_FILES['hinhAnh'] ?? $_FILES['anh'] ?? null;
            if (($hanhDong === 'cap-nhat' && $phuongThuc === 'POST') || $phuongThuc === 'PUT') {
                traKetQuaController($ctrl->capNhatDienThoai((int)($duLieu['maDienThoai'] ?? $_GET['maDienThoai'] ?? 0), $duLieu, $tepAnh));
            }
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->luuDienThoai($duLieu, $tepAnh));
            }
            if ($phuongThuc === 'PATCH') {
                traKetQuaController($ctrl->capNhatTrangThaiDienThoai((int)($duLieu['maDienThoai'] ?? 0), (string)($duLieu['trangThai'] ?? '')));
            }
            if ($phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->xoaDienThoai((int)($_GET['maDienThoai'] ?? $duLieu['maDienThoai'] ?? 0)));
            }
            break;

        // 3. TimKiemSanPhamCtrl (UC7 - UC9) — Công khai (không lộ giaVonBinhQuan)
        case 'tim-kiem':
            $ctrl = new TimKiemSanPhamCtrl($pdo);
            if ($hanhDong === 'chi-tiet' || !empty($_GET['maDienThoai'])) {
                traKetQuaController($ctrl->layChiTietSanPham((int)($_GET['maDienThoai'] ?? 0)));
            }
            $boLoc = $_GET;
            unset($boLoc['duongDan'], $boLoc['taiNguyen'], $boLoc['hanhDong']);
            if (empty($boLoc)) {
                traKetQuaController($ctrl->layDanhSachSanPham());
            }
            traKetQuaController($ctrl->locSanPham($boLoc));
            break;

        // 4. ChinhSachGiaCtrl (UC11) — Ban Quản lý
        case 'chinh-sach-gia':
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            $ctrl = new ChinhSachGiaCtrl($pdo);
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layCauHinhLoiNhuan());
            }
            if ($phuongThuc === 'POST' || $phuongThuc === 'PUT') {
                traKetQuaController($ctrl->luuCauHinhLoiNhuan($duLieu));
            }
            break;

        // 5. PhieuNhapCtrl (UC12 - UC14) — Nhân viên Kho lập/gửi duyệt; Ban Quản lý duyệt/từ chối
        case 'phieu-nhap':
            $ctrl = new PhieuNhapCtrl($pdo);
            if ($phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Kho', 'Ban Quản lý'], $duLieu);
                if (!empty($_GET['maPhieuNhap'])) {
                    traKetQuaController($ctrl->layChiTietPhieuNhap((int)$_GET['maPhieuNhap']));
                }
                if ($hanhDong === 'cho-duyet') {
                    traKetQuaController($ctrl->layDanhSachPhieuChoDuyet());
                }
                if (!empty($_GET['trangThai']) || !empty($_GET['tuNgay']) || !empty($_GET['denNgay'])) {
                    traKetQuaController($ctrl->locPhieuNhap($_GET['trangThai'] ?? null, $_GET['tuNgay'] ?? null, $_GET['denNgay'] ?? null));
                }
                traKetQuaController($ctrl->layDanhSachPhieuNhap());
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'duyet') {
                yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->duyetPhieuNhap((int)($duLieu['maPhieuNhap'] ?? 0)));
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'tu-choi') {
                yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->tuChoiPhieuNhap((int)($duLieu['maPhieuNhap'] ?? 0), (string)($duLieu['lyDo'] ?? '')));
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'gui-duyet') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Kho'], $duLieu);
                traKetQuaController($ctrl->guiDuyetPhieuNhap((int)($duLieu['maPhieuNhap'] ?? 0)));
            }
            if ($phuongThuc === 'POST') {
                $phien = yeuCauPhanQuyen($pdo, ['Nhân viên Kho'], $duLieu);
                traKetQuaController($ctrl->luuPhieuNhap($duLieu, (int)($phien['maNhanVien'] ?? 0)));
            }
            break;

        // 6. PhieuXuatCtrl (UC15 - UC17) — Nhân viên Kho lập/xác nhận/hủy
        case 'phieu-xuat':
            $ctrl = new PhieuXuatCtrl($pdo);
            if ($phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Kho', 'Ban Quản lý', 'Nhân viên Bán hàng / CSKH'], $duLieu);
                if (!empty($_GET['maPhieuXuat'])) {
                    traKetQuaController($ctrl->layChiTietPhieuXuat((int)$_GET['maPhieuXuat']));
                }
                if (!empty($_GET['trangThai']) || !empty($_GET['tuNgay']) || !empty($_GET['denNgay'])) {
                    traKetQuaController($ctrl->locPhieuXuat($_GET['trangThai'] ?? null, $_GET['tuNgay'] ?? null, $_GET['denNgay'] ?? null));
                }
                traKetQuaController($ctrl->layDanhSachPhieuXuat());
            }
            $phien = yeuCauPhanQuyen($pdo, ['Nhân viên Kho'], $duLieu);
            if ($phuongThuc === 'POST' && $hanhDong === 'xac-nhan') {
                traKetQuaController($ctrl->xacNhanXuatKho((int)($duLieu['maPhieuXuat'] ?? 0)));
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'huy') {
                traKetQuaController($ctrl->huyPhieuXuat((int)($duLieu['maPhieuXuat'] ?? 0)));
            }
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->luuPhieuXuat((int)($duLieu['maDonHang'] ?? 0), (int)($phien['maNhanVien'] ?? 0), $duLieu['ghiChu'] ?? null));
            }
            break;

        // 7. KiemKeCtrl (UC18 - UC21) — Nhân viên Kho lập & xem cảnh báo; Ban Quản lý duyệt & cấu hình ngưỡng
        case 'kiem-ke':
            $ctrl = new KiemKeCtrl($pdo);
            if ($hanhDong === 'canh-bao' && $phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Kho', 'Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->layDanhSachCanhBao($_GET['loaiCanhBao'] ?? null));
            }
            if ($hanhDong === 'nguong-canh-bao' && $phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Kho', 'Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->layDanhSachNguong());
            }
            if ($hanhDong === 'nguong-canh-bao' && ($phuongThuc === 'POST' || $phuongThuc === 'PUT')) {
                yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->luuNguongTonKho($duLieu));
            }
            if ($hanhDong === 'tu-choi' && $phuongThuc === 'POST') {
                yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->tuChoiBienBan((int)($duLieu['maBienBan'] ?? 0), $duLieu['lyDo'] ?? null));
            }
            if ($hanhDong === 'duyet' && $phuongThuc === 'POST') {
                yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
                $pheDuyet = array_key_exists('pheDuyet', $duLieu) ? (bool)$duLieu['pheDuyet'] : true;
                if ($pheDuyet) {
                    traKetQuaController($ctrl->duyetBienBan((int)($duLieu['maBienBan'] ?? 0)));
                } else {
                    traKetQuaController($ctrl->tuChoiBienBan((int)($duLieu['maBienBan'] ?? 0), $duLieu['lyDo'] ?? null));
                }
            }
            if ($phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Kho', 'Ban Quản lý'], $duLieu);
                if (!empty($_GET['maBienBan'])) {
                    traKetQuaController($ctrl->layChiTietBienBan((int)$_GET['maBienBan']));
                }
                traKetQuaController($ctrl->layDanhSachBienBanKiemKe());
            }
            if ($phuongThuc === 'POST') {
                $phien = yeuCauPhanQuyen($pdo, ['Nhân viên Kho'], $duLieu);
                traKetQuaController($ctrl->hoanTatKiemKe($duLieu, (int)($phien['maNhanVien'] ?? 0)));
            }
            break;

        // 8. GioHangCtrl (UC22 - UC24)
        case 'gio-hang':
            $ctrl = new GioHangCtrl($pdo);
            $maPhien = layMaPhienTuYeuCau($duLieu) ?: ('KHACH_' . session_id());
            $phien = (new XacThucCtrl($pdo))->kiemTraPhien($maPhien);
            $maKhachHang = $phien['maKhachHang'] ?? null;

            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layGioHang($maKhachHang, $maPhien));
            }
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->themVaoGioHang($maKhachHang, $maPhien, (int)($duLieu['maDienThoai'] ?? 0), (int)($duLieu['soLuong'] ?? 1)));
            }
            if ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH') {
                traKetQuaController($ctrl->capNhatSoLuong($maKhachHang, $maPhien, (int)($duLieu['maDienThoai'] ?? 0), (int)($duLieu['soLuong'] ?? 0)));
            }
            if ($phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->xoaKhoiGioHang($maKhachHang, $maPhien, (int)($_GET['maDienThoai'] ?? $duLieu['maDienThoai'] ?? 0)));
            }
            break;

        // 9. DatHangCtrl (UC25 - UC29) + UC27 Phí vận chuyển
        case 'phi-van-chuyen':
            $ctrl = new DatHangCtrl($pdo);
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layQuyTacPhi());
            }
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            if ($phuongThuc === 'POST' || $phuongThuc === 'PUT') {
                traKetQuaController($ctrl->luuQuyTacPhi($duLieu));
            }
            break;

        case 'dat-hang':
            $ctrl = new DatHangCtrl($pdo);
            if ($hanhDong === 'phi-van-chuyen') {
                traKetQuaController($ctrl->tinhPhiVanChuyen(
                    isset($_GET['maDiaChi']) ? (int)$_GET['maDiaChi'] : (isset($duLieu['maDiaChi']) ? (int)$duLieu['maDiaChi'] : null),
                    (float)($_GET['tongTamTinh'] ?? $duLieu['tongTamTinh'] ?? 0),
                    isset($_GET['maQuyTac']) ? (int)$_GET['maQuyTac'] : (isset($duLieu['maQuyTac']) ? (int)$duLieu['maQuyTac'] : null)
                ));
            }
            if ($hanhDong === 'thanh-toan' && $phuongThuc === 'POST') {
                $phien = yeuCauPhanQuyen($pdo, ['Khách hàng'], $duLieu);
                traKetQuaController($ctrl->thanhToan((int)($phien['maKhachHang'] ?? 0), $duLieu));
            }
            $phien = yeuCauPhanQuyen($pdo, ['Khách hàng'], $duLieu);
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->xacNhanDatHang((int)($phien['maKhachHang'] ?? 0), $duLieu, layMaPhienTuYeuCau($duLieu)));
            }
            break;

        // 10. ApDungVoucherCtrl (UC30) — Có đối chiếu quyền sở hữu giỏ hàng chống IDOR
        case 'ap-dung-voucher':
            $ctrl = new ApDungVoucherCtrl($pdo);
            $maPhien = layMaPhienTuYeuCau($duLieu);
            $phien = (new XacThucCtrl($pdo))->kiemTraPhien($maPhien);
            $maKhachHang = $phien['maKhachHang'] ?? null;
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->apDungVoucher((int)($duLieu['maGioHang'] ?? 0), (string)($duLieu['maVoucher'] ?? ''), $maKhachHang, $maPhien));
            }
            if ($phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->boApDungVoucher((int)($_GET['maGioHang'] ?? $duLieu['maGioHang'] ?? 0), $maKhachHang, $maPhien));
            }
            break;

        // 11. HuyDonHangCtrl (UC31 - UC32, UC35 - UC38)
        case 'huy-don':
            $ctrl = new HuyDonHangCtrl($pdo);
            if ($phuongThuc === 'POST' && $hanhDong === 'tu-huy') {
                $phien = yeuCauPhanQuyen($pdo, ['Khách hàng'], $duLieu);
                traKetQuaController($ctrl->tuHuyDonHang((int)($duLieu['maDonHang'] ?? 0), (int)($phien['maKhachHang'] ?? 0), (string)($duLieu['lyDo'] ?? '')));
            }
            if ($phuongThuc === 'POST' && ($hanhDong === '' || $hanhDong === 'gui-yeu-cau')) {
                $phien = yeuCauPhanQuyen($pdo, ['Khách hàng'], $duLieu);
                traKetQuaController($ctrl->guiYeuCauHuy((int)($duLieu['maDonHang'] ?? 0), (string)($duLieu['lyDo'] ?? ''), (int)($phien['maKhachHang'] ?? 0)));
            }
            yeuCauPhanQuyen($pdo, ['Nhân viên Bán hàng / CSKH'], $duLieu);
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layDanhSachYeuCauHuy());
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'xac-nhan') {
                traKetQuaController($ctrl->xacNhanHuyDon((int)($duLieu['maYeuCau'] ?? 0)));
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'tu-choi') {
                traKetQuaController($ctrl->tuChoiYeuCauHuy((int)($duLieu['maYeuCau'] ?? 0), $duLieu['lyDo'] ?? $duLieu['lyDoTuChoi'] ?? null));
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'xu-ly') {
                $chapNhan = array_key_exists('chapNhan', $duLieu) ? (bool)$duLieu['chapNhan'] : true;
                if ($chapNhan) {
                    traKetQuaController($ctrl->xacNhanHuyDon((int)($duLieu['maYeuCau'] ?? 0)));
                } else {
                    traKetQuaController($ctrl->tuChoiYeuCauHuy((int)($duLieu['maYeuCau'] ?? 0), $duLieu['lyDoTuChoi'] ?? $duLieu['lyDo'] ?? null));
                }
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'hoan-tien') {
                $soTienHoan = (isset($duLieu['soTienHoan']) && $duLieu['soTienHoan'] !== '') ? (float)$duLieu['soTienHoan'] : null;
                $lyDo = isset($duLieu['lyDo']) ? (string)$duLieu['lyDo'] : null;
                $hoanThanhCong = array_key_exists('hoanThanhCong', $duLieu) ? (bool)$duLieu['hoanThanhCong'] : true;
                traKetQuaController($ctrl->hoanTien((int)($duLieu['maDonHang'] ?? 0), $soTienHoan, $lyDo, $hoanThanhCong));
            }
            break;

        // 12. QuanLyDonHangCtrl (UC33 - UC34) — CSKH duyệt & cập nhật tiến độ; Kho & BQL chỉ xem
        case 'don-hang':
            $ctrl = new QuanLyDonHangCtrl($pdo);
            if ($phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Bán hàng / CSKH', 'Nhân viên Kho', 'Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->layDanhSachDonHang($_GET['trangThai'] ?? null));
            }
            yeuCauPhanQuyen($pdo, ['Nhân viên Bán hàng / CSKH'], $duLieu);
            if ($hanhDong === 'duyet' && ($phuongThuc === 'POST' || $phuongThuc === 'PATCH')) {
                traKetQuaController($ctrl->duyetDonHang((int)($duLieu['maDonHang'] ?? 0)));
            }
            if ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH') {
                $trangThaiMoi = (string)($duLieu['trangThai'] ?? '');
                if ($trangThaiMoi === 'Đã duyệt') {
                    traKetQuaController($ctrl->duyetDonHang((int)($duLieu['maDonHang'] ?? 0)));
                }
                traKetQuaController($ctrl->capNhatTienDo((int)($duLieu['maDonHang'] ?? 0), $trangThaiMoi));
            }
            break;

        // 13. XacThucCtrl (UC39 - UC42) — Cả 4 vai trò kế thừa
        case 'xac-thuc':
            $ctrl = new XacThucCtrl($pdo);
            if ($hanhDong === 'dang-ky' && $phuongThuc === 'POST') {
                traKetQuaController($ctrl->dangKy($duLieu));
            }
            if ($hanhDong === 'dang-nhap' && $phuongThuc === 'POST') {
                traKetQuaController($ctrl->dangNhap($duLieu));
            }
            if ($hanhDong === 'dang-xuat' && $phuongThuc === 'POST') {
                traKetQuaController($ctrl->dangXuat(layMaPhienTuYeuCau($duLieu)));
            }
            if ($hanhDong === 'kiem-tra-phien' && $phuongThuc === 'GET') {
                $phien = $ctrl->kiemTraPhien(layMaPhienTuYeuCau($duLieu));
                if ($phien === null) {
                    phanHoiJson(false, 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn', null, 401);
                }
                phanHoiJson(true, 'Phiên đăng nhập còn hiệu lực', $phien);
            }
            if ($hanhDong === 'quen-mat-khau' && $phuongThuc === 'POST') {
                traKetQuaController($ctrl->guiMaXacThuc((string)($duLieu['dinhDanh'] ?? '')));
            }
            if ($hanhDong === 'dat-lai-mat-khau' && $phuongThuc === 'POST') {
                traKetQuaController($ctrl->datLaiMatKhau(
                    (string)($duLieu['dinhDanh'] ?? ''),
                    (string)($duLieu['maXacThuc'] ?? ''),
                    (string)($duLieu['matKhauMoi'] ?? ''),
                    isset($duLieu['xacNhanMatKhau']) ? (string)$duLieu['xacNhanMatKhau'] : null
                ));
            }
            break;

        // 14. HoSoKhachHangCtrl (UC43 - UC47) & Thông báo nội bộ
        case 'thong-bao':
            $phien = yeuCauPhanQuyen($pdo, ['Ban Quản lý', 'Nhân viên Kho', 'Nhân viên Bán hàng / CSKH', 'Khách hàng'], $duLieu);
            $ctrl = new HoSoKhachHangCtrl($pdo);
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layThongBao((int)$phien['maTaiKhoan']));
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'da-doc') {
                traKetQuaController($ctrl->danhDauDaDocThongBao((int)$phien['maTaiKhoan'], isset($duLieu['maThongBao']) ? (int)$duLieu['maThongBao'] : null));
            }
            break;

        case 'khach-hang':
            // UC47: Nhân viên Bán hàng / CSKH tra cứu thông tin & lịch sử mua khách hàng (chỉ xem)
            yeuCauPhanQuyen($pdo, ['Nhân viên Bán hàng / CSKH', 'Ban Quản lý'], $duLieu);
            $ctrl = new HoSoKhachHangCtrl($pdo);
            if ($phuongThuc === 'GET' && $hanhDong === 'lich-su') {
                traKetQuaController($ctrl->layLichSuMuaHang((int)($_GET['maKhachHang'] ?? 0)));
            }
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->timKhachHang($_GET['tuKhoa'] ?? null));
            }
            break;

        case 'ho-so':
            $phien = yeuCauPhanQuyen($pdo, ['Khách hàng'], $duLieu);
            $ctrl = new HoSoKhachHangCtrl($pdo);
            $maKhachHang = (int)($phien['maKhachHang'] ?? 0);

            if ($hanhDong === 'dia-chi' && $phuongThuc === 'GET') {
                traKetQuaController($ctrl->layDanhSachDiaChi($maKhachHang));
            }
            if ($hanhDong === 'dia-chi' && $phuongThuc === 'POST') {
                traKetQuaController($ctrl->themDiaChiGiaoHang($maKhachHang, $duLieu));
            }
            if ($hanhDong === 'dia-chi' && ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH')) {
                traKetQuaController($ctrl->capNhatDiaChi($maKhachHang, (int)($duLieu['maDiaChi'] ?? 0), $duLieu));
            }
            if ($hanhDong === 'dia-chi' && $phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->xoaDiaChiGiaoHang($maKhachHang, (int)($_GET['maDiaChi'] ?? $duLieu['maDiaChi'] ?? 0)));
            }
            if ($hanhDong === 'don-hang' && $phuongThuc === 'GET') {
                traKetQuaController($ctrl->layLichSuMuaHang($maKhachHang));
            }
            if ($hanhDong === 'thong-bao' && $phuongThuc === 'GET') {
                traKetQuaController($ctrl->layThongBao((int)$phien['maTaiKhoan']));
            }
            if ($hanhDong === 'doi-mat-khau' && $phuongThuc === 'POST') {
                traKetQuaController($ctrl->doiMatKhau((int)$phien['maTaiKhoan'], (string)($duLieu['matKhauCu'] ?? ''), (string)($duLieu['matKhauMoi'] ?? '')));
            }
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layHoSo($maKhachHang));
            }
            if ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH') {
                traKetQuaController($ctrl->capNhatHoSo($maKhachHang, $duLieu));
            }
            break;

        // 15. QuanLyTaiKhoanCtrl (UC48 - UC52) — Ban Quản lý
        case 'tai-khoan':
            $phien = yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            $ctrl = new QuanLyTaiKhoanCtrl($pdo);
            if ($phuongThuc === 'GET') {
                if (!empty($_GET['tuKhoa'])) {
                    traKetQuaController($ctrl->timKiemTaiKhoan((string)$_GET['tuKhoa']));
                }
                traKetQuaController($ctrl->layDanhSachTaiKhoan());
            }
            if ($phuongThuc === 'POST' && $hanhDong === '') {
                traKetQuaController($ctrl->taoTaiKhoanNhanVien($duLieu));
            }
            if (($hanhDong === 'nhan-vien' || $hanhDong === 'vai-tro') && ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH')) {
                traKetQuaController($ctrl->capNhatNhanVien((int)($duLieu['maTaiKhoan'] ?? 0), $duLieu));
            }
            if ($hanhDong === 'mo-khoa' && ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH' || $phuongThuc === 'POST')) {
                traKetQuaController($ctrl->moKhoaTaiKhoan((int)($duLieu['maTaiKhoan'] ?? 0)));
            }
            if ($hanhDong === 'khoa' && ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH' || $phuongThuc === 'POST')) {
                $khoa = array_key_exists('khoa', $duLieu) ? (bool)$duLieu['khoa'] : true;
                if ($khoa) {
                    traKetQuaController($ctrl->khoaTaiKhoan((int)($duLieu['maTaiKhoan'] ?? 0), $duLieu['lyDoKhoa'] ?? $duLieu['lyDo'] ?? null, (int)$phien['maTaiKhoan']));
                } else {
                    traKetQuaController($ctrl->moKhoaTaiKhoan((int)($duLieu['maTaiKhoan'] ?? 0)));
                }
            }
            break;

        // 16. ThongKeBaoCaoCtrl (UC53 - UC56) — Ban Quản lý
        case 'bao-cao':
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            $ctrl = new ThongKeBaoCaoCtrl($pdo);
            $tuNgay = (!empty($_GET['tuNgay'])) ? (string)$_GET['tuNgay'] : null;
            $denNgay = (!empty($_GET['denNgay'])) ? (string)$_GET['denNgay'] : null;
            $maDanhMuc = (!empty($_GET['maDanhMuc'])) ? (int)$_GET['maDanhMuc'] : null;

            if ($hanhDong === 'san-pham-ban-chay') {
                $gioiHan = (int)($_GET['gioiHan'] ?? 10);
                if ($tuNgay || $denNgay || $maDanhMuc) {
                    traKetQuaController($ctrl->locBaoCaoBanChay($tuNgay, $denNgay, $maDanhMuc, $gioiHan));
                }
                traKetQuaController($ctrl->layBaoCaoBanChay($gioiHan));
            }
            if ($hanhDong === 'don-hang') {
                if ($tuNgay || $denNgay) {
                    traKetQuaController($ctrl->layBaoCaoDonHang($tuNgay, $denNgay));
                }
                traKetQuaController($ctrl->layBaoCaoDonHangMacDinh());
            }
            if ($hanhDong === 'ton-kho') {
                traKetQuaController($ctrl->layBaoCaoHangSapHet());
            }
            if ($tuNgay || $denNgay) {
                traKetQuaController($ctrl->layBaoCaoDoanhThu($tuNgay, $denNgay));
            }
            traKetQuaController($ctrl->layBaoCaoDoanhThuMacDinh());
            break;

        // 17. QuanLyKhuyenMaiCtrl (UC57 - UC59) — Ban Quản lý
        case 'khuyen-mai':
            $ctrl = new QuanLyKhuyenMaiCtrl($pdo);
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layDanhSachKhuyenMai());
            }
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->luuKhuyenMai($duLieu));
            }
            if ($phuongThuc === 'PUT') {
                traKetQuaController($ctrl->capNhatKhuyenMai((int)($duLieu['maKhuyenMai'] ?? 0), $duLieu));
            }
            if ($phuongThuc === 'PATCH') {
                traKetQuaController($ctrl->capNhatTrangThaiKhuyenMai((int)($duLieu['maKhuyenMai'] ?? 0), (string)($duLieu['trangThai'] ?? 'Ngừng áp dụng')));
            }
            if ($phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->xoaKhuyenMai((int)($_GET['maKhuyenMai'] ?? $duLieu['maKhuyenMai'] ?? 0)));
            }
            break;

        // 18. QuanLyVoucherCtrl (UC60 - UC64) — Ban Quản lý
        case 'voucher':
            $ctrl = new QuanLyVoucherCtrl($pdo);
            if ($phuongThuc === 'GET') {
                if (!empty($_GET['trangThai'])) {
                    traKetQuaController($ctrl->locVoucher((string)$_GET['trangThai']));
                }
                traKetQuaController($ctrl->layDanhSachVoucher());
            }
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->luuVoucher($duLieu));
            }
            if ($phuongThuc === 'PUT') {
                traKetQuaController($ctrl->capNhatVoucher((string)($duLieu['maVoucher'] ?? ''), $duLieu));
            }
            if ($phuongThuc === 'PATCH') {
                $maVc = (string)($duLieu['maVoucher'] ?? '');
                $ttVc = (string)($duLieu['trangThai'] ?? 'Đã khóa');
                if ($ttVc === 'Đã khóa') {
                    traKetQuaController($ctrl->khoaVoucher($maVc));
                }
                if ($ttVc === 'Đang hiệu lực') {
                    traKetQuaController($ctrl->moKhoaVoucher($maVc));
                }
                traKetQuaController($ctrl->capNhatTrangThaiVoucher($maVc, $ttVc));
            }
            if ($phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->xoaVoucher((string)($_GET['maVoucher'] ?? $duLieu['maVoucher'] ?? '')));
            }
            break;

        default:
            phanHoiJson(false, 'Không tìm thấy tài nguyên API yêu cầu', ['taiNguyen' => $taiNguyen], 404);
    }

    phanHoiJson(false, 'Phương thức HTTP không được hỗ trợ cho tài nguyên này', ['phuongThuc' => $phuongThuc, 'taiNguyen' => $taiNguyen], 405);
} catch (Throwable $ngoaiLe) {
    error_log('Router error: ' . $ngoaiLe->getMessage());
    phanHoiJson(false, 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau', null, 500);
}
