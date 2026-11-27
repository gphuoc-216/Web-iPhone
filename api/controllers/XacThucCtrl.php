<?php
// Controller BCE: XacThucCtrl (UC39 - UC42) — Phụ lục B.1 Lan03

class XacThucCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // UC39: dangKy(hoTen, dinhDanh, matKhau, xacNhanMatKhau)
    public function dangKy(array $duLieu): array
    {
        $dinhDanh = trim((string)($duLieu['dinhDanh'] ?? ''));
        $matKhau = trim((string)($duLieu['matKhau'] ?? ''));
        $xacNhanMatKhau = isset($duLieu['xacNhanMatKhau']) ? trim((string)$duLieu['xacNhanMatKhau']) : $matKhau;
        $hoTen = trim((string)($duLieu['hoTen'] ?? ''));
        $soDienThoai = isset($duLieu['soDienThoai']) ? trim((string)$duLieu['soDienThoai']) : null;
        $email = isset($duLieu['email']) ? trim((string)$duLieu['email']) : null;
        $maPhienTam = trim((string)($duLieu['maPhienTam'] ?? ''));

        if ($matKhau !== $xacNhanMatKhau) {
            return ['thanhCong' => false, 'thongBao' => 'Mật khẩu xác nhận không khớp', 'duLieu' => null, 'maHttp' => 400];
        }
        if (!TaiKhoan::kiemTraHopLe($dinhDanh, $matKhau, $xacNhanMatKhau) || $hoTen === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập đầy đủ họ tên, định danh và mật khẩu (tối thiểu 6 ký tự)', 'duLieu' => null, 'maHttp' => 400];
        }

        if ($email === null && strpos($dinhDanh, '@') !== false) {
            $email = $dinhDanh;
        } elseif ($soDienThoai === null && preg_match('/^[0-9+\s.-]{8,15}$/', $dinhDanh)) {
            $soDienThoai = $dinhDanh;
        }

        if (TaiKhoan::kiemTraTonTai($dinhDanh, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Email hoặc số điện thoại này đã được đăng ký', 'duLieu' => null, 'maHttp' => 409];
        }

        try {
            batDauGiaoDich($this->pdo);
            $matKhauDaBam = password_hash($matKhau, PASSWORD_BCRYPT);
            $maTaiKhoan = TaiKhoan::luu($dinhDanh, $matKhauDaBam, 'Khách hàng', 'Hoạt động', $this->pdo);
            $maKhachHang = KhachHang::luu($maTaiKhoan, $hoTen, $soDienThoai, $email, $this->pdo);
            $phien = PhienDangNhap::taoPhien($maTaiKhoan, $this->pdo);
            xacNhanGiaoDich($this->pdo);

            if ($maPhienTam !== '') {
                GioHang::gopGioHangTam($maPhienTam, $maKhachHang, $phien['maPhien'], $this->pdo);
            }

            return [
                'thanhCong' => true,
                'thongBao'  => 'Đăng ký tài khoản thành công',
                'duLieu'    => [
                    'maPhien'           => $phien['maPhien'],
                    'thoiDiemHetHan'    => $phien['thoiDiemHetHan'],
                    'duongDanDieuHuong' => '/',
                    'taiKhoan'          => [
                        'maTaiKhoan'  => $maTaiKhoan,
                        'maKhachHang' => $maKhachHang,
                        'maNhanVien'  => null,
                        'dinhDanh'    => $dinhDanh,
                        'hoTen'       => $hoTen,
                        'soDienThoai' => $soDienThoai,
                        'email'       => $email,
                        'vaiTro'      => 'Khách hàng',
                        'trangThai'   => 'Hoạt động',
                    ],
                ],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('dangKy: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể đăng ký tài khoản. Vui lòng thử lại sau', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC40: dangNhap(dinhDanh, matKhau)
    public function dangNhap(array $duLieu): array
    {
        $dinhDanh = trim((string)($duLieu['dinhDanh'] ?? ''));
        $matKhau = trim((string)($duLieu['matKhau'] ?? ''));
        $maPhienTam = trim((string)($duLieu['maPhienTam'] ?? ''));

        if ($dinhDanh === '' || $matKhau === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu', 'duLieu' => null, 'maHttp' => 400];
        }

        $taiKhoan = TaiKhoan::timTheoDinhDanh($dinhDanh, $this->pdo);
        if (!$taiKhoan) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin đăng nhập không chính xác', 'duLieu' => null, 'maHttp' => 401];
        }

        if (!$taiKhoan->dangHoatDong()) {
            return ['thanhCong' => false, 'thongBao' => 'Tài khoản đã bị khóa', 'duLieu' => ['lyDoKhoa' => $taiKhoan->lyDoKhoa], 'maHttp' => 403];
        }

        if (!TaiKhoan::kiemTraMatKhau($dinhDanh, $matKhau, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin đăng nhập không chính xác', 'duLieu' => null, 'maHttp' => 401];
        }

        $phien = PhienDangNhap::taoPhien((int)$taiKhoan->maTaiKhoan, $this->pdo);
        $hoSo = $this->layHoSoTheoTaiKhoan((int)$taiKhoan->maTaiKhoan, $taiKhoan->vaiTro);

        if ($taiKhoan->vaiTro === 'Khách hàng' && !empty($hoSo['maKhachHang']) && $maPhienTam !== '') {
            GioHang::gopGioHangTam($maPhienTam, (int)$hoSo['maKhachHang'], $phien['maPhien'], $this->pdo);
        }

        return [
            'thanhCong' => true,
            'thongBao'  => 'Đăng nhập thành công',
            'duLieu'    => [
                'maPhien'           => $phien['maPhien'],
                'thoiDiemHetHan'    => $phien['thoiDiemHetHan'],
                'duongDanDieuHuong' => ($taiKhoan->vaiTro === 'Khách hàng') ? '/' : '/admin',
                'taiKhoan'          => array_merge($taiKhoan->chuyenThanhMangAnToan(), $hoSo),
            ],
        ];
    }

    // UC41: dangXuat(maPhien)
    public function dangXuat(string $maPhien): array
    {
        PhienDangNhap::huyPhien($maPhien, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Đăng xuất thành công', 'duLieu' => null];
    }

    public function kiemTraPhien(string $maPhien): ?array
    {
        $row = PhienDangNhap::layThongTinPhien($maPhien, $this->pdo);
        if (!$row) return null;
        $hoSo = $this->layHoSoTheoTaiKhoan((int)$row['maTaiKhoan'], $row['vaiTro']);
        return array_merge([
            'maPhien'    => $row['maPhien'],
            'maTaiKhoan' => (int)$row['maTaiKhoan'],
            'dinhDanh'   => $row['dinhDanh'],
            'vaiTro'     => $row['vaiTro'],
        ], $hoSo);
    }

    // UC42: guiMaXacThuc(dinhDanh) — Kiểm tra trangThai === 'Bị khóa' theo luồng phụ 3.2!
    public function guiMaXacThuc(string $dinhDanh): array
    {
        $dinhDanh = trim($dinhDanh);
        if ($dinhDanh === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập email hoặc số điện thoại', 'duLieu' => null, 'maHttp' => 400];
        }

        $taiKhoan = TaiKhoan::timTheoDinhDanh($dinhDanh, $this->pdo);
        if ($taiKhoan && !$taiKhoan->dangHoatDong()) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Tài khoản của bạn đã bị khóa, không thể thực hiện khôi phục mật khẩu',
                'duLieu'    => null,
                'maHttp'    => 403,
            ];
        }

        $duLieuMoPhong = null;
        if ($taiKhoan) {
            $duLieuMoPhong = TaiKhoan::taoMaXacThuc((int)$taiKhoan->maTaiKhoan, $this->pdo);
        }

        return [
            'thanhCong' => true,
            'thongBao'  => 'Nếu tài khoản tồn tại trong hệ thống, mã xác thực đã được gửi (hiệu lực 15 phút)',
            'duLieu'    => $duLieuMoPhong,
        ];
    }

    // UC42: datLaiMatKhau(dinhDanh, maXacThuc, matKhauMoi, xacNhanMatKhau)
    public function datLaiMatKhau(string $dinhDanh, string $maXacThuc, string $matKhauMoi, ?string $xacNhanMatKhau = null): array
    {
        if (strlen($matKhauMoi) < 6) {
            return ['thanhCong' => false, 'thongBao' => 'Mật khẩu mới phải có ít nhất 6 ký tự', 'duLieu' => null, 'maHttp' => 400];
        }
        if ($xacNhanMatKhau !== null && $matKhauMoi !== $xacNhanMatKhau) {
            return ['thanhCong' => false, 'thongBao' => 'Mật khẩu xác nhận không khớp', 'duLieu' => null, 'maHttp' => 400];
        }

        $tk = TaiKhoan::kiemTraMaXacThuc($dinhDanh, $maXacThuc, $this->pdo);
        if (!$tk) {
            return ['thanhCong' => false, 'thongBao' => 'Mã xác thực không đúng hoặc đã hết hạn', 'duLieu' => null, 'maHttp' => 400];
        }
        if (!$tk->dangHoatDong()) {
            return ['thanhCong' => false, 'thongBao' => 'Tài khoản đã bị khóa', 'duLieu' => null, 'maHttp' => 403];
        }

        try {
            batDauGiaoDich($this->pdo);
            TaiKhoan::capNhatMatKhau((int)$tk->maTaiKhoan, password_hash($matKhauMoi, PASSWORD_BCRYPT), $this->pdo);
            PhienDangNhap::huyPhienTheoTaiKhoan((int)$tk->maTaiKhoan, $this->pdo);
            xacNhanGiaoDich($this->pdo);
            return ['thanhCong' => true, 'thongBao' => 'Đặt lại mật khẩu thành công. Toàn bộ phiên đăng nhập cũ đã bị hủy', 'duLieu' => null];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('datLaiMatKhau: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    private function layHoSoTheoTaiKhoan(int $maTaiKhoan, string $vaiTro): array
    {
        if ($vaiTro === 'Khách hàng') {
            $kh = KhachHang::timTheoTaiKhoan($maTaiKhoan, $this->pdo);
            return $kh ? [
                'maKhachHang' => (int)$kh['maKhachHang'],
                'maNhanVien'  => null,
                'hoTen'       => $kh['hoTen'],
                'soDienThoai' => $kh['soDienThoai'],
                'email'       => $kh['email'],
            ] : ['maKhachHang' => null, 'maNhanVien' => null];
        }
        $nv = NhanVien::timTheoTaiKhoan($maTaiKhoan, $this->pdo);
        return $nv ? [
            'maKhachHang' => null,
            'maNhanVien'  => (int)$nv['maNhanVien'],
            'hoTen'       => $nv['hoTen'],
            'soDienThoai' => $nv['soDienThoai'],
        ] : ['maKhachHang' => null, 'maNhanVien' => null];
    }
}
