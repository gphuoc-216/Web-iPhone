<?php
// Controller BCE: QuanLyTaiKhoanCtrl (UC48 - UC52) — Phụ lục B.1 Lan03

class QuanLyTaiKhoanCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function chuanHoaTaiKhoan(array $dong): array
    {
        return [
            'maTaiKhoan'  => (int)$dong['maTaiKhoan'],
            'dinhDanh'    => $dong['dinhDanh'],
            'vaiTro'      => $dong['vaiTro'],
            'trangThai'   => $dong['trangThai'],
            'lyDoKhoa'    => $dong['lyDoKhoa'] ?? null,
            'hoTen'       => $dong['hoTen'] ?? '',
            'soDienThoai' => $dong['soDienThoai'] ?? null,
            'email'       => $dong['email'] ?? null,
        ];
    }

    // UC48: layDanhSachTaiKhoan(), timKiemTaiKhoan(tuKhoa), layChiTietTaiKhoan(maTaiKhoan)
    public function layDanhSachTaiKhoan(): array
    {
        $ds = array_map([$this, 'chuanHoaTaiKhoan'], TaiKhoan::timTatCa($this->pdo));
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách tài khoản thành công', 'duLieu' => $ds];
    }

    public function timKiemTaiKhoan(string $tuKhoa): array
    {
        $ds = array_map([$this, 'chuanHoaTaiKhoan'], TaiKhoan::timTheoTuKhoa($tuKhoa, $this->pdo));
        return ['thanhCong' => true, 'thongBao' => 'Tìm kiếm tài khoản thành công', 'duLieu' => $ds];
    }

    public function layChiTietTaiKhoan(int $maTaiKhoan): array
    {
        $tk = TaiKhoan::timTheoMa($maTaiKhoan, $this->pdo);
        if (!$tk) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy tài khoản', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết tài khoản thành công', 'duLieu' => $this->chuanHoaTaiKhoan($tk)];
    }

    // UC49: khoaTaiKhoan(maTaiKhoan, lyDo)
    public function khoaTaiKhoan(int $maTaiKhoan, ?string $lyDo, int $maTaiKhoanThucHien): array
    {
        if (!TaiKhoan::kiemTraQuyenKhoa($maTaiKhoanThucHien, $maTaiKhoan)) {
            return ['thanhCong' => false, 'thongBao' => 'Không thể tự khóa tài khoản của chính mình', 'duLieu' => null, 'maHttp' => 400];
        }
        $lyDoSach = trim((string)($lyDo ?: 'Khóa bởi quản trị viên'));
        TaiKhoan::capNhatTrangThai($maTaiKhoan, 'Bị khóa', $lyDoSach, $this->pdo);
        PhienDangNhap::huyPhienTheoTaiKhoan($maTaiKhoan, $this->pdo);

        return [
            'thanhCong' => true,
            'thongBao'  => 'Đã khóa tài khoản và thu hồi toàn bộ phiên đăng nhập',
            'duLieu'    => ['maTaiKhoan' => $maTaiKhoan, 'trangThai' => 'Bị khóa'],
        ];
    }

    // UC50: moKhoaTaiKhoan(maTaiKhoan)
    public function moKhoaTaiKhoan(int $maTaiKhoan): array
    {
        TaiKhoan::capNhatTrangThai($maTaiKhoan, 'Hoạt động', null, $this->pdo);
        return [
            'thanhCong' => true,
            'thongBao'  => 'Đã mở khóa tài khoản thành công',
            'duLieu'    => ['maTaiKhoan' => $maTaiKhoan, 'trangThai' => 'Hoạt động'],
        ];
    }

    // UC51: layDanhSachNhanVien() & taoTaiKhoanNhanVien(...)
    public function layDanhSachNhanVien(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách nhân viên thành công', 'duLieu' => NhanVien::timTatCa($this->pdo)];
    }

    public function taoTaiKhoanNhanVien(array $duLieu): array
    {
        $dinhDanh = trim((string)($duLieu['dinhDanh'] ?? $duLieu['email'] ?? ''));
        $matKhau = trim((string)($duLieu['matKhau'] ?? $duLieu['matKhauTam'] ?? '123456'));
        $vaiTro = trim((string)($duLieu['vaiTro'] ?? 'Nhân viên Bán hàng / CSKH'));
        $hoTen = trim((string)($duLieu['hoTen'] ?? ''));
        $soDienThoai = isset($duLieu['soDienThoai']) ? trim((string)$duLieu['soDienThoai']) : null;

        if ($hoTen === '' || !TaiKhoan::kiemTraHopLeNhanVien($dinhDanh, $vaiTro, $matKhau)) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin tài khoản nhân viên hoặc vai trò nội bộ không hợp lệ (mật khẩu tối thiểu 6 ký tự)', 'duLieu' => null, 'maHttp' => 400];
        }
        if (TaiKhoan::kiemTraTonTai($dinhDanh, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Định danh tài khoản đã tồn tại trong hệ thống', 'duLieu' => null, 'maHttp' => 409];
        }

        try {
            batDauGiaoDich($this->pdo);
            $maTaiKhoan = TaiKhoan::luu($dinhDanh, password_hash($matKhau, PASSWORD_BCRYPT), $vaiTro, 'Hoạt động', $this->pdo);
            $maNhanVien = NhanVien::luu($maTaiKhoan, $hoTen, $soDienThoai, $this->pdo);
            xacNhanGiaoDich($this->pdo);

            return [
                'thanhCong' => true,
                'thongBao'  => 'Tạo tài khoản nhân viên thành công',
                'duLieu'    => ['maTaiKhoan' => $maTaiKhoan, 'maNhanVien' => $maNhanVien, 'vaiTro' => $vaiTro],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('taoTaiKhoanNhanVien: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể tạo tài khoản nhân viên', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC52: layThongTinNhanVien(maTaiKhoan) & capNhatNhanVien(maTaiKhoan, hoTen, soDienThoai, vaiTro)
    public function layThongTinNhanVien(int $maTaiKhoan): array
    {
        $nv = NhanVien::timTheoTaiKhoan($maTaiKhoan, $this->pdo);
        if (!$nv) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy hồ sơ nhân viên', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin nhân viên thành công', 'duLieu' => $nv];
    }

    public function capNhatNhanVien(int $maTaiKhoan, array $duLieu): array
    {
        $nvHienTai = NhanVien::timTheoTaiKhoan($maTaiKhoan, $this->pdo);
        if (!$nvHienTai) {
            return ['thanhCong' => false, 'thongBao' => 'Chỉ được phép cập nhật thông tin/vai trò của tài khoản Nhân viên nội bộ', 'duLieu' => null, 'maHttp' => 400];
        }

        $vaiTroMoi = trim((string)($duLieu['vaiTro'] ?? $nvHienTai['vaiTro']));
        $hoTenMoi = trim((string)($duLieu['hoTen'] ?? $nvHienTai['hoTen']));
        $sdtMoi = isset($duLieu['soDienThoai']) ? trim((string)$duLieu['soDienThoai']) : $nvHienTai['soDienThoai'];

        // Chặn đổi nhân viên thành 'Khách hàng'
        if (!TaiKhoan::kiemTraHopLeCapNhat($maTaiKhoan, $vaiTroMoi) || $hoTenMoi === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vai trò nhân viên không hợp lệ (chỉ nhận Ban Quản lý, Nhân viên Kho, Nhân viên Bán hàng / CSKH) hoặc họ tên trống', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            batDauGiaoDich($this->pdo);
            NhanVien::capNhat($maTaiKhoan, $hoTenMoi, $sdtMoi, $this->pdo);
            $doiVaiTro = ($vaiTroMoi !== $nvHienTai['vaiTro']);
            if ($doiVaiTro) {
                TaiKhoan::capNhatVaiTro($maTaiKhoan, $vaiTroMoi, $this->pdo);
                // UC52 bước 6: Hủy toàn bộ PhienDangNhap cũ khi đổi vai trò nhân viên!
                PhienDangNhap::huyPhienTheoTaiKhoan($maTaiKhoan, $this->pdo);
            }
            xacNhanGiaoDich($this->pdo);

            return [
                'thanhCong' => true,
                'thongBao'  => 'Cập nhật thông tin nhân viên thành công' . ($doiVaiTro ? ' và đã thu hồi các phiên đăng nhập cũ' : ''),
                'duLieu'    => ['maTaiKhoan' => $maTaiKhoan, 'hoTen' => $hoTenMoi, 'soDienThoai' => $sdtMoi, 'vaiTro' => $vaiTroMoi],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('capNhatNhanVien: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể cập nhật thông tin nhân viên', 'duLieu' => null, 'maHttp' => 500];
        }
    }
}
