<?php
// Controller BCE: TimKiemSanPhamCtrl (UC7 - UC9) — Phụ lục B.1 Lan03

class TimKiemSanPhamCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function chuyenSanPhamCongKhai(array $dong, array $banDoKm): array
    {
        $sp = DienThoai::tuMang($dong)->chuyenThanhMang();
        // Không lộ giaVonBinhQuan ra API công khai
        unset($sp['giaVonBinhQuan']);

        $maDt = (int)$sp['maDienThoai'];
        $km = $banDoKm[$maDt] ?? null;
        $mucUuDai = $km ? (float)$km['mucUuDai'] : 0.0;
        $giaBanCuoi = $mucUuDai > 0 ? round($sp['giaBan'] * (1 - $mucUuDai / 100), 2) : $sp['giaBan'];
        $tonKhaDung = (int)($dong['tonKhaDung'] ?? 0);

        $sp['hinhAnh'] = chuanHoaDuongDanAnh($sp['hinhAnh']);
        $sp['tenDanhMuc'] = $dong['tenDanhMuc'] ?? '';
        $sp['giaGoc'] = $sp['giaBan'];
        $sp['mucUuDaiKhuyenMai'] = $mucUuDai;
        $sp['tenKhuyenMai'] = $km ? $km['tenKhuyenMai'] : null;
        $sp['giaBanCuoi'] = $giaBanCuoi;
        $sp['tonKhaDung'] = $tonKhaDung;
        $sp['trangThaiTonKho'] = $tonKhaDung > 0 ? 'Còn hàng' : 'Hết hàng';
        $sp['thongSoKyThuat'] = [
            'manHinh'      => $dong['manHinh'] ?? null,
            'chip'         => $dong['chip'] ?? null,
            'ram'          => (int)($dong['ram'] ?? 0),
            'rom'          => (int)($dong['rom'] ?? 0),
            'camera'       => $dong['camera'] ?? null,
            'dungLuongPin' => (int)($dong['dungLuongPin'] ?? 0),
            'heDieuHanh'   => $dong['heDieuHanh'] ?? null,
        ];
        return $sp;
    }

    public function layDanhSachSanPham(): array
    {
        return $this->locSanPham([]);
    }

    // UC7: timKiemSanPham(tuKhoa)
    public function timKiemSanPham(string $tuKhoa): array
    {
        return $this->locSanPham(['tuKhoa' => $tuKhoa]);
    }

    // UC8: locSanPham(hang, giaTu, giaDen, ram, rom, dungLuongPin)
    public function locSanPham(array $boLoc = []): array
    {
        $giaTu = (isset($boLoc['giaTu']) && $boLoc['giaTu'] !== '') ? (float)$boLoc['giaTu'] : null;
        $giaDen = (isset($boLoc['giaDen']) && $boLoc['giaDen'] !== '') ? (float)$boLoc['giaDen'] : null;

        if (!DienThoai::kiemTraKhoangGia($giaTu, $giaDen)) {
            return ['thanhCong' => false, 'thongBao' => 'Khoảng giá không hợp lệ (Giá từ phải nhỏ hơn hoặc bằng Giá đến và không âm)', 'duLieu' => null, 'maHttp' => 400];
        }

        $banDoKm = KhuyenMai::timKhuyenMaiDangApDung(null, $this->pdo);
        $danhSach = DienThoai::timTheoDieuKienLoc($boLoc, $this->pdo);
        $ketQua = array_map(fn($d) => $this->chuyenSanPhamCongKhai($d, $banDoKm), $danhSach);

        return ['thanhCong' => true, 'thongBao' => 'Tìm kiếm sản phẩm thành công', 'duLieu' => $ketQua];
    }

    // UC9: layChiTietSanPham(maDienThoai)
    public function layChiTietSanPham(int $maDienThoai): array
    {
        $dong = DienThoai::timTheoMa($maDienThoai, $this->pdo);
        if (!$dong || $dong['trangThai'] !== 'Đang kinh doanh') {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy sản phẩm điện thoại', 'duLieu' => null, 'maHttp' => 404];
        }

        $khuyenMaiApDung = KhuyenMai::timKhuyenMaiDangApDung($maDienThoai, $this->pdo);
        $mucUuDaiCaoNhat = 0.0;
        foreach ($khuyenMaiApDung as $km) {
            if ((float)$km['mucUuDai'] > $mucUuDaiCaoNhat) {
                $mucUuDaiCaoNhat = (float)$km['mucUuDai'];
            }
        }

        $tonKhaDung = TonKho::tinhTonKhaDung($maDienThoai, $this->pdo);
        $chiTiet = DienThoai::tuMang($dong)->chuyenThanhMang();
        unset($chiTiet['giaVonBinhQuan']);

        $chiTiet['hinhAnh'] = chuanHoaDuongDanAnh($chiTiet['hinhAnh']);
        $chiTiet['tenDanhMuc'] = $dong['tenDanhMuc'] ?? '';
        $chiTiet['giaGoc'] = $chiTiet['giaBan'];
        $chiTiet['mucUuDaiKhuyenMai'] = $mucUuDaiCaoNhat;
        $chiTiet['giaBanCuoi'] = $mucUuDaiCaoNhat > 0 ? round($chiTiet['giaBan'] * (1 - $mucUuDaiCaoNhat / 100), 2) : $chiTiet['giaBan'];
        $chiTiet['trangThaiTonKho'] = $tonKhaDung > 0 ? 'Còn hàng' : 'Hết hàng';
        $tsObj = ThongSoKyThuat::timTheoDienThoai($maDienThoai, $this->pdo);
        $chiTiet['thongSoKyThuat'] = $tsObj ? $tsObj->chuyenThanhMang() : [];
        $chiTiet['tonKho'] = [
            'tonKhaDung' => $tonKhaDung,
        ];
        $chiTiet['khuyenMaiApDung'] = $khuyenMaiApDung;

        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết điện thoại thành công', 'duLieu' => $chiTiet];
    }
}
