<?php
// Entity BCE: GioHang — Bảng GioHang (Mục 3.4.3.15: Bảng 105, Bảng 106; RDM: Bảng 154 — 3NF: không lưu vật lý tongTamTinh, tongTien)

class GioHang
{
    public const TEN_BANG = 'GioHang';
    public const KHOA_CHINH = 'maGioHang';

    private ?int $maGioHang = null;
    private ?int $maKhachHang = null;
    private string $maPhien = '';
    private ?string $maVoucher = null;
    private float $tongTamTinh = 0.0;
    private float $tienGiamGia = 0.0;
    private float $tongTien = 0.0;
    private string $trangThai = 'Đang dùng';
    private ?int $maDiaChi = null;
    private float $phiVanChuyen = 0.0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maGioHang', $duLieu)) {
            $this->maGioHang = isset($duLieu['maGioHang']) && $duLieu['maGioHang'] !== '' ? (int)$duLieu['maGioHang'] : null;
        }
        if (array_key_exists('maKhachHang', $duLieu)) {
            $this->maKhachHang = isset($duLieu['maKhachHang']) && $duLieu['maKhachHang'] !== '' ? (int)$duLieu['maKhachHang'] : null;
        }
        if (array_key_exists('maPhien', $duLieu)) {
            $this->maPhien = (string)($duLieu['maPhien'] ?? '');
        }
        if (array_key_exists('maVoucher', $duLieu)) {
            $this->maVoucher = isset($duLieu['maVoucher']) && $duLieu['maVoucher'] !== '' ? (string)$duLieu['maVoucher'] : null;
        }
        if (array_key_exists('tongTamTinh', $duLieu)) {
            $this->tongTamTinh = (float)($duLieu['tongTamTinh'] ?? 0.0);
        }
        if (array_key_exists('tienGiamGia', $duLieu)) {
            $this->tienGiamGia = (float)($duLieu['tienGiamGia'] ?? 0.0);
        }
        if (array_key_exists('maDiaChi', $duLieu)) {
            $this->maDiaChi = isset($duLieu['maDiaChi']) && $duLieu['maDiaChi'] !== '' ? (int)$duLieu['maDiaChi'] : null;
        }
        if (array_key_exists('phiVanChuyen', $duLieu)) {
            $this->phiVanChuyen = (float)($duLieu['phiVanChuyen'] ?? 0.0);
        }
        $this->tongTien = array_key_exists('tongTien', $duLieu)
            ? (float)$duLieu['tongTien']
            : max(0.0, $this->tongTamTinh - $this->tienGiamGia);
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
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
            'maGioHang'    => $this->maGioHang,
            'maKhachHang'  => $this->maKhachHang,
            'maPhien'      => $this->maPhien,
            'maVoucher'    => $this->maVoucher,
            'tongTamTinh'  => $this->tongTamTinh,
            'tienGiamGia'  => $this->tienGiamGia,
            'tongTien'     => $this->tongTien,
            'trangThai'    => $this->trangThai,
            'maDiaChi'     => $this->maDiaChi,
            'phiVanChuyen' => $this->phiVanChuyen,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    private static function boSungThuocTinhDanXuat(array $dong, ?PDO $pdo = null): array
    {
        $tamTinh = self::tinhTongTamTinh((int)$dong['maGioHang'], $pdo);
        $giam = (float)($dong['tienGiamGia'] ?? 0.0);
        $phi = (float)($dong['phiVanChuyen'] ?? 0.0);
        $dong['tongTamTinh'] = $tamTinh;
        $dong['tienGiamGia'] = $giam;
        $dong['phiVanChuyen'] = $phi;
        $dong['tongTien'] = max(0.0, $tamTinh - $giam);
        $dong['tongThanhToan'] = max(0.0, $tamTinh - $giam + $phi);
        return $dong;
    }

    public static function timTheoMa(int $maGioHang, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $db = self::pdo($pdo);
        $sql = "SELECT * FROM GioHang WHERE maGioHang = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = $db->prepare($sql);
        $stmt->execute([$maGioHang]);
        $r = $stmt->fetch();
        return $r ? self::boSungThuocTinhDanXuat($r, $db) : null;
    }

    // [UC22 - Hình 30] GioHang::timTheoKhachHang(maKhachHang) — Bảng 106 STT 8
    public static function timTheoKhachHang(int $maKhachHang, ?PDO $pdo = null): ?array
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT * FROM GioHang WHERE maKhachHang = ? AND trangThai = 'Đang dùng' ORDER BY maGioHang DESC LIMIT 1");
        $stmt->execute([$maKhachHang]);
        $r = $stmt->fetch();
        return $r ? self::boSungThuocTinhDanXuat($r, $db) : null;
    }

    // [UC22 - Hình 30] GioHang::layGioHang(maPhien) — Bảng 106 STT 6
    public static function layGioHang(string $maPhien, ?PDO $pdo = null): ?array
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT * FROM GioHang WHERE maPhien = ? AND trangThai = 'Đang dùng' ORDER BY maGioHang DESC LIMIT 1");
        $stmt->execute([$maPhien]);
        $r = $stmt->fetch();
        return $r ? self::boSungThuocTinhDanXuat($r, $db) : null;
    }

    // [UC22 - Hình 30] GioHang::taoGioHangTam(maPhien) — Bảng 106 STT 7
    public static function taoGioHangTam(string $maPhien, ?int $maKhachHang = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO GioHang (maKhachHang, maPhien, maVoucher, tienGiamGia, trangThai, maDiaChi, phiVanChuyen) VALUES (?, ?, NULL, 0, 'Đang dùng', NULL, 0)")
           ->execute([$maKhachHang, $maPhien]);
        $ma = (int)$db->lastInsertId();
        return [
            'maGioHang'     => $ma,
            'maKhachHang'   => $maKhachHang,
            'maPhien'       => $maPhien,
            'maVoucher'     => null,
            'tongTamTinh'   => 0.0,
            'tienGiamGia'   => 0.0,
            'tongTien'      => 0.0,
            'tongThanhToan' => 0.0,
            'trangThai'     => 'Đang dùng',
            'maDiaChi'      => null,
            'phiVanChuyen'  => 0.0,
        ];
    }

    public static function timHoacTao(?int $maKhachHang, string $maPhien, ?PDO $pdo = null): array
    {
        if ($maKhachHang !== null) {
            $gio = self::timTheoKhachHang($maKhachHang, $pdo);
            if ($gio) return $gio;
        }
        $gio = self::layGioHang($maPhien, $pdo);
        if ($gio) return $gio;
        return self::taoGioHangTam($maPhien, $maKhachHang, $pdo);
    }

    public static function kiemTraSoHuu(array $gio, ?int $maKhachHang, string $maPhien): bool
    {
        if ($maKhachHang !== null && !empty($gio['maKhachHang'])) {
            return (int)$gio['maKhachHang'] === $maKhachHang;
        }
        return (string)($gio['maPhien'] ?? '') === $maPhien;
    }

    // [UC29 - Hình 37] GioHang::kiemTraGiuChoTam(maGioHang) — Bảng 106 STT 5
    public static function kiemTraGiuChoTam(int $maGioHang, ?PDO $pdo = null): bool
    {
        $gio = self::timTheoMa($maGioHang, false, $pdo);
        return $gio !== null && $gio['trangThai'] === 'Đã khóa';
    }

    // [UC22, UC23, UC24 - Hình 30 - Hình 32] GioHang::tinhTongTamTinh(maGioHang) — Bảng 106 STT 11
    public static function tinhTongTamTinh(int $maGioHang, ?PDO $pdo = null): float
    {
        $stmt = self::pdo($pdo)->prepare("SELECT COALESCE(SUM(soLuong * donGia), 0) FROM ChiTietGioHang WHERE maGioHang = ?");
        $stmt->execute([$maGioHang]);
        return (float)$stmt->fetchColumn();
    }

    // [UC30 - Hình 38] GioHang::tinhGiamGia(maGioHang, maVoucher) — Bảng 106 STT 9
    public static function tinhGiamGia(int $maGioHang, string $maVoucher, ?int $maKhachHang = null, ?PDO $pdo = null): float
    {
        $db = self::pdo($pdo);
        $tongTamTinh = self::tinhTongTamTinh($maGioHang, $db);
        $voucher = Voucher::timTheoMa($maVoucher, $db);
        if (!$voucher || !Voucher::kiemTraHopLe($maVoucher, $db)) {
            return 0.0;
        }
        if ($maKhachHang === null) {
            $gio = self::timTheoMa($maGioHang, false, $db);
            $maKhachHang = !empty($gio['maKhachHang']) ? (int)$gio['maKhachHang'] : null;
        }
        if ($maKhachHang !== null && DonHang::demLuotDungVoucher($maKhachHang, $maVoucher, $db) >= (int)$voucher['gioiHanSuDung']) {
            return 0.0;
        }
        if (!DieuKienApDung::kiemTraDieuKien($maVoucher, $maGioHang, $tongTamTinh, $maKhachHang, $db)) {
            return 0.0;
        }
        $soTienGiam = $voucher['loaiGiam'] === 'Phần trăm'
            ? round($tongTamTinh * ((float)$voucher['giaTriGiam'] / 100), 2)
            : min($tongTamTinh, (float)$voucher['giaTriGiam']);

        $db->prepare("UPDATE GioHang SET maVoucher = ?, tienGiamGia = ? WHERE maGioHang = ?")
           ->execute([$maVoucher, $soTienGiam, $maGioHang]);

        return $soTienGiam;
    }

    // [UC30 - Hình 38] GioHang::tinhLaiTongTien(maGioHang) — Bảng 106 STT 10
    public static function tinhLaiTongTien(int $maGioHang, ?PDO $pdo = null): float
    {
        $db = self::pdo($pdo);
        $tongTamTinh = self::tinhTongTamTinh($maGioHang, $db);
        $stmt = $db->prepare("SELECT maVoucher, maKhachHang, phiVanChuyen FROM GioHang WHERE maGioHang = ? LIMIT 1");
        $stmt->execute([$maGioHang]);
        $gio = $stmt->fetch() ?: [];

        $tienGiamGia = 0.0;
        $maVoucher = $gio['maVoucher'] ?? null;
        $maKh = !empty($gio['maKhachHang']) ? (int)$gio['maKhachHang'] : null;
        $phiVanChuyen = (float)($gio['phiVanChuyen'] ?? 0.0);

        if (!empty($maVoucher)) {
            $giam = self::tinhGiamGia($maGioHang, (string)$maVoucher, $maKh, $db);
            if ($giam > 0) {
                $tienGiamGia = $giam;
            } else {
                $maVoucher = null;
                $tienGiamGia = 0.0;
                $db->prepare("UPDATE GioHang SET maVoucher = NULL, tienGiamGia = 0 WHERE maGioHang = ?")
                   ->execute([$maGioHang]);
            }
        } else {
            $db->prepare("UPDATE GioHang SET tienGiamGia = 0 WHERE maGioHang = ?")->execute([$maGioHang]);
        }

        return max(0.0, $tongTamTinh - $tienGiamGia + $phiVanChuyen);
    }

    public static function goBoVoucher(int $maGioHang, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE GioHang SET maVoucher = NULL, tienGiamGia = 0 WHERE maGioHang = ?")
            ->execute([$maGioHang]);
    }

    // [UC28 - Hình 36] GioHang::khoaGioHang(maGioHang) — Bảng 106 STT 4
    public static function khoaGioHang(int $maGioHang, ?int $maKhachHang = null, ?PDO $pdo = null): bool
    {
        if ($maKhachHang !== null) {
            return self::pdo($pdo)->prepare("UPDATE GioHang SET trangThai = 'Đã khóa', maKhachHang = ? WHERE maGioHang = ?")
                ->execute([$maKhachHang, $maGioHang]);
        }
        return self::pdo($pdo)->prepare("UPDATE GioHang SET trangThai = 'Đã khóa' WHERE maGioHang = ?")->execute([$maGioHang]);
    }

    public static function moKhoaGioHang(int $maGioHang, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE GioHang SET trangThai = 'Đang dùng' WHERE maGioHang = ?")->execute([$maGioHang]);
    }

    // [UC28 - Hình 36] GioHang::xoaSanPhamDaDat(maGioHang) — Bảng 106 STT 12
    public static function xoaSanPhamDaDat(int $maGioHang, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $db->prepare("DELETE FROM ChiTietGioHang WHERE maGioHang = ?")->execute([$maGioHang]);
        return $db->prepare("UPDATE GioHang SET maVoucher = NULL, tienGiamGia = 0, phiVanChuyen = 0, trangThai = 'Đang dùng' WHERE maGioHang = ?")
                  ->execute([$maGioHang]);
    }

    // [UC39, UC40 - Hình 47, Hình 48] GioHang::gopGioHangTam(maPhien, maKhachHang) — Bảng 106 STT 3
    public static function gopGioHangTam(string $maPhien, int $maKhachHang, string $maPhienMoi = '', ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT * FROM GioHang WHERE maPhien = ? AND maKhachHang IS NULL AND trangThai = 'Đang dùng' ORDER BY maGioHang DESC LIMIT 1");
        $stmt->execute([$maPhien]);
        $gioTam = $stmt->fetch();
        if (!$gioTam) return false;

        $phienDich = $maPhienMoi !== '' ? $maPhienMoi : $maPhien;
        $gioKhach = self::timHoacTao($maKhachHang, $phienDich, $db);
        $maGioDich = (int)$gioKhach['maGioHang'];
        $maGioNguon = (int)$gioTam['maGioHang'];

        if ($maGioDich === $maGioNguon) {
            $db->prepare("UPDATE GioHang SET maKhachHang = ?, maPhien = ? WHERE maGioHang = ?")
               ->execute([$maKhachHang, $phienDich, $maGioDich]);
            self::tinhLaiTongTien($maGioDich, $db);
            return true;
        }

        foreach (ChiTietGioHang::timTheoGioHang($maGioNguon, $db) as $ct) {
            $maDt = (int)$ct['maDienThoai'];
            $tonKhaDung = TonKho::tinhTonKhaDung($maDt, $db);
            if ($tonKhaDung <= 0) continue;
            ChiTietGioHang::themHoacCongDon($maGioDich, $maDt, (int)$ct['soLuong'], (float)$ct['donGia'], $tonKhaDung, $db);
        }

        if (!empty($gioTam['maVoucher']) && empty($gioKhach['maVoucher'])) {
            $db->prepare("UPDATE GioHang SET maVoucher = ? WHERE maGioHang = ?")->execute([$gioTam['maVoucher'], $maGioDich]);
        }

        $db->prepare("DELETE FROM ChiTietGioHang WHERE maGioHang = ?")->execute([$maGioNguon]);
        self::khoaGioHang($maGioNguon, null, $db);
        self::tinhLaiTongTien($maGioDich, $db);
        return true;
    }

    // [UC26 - Hình 34] GioHang::capNhatPhiVanChuyen(maGioHang, phiVanChuyen) — Bảng 106 STT 1
    public static function capNhatPhiVanChuyen(int $maGioHang, float $phiVanChuyen, ?PDO $pdo = null): float
    {
        $db = self::pdo($pdo);
        $phiSach = max(0.0, $phiVanChuyen);
        $db->prepare("UPDATE GioHang SET phiVanChuyen = ? WHERE maGioHang = ?")->execute([$phiSach, $maGioHang]);
        $gio = self::timTheoMa($maGioHang, false, $db);
        return max(0.0, (float)($gio['tongTien'] ?? 0.0) + $phiSach);
    }

    // [UC25 - Hình 33] GioHang::ghiNhanDiaChi(maGioHang, maDiaChi) — Bảng 106 STT 2
    public static function ghiNhanDiaChi(int $maGioHang, int $maDiaChi, ?PDO $pdo = null): bool
    {
        if ($maGioHang <= 0 || $maDiaChi <= 0) return false;
        return self::pdo($pdo)->prepare("UPDATE GioHang SET maDiaChi = ? WHERE maGioHang = ?")
            ->execute([$maDiaChi, $maGioHang]);
    }
}
