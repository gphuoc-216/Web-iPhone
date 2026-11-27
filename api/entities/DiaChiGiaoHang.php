<?php
// Entity BCE: DiaChiGiaoHang — Bảng DiaChiGiaoHang

class DiaChiGiaoHang
{
    public const TEN_BANG = 'DiaChiGiaoHang';
    public const KHOA_CHINH = 'maDiaChi';

    private ?int $maDiaChi = null;
    private ?int $maKhachHang = null;
    private string $nguoiNhan = '';
    private string $soDienThoai = '';
    private string $diaChi = '';
    private ?int $maQuyTac = null;
    private bool $macDinh = false;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maDiaChi', $duLieu)) {
            $this->maDiaChi = isset($duLieu['maDiaChi']) && $duLieu['maDiaChi'] !== '' ? (int)$duLieu['maDiaChi'] : null;
        }
        if (array_key_exists('maKhachHang', $duLieu)) {
            $this->maKhachHang = isset($duLieu['maKhachHang']) && $duLieu['maKhachHang'] !== '' ? (int)$duLieu['maKhachHang'] : null;
        }
        if (array_key_exists('nguoiNhan', $duLieu)) {
            $this->nguoiNhan = (string)($duLieu['nguoiNhan'] ?? '');
        }
        if (array_key_exists('soDienThoai', $duLieu)) {
            $this->soDienThoai = (string)($duLieu['soDienThoai'] ?? '');
        }
        if (array_key_exists('diaChi', $duLieu)) {
            $this->diaChi = (string)($duLieu['diaChi'] ?? '');
        }
        if (array_key_exists('maQuyTac', $duLieu)) {
            $this->maQuyTac = isset($duLieu['maQuyTac']) && $duLieu['maQuyTac'] !== '' ? (int)$duLieu['maQuyTac'] : null;
        }
        if (array_key_exists('macDinh', $duLieu)) {
            $this->macDinh = (bool)($duLieu['macDinh'] ?? false);
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
            'maDiaChi' => $this->maDiaChi,
            'maKhachHang' => $this->maKhachHang,
            'nguoiNhan' => $this->nguoiNhan,
            'soDienThoai' => $this->soDienThoai,
            'diaChi' => $this->diaChi,
            'maQuyTac' => $this->maQuyTac,
            'macDinh' => $this->macDinh,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraHopLe(string $nguoiNhan, string $soDienThoai, string $diaChi, int $maQuyTac = 1): bool
    {
        return trim($nguoiNhan) !== '' && trim($soDienThoai) !== '' && trim($diaChi) !== '' && $maQuyTac > 0;
    }

    public static function timTheoKhachHang(int $maKhachHang, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT dc.*, qt.khuVuc, qt.mucPhi
             FROM DiaChiGiaoHang dc
             JOIN QuyTacPhiVanChuyen qt ON qt.maQuyTac = dc.maQuyTac
             WHERE dc.maKhachHang = ?
             ORDER BY dc.macDinh DESC, dc.maDiaChi DESC"
        );
        $stmt->execute([$maKhachHang]);
        return $stmt->fetchAll();
    }

    public static function timTheoMa(int $maDiaChi, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM DiaChiGiaoHang WHERE maDiaChi = ? LIMIT 1");
        $stmt->execute([$maDiaChi]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function layMaQuyTac(int $maDiaChi, ?PDO $pdo = null): ?int
    {
        $dc = self::timTheoMa($maDiaChi, $pdo);
        return $dc ? (int)$dc['maQuyTac'] : null;
    }

    public static function xacDinhKhuVuc(int $maDiaChi, ?PDO $pdo = null): ?int
    {
        return self::layMaQuyTac($maDiaChi, $pdo);
    }

    public static function luu(int $maKhachHang, string $nguoiNhan, string $soDienThoai, string $diaChi, int $maQuyTac = 1, bool $macDinh = false, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        if ($macDinh) {
            $db->prepare("UPDATE DiaChiGiaoHang SET macDinh = FALSE WHERE maKhachHang = ?")->execute([$maKhachHang]);
        }
        $db->prepare("INSERT INTO DiaChiGiaoHang (maKhachHang, nguoiNhan, soDienThoai, diaChi, maQuyTac, macDinh) VALUES (?, ?, ?, ?, ?, ?)")
           ->execute([$maKhachHang, trim($nguoiNhan), trim($soDienThoai), trim($diaChi), $maQuyTac, $macDinh ? 1 : 0]);
        return (int)$db->lastInsertId();
    }

    public static function capNhat(int $maDiaChi, int $maKhachHang, array $duLieu, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $macDinh = !empty($duLieu['macDinh']);
        if ($macDinh) {
            $db->prepare("UPDATE DiaChiGiaoHang SET macDinh = FALSE WHERE maKhachHang = ?")->execute([$maKhachHang]);
        }
        $stmt = $db->prepare(
            "UPDATE DiaChiGiaoHang
             SET nguoiNhan = COALESCE(?, nguoiNhan),
                 soDienThoai = COALESCE(?, soDienThoai),
                 diaChi = COALESCE(?, diaChi),
                 maQuyTac = COALESCE(?, maQuyTac),
                 macDinh = ?
             WHERE maDiaChi = ? AND maKhachHang = ?"
        );
        return $stmt->execute([
            $duLieu['nguoiNhan'] ?? null,
            $duLieu['soDienThoai'] ?? null,
            $duLieu['diaChi'] ?? null,
            isset($duLieu['maQuyTac']) ? (int)$duLieu['maQuyTac'] : null,
            $macDinh ? 1 : 0,
            $maDiaChi,
            $maKhachHang,
        ]);
    }

    public static function xoa(int $maDiaChi, int $maKhachHang, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("DELETE FROM DiaChiGiaoHang WHERE maDiaChi = ? AND maKhachHang = ?");
        $stmt->execute([$maDiaChi, $maKhachHang]);
        return $stmt->rowCount() > 0;
    }

}
