<?php
// Controller BCE: XacThucCtrl (UC39 - UC42)

class XacThucCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // UC39: Đăng ký tài khoản Khách hàng & tự động gộp giỏ hàng tạm theo maPhienTam
    public function dangKy(array $duLieu): array
    {
        $dinhDanh = trim((string)($duLieu['dinhDanh'] ?? ''));
        $matKhau = trim((string)($duLieu['matKhau'] ?? ''));
        $hoTen = trim((string)($duLieu['hoTen'] ?? ''));
        $soDienThoai = isset($duLieu['soDienThoai']) ? trim((string)$duLieu['soDienThoai']) : null;
        $email = isset($duLieu['email']) ? trim((string)$duLieu['email']) : null;
        $maPhienTam = trim((string)($duLieu['maPhienTam'] ?? ''));

        if ($dinhDanh === '' || strlen($matKhau) < 6 || $hoTen === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập đầy đủ họ tên, email/SĐT và mật khẩu (tối thiểu 6 ký tự)', 'duLieu' => null, 'maHttp' => 400];
        }

        if ($email === null && strpos($dinhDanh, '@') !== false) {
            $email = $dinhDanh;
        } elseif ($soDienThoai === null && preg_match('/^[0-9+\s.-]{8,15}$/', $dinhDanh)) {
            $soDienThoai = $dinhDanh;
        }

        $kiemTra = $this->pdo->prepare("SELECT maTaiKhoan FROM TaiKhoan WHERE dinhDanh = ? LIMIT 1");
        $kiemTra->execute([$dinhDanh]);
        if ($kiemTra->fetch()) {
            return ['thanhCong' => false, 'thongBao' => 'Email hoặc số điện thoại này đã được đăng ký', 'duLieu' => null, 'maHttp' => 409];
        }

        try {
            $this->pdo->beginTransaction();

            $matKhauDaBam = password_hash($matKhau, PASSWORD_BCRYPT);
            $this->pdo->prepare(
                "INSERT INTO TaiKhoan (dinhDanh, matKhauDaBam, vaiTro, trangThai) VALUES (?, ?, 'Khách hàng', 'Hoạt động')"
            )->execute([$dinhDanh, $matKhauDaBam]);
            $maTaiKhoan = (int)$this->pdo->lastInsertId();

            $this->pdo->prepare(
                "INSERT INTO KhachHang (maTaiKhoan, hoTen, soDienThoai, email) VALUES (?, ?, ?, ?)"
            )->execute([$maTaiKhoan, $hoTen, $soDienThoai, $email]);
            $maKhachHang = (int)$this->pdo->lastInsertId();

            // Tạo phiên đăng nhập mới luôn sau khi đăng ký
            $maPhien = bin2hex(random_bytes(32));
            $thoiDiemHetHan = date('Y-m-d H:i:s', time() + 86400 * 7);
            $this->pdo->prepare(
                "INSERT INTO PhienDangNhap (maPhien, maTaiKhoan, thoiDiemTao, thoiDiemHetHan, trangThai)
                 VALUES (?, ?, CURRENT_TIMESTAMP, ?, 'Còn hiệu lực')"
            )->execute([$maPhien, $maTaiKhoan, $thoiDiemHetHan]);

            $this->pdo->commit();

            // Tự động gộp giỏ hàng tạm (nếu có) vào giỏ hàng của khách hàng mới tạo
            if ($maPhienTam !== '') {
                (new GioHangCtrl($this->pdo))->gopGioHangTam($maPhienTam, $maKhachHang, $maPhien);
            }

            return [
                'thanhCong' => true,
                'thongBao'  => 'Đăng ký tài khoản thành công',
                'duLieu'    => [
                    'maPhien'           => $maPhien,
                    'thoiDiemHetHan'    => $thoiDiemHetHan,
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
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Không thể đăng ký tài khoản: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC40: Đăng nhập (kiểm tra khóa, tạo phiên, gộp giỏ tạm, trả đường dẫn điều hướng theo vaiTro)
    public function dangNhap(array $duLieu): array
    {
        $dinhDanh = trim((string)($duLieu['dinhDanh'] ?? ''));
        $matKhau = trim((string)($duLieu['matKhau'] ?? ''));
        $maPhienTam = trim((string)($duLieu['maPhienTam'] ?? ''));

        if ($dinhDanh === '' || $matKhau === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu', 'duLieu' => null, 'maHttp' => 400];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM TaiKhoan WHERE dinhDanh = ? LIMIT 1");
        $stmt->execute([$dinhDanh]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin đăng nhập không chính xác', 'duLieu' => null, 'maHttp' => 401];
        }

        $taiKhoan = TaiKhoan::tuMang($row);
        if (!$taiKhoan->dangHoatDong()) {
            return ['thanhCong' => false, 'thongBao' => 'Tài khoản đã bị khóa', 'duLieu' => ['lyDoKhoa' => $taiKhoan->lyDoKhoa], 'maHttp' => 403];
        }

        if (!password_verify($matKhau, $taiKhoan->matKhauDaBam)) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin đăng nhập không chính xác', 'duLieu' => null, 'maHttp' => 401];
        }

        $maPhien = bin2hex(random_bytes(32));
        $thoiDiemHetHan = date('Y-m-d H:i:s', time() + 86400 * 7);

        $this->pdo->prepare(
            "INSERT INTO PhienDangNhap (maPhien, maTaiKhoan, thoiDiemTao, thoiDiemHetHan, trangThai)
             VALUES (?, ?, CURRENT_TIMESTAMP, ?, 'Còn hiệu lực')"
        )->execute([$maPhien, $taiKhoan->maTaiKhoan, $thoiDiemHetHan]);

        $thongTinHoSo = $this->layThongTinHoSoTheoTaiKhoan((int)$taiKhoan->maTaiKhoan, $taiKhoan->vaiTro);

        if ($taiKhoan->vaiTro === 'Khách hàng' && !empty($thongTinHoSo['maKhachHang']) && $maPhienTam !== '') {
            (new GioHangCtrl($this->pdo))->gopGioHangTam($maPhienTam, (int)$thongTinHoSo['maKhachHang'], $maPhien);
        }

        $duongDanDieuHuong = ($taiKhoan->vaiTro === 'Khách hàng') ? '/' : '/admin';

        return [
            'thanhCong' => true,
            'thongBao'  => 'Đăng nhập thành công',
            'duLieu'    => [
                'maPhien'           => $maPhien,
                'thoiDiemHetHan'    => $thoiDiemHetHan,
                'duongDanDieuHuong' => $duongDanDieuHuong,
                'taiKhoan'          => array_merge($taiKhoan->chuyenThanhMangAnToan(), $thongTinHoSo),
            ],
        ];
    }

    // UC41: Đăng xuất (hủy phiên trong PhienDangNhap, giữ nguyên giỏ hàng của tài khoản trong DB)
    public function dangXuat(string $maPhien): array
    {
        if ($maPhien !== '') {
            $this->pdo->prepare("UPDATE PhienDangNhap SET trangThai = 'Đã hủy' WHERE maPhien = ?")->execute([$maPhien]);
        }
        return ['thanhCong' => true, 'thongBao' => 'Đăng xuất thành công', 'duLieu' => null];
    }

    public function kiemTraPhien(string $maPhien): ?array
    {
        if ($maPhien === '') {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT p.*, tk.dinhDanh, tk.vaiTro, tk.trangThai AS trangThaiTaiKhoan
             FROM PhienDangNhap p
             JOIN TaiKhoan tk ON tk.maTaiKhoan = p.maTaiKhoan
             WHERE p.maPhien = ? LIMIT 1"
        );
        $stmt->execute([$maPhien]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $phien = PhienDangNhap::tuMang($row);
        if (!$phien->conHieuLuc() || $row['trangThaiTaiKhoan'] !== 'Hoạt động') {
            return null;
        }

        $hoSo = $this->layThongTinHoSoTheoTaiKhoan((int)$row['maTaiKhoan'], $row['vaiTro']);
        return array_merge([
            'maPhien'    => $row['maPhien'],
            'maTaiKhoan' => (int)$row['maTaiKhoan'],
            'dinhDanh'   => $row['dinhDanh'],
            'vaiTro'     => $row['vaiTro'],
        ], $hoSo);
    }

    // UC42: Gửi mã xác thực (thông báo chung chống dò tài khoản)
    public function guiMaXacThuc(string $dinhDanh): array
    {
        $dinhDanh = trim($dinhDanh);
        if ($dinhDanh === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập email hoặc số điện thoại', 'duLieu' => null, 'maHttp' => 400];
        }

        $stmt = $this->pdo->prepare("SELECT maTaiKhoan FROM TaiKhoan WHERE dinhDanh = ? LIMIT 1");
        $stmt->execute([$dinhDanh]);
        $maTaiKhoan = $stmt->fetchColumn();

        $duLieuMoPhong = null;
        if ($maTaiKhoan) {
            $maXacThuc = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $hanMa = date('Y-m-d H:i:s', time() + 900);
            $this->pdo->prepare("UPDATE TaiKhoan SET maXacThuc = ?, hanMaXacThuc = ? WHERE maTaiKhoan = ?")
                ->execute([$maXacThuc, $hanMa, $maTaiKhoan]);
            $duLieuMoPhong = ['maXacThucMoPhong' => $maXacThuc, 'hanMaXacThuc' => $hanMa];
        }

        // Thông báo chung chống dò tài khoản (Anti-enumeration)
        return [
            'thanhCong' => true,
            'thongBao'  => 'Nếu tài khoản tồn tại trong hệ thống, mã xác thực đã được gửi (hiệu lực 15 phút)',
            'duLieu'    => $duLieuMoPhong,
        ];
    }

    public function yeuCauKhoiPhucMatKhau(string $dinhDanh): array
    {
        return $this->guiMaXacThuc($dinhDanh);
    }

    // UC42: Đặt lại mật khẩu và hủy toàn bộ phiên đăng nhập cũ
    public function datLaiMatKhau(string $dinhDanh, string $maXacThuc, string $matKhauMoi): array
    {
        if (strlen($matKhauMoi) < 6) {
            return ['thanhCong' => false, 'thongBao' => 'Mật khẩu mới phải có ít nhất 6 ký tự', 'duLieu' => null, 'maHttp' => 400];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM TaiKhoan WHERE dinhDanh = ? LIMIT 1");
        $stmt->execute([trim($dinhDanh)]);
        $row = $stmt->fetch();

        if (!$row || $row['maXacThuc'] !== trim($maXacThuc) || strtotime((string)$row['hanMaXacThuc']) < time()) {
            return ['thanhCong' => false, 'thongBao' => 'Mã xác thực không đúng hoặc đã hết hạn', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            $this->pdo->beginTransaction();
            $bamMoi = password_hash($matKhauMoi, PASSWORD_BCRYPT);
            $this->pdo->prepare("UPDATE TaiKhoan SET matKhauDaBam = ?, maXacThuc = NULL, hanMaXacThuc = NULL WHERE maTaiKhoan = ?")
                ->execute([$bamMoi, $row['maTaiKhoan']]);
            // Hủy toàn bộ phiên đăng nhập cũ của tài khoản
            $this->pdo->prepare("UPDATE PhienDangNhap SET trangThai = 'Đã hủy' WHERE maTaiKhoan = ?")
                ->execute([$row['maTaiKhoan']]);
            $this->pdo->commit();

            return ['thanhCong' => true, 'thongBao' => 'Đặt lại mật khẩu thành công. Toàn bộ phiên đăng nhập cũ đã bị hủy', 'duLieu' => null];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    private function layThongTinHoSoTheoTaiKhoan(int $maTaiKhoan, string $vaiTro): array
    {
        if ($vaiTro === 'Khách hàng') {
            $stmt = $this->pdo->prepare("SELECT maKhachHang, hoTen, soDienThoai, email FROM KhachHang WHERE maTaiKhoan = ? LIMIT 1");
            $stmt->execute([$maTaiKhoan]);
            $kh = $stmt->fetch();
            return $kh ? [
                'maKhachHang' => (int)$kh['maKhachHang'],
                'maNhanVien'  => null,
                'hoTen'       => $kh['hoTen'],
                'soDienThoai' => $kh['soDienThoai'],
                'email'       => $kh['email'],
            ] : ['maKhachHang' => null, 'maNhanVien' => null];
        }

        $stmt = $this->pdo->prepare("SELECT maNhanVien, hoTen, soDienThoai FROM NhanVien WHERE maTaiKhoan = ? LIMIT 1");
        $stmt->execute([$maTaiKhoan]);
        $nv = $stmt->fetch();
        return $nv ? [
            'maKhachHang' => null,
            'maNhanVien'  => (int)$nv['maNhanVien'],
            'hoTen'       => $nv['hoTen'],
            'soDienThoai' => $nv['soDienThoai'],
        ] : ['maKhachHang' => null, 'maNhanVien' => null];
    }
}
