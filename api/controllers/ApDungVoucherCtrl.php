<?php
// Controller BCE: ApDungVoucherCtrl (UC30)

class ApDungVoucherCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // UC30: Kiểm tra thời hạn, soLuong, gioiHanSuDung mỗi khách & DieuKienApDung; giảm trên tongTamTinh (không tính phí ship)
    public function apDungVoucher(int $maGioHang, string $maVoucher, ?int $maKhachHang = null): array
    {
        $maVoucher = strtoupper(trim($maVoucher));
        if ($maGioHang <= 0 || $maVoucher === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập mã voucher hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $gioStmt = $this->pdo->prepare("SELECT * FROM GioHang WHERE maGioHang = ? AND trangThai = 'Đang dùng' LIMIT 1");
        $gioStmt->execute([$maGioHang]);
        $gio = $gioStmt->fetch();
        if (!$gio) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy giỏ hàng đang hoạt động', 'duLieu' => null, 'maHttp' => 404];
        }

        $vStmt = $this->pdo->prepare("SELECT * FROM Voucher WHERE maVoucher = ? LIMIT 1");
        $vStmt->execute([$maVoucher]);
        $rowVoucher = $vStmt->fetch();
        if (!$rowVoucher) {
            return ['thanhCong' => false, 'thongBao' => 'Mã voucher không tồn tại', 'duLieu' => null, 'maHttp' => 404];
        }

        $voucher = Voucher::tuMang($rowVoucher);
        if (!$voucher->conHieuLuc()) {
            return ['thanhCong' => false, 'thongBao' => 'Mã voucher đã hết hạn, đã khóa hoặc hết lượt sử dụng', 'duLieu' => null, 'maHttp' => 400];
        }

        $maKh = $maKhachHang ?? (isset($gio['maKhachHang']) ? (int)$gio['maKhachHang'] : null);

        // Kiểm tra giới hạn sử dụng mỗi khách hàng
        if ($maKh !== null) {
            $demStmt = $this->pdo->prepare("SELECT COUNT(*) FROM DonHang WHERE maKhachHang = ? AND maVoucher = ? AND trangThai <> 'Đã hủy'");
            $demStmt->execute([$maKh, $maVoucher]);
            if ((int)$demStmt->fetchColumn() >= $voucher->gioiHanSuDung) {
                return ['thanhCong' => false, 'thongBao' => "Bạn đã dùng hết giới hạn ({$voucher->gioiHanSuDung} lượt) của mã voucher này", 'duLieu' => null, 'maHttp' => 400];
            }
        }

        // Kiểm tra bảng DieuKienApDung (giaTriToiThieu, nhomHang, doiTuong)
        $dkStmt = $this->pdo->prepare("SELECT * FROM DieuKienApDung WHERE maVoucher = ? LIMIT 1");
        $dkStmt->execute([$maVoucher]);
        $dieuKien = $dkStmt->fetch();
        $tongTamTinh = (float)$gio['tongTamTinh'];

        if ($dieuKien) {
            if (!empty($dieuKien['doiTuong']) && stripos($dieuKien['doiTuong'], 'đăng nhập') !== false && $maKh === null) {
                return ['thanhCong' => false, 'thongBao' => 'Mã voucher này chỉ áp dụng cho khách hàng đã đăng nhập', 'duLieu' => null, 'maHttp' => 401];
            }
            if ($tongTamTinh < (float)$dieuKien['giaTriToiThieu']) {
                $toiThieu = number_format((float)$dieuKien['giaTriToiThieu'], 0, ',', '.');
                return ['thanhCong' => false, 'thongBao' => "Tổng tiền hàng chưa đạt giá trị tối thiểu {$toiThieu}đ để áp dụng voucher", 'duLieu' => null, 'maHttp' => 400];
            }
            $nhomHang = trim((string)($dieuKien['nhomHang'] ?? ''));
            if ($nhomHang !== '' && $nhomHang !== 'Tất cả') {
                $nhomStmt = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM ChiTietGioHang ct
                     JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai
                     LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                     WHERE ct.maGioHang = ? AND (d.hang = ? OR dm.ten = ?)"
                );
                $nhomStmt->execute([$maGioHang, $nhomHang, $nhomHang]);
                if ((int)$nhomStmt->fetchColumn() === 0) {
                    return ['thanhCong' => false, 'thongBao' => "Mã voucher chỉ áp dụng cho nhóm hàng: {$nhomHang}", 'duLieu' => null, 'maHttp' => 400];
                }
            }
        }

        // Tính tiền giảm giá chỉ trên tongTamTinh (đã gồm khuyến mãi, KHÔNG tính trên phí vận chuyển)
        $tienGiamGia = $voucher->loaiGiam === 'Phần trăm'
            ? round($tongTamTinh * ($voucher->giaTriGiam / 100), 2)
            : min($tongTamTinh, $voucher->giaTriGiam);
        $tongTien = max(0.0, $tongTamTinh - $tienGiamGia);

        $this->pdo->prepare("UPDATE GioHang SET maVoucher = ?, tienGiamGia = ?, tongTien = ? WHERE maGioHang = ?")
            ->execute([$maVoucher, $tienGiamGia, $tongTien, $maGioHang]);

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

    public function goBoVoucher(int $maGioHang): array
    {
        $this->pdo->prepare("UPDATE GioHang SET maVoucher = NULL, tienGiamGia = 0, tongTien = tongTamTinh WHERE maGioHang = ?")
            ->execute([$maGioHang]);

        return ['thanhCong' => true, 'thongBao' => 'Đã gỡ bỏ mã voucher khỏi giỏ hàng', 'duLieu' => ['maGioHang' => $maGioHang]];
    }
}
