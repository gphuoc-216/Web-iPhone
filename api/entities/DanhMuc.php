<?php
// Entity BCE: DanhMuc — Bảng DanhMuc

class DanhMuc
{
    public const TEN_BANG = 'DanhMuc';
    public const KHOA_CHINH = 'maDanhMuc';

    private ?int $maDanhMuc = null;
    private string $ten = '';
    private ?string $moTa = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maDanhMuc', $duLieu)) {
            $this->maDanhMuc = isset($duLieu['maDanhMuc']) && $duLieu['maDanhMuc'] !== '' ? (int)$duLieu['maDanhMuc'] : null;
        }
        if (array_key_exists('ten', $duLieu)) {
            $this->ten = (string)($duLieu['ten'] ?? '');
        }
        if (array_key_exists('moTa', $duLieu)) {
            $this->moTa = isset($duLieu['moTa']) && $duLieu['moTa'] !== '' ? (string)$duLieu['moTa'] : null;
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
            'maDanhMuc' => $this->maDanhMuc,
            'ten' => $this->ten,
            'moTa' => $this->moTa,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraHopLe(string $ten): bool
    {
        return trim($ten) !== '' && mb_strlen(trim($ten)) <= 100;
    }

    public static function kiemTraTonTai(int $maDanhMuc, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("SELECT 1 FROM DanhMuc WHERE maDanhMuc = ? LIMIT 1");
        $stmt->execute([$maDanhMuc]);
        return (bool)$stmt->fetchColumn();
    }

    public static function kiemTraTrungTen(string $ten, ?int $boQuaMa = null, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if ($boQuaMa !== null) {
            $stmt = $db->prepare("SELECT 1 FROM DanhMuc WHERE ten = ? AND maDanhMuc <> ? LIMIT 1");
            $stmt->execute([trim($ten), $boQuaMa]);
        } else {
            $stmt = $db->prepare("SELECT 1 FROM DanhMuc WHERE ten = ? LIMIT 1");
            $stmt->execute([trim($ten)]);
        }
        return (bool)$stmt->fetchColumn();
    }

    public static function timTatCa(?PDO $pdo = null): array
    {
        $rows = self::pdo($pdo)->query("SELECT maDanhMuc, ten, moTa FROM DanhMuc ORDER BY maDanhMuc ASC")->fetchAll();
        return array_map(fn($r) => self::tuMang($r)->chuyenThanhMang(), $rows);
    }

    public static function timTheoMa(int $maDanhMuc, ?PDO $pdo = null): ?self
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM DanhMuc WHERE maDanhMuc = ? LIMIT 1");
        $stmt->execute([$maDanhMuc]);
        $row = $stmt->fetch();
        return $row ? self::tuMang($row) : null;
    }

    public static function luu(string $ten, ?string $moTa = null, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("INSERT INTO DanhMuc (ten, moTa) VALUES (?, ?)");
        $stmt->execute([trim($ten), $moTa !== null && trim($moTa) !== '' ? trim($moTa) : null]);
        return (int)$db->lastInsertId();
    }

    public static function capNhat(int $maDanhMuc, string $ten, ?string $moTa = null, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("UPDATE DanhMuc SET ten = ?, moTa = ? WHERE maDanhMuc = ?");
        return $stmt->execute([trim($ten), $moTa !== null && trim($moTa) !== '' ? trim($moTa) : null, $maDanhMuc]);
    }

    public static function xoa(int $maDanhMuc, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("DELETE FROM DanhMuc WHERE maDanhMuc = ?");
        return $stmt->execute([$maDanhMuc]);
    }

}
