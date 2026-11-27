<?php
// Controller BCE: HoSoKhachHangCtrl (UC43 - UC47) — Phụ lục B.1 Lan03

class HoSoKhachHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // UC43: layHoSo(maKhachHang)
    public function layHoSo(int $maKhachHang): array
    {
        $row = KhachHang::timTheoMa($maKhachHang, $this->pdo);
        if (!$row) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy hồ sơ khách hàng', 'duLieu' => null, 'maHttp' => 404];
        }
        $hoSo = KhachHang::tuMang($row)->chuyenThanhMang();
        $hoSo['dinhDanh'] = $row['dinhDanh'];
        $hoSo['vaiTro'] = $row['vaiTro'];
        $hoSo['trangThai'] = $row['trangThai'];
        return ['thanhCong' => true, 'thongBao' => 'Lấy hồ sơ khách hàng thành công', 'duLieu' => $hoSo];
    }

    // UC44: capNhatHoSo(maKhachHang, hoTen, soDienThoai, email)
    public function capNhatHoSo(int $maKhachHang, array $duLieu): array
    {
        $hoTen = trim((string)($duLieu['hoTen'] ?? ''));
        $soDienThoai = isset($duLieu['soDienThoai']) && trim((string)$duLieu['soDienThoai']) !== '' ? trim((string)$duLieu['soDienThoai']) : null;
        $email = isset($duLieu['email']) && trim((string)$duLieu['email']) !== '' ? trim((string)$duLieu['email']) : null;

        if (!KhachHang::kiemTraHopLe($hoTen, $soDienThoai, $email)) {
            return ['thanhCong' => false, 'thongBao' => 'Họ tên hoặc định dạng email không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        if (TaiKhoan::kiemTraTrungThongTin($maKhachHang, $soDienThoai, $email, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Số điện thoại hoặc email đã được sử dụng bởi khách hàng khác', 'duLieu' => null, 'maHttp' => 409];
        }

        KhachHang::capNhat($maKhachHang, $hoTen, $soDienThoai, $email, $this->pdo);
        return $this->layHoSo($maKhachHang);
    }

    public function doiMatKhau(int $maTaiKhoan, string $matKhauCu, string $matKhauMoi): array
    {
        if (strlen($matKhauMoi) < 6) {
            return ['thanhCong' => false, 'thongBao' => 'Mật khẩu mới phải có ít nhất 6 ký tự', 'duLieu' => null, 'maHttp' => 400];
        }
        $tkInfo = TaiKhoan::timTheoMa($maTaiKhoan, $this->pdo);
        if (!$tkInfo || !TaiKhoan::kiemTraMatKhau($tkInfo['dinhDanh'], $matKhauCu, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Mật khẩu hiện tại không chính xác', 'duLieu' => null, 'maHttp' => 400];
        }
        TaiKhoan::capNhatMatKhau($maTaiKhoan, password_hash($matKhauMoi, PASSWORD_BCRYPT), $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Đổi mật khẩu thành công', 'duLieu' => null];
    }

    // UC45: layDanhSachDiaChi(maKhachHang), layDiaChi(maDiaChi), capNhatDiaChi(...), themDiaChiGiaoHang(...), xoaDiaChiGiaoHang(...)
    public function layDanhSachDiaChi(int $maKhachHang): array
    {
        $ketQua = [];
        foreach (DiaChiGiaoHang::timTheoKhachHang($maKhachHang, $this->pdo) as $dong) {
            $muc = DiaChiGiaoHang::tuMang($dong)->chuyenThanhMang();
            $muc['khuVuc'] = $dong['khuVuc'];
            $muc['mucPhi'] = (float)$dong['mucPhi'];
            $ketQua[] = $muc;
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách địa chỉ giao hàng thành công', 'duLieu' => $ketQua];
    }

    public function layDiaChi(int $maDiaChi, int $maKhachHang): array
    {
        $dc = DiaChiGiaoHang::timTheoMa($maDiaChi, $this->pdo);
        if (!$dc || (int)$dc['maKhachHang'] !== $maKhachHang) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy địa chỉ giao hàng', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy địa chỉ giao hàng thành công', 'duLieu' => $dc];
    }

    public function themDiaChiGiaoHang(int $maKhachHang, array $duLieu): array
    {
        $nguoiNhan = trim((string)($duLieu['nguoiNhan'] ?? ''));
        $soDienThoai = trim((string)($duLieu['soDienThoai'] ?? ''));
        $diaChi = trim((string)($duLieu['diaChi'] ?? ''));
        $maQuyTac = (int)($duLieu['maQuyTac'] ?? 1);
        $macDinh = !empty($duLieu['macDinh']);

        if (!DiaChiGiaoHang::kiemTraHopLe($nguoiNhan, $soDienThoai, $diaChi, $maQuyTac)) {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập đầy đủ người nhận, số điện thoại và địa chỉ', 'duLieu' => null, 'maHttp' => 400];
        }
        $maDiaChi = DiaChiGiaoHang::luu($maKhachHang, $nguoiNhan, $soDienThoai, $diaChi, $maQuyTac, $macDinh, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Thêm địa chỉ giao hàng thành công', 'duLieu' => ['maDiaChi' => $maDiaChi], 'maHttp' => 201];
    }

    public function capNhatDiaChi(int $maKhachHang, int $maDiaChi, array $duLieu): array
    {
        $dc = DiaChiGiaoHang::timTheoMa($maDiaChi, $this->pdo);
        if (!$dc || (int)$dc['maKhachHang'] !== $maKhachHang) {
            return ['thanhCong' => false, 'thongBao' => 'Địa chỉ không tồn tại hoặc không thuộc quyền sở hữu của bạn', 'duLieu' => null, 'maHttp' => 403];
        }
        DiaChiGiaoHang::capNhat($maDiaChi, $maKhachHang, $duLieu, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật địa chỉ giao hàng thành công', 'duLieu' => ['maDiaChi' => $maDiaChi]];
    }

    public function xoaDiaChiGiaoHang(int $maKhachHang, int $maDiaChi): array
    {
        try {
            if (!DiaChiGiaoHang::xoa($maDiaChi, $maKhachHang, $this->pdo)) {
                return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy địa chỉ thuộc quyền sở hữu của bạn', 'duLieu' => null, 'maHttp' => 404];
            }
            return ['thanhCong' => true, 'thongBao' => 'Xóa địa chỉ giao hàng thành công', 'duLieu' => ['maDiaChi' => $maDiaChi]];
        } catch (Throwable $ngoaiLe) {
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa địa chỉ đang được sử dụng trong đơn hàng', 'duLieu' => null, 'maHttp' => 409];
        }
    }

    // UC46: layLichSuMuaHang(maKhachHang) & layChiTietDonHang(maDonHang)
    public function layLichSuMuaHang(int $maKhachHang): array
    {
        $dsDon = DonHang::timTheoKhachHang($maKhachHang, $this->pdo);
        $ctTheoDon = [];
        foreach (ChiTietDonHang::timTheoDonHang(null, $maKhachHang, $this->pdo) as $ct) {
            $ma = (int)$ct['maDonHang'];
            $muc = ChiTietDonHang::tuMang($ct)->chuyenThanhMang();
            $muc['tenDienThoai'] = $ct['tenDienThoai'] ?? '';
            $muc['hinhAnh'] = chuanHoaDuongDanAnh($ct['hinhAnh'] ?? '');
            $ctTheoDon[$ma][] = $muc;
        }

        $ketQua = [];
        foreach ($dsDon as $dong) {
            $muc = DonHang::tuMang($dong)->chuyenThanhMang();
            $muc['phuongThucThanhToan'] = $dong['phuongThuc'] ?? '';
            $muc['trangThaiThanhToan'] = $dong['trangThaiThanhToan'] ?? '';
            $muc['nguoiNhan'] = $dong['nguoiNhan'] ?? '';
            $muc['soDienThoai'] = $dong['soDienThoai'] ?? '';
            $muc['diaChiGiaoHang'] = $dong['diaChiGiaoHang'] ?? '';
            $muc['trangThaiYeuCauHuy'] = $dong['trangThaiYeuCauHuy'] ?? null;
            $muc['chiTiet'] = $ctTheoDon[$muc['maDonHang']] ?? [];
            $ketQua[] = $muc;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy lịch sử mua hàng thành công', 'duLieu' => $ketQua];
    }

    public function layChiTietDonHang(int $maDonHang, ?int $maKhachHangKiemTra = null): array
    {
        $dh = DonHang::timTheoMa($maDonHang, false, $this->pdo);
        if (!$dh) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy đơn hàng', 'duLieu' => null, 'maHttp' => 404];
        }
        if ($maKhachHangKiemTra !== null && (int)$dh['maKhachHang'] !== $maKhachHangKiemTra) {
            return ['thanhCong' => false, 'thongBao' => 'Bạn không có quyền xem đơn hàng của người khác', 'duLieu' => null, 'maHttp' => 403];
        }
        $dh['chiTiet'] = ChiTietDonHang::timTheoDonHang($maDonHang, null, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết đơn hàng thành công', 'duLieu' => $dh];
    }

    // UC47: timKhachHang(tuKhoa) — Nhân viên Bán hàng / CSKH tra cứu chỉ xem
    public function timKhachHang(?string $tuKhoa = null): array
    {
        $ketQua = [];
        foreach (KhachHang::timTheoTuKhoa($tuKhoa, $this->pdo) as $dong) {
            $muc = KhachHang::tuMang($dong)->chuyenThanhMang();
            $muc['dinhDanh'] = $dong['dinhDanh'];
            $muc['trangThai'] = $dong['trangThai'];
            $muc['tongSoDon'] = (int)$dong['tongSoDon'];
            $muc['tongChiTieu'] = (float)$dong['tongChiTieu'];
            $ketQua[] = $muc;
        }
        return ['thanhCong' => true, 'thongBao' => 'Tra cứu danh sách khách hàng thành công', 'duLieu' => $ketQua];
    }

    public function layThongBao(int $maTaiKhoan): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách thông báo thành công', 'duLieu' => ThongBao::timTheoNguoiNhan($maTaiKhoan, $this->pdo)];
    }

    public function danhDauDaDocThongBao(int $maTaiKhoan, ?int $maThongBao = null): array
    {
        ThongBao::danhDauDaDoc($maTaiKhoan, $maThongBao, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Đã đánh dấu thông báo đã đọc', 'duLieu' => null];
    }
}
