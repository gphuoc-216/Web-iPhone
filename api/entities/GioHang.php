<?php
// Entity BCE: GioHang — Bảng GioHang

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
        if (array_key_exists('tongTien', $duLieu)) {
            $this->tongTien = (float)($duLieu['tongTien'] ?? 0.0);
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
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
            'maGioHang' => $this->maGioHang,
            'maKhachHang' => $this->maKhachHang,
            'maPhien' => $this->maPhien,
            'maVoucher' => $this->maVoucher,
            'tongTamTinh' => $this->tongTamTinh,
            'tienGiamGia' => $this->tienGiamGia,
            'tongTien' => $this->tongTien,
            'trangThai' => $this->trangThai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function timTheoMa(int $maGioHang, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT * FROM GioHang WHERE maGioHang = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maGioHang]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function timTheoKhachHang(int $maKhachHang, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM GioHang WHERE maKhachHang = ? AND trangThai = 'Đang dùng' ORDER BY maGioHang DESC LIMIT 1");
        $stmt->execute([$maKhachHang]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function layGioHang(string $maPhien, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM GioHang WHERE maPhien = ? AND trangThai = 'Đang dùng' ORDER BY maGioHang DESC LIMIT 1");
        $stmt->execute([$maPhien]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function taoGioHangTam(string $maPhien, ?int $maKhachHang = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO GioHang (maKhachHang, maPhien, maVoucher, tongTamTinh, tienGiamGia, tongTien, trangThai) VALUES (?, ?, NULL, 0, 0, 0, 'Đang dùng')")
           ->execute([$maKhachHang, $maPhien]);
        $ma = (int)$db->lastInsertId();
        return [
            'maGioHang'   => $ma,
            'maKhachHang' => $maKhachHang,
            'maPhien'     => $maPhien,
            'maVoucher'   => null,
            'tongTamTinh' => 0.0,
            'tienGiamGia' => 0.0,
            'tongTien'    => 0.0,
            'trangThai'   => 'Đang dùng',
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

    public static function kiemTraDangNhap(string $maPhien, ?PDO $pdo = null): bool
    {
        return PhienDangNhap::kiemTraConHieuLuc($maPhien, $pdo);
    }

    public static function kiemTraGiuChoTam(int $maGioHang, ?PDO $pdo = null): bool
    {
        $gio = self::timTheoMa($maGioHang, false, $pdo);
        return $gio !== null && $gio['trangThai'] === 'Đã khóa';
    }

    public static function tinhTongTamTinh(int $maGioHang, ?PDO $pdo = null): float
    {
        $stmt = self::pdo($pdo)->prepare("SELECT COALESCE(SUM(soLuong * donGia), 0) FROM ChiTietGioHang WHERE maGioHang = ?");
        $stmt->execute([$maGioHang]);
        return (float)$stmt->fetchColumn();
    }

    public static function tinhGiamGia(int $maGioHang, string $maVoucher, ?int $maKhachHang = null, ?PDO $pdo = null): float
    {
        $db = self::pdo($pdo);
        $tongTamTinh = self::tinhTongTamTinh($maGioHang, $db);
        $voucher = Voucher::timTheoMa($maVoucher, $db);
        if (!$voucher || !Voucher::kiemTraMaHopLe($maVoucher, $db)) {
            return 0.0;
        }
        if ($maKhachHang !== null && !Voucher::kiemTraLuotSuDung($maVoucher, $maKhachHang, $db)) {
            return 0.0;
        }
        $ktDk = DieuKienApDung::kiemTraDieuKien($maVoucher, $maGioHang, $tongTamTinh, $maKhachHang, $db);
        if (!$ktDk['hopLe']) {
            return 0.0;
        }
        return $voucher['loaiGiam'] === 'Phần trăm'
            ? round($tongTamTinh * ((float)$voucher['giaTriGiam'] / 100), 2)
            : min($tongTamTinh, (float)$voucher['giaTriGiam']);
    }

    public static function tinhLaiTongTien(int $maGioHang, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $tongTamTinh = self::tinhTongTamTinh($maGioHang, $db);
        $gio = self::timTheoMa($maGioHang, false, $db);
        $tienGiamGia = 0.0;
        $maVoucher = $gio['maVoucher'] ?? null;
        $maKh = isset($gio['maKhachHang']) ? (int)$gio['maKhachHang'] : null;

        // Tự động kiểm tra lại cả hiệu lực Voucher lẫn DieuKienApDung (giaTriToiThieu, nhomHang) khi sửa/xóa SP trong giỏ
        if (!empty($maVoucher)) {
            $giam = self::tinhGiamGia($maGioHang, $maVoucher, $maKh, $db);
            if ($giam > 0) {
                $tienGiamGia = $giam;
            } else {
                $maVoucher = null;
                $tienGiamGia = 0.0;
            }
        }

        $tongTien = max(0.0, $tongTamTinh - $tienGiamGia);
        $db->prepare("UPDATE GioHang SET maVoucher = ?, tongTamTinh = ?, tienGiamGia = ?, tongTien = ? WHERE maGioHang = ?")
           ->execute([$maVoucher, $tongTamTinh, $tienGiamGia, $tongTien, $maGioHang]);

        return ['tongTamTinh' => $tongTamTinh, 'tienGiamGia' => $tienGiamGia, 'tongTien' => $tongTien, 'maVoucher' => $maVoucher];
    }

    public static function ganVoucher(int $maGioHang, string $maVoucher, float $tongTamTinh, float $tienGiamGia, float $tongTien, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE GioHang SET maVoucher = ?, tongTamTinh = ?, tienGiamGia = ?, tongTien = ? WHERE maGioHang = ?")
            ->execute([$maVoucher, $tongTamTinh, $tienGiamGia, $tongTien, $maGioHang]);
    }

    public static function goBoVoucher(int $maGioHang, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE GioHang SET maVoucher = NULL, tienGiamGia = 0, tongTien = tongTamTinh WHERE maGioHang = ?")
            ->execute([$maGioHang]);
    }

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

    public static function xoaSanPhamDaDat(int $maGioHang, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $db->prepare("DELETE FROM ChiTietGioHang WHERE maGioHang = ?")->execute([$maGioHang]);
        return $db->prepare("UPDATE GioHang SET maVoucher = NULL, tongTamTinh = 0, tienGiamGia = 0, tongTien = 0, trangThai = 'Đã khóa' WHERE maGioHang = ?")
                  ->execute([$maGioHang]);
    }

    public static function gopGioHangTam(string $maPhienTam, int $maKhachHang, string $maPhienMoi = '', ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT * FROM GioHang WHERE maPhien = ? AND maKhachHang IS NULL AND trangThai = 'Đang dùng' ORDER BY maGioHang DESC LIMIT 1");
        $stmt->execute([$maPhienTam]);
        $gioTam = $stmt->fetch();
        if (!$gioTam) return false;

        $phienDich = $maPhienMoi !== '' ? $maPhienMoi : $maPhienTam;
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

    public static function capNhatPhiVanChuyen(int $maGioHang, float $phiVanChuyen, ?PDO $pdo = null): float
    {
        $gio = self::timTheoMa($maGioHang, false, $pdo);
        return max(0.0, (float)($gio['tongTien'] ?? 0) + $phiVanChuyen);
    }

    public static function ghiNhanDiaChi(int $maGioHang, int $maDiaChi): bool
    {
        return $maGioHang > 0 && $maDiaChi > 0;
    }

}
