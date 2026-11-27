<?php
// Entity BCE: ChiTietGioHang — Bảng ChiTietGioHang

class ChiTietGioHang
{
    public const TEN_BANG = 'ChiTietGioHang';
    public const KHOA_CHINH = 'maChiTietGioHang';

    private ?int $maChiTietGioHang = null;
    private ?int $maGioHang = null;
    private ?int $maDienThoai = null;
    private int $soLuong = 0;
    private float $donGia = 0.0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maChiTietGioHang', $duLieu)) {
            $this->maChiTietGioHang = isset($duLieu['maChiTietGioHang']) && $duLieu['maChiTietGioHang'] !== '' ? (int)$duLieu['maChiTietGioHang'] : null;
        }
        if (array_key_exists('maGioHang', $duLieu)) {
            $this->maGioHang = isset($duLieu['maGioHang']) && $duLieu['maGioHang'] !== '' ? (int)$duLieu['maGioHang'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('soLuong', $duLieu)) {
            $this->soLuong = (int)($duLieu['soLuong'] ?? 0);
        }
        if (array_key_exists('donGia', $duLieu)) {
            $this->donGia = (float)($duLieu['donGia'] ?? 0.0);
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
            'maChiTietGioHang' => $this->maChiTietGioHang,
            'maGioHang' => $this->maGioHang,
            'maDienThoai' => $this->maDienThoai,
            'soLuong' => $this->soLuong,
            'donGia' => $this->donGia,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function timTheoGioHang(int $maGioHang, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT ct.*, d.ten AS tenDienThoai, d.hinhAnh, d.giaBan AS giaGoc, COALESCE(tk.tonKhaDung, 0) AS tonKhaDung
             FROM ChiTietGioHang ct
             JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai
             LEFT JOIN TonKho tk ON tk.maDienThoai = ct.maDienThoai AND tk.maKho = 1
             WHERE ct.maGioHang = ?"
        );
        $stmt->execute([$maGioHang]);
        return $stmt->fetchAll();
    }

    public static function timMotMuc(int $maGioHang, int $maDienThoai, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM ChiTietGioHang WHERE maGioHang = ? AND maDienThoai = ? LIMIT 1");
        $stmt->execute([$maGioHang, $maDienThoai]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function demTheoGioHang(int $maGioHang, ?PDO $pdo = null): int
    {
        $stmt = self::pdo($pdo)->prepare("SELECT COUNT(*) FROM ChiTietGioHang WHERE maGioHang = ?");
        $stmt->execute([$maGioHang]);
        return (int)$stmt->fetchColumn();
    }

    public static function themHoacCongDon(int $maGioHang, int $maDienThoai, int $soLuong, float $donGia, ?int $gioiHanTon = null, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $hienCo = self::timMotMuc($maGioHang, $maDienThoai, $db);
        if ($hienCo) {
            $slMoi = (int)$hienCo['soLuong'] + $soLuong;
            if ($gioiHanTon !== null) $slMoi = min($gioiHanTon, $slMoi);
            return $db->prepare("UPDATE ChiTietGioHang SET soLuong = ?, donGia = ? WHERE maChiTietGioHang = ?")
                      ->execute([$slMoi, $donGia, $hienCo['maChiTietGioHang']]);
        }
        $slMoi = ($gioiHanTon !== null) ? min($gioiHanTon, $soLuong) : $soLuong;
        return $db->prepare("INSERT INTO ChiTietGioHang (maGioHang, maDienThoai, soLuong, donGia) VALUES (?, ?, ?, ?)")
                  ->execute([$maGioHang, $maDienThoai, $slMoi, $donGia]);
    }

    public static function capNhatSoLuong(int $maChiTietGioHang, int $soLuong, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE ChiTietGioHang SET soLuong = ? WHERE maChiTietGioHang = ?")
            ->execute([$soLuong, $maChiTietGioHang]);
    }

    public static function capNhatSoLuongTheoSanPham(int $maGioHang, int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE ChiTietGioHang SET soLuong = ? WHERE maGioHang = ? AND maDienThoai = ?")
            ->execute([$soLuong, $maGioHang, $maDienThoai]);
    }

    public static function xoa(int $maChiTietGioHang, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("DELETE FROM ChiTietGioHang WHERE maChiTietGioHang = ?")->execute([$maChiTietGioHang]);
    }

    public static function xoaTheoSanPham(int $maGioHang, int $maDienThoai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("DELETE FROM ChiTietGioHang WHERE maGioHang = ? AND maDienThoai = ?")
            ->execute([$maGioHang, $maDienThoai]);
    }

}
