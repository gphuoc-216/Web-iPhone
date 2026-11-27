<?php
// Entity BCE: NhanVien — Bảng NhanVien

class NhanVien
{
    public const TEN_BANG = 'NhanVien';
    public const KHOA_CHINH = 'maNhanVien';

    private ?int $maNhanVien = null;
    private ?int $maTaiKhoan = null;
    private string $hoTen = '';
    private ?string $soDienThoai = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maNhanVien', $duLieu)) {
            $this->maNhanVien = isset($duLieu['maNhanVien']) && $duLieu['maNhanVien'] !== '' ? (int)$duLieu['maNhanVien'] : null;
        }
        if (array_key_exists('maTaiKhoan', $duLieu)) {
            $this->maTaiKhoan = isset($duLieu['maTaiKhoan']) && $duLieu['maTaiKhoan'] !== '' ? (int)$duLieu['maTaiKhoan'] : null;
        }
        if (array_key_exists('hoTen', $duLieu)) {
            $this->hoTen = (string)($duLieu['hoTen'] ?? '');
        }
        if (array_key_exists('soDienThoai', $duLieu)) {
            $this->soDienThoai = isset($duLieu['soDienThoai']) && $duLieu['soDienThoai'] !== '' ? (string)$duLieu['soDienThoai'] : null;
        }
    }

    public function __get(string $tenThuocTinh)
    {
        if (property_exists($this, $tenThuocTinh)) {
            return $this->$tenThuocTinh;
        }
        return null;
    }

    public function __set(string $tenThuocTinh, $giaTri): void
    {
        if (property_exists($this, $tenThuocTinh)) {
            $this->$tenThuocTinh = $giaTri;
        }
    }

    public function __isset(string $tenThuocTinh): bool
    {
        return property_exists($this, $tenThuocTinh) && $this->$tenThuocTinh !== null;
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maNhanVien' => $this->maNhanVien,
            'maTaiKhoan' => $this->maTaiKhoan,
            'hoTen' => $this->hoTen,
            'soDienThoai' => $this->soDienThoai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function luu(int $maTaiKhoan, string $hoTen, ?string $soDienThoai = null, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO NhanVien (maTaiKhoan, hoTen, soDienThoai) VALUES (?, ?, ?)")
           ->execute([$maTaiKhoan, trim($hoTen), $soDienThoai]);
        return (int)$db->lastInsertId();
    }

    public static function capNhat(int $maTaiKhoan, string $hoTen, ?string $soDienThoai = null, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE NhanVien SET hoTen = ?, soDienThoai = ? WHERE maTaiKhoan = ?")
            ->execute([trim($hoTen), $soDienThoai, $maTaiKhoan]);
    }

    public static function timTatCa(?PDO $pdo = null): array
    {
        return self::pdo($pdo)->query(
            "SELECT nv.*, tk.dinhDanh, tk.vaiTro, tk.trangThai
             FROM NhanVien nv
             JOIN TaiKhoan tk ON tk.maTaiKhoan = nv.maTaiKhoan
             ORDER BY nv.maNhanVien DESC"
        )->fetchAll();
    }

    public static function timTheoTaiKhoan(int $maTaiKhoan, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT nv.*, tk.dinhDanh, tk.vaiTro, tk.trangThai
             FROM NhanVien nv
             JOIN TaiKhoan tk ON tk.maTaiKhoan = nv.maTaiKhoan
             WHERE nv.maTaiKhoan = ? LIMIT 1"
        );
        $stmt->execute([$maTaiKhoan]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function layMaTaiKhoan(int $maNhanVien, ?PDO $pdo = null): ?int
    {
        $stmt = self::pdo($pdo)->prepare("SELECT maTaiKhoan FROM NhanVien WHERE maNhanVien = ? LIMIT 1");
        $stmt->execute([$maNhanVien]);
        $v = $stmt->fetchColumn();
        return $v !== false ? (int)$v : null;
    }

}
