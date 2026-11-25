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
                traKetQuaController($ctrl->layDanhSachDanhMuc());
            }
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->themDanhMuc($duLieu));
            }
            if ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH') {
                traKetQuaController($ctrl->capNhatDanhMuc((int)($duLieu['maDanhMuc'] ?? $_GET['maDanhMuc'] ?? 0), $duLieu));
            }
            if ($phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->xoaDanhMuc((int)($_GET['maDanhMuc'] ?? $duLieu['maDanhMuc'] ?? 0)));
            }
            break;

        // 2. QuanLyDienThoaiCtrl (UC4 - UC6, UC10) — Ban Quản lý
        case 'dien-thoai':
            $ctrl = new QuanLyDienThoaiCtrl($pdo);
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layDanhSachDienThoai($_GET['trangThai'] ?? null));
            }
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            if ($hanhDong === 'khoi-phuc' && ($phuongThuc === 'POST' || $phuongThuc === 'PATCH')) {
                traKetQuaController($ctrl->khoiPhucDienThoai((int)($duLieu['maDienThoai'] ?? 0)));
            }
            $tepAnh = $_FILES['hinhAnh'] ?? $_FILES['anh'] ?? null;
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->themDienThoai($duLieu, $tepAnh));
            }
            if ($phuongThuc === 'PUT') {
                traKetQuaController($ctrl->capNhatDienThoai((int)($duLieu['maDienThoai'] ?? $_GET['maDienThoai'] ?? 0), $duLieu, $tepAnh));
            }
            if ($phuongThuc === 'PATCH') {
                traKetQuaController($ctrl->capNhatTrangThaiDienThoai((int)($duLieu['maDienThoai'] ?? 0), (string)($duLieu['trangThai'] ?? '')));
            }
            if ($phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->xoaDienThoai((int)($_GET['maDienThoai'] ?? $duLieu['maDienThoai'] ?? 0)));
            }
            break;

        // 3. TimKiemSanPhamCtrl (UC7 - UC9) — Công khai
        case 'tim-kiem':
            $ctrl = new TimKiemSanPhamCtrl($pdo);
            if ($hanhDong === 'chi-tiet' || !empty($_GET['maDienThoai'])) {
                traKetQuaController($ctrl->xemChiTietDienThoai((int)($_GET['maDienThoai'] ?? 0)));
            }
            traKetQuaController($ctrl->timKiemVaLocDienThoai($_GET));
            break;

        // 4. ChinhSachGiaCtrl (UC11) — Ban Quản lý
        case 'chinh-sach-gia':
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            $ctrl = new ChinhSachGiaCtrl($pdo);
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layCauHinhLoiNhuan());
            }
            if ($phuongThuc === 'POST' || $phuongThuc === 'PUT') {
                traKetQuaController($ctrl->capNhatChinhSachLoiNhuan($duLieu));
            }
            break;

        // 5. PhieuNhapCtrl (UC12 - UC14) — Nhân viên Kho lập/gửi duyệt; Ban Quản lý duyệt/từ chối
        case 'phieu-nhap':
            $ctrl = new PhieuNhapCtrl($pdo);
            if ($phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Kho', 'Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->layDanhSachPhieuNhap($_GET['trangThai'] ?? null));
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
                traKetQuaController($ctrl->taoPhieuNhap($duLieu, (int)($phien['maNhanVien'] ?? 0)));
            }
            break;

        // 6. PhieuXuatCtrl (UC15 - UC17) — DUY NHẤT Nhân viên Kho lập/xác nhận/hủy
        case 'phieu-xuat':
            $ctrl = new PhieuXuatCtrl($pdo);
            if ($phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Kho', 'Ban Quản lý', 'Nhân viên Bán hàng / CSKH'], $duLieu);
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
                traKetQuaController($ctrl->taoPhieuXuatTuDonHang((int)($duLieu['maDonHang'] ?? 0), (int)($phien['maNhanVien'] ?? 0), $duLieu['ghiChu'] ?? null));
            }
            break;

        // 7. KiemKeCtrl (UC18 - UC21) — Nhân viên Kho lập & xem cảnh báo; Ban Quản lý duyệt & cấu hình ngưỡng
        case 'kiem-ke':
            $ctrl = new KiemKeCtrl($pdo);
            if ($hanhDong === 'canh-bao' && $phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Kho', 'Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->layCanhBaoTonKho());
            }
            if ($hanhDong === 'nguong-canh-bao' && ($phuongThuc === 'POST' || $phuongThuc === 'PUT')) {
                yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->capNhatNguongCanhBao($duLieu));
            }
            if ($hanhDong === 'duyet' && $phuongThuc === 'POST') {
                yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->duyetBienBanKiemKe((int)($duLieu['maBienBan'] ?? 0), (bool)($duLieu['pheDuyet'] ?? true), $duLieu['lyDo'] ?? null));
            }
            if ($phuongThuc === 'GET') {
                yeuCauPhanQuyen($pdo, ['Nhân viên Kho', 'Ban Quản lý'], $duLieu);
                traKetQuaController($ctrl->layDanhSachBienBan());
            }
            if ($phuongThuc === 'POST') {
                $phien = yeuCauPhanQuyen($pdo, ['Nhân viên Kho'], $duLieu);
                traKetQuaController($ctrl->taoBienBanKiemKe($duLieu, (int)($phien['maNhanVien'] ?? 0)));
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
                traKetQuaController($ctrl->layDanhSachQuyTacPhi());
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
            if ($hanhDong === 'xac-nhan-thanh-toan' && $phuongThuc === 'POST') {
                yeuCauPhanQuyen($pdo, ['Khách hàng', 'Nhân viên Bán hàng / CSKH'], $duLieu);
                traKetQuaController($ctrl->xacNhanThanhToanTrucTuyen((int)($duLieu['maDonHang'] ?? 0), (bool)($duLieu['thanhCong'] ?? true)));
            }
            $phien = yeuCauPhanQuyen($pdo, ['Khách hàng'], $duLieu);
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->datHang((int)($phien['maKhachHang'] ?? 0), $duLieu));
            }
            break;

        // 10. ApDungVoucherCtrl (UC30)
        case 'ap-dung-voucher':
            $ctrl = new ApDungVoucherCtrl($pdo);
            $phien = (new XacThucCtrl($pdo))->kiemTraPhien(layMaPhienTuYeuCau($duLieu));
            $maKhachHang = $phien['maKhachHang'] ?? null;
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->apDungVoucher((int)($duLieu['maGioHang'] ?? 0), (string)($duLieu['maVoucher'] ?? ''), $maKhachHang));
            }
            if ($phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->goBoVoucher((int)($_GET['maGioHang'] ?? $duLieu['maGioHang'] ?? 0)));
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
                yeuCauPhanQuyen($pdo, ['Khách hàng'], $duLieu);
                traKetQuaController($ctrl->guiYeuCauHuyDon((int)($duLieu['maDonHang'] ?? 0), (string)($duLieu['lyDo'] ?? '')));
            }
            yeuCauPhanQuyen($pdo, ['Nhân viên Bán hàng / CSKH'], $duLieu);
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layDanhSachYeuCauHuy());
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'xu-ly') {
                traKetQuaController($ctrl->xuLyYeuCauHuyDon((int)($duLieu['maYeuCau'] ?? 0), (bool)($duLieu['chapNhan'] ?? true), $duLieu['lyDoTuChoi'] ?? null));
            }
            if ($phuongThuc === 'POST' && $hanhDong === 'hoan-tien') {
                traKetQuaController($ctrl->xuLyHoanTien((int)($duLieu['maDonHang'] ?? 0), (bool)($duLieu['hoanThanhCong'] ?? true)));
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
            if ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH') {
                traKetQuaController($ctrl->capNhatTrangThaiDonHang((int)($duLieu['maDonHang'] ?? 0), (string)($duLieu['trangThai'] ?? '')));
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
                traKetQuaController($ctrl->yeuCauKhoiPhucMatKhau((string)($duLieu['dinhDanh'] ?? '')));
            }
            if ($hanhDong === 'dat-lai-mat-khau' && $phuongThuc === 'POST') {
                traKetQuaController($ctrl->datLaiMatKhau((string)($duLieu['dinhDanh'] ?? ''), (string)($duLieu['maXacThuc'] ?? ''), (string)($duLieu['matKhauMoi'] ?? '')));
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
                traKetQuaController($ctrl->layLichSuDonHang((int)($_GET['maKhachHang'] ?? 0)));
            }
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->traCuuKhachHang($_GET['tuKhoa'] ?? null));
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
                traKetQuaController($ctrl->capNhatDiaChiGiaoHang($maKhachHang, (int)($duLieu['maDiaChi'] ?? 0), $duLieu));
            }
            if ($hanhDong === 'dia-chi' && $phuongThuc === 'DELETE') {
                traKetQuaController($ctrl->xoaDiaChiGiaoHang($maKhachHang, (int)($_GET['maDiaChi'] ?? $duLieu['maDiaChi'] ?? 0)));
            }
            if ($hanhDong === 'don-hang' && $phuongThuc === 'GET') {
                traKetQuaController($ctrl->layLichSuDonHang($maKhachHang));
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
                traKetQuaController($ctrl->layDanhSachTaiKhoan());
            }
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->taoTaiKhoanNhanVien($duLieu));
            }
            if ($hanhDong === 'vai-tro' && ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH')) {
                traKetQuaController($ctrl->capNhatVaiTro((int)($duLieu['maTaiKhoan'] ?? 0), (string)($duLieu['vaiTro'] ?? '')));
            }
            if ($hanhDong === 'khoa' && ($phuongThuc === 'PUT' || $phuongThuc === 'PATCH')) {
                traKetQuaController($ctrl->khoaHoacMoKhoaTaiKhoan((int)($duLieu['maTaiKhoan'] ?? 0), (bool)($duLieu['khoa'] ?? true), $duLieu['lyDoKhoa'] ?? null, (int)$phien['maTaiKhoan']));
            }
            break;

        // 16. ThongKeBaoCaoCtrl (UC53 - UC56) — Ban Quản lý
        case 'bao-cao':
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            $ctrl = new ThongKeBaoCaoCtrl($pdo);
            if ($hanhDong === 'san-pham-ban-chay') {
                traKetQuaController($ctrl->thongKeSanPhamBanChay((int)($_GET['gioiHan'] ?? 10)));
            }
            if ($hanhDong === 'don-hang') {
                traKetQuaController($ctrl->thongKeDonHangTheoTrangThai());
            }
            if ($hanhDong === 'ton-kho') {
                traKetQuaController($ctrl->thongKeCanhBaoTonKho());
            }
            traKetQuaController($ctrl->thongKeDoanhThuLoiNhuan($_GET['tuNgay'] ?? null, $_GET['denNgay'] ?? null));
            break;

        // 17. QuanLyKhuyenMaiCtrl (UC57 - UC59) — Ban Quản lý
        case 'khuyen-mai':
            $ctrl = new QuanLyKhuyenMaiCtrl($pdo);
            if ($phuongThuc === 'GET') {
                traKetQuaController($ctrl->layDanhSachKhuyenMai());
            }
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->taoKhuyenMai($duLieu));
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
                traKetQuaController($ctrl->layDanhSachVoucher());
            }
            yeuCauPhanQuyen($pdo, ['Ban Quản lý'], $duLieu);
            if ($phuongThuc === 'POST') {
                traKetQuaController($ctrl->taoVoucher($duLieu));
            }
            if ($phuongThuc === 'PUT') {
                traKetQuaController($ctrl->capNhatVoucher((string)($duLieu['maVoucher'] ?? ''), $duLieu));
            }
            if ($phuongThuc === 'PATCH') {
                traKetQuaController($ctrl->capNhatTrangThaiVoucher((string)($duLieu['maVoucher'] ?? ''), (string)($duLieu['trangThai'] ?? 'Đã khóa')));
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
    phanHoiJson(false, 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau', null, 500);
}
