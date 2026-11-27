<?php
// Entity BCE: TonKho — Bảng TonKho

class TonKho
{
    public const TEN_BANG = 'TonKho';
    public const KHOA_CHINH = 'maTonKho';

    private ?int $maTonKho = null;
    private ?int $maDienThoai = null;
    private int $maKho = 1;
    private int $tonThucTe = 0;
    private int $giuCho = 0;
    private int $tonKhaDung = 0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maTonKho', $duLieu)) {
            $this->maTonKho = isset($duLieu['maTonKho']) && $duLieu['maTonKho'] !== '' ? (int)$duLieu['maTonKho'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('maKho', $duLieu)) {
            $this->maKho = (int)($duLieu['maKho'] ?? 0);
        }
        if (array_key_exists('tonThucTe', $duLieu)) {
            $this->tonThucTe = (int)($duLieu['tonThucTe'] ?? 0);
        }
        if (array_key_exists('giuCho', $duLieu)) {
            $this->giuCho = (int)($duLieu['giuCho'] ?? 0);
        }
        if (array_key_exists('tonKhaDung', $duLieu)) {
            $this->tonKhaDung = (int)($duLieu['tonKhaDung'] ?? 0);
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
            'maTonKho' => $this->maTonKho,
            'maDienThoai' => $this->maDienThoai,
            'maKho' => $this->maKho,
            'tonThucTe' => $this->tonThucTe,
            'giuCho' => $this->giuCho,
            'tonKhaDung' => $this->tonKhaDung,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function khoiTaoTonKho(int $maDienThoai, int $maKho = 1, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("INSERT IGNORE INTO TonKho (maDienThoai, maKho, tonThucTe, giuCho, tonKhaDung) VALUES (?, ?, 0, 0, 0)");
        return $stmt->execute([$maDienThoai, $maKho]);
    }

    public static function timTheoDienThoai(int $maDienThoai, int $maKho = 1, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT * FROM TonKho WHERE maDienThoai = ? AND maKho = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maDienThoai, $maKho]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function tinhTonKhaDung(int $maDienThoai, ?PDO $pdo = null): int
    {
        $ton = self::timTheoDienThoai($maDienThoai, 1, false, $pdo);
        return $ton ? ((int)$ton['tonThucTe'] - (int)$ton['giuCho']) : 0;
    }

    public static function layTonKhoHienHanh(?PDO $pdo = null): array
    {
        return self::layTonTheoKho(1, $pdo);
    }

    public static function layTonTheoKho(int $maKho = 1, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT tk.*, d.ten AS tenDienThoai, d.hang
             FROM TonKho tk JOIN DienThoai d ON d.maDienThoai = tk.maDienThoai
             WHERE tk.maKho = ? ORDER BY tk.tonKhaDung ASC"
        );
        $stmt->execute([$maKho]);
        return $stmt->fetchAll();
    }

    public static function tangTonThucTe(int $maDienThoai, int $soLuong, int $maKho = 1, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $ton = self::timTheoDienThoai($maDienThoai, $maKho, true, $db);
        $tonCu = $ton ? (int)$ton['tonThucTe'] : 0;
        if ($ton) {
            $thucTeMoi = $tonCu + $soLuong;
            $khaDungMoi = $thucTeMoi - (int)$ton['giuCho'];
            $db->prepare("UPDATE TonKho SET tonThucTe = ?, tonKhaDung = ? WHERE maTonKho = ?")
               ->execute([$thucTeMoi, $khaDungMoi, $ton['maTonKho']]);
        } else {
            $db->prepare("INSERT INTO TonKho (maDienThoai, maKho, tonThucTe, giuCho, tonKhaDung) VALUES (?, ?, ?, 0, ?)")
               ->execute([$maDienThoai, $maKho, $soLuong, $soLuong]);
        }
        return $tonCu;
    }

    public static function truTonThucTe(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $ton = self::timTheoDienThoai($maDienThoai, 1, true, $db);
        if (!$ton || (int)$ton['tonThucTe'] < $soLuong) {
            return false;
        }
        $thucTeMoi = (int)$ton['tonThucTe'] - $soLuong;
        $giuChoMoi = max(0, (int)$ton['giuCho'] - $soLuong);
        $khaDungMoi = $thucTeMoi - $giuChoMoi;
        return $db->prepare("UPDATE TonKho SET tonThucTe = ?, giuCho = ?, tonKhaDung = ? WHERE maTonKho = ?")
                  ->execute([$thucTeMoi, $giuChoMoi, $khaDungMoi, $ton['maTonKho']]);
    }

    public static function giamGiuCho(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        return self::nhaGiuCho($maDienThoai, $soLuong, $pdo);
    }

    public static function giuChoTam(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $ton = self::timTheoDienThoai($maDienThoai, 1, true, $db);
        if (!$ton || (int)$ton['tonKhaDung'] < $soLuong) {
            return false;
        }
        $giuChoMoi = (int)$ton['giuCho'] + $soLuong;
        $khaDungMoi = (int)$ton['tonThucTe'] - $giuChoMoi;
        return $db->prepare("UPDATE TonKho SET giuCho = ?, tonKhaDung = ? WHERE maTonKho = ?")
                  ->execute([$giuChoMoi, $khaDungMoi, $ton['maTonKho']]);
    }

