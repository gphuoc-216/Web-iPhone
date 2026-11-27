<?php
// Entity BCE: CanhBaoTonKho — Bảng CanhBaoTonKho

class CanhBaoTonKho
{
    public const TEN_BANG = 'CanhBaoTonKho';
    public const KHOA_CHINH = 'maNguong';

    private ?int $maNguong = null;
    private string $phamVi = 'Toàn bộ sản phẩm';
    private int $nguongSom = 15;
    private int $nguongToiThieu = 5;
    private int $nguongToiDa = 350;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maNguong', $duLieu)) {
            $this->maNguong = isset($duLieu['maNguong']) && $duLieu['maNguong'] !== '' ? (int)$duLieu['maNguong'] : null;
        }
        if (array_key_exists('phamVi', $duLieu)) {
            $this->phamVi = (string)($duLieu['phamVi'] ?? '');
        }
        if (array_key_exists('nguongSom', $duLieu)) {
            $this->nguongSom = (int)($duLieu['nguongSom'] ?? 0);
        }
        if (array_key_exists('nguongToiThieu', $duLieu)) {
            $this->nguongToiThieu = (int)($duLieu['nguongToiThieu'] ?? 0);
        }
        if (array_key_exists('nguongToiDa', $duLieu)) {
            $this->nguongToiDa = (int)($duLieu['nguongToiDa'] ?? 0);
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
            'maNguong' => $this->maNguong,
            'phamVi' => $this->phamVi,
            'nguongSom' => $this->nguongSom,
            'nguongToiThieu' => $this->nguongToiThieu,
            'nguongToiDa' => $this->nguongToiDa,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraHopLeNguong(int $nguongSom, int $nguongToiThieu, int $nguongToiDa): bool
    {
        return $nguongToiThieu >= 0 && $nguongToiThieu < $nguongSom && $nguongSom < $nguongToiDa;
    }

    public static function timTatCa(?PDO $pdo = null): array
    {
        $rows = self::pdo($pdo)->query("SELECT * FROM CanhBaoTonKho ORDER BY maNguong ASC")->fetchAll();
        return array_map(fn($r) => self::tuMang($r)->chuyenThanhMang(), $rows);
    }

    public static function layNguongCauHinh(?string $phamVi = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        if ($phamVi) {
            $stmt = $db->prepare("SELECT * FROM CanhBaoTonKho WHERE phamVi = ? LIMIT 1");
            $stmt->execute([$phamVi]);
            if ($r = $stmt->fetch()) return self::tuMang($r)->chuyenThanhMang();
        }
        $r = $db->query("SELECT * FROM CanhBaoTonKho ORDER BY maNguong ASC LIMIT 1")->fetch();
        return $r ? self::tuMang($r)->chuyenThanhMang() : (new self())->chuyenThanhMang();
    }

    public static function luuNguong(string $phamVi, int $nguongSom, int $nguongToiThieu, int $nguongToiDa, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT maNguong FROM CanhBaoTonKho WHERE phamVi = ? LIMIT 1");
        $stmt->execute([$phamVi]);
        $ma = $stmt->fetchColumn();
        if ($ma) {
            $db->prepare("UPDATE CanhBaoTonKho SET nguongSom = ?, nguongToiThieu = ?, nguongToiDa = ? WHERE maNguong = ?")
               ->execute([$nguongSom, $nguongToiThieu, $nguongToiDa, (int)$ma]);
            return (int)$ma;
        }
        $db->prepare("INSERT INTO CanhBaoTonKho (phamVi, nguongSom, nguongToiThieu, nguongToiDa) VALUES (?, ?, ?, ?)")
           ->execute([$phamVi, $nguongSom, $nguongToiThieu, $nguongToiDa]);
        return (int)$db->lastInsertId();
    }

}
