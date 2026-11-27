<?php
// Entity BCE: PhieuXuat — Bảng PhieuXuat

class PhieuXuat
{
    public const TEN_BANG = 'PhieuXuat';
    public const KHOA_CHINH = 'maPhieuXuat';

    private ?int $maPhieuXuat = null;
    private ?int $maDonHang = null;
    private ?string $ngayLap = null;
    private ?string $ghiChu = null;
    private string $trangThai = 'Nháp';
    private ?int $maNhanVien = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maPhieuXuat', $duLieu)) {
            $this->maPhieuXuat = isset($duLieu['maPhieuXuat']) && $duLieu['maPhieuXuat'] !== '' ? (int)$duLieu['maPhieuXuat'] : null;
        }
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
        }
        if (array_key_exists('ngayLap', $duLieu)) {
            $this->ngayLap = isset($duLieu['ngayLap']) && $duLieu['ngayLap'] !== '' ? (string)$duLieu['ngayLap'] : null;
        }
        if (array_key_exists('ghiChu', $duLieu)) {
            $this->ghiChu = isset($duLieu['ghiChu']) && $duLieu['ghiChu'] !== '' ? (string)$duLieu['ghiChu'] : null;
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
        }
        if (array_key_exists('maNhanVien', $duLieu)) {
            $this->maNhanVien = isset($duLieu['maNhanVien']) && $duLieu['maNhanVien'] !== '' ? (int)$duLieu['maNhanVien'] : null;
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
            'maPhieuXuat' => $this->maPhieuXuat,
            'maDonHang' => $this->maDonHang,
            'ngayLap' => $this->ngayLap,
            'ghiChu' => $this->ghiChu,
            'trangThai' => $this->trangThai,
            'maNhanVien' => $this->maNhanVien,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function timTatCa(?PDO $pdo = null): array
    {
        return self::timTheoDieuKien(null, null, null, $pdo);
    }

    public static function timTheoDieuKien(?string $trangThai = null, ?string $tuNgay = null, ?string $denNgay = null, ?PDO $pdo = null): array
    {
        $dk = ['1=1'];
        $ts = [];
        if ($trangThai !== null && $trangThai !== '') { $dk[] = "px.trangThai = ?"; $ts[] = $trangThai; }
        if ($tuNgay !== null && $tuNgay !== '') { $dk[] = "px.ngayLap >= ?"; $ts[] = $tuNgay; }
        if ($denNgay !== null && $denNgay !== '') { $dk[] = "px.ngayLap <= ?"; $ts[] = $denNgay; }

        $sql = "SELECT px.*, nv.hoTen AS tenNhanVien, dh.trangThai AS trangThaiDonHang
                FROM PhieuXuat px
                LEFT JOIN NhanVien nv ON nv.maNhanVien = px.maNhanVien
                LEFT JOIN DonHang dh ON dh.maDonHang = px.maDonHang
                WHERE " . implode(' AND ', $dk) . "
                ORDER BY px.maPhieuXuat DESC";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($ts);
        return $stmt->fetchAll();
    }

    public static function timTheoMa(int $maPhieuXuat, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT * FROM PhieuXuat WHERE maPhieuXuat = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maPhieuXuat]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function kiemTraDaCoPhieuXuat(int $maDonHang, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("SELECT 1 FROM PhieuXuat WHERE maDonHang = ? AND trangThai <> 'Đã hủy' LIMIT 1");
        $stmt->execute([$maDonHang]);
        return (bool)$stmt->fetchColumn();
    }

    public static function kiemTraDaXacNhan(int $maDonHang, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("SELECT 1 FROM PhieuXuat WHERE maDonHang = ? AND trangThai = 'Đã xuất' LIMIT 1");
        $stmt->execute([$maDonHang]);
        return (bool)$stmt->fetchColumn();
    }

    public static function kiemTraHopLe(int $maPhieuXuat, ?PDO $pdo = null): bool
    {
        $r = self::timTheoMa($maPhieuXuat, false, $pdo);
        return $r !== null && $r['trangThai'] === 'Nháp';
    }

    public static function luu(int $maDonHang, int $maNhanVien, ?string $ghiChu = null, string $trangThai = 'Nháp', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        // Nếu DB cũ còn cột UNIQUE maDonHang có phiếu Đã hủy -> tái sử dụng bản ghi đó an toàn
        $cuStmt = $db->prepare("SELECT maPhieuXuat FROM PhieuXuat WHERE maDonHang = ? AND trangThai = 'Đã hủy' LIMIT 1");
        $cuStmt->execute([$maDonHang]);
        $maCu = $cuStmt->fetchColumn();
        if ($maCu) {
            $db->prepare("DELETE FROM ChiTietPhieuXuat WHERE maPhieuXuat = ?")->execute([(int)$maCu]);
            $db->prepare("UPDATE PhieuXuat SET ngayLap = CURDATE(), ghiChu = ?, trangThai = ?, maNhanVien = ? WHERE maPhieuXuat = ?")
               ->execute([$ghiChu, $trangThai, $maNhanVien, (int)$maCu]);
            return (int)$maCu;
        }
        $db->prepare("INSERT INTO PhieuXuat (maDonHang, ngayLap, ghiChu, trangThai, maNhanVien) VALUES (?, CURDATE(), ?, ?, ?)")
           ->execute([$maDonHang, $ghiChu, $trangThai, $maNhanVien]);
        return (int)$db->lastInsertId();
    }

    public static function capNhatTrangThai(int $maPhieuXuat, string $trangThai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE PhieuXuat SET trangThai = ? WHERE maPhieuXuat = ?")->execute([$trangThai, $maPhieuXuat]);
    }

    public static function huyPhieuXuat(int $maPhieuXuat, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("UPDATE PhieuXuat SET trangThai = 'Đã hủy' WHERE maPhieuXuat = ? AND trangThai = 'Nháp'");
        $stmt->execute([$maPhieuXuat]);
        return $stmt->rowCount() > 0;
    }

    public static function huyPhieuXuatTheoDon(int $maDonHang, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE PhieuXuat SET trangThai = 'Đã hủy' WHERE maDonHang = ? AND trangThai = 'Nháp'")->execute([$maDonHang]);
    }

}
