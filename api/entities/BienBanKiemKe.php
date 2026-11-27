<?php
// Entity BCE: BienBanKiemKe — Bảng BienBanKiemKe

class BienBanKiemKe
{
    public const TEN_BANG = 'BienBanKiemKe';
    public const KHOA_CHINH = 'maBienBan';

    private ?int $maBienBan = null;
    private int $maKho = 1;
    private ?string $thoiDiem = null;
    private ?string $ghiChu = null;
    private string $trangThai = 'Nháp';
    private ?int $maNhanVien = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maBienBan', $duLieu)) {
            $this->maBienBan = isset($duLieu['maBienBan']) && $duLieu['maBienBan'] !== '' ? (int)$duLieu['maBienBan'] : null;
        }
        if (array_key_exists('maKho', $duLieu)) {
            $this->maKho = (int)($duLieu['maKho'] ?? 0);
        }
        if (array_key_exists('thoiDiem', $duLieu)) {
            $this->thoiDiem = isset($duLieu['thoiDiem']) && $duLieu['thoiDiem'] !== '' ? (string)$duLieu['thoiDiem'] : null;
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
            'maBienBan' => $this->maBienBan,
            'maKho' => $this->maKho,
            'thoiDiem' => $this->thoiDiem,
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
        return self::pdo($pdo)->query(
            "SELECT bb.*, nv.hoTen AS tenNhanVien
             FROM BienBanKiemKe bb
             LEFT JOIN NhanVien nv ON nv.maNhanVien = bb.maNhanVien
             ORDER BY bb.maBienBan DESC"
        )->fetchAll();
    }

    public static function timTheoTrangThai(string $trangThai, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT bb.*, nv.hoTen AS tenNhanVien
             FROM BienBanKiemKe bb
             LEFT JOIN NhanVien nv ON nv.maNhanVien = bb.maNhanVien
             WHERE bb.trangThai = ? ORDER BY bb.maBienBan DESC"
        );
        $stmt->execute([$trangThai]);
        return $stmt->fetchAll();
    }

    public static function timTheoMa(int $maBienBan, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT bb.*, nv.maTaiKhoan FROM BienBanKiemKe bb LEFT JOIN NhanVien nv ON nv.maNhanVien = bb.maNhanVien WHERE bb.maBienBan = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maBienBan]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function taoMoi(int $maKho, int $maNhanVien, ?string $ghiChu = null, string $trangThai = 'Nháp', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO BienBanKiemKe (maKho, thoiDiem, ghiChu, trangThai, maNhanVien) VALUES (?, CURRENT_TIMESTAMP, ?, ?, ?)")
           ->execute([$maKho, $ghiChu, $trangThai, $maNhanVien]);
        return (int)$db->lastInsertId();
    }

    public static function luu(int $maBienBan, ?string $ghiChu, string $trangThai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE BienBanKiemKe SET ghiChu = ?, trangThai = ? WHERE maBienBan = ?")
            ->execute([$ghiChu, $trangThai, $maBienBan]);
    }

    public static function luuTienDo(int $maBienBan, ?PDO $pdo = null): bool
    {
        return self::capNhatTrangThai($maBienBan, 'Nháp', $pdo);
    }

    public static function capNhatTrangThai(int $maBienBan, string $trangThai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE BienBanKiemKe SET trangThai = ? WHERE maBienBan = ?")->execute([$trangThai, $maBienBan]);
    }

    public static function kiemTraCoChenhLech(int $maBienBan, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("SELECT COUNT(*) FROM ChiTietKiemKe WHERE maBienBan = ? AND chenhLech <> 0");
        $stmt->execute([$maBienBan]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

}
