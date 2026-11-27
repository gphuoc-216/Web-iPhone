<?php
// Entity BCE: ChiTietKiemKe — Bảng ChiTietKiemKe

class ChiTietKiemKe
{
    public const TEN_BANG = 'ChiTietKiemKe';
    public const KHOA_CHINH = 'maChiTiet';

    private ?int $maChiTiet = null;
    private ?int $maBienBan = null;
    private ?int $maDienThoai = null;
    private int $soLuongHeThong = 0;
    private int $soLuongThucTe = 0;
    private int $chenhLech = 0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maChiTiet', $duLieu)) {
            $this->maChiTiet = isset($duLieu['maChiTiet']) && $duLieu['maChiTiet'] !== '' ? (int)$duLieu['maChiTiet'] : null;
        }
        if (array_key_exists('maBienBan', $duLieu)) {
            $this->maBienBan = isset($duLieu['maBienBan']) && $duLieu['maBienBan'] !== '' ? (int)$duLieu['maBienBan'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('soLuongHeThong', $duLieu)) {
            $this->soLuongHeThong = (int)($duLieu['soLuongHeThong'] ?? 0);
        }
        if (array_key_exists('soLuongThucTe', $duLieu)) {
            $this->soLuongThucTe = (int)($duLieu['soLuongThucTe'] ?? 0);
        }
        if (array_key_exists('chenhLech', $duLieu)) {
            $this->chenhLech = (int)($duLieu['chenhLech'] ?? 0);
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
            'maChiTiet' => $this->maChiTiet,
            'maBienBan' => $this->maBienBan,
            'maDienThoai' => $this->maDienThoai,
            'soLuongHeThong' => $this->soLuongHeThong,
            'soLuongThucTe' => $this->soLuongThucTe,
            'chenhLech' => $this->chenhLech,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraHopLe(array $danhSachSoLuong): bool
    {
        if (empty($danhSachSoLuong)) return false;
        foreach ($danhSachSoLuong as $d) {
            if ((int)($d['maDienThoai'] ?? 0) <= 0 || !isset($d['soLuongThucTe']) || (int)$d['soLuongThucTe'] < 0) {
                return false;
            }
        }
        return true;
    }

    public static function tinhChenhLech(int $maDienThoai, int $soLuongThucTe, int $maKho = 1, ?PDO $pdo = null): array
    {
        $ton = TonKho::timTheoDienThoai($maDienThoai, $maKho, false, $pdo);
        $heThong = $ton ? (int)$ton['tonThucTe'] : 0;
        return [
            'soLuongHeThong' => $heThong,
            'soLuongThucTe'  => $soLuongThucTe,
            'chenhLech'      => $soLuongThucTe - $heThong,
        ];
    }

    public static function luu(int $maBienBan, int $maDienThoai, int $soLuongHeThong, int $soLuongThucTe, int $chenhLech, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO ChiTietKiemKe (maBienBan, maDienThoai, soLuongHeThong, soLuongThucTe, chenhLech) VALUES (?, ?, ?, ?, ?)")
           ->execute([$maBienBan, $maDienThoai, $soLuongHeThong, $soLuongThucTe, $chenhLech]);
        return (int)$db->lastInsertId();
    }

    public static function timTheoBienBan(?int $maBienBan = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        if ($maBienBan !== null) {
            $stmt = $db->prepare("SELECT ct.*, d.ten AS tenDienThoai FROM ChiTietKiemKe ct LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai WHERE ct.maBienBan = ?");
            $stmt->execute([$maBienBan]);
            return $stmt->fetchAll();
        }
        return $db->query("SELECT ct.*, d.ten AS tenDienThoai FROM ChiTietKiemKe ct LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai")->fetchAll();
    }

}
