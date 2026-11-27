<?php
// Entity BCE: ThanhToan — Bảng ThanhToan (Mục 3.4.3.25: Bảng 125, Bảng 126; RDM: Bảng 165)

class ThanhToan
{
    public const TEN_BANG = 'ThanhToan';
    public const KHOA_CHINH = 'maThanhToan';

    private ?int $maThanhToan = null;
    private ?int $maGioHang = null;
    private ?int $maDonHang = null;
    private string $phuongThuc = 'Thanh toán khi nhận hàng';
    private float $soTien = 0.0;
    private float $soTienHoan = 0.0;
    private string $trangThai = 'Chờ thanh toán';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maThanhToan', $duLieu)) {
            $this->maThanhToan = isset($duLieu['maThanhToan']) && $duLieu['maThanhToan'] !== '' ? (int)$duLieu['maThanhToan'] : null;
        }
        if (array_key_exists('maGioHang', $duLieu)) {
            $this->maGioHang = isset($duLieu['maGioHang']) && $duLieu['maGioHang'] !== '' ? (int)$duLieu['maGioHang'] : null;
        }
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
        }
        if (array_key_exists('phuongThuc', $duLieu)) {
            $this->phuongThuc = (string)($duLieu['phuongThuc'] ?? '');
        }
        if (array_key_exists('soTien', $duLieu)) {
            $this->soTien = (float)($duLieu['soTien'] ?? 0.0);
        }
        if (array_key_exists('soTienHoan', $duLieu)) {
            $this->soTienHoan = (float)($duLieu['soTienHoan'] ?? 0.0);
        }
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
            'maThanhToan' => $this->maThanhToan,
            'maGioHang'   => $this->maGioHang,
            'maDonHang'   => $this->maDonHang,
            'phuongThuc'  => $this->phuongThuc,
            'soTien'      => $this->soTien,
            'soTienHoan'  => $this->soTienHoan,
            'trangThai'   => $this->trangThai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC29 - Hình 37] ThanhToan::kiemTraPhuongThuc(phuongThuc)
    public static function kiemTraPhuongThuc(string $phuongThuc): bool
    {
        return in_array(trim($phuongThuc), [
            'Thanh toán khi nhận hàng',
            'Thanh toán trực tuyến mô phỏng',
        ], true);
    }

    public static function timTheoMa(int $maThanhToan, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT * FROM ThanhToan WHERE maThanhToan = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maThanhToan]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    // [UC38 - Hình 46] ThanhToan::timTheoDonHang(maDonHang) — Bảng 126 STT 12
    public static function timTheoDonHang(int $maDonHang, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT * FROM ThanhToan WHERE maDonHang = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maDonHang]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function timGiaoDichChoTheoGioHang(int $maGioHang, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT * FROM ThanhToan WHERE maGioHang = ? AND maDonHang IS NULL ORDER BY maThanhToan DESC LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maGioHang]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    // [UC29 - Hình 37] ThanhToan::ghiNhanPhuongThuc(maGioHang, phuongThuc) — Bảng 126 STT 4
    public static function ghiNhanPhuongThuc(int $maGioHang, string $phuongThuc, ?float $soTien = null, ?int $maDonHang = null, string $trangThai = 'Chờ thanh toán', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        if ($soTien === null) {
            $gio = GioHang::timTheoMa($maGioHang, false, $db);
            $soTien = (float)($gio['tongThanhToan'] ?? 0.0);
        }
        $db->prepare("INSERT INTO ThanhToan (maGioHang, maDonHang, phuongThuc, soTien, soTienHoan, trangThai) VALUES (?, ?, ?, ?, 0, ?)")
           ->execute([$maGioHang, $maDonHang, trim($phuongThuc), max(0.0, $soTien), $trangThai]);
        return (int)$db->lastInsertId();
    }

    // [UC28 - Hình 36] ThanhToan::ganDonHang(maGioHang, maDonHang) — Bảng 126 STT 3 (hỗ trợ cả maGioHang lẫn maThanhToan)
    public static function ganDonHang(int $maGioHangHoacMaTT, int $maDonHang, string $trangThai = 'Thành công', ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $theoGio = self::timGiaoDichChoTheoGioHang($maGioHangHoacMaTT, true, $db);
        if ($theoGio) {
            return $db->prepare("UPDATE ThanhToan SET maDonHang = ?, trangThai = ? WHERE maThanhToan = ?")
                      ->execute([$maDonHang, $trangThai, (int)$theoGio['maThanhToan']]);
        }
        return $db->prepare("UPDATE ThanhToan SET maDonHang = ?, trangThai = ? WHERE maThanhToan = ?")
                  ->execute([$maDonHang, $trangThai, $maGioHangHoacMaTT]);
    }

    // [UC38 - Hình 46] ThanhToan::capNhatTrangThai(maDonHang, trangThai) — Bảng 126 STT 1
    public static function capNhatTrangThai(int $maDonHang, string $trangThai, ?string $chiKhiTrangThai = null, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if ($chiKhiTrangThai !== null) {
            return $db->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maDonHang = ? AND trangThai = ?")
                      ->execute([$trangThai, $maDonHang, $chiKhiTrangThai]);
        }
        return $db->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maDonHang = ?")->execute([$trangThai, $maDonHang]);
    }

    // [UC29 - Hình 37] ThanhToan::huyGiaoDich(maGioHang) — Bảng 126 STT 5 (hỗ trợ cả maGioHang lẫn maThanhToan)
    public static function huyGiaoDich(int $maGioHangHoacMaTT, string $trangThai = 'Thất bại', ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $theoGio = self::timGiaoDichChoTheoGioHang($maGioHangHoacMaTT, true, $db);
        if ($theoGio) {
            return $db->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maThanhToan = ?")
                      ->execute([$trangThai, (int)$theoGio['maThanhToan']]);
        }
        return $db->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maThanhToan = ?")
                  ->execute([$trangThai, $maGioHangHoacMaTT]);
    }

    // [UC31, UC36 - Hình 39, Hình 44] ThanhToan::kiemTraDaThanhToanTrucTuyen(maDonHang) — Bảng 126 STT 6
    public static function kiemTraDaThanhToanTrucTuyen(int $maDonHang, ?PDO $pdo = null): bool
    {
        $tt = self::timTheoDonHang($maDonHang, false, $pdo);
        return $tt !== null && $tt['trangThai'] === 'Thành công';
    }

    // [UC31, UC36 - Hình 39, Hình 44] ThanhToan::danhDauCanHoanTien(maDonHang) — Bảng 126 STT 2
    public static function danhDauCanHoanTien(int $maDonHang, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $tt = self::timTheoDonHang($maDonHang, true, $db);
        if (!$tt) return false;
        $moi = ($tt['trangThai'] === 'Thành công') ? 'Cần hoàn tiền' : 'Đã hủy';
        return $db->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maThanhToan = ?")->execute([$moi, $tt['maThanhToan']]);
    }

    // [UC38 - Hình 46] ThanhToan::kiemTraSoTienHoan(maDonHang, soTienHoan) — Bảng 126 STT 7
    public static function kiemTraSoTienHoan(int $maDonHang, float $soTienHoan, ?PDO $pdo = null): bool
    {
        $tt = self::timTheoDonHang($maDonHang, false, $pdo);
        if (!$tt) return false;
        return $soTienHoan > 0 && $soTienHoan <= (float)$tt['soTien'];
    }

    // [UC38 - Hình 46] ThanhToan::thucHienHoanTienMoPhong(maDonHang, soTienHoan) — Bảng 126 STT 10
    public static function thucHienHoanTienMoPhong(int $maDonHang, float $soTienHoan, bool $thanhCong = true, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if ($thanhCong) {
            return $db->prepare("UPDATE ThanhToan SET soTienHoan = ?, trangThai = 'Đã hoàn tiền' WHERE maDonHang = ?")
                      ->execute([$soTienHoan, $maDonHang]);
        }
        return $db->prepare("UPDATE ThanhToan SET trangThai = 'Hoàn tiền lỗi' WHERE maDonHang = ?")->execute([$maDonHang]);
    }

    // [UC29 - Hình 37] ThanhToan::thucHienThanhToanMoPhong(maGioHang, phuongThuc) — Bảng 126 STT 11
    public static function thucHienThanhToanMoPhong(int $maGioHang, string $phuongThuc): bool
    {
        return $maGioHang > 0 && self::kiemTraPhuongThuc($phuongThuc);
    }

    // [UC29 - Hình 37] ThanhToan::layTongTienCanThanhToan(maGioHang) — Bảng 126 STT 9
    public static function layTongTienCanThanhToan(int $maGioHang, ?PDO $pdo = null): float
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT soTien FROM ThanhToan WHERE maGioHang = ? AND maDonHang IS NULL ORDER BY maThanhToan DESC LIMIT 1");
        $stmt->execute([$maGioHang]);
        $soTien = $stmt->fetchColumn();
        if ($soTien !== false && (float)$soTien > 0) {
            return (float)$soTien;
        }
        $gio = GioHang::timTheoMa($maGioHang, false, $db);
        return (float)($gio['tongThanhToan'] ?? 0.0);
    }

    // [UC28 - Hình 36] ThanhToan::layKetQuaThanhToan(maGioHang) — Bảng 126 STT 8
    public static function layKetQuaThanhToan(int $maGioHang, ?PDO $pdo = null): string
    {
        $stmt = self::pdo($pdo)->prepare("SELECT trangThai FROM ThanhToan WHERE maGioHang = ? ORDER BY maThanhToan DESC LIMIT 1");
        $stmt->execute([$maGioHang]);
        return (string)($stmt->fetchColumn() ?: 'Chờ thanh toán');
    }
}
