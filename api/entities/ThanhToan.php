<?php
// Entity BCE: ThanhToan — Bảng ThanhToan

class ThanhToan
{
    public const TEN_BANG = 'ThanhToan';
    public const KHOA_CHINH = 'maThanhToan';

    private ?int $maThanhToan = null;
    private ?int $maGioHang = null;
    private ?int $maDonHang = null;
    private string $phuongThuc = 'Thanh toán khi nhận hàng';
    private float $soTien = 0.0;
    private float $soTienHoan = 0.0;
    private string $trangThai = 'Chờ thanh toán';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maThanhToan', $duLieu)) {
            $this->maThanhToan = isset($duLieu['maThanhToan']) && $duLieu['maThanhToan'] !== '' ? (int)$duLieu['maThanhToan'] : null;
        }
        if (array_key_exists('maGioHang', $duLieu)) {
            $this->maGioHang = isset($duLieu['maGioHang']) && $duLieu['maGioHang'] !== '' ? (int)$duLieu['maGioHang'] : null;
        }
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
        }
        if (array_key_exists('phuongThuc', $duLieu)) {
            $this->phuongThuc = (string)($duLieu['phuongThuc'] ?? '');
        }
        if (array_key_exists('soTien', $duLieu)) {
            $this->soTien = (float)($duLieu['soTien'] ?? 0.0);
        }
        if (array_key_exists('soTienHoan', $duLieu)) {
            $this->soTienHoan = (float)($duLieu['soTienHoan'] ?? 0.0);
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
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
            'maThanhToan' => $this->maThanhToan,
            'maGioHang' => $this->maGioHang,
            'maDonHang' => $this->maDonHang,
            'phuongThuc' => $this->phuongThuc,
            'soTien' => $this->soTien,
            'soTienHoan' => $this->soTienHoan,
            'trangThai' => $this->trangThai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function timTheoMa(int $maThanhToan, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT * FROM ThanhToan WHERE maThanhToan = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maThanhToan]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function timTheoDonHang(int $maDonHang, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT * FROM ThanhToan WHERE maDonHang = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maDonHang]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function ghiNhanPhuongThuc(int $maGioHang, string $phuongThuc, float $soTien, ?int $maDonHang = null, string $trangThai = 'Chờ thanh toán', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO ThanhToan (maGioHang, maDonHang, phuongThuc, soTien, soTienHoan, trangThai) VALUES (?, ?, ?, ?, 0, ?)")
           ->execute([$maGioHang, $maDonHang, $phuongThuc, $soTien, $trangThai]);
        return (int)$db->lastInsertId();
    }

    public static function ganDonHang(int $maThanhToan, int $maDonHang, string $trangThai = 'Thành công', ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE ThanhToan SET maDonHang = ?, trangThai = ? WHERE maThanhToan = ?")
            ->execute([$maDonHang, $trangThai, $maThanhToan]);
    }

    public static function capNhatTrangThai(int $maDonHang, string $trangThai, ?string $chiKhiTrangThai = null, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if ($chiKhiTrangThai !== null) {
            return $db->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maDonHang = ? AND trangThai = ?")
                      ->execute([$trangThai, $maDonHang, $chiKhiTrangThai]);
        }
        return $db->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maDonHang = ?")->execute([$trangThai, $maDonHang]);
    }

    public static function huyGiaoDich(int $maThanhToan, string $trangThai = 'Thất bại', ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maThanhToan = ?")->execute([$trangThai, $maThanhToan]);
    }

    public static function kiemTraDaThanhToanTrucTuyen(int $maDonHang, ?PDO $pdo = null): bool
    {
        $tt = self::timTheoDonHang($maDonHang, false, $pdo);
        return $tt !== null && $tt['trangThai'] === 'Thành công';
    }

    public static function danhDauCanHoanTien(int $maDonHang, ?PDO $pdo = null): string
    {
        $db = self::pdo($pdo);
        $tt = self::timTheoDonHang($maDonHang, true, $db);
        if (!$tt) return 'Đã hủy';
        $moi = ($tt['trangThai'] === 'Thành công') ? 'Cần hoàn tiền' : 'Đã hủy';
        $db->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maThanhToan = ?")->execute([$moi, $tt['maThanhToan']]);
        return $moi;
    }

    public static function kiemTraSoTienHoan(int $maDonHang, float $soTienHoan, ?PDO $pdo = null): bool
    {
        $tt = self::timTheoDonHang($maDonHang, false, $pdo);
        if (!$tt) return false;
        return $soTienHoan > 0 && $soTienHoan <= (float)$tt['soTien'];
    }

    public static function thucHienHoanTienMoPhong(int $maDonHang, float $soTienHoan, bool $thanhCong = true, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if ($thanhCong) {
            return $db->prepare("UPDATE ThanhToan SET soTienHoan = ?, trangThai = 'Đã hoàn tiền' WHERE maDonHang = ?")
                      ->execute([$soTienHoan, $maDonHang]);
        }
        return $db->prepare("UPDATE ThanhToan SET trangThai = 'Hoàn tiền lỗi' WHERE maDonHang = ?")->execute([$maDonHang]);
    }

    public static function thucHienThanhToanMoPhong(int $maGioHang, string $phuongThuc): bool
    {
        return $maGioHang > 0 && $phuongThuc !== '';
    }

    public static function layTongTienCanThanhToan(int $maGioHang, ?PDO $pdo = null): float
    {
        $stmt = self::pdo($pdo)->prepare("SELECT soTien FROM ThanhToan WHERE maGioHang = ? ORDER BY maThanhToan DESC LIMIT 1");
        $stmt->execute([$maGioHang]);
        return (float)($stmt->fetchColumn() ?: 0.0);
    }

    public static function layKetQuaThanhToan(int $maGioHang, ?PDO $pdo = null): string
    {
        $stmt = self::pdo($pdo)->prepare("SELECT trangThai FROM ThanhToan WHERE maGioHang = ? ORDER BY maThanhToan DESC LIMIT 1");
        $stmt->execute([$maGioHang]);
        return (string)($stmt->fetchColumn() ?: 'Chờ thanh toán');
    }

}
