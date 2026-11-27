<?php
// Entity BCE: ChiTietPhieuNhap — Bảng ChiTietPhieuNhap

class ChiTietPhieuNhap
{
    public const TEN_BANG = 'ChiTietPhieuNhap';
    public const KHOA_CHINH = 'maChiTiet';

    private ?int $maChiTiet = null;
    private ?int $maPhieuNhap = null;
    private ?int $maDienThoai = null;
    private int $soLuong = 0;
    private float $donGiaNhap = 0.0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maChiTiet', $duLieu)) {
            $this->maChiTiet = isset($duLieu['maChiTiet']) && $duLieu['maChiTiet'] !== '' ? (int)$duLieu['maChiTiet'] : null;
        }
        if (array_key_exists('maPhieuNhap', $duLieu)) {
            $this->maPhieuNhap = isset($duLieu['maPhieuNhap']) && $duLieu['maPhieuNhap'] !== '' ? (int)$duLieu['maPhieuNhap'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('soLuong', $duLieu)) {
            $this->soLuong = (int)($duLieu['soLuong'] ?? 0);
        }
        if (array_key_exists('donGiaNhap', $duLieu)) {
            $this->donGiaNhap = (float)($duLieu['donGiaNhap'] ?? 0.0);
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
            'maPhieuNhap' => $this->maPhieuNhap,
            'maDienThoai' => $this->maDienThoai,
            'soLuong' => $this->soLuong,
            'donGiaNhap' => $this->donGiaNhap,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraHopLe(int $soLuong, float $donGiaNhap): bool
    {
        return $soLuong > 0 && $donGiaNhap > 0;
    }

    public static function luu(int $maPhieuNhap, int $maDienThoai, int $soLuong, float $donGiaNhap, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO ChiTietPhieuNhap (maPhieuNhap, maDienThoai, soLuong, donGiaNhap) VALUES (?, ?, ?, ?)")
           ->execute([$maPhieuNhap, $maDienThoai, $soLuong, $donGiaNhap]);
        return (int)$db->lastInsertId();
    }

    public static function timTheoPhieuNhap(?int $maPhieuNhap = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        if ($maPhieuNhap !== null) {
            $stmt = $db->prepare("SELECT ct.*, d.ten AS tenDienThoai FROM ChiTietPhieuNhap ct LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai WHERE ct.maPhieuNhap = ?");
            $stmt->execute([$maPhieuNhap]);
            return $stmt->fetchAll();
        }
        return $db->query("SELECT ct.*, d.ten AS tenDienThoai FROM ChiTietPhieuNhap ct LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai")->fetchAll();
    }

}
