<?php
// Controller BCE: TimKiemSanPhamCtrl (UC7 - UC9)
// Đồng bộ 1-1 Báo cáo v4: Hình 3, Bảng 16 - Bảng 18, Sequence Hình 15 - Hình 17, Bảng 231 dòng 7

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
        unset($sp['giaVonBinhQuan']);

        $maDt = (int)$sp['maDienThoai'];
        $km = $banDoKm[$maDt] ?? null;
        $mucUuDai = $km ? (float)$km['mucUuDai'] : 0.0;
        $giaBanCuoi = $mucUuDai > 0 ? round($sp['giaBan'] * (1 - $mucUuDai / 100), 2) : $sp['giaBan'];
        $tonKhaDung = isset($dong['tonKhaDung'])
            ? (int)$dong['tonKhaDung']
            : ((int)($dong['tonThucTe'] ?? 0) - (int)($dong['giuCho'] ?? 0));

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

    // [UC7, UC8, UC9 - Hình 15, Hình 16, Hình 17] TimKiemSanPhamCtrl::layDanhSachSanPham -> DienThoai::timDangKinhDoanh
    public function layDanhSachSanPham(): array
    {
        $banDoKm = KhuyenMai::timKhuyenMaiDangApDung(null, $this->pdo);
        $danhSach = DienThoai::timDangKinhDoanh($this->pdo);
        $ketQua = array_map(fn($d) => $this->chuyenSanPhamCongKhai($d, $banDoKm), $danhSach);
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách sản phẩm thành công', 'duLieu' => $ketQua];
    }

    // [UC7 - Hình 15] TimKiemSanPhamCtrl::timKiemSanPham -> DienThoai::timTheoTuKhoa
    public function timKiemSanPham(string $tuKhoa): array
    {
        $banDoKm = KhuyenMai::timKhuyenMaiDangApDung(null, $this->pdo);
        $danhSach = DienThoai::timTheoTuKhoa(trim($tuKhoa), $this->pdo);
        $ketQua = array_map(fn($d) => $this->chuyenSanPhamCongKhai($d, $banDoKm), $danhSach);
        $thongBao = empty($ketQua) ? 'Không tìm thấy sản phẩm phù hợp' : 'Tìm kiếm sản phẩm thành công';
        return ['thanhCong' => true, 'thongBao' => $thongBao, 'duLieu' => $ketQua];
    }

    // [UC8 - Hình 16] TimKiemSanPhamCtrl::locSanPham -> DienThoai::kiemTraKhoangGia -> DienThoai::timTheoDieuKienLoc -> ThongSoKyThuat::locTheoThongSo
    public function locSanPham($hang = null, $giaTu = null, $giaDen = null, $ram = null, $rom = null, $dungLuongPin = null): array
    {
        $boLocMoRong = [];
        if (is_array($hang)) {
            $boLocMoRong = $hang;
            $hang = $boLocMoRong['hang'] ?? null;
            $giaTu = $boLocMoRong['giaTu'] ?? null;
            $giaDen = $boLocMoRong['giaDen'] ?? null;
            $ram = $boLocMoRong['ram'] ?? null;
            $rom = $boLocMoRong['rom'] ?? null;
            $dungLuongPin = $boLocMoRong['dungLuongPin'] ?? $boLocMoRong['pinTu'] ?? null;
        }

        $giaTuSo = ($giaTu !== null && $giaTu !== '') ? (float)$giaTu : null;
        $giaDenSo = ($giaDen !== null && $giaDen !== '') ? (float)$giaDen : null;

        // 1. DienThoai::kiemTraKhoangGia(giaTu, giaDen)
        if (!DienThoai::kiemTraKhoangGia($giaTuSo, $giaDenSo)) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Khoảng giá không hợp lệ. Giá thấp nhất không được lớn hơn giá cao nhất',
                'duLieu'    => null,
                'maHttp'    => 400,
            ];
        }

        // 2. DienThoai::timTheoDieuKienLoc(hang, giaTu, giaDen)
        if (!empty($boLocMoRong['tuKhoa']) || !empty($boLocMoRong['maDanhMuc']) || !empty($boLocMoRong['sapXep'])) {
            $boLocMoRong['hang'] = $hang;
            $boLocMoRong['giaTu'] = $giaTuSo;
            $boLocMoRong['giaDen'] = $giaDenSo;
            $danhSach = DienThoai::timTheoDieuKienLoc($boLocMoRong, $this->pdo);
        } else {
            $danhSach = DienThoai::timTheoDieuKienLoc($hang !== null ? (string)$hang : null, $giaTuSo, $giaDenSo, $this->pdo);
        }

        // 3. ThongSoKyThuat::locTheoThongSo(ram, rom, dungLuongPin)
        $ramSo = ($ram !== null && $ram !== '' && (int)$ram > 0) ? (int)$ram : null;
        $romSo = ($rom !== null && $rom !== '' && (int)$rom > 0) ? (int)$rom : null;
        $pinSo = ($dungLuongPin !== null && $dungLuongPin !== '' && (int)$dungLuongPin > 0) ? (int)$dungLuongPin : null;

        if ($ramSo !== null || $romSo !== null || $pinSo !== null) {
            $danhSachMaDienThoai = ThongSoKyThuat::locTheoThongSo($ramSo, $romSo, $pinSo, $this->pdo);
            $tapMa = array_flip($danhSachMaDienThoai);
            $danhSach = array_values(array_filter($danhSach, fn($d) => isset($tapMa[(int)$d['maDienThoai']])));
        }

        $banDoKm = KhuyenMai::timKhuyenMaiDangApDung(null, $this->pdo);
        $ketQua = array_map(fn($d) => $this->chuyenSanPhamCongKhai($d, $banDoKm), $danhSach);
        $thongBao = empty($ketQua) ? 'Không tìm thấy sản phẩm phù hợp' : 'Lọc sản phẩm thành công';

        return ['thanhCong' => true, 'thongBao' => $thongBao, 'duLieu' => $ketQua];
    }

    // [UC9 - Hình 17] TimKiemSanPhamCtrl::layChiTietSanPham -> DienThoai::timTheoMa -> ThongSoKyThuat::timTheoDienThoai -> KhuyenMai::timKhuyenMaiDangApDung -> TonKho::tinhTonKhaDung
    public function layChiTietSanPham(int $maDienThoai): array
    {
        $dong = DienThoai::timTheoMa($maDienThoai, $this->pdo);
        if (!$dong || $dong['trangThai'] !== 'Đang kinh doanh') {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy sản phẩm điện thoại', 'duLieu' => null, 'maHttp' => 404];
        }

        $tsObj = ThongSoKyThuat::timTheoDienThoai($maDienThoai, $this->pdo);
        $khuyenMaiApDung = KhuyenMai::timKhuyenMaiDangApDung($maDienThoai, $this->pdo);
        $tonKhaDung = TonKho::tinhTonKhaDung($maDienThoai, $this->pdo);

        $mucUuDaiCaoNhat = 0.0;
        foreach ($khuyenMaiApDung as $km) {
            if ((float)$km['mucUuDai'] > $mucUuDaiCaoNhat) {
                $mucUuDaiCaoNhat = (float)$km['mucUuDai'];
            }
        }

        $chiTiet = DienThoai::tuMang($dong)->chuyenThanhMang();
        unset($chiTiet['giaVonBinhQuan']);

        $chiTiet['hinhAnh'] = chuanHoaDuongDanAnh($chiTiet['hinhAnh']);
        $chiTiet['tenDanhMuc'] = $dong['tenDanhMuc'] ?? '';
        $chiTiet['giaGoc'] = $chiTiet['giaBan'];
        $chiTiet['mucUuDaiKhuyenMai'] = $mucUuDaiCaoNhat;
        $chiTiet['giaBanCuoi'] = $mucUuDaiCaoNhat > 0 ? round($chiTiet['giaBan'] * (1 - $mucUuDaiCaoNhat / 100), 2) : $chiTiet['giaBan'];
        $chiTiet['trangThaiTonKho'] = $tonKhaDung > 0 ? 'Còn hàng' : 'Hết hàng';
        $chiTiet['thongSoKyThuat'] = $tsObj ? $tsObj->chuyenThanhMang() : [];
        $chiTiet['tonKho'] = [
            'tonKhaDung' => $tonKhaDung,
        ];
        $chiTiet['khuyenMaiApDung'] = $khuyenMaiApDung;

        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết sản phẩm thành công', 'duLieu' => $chiTiet];
    }
}
