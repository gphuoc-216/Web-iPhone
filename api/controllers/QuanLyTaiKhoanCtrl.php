<?php
// Controller BCE: QuanLyTaiKhoanCtrl (UC48 - UC52)

class QuanLyTaiKhoanCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDanhSachTaiKhoan(): array
    {
        $sql = "SELECT tk.maTaiKhoan, tk.dinhDanh, tk.vaiTro, tk.trangThai, tk.lyDoKhoa,
                       COALESCE(kh.hoTen, nv.hoTen) AS hoTen,
                       COALESCE(kh.soDienThoai, nv.soDienThoai) AS soDienThoai,
                       kh.email
                FROM TaiKhoan tk
                LEFT JOIN KhachHang kh ON kh.maTaiKhoan = tk.maTaiKhoan
                LEFT JOIN NhanVien nv ON nv.maTaiKhoan = tk.maTaiKhoan
                ORDER BY tk.maTaiKhoan DESC";

        $ketQua = [];
        foreach ($this->pdo->query($sql)->fetchAll() as $dong) {
            $ketQua[] = [
                'maTaiKhoan'  => (int)$dong['maTaiKhoan'],
                'dinhDanh'    => $dong['dinhDanh'],
                'vaiTro'      => $dong['vaiTro'],
                'trangThai'   => $dong['trangThai'],
                'lyDoKhoa'    => $dong['lyDoKhoa'],
                'hoTen'       => $dong['hoTen'] ?? '',
                'soDienThoai' => $dong['soDienThoai'] ?? null,
                'email'       => $dong['email'] ?? null,
            ];
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách tài khoản thành công', 'duLieu' => $ketQua];
    }

    public function taoTaiKhoanNhanVien(array $duLieu): array
    {
        $dinhDanh = trim((string)($duLieu['dinhDanh'] ?? ''));
        $matKhau = trim((string)($duLieu['matKhau'] ?? '123456'));
        $vaiTro = trim((string)($duLieu['vaiTro'] ?? 'Nhân viên Bán hàng / CSKH'));
        $hoTen = trim((string)($duLieu['hoTen'] ?? ''));
        $soDienThoai = isset($duLieu['soDienThoai']) ? trim((string)$duLieu['soDienThoai']) : null;

        $vaiTroHopLe = ['Ban Quản lý', 'Nhân viên Bán hàng / CSKH', 'Nhân viên Kho'];
        if ($dinhDanh === '' || $hoTen === '' || !in_array($vaiTro, $vaiTroHopLe, true)) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin tài khoản nhân viên hoặc vai trò không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare(
                "INSERT INTO TaiKhoan (dinhDanh, matKhauDaBam, vaiTro, trangThai) VALUES (?, ?, ?, 'Hoạt động')"
            )->execute([$dinhDanh, password_hash($matKhau, PASSWORD_BCRYPT), $vaiTro]);
            $maTaiKhoan = (int)$this->pdo->lastInsertId();

            $this->pdo->prepare("INSERT INTO NhanVien (maTaiKhoan, hoTen, soDienThoai) VALUES (?, ?, ?)")
                ->execute([$maTaiKhoan, $hoTen, $soDienThoai]);
            $maNhanVien = (int)$this->pdo->lastInsertId();

            $this->pdo->commit();
            return [
                'thanhCong' => true,
                'thongBao'  => 'Tạo tài khoản nhân viên thành công',
                'duLieu'    => ['maTaiKhoan' => $maTaiKhoan, 'maNhanVien' => $maNhanVien, 'vaiTro' => $vaiTro],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Không thể tạo tài khoản nhân viên: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 409];
        }
    }

    public function capNhatVaiTro(int $maTaiKhoan, string $vaiTroMoi): array
    {
        $vaiTroHopLe = ['Khách hàng', 'Nhân viên Bán hàng / CSKH', 'Nhân viên Kho', 'Ban Quản lý'];
        if (!in_array($vaiTroMoi, $vaiTroHopLe, true)) {
            return ['thanhCong' => false, 'thongBao' => 'Vai trò không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $this->pdo->prepare("UPDATE TaiKhoan SET vaiTro = ? WHERE maTaiKhoan = ?")->execute([$vaiTroMoi, $maTaiKhoan]);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật vai trò thành công', 'duLieu' => ['maTaiKhoan' => $maTaiKhoan, 'vaiTro' => $vaiTroMoi]];
    }

    public function khoaHoacMoKhoaTaiKhoan(int $maTaiKhoan, bool $khoa, ?string $lyDoKhoa = null, ?int $maTaiKhoanHienTai = null): array
    {
        if ($khoa && $maTaiKhoanHienTai !== null && $maTaiKhoan === $maTaiKhoanHienTai) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Không thể tự khóa tài khoản của chính mình',
                'duLieu'    => null,
                'maHttp'    => 400,
            ];
        }

        $trangThaiMoi = $khoa ? 'Bị khóa' : 'Hoạt động';
        $this->pdo->prepare("UPDATE TaiKhoan SET trangThai = ?, lyDoKhoa = ? WHERE maTaiKhoan = ?")
            ->execute([$trangThaiMoi, $khoa ? ($lyDoKhoa ?: 'Khóa bởi quản trị viên') : null, $maTaiKhoan]);

        if ($khoa) {
            $this->pdo->prepare("UPDATE PhienDangNhap SET trangThai = 'Đã hủy' WHERE maTaiKhoan = ?")->execute([$maTaiKhoan]);
        }

        return [
            'thanhCong' => true,
            'thongBao'  => $khoa ? 'Đã khóa tài khoản và thu hồi toàn bộ phiên đăng nhập' : 'Đã mở khóa tài khoản thành công',
            'duLieu'    => ['maTaiKhoan' => $maTaiKhoan, 'trangThai' => $trangThaiMoi],
        ];
    }
}
