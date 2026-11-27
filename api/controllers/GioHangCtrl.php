<?php
// Controller BCE: GioHangCtrl (UC22 - UC24) — Phụ lục B.1 Lan03

class GioHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function tinhGiaBanSauKhuyenMai(int $maDienThoai, float $giaGoc): float
    {
        $dsKm = KhuyenMai::timKhuyenMaiDangApDung($maDienThoai, $this->pdo);
        $mucUuDai = !empty($dsKm) ? (float)$dsKm[0]['mucUuDai'] : 0.0;
        return $mucUuDai > 0 ? round($giaGoc * (1 - $mucUuDai / 100), 2) : $giaGoc;
    }

    public function layGioHang(?int $maKhachHang, string $maPhien): array
    {
        $gio = GioHang::timHoacTao($maKhachHang, $maPhien, $this->pdo);
        GioHang::tinhLaiTongTien((int)$gio['maGioHang'], $this->pdo);

        $gioMoi = GioHang::tuMang(GioHang::timTheoMa((int)$gio['maGioHang'], false, $this->pdo))->chuyenThanhMang();
        $chiTiet = [];
        foreach (ChiTietGioHang::timTheoGioHang((int)$gio['maGioHang'], $this->pdo) as $dong) {
            $muc = ChiTietGioHang::tuMang($dong)->chuyenThanhMang();
            $muc['tenDienThoai'] = $dong['tenDienThoai'];
            $muc['hinhAnh'] = chuanHoaDuongDanAnh($dong['hinhAnh']);
            $muc['giaGoc'] = (float)$dong['giaGoc'];
            $muc['tonKhaDung'] = (int)$dong['tonKhaDung'];
            $chiTiet[] = $muc;
        }
        $gioMoi['chiTiet'] = $chiTiet;

        return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin giỏ hàng thành công', 'duLieu' => $gioMoi];
    }

    // UC22: themVaoGioHang(maDienThoai, soLuong)
    public function themVaoGioHang(?int $maKhachHang, string $maPhien, int $maDienThoai, int $soLuong = 1): array
    {
        if ($maDienThoai <= 0 || $soLuong <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Sản phẩm hoặc số lượng không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $sp = DienThoai::timTheoMa($maDienThoai, $this->pdo);
        if (!$sp || $sp['trangThai'] !== 'Đang kinh doanh') {
            return ['thanhCong' => false, 'thongBao' => 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh', 'duLieu' => null, 'maHttp' => 404];
        }

        $gio = GioHang::timHoacTao($maKhachHang, $maPhien, $this->pdo);
        $maGioHang = (int)$gio['maGioHang'];

        $hienCo = ChiTietGioHang::timMotMuc($maGioHang, $maDienThoai, $this->pdo);
        $tongSoLuong = $soLuong + ($hienCo ? (int)$hienCo['soLuong'] : 0);
        $tonKhaDung = TonKho::tinhTonKhaDung($maDienThoai, $this->pdo);

        if ($tongSoLuong > $tonKhaDung) {
            return ['thanhCong' => false, 'thongBao' => "Số lượng vượt quá tồn khả dụng ({$tonKhaDung} sản phẩm)", 'duLieu' => null, 'maHttp' => 400];
        }

        $donGiaCuoi = $this->tinhGiaBanSauKhuyenMai($maDienThoai, (float)$sp['giaBan']);
        ChiTietGioHang::themHoacCongDon($maGioHang, $maDienThoai, $soLuong, $donGiaCuoi, null, $this->pdo);
        GioHang::tinhLaiTongTien($maGioHang, $this->pdo);

        return $this->layGioHang($maKhachHang, $maPhien);
    }

    // UC23: capNhatSoLuong(maChiTietGioHang / maDienThoai, soLuong)
    public function capNhatSoLuong(?int $maKhachHang, string $maPhien, int $maDienThoai, int $soLuong): array
    {
        $gio = GioHang::timHoacTao($maKhachHang, $maPhien, $this->pdo);
        $maGioHang = (int)$gio['maGioHang'];

        if ($soLuong <= 0) {
            return $this->xoaKhoiGioHang($maKhachHang, $maPhien, $maDienThoai);
        }

        $tonKhaDung = TonKho::tinhTonKhaDung($maDienThoai, $this->pdo);
        if ($soLuong > $tonKhaDung) {
            return ['thanhCong' => false, 'thongBao' => "Kho chỉ còn {$tonKhaDung} sản phẩm khả dụng", 'duLieu' => null, 'maHttp' => 400];
        }

        ChiTietGioHang::capNhatSoLuongTheoSanPham($maGioHang, $maDienThoai, $soLuong, $this->pdo);
        GioHang::tinhLaiTongTien($maGioHang, $this->pdo);

        return $this->layGioHang($maKhachHang, $maPhien);
    }

    // UC24: xoaKhoiGioHang(maChiTietGioHang / maDienThoai)
    public function xoaKhoiGioHang(?int $maKhachHang, string $maPhien, int $maDienThoai): array
    {
        $gio = GioHang::timHoacTao($maKhachHang, $maPhien, $this->pdo);
        $maGioHang = (int)$gio['maGioHang'];

        ChiTietGioHang::xoaTheoSanPham($maGioHang, $maDienThoai, $this->pdo);
        GioHang::tinhLaiTongTien($maGioHang, $this->pdo);

        return $this->layGioHang($maKhachHang, $maPhien);
    }
}
