<?php
// Controller BCE: ApDungVoucherCtrl (UC30)
// Đồng bộ 1-1 Báo cáo v4: Bảng 39, Sequence Hình 38, Phụ lục A (Bảng 236)

class ApDungVoucherCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // [UC30 - Hình 38] ApDungVoucherCtrl::apDungVoucher -> Voucher::kiemTraHopLe -> DonHang::demLuotDungVoucher -> DieuKienApDung::kiemTraDieuKien -> GioHang::tinhGiamGia -> GioHang::tinhLaiTongTien
    public function apDungVoucher($maVoucherHoacGioHang, $maGioHangHoacVoucher, ?int $maKhachHang = null, string $maPhien = ''): array
    {
        // Hỗ trợ cả chữ ký chuẩn Hình 38 (maVoucher, maGioHang, maKhachHang) và trường hợp gọi ngược cũ
        if (is_int($maVoucherHoacGioHang) || (is_numeric($maVoucherHoacGioHang) && !is_numeric($maGioHangHoacVoucher))) {
            $maGioHang = (int)$maVoucherHoacGioHang;
            $maVoucher = strtoupper(trim((string)$maGioHangHoacVoucher));
        } else {
            $maVoucher = strtoupper(trim((string)$maVoucherHoacGioHang));
            $maGioHang = (int)$maGioHangHoacVoucher;
        }

        if ($maGioHang <= 0 || $maVoucher === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập mã giảm giá hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $gio = GioHang::timTheoMa($maGioHang, false, $this->pdo);
        if (!$gio || $gio['trangThai'] !== 'Đang dùng') {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy giỏ hàng đang hoạt động', 'duLieu' => null, 'maHttp' => 404];
        }

        if ($maPhien !== '' && !GioHang::kiemTraSoHuu($gio, $maKhachHang, $maPhien)) {
            return ['thanhCong' => false, 'thongBao' => 'Bạn không có quyền thao tác trên giỏ hàng này', 'duLieu' => null, 'maHttp' => 403];
        }

        // 1. Voucher::kiemTraHopLe(maVoucher) — kiểm tra tồn tại, đang hiệu lực, thời hạn, luotDaDung < soLuong
        $voucher = Voucher::timTheoMa($maVoucher, $this->pdo);
        if (!$voucher || !Voucher::kiemTraHopLe($maVoucher, $this->pdo)) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Mã giảm giá không hợp lệ, đã hết hạn hoặc hết lượt sử dụng',
                'duLieu'    => null,
                'maHttp'    => 400,
            ];
        }

        // 2. DonHang::demLuotDungVoucher(maKhachHang, maVoucher) < gioiHanSuDung
        $maKh = $maKhachHang ?? (!empty($gio['maKhachHang']) ? (int)$gio['maKhachHang'] : null);
        if ($maKh !== null) {
            $soLuotDaDung = DonHang::demLuotDungVoucher($maKh, $maVoucher, $this->pdo);
            if ($soLuotDaDung >= (int)$voucher['gioiHanSuDung']) {
                return [
                    'thanhCong' => false,
                    'thongBao'  => 'Tài khoản đã dùng hết số lượt cho phép của mã giảm giá này',
                    'duLieu'    => null,
                    'maHttp'    => 400,
                ];
            }
        }

        // 3. DieuKienApDung::kiemTraDieuKien(maVoucher, maGioHang)
        $tongTamTinh = GioHang::tinhTongTamTinh($maGioHang, $this->pdo);
        if (!DieuKienApDung::kiemTraDieuKien($maVoucher, $maGioHang, $tongTamTinh, $maKh, $this->pdo)) {
            $chiTietLoi = DieuKienApDung::kiemTraDieuKienChiTiet($maVoucher, $maGioHang, $tongTamTinh, $maKh, $this->pdo);
            return [
                'thanhCong' => false,
                'thongBao'  => $chiTietLoi['thongBao'] ?? 'Đơn hàng chưa đạt điều kiện áp dụng mã giảm giá',
                'duLieu'    => null,
                'maHttp'    => $chiTietLoi['maHttp'] ?? 400,
            ];
        }

        // 4. GioHang::tinhGiamGia(maGioHang, maVoucher) & 5. GioHang::tinhLaiTongTien(maGioHang)
        $soTienGiam = GioHang::tinhGiamGia($maGioHang, $maVoucher, $maKh, $this->pdo);
        $tongThanhToan = GioHang::tinhLaiTongTien($maGioHang, $this->pdo);
        $gioCapNhat = GioHang::timTheoMa($maGioHang, false, $this->pdo);

        return [
            'thanhCong' => true,
            'thongBao'  => 'Áp dụng mã giảm giá thành công',
            'duLieu'    => [
                'maGioHang'     => $maGioHang,
                'maVoucher'     => $maVoucher,
                'tongTamTinh'   => $tongTamTinh,
                'tienGiamGia'   => $soTienGiam,
                'phiVanChuyen'  => (float)($gioCapNhat['phiVanChuyen'] ?? 0.0),
                'tongTien'      => (float)($gioCapNhat['tongTien'] ?? max(0.0, $tongTamTinh - $soTienGiam)),
                'tongThanhToan' => $tongThanhToan,
            ],
        ];
    }

    // [UC30 - Hình 38 luồng phụ 4.1] ApDungVoucherCtrl::boApDungVoucher -> GioHang::goBoVoucher -> GioHang::tinhLaiTongTien
    public function boApDungVoucher(int $maGioHang, ?int $maKhachHang = null, string $maPhien = ''): array
    {
        $gio = GioHang::timTheoMa($maGioHang, false, $this->pdo);
        if (!$gio) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy giỏ hàng', 'duLieu' => null, 'maHttp' => 404];
        }
        if ($maPhien !== '' && !GioHang::kiemTraSoHuu($gio, $maKhachHang, $maPhien)) {
            return ['thanhCong' => false, 'thongBao' => 'Bạn không có quyền gỡ mã giảm giá khỏi giỏ hàng của người khác', 'duLieu' => null, 'maHttp' => 403];
        }
        GioHang::goBoVoucher($maGioHang, $this->pdo);
        $tongThanhToan = GioHang::tinhLaiTongTien($maGioHang, $this->pdo);
        $gioCapNhat = GioHang::timTheoMa($maGioHang, false, $this->pdo);

        return [
            'thanhCong' => true,
            'thongBao'  => 'Đã hủy bỏ áp dụng mã giảm giá',
            'duLieu'    => [
                'maGioHang'     => $maGioHang,
                'tongTamTinh'   => (float)($gioCapNhat['tongTamTinh'] ?? 0.0),
                'tienGiamGia'   => 0.0,
                'tongTien'      => (float)($gioCapNhat['tongTien'] ?? 0.0),
                'tongThanhToan' => $tongThanhToan,
            ],
        ];
    }
}
