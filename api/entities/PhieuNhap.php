<?php
// Entity BCE: PhieuNhap — Bảng PhieuNhap

class PhieuNhap
{
    public const TEN_BANG = 'PhieuNhap';
    public const KHOA_CHINH = 'maPhieuNhap';

    private ?int $maPhieuNhap = null;
    private string $nguonNhap = '';
    private ?string $ngayNhap = null;
    private string $trangThai = 'Nháp';
    private ?int $maNhanVien = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maPhieuNhap', $duLieu)) {
            $this->maPhieuNhap = isset($duLieu['maPhieuNhap']) && $duLieu['maPhieuNhap'] !== '' ? (int)$duLieu['maPhieuNhap'] : null;
        }
        if (array_key_exists('nguonNhap', $duLieu)) {
            $this->nguonNhap = (string)($duLieu['nguonNhap'] ?? '');
        }
        if (array_key_exists('ngayNhap', $duLieu)) {
            $this->ngayNhap = isset($duLieu['ngayNhap']) && $duLieu['ngayNhap'] !== '' ? (string)$duLieu['ngayNhap'] : null;
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
            'maPhieuNhap' => $this->maPhieuNhap,
            'nguonNhap' => $this->nguonNhap,
            'ngayNhap' => $this->ngayNhap,
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

    public static function timTheoTrangThai(string $trangThai, ?PDO $pdo = null): array
    {
        return self::timTheoDieuKien($trangThai, null, null, $pdo);
    }

    public static function timTheoDieuKien(?string $trangThai = null, ?string $tuNgay = null, ?string $denNgay = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $dk = ['1=1'];
        $ts = [];
        if ($trangThai !== null && $trangThai !== '') { $dk[] = "pn.trangThai = ?"; $ts[] = $trangThai; }
        if ($tuNgay !== null && $tuNgay !== '') { $dk[] = "pn.ngayNhap >= ?"; $ts[] = $tuNgay; }
        if ($denNgay !== null && $denNgay !== '') { $dk[] = "pn.ngayNhap <= ?"; $ts[] = $denNgay; }

        $sql = "SELECT pn.*, nv.hoTen AS tenNhanVien
                FROM PhieuNhap pn
                LEFT JOIN NhanVien nv ON nv.maNhanVien = pn.maNhanVien
                WHERE " . implode(' AND ', $dk) . "
                ORDER BY pn.maPhieuNhap DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($ts);
        return $stmt->fetchAll();
    }

    public static function timTheoMa(int $maPhieuNhap, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT pn.*, nv.hoTen AS tenNhanVien FROM PhieuNhap pn LEFT JOIN NhanVien nv ON nv.maNhanVien = pn.maNhanVien WHERE pn.maPhieuNhap = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maPhieuNhap]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function kiemTraChoDuyet(int $maPhieuNhap, ?PDO $pdo = null): bool
    {
        $r = self::timTheoMa($maPhieuNhap, false, $pdo);
        return $r !== null && $r['trangThai'] === 'Chờ duyệt';
    }

    public static function luu(int $maNhanVien, string $nguonNhap, string $ngayNhap, string $trangThai = 'Nháp', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO PhieuNhap (nguonNhap, ngayNhap, trangThai, maNhanVien) VALUES (?, ?, ?, ?)")
           ->execute([trim($nguonNhap), $ngayNhap, $trangThai, $maNhanVien]);
        return (int)$db->lastInsertId();
    }

    public static function capNhatTrangThai(int $maPhieuNhap, string $trangThai, ?string $trangThaiCu = null, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if ($trangThaiCu !== null) {
            $stmt = $db->prepare("UPDATE PhieuNhap SET trangThai = ? WHERE maPhieuNhap = ? AND trangThai = ?");
            $stmt->execute([$trangThai, $maPhieuNhap, $trangThaiCu]);
            return $stmt->rowCount() > 0;
        }
        return $db->prepare("UPDATE PhieuNhap SET trangThai = ? WHERE maPhieuNhap = ?")->execute([$trangThai, $maPhieuNhap]);
    }

}
