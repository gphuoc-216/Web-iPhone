<?php
// Entity BCE: ChiTietPhieuXuat — Bảng ChiTietPhieuXuat

class ChiTietPhieuXuat
{
    public const TEN_BANG = 'ChiTietPhieuXuat';
    public const KHOA_CHINH = 'maChiTiet';

    private ?int $maChiTiet = null;
    private ?int $maPhieuXuat = null;
    private ?int $maDienThoai = null;
    private int $soLuong = 0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maChiTiet', $duLieu)) {
            $this->maChiTiet = isset($duLieu['maChiTiet']) && $duLieu['maChiTiet'] !== '' ? (int)$duLieu['maChiTiet'] : null;
        }
        if (array_key_exists('maPhieuXuat', $duLieu)) {
            $this->maPhieuXuat = isset($duLieu['maPhieuXuat']) && $duLieu['maPhieuXuat'] !== '' ? (int)$duLieu['maPhieuXuat'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('soLuong', $duLieu)) {
            $this->soLuong = (int)($duLieu['soLuong'] ?? 0);
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
            'maChiTiet' => $this->maChiTiet,
            'maPhieuXuat' => $this->maPhieuXuat,
            'maDienThoai' => $this->maDienThoai,
            'soLuong' => $this->soLuong,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function luu(int $maPhieuXuat, int $maDienThoai, int $soLuong, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO ChiTietPhieuXuat (maPhieuXuat, maDienThoai, soLuong) VALUES (?, ?, ?)")
           ->execute([$maPhieuXuat, $maDienThoai, $soLuong]);
        return (int)$db->lastInsertId();
    }

    public static function timTheoPhieuXuat(?int $maPhieuXuat = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        if ($maPhieuXuat !== null) {
            $stmt = $db->prepare("SELECT ct.*, d.ten AS tenDienThoai FROM ChiTietPhieuXuat ct LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai WHERE ct.maPhieuXuat = ?");
            $stmt->execute([$maPhieuXuat]);
            return $stmt->fetchAll();
        }
        return $db->query("SELECT ct.*, d.ten AS tenDienThoai FROM ChiTietPhieuXuat ct LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai")->fetchAll();
    }

}
