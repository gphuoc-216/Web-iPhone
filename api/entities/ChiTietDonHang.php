<?php
// Entity BCE: ChiTietDonHang — Bảng ChiTietDonHang

class ChiTietDonHang
{
    public const TEN_BANG = 'ChiTietDonHang';
    public const KHOA_CHINH = 'maChiTietDonHang';

    private ?int $maChiTietDonHang = null;
    private ?int $maDonHang = null;
    private ?int $maDienThoai = null;
    private int $soLuong = 0;
    private float $donGia = 0.0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maChiTietDonHang', $duLieu)) {
            $this->maChiTietDonHang = isset($duLieu['maChiTietDonHang']) && $duLieu['maChiTietDonHang'] !== '' ? (int)$duLieu['maChiTietDonHang'] : null;
        }
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
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
            'maChiTietDonHang' => $this->maChiTietDonHang,
            'maDonHang' => $this->maDonHang,
            'maDienThoai' => $this->maDienThoai,
            'soLuong' => $this->soLuong,
            'donGia' => $this->donGia,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function luu(int $maDonHang, int $maDienThoai, int $soLuong, float $donGia, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO ChiTietDonHang (maDonHang, maDienThoai, soLuong, donGia) VALUES (?, ?, ?, ?)")
           ->execute([$maDonHang, $maDienThoai, $soLuong, $donGia]);
        return (int)$db->lastInsertId();
    }

    public static function timTheoDonHang(?int $maDonHang = null, ?int $maKhachHang = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        if ($maDonHang !== null) {
            $stmt = $db->prepare("SELECT ct.*, d.ten AS tenDienThoai, d.hinhAnh FROM ChiTietDonHang ct LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai WHERE ct.maDonHang = ?");
            $stmt->execute([$maDonHang]);
            return $stmt->fetchAll();
        }
        if ($maKhachHang !== null) {
            $stmt = $db->prepare("SELECT ct.*, d.ten AS tenDienThoai, d.hinhAnh FROM ChiTietDonHang ct JOIN DonHang dh ON dh.maDonHang = ct.maDonHang LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai WHERE dh.maKhachHang = ?");
            $stmt->execute([$maKhachHang]);
            return $stmt->fetchAll();
        }
        return $db->query("SELECT ct.*, d.ten AS tenDienThoai, d.hinhAnh FROM ChiTietDonHang ct LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai")->fetchAll();
    }

    public static function tongHopSoLuongBanTheoDonThanhCong(int $gioiHan = 10, ?PDO $pdo = null): array
    {
        return self::tongHopSoLuongBanTheoDieuKien(null, null, null, $gioiHan, $pdo);
    }

    public static function tongHopSoLuongBanTheoDieuKien(?string $tuNgay = null, ?string $denNgay = null, ?int $maDanhMuc = null, int $gioiHan = 10, ?PDO $pdo = null): array
    {
        $dk = ["dh.trangThai = 'Đã giao thành công'"];
        $ts = [];
        if ($tuNgay !== null && $tuNgay !== '') { $dk[] = "DATE(dh.ngayDat) >= ?"; $ts[] = $tuNgay; }
        if ($denNgay !== null && $denNgay !== '') { $dk[] = "DATE(dh.ngayDat) <= ?"; $ts[] = $denNgay; }
        if ($maDanhMuc !== null && $maDanhMuc > 0) { $dk[] = "d.maDanhMuc = ?"; $ts[] = $maDanhMuc; }

        $gioiHan = max(1, min(100, $gioiHan));
        $sql = "SELECT d.maDienThoai, d.ten, d.hang, d.maDanhMuc,
                       SUM(ct.soLuong) AS tongSoLuongBan,
                       SUM(ct.soLuong * ct.donGia) AS tongDoanhThu
                FROM ChiTietDonHang ct
                JOIN DonHang dh ON dh.maDonHang = ct.maDonHang
                JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai
                WHERE " . implode(' AND ', $dk) . "
                GROUP BY d.maDienThoai, d.ten, d.hang, d.maDanhMuc
                ORDER BY tongSoLuongBan DESC, tongDoanhThu DESC
                LIMIT {$gioiHan}";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($ts);
        return $stmt->fetchAll();
    }

}
