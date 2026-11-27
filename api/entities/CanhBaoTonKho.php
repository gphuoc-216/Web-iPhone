<?php
// Entity BCE: CanhBaoTonKho — Bảng CanhBaoTonKho (Mục 3.4.3.3: Bảng 81, Bảng 82; RDM: Bảng 142)

class CanhBaoTonKho
{
    public const TEN_BANG = 'CanhBaoTonKho';
    public const KHOA_CHINH = 'maNguong';

    private ?int $maNguong = null;
    private string $phamVi = 'Toàn bộ sản phẩm';
    private int $nguongSom = 15;
    private int $nguongToiThieu = 5;
    private int $nguongToiDa = 350;
    private ?int $maDanhMuc = null;
    private ?int $maDienThoai = null;

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
        if (array_key_exists('maDanhMuc', $duLieu)) {
            $this->maDanhMuc = isset($duLieu['maDanhMuc']) && $duLieu['maDanhMuc'] !== '' ? (int)$duLieu['maDanhMuc'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
    }

    public function __get(string $tenThuocTinh)
    {
        return property_exists($this, $tenThuocTinh) ? $this->$tenThuocTinh : null;
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
            'maNguong'       => $this->maNguong,
            'phamVi'         => $this->phamVi,
            'nguongSom'      => $this->nguongSom,
            'nguongToiThieu' => $this->nguongToiThieu,
            'nguongToiDa'    => $this->nguongToiDa,
            'maDanhMuc'      => $this->maDanhMuc,
            'maDienThoai'    => $this->maDienThoai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC21 - Hình 29] CanhBaoTonKho::kiemTraHopLeNguong(nguongSom, nguongToiThieu, nguongToiDa) — Bảng 82 STT 1
    public static function kiemTraHopLeNguong(int $nguongSom, int $nguongToiThieu, int $nguongToiDa): bool
    {
        return $nguongToiThieu >= 0 && $nguongToiThieu < $nguongSom && $nguongSom < $nguongToiDa;
    }

    // [UC21 - Hình 29] CanhBaoTonKho::timTatCa() — Bảng 82 STT 4
    public static function timTatCa(?PDO $pdo = null): array
    {
        $rows = self::pdo($pdo)->query("SELECT * FROM CanhBaoTonKho ORDER BY maNguong ASC")->fetchAll();
        return array_map(fn($r) => self::tuMang($r)->chuyenThanhMang(), $rows);
    }

    // [UC20, UC56 - Hình 28, Hình 64] CanhBaoTonKho::layNguongCauHinh(loaiCanhBao) — Bảng 82 STT 2
    public static function layNguongCauHinh(?string $loaiCanhBao = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        if ($loaiCanhBao) {
            $stmt = $db->prepare("SELECT * FROM CanhBaoTonKho WHERE phamVi = ? LIMIT 1");
            $stmt->execute([$loaiCanhBao]);
            if ($r = $stmt->fetch()) return self::tuMang($r)->chuyenThanhMang();
        }
        $r = $db->query("SELECT * FROM CanhBaoTonKho ORDER BY maNguong ASC LIMIT 1")->fetch();
        return $r ? self::tuMang($r)->chuyenThanhMang() : (new self())->chuyenThanhMang();
    }

    // [UC21 - Hình 29] CanhBaoTonKho::luuNguong(phamVi, nguongSom, nguongToiThieu, nguongToiDa) — Bảng 82 STT 3
    public static function luuNguong(string $phamVi, int $nguongSom, int $nguongToiThieu, int $nguongToiDa, ?int $maDanhMuc = null, ?int $maDienThoai = null, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT maNguong FROM CanhBaoTonKho WHERE phamVi = ? AND COALESCE(maDanhMuc, 0) = COALESCE(?, 0) AND COALESCE(maDienThoai, 0) = COALESCE(?, 0) LIMIT 1");
        $stmt->execute([$phamVi, $maDanhMuc, $maDienThoai]);
        $ma = $stmt->fetchColumn();
        if ($ma) {
            $db->prepare("UPDATE CanhBaoTonKho SET nguongSom = ?, nguongToiThieu = ?, nguongToiDa = ?, maDanhMuc = ?, maDienThoai = ? WHERE maNguong = ?")
               ->execute([$nguongSom, $nguongToiThieu, $nguongToiDa, $maDanhMuc, $maDienThoai, (int)$ma]);
            return (int)$ma;
        }
        $db->prepare("INSERT INTO CanhBaoTonKho (phamVi, nguongSom, nguongToiThieu, nguongToiDa, maDanhMuc, maDienThoai) VALUES (?, ?, ?, ?, ?, ?)")
           ->execute([$phamVi, $nguongSom, $nguongToiThieu, $nguongToiDa, $maDanhMuc, $maDienThoai]);
        return (int)$db->lastInsertId();
    }
}
