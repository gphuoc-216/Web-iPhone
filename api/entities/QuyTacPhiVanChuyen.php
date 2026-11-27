<?php
// Entity BCE: QuyTacPhiVanChuyen — Bảng QuyTacPhiVanChuyen

class QuyTacPhiVanChuyen
{
    public const TEN_BANG = 'QuyTacPhiVanChuyen';
    public const KHOA_CHINH = 'maQuyTac';

    private ?int $maQuyTac = null;
    private string $khuVuc = '';
    private float $mucPhi = 0.0;
    private float $mucPhiMacDinh = 35000.0;
    private float $nguongMienPhi = 0.0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maQuyTac', $duLieu)) {
            $this->maQuyTac = isset($duLieu['maQuyTac']) && $duLieu['maQuyTac'] !== '' ? (int)$duLieu['maQuyTac'] : null;
        }
        if (array_key_exists('khuVuc', $duLieu)) {
            $this->khuVuc = (string)($duLieu['khuVuc'] ?? '');
        }
        if (array_key_exists('mucPhi', $duLieu)) {
            $this->mucPhi = (float)($duLieu['mucPhi'] ?? 0.0);
        }
        if (array_key_exists('mucPhiMacDinh', $duLieu)) {
            $this->mucPhiMacDinh = (float)($duLieu['mucPhiMacDinh'] ?? 0.0);
        }
        if (array_key_exists('nguongMienPhi', $duLieu)) {
            $this->nguongMienPhi = (float)($duLieu['nguongMienPhi'] ?? 0.0);
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
            'maQuyTac' => $this->maQuyTac,
            'khuVuc' => $this->khuVuc,
            'mucPhi' => $this->mucPhi,
            'mucPhiMacDinh' => $this->mucPhiMacDinh,
            'nguongMienPhi' => $this->nguongMienPhi,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraHopLe(string $khuVuc, float $mucPhi, float $mucPhiMacDinh = 35000.0, float $nguongMienPhi = 0.0): bool
    {
        return trim($khuVuc) !== '' && $mucPhi >= 0 && $mucPhiMacDinh >= 0 && $nguongMienPhi >= 0;
    }

    public static function timTatCa(?PDO $pdo = null): array
    {
        $rows = self::pdo($pdo)->query("SELECT * FROM QuyTacPhiVanChuyen ORDER BY maQuyTac ASC")->fetchAll();
        return array_map(fn($r) => self::tuMang($r)->chuyenThanhMang(), $rows);
    }

    public static function timTheoMa(int $maQuyTac, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM QuyTacPhiVanChuyen WHERE maQuyTac = ? LIMIT 1");
        $stmt->execute([$maQuyTac]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function timTheoKhuVuc(string $khuVuc, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM QuyTacPhiVanChuyen WHERE khuVuc = ? LIMIT 1");
        $stmt->execute([trim($khuVuc)]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function layMucPhiMacDinh(?PDO $pdo = null): float
    {
        $phi = self::pdo($pdo)->query("SELECT mucPhiMacDinh FROM QuyTacPhiVanChuyen ORDER BY maQuyTac ASC LIMIT 1")->fetchColumn();
        return $phi !== false ? (float)$phi : 35000.0;
    }

    public static function tinhPhi(?int $maQuyTac, float $tongTienHang, ?PDO $pdo = null): array
    {
        $quyTac = $maQuyTac ? self::timTheoMa($maQuyTac, $pdo) : null;
        if (!$quyTac) {
            return [
                'khuVuc'        => 'Mặc định',
                'phiVanChuyen'  => self::layMucPhiMacDinh($pdo),
                'nguongMienPhi' => 0.0,
            ];
        }
        $nguong = (float)$quyTac['nguongMienPhi'];
        $phi = ($nguong > 0 && $tongTienHang >= $nguong) ? 0.0 : (float)$quyTac['mucPhi'];
        return [
            'khuVuc'        => $quyTac['khuVuc'],
            'phiVanChuyen'  => $phi,
            'nguongMienPhi' => $nguong,
        ];
    }

    public static function luu(string $khuVuc, float $mucPhi, float $mucPhiMacDinh = 35000.0, float $nguongMienPhi = 0.0, ?int $maQuyTac = null, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        if ($maQuyTac !== null && $maQuyTac > 0) {
            $db->prepare("UPDATE QuyTacPhiVanChuyen SET khuVuc = ?, mucPhi = ?, mucPhiMacDinh = ?, nguongMienPhi = ? WHERE maQuyTac = ?")
               ->execute([trim($khuVuc), $mucPhi, $mucPhiMacDinh, $nguongMienPhi, $maQuyTac]);
            return $maQuyTac;
        }
        $db->prepare("INSERT INTO QuyTacPhiVanChuyen (khuVuc, mucPhi, mucPhiMacDinh, nguongMienPhi) VALUES (?, ?, ?, ?)")
           ->execute([trim($khuVuc), $mucPhi, $mucPhiMacDinh, $nguongMienPhi]);
        return (int)$db->lastInsertId();
    }

}
