<?php
// Entity BCE: CauHinhLoiNhuan — Bảng CauHinhLoiNhuan

class CauHinhLoiNhuan
{
    public const TEN_BANG = 'CauHinhLoiNhuan';
    public const KHOA_CHINH = 'maCauHinh';

    private ?int $maCauHinh = null;
    private string $phamVi = 'Toàn bộ sản phẩm';
    private float $tyLe = 25.0;
    private ?string $ngayApDung = null;
    private ?string $ghiChu = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maCauHinh', $duLieu)) {
            $this->maCauHinh = isset($duLieu['maCauHinh']) && $duLieu['maCauHinh'] !== '' ? (int)$duLieu['maCauHinh'] : null;
        }
        if (array_key_exists('phamVi', $duLieu)) {
            $this->phamVi = (string)($duLieu['phamVi'] ?? '');
        }
        if (array_key_exists('tyLe', $duLieu)) {
            $this->tyLe = (float)($duLieu['tyLe'] ?? 0.0);
        }
        if (array_key_exists('ngayApDung', $duLieu)) {
            $this->ngayApDung = isset($duLieu['ngayApDung']) && $duLieu['ngayApDung'] !== '' ? (string)$duLieu['ngayApDung'] : null;
        }
        if (array_key_exists('ghiChu', $duLieu)) {
            $this->ghiChu = isset($duLieu['ghiChu']) && $duLieu['ghiChu'] !== '' ? (string)$duLieu['ghiChu'] : null;
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
            'maCauHinh' => $this->maCauHinh,
            'phamVi' => $this->phamVi,
            'tyLe' => $this->tyLe,
            'ngayApDung' => $this->ngayApDung,
            'ghiChu' => $this->ghiChu,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraHopLe(float $tyLe, string $ngayApDung): bool
    {
        return $tyLe >= 0 && $tyLe <= 500 && strtotime($ngayApDung) !== false;
    }

    public static function timTatCa(?PDO $pdo = null): array
    {
        $rows = self::pdo($pdo)->query("SELECT * FROM CauHinhLoiNhuan ORDER BY maCauHinh DESC")->fetchAll();
        return array_map(fn($r) => self::tuMang($r)->chuyenThanhMang(), $rows);
    }

    public static function layCauHinhHienHanh(string $phamVi = 'Toàn bộ sản phẩm', ?PDO $pdo = null): ?self
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT * FROM CauHinhLoiNhuan WHERE phamVi = ? ORDER BY maCauHinh DESC LIMIT 1");
        $stmt->execute([$phamVi]);
        $row = $stmt->fetch();
        if (!$row) {
            $row = $db->query("SELECT * FROM CauHinhLoiNhuan ORDER BY maCauHinh DESC LIMIT 1")->fetch();
        }
        return $row ? self::tuMang($row) : null;
    }

    public static function layTyLeHienHanh(?string $hang = null, ?PDO $pdo = null): float
    {
        $db = self::pdo($pdo);
        if ($hang !== null && $hang !== '') {
            $stmt = $db->prepare("SELECT tyLe FROM CauHinhLoiNhuan WHERE phamVi = ? ORDER BY maCauHinh DESC LIMIT 1");
            $stmt->execute([$hang]);
            $tl = $stmt->fetchColumn();
            if ($tl !== false) return (float)$tl;
        }
        $tlChung = $db->query("SELECT tyLe FROM CauHinhLoiNhuan ORDER BY maCauHinh DESC LIMIT 1")->fetchColumn();
        return $tlChung !== false ? (float)$tlChung : 25.0;
    }

    public static function luu(string $phamVi, float $tyLe, string $ngayApDung, ?string $ghiChu = null, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT maCauHinh FROM CauHinhLoiNhuan WHERE phamVi = ? LIMIT 1");
        $stmt->execute([$phamVi]);
        $hienCo = $stmt->fetchColumn();
        if ($hienCo) {
            $db->prepare("UPDATE CauHinhLoiNhuan SET tyLe = ?, ngayApDung = ?, ghiChu = ? WHERE maCauHinh = ?")
               ->execute([$tyLe, $ngayApDung, $ghiChu, (int)$hienCo]);
            return (int)$hienCo;
        }
        $db->prepare("INSERT INTO CauHinhLoiNhuan (phamVi, tyLe, ngayApDung, ghiChu) VALUES (?, ?, ?, ?)")
           ->execute([$phamVi, $tyLe, $ngayApDung, $ghiChu]);
        return (int)$db->lastInsertId();
    }

}
