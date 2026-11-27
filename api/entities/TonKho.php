<?php
// Entity BCE: TonKho — Bảng TonKho (Mục 3.4.3.28: Bảng 131, Bảng 132; RDM: Bảng 168 — 3NF: không lưu maKho, tonKhaDung)

class TonKho
{
    public const TEN_BANG = 'TonKho';
    public const KHOA_CHINH = 'maTonKho';

    private ?int $maTonKho = null;
    private ?int $maDienThoai = null;
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
        if (array_key_exists('tonThucTe', $duLieu)) {
            $this->tonThucTe = (int)($duLieu['tonThucTe'] ?? 0);
        }
        if (array_key_exists('giuCho', $duLieu)) {
            $this->giuCho = (int)($duLieu['giuCho'] ?? 0);
        }
        $this->tonKhaDung = array_key_exists('tonKhaDung', $duLieu)
            ? (int)$duLieu['tonKhaDung']
            : ($this->tonThucTe - $this->giuCho);
    }

    public function __get(string $tenThuocTinh)
    {
        if ($tenThuocTinh === 'tonKhaDung') {
            return $this->tonThucTe - $this->giuCho;
        }
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
            'maTonKho'    => $this->maTonKho,
            'maDienThoai' => $this->maDienThoai,
            'tonThucTe'   => $this->tonThucTe,
            'giuCho'      => $this->giuCho,
            'tonKhaDung'  => $this->tonThucTe - $this->giuCho,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC20 - Hình 28] TonKho::timTheoDienThoai(maDienThoai) — Bảng 132 STT 12
    public static function timTheoDienThoai(int $maDienThoai, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT maTonKho, maDienThoai, tonThucTe, giuCho, (tonThucTe - giuCho) AS tonKhaDung
                FROM TonKho WHERE maDienThoai = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maDienThoai]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // [UC9, UC22, UC23, UC28 - Hình 17, 30, 31, 36] TonKho::tinhTonKhaDung(maDienThoai) — Bảng 132 STT 15
    public static function tinhTonKhaDung(int $maDienThoai, ?PDO $pdo = null): int
    {
        $ton = self::timTheoDienThoai($maDienThoai, false, $pdo);
        return $ton ? ((int)$ton['tonThucTe'] - (int)$ton['giuCho']) : 0;
    }

    // [UC18 - Hình 26] TonKho::layTonKhoHienHanh() — Bảng 132 STT 8
    public static function layTonKhoHienHanh(?PDO $pdo = null): array
    {
        return self::pdo($pdo)->query(
            "SELECT tk.maTonKho, tk.maDienThoai, tk.tonThucTe, tk.giuCho,
                    (tk.tonThucTe - tk.giuCho) AS tonKhaDung,
                    d.ten AS tenDienThoai, d.hang
             FROM TonKho tk
             JOIN DienThoai d ON d.maDienThoai = tk.maDienThoai
             ORDER BY (tk.tonThucTe - tk.giuCho) ASC"
        )->fetchAll();
    }

    // [UC13 - Hình 21] TonKho::tangTonThucTe(maDienThoai, soLuong) — Bảng 132 STT 11
    public static function tangTonThucTe(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $ton = self::timTheoDienThoai($maDienThoai, true, $db);
        if ($ton) {
            $thucTeMoi = (int)$ton['tonThucTe'] + $soLuong;
            return $db->prepare("UPDATE TonKho SET tonThucTe = ? WHERE maTonKho = ?")
                      ->execute([$thucTeMoi, $ton['maTonKho']]);
        }
        return $db->prepare("INSERT INTO TonKho (maDienThoai, tonThucTe, giuCho) VALUES (?, ?, 0)")
                  ->execute([$maDienThoai, $soLuong]);
    }

    // [UC16 - Hình 24] TonKho::truTonThucTe(maDienThoai, soLuong) — Bảng 132 STT 16 (chỉ trừ tonThucTe; giamGiuCho xử lý riêng)
    public static function truTonThucTe(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $ton = self::timTheoDienThoai($maDienThoai, true, $db);
        if (!$ton || (int)$ton['tonThucTe'] < $soLuong) {
            return false;
        }
        $thucTeMoi = (int)$ton['tonThucTe'] - $soLuong;
        return $db->prepare("UPDATE TonKho SET tonThucTe = ? WHERE maTonKho = ?")
                  ->execute([$thucTeMoi, $ton['maTonKho']]);
    }

    // [UC16 - Hình 24] TonKho::giamGiuCho(maDienThoai, soLuong) — Bảng 132 STT 3
    public static function giamGiuCho(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        return self::nhaGiuCho($maDienThoai, $soLuong, $pdo);
    }

    // [UC28 - Hình 36] TonKho::giuChoTam(maDienThoai, soLuong) — Bảng 132 STT 4
    public static function giuChoTam(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $ton = self::timTheoDienThoai($maDienThoai, true, $db);
        $khaDung = $ton ? ((int)$ton['tonThucTe'] - (int)$ton['giuCho']) : 0;
        if (!$ton || $khaDung < $soLuong) {
            return false;
        }
        $giuChoMoi = (int)$ton['giuCho'] + $soLuong;
        return $db->prepare("UPDATE TonKho SET giuCho = ? WHERE maTonKho = ?")
                  ->execute([$giuChoMoi, $ton['maTonKho']]);
    }

    // [UC28 - Hình 36] TonKho::nhaGiuChoTam(maDienThoai, soLuong) — Bảng 132 STT 10
    public static function nhaGiuChoTam(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        return self::nhaGiuCho($maDienThoai, $soLuong, $pdo);
    }

    // [UC31, UC36 - Hình 39, Hình 44] TonKho::nhaGiuCho(maDienThoai, soLuong) — Bảng 132 STT 9
    public static function nhaGiuCho(int $maDienThoai, int $soLuong, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $ton = self::timTheoDienThoai($maDienThoai, true, $db);
        if (!$ton) return false;
        $giuChoMoi = max(0, (int)$ton['giuCho'] - $soLuong);
        return $db->prepare("UPDATE TonKho SET giuCho = ? WHERE maTonKho = ?")
                  ->execute([$giuChoMoi, $ton['maTonKho']]);
    }

    // [UC28 - Hình 36] TonKho::chuyenGiuChoChoDonHang(maDienThoai, soLuong, maDonHang) — Bảng 132 STT 1
    public static function chuyenGiuChoChoDonHang(int $maDienThoai, int $soLuong, int $maDonHang, ?PDO $pdo = null): bool
    {
        return $maDienThoai > 0 && $maDonHang > 0 && $soLuong > 0;
    }

    // [UC19 - Hình 27] TonKho::dieuChinhTonThucTe(maDienThoai, soLuongThucTe) — Bảng 132 STT 2
    public static function dieuChinhTonThucTe(int $maDienThoai, int $soLuongThucTe, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $ton = self::timTheoDienThoai($maDienThoai, true, $db);
        if ($ton) {
            return $db->prepare("UPDATE TonKho SET tonThucTe = ? WHERE maTonKho = ?")
                      ->execute([max(0, $soLuongThucTe), $ton['maTonKho']]);
        }
        return $db->prepare("INSERT INTO TonKho (maDienThoai, tonThucTe, giuCho) VALUES (?, ?, 0)")
                  ->execute([$maDienThoai, max(0, $soLuongThucTe)]);
    }

    // [UC33 - Hình 41] TonKho::kiemTraGiuCho(maDonHang) — Bảng 132 STT 6
    public static function kiemTraGiuCho(int $maDonHang, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare(
            "SELECT ctdh.soLuong, COALESCE(tk.tonThucTe, 0) AS tonThucTe, COALESCE(tk.giuCho, 0) AS giuCho
             FROM ChiTietDonHang ctdh
             LEFT JOIN TonKho tk ON tk.maDienThoai = ctdh.maDienThoai
             WHERE ctdh.maDonHang = ?"
        );
        $stmt->execute([$maDonHang]);
        foreach ($stmt->fetchAll() as $r) {
            if ((int)$r['tonThucTe'] < (int)$r['soLuong'] || (int)$r['tonThucTe'] < (int)$r['giuCho']) {
                return false;
            }
        }
        return true;
    }

    // [UC15 - Hình 23] TonKho::kiemTraDuHangTheoGiuCho(maDonHang) — Bảng 132 STT 5
    public static function kiemTraDuHangTheoGiuCho(int $maDonHang, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare(
            "SELECT ctdh.soLuong, COALESCE(tk.tonThucTe, 0) AS tonThucTe
             FROM ChiTietDonHang ctdh
             LEFT JOIN TonKho tk ON tk.maDienThoai = ctdh.maDienThoai
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

    // [UC19 - Hình 27] TonKho::kiemTraTonThieuGiuCho() — Bảng 132 STT 7
    public static function kiemTraTonThieuGiuCho(?PDO $pdo = null): array
    {
        return self::pdo($pdo)->query(
            "SELECT DISTINCT dh.*
             FROM DonHang dh
             JOIN ChiTietDonHang ct ON ct.maDonHang = dh.maDonHang
             JOIN TonKho tk ON tk.maDienThoai = ct.maDienThoai
             WHERE tk.tonThucTe < tk.giuCho
               AND dh.trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đang đóng gói')"
        )->fetchAll();
    }

    // [UC56 - Hình 64] TonKho::timTheoNguongCanhBaoSom(nguong) — Bảng 132 STT 14
    public static function timTheoNguongCanhBaoSom(int $nguong, int $nguongToiDa = 350, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT tk.maTonKho, tk.maDienThoai, tk.tonThucTe, tk.giuCho,
                    (tk.tonThucTe - tk.giuCho) AS tonKhaDung,
                    d.ten AS tenDienThoai, d.hang, dm.ten AS tenDanhMuc
             FROM TonKho tk
             JOIN DienThoai d ON d.maDienThoai = tk.maDienThoai
             LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
             WHERE (tk.tonThucTe - tk.giuCho) <= ? OR tk.tonThucTe > ?
             ORDER BY (tk.tonThucTe - tk.giuCho) ASC"
        );
        $stmt->execute([$nguong, $nguongToiDa]);
        return $stmt->fetchAll();
    }

    // [UC20 - Hình 28] TonKho::timTheoDieuKienCanhBao(loaiCanhBao, nguong) — Bảng 132 STT 13
    public static function timTheoDieuKienCanhBao(?string $loaiCanhBao, $nguong, ?PDO $pdo = null): array
    {
        if (is_array($nguong)) {
            $som = (int)($nguong['nguongSom'] ?? 15);
            $min = (int)($nguong['nguongToiThieu'] ?? 5);
            $max = (int)($nguong['nguongToiDa'] ?? 350);
        } else {
            $som = (int)$nguong;
            $min = 5;
            $max = 350;
        }
        $tatCa = BaoCao::tongHopHangSapHet(self::timTheoNguongCanhBaoSom($som, $max, $pdo), $som, $min, $max);
        if ($loaiCanhBao !== null && $loaiCanhBao !== '') {
            return array_values(array_filter($tatCa, fn($x) => $x['mucDoCanhBao'] === $loaiCanhBao));
        }
        return $tatCa;
    }
}
