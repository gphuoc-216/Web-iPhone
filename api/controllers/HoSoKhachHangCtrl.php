<?php
// Controller BCE: HoSoKhachHangCtrl (UC43 - UC47)

class HoSoKhachHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layHoSo(int $maKhachHang): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT kh.*, tk.dinhDanh, tk.vaiTro, tk.trangThai
             FROM KhachHang kh
             JOIN TaiKhoan tk ON tk.maTaiKhoan = kh.maTaiKhoan
             WHERE kh.maKhachHang = ? LIMIT 1"
        );
        $stmt->execute([$maKhachHang]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy hồ sơ khách hàng', 'duLieu' => null, 'maHttp' => 404];
        }

        $hoSo = KhachHang::tuMang($row)->chuyenThanhMang();
        $hoSo['dinhDanh'] = $row['dinhDanh'];
        $hoSo['vaiTro'] = $row['vaiTro'];
        $hoSo['trangThai'] = $row['trangThai'];

        return ['thanhCong' => true, 'thongBao' => 'Lấy hồ sơ khách hàng thành công', 'duLieu' => $hoSo];
    }

    public function capNhatHoSo(int $maKhachHang, array $duLieu): array
    {
        $hoTen = trim((string)($duLieu['hoTen'] ?? ''));
        $soDienThoai = isset($duLieu['soDienThoai']) ? trim((string)$duLieu['soDienThoai']) : null;
        $email = isset($duLieu['email']) ? trim((string)$duLieu['email']) : null;

        if ($hoTen === '') {
            return ['thanhCong' => false, 'thongBao' => 'Họ tên không được để trống', 'duLieu' => null, 'maHttp' => 400];
        }

        $this->pdo->prepare("UPDATE KhachHang SET hoTen = ?, soDienThoai = ?, email = ? WHERE maKhachHang = ?")
            ->execute([$hoTen, $soDienThoai, $email, $maKhachHang]);

        return $this->layHoSo($maKhachHang);
    }

    public function doiMatKhau(int $maTaiKhoan, string $matKhauCu, string $matKhauMoi): array
    {
        if (strlen($matKhauMoi) < 6) {
            return ['thanhCong' => false, 'thongBao' => 'Mật khẩu mới phải có ít nhất 6 ký tự', 'duLieu' => null, 'maHttp' => 400];
        }

        $stmt = $this->pdo->prepare("SELECT matKhauDaBam FROM TaiKhoan WHERE maTaiKhoan = ? LIMIT 1");
        $stmt->execute([$maTaiKhoan]);
        $bamCu = $stmt->fetchColumn();

        if (!$bamCu || !password_verify($matKhauCu, (string)$bamCu)) {
            return ['thanhCong' => false, 'thongBao' => 'Mật khẩu hiện tại không chính xác', 'duLieu' => null, 'maHttp' => 400];
        }

        $this->pdo->prepare("UPDATE TaiKhoan SET matKhauDaBam = ? WHERE maTaiKhoan = ?")
            ->execute([password_hash($matKhauMoi, PASSWORD_BCRYPT), $maTaiKhoan]);

        return ['thanhCong' => true, 'thongBao' => 'Đổi mật khẩu thành công', 'duLieu' => null];
    }

    public function layDanhSachDiaChi(int $maKhachHang): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT dc.*, qt.khuVuc, qt.mucPhi
             FROM DiaChiGiaoHang dc
             JOIN QuyTacPhiVanChuyen qt ON qt.maQuyTac = dc.maQuyTac
             WHERE dc.maKhachHang = ?
             ORDER BY dc.macDinh DESC, dc.maDiaChi DESC"
        );
        $stmt->execute([$maKhachHang]);

        $ketQua = [];
        foreach ($stmt->fetchAll() as $dong) {
            $muc = DiaChiGiaoHang::tuMang($dong)->chuyenThanhMang();
            $muc['khuVuc'] = $dong['khuVuc'];
            $muc['mucPhi'] = (float)$dong['mucPhi'];
            $ketQua[] = $muc;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách địa chỉ giao hàng thành công', 'duLieu' => $ketQua];
    }

    public function themDiaChiGiaoHang(int $maKhachHang, array $duLieu): array
    {
        $nguoiNhan = trim((string)($duLieu['nguoiNhan'] ?? ''));
        $soDienThoai = trim((string)($duLieu['soDienThoai'] ?? ''));
        $diaChi = trim((string)($duLieu['diaChi'] ?? ''));
        $maQuyTac = (int)($duLieu['maQuyTac'] ?? 1);
        $macDinh = (bool)($duLieu['macDinh'] ?? false);

        if ($nguoiNhan === '' || $soDienThoai === '' || $diaChi === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập đầy đủ người nhận, số điện thoại và địa chỉ', 'duLieu' => null, 'maHttp' => 400];
        }

        if ($macDinh) {
            $this->pdo->prepare("UPDATE DiaChiGiaoHang SET macDinh = FALSE WHERE maKhachHang = ?")->execute([$maKhachHang]);
        }

        $this->pdo->prepare(
            "INSERT INTO DiaChiGiaoHang (maKhachHang, nguoiNhan, soDienThoai, diaChi, maQuyTac, macDinh)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$maKhachHang, $nguoiNhan, $soDienThoai, $diaChi, $maQuyTac, $macDinh ? 1 : 0]);

        return ['thanhCong' => true, 'thongBao' => 'Thêm địa chỉ giao hàng thành công', 'duLieu' => ['maDiaChi' => (int)$this->pdo->lastInsertId()], 'maHttp' => 201];
    }

    public function capNhatDiaChiGiaoHang(int $maKhachHang, int $maDiaChi, array $duLieu): array
    {
        $macDinh = (bool)($duLieu['macDinh'] ?? false);
        if ($macDinh) {
            $this->pdo->prepare("UPDATE DiaChiGiaoHang SET macDinh = FALSE WHERE maKhachHang = ?")->execute([$maKhachHang]);
        }
        $this->pdo->prepare(
            "UPDATE DiaChiGiaoHang
             SET nguoiNhan = COALESCE(?, nguoiNhan),
                 soDienThoai = COALESCE(?, soDienThoai),
                 diaChi = COALESCE(?, diaChi),
                 maQuyTac = COALESCE(?, maQuyTac),
                 macDinh = ?
             WHERE maDiaChi = ? AND maKhachHang = ?"
        )->execute([
            $duLieu['nguoiNhan'] ?? null,
            $duLieu['soDienThoai'] ?? null,
            $duLieu['diaChi'] ?? null,
            isset($duLieu['maQuyTac']) ? (int)$duLieu['maQuyTac'] : null,
            $macDinh ? 1 : 0,
            $maDiaChi,
            $maKhachHang,
        ]);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật địa chỉ giao hàng thành công', 'duLieu' => ['maDiaChi' => $maDiaChi]];
    }

    public function xoaDiaChiGiaoHang(int $maKhachHang, int $maDiaChi): array
    {
        try {
            $this->pdo->prepare("DELETE FROM DiaChiGiaoHang WHERE maDiaChi = ? AND maKhachHang = ?")->execute([$maDiaChi, $maKhachHang]);
            return ['thanhCong' => true, 'thongBao' => 'Xóa địa chỉ giao hàng thành công', 'duLieu' => ['maDiaChi' => $maDiaChi]];
        } catch (Throwable $ngoaiLe) {
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa địa chỉ đang được sử dụng trong đơn hàng', 'duLieu' => null, 'maHttp' => 409];
        }
    }

    public function layLichSuDonHang(int $maKhachHang): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT dh.*, tt.phuongThuc, tt.trangThai AS trangThaiThanhToan,
                    dc.nguoiNhan, dc.soDienThoai, dc.diaChi AS diaChiGiaoHang,
                    (SELECT yc.trangThai FROM YeuCauHuyDon yc WHERE yc.maDonHang = dh.maDonHang ORDER BY yc.maYeuCau DESC LIMIT 1) AS trangThaiYeuCauHuy
             FROM DonHang dh
             LEFT JOIN ThanhToan tt ON tt.maDonHang = dh.maDonHang
             LEFT JOIN DiaChiGiaoHang dc ON dc.maDiaChi = dh.maDiaChi
             WHERE dh.maKhachHang = ?
             ORDER BY dh.ngayDat DESC"
        );
        $stmt->execute([$maKhachHang]);
        $dsDon = $stmt->fetchAll();

        $ctStmt = $this->pdo->prepare(
            "SELECT ct.*, d.ten AS tenDienThoai, d.hinhAnh
             FROM ChiTietDonHang ct
             JOIN DonHang dh ON dh.maDonHang = ct.maDonHang
             LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai
             WHERE dh.maKhachHang = ?"
        );
        $ctStmt->execute([$maKhachHang]);
        $ctTheoDon = [];
        foreach ($ctStmt->fetchAll() as $ct) {
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

        return ['thanhCong' => true, 'thongBao' => 'Lấy lịch sử đơn hàng thành công', 'duLieu' => $ketQua];
    }

    public function layThongBao(int $maTaiKhoan): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ThongBao WHERE maNguoiNhan = ? ORDER BY thoiDiemTao DESC LIMIT 50");
        $stmt->execute([$maTaiKhoan]);
        $ketQua = array_map(fn($d) => ThongBao::tuMang($d)->chuyenThanhMang(), $stmt->fetchAll());
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách thông báo thành công', 'duLieu' => $ketQua];
    }

    public function danhDauDaDocThongBao(int $maTaiKhoan, ?int $maThongBao = null): array
    {
        if ($maThongBao !== null && $maThongBao > 0) {
            $this->pdo->prepare("UPDATE ThongBao SET daDoc = TRUE WHERE maThongBao = ? AND maNguoiNhan = ?")
                ->execute([$maThongBao, $maTaiKhoan]);
        } else {
            $this->pdo->prepare("UPDATE ThongBao SET daDoc = TRUE WHERE maNguoiNhan = ?")->execute([$maTaiKhoan]);
        }
        return ['thanhCong' => true, 'thongBao' => 'Đã đánh dấu thông báo đã đọc', 'duLieu' => null];
    }

    // UC47: Nhân viên Bán hàng / CSKH tra cứu thông tin & lịch sử mua của khách hàng (chỉ xem)
    public function traCuuKhachHang(?string $tuKhoa = null): array
    {
        $sql = "SELECT kh.*, tk.dinhDanh, tk.trangThai,
                       COUNT(dh.maDonHang) AS tongSoDon,
                       COALESCE(SUM(CASE WHEN dh.trangThai <> 'Đã hủy' THEN dh.tongTien ELSE 0 END), 0) AS tongChiTieu
                FROM KhachHang kh
                JOIN TaiKhoan tk ON tk.maTaiKhoan = kh.maTaiKhoan
                LEFT JOIN DonHang dh ON dh.maKhachHang = kh.maKhachHang";
        $thamSo = [];
        $tu = trim((string)$tuKhoa);
        if ($tu !== '') {
            $sql .= " WHERE kh.hoTen LIKE ? OR kh.soDienThoai LIKE ? OR kh.email LIKE ?";
            $mau = '%' . $tu . '%';
            $thamSo = [$mau, $mau, $mau];
        }
        $sql .= " GROUP BY kh.maKhachHang ORDER BY kh.maKhachHang DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($thamSo);
        $ketQua = [];
        foreach ($stmt->fetchAll() as $dong) {
            $muc = KhachHang::tuMang($dong)->chuyenThanhMang();
            $muc['dinhDanh'] = $dong['dinhDanh'];
            $muc['trangThai'] = $dong['trangThai'];
            $muc['tongSoDon'] = (int)$dong['tongSoDon'];
            $muc['tongChiTieu'] = (float)$dong['tongChiTieu'];
            $ketQua[] = $muc;
        }
        return ['thanhCong' => true, 'thongBao' => 'Tra cứu danh sách khách hàng thành công', 'duLieu' => $ketQua];
    }
}