    public static function nhaGiuChoTam(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        return self::nhaGiuCho($maDienThoai, $soLuong, $pdo);
    }

    public static function nhaGiuCho(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $ton = self::timTheoDienThoai($maDienThoai, 1, true, $db);
        if (!$ton) return false;
        $giuChoMoi = max(0, (int)$ton['giuCho'] - $soLuong);
        $khaDungMoi = (int)$ton['tonThucTe'] - $giuChoMoi;
        return $db->prepare("UPDATE TonKho SET giuCho = ?, tonKhaDung = ? WHERE maTonKho = ?")
                  ->execute([$giuChoMoi, $khaDungMoi, $ton['maTonKho']]);
    }

    public static function chuyenGiuChoChoDonHang(int $maDienThoai, int $soLuong, int $maDonHang, ?PDO $pdo = null): bool
    {
        return $maDonHang > 0 && $soLuong > 0;
    }

    public static function dieuChinhTonThucTe(int $maDienThoai, int $soLuongThucTe, int $maKho = 1, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $ton = self::timTheoDienThoai($maDienThoai, $maKho, true, $db);
        if ($ton) {
            $giuCho = (int)$ton['giuCho'];
            $khaDungMoi = $soLuongThucTe - $giuCho;
            $db->prepare("UPDATE TonKho SET tonThucTe = ?, tonKhaDung = ? WHERE maTonKho = ?")
               ->execute([$soLuongThucTe, $khaDungMoi, $ton['maTonKho']]);
            return ['thieuGiuCho' => $soLuongThucTe < $giuCho, 'giuCho' => $giuCho];
        }
        $db->prepare("INSERT INTO TonKho (maDienThoai, maKho, tonThucTe, giuCho, tonKhaDung) VALUES (?, ?, ?, 0, ?)")
           ->execute([$maDienThoai, $maKho, $soLuongThucTe, $soLuongThucTe]);
        return ['thieuGiuCho' => false, 'giuCho' => 0];
    }

    public static function kiemTraGiuCho(int $maDonHang, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare(
            "SELECT ctdh.soLuong, tk.tonThucTe, tk.giuCho
             FROM ChiTietDonHang ctdh
             LEFT JOIN TonKho tk ON tk.maDienThoai = ctdh.maDienThoai AND tk.maKho = 1
             WHERE ctdh.maDonHang = ?"
        );
        $stmt->execute([$maDonHang]);
        foreach ($stmt->fetchAll() as $r) {
            if ((int)($r['tonThucTe'] ?? 0) < (int)$r['soLuong'] || (int)($r['tonThucTe'] ?? 0) < (int)($r['giuCho'] ?? 0)) {
                return false;
            }
        }
        return true;
    }

    public static function kiemTraDuHangTheoGiuCho(int $maDonHang, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare(
            "SELECT ctdh.soLuong, COALESCE(tk.tonThucTe, 0) AS tonThucTe
             FROM ChiTietDonHang ctdh
             LEFT JOIN TonKho tk ON tk.maDienThoai = ctdh.maDienThoai AND tk.maKho = 1
             WHERE ctdh.maDonHang = ?"
        );
        $stmt->execute([$maDonHang]);
        foreach ($stmt->fetchAll() as $r) {
            if ((int)$r['tonThucTe'] < (int)$r['soLuong']) {
                return false;
            }
        }
        return true;
    }

    public static function kiemTraTonThieuGiuCho(?PDO $pdo = null): array
    {
        return self::pdo($pdo)->query("SELECT * FROM TonKho WHERE tonThucTe < giuCho")->fetchAll();
    }

    public static function timTheoNguongCanhBaoSom(int $nguongSom, int $nguongToiDa = 350, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT tk.*, d.ten AS tenDienThoai, d.hang
             FROM TonKho tk
             JOIN DienThoai d ON d.maDienThoai = tk.maDienThoai
             WHERE tk.tonKhaDung <= ? OR tk.tonThucTe > ?
             ORDER BY tk.tonKhaDung ASC"
        );
        $stmt->execute([$nguongSom, $nguongToiDa]);
        return $stmt->fetchAll();
    }

    public static function timTheoDieuKienCanhBao(?string $loaiCanhBao, array $nguong, ?PDO $pdo = null): array
    {
        $som = (int)($nguong['nguongSom'] ?? 15);
        $min = (int)($nguong['nguongToiThieu'] ?? 5);
        $max = (int)($nguong['nguongToiDa'] ?? 350);
        $tatCa = BaoCao::tongHopHangSapHet(self::timTheoNguongCanhBaoSom($som, $max, $pdo), $som, $min, $max);
        if ($loaiCanhBao !== null && $loaiCanhBao !== '') {
            return array_values(array_filter($tatCa, fn($x) => $x['mucDoCanhBao'] === $loaiCanhBao));
        }
        return $tatCa;
    }

    public static function xoaTheoDienThoai(int $maDienThoai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("DELETE FROM TonKho WHERE maDienThoai = ?")->execute([$maDienThoai]);
    }

}
