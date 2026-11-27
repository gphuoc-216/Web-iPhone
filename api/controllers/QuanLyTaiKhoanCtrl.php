<?php
// Controller BCE: QuanLyTaiKhoanCtrl (UC48 - UC52)
// Đồng bộ 1-1 Báo cáo v4: Hình 6, Bảng 57 - Bảng 61, Sequence Hình 56 - Hình 60, Phụ lục A (Bảng 249)

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

    // [UC48 - Hình 56] QuanLyTaiKhoanCtrl::layDanhSachTaiKhoan -> TaiKhoan::timTatCa
    public function layDanhSachTaiKhoan(): array
    {
        $ds = array_map([$this, 'chuanHoaTaiKhoan'], TaiKhoan::timTatCa($this->pdo));
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách tài khoản thành công', 'duLieu' => $ds];
    }

    // [UC48 - Hình 56] QuanLyTaiKhoanCtrl::timKiemTaiKhoan -> TaiKhoan::timTheoTuKhoa
    public function timKiemTaiKhoan(string $tuKhoa): array
    {
        $ds = array_map([$this, 'chuanHoaTaiKhoan'], TaiKhoan::timTheoTuKhoa($tuKhoa, $this->pdo));
        $thongBao = empty($ds) ? 'Không tìm thấy tài khoản phù hợp' : 'Tìm kiếm tài khoản thành công';
        return ['thanhCong' => true, 'thongBao' => $thongBao, 'duLieu' => $ds];
    }

    // [UC48 - Hình 56] QuanLyTaiKhoanCtrl::layChiTietTaiKhoan -> TaiKhoan::timTheoMa
    public function layChiTietTaiKhoan(int $maTaiKhoan): array
    {
        $tk = TaiKhoan::timTheoMa($maTaiKhoan, $this->pdo);
        if (!$tk) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy tài khoản', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết tài khoản thành công', 'duLieu' => $this->chuanHoaTaiKhoan($tk)];
    }

    // [UC49 - Hình 57] QuanLyTaiKhoanCtrl::khoaTaiKhoan -> TaiKhoan::kiemTraQuyenKhoa -> TaiKhoan::capNhatTrangThai -> PhienDangNhap::huyPhienTheoTaiKhoan -> TaiKhoan::timTatCa
    public function khoaTaiKhoan(int $maTaiKhoan, ?string $lyDoKhoa, int $maTaiKhoanThucHien = 0): array
    {
        if (!TaiKhoan::kiemTraQuyenKhoa($maTaiKhoanThucHien, $maTaiKhoan, $this->pdo)) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Bạn không có quyền thực hiện thao tác này',
                'duLieu'    => null,
                'maHttp'    => 403,
            ];
        }
        $lyDoSach = trim((string)($lyDoKhoa ?: 'Vi phạm quy định hệ thống'));
        TaiKhoan::capNhatTrangThai($maTaiKhoan, 'Bị khóa', $lyDoSach, $this->pdo);
        PhienDangNhap::huyPhienTheoTaiKhoan($maTaiKhoan, $this->pdo);
        $danhSachMoi = array_map([$this, 'chuanHoaTaiKhoan'], TaiKhoan::timTatCa($this->pdo));

        return [
            'thanhCong' => true,
            'thongBao'  => 'Khóa tài khoản thành công',
            'duLieu'    => [
                'maTaiKhoan'       => $maTaiKhoan,
                'trangThai'        => 'Bị khóa',
                'lyDoKhoa'         => $lyDoSach,
                'danhSachTaiKhoan' => $danhSachMoi,
            ],
        ];
    }

    // [UC50 - Hình 58] QuanLyTaiKhoanCtrl::moKhoaTaiKhoan -> TaiKhoan::capNhatTrangThai -> TaiKhoan::timTatCa
    public function moKhoaTaiKhoan(int $maTaiKhoan): array
    {
        if (!TaiKhoan::timTheoMa($maTaiKhoan, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy tài khoản cần mở khóa', 'duLieu' => null, 'maHttp' => 404];
        }
        TaiKhoan::capNhatTrangThai($maTaiKhoan, 'Hoạt động', null, $this->pdo);
        $danhSachMoi = array_map([$this, 'chuanHoaTaiKhoan'], TaiKhoan::timTatCa($this->pdo));

        return [
            'thanhCong' => true,
            'thongBao'  => 'Mở khóa tài khoản thành công',
            'duLieu'    => [
                'maTaiKhoan'       => $maTaiKhoan,
                'trangThai'        => 'Hoạt động',
                'danhSachTaiKhoan' => $danhSachMoi,
            ],
        ];
    }

    // [UC51 - Hình 59] QuanLyTaiKhoanCtrl::layDanhSachNhanVien -> NhanVien::timTatCa
    public function layDanhSachNhanVien(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách nhân viên thành công', 'duLieu' => NhanVien::timTatCa($this->pdo)];
    }

    // [UC51 - Hình 59] QuanLyTaiKhoanCtrl::taoTaiKhoanNhanVien -> TaiKhoan::kiemTraHopLe -> TaiKhoan::kiemTraTonTai -> TaiKhoan::luu -> NhanVien::luu -> NhanVien::timTatCa
    public function taoTaiKhoanNhanVien($hoTenHoacDuLieu, ?string $email = null, ?string $soDienThoai = null, ?string $vaiTro = null, ?string $matKhauTam = null): array
    {
        if (is_array($hoTenHoacDuLieu)) {
            $d = $hoTenHoacDuLieu;
            $hoTen = trim((string)($d['hoTen'] ?? ''));
            $email = trim((string)($d['email'] ?? $d['dinhDanh'] ?? ''));
            $soDienThoai = trim((string)($d['soDienThoai'] ?? ''));
            $vaiTro = trim((string)($d['vaiTro'] ?? 'Nhân viên Bán hàng / CSKH'));
            $matKhauTam = trim((string)($d['matKhauTam'] ?? $d['matKhau'] ?? ''));
        } else {
            $hoTen = trim((string)$hoTenHoacDuLieu);
            $email = trim((string)$email);
            $soDienThoai = trim((string)$soDienThoai);
            $vaiTro = trim((string)($vaiTro ?? 'Nhân viên Bán hàng / CSKH'));
            $matKhauTam = trim((string)$matKhauTam);
        }

        $ketQuaHopLe = $hoTen !== '' && TaiKhoan::kiemTraHopLe($email, $soDienThoai, $vaiTro, $matKhauTam);
        $daTonTai = $email !== '' && TaiKhoan::kiemTraTonTai($email, $soDienThoai, $this->pdo);

        if ($daTonTai) {
            return ['thanhCong' => false, 'thongBao' => 'Email hoặc số điện thoại đã tồn tại trong hệ thống', 'duLieu' => null, 'maHttp' => 409];
        }
        if (!$ketQuaHopLe) {
            return ['thanhCong' => false, 'thongBao' => 'Dữ liệu thiếu, sai định dạng hoặc mật khẩu tạm chưa đủ độ mạnh (tối thiểu 6 ký tự)', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            batDauGiaoDich($this->pdo);
            $maTaiKhoan = TaiKhoan::luu($email, password_hash($matKhauTam, PASSWORD_BCRYPT), $vaiTro, 'Hoạt động', $this->pdo);
            $maNhanVien = NhanVien::luu($maTaiKhoan, $hoTen, $soDienThoai, $this->pdo);
            xacNhanGiaoDich($this->pdo);

            $danhSachNv = NhanVien::timTatCa($this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Tạo tài khoản nhân viên thành công',
                'duLieu'    => [
                    'maTaiKhoan'       => $maTaiKhoan,
                    'maNhanVien'       => $maNhanVien,
                    'vaiTro'           => $vaiTro,
                    'danhSachNhanVien' => $danhSachNv,
                ],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('taoTaiKhoanNhanVien: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể tạo tài khoản nhân viên', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // [UC52 - Hình 60] QuanLyTaiKhoanCtrl::layThongTinNhanVien -> NhanVien::timTheoTaiKhoan -> TaiKhoan::layVaiTro
    public function layThongTinNhanVien(int $maTaiKhoan): array
    {
        $nv = NhanVien::timTheoTaiKhoan($maTaiKhoan, $this->pdo);
        $vaiTro = TaiKhoan::layVaiTro($maTaiKhoan, $this->pdo);
        if (!$nv || !in_array($vaiTro, ['Nhân viên Bán hàng / CSKH', 'Nhân viên Kho'], true)) {
            return ['thanhCong' => false, 'thongBao' => 'Tài khoản được chọn không phải tài khoản nhân viên nên từ chối chỉnh sửa vai trò', 'duLieu' => null, 'maHttp' => 400];
        }
        $nv['vaiTro'] = $vaiTro;
        return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin nhân viên thành công', 'duLieu' => $nv];
    }

    // [UC52 - Hình 60] QuanLyTaiKhoanCtrl::capNhatNhanVien -> TaiKhoan::kiemTraHopLeCapNhat -> NhanVien::capNhat -> TaiKhoan::capNhatVaiTro -> PhienDangNhap::huyPhienTheoTaiKhoan
    public function capNhatNhanVien(int $maTaiKhoan, $hoTenHoacDuLieu, ?string $soDienThoai = null, ?string $vaiTro = null): array
    {
        $nvHienTai = NhanVien::timTheoTaiKhoan($maTaiKhoan, $this->pdo);
        $vaiTroCu = TaiKhoan::layVaiTro($maTaiKhoan, $this->pdo);
        if (!$nvHienTai || !in_array($vaiTroCu, ['Nhân viên Bán hàng / CSKH', 'Nhân viên Kho'], true)) {
            return ['thanhCong' => false, 'thongBao' => 'Tài khoản được chọn không phải tài khoản nhân viên (là Khách hàng hoặc Ban Quản lý), từ chối chỉnh sửa vai trò', 'duLieu' => null, 'maHttp' => 400];
        }

        $daXacNhanMatQuyen = true;
        if (is_array($hoTenHoacDuLieu)) {
            $d = $hoTenHoacDuLieu;
            $hoTenMoi = array_key_exists('hoTen', $d) ? trim((string)$d['hoTen']) : (string)$nvHienTai['hoTen'];
            $sdtMoi = array_key_exists('soDienThoai', $d) ? trim((string)$d['soDienThoai']) : (string)($nvHienTai['soDienThoai'] ?? '');
            $vaiTroMoi = array_key_exists('vaiTro', $d) ? trim((string)$d['vaiTro']) : (string)$vaiTroCu;
            $daXacNhanMatQuyen = array_key_exists('xacNhanDoiVaiTro', $d) ? (bool)$d['xacNhanDoiVaiTro'] : true;
        } else {
            $hoTenMoi = trim((string)$hoTenHoacDuLieu);
            $sdtMoi = trim((string)($soDienThoai ?? $nvHienTai['soDienThoai'] ?? ''));
            $vaiTroMoi = trim((string)($vaiTro ?? $vaiTroCu));
        }

        if ($hoTenMoi === '' || !TaiKhoan::kiemTraHopLeCapNhat($maTaiKhoan, $sdtMoi, $vaiTroMoi, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Dữ liệu cập nhật không hợp lệ hoặc số điện thoại đã bị trùng', 'duLieu' => null, 'maHttp' => 400];
        }

        // Luồng phụ 5.2 (Hình 60): Cảnh báo nếu đổi vai trò khi nhân viên đang có phiếu kho nháp/chờ duyệt
        if ($vaiTroMoi !== $vaiTroCu && !$daXacNhanMatQuyen) {
            if (PhieuNhap::demPhieuDangXuLyTheoNhanVien((int)$nvHienTai['maNhanVien'], $this->pdo) > 0) {
                return [
                    'thanhCong' => false,
                    'thongBao'  => 'Thay đổi vai trò làm nhân viên mất quyền xử lý phiếu kho đang dở dang. Vui lòng xác nhận để tiếp tục',
                    'duLieu'    => ['canhBaoMatQuyen' => true],
                    'maHttp'    => 409,
                ];
            }
        }

        try {
            batDauGiaoDich($this->pdo);
            NhanVien::capNhat($maTaiKhoan, $hoTenMoi, $sdtMoi, $this->pdo);
            TaiKhoan::capNhatVaiTro($maTaiKhoan, $vaiTroMoi, $this->pdo);
            PhienDangNhap::huyPhienTheoTaiKhoan($maTaiKhoan, $this->pdo);
            xacNhanGiaoDich($this->pdo);

            return [
                'thanhCong' => true,
                'thongBao'  => 'Cập nhật tài khoản nhân viên thành công',
                'duLieu'    => ['maTaiKhoan' => $maTaiKhoan, 'hoTen' => $hoTenMoi, 'soDienThoai' => $sdtMoi, 'vaiTro' => $vaiTroMoi],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('capNhatNhanVien: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể cập nhật tài khoản nhân viên', 'duLieu' => null, 'maHttp' => 500];
        }
    }
}
