<?php
// Controller BCE: ApDungVoucherCtrl (UC30) — Phụ lục B.1 Lan03

class ApDungVoucherCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // UC30: apDungVoucher(maVoucher, maGioHang)
    public function apDungVoucher(int $maGioHang, string $maVoucher, ?int $maKhachHang = null, string $maPhien = ''): array
    {
        $maVoucher = strtoupper(trim($maVoucher));
        if ($maGioHang <= 0 || $maVoucher === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập mã voucher hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $gio = GioHang::timTheoMa($maGioHang, false, $this->pdo);
        if (!$gio || $gio['trangThai'] !== 'Đang dùng') {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy giỏ hàng đang hoạt động', 'duLieu' => null, 'maHttp' => 404];
        }

        if (!GioHang::kiemTraSoHuu($gio, $maKhachHang, $maPhien)) {
            return ['thanhCong' => false, 'thongBao' => 'Bạn không có quyền thao tác trên giỏ hàng này', 'duLieu' => null, 'maHttp' => 403];
        }

        $voucher = Voucher::timTheoMa($maVoucher, $this->pdo);
        if (!$voucher) {
            return ['thanhCong' => false, 'thongBao' => 'Mã voucher không tồn tại', 'duLieu' => null, 'maHttp' => 404];
        }

        if (!Voucher::kiemTraMaHopLe($maVoucher, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Mã voucher đã hết hạn, đã khóa hoặc hết lượt sử dụng', 'duLieu' => null, 'maHttp' => 400];
        }

        $maKh = $maKhachHang ?? (isset($gio['maKhachHang']) ? (int)$gio['maKhachHang'] : null);
        if ($maKh !== null && !Voucher::kiemTraLuotSuDung($maVoucher, $maKh, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => "Bạn đã dùng hết giới hạn ({$voucher['gioiHanSuDung']} lượt) của mã voucher này", 'duLieu' => null, 'maHttp' => 400];
        }

        $tongTamTinh = GioHang::tinhTongTamTinh($maGioHang, $this->pdo);
        $ktDk = DieuKienApDung::kiemTraDieuKien($maVoucher, $maGioHang, $tongTamTinh, $maKh, $this->pdo);
        if (!$ktDk['hopLe']) {
            return ['thanhCong' => false, 'thongBao' => $ktDk['thongBao'], 'duLieu' => null, 'maHttp' => $ktDk['maHttp'] ?? 400];
        }

        $tienGiamGia = $voucher['loaiGiam'] === 'Phần trăm'
            ? round($tongTamTinh * ((float)$voucher['giaTriGiam'] / 100), 2)
            : min($tongTamTinh, (float)$voucher['giaTriGiam']);
        $tongTien = max(0.0, $tongTamTinh - $tienGiamGia);

        GioHang::ganVoucher($maGioHang, $maVoucher, $tongTamTinh, $tienGiamGia, $tongTien, $this->pdo);

        return [
            'thanhCong' => true,
            'thongBao'  => 'Áp dụng mã voucher thành công',
            'duLieu'    => [
                'maGioHang'   => $maGioHang,
                'maVoucher'   => $maVoucher,
                'tongTamTinh' => $tongTamTinh,
                'tienGiamGia' => $tienGiamGia,
                'tongTien'    => $tongTien,
            ],
        ];
    }

    // UC30: boApDungVoucher(maGioHang) — kiểm tra quyền sở hữu giỏ hàng chống IDOR
    public function boApDungVoucher(int $maGioHang, ?int $maKhachHang = null, string $maPhien = ''): array
    {
        $gio = GioHang::timTheoMa($maGioHang, false, $this->pdo);
        if (!$gio) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy giỏ hàng', 'duLieu' => null, 'maHttp' => 404];
        }
        if (!GioHang::kiemTraSoHuu($gio, $maKhachHang, $maPhien)) {
            return ['thanhCong' => false, 'thongBao' => 'Bạn không có quyền gỡ voucher khỏi giỏ hàng của người khác', 'duLieu' => null, 'maHttp' => 403];
        }
        GioHang::goBoVoucher($maGioHang, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Đã gỡ bỏ mã voucher khỏi giỏ hàng', 'duLieu' => ['maGioHang' => $maGioHang]];
    }
}
