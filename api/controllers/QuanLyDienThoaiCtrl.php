<?php
// Controller BCE: QuanLyDienThoaiCtrl (UC4 - UC6, UC10) — Phụ lục B.1 Lan03

class QuanLyDienThoaiCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function chuanHoaDongDienThoai(array $dong): array
    {
        $dt = DienThoai::tuMang($dong)->chuyenThanhMang();
        $dt['hinhAnh'] = chuanHoaDuongDanAnh($dt['hinhAnh']);
        $dt['tenDanhMuc'] = $dong['tenDanhMuc'] ?? '';
        $dt['thongSoKyThuat'] = [
            'maThongSo'    => isset($dong['maThongSo']) ? (int)$dong['maThongSo'] : null,
            'manHinh'      => $dong['manHinh'] ?? null,
            'chip'         => $dong['chip'] ?? null,
            'ram'          => (int)($dong['ram'] ?? 8),
            'rom'          => (int)($dong['rom'] ?? 128),
            'camera'       => $dong['camera'] ?? null,
            'dungLuongPin' => (int)($dong['dungLuongPin'] ?? 4500),
            'heDieuHanh'   => $dong['heDieuHanh'] ?? null,
        ];
        $dt['tonKho'] = [
            'tonThucTe'  => (int)($dong['tonThucTe'] ?? 0),
            'giuCho'     => (int)($dong['giuCho'] ?? 0),
            'tonKhaDung' => (int)($dong['tonKhaDung'] ?? 0),
        ];
        return $dt;
    }

    public function layDanhSachDanhMuc(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách danh mục thành công', 'duLieu' => DanhMuc::timTatCa($this->pdo)];
    }

    public function layDanhSachDienThoai(?string $trangThai = null): array
    {
        $danhSach = array_map([$this, 'chuanHoaDongDienThoai'], DienThoai::timTatCa($trangThai, $this->pdo));
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách điện thoại thành công', 'duLieu' => $danhSach];
    }

    public function layDanhSachNgungKinhDoanh(): array
    {
        $danhSach = array_map([$this, 'chuanHoaDongDienThoai'], DienThoai::timNgungKinhDoanh($this->pdo));
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách điện thoại ngừng kinh doanh thành công', 'duLieu' => $danhSach];
    }

    public function layThongTinDienThoai(int $maDienThoai): array
    {
        $dong = DienThoai::timTheoMa($maDienThoai, $this->pdo);
        if (!$dong) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy thông tin điện thoại', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin điện thoại thành công', 'duLieu' => $this->chuanHoaDongDienThoai($dong)];
    }

    // UC4: luuDienThoai(ten, maDanhMuc, moTa, thongSo, hinhAnh)
    public function luuDienThoai(array $duLieu, ?array $tepAnh = null): array
    {
        $ten = trim((string)($duLieu['ten'] ?? ''));
        $maDanhMuc = (int)($duLieu['maDanhMuc'] ?? 0);
        $hang = trim((string)($duLieu['hang'] ?? ''));
        $moTa = isset($duLieu['moTa']) ? trim((string)$duLieu['moTa']) : null;
        $giaVonBinhQuan = (float)($duLieu['giaVonBinhQuan'] ?? 0);
        $giaBan = (float)($duLieu['giaBan'] ?? 0);
        $thongSo = is_array($duLieu['thongSoKyThuat'] ?? null) ? $duLieu['thongSoKyThuat'] : $duLieu;

        if (!DienThoai::kiemTraHopLe($ten, $thongSo) || $maDanhMuc <= 0 || $hang === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập đầy đủ tên, danh mục, hãng và thông số kỹ thuật hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        if (!DanhMuc::kiemTraTonTai($maDanhMuc, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Danh mục được chọn không tồn tại', 'duLieu' => null, 'maHttp' => 404];
        }

        $duongDanAnh = $this->luuAnhSanPham($tepAnh) ?? trim((string)($duLieu['hinhAnh'] ?? 'storage/shop-assets/img/iphone17.webp'));

        try {
            batDauGiaoDich($this->pdo);
            $maDienThoai = DienThoai::luu($ten, $maDanhMuc, $hang, $moTa, $duongDanAnh, $giaVonBinhQuan, $giaBan, 'Đang kinh doanh', $this->pdo);
            ThongSoKyThuat::luu($maDienThoai, $thongSo, $this->pdo);
            TonKho::khoiTaoTonKho($maDienThoai, 1, $this->pdo);
            xacNhanGiaoDich($this->pdo);

            return [
                'thanhCong' => true,
                'thongBao'  => 'Thêm điện thoại mới thành công',
                'duLieu'    => ['maDienThoai' => $maDienThoai, 'hinhAnh' => chuanHoaDuongDanAnh($duongDanAnh)],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('luuDienThoai: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể lưu điện thoại mới. Vui lòng kiểm tra lại dữ liệu', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC5: capNhatDienThoai(maDienThoai, ten, maDanhMuc, moTa, thongSo, hinhAnh)
    public function capNhatDienThoai(int $maDienThoai, array $duLieu, ?array $tepAnh = null): array
    {
        if ($maDienThoai <= 0 || !DienThoai::kiemTraTonTai($maDienThoai, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy điện thoại cần cập nhật', 'duLieu' => null, 'maHttp' => 404];
        }
        if (!empty($duLieu['maDanhMuc']) && !DanhMuc::kiemTraTonTai((int)$duLieu['maDanhMuc'], $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Danh mục được chọn không tồn tại', 'duLieu' => null, 'maHttp' => 404];
        }

        $duongDanAnhMoi = $this->luuAnhSanPham($tepAnh);
        if ($duongDanAnhMoi !== null) {
            $duLieu['hinhAnh'] = $duongDanAnhMoi;
        }
        $thongSo = is_array($duLieu['thongSoKyThuat'] ?? null)
            ? $duLieu['thongSoKyThuat']
            : array_intersect_key($duLieu, array_flip(['manHinh', 'chip', 'ram', 'rom', 'camera', 'dungLuongPin', 'heDieuHanh']));

        try {
            batDauGiaoDich($this->pdo);
            DienThoai::capNhat($maDienThoai, $duLieu, $this->pdo);
            if (!empty($thongSo)) {
                ThongSoKyThuat::capNhat($maDienThoai, $thongSo, $this->pdo);
            }
            xacNhanGiaoDich($this->pdo);

            return ['thanhCong' => true, 'thongBao' => 'Cập nhật thông tin điện thoại thành công', 'duLieu' => ['maDienThoai' => $maDienThoai]];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('capNhatDienThoai: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể cập nhật thông tin điện thoại', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function capNhatTrangThaiDienThoai(int $maDienThoai, string $trangThai): array
    {
        if (!in_array($trangThai, ['Đang kinh doanh', 'Ngừng kinh doanh'], true)) {
            return ['thanhCong' => false, 'thongBao' => 'Trạng thái kinh doanh không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        DienThoai::capNhatTrangThai($maDienThoai, $trangThai, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật trạng thái kinh doanh thành công', 'duLieu' => ['maDienThoai' => $maDienThoai, 'trangThai' => $trangThai]];
    }

    // UC6: xoaDienThoai(maDienThoai)
    public function xoaDienThoai(int $maDienThoai): array
    {
        if ($maDienThoai <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Mã điện thoại không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        if (DonHang::demDonChuaHoanTatTheoDienThoai($maDienThoai, $this->pdo) > 0) {
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa điện thoại đang nằm trong đơn hàng chưa hoàn tất', 'duLieu' => null, 'maHttp' => 409];
        }
        if (DienThoai::kiemTraPhatSinhGiaoDich($maDienThoai, $this->pdo)) {
            DienThoai::chuyenNgungKinhDoanh($maDienThoai, $this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Sản phẩm đã phát sinh giao dịch nên được xóa mềm (chuyển sang Ngừng kinh doanh)',
                'duLieu'    => ['maDienThoai' => $maDienThoai, 'trangThai' => 'Ngừng kinh doanh', 'kieuXoa' => 'xoaMem'],
            ];
        }

        try {
            batDauGiaoDich($this->pdo);
            TonKho::xoaTheoDienThoai($maDienThoai, $this->pdo);
            ThongSoKyThuat::xoaTheoDienThoai($maDienThoai, $this->pdo);
            DienThoai::xoa($maDienThoai, $this->pdo);
            xacNhanGiaoDich($this->pdo);
            return ['thanhCong' => true, 'thongBao' => 'Đã xóa cứng điện thoại khỏi hệ thống', 'duLieu' => ['maDienThoai' => $maDienThoai, 'kieuXoa' => 'xoaCung']];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            DienThoai::chuyenNgungKinhDoanh($maDienThoai, $this->pdo);
            return ['thanhCong' => true, 'thongBao' => 'Sản phẩm có dữ liệu liên kết nên được chuyển sang Ngừng kinh doanh', 'duLieu' => ['maDienThoai' => $maDienThoai, 'trangThai' => 'Ngừng kinh doanh']];
        }
    }

    // UC10: khoiPhucDienThoai(maDienThoai)
    public function khoiPhucDienThoai(int $maDienThoai): array
    {
        $dong = DienThoai::timTheoMa($maDienThoai, $this->pdo);
        if (!$dong) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy điện thoại cần khôi phục', 'duLieu' => null, 'maHttp' => 404];
        }
        if (!DanhMuc::kiemTraTonTai((int)$dong['maDanhMuc'], $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Không thể khôi phục vì danh mục của sản phẩm không còn tồn tại', 'duLieu' => null, 'maHttp' => 400];
        }
        DienThoai::capNhatTrangThai($maDienThoai, 'Đang kinh doanh', $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Khôi phục kinh doanh điện thoại thành công', 'duLieu' => ['maDienThoai' => $maDienThoai, 'trangThai' => 'Đang kinh doanh']];
    }

    // Bỏ 'svg' để chống Stored XSS
    public function luuAnhSanPham(?array $tepAnh): ?string
    {
        if (!$tepAnh || ($tepAnh['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $duoiHopLe = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $duoiTep = strtolower(pathinfo($tepAnh['name'], PATHINFO_EXTENSION));
        if (!in_array($duoiTep, $duoiHopLe, true)) {
            return null;
        }
        if (@getimagesize($tepAnh['tmp_name']) === false) {
            return null;
        }

        $thuMucUpload = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($thuMucUpload)) {
            @mkdir($thuMucUpload, 0775, true);
        }

        $tenGoc = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($tepAnh['name'], PATHINFO_FILENAME));
        $tenMoi = (trim($tenGoc, '_') ?: 'dienthoai') . '_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $duoiTep;
        $dichDen = $thuMucUpload . DIRECTORY_SEPARATOR . $tenMoi;

        if (move_uploaded_file($tepAnh['tmp_name'], $dichDen)) {
            return 'uploads/' . $tenMoi;
        }
        return null;
    }
}
