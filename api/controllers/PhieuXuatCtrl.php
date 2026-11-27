<?php
// Controller BCE: PhieuXuatCtrl (UC15 - UC17)
// Đồng bộ 1-1 Báo cáo v4: Bảng 24 - Bảng 26, Sequence Hình 23 - Hình 25

class PhieuXuatCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function dinhKemChiTiet(array $dsPhieu): array
    {
        $tatCaCt = ChiTietPhieuXuat::timTheoPhieuXuat(null, $this->pdo);
        $ctTheoPhieu = [];
        foreach ($tatCaCt as $ct) {
            $ma = (int)$ct['maPhieuXuat'];
            $muc = ChiTietPhieuXuat::tuMang($ct)->chuyenThanhMang();
            $muc['tenDienThoai'] = $ct['tenDienThoai'] ?? '';
            $ctTheoPhieu[$ma][] = $muc;
        }
        $ketQua = [];
        foreach ($dsPhieu as $p) {
            $phieu = PhieuXuat::tuMang($p)->chuyenThanhMang();
            $phieu['tenNhanVien'] = $p['tenNhanVien'] ?? '';
            $phieu['tenNguoiXacNhan'] = $p['tenNguoiXacNhan'] ?? '';
            $phieu['trangThaiDonHang'] = $p['trangThaiDonHang'] ?? '';
            $phieu['chiTiet'] = $ctTheoPhieu[$phieu['maPhieuXuat']] ?? [];
            $ketQua[] = $phieu;
        }
        return $ketQua;
    }

    // [UC15, UC17 - Hình 23, Hình 25] PhieuXuatCtrl::layDanhSachPhieuXuat -> PhieuXuat::timTatCa
    public function layDanhSachPhieuXuat(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách phiếu xuất thành công', 'duLieu' => $this->dinhKemChiTiet(PhieuXuat::timTatCa($this->pdo))];
    }

    // [UC17 - Hình 25] PhieuXuatCtrl::locPhieuXuat -> PhieuXuat::timTheoDieuKien
    public function locPhieuXuat(?string $trangThai = null, ?string $tuNgay = null, ?string $denNgay = null): array
    {
        $ds = PhieuXuat::timTheoDieuKien($trangThai, $tuNgay, $denNgay, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lọc danh sách phiếu xuất thành công', 'duLieu' => $this->dinhKemChiTiet($ds)];
    }

    // [UC16, UC17 - Hình 24, Hình 25] PhieuXuatCtrl::layChiTietPhieuXuat -> PhieuXuat::timTheoMa -> ChiTietPhieuXuat::timTheoPhieuXuat
    public function layChiTietPhieuXuat(int $maPhieuXuat): array
    {
        $px = PhieuXuat::timTheoMa($maPhieuXuat, false, $this->pdo);
        if (!$px) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy phiếu xuất', 'duLieu' => null, 'maHttp' => 404];
        }
        $muc = PhieuXuat::tuMang($px)->chuyenThanhMang();
        $muc['tenNhanVien'] = $px['tenNhanVien'] ?? '';
        $muc['tenNguoiXacNhan'] = $px['tenNguoiXacNhan'] ?? '';
        $muc['chiTiet'] = ChiTietPhieuXuat::timTheoPhieuXuat($maPhieuXuat, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết phiếu xuất thành công', 'duLieu' => $muc];
    }

    public function taoPhieuXuat(int $maDonHang): array
    {
        $dh = DonHang::timTheoMa($maDonHang, false, $this->pdo);
        if (!$dh) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy đơn hàng', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin đơn hàng để lập phiếu xuất thành công', 'duLieu' => ['donHang' => $dh, 'chiTiet' => DonHang::layChiTietDonHang($maDonHang, $this->pdo)]];
    }

    // [UC15 - Hình 23] PhieuXuatCtrl::luuPhieuXuat -> DonHang::kiemTraTrangThaiXuatKho -> PhieuXuat::kiemTraDaCoPhieuXuat -> TonKho::kiemTraDuHangTheoGiuCho -> PhieuXuat::luu -> ChiTietPhieuXuat::luu -> DonHang::capNhatTrangThai
    public function luuPhieuXuat(int $maDonHang, int $maNhanVien, ?string $ghiChu = null): array
    {
        try {
            batDauGiaoDich($this->pdo);

            if (!DonHang::kiemTraTrangThaiXuatKho($maDonHang, $this->pdo)) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Chỉ có thể lập phiếu xuất cho đơn hàng ở trạng thái Đã duyệt hoặc Đang đóng gói', 'duLieu' => null, 'maHttp' => 400];
            }

            if (PhieuXuat::kiemTraDaCoPhieuXuat($maDonHang, $this->pdo)) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Đơn hàng này đã có phiếu xuất kho đang hiệu lực', 'duLieu' => null, 'maHttp' => 409];
            }

            if (!TonKho::kiemTraDuHangTheoGiuCho($maDonHang, $this->pdo)) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Tồn thực tế trong kho hiện không đủ số lượng cho đơn hàng này', 'duLieu' => null, 'maHttp' => 400];
            }

            $maPhieuXuat = PhieuXuat::luu($maDonHang, $maNhanVien, $ghiChu, 'Nháp', $this->pdo);
            foreach (DonHang::layChiTietDonHang($maDonHang, $this->pdo) as $ct) {
                ChiTietPhieuXuat::luu($maPhieuXuat, (int)$ct['maDienThoai'], (int)$ct['soLuong'], $this->pdo);
            }
            DonHang::capNhatTrangThai($maDonHang, 'Đang đóng gói', $this->pdo);

            xacNhanGiaoDich($this->pdo);
            return ['thanhCong' => true, 'thongBao' => 'Lập phiếu xuất kho (Nháp) thành công', 'duLieu' => ['maPhieuXuat' => $maPhieuXuat, 'trangThai' => 'Nháp'], 'maHttp' => 201];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('luuPhieuXuat: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể lập phiếu xuất kho', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // [UC16 - Hình 24] PhieuXuatCtrl::xacNhanXuatKho -> PhieuXuat::kiemTraHopLe -> ChiTietPhieuXuat::timTheoPhieuXuat -> TonKho::truTonThucTe -> TonKho::giamGiuCho -> PhieuXuat::capNhatTrangThai -> DonHang::capNhatTrangThai -> ThongBao::taoThongBao
    public function xacNhanXuatKho(int $maPhieuXuat, ?int $maNguoiXacNhan = null): array
    {
        try {
            batDauGiaoDich($this->pdo);

            $phieu = PhieuXuat::timTheoMa($maPhieuXuat, true, $this->pdo);
            if (!$phieu || $phieu['trangThai'] !== 'Nháp') {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Phiếu xuất không tồn tại hoặc không ở trạng thái Nháp', 'duLieu' => null, 'maHttp' => 400];
            }

            $maDonHang = (int)$phieu['maDonHang'];
            $donHang = DonHang::timTheoMa($maDonHang, true, $this->pdo);
            if (!$donHang || $donHang['trangThai'] === 'Đã hủy' || !in_array($donHang['trangThai'], ['Đã duyệt', 'Đang đóng gói'], true)) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Đơn hàng đã bị hủy hoặc không ở trạng thái hợp lệ để xuất kho', 'duLieu' => null, 'maHttp' => 400];
            }

            foreach (ChiTietPhieuXuat::timTheoPhieuXuat($maPhieuXuat, $this->pdo) as $ct) {
                $maDt = (int)$ct['maDienThoai'];
                $soLuong = (int)$ct['soLuong'];
                if (!TonKho::truTonThucTe($maDt, $soLuong, $this->pdo)) {
                    hoanTacGiaoDich($this->pdo);
                    return ['thanhCong' => false, 'thongBao' => 'Tồn thực tế trong kho không đủ để xuất sản phẩm mã #' . $maDt, 'duLieu' => null, 'maHttp' => 400];
                }
                TonKho::giamGiuCho($maDt, $soLuong, $this->pdo);
            }

            PhieuXuat::capNhatTrangThai($maPhieuXuat, 'Đã xuất', $maNguoiXacNhan, null, $this->pdo);
            DonHang::capNhatTrangThai($maDonHang, 'Đã giao shipper', $this->pdo);

            if (!empty($donHang['maTaiKhoan'])) {
                ThongBao::taoThongBao((int)$donHang['maTaiKhoan'], "Đơn hàng #{$maDonHang} đã xuất kho và bàn giao cho đơn vị vận chuyển.", $this->pdo);
            }

            xacNhanGiaoDich($this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Xác nhận xuất kho thành công và tự động chuyển đơn hàng sang Đã giao shipper',
                'duLieu'    => ['maPhieuXuat' => $maPhieuXuat, 'maDonHang' => $maDonHang, 'trangThai' => 'Đã xuất'],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('xacNhanXuatKho: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể xác nhận xuất kho', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function huyPhieuXuat(int $maPhieuXuat): array
    {
        if (!PhieuXuat::huyPhieuXuat($maPhieuXuat, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Chỉ được hủy phiếu xuất ở trạng thái Nháp', 'duLieu' => null, 'maHttp' => 400];
        }
        return ['thanhCong' => true, 'thongBao' => 'Hủy phiếu xuất nháp thành công (có thể lập lại phiếu xuất mới cho đơn hàng này)', 'duLieu' => ['maPhieuXuat' => $maPhieuXuat, 'trangThai' => 'Đã hủy']];
    }
}
