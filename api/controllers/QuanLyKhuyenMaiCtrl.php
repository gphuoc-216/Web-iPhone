<?php
// Controller BCE: QuanLyKhuyenMaiCtrl (UC57 - UC59) — Phụ lục B.1 Lan03

class QuanLyKhuyenMaiCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDanhSachKhuyenMai(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách chương trình khuyến mãi thành công', 'duLieu' => KhuyenMai::timTatCa($this->pdo)];
    }

    public function layThongTinKhuyenMai(int $maKhuyenMai): array
    {
        foreach (KhuyenMai::timTatCa($this->pdo) as $km) {
            if ((int)$km['maKhuyenMai'] === $maKhuyenMai) {
                return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin khuyến mãi thành công', 'duLieu' => $km];
            }
        }
        return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy chương trình khuyến mãi', 'duLieu' => null, 'maHttp' => 404];
    }

    // UC57: luuKhuyenMai(...)
    public function luuKhuyenMai(array $duLieu): array
    {
        $ten = trim((string)($duLieu['ten'] ?? ''));
        $noiDung = isset($duLieu['noiDung']) ? trim((string)$duLieu['noiDung']) : null;
        $mucUuDai = (float)($duLieu['mucUuDai'] ?? 0);
        $ngayBatDau = trim((string)($duLieu['ngayBatDau'] ?? date('Y-m-d')));
        $ngayKetThuc = trim((string)($duLieu['ngayKetThuc'] ?? date('Y-m-d', strtotime('+30 days'))));
        $khungGio = trim((string)($duLieu['khungGio'] ?? '00:00-23:59'));
        $trangThai = trim((string)($duLieu['trangThai'] ?? 'Đang áp dụng'));
        $danhSachMaDt = is_array($duLieu['danhSachMaDienThoai'] ?? null) ? $duLieu['danhSachMaDienThoai'] : [];

        if (!KhuyenMai::kiemTraHopLe($ten, $mucUuDai, $ngayBatDau, $ngayKetThuc)) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin khuyến mãi không hợp lệ (ưu đãi 1-100%, ngày kết thúc >= ngày bắt đầu)', 'duLieu' => null, 'maHttp' => 400];
        }

        if ($loiXungDot = KhuyenMai::kiemTraXungDotThoiGian($danhSachMaDt, $ngayBatDau, $ngayKetThuc, null, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => $loiXungDot, 'duLieu' => null, 'maHttp' => 409];
        }

        $dk = is_array($duLieu['dieuKienApDung'] ?? null) ? $duLieu['dieuKienApDung'] : $duLieu;

        try {
            batDauGiaoDich($this->pdo);
            $maKhuyenMai = KhuyenMai::luu($ten, $noiDung, $mucUuDai, $ngayBatDau, $ngayKetThuc, $khungGio, $trangThai, $this->pdo);
            KhuyenMai::ganDanhSachSanPham($maKhuyenMai, $danhSachMaDt, $this->pdo);
            DieuKienApDung::luu($maKhuyenMai, (float)($dk['giaTriToiThieu'] ?? 0), $dk['nhomHang'] ?? 'Tất cả', $dk['doiTuong'] ?? 'Tất cả khách hàng', $this->pdo);
            xacNhanGiaoDich($this->pdo);

            return ['thanhCong' => true, 'thongBao' => 'Tạo chương trình khuyến mãi thành công', 'duLieu' => ['maKhuyenMai' => $maKhuyenMai], 'maHttp' => 201];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('luuKhuyenMai: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể tạo chương trình khuyến mãi', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC58: capNhatKhuyenMai(...) — Cập nhật cả KhuyenMai_DienThoai lẫn DieuKienApDung!
    public function capNhatKhuyenMai(int $maKhuyenMai, array $duLieu): array
    {
        if ($maKhuyenMai <= 0 || !KhuyenMai::timTheoMa($maKhuyenMai, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy chương trình khuyến mãi', 'duLieu' => null, 'maHttp' => 404];
        }

        $ten = trim((string)($duLieu['ten'] ?? ''));
        $noiDung = isset($duLieu['noiDung']) ? trim((string)$duLieu['noiDung']) : null;
        $mucUuDai = (float)($duLieu['mucUuDai'] ?? 0);
        $ngayBatDau = trim((string)($duLieu['ngayBatDau'] ?? date('Y-m-d')));
        $ngayKetThuc = trim((string)($duLieu['ngayKetThuc'] ?? date('Y-m-d')));
        $khungGio = trim((string)($duLieu['khungGio'] ?? '00:00-23:59'));
        $trangThai = isset($duLieu['trangThai']) ? trim((string)$duLieu['trangThai']) : null;
        $danhSachMaDt = is_array($duLieu['danhSachMaDienThoai'] ?? null) ? $duLieu['danhSachMaDienThoai'] : null;

        if (!KhuyenMai::kiemTraHopLe($ten, $mucUuDai, $ngayBatDau, $ngayKetThuc)) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin cập nhật khuyến mãi không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        if ($danhSachMaDt !== null) {
            if ($loiXungDot = KhuyenMai::kiemTraXungDotThoiGian($danhSachMaDt, $ngayBatDau, $ngayKetThuc, $maKhuyenMai, $this->pdo)) {
                return ['thanhCong' => false, 'thongBao' => $loiXungDot, 'duLieu' => null, 'maHttp' => 409];
            }
        }

        $dk = is_array($duLieu['dieuKienApDung'] ?? null) ? $duLieu['dieuKienApDung'] : $duLieu;

        try {
            batDauGiaoDich($this->pdo);
            KhuyenMai::capNhat($maKhuyenMai, $ten, $noiDung, $mucUuDai, $ngayBatDau, $ngayKetThuc, $khungGio, $trangThai, $this->pdo);
            if ($danhSachMaDt !== null) {
                KhuyenMai::ganDanhSachSanPham($maKhuyenMai, $danhSachMaDt, $this->pdo);
            }
            DieuKienApDung::capNhat($maKhuyenMai, (float)($dk['giaTriToiThieu'] ?? 0), $dk['nhomHang'] ?? 'Tất cả', $dk['doiTuong'] ?? 'Tất cả khách hàng', $this->pdo);
            xacNhanGiaoDich($this->pdo);

            return ['thanhCong' => true, 'thongBao' => 'Cập nhật chương trình khuyến mãi thành công', 'duLieu' => ['maKhuyenMai' => $maKhuyenMai]];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('capNhatKhuyenMai: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể cập nhật chương trình khuyến mãi', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function capNhatTrangThaiKhuyenMai(int $maKhuyenMai, string $trangThai): array
    {
        KhuyenMai::capNhatTrangThai($maKhuyenMai, $trangThai, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật trạng thái khuyến mãi thành công', 'duLieu' => ['maKhuyenMai' => $maKhuyenMai, 'trangThai' => $trangThai]];
    }

    // UC59: xoaKhuyenMai(maKhuyenMai)
    public function xoaKhuyenMai(int $maKhuyenMai): array
    {
        if ($maKhuyenMai <= 0 || !KhuyenMai::timTheoMa($maKhuyenMai, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy chương trình khuyến mãi', 'duLieu' => null, 'maHttp' => 404];
        }
        if (KhuyenMai::kiemTraDangSuDung($maKhuyenMai, $this->pdo)) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Không thể xóa chương trình khuyến mãi đang diễn ra. Vui lòng chuyển sang Ngừng áp dụng trước khi xóa',
                'duLieu'    => null,
                'maHttp'    => 409,
            ];
        }

        try {
            batDauGiaoDich($this->pdo);
            DieuKienApDung::xoaTheoKhuyenMai($maKhuyenMai, $this->pdo);
            KhuyenMai::xoa($maKhuyenMai, $this->pdo);
            xacNhanGiaoDich($this->pdo);
            return ['thanhCong' => true, 'thongBao' => 'Đã xóa chương trình khuyến mãi thành công', 'duLieu' => ['maKhuyenMai' => $maKhuyenMai]];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('xoaKhuyenMai: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa chương trình khuyến mãi', 'duLieu' => null, 'maHttp' => 500];
        }
    }
}
