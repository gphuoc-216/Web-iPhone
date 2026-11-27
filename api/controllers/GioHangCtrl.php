<?php
// Controller BCE: GioHangCtrl (UC22 - UC24)
// Đồng bộ 1-1 Báo cáo v4: Bảng 31 - Bảng 33, Sequence Hình 30 - Hình 32

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
        $mucUuDai = 0.0;
        foreach ($dsKm as $km) {
            if ((float)$km['mucUuDai'] > $mucUuDai) {
                $mucUuDai = (float)$km['mucUuDai'];
            }
        }
        return $mucUuDai > 0 ? round($giaGoc * (1 - $mucUuDai / 100), 2) : $giaGoc;
    }

    public function layGioHang(?int $maKhachHang, string $maPhien): array
    {
        $gio = GioHang::timHoacTao($maKhachHang, $maPhien, $this->pdo);
        $maGioHang = (int)$gio['maGioHang'];
        GioHang::tinhLaiTongTien($maGioHang, $this->pdo);

        $gioDayDu = GioHang::timTheoMa($maGioHang, false, $this->pdo);
        $gioMoi = GioHang::tuMang($gioDayDu)->chuyenThanhMang();
        $gioMoi['tongThanhToan'] = (float)($gioDayDu['tongThanhToan'] ?? $gioMoi['tongTien']);

        $chiTiet = [];
        foreach (ChiTietGioHang::timTheoGioHang($maGioHang, $this->pdo) as $dong) {
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

    // [UC22 - Hình 30] GioHangCtrl::themVaoGioHang -> TonKho::tinhTonKhaDung -> GioHang::timTheoKhachHang / layGioHang / taoGioHangTam -> ChiTietGioHang::themHoacCongDon -> GioHang::tinhTongTamTinh
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
            return ['thanhCong' => false, 'thongBao' => "Số lượng chọn vượt quá tồn khả dụng ({$tonKhaDung} sản phẩm)", 'duLieu' => null, 'maHttp' => 400];
        }

        $donGiaCuoi = $this->tinhGiaBanSauKhuyenMai($maDienThoai, (float)$sp['giaBan']);
        ChiTietGioHang::themHoacCongDon($maGioHang, $maDienThoai, $soLuong, $donGiaCuoi, null, $this->pdo);
        GioHang::tinhTongTamTinh($maGioHang, $this->pdo);
        GioHang::tinhLaiTongTien($maGioHang, $this->pdo);

        return $this->layGioHang($maKhachHang, $maPhien);
    }

    // [UC23 - Hình 31] GioHangCtrl::capNhatSoLuong -> ChiTietGioHang::timTheoMa -> TonKho::tinhTonKhaDung -> ChiTietGioHang::capNhatSoLuong -> GioHang::tinhTongTamTinh
    public function capNhatSoLuong(?int $maKhachHang, string $maPhien, int $maChiTietHoacDienThoai, int $soLuong, bool $theoMaChiTiet = false): array
    {
        $gio = GioHang::timHoacTao($maKhachHang, $maPhien, $this->pdo);
        $maGioHang = (int)$gio['maGioHang'];

        $muc = null;
        if ($theoMaChiTiet) {
            $muc = ChiTietGioHang::timTheoMa($maChiTietHoacDienThoai, $this->pdo);
        } else {
            $muc = ChiTietGioHang::timMotMuc($maGioHang, $maChiTietHoacDienThoai, $this->pdo);
            if (!$muc) {
                $muc = ChiTietGioHang::timTheoMa($maChiTietHoacDienThoai, $this->pdo);
            }
        }

        if (!$muc || (int)$muc['maGioHang'] !== $maGioHang) {
            return ['thanhCong' => false, 'thongBao' => 'Sản phẩm không tồn tại trong giỏ hàng', 'duLieu' => null, 'maHttp' => 404];
        }

        $maChiTietGioHang = (int)$muc['maChiTietGioHang'];
        $maDienThoai = (int)$muc['maDienThoai'];

        if ($soLuong <= 0) {
            return $this->xoaKhoiGioHang($maKhachHang, $maPhien, $maChiTietGioHang, true);
        }

        $tonKhaDung = TonKho::tinhTonKhaDung($maDienThoai, $this->pdo);
        if ($soLuong > $tonKhaDung) {
            return ['thanhCong' => false, 'thongBao' => "Số lượng chọn vượt quá tồn khả dụng ({$tonKhaDung} sản phẩm)", 'duLieu' => null, 'maHttp' => 400];
        }

        ChiTietGioHang::capNhatSoLuong($maChiTietGioHang, $soLuong, $this->pdo);
        GioHang::tinhTongTamTinh($maGioHang, $this->pdo);
        GioHang::tinhLaiTongTien($maGioHang, $this->pdo);

        return $this->layGioHang($maKhachHang, $maPhien);
    }

    // [UC24 - Hình 32] GioHangCtrl::xoaKhoiGioHang -> ChiTietGioHang::xoa -> ChiTietGioHang::demTheoGioHang -> GioHang::tinhTongTamTinh
    public function xoaKhoiGioHang(?int $maKhachHang, string $maPhien, int $maChiTietHoacDienThoai, bool $theoMaChiTiet = false): array
    {
        $gio = GioHang::timHoacTao($maKhachHang, $maPhien, $this->pdo);
        $maGioHang = (int)$gio['maGioHang'];

        if ($theoMaChiTiet) {
            $muc = ChiTietGioHang::timTheoMa($maChiTietHoacDienThoai, $this->pdo);
            if ($muc && (int)$muc['maGioHang'] === $maGioHang) {
                ChiTietGioHang::xoa((int)$muc['maChiTietGioHang'], $this->pdo);
            }
        } else {
            $muc = ChiTietGioHang::timMotMuc($maGioHang, $maChiTietHoacDienThoai, $this->pdo);
            if ($muc) {
                ChiTietGioHang::xoa((int)$muc['maChiTietGioHang'], $this->pdo);
            } else {
                $theoCt = ChiTietGioHang::timTheoMa($maChiTietHoacDienThoai, $this->pdo);
                if ($theoCt && (int)$theoCt['maGioHang'] === $maGioHang) {
                    ChiTietGioHang::xoa((int)$theoCt['maChiTietGioHang'], $this->pdo);
                }
            }
        }

        ChiTietGioHang::demTheoGioHang($maGioHang, $this->pdo);
        GioHang::tinhTongTamTinh($maGioHang, $this->pdo);
        GioHang::tinhLaiTongTien($maGioHang, $this->pdo);

        return $this->layGioHang($maKhachHang, $maPhien);
    }
}
