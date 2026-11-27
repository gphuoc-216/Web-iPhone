<?php
// Controller BCE: QuanLyDienThoaiCtrl (UC4 - UC6, UC10)
// Khớp 1-1 Sequence Diagram Hình 12 - Hình 14, Hình 18 và Bảng 231 dòng 4 - 6, 8 (Báo cáo v4)

class QuanLyDienThoaiCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Chuẩn hóa dữ liệu trả về cho màn hình (không gọi thêm Entity)
    private function chuanHoaDongDienThoai(array $dong, ?ThongSoKyThuat $thongSo = null): array
    {
        $dt = DienThoai::tuMang($dong)->chuyenThanhMang();
        $dt['hinhAnh'] = chuanHoaDuongDanAnh($dt['hinhAnh']);
        $dt['tenDanhMuc'] = $dong['tenDanhMuc'] ?? '';
        $dt['thongSoKyThuat'] = $thongSo !== null ? $thongSo->chuyenThanhMang() : [
            'maThongSo'    => isset($dong['maThongSo']) ? (int)$dong['maThongSo'] : null,
            'manHinh'      => $dong['manHinh'] ?? null,
            'chip'         => $dong['chip'] ?? null,
            'ram'          => (int)($dong['ram'] ?? 0),
            'rom'          => (int)($dong['rom'] ?? 0),
            'camera'       => $dong['camera'] ?? null,
            'dungLuongPin' => (int)($dong['dungLuongPin'] ?? 0),
            'heDieuHanh'   => $dong['heDieuHanh'] ?? null,
        ];
        $tonThucTe = (int)($dong['tonThucTe'] ?? 0);
        $giuCho = (int)($dong['giuCho'] ?? 0);
        $dt['tonKho'] = ['tonThucTe' => $tonThucTe, 'giuCho' => $giuCho, 'tonKhaDung' => $tonThucTe - $giuCho];
        return $dt;
    }

    private function danhSach(array $nguon): array
    {
        return array_map(fn($d) => $this->chuanHoaDongDienThoai($d), $nguon);
    }

    // [UC4, UC5, UC6 - Hình 12] layDanhSachDienThoai() -> DienThoai::timDangKinhDoanh()
    public function layDanhSachDienThoai(): array
    {
        $danhSachDienThoai = $this->danhSach(DienThoai::timDangKinhDoanh($this->pdo));
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách điện thoại thành công', 'duLieu' => $danhSachDienThoai];
    }

    // [UC4 - Hình 12] layDanhSachDanhMuc() -> DanhMuc::timTatCa()
    public function layDanhSachDanhMuc(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách danh mục thành công', 'duLieu' => DanhMuc::timTatCa($this->pdo)];
    }

    // [UC10 - Hình 18] layDanhSachNgungKinhDoanh() -> DienThoai::timNgungKinhDoanh()
    public function layDanhSachNgungKinhDoanh(): array
    {
        $danhSachDienThoai = $this->danhSach(DienThoai::timNgungKinhDoanh($this->pdo));
        $thongBao = empty($danhSachDienThoai) ? 'Chưa có sản phẩm ngừng kinh doanh' : 'Lấy danh sách điện thoại ngừng kinh doanh thành công';
        return ['thanhCong' => true, 'thongBao' => $thongBao, 'duLieu' => $danhSachDienThoai];
    }

    // [UC5 - Hình 13] layThongTinDienThoai(maDienThoai) -> DienThoai::timTheoMa -> ThongSoKyThuat::timTheoDienThoai -> DanhMuc::timTatCa
    public function layThongTinDienThoai(int $maDienThoai): array
    {
        $dienThoai = DienThoai::timTheoMa($maDienThoai, $this->pdo);
        if (!$dienThoai) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy thông tin điện thoại', 'duLieu' => null, 'maHttp' => 404];
        }
        $thongSo = ThongSoKyThuat::timTheoDienThoai($maDienThoai, $this->pdo);
        $danhSachDanhMuc = DanhMuc::timTatCa($this->pdo);

        $duLieu = $this->chuanHoaDongDienThoai($dienThoai, $thongSo);
        $duLieu['danhSachDanhMuc'] = $danhSachDanhMuc;
        return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin điện thoại thành công', 'duLieu' => $duLieu];
    }

    // [UC4 - Hình 12] luuDienThoai(ten, maDanhMuc, hang, moTa, thongSo, hinhAnh)
    //   -> DanhMuc::kiemTraTonTai(maDanhMuc) -> DienThoai::kiemTraHopLe(ten, thongSo) -> DienThoai::kiemTraTrungTen(ten, 0)
    //   -> DienThoai::luu(ten, maDanhMuc, hang, moTa, hinhAnh) -> ThongSoKyThuat::luu(maDienThoai, thongSo) -> DienThoai::timDangKinhDoanh()
    public function luuDienThoai(string $ten, int $maDanhMuc, string $hang, ?string $moTa, array $thongSo, ?string $hinhAnh): array
    {
        $ten = trim($ten);
        $hang = trim($hang);
        $ketQuaDanhMuc = DanhMuc::kiemTraTonTai($maDanhMuc, $this->pdo);
        $ketQuaKiemTra = DienThoai::kiemTraHopLe($ten, $thongSo);
        $daTrungTen = DienThoai::kiemTraTrungTen($ten, 0, $this->pdo);

        if (!$ketQuaDanhMuc || !$ketQuaKiemTra || $hang === '') {
            return ['thanhCong' => false, 'thongBao' => 'Dữ liệu không hợp lệ (thiếu tên, chưa chọn danh mục hoặc thông số sai định dạng)', 'duLieu' => null, 'maHttp' => 400];
        }
        if ($daTrungTen) {
            return ['thanhCong' => false, 'thongBao' => 'Tên điện thoại đã tồn tại', 'duLieu' => null, 'maHttp' => 409];
        }

        try {
            batDauGiaoDich($this->pdo);
            $maDienThoai = DienThoai::luu($ten, $maDanhMuc, $hang, $moTa, $hinhAnh, $this->pdo);
            ThongSoKyThuat::luu($maDienThoai, $thongSo, $this->pdo);
            xacNhanGiaoDich($this->pdo);
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('luuDienThoai: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau', 'duLieu' => null, 'maHttp' => 500];
        }

        $danhSachDienThoai = $this->danhSach(DienThoai::timDangKinhDoanh($this->pdo));
        return [
            'thanhCong' => true,
            'thongBao'  => 'Thêm điện thoại thành công',
            'duLieu'    => ['maDienThoai' => $maDienThoai, 'danhSachDienThoai' => $danhSachDienThoai],
            'maHttp'    => 201,
        ];
    }

    // [UC5 - Hình 13] capNhatDienThoai(maDienThoai, ten, maDanhMuc, hang, moTa, thongSo, hinhAnh)
    //   -> DanhMuc::kiemTraTonTai(maDanhMuc) -> DienThoai::kiemTraHopLe(ten, thongSo) -> DienThoai::kiemTraTrungTen(ten, maDienThoai)
    //   -> DienThoai::capNhat(maDienThoai, ten, maDanhMuc, hang, moTa, hinhAnh) -> ThongSoKyThuat::capNhat(maDienThoai, thongSo) -> DienThoai::timDangKinhDoanh()
    public function capNhatDienThoai(int $maDienThoai, string $ten, int $maDanhMuc, string $hang, ?string $moTa, array $thongSo, ?string $hinhAnh): array
    {
        $ten = trim($ten);
        $hang = trim($hang);
        $ketQuaDanhMuc = DanhMuc::kiemTraTonTai($maDanhMuc, $this->pdo);
        $ketQuaKiemTra = DienThoai::kiemTraHopLe($ten, $thongSo);
        $daTrungTen = DienThoai::kiemTraTrungTen($ten, $maDienThoai, $this->pdo);

        if (!$ketQuaDanhMuc || !$ketQuaKiemTra || $hang === '') {
            return ['thanhCong' => false, 'thongBao' => 'Dữ liệu không hợp lệ (thiếu tên, chưa chọn danh mục hoặc thông số sai định dạng)', 'duLieu' => null, 'maHttp' => 400];
        }
        if ($daTrungTen) {
            return ['thanhCong' => false, 'thongBao' => 'Tên điện thoại đã tồn tại', 'duLieu' => null, 'maHttp' => 409];
        }

        try {
            batDauGiaoDich($this->pdo);
            DienThoai::capNhat($maDienThoai, $ten, $maDanhMuc, $hang, $moTa, $hinhAnh, $this->pdo);
            ThongSoKyThuat::capNhat($maDienThoai, $thongSo, $this->pdo);
            xacNhanGiaoDich($this->pdo);
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('capNhatDienThoai: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau', 'duLieu' => null, 'maHttp' => 500];
        }

        $danhSachDienThoai = $this->danhSach(DienThoai::timDangKinhDoanh($this->pdo));
        return [
            'thanhCong' => true,
            'thongBao'  => 'Cập nhật thông tin điện thoại thành công',
            'duLieu'    => ['maDienThoai' => $maDienThoai, 'danhSachDienThoai' => $danhSachDienThoai],
        ];
    }

    // [UC6 - Hình 14] xoaDienThoai(maDienThoai)
    //   -> DonHang::demDonChuaHoanTatTheoDienThoai(maDienThoai) -> DienThoai::kiemTraPhatSinhGiaoDich(maDienThoai)
    //   -> alt: loiDangCoDonHang | DienThoai::chuyenNgungKinhDoanh(maDienThoai) | DienThoai::xoa(maDienThoai) -> DienThoai::timDangKinhDoanh()
    public function xoaDienThoai(int $maDienThoai): array
    {
        $soDonChuaHoanTat = DonHang::demDonChuaHoanTatTheoDienThoai($maDienThoai, $this->pdo);
        $coGiaoDich = DienThoai::kiemTraPhatSinhGiaoDich($maDienThoai, $this->pdo);

        if ($soDonChuaHoanTat > 0) {
            return [
                'thanhCong' => false,
                'thongBao'  => "Không thể xóa: điện thoại đang nằm trong {$soDonChuaHoanTat} đơn hàng chưa hoàn tất",
                'duLieu'    => ['soDonChuaHoanTat' => $soDonChuaHoanTat],
                'maHttp'    => 409,
            ];
        }

        if ($coGiaoDich) {
            DienThoai::chuyenNgungKinhDoanh($maDienThoai, $this->pdo);
            $danhSachDienThoai = $this->danhSach(DienThoai::timDangKinhDoanh($this->pdo));
            return [
                'thanhCong' => true,
                'thongBao'  => 'Điện thoại đã phát sinh giao dịch nên được chuyển sang trạng thái Ngừng kinh doanh',
                'duLieu'    => ['maDienThoai' => $maDienThoai, 'kieuXoa' => 'xoaMem', 'danhSachDienThoai' => $danhSachDienThoai],
            ];
        }

        try {
            batDauGiaoDich($this->pdo);
            DienThoai::xoa($maDienThoai, $this->pdo);
            xacNhanGiaoDich($this->pdo);
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('xoaDienThoai: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa điện thoại. Vui lòng thử lại sau', 'duLieu' => null, 'maHttp' => 500];
        }

        $danhSachDienThoai = $this->danhSach(DienThoai::timDangKinhDoanh($this->pdo));
        return [
            'thanhCong' => true,
            'thongBao'  => 'Xóa thành công',
            'duLieu'    => ['maDienThoai' => $maDienThoai, 'kieuXoa' => 'xoaCung', 'danhSachDienThoai' => $danhSachDienThoai],
        ];
    }

    // [UC10 - Hình 18] khoiPhucDienThoai(maDienThoai) -> DienThoai::timTheoMa(maDienThoai) -> DanhMuc::kiemTraTonTai(maDanhMuc) -> yeuCauXacNhan
    public function khoiPhucDienThoai(int $maDienThoai): array
    {
        $dienThoai = DienThoai::timTheoMa($maDienThoai, $this->pdo);
        if (!$dienThoai) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy điện thoại cần khôi phục', 'duLieu' => null, 'maHttp' => 404];
        }

        $ketQuaDanhMuc = DanhMuc::kiemTraTonTai((int)$dienThoai['maDanhMuc'], $this->pdo);
        if (!$ketQuaDanhMuc) {
            return ['thanhCong' => false, 'thongBao' => 'Danh mục của sản phẩm đã bị xóa. Vui lòng gán sản phẩm vào danh mục khác trước khi khôi phục', 'duLieu' => null, 'maHttp' => 400];
        }

        return ['thanhCong' => true, 'thongBao' => 'Vui lòng xác nhận khôi phục kinh doanh', 'duLieu' => ['maDienThoai' => $maDienThoai, 'yeuCauXacNhan' => true]];
    }

    // [UC10 - Hình 18] xacNhanKhoiPhuc(maDienThoai) -> DienThoai::capNhatTrangThai(maDienThoai, "Đang kinh doanh") -> DienThoai::timNgungKinhDoanh()
    public function xacNhanKhoiPhuc(int $maDienThoai): array
    {
        DienThoai::capNhatTrangThai($maDienThoai, 'Đang kinh doanh', $this->pdo);
        $danhSachDienThoai = $this->danhSach(DienThoai::timNgungKinhDoanh($this->pdo));
        return [
            'thanhCong' => true,
            'thongBao'  => 'Khôi phục kinh doanh thành công',
            'duLieu'    => ['maDienThoai' => $maDienThoai, 'trangThai' => 'Đang kinh doanh', 'danhSachNgungKinhDoanh' => $danhSachDienThoai],
        ];
    }
}
