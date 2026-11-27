<?php
// Controller BCE: QuanLyDonHangCtrl (UC33 - UC34)
// Đồng bộ 1-1 Báo cáo v4: Bảng 42 - Bảng 43, Sequence Hình 41 - Hình 42

class QuanLyDonHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // [UC33, UC34 - Hình 41, Hình 42] QuanLyDonHangCtrl::layDanhSachDonHang -> DonHang::timTheoTrangThai
    public function layDanhSachDonHang(?string $trangThai = null): array
    {
        $dsDonHang = DonHang::timTheoTrangThai($trangThai, $this->pdo);
        $tatCaCt = ChiTietDonHang::timTheoDonHang(null, null, $this->pdo);
        $ctTheoDon = [];
        foreach ($tatCaCt as $ct) {
            $ma = (int)$ct['maDonHang'];
            $muc = ChiTietDonHang::tuMang($ct)->chuyenThanhMang();
            $muc['tenDienThoai'] = $ct['tenDienThoai'] ?? '';
            $muc['hinhAnh'] = chuanHoaDuongDanAnh($ct['hinhAnh'] ?? '');
            $ctTheoDon[$ma][] = $muc;
        }

        $ketQua = [];
        foreach ($dsDonHang as $dh) {
            $muc = DonHang::tuMang($dh)->chuyenThanhMang();
            $muc['tenKhachHang'] = $dh['tenKhachHang'];
            $muc['soDienThoai'] = $dh['soDienThoai'];
            $muc['nguoiNhan'] = $dh['nguoiNhan'];
            $muc['diaChiGiaoHang'] = $dh['diaChiGiaoHang'];
            $muc['tenNhanVienDuyet'] = $dh['tenNhanVienDuyet'] ?? '';
            $muc['phuongThucThanhToan'] = $dh['phuongThuc'] ?? '';
            $muc['trangThaiThanhToan'] = $dh['trangThaiThanhToan'] ?? '';
            $muc['maPhieuXuat'] = isset($dh['maPhieuXuat']) ? (int)$dh['maPhieuXuat'] : null;
            $muc['trangThaiPhieuXuat'] = $dh['trangThaiPhieuXuat'] ?? null;
            $muc['chiTiet'] = $ctTheoDon[$muc['maDonHang']] ?? [];
            $ketQua[] = $muc;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách đơn hàng thành công', 'duLieu' => $ketQua];
    }

    // [UC33 - Hình 41] QuanLyDonHangCtrl::duyetDonHang -> DonHang::timTheoMa -> TonKho::kiemTraGiuCho -> DonHang::capNhatTrangThai -> ThongBao::taoThongBao
    public function duyetDonHang(int $maDonHang, ?int $maNhanVienDuyet = null): array
    {
        $donHang = DonHang::timTheoMa($maDonHang, false, $this->pdo);
        if (!$donHang) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy đơn hàng', 'duLieu' => null, 'maHttp' => 404];
        }
        if ($donHang['trangThai'] !== 'Chờ duyệt') {
            return ['thanhCong' => false, 'thongBao' => 'Chỉ có thể duyệt đơn hàng đang ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
        }
        if (!TonKho::kiemTraGiuCho($maDonHang, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Không thể duyệt đơn hàng vì tồn kho thực tế đang thiếu so với số lượng giữ chỗ', 'duLieu' => null, 'maHttp' => 400];
        }

        DonHang::capNhatTrangThai($maDonHang, 'Đã duyệt', null, $maNhanVienDuyet, $this->pdo);
        if (!empty($donHang['maTaiKhoan'])) {
            ThongBao::taoThongBao((int)$donHang['maTaiKhoan'], "Đơn hàng #{$maDonHang} của bạn đã được duyệt và chuẩn bị đóng gói.", $this->pdo);
        }

        return [
            'thanhCong' => true,
            'thongBao'  => "Duyệt đơn hàng #{$maDonHang} thành công",
            'duLieu'    => ['maDonHang' => $maDonHang, 'trangThai' => 'Đã duyệt'],
        ];
    }

    // [UC34 - Hình 42] QuanLyDonHangCtrl::capNhatTienDo -> DonHang::kiemTraChuyenTrangThai -> PhieuXuat::kiemTraDaXacNhan -> DonHang::capNhatTrangThai -> ThanhToan::capNhatTrangThai
    public function capNhatTienDo(int $maDonHang, string $trangThaiMoi, ?int $maNhanVienDuyet = null): array
    {
        if ($trangThaiMoi === 'Đã duyệt') {
            return $this->duyetDonHang($maDonHang, $maNhanVienDuyet);
        }

        $donHang = DonHang::timTheoMa($maDonHang, false, $this->pdo);
        if (!$donHang) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy đơn hàng', 'duLieu' => null, 'maHttp' => 404];
        }

        if ($trangThaiMoi === 'Đã giao shipper') {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Trạng thái Đã giao shipper được cập nhật tự động khi Nhân viên Kho xác nhận xuất kho',
                'duLieu'    => null,
                'maHttp'    => 400,
            ];
        }

        $trangThaiHienTai = $donHang['trangThai'];
        if (!DonHang::kiemTraChuyenTrangThai($trangThaiHienTai, $trangThaiMoi)) {
            return [
                'thanhCong' => false,
                'thongBao'  => "Không thể chuyển đơn hàng từ '{$trangThaiHienTai}' sang '{$trangThaiMoi}' (sai thứ tự vòng đời đơn hàng)",
                'duLieu'    => null,
                'maHttp'    => 400,
            ];
        }

        if ($trangThaiMoi === 'Đang giao' && !PhieuXuat::kiemTraDaXacNhan($maDonHang, $this->pdo)) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Không thể chuyển sang Đang giao vì đơn hàng chưa có phiếu xuất kho ở trạng thái Đã xuất',
                'duLieu'    => null,
                'maHttp'    => 400,
            ];
        }

        DonHang::capNhatTrangThai($maDonHang, $trangThaiMoi, $this->pdo);

        if ($trangThaiMoi === 'Đã giao thành công') {
            ThanhToan::capNhatTrangThai($maDonHang, 'Thành công', 'Chưa thu tiền', $this->pdo);
        }

        if (!empty($donHang['maTaiKhoan'])) {
            ThongBao::taoThongBao((int)$donHang['maTaiKhoan'], "Cập nhật tiến độ đơn hàng #{$maDonHang}: {$trangThaiMoi}.", $this->pdo);
        }

        return [
            'thanhCong' => true,
            'thongBao'  => "Cập nhật tiến độ đơn hàng #{$maDonHang} sang '{$trangThaiMoi}' thành công",
            'duLieu'    => ['maDonHang' => $maDonHang, 'trangThai' => $trangThaiMoi],
        ];
    }
}
