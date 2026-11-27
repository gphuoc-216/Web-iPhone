<?php
// Entity BCE: DieuKienApDung — Bảng DieuKienApDung

class DieuKienApDung
{
    public const TEN_BANG = 'DieuKienApDung';
    public const KHOA_CHINH = 'maDieuKien';

    private ?int $maDieuKien = null;
    private ?int $maKhuyenMai = null;
    private ?string $maVoucher = null;
    private float $giaTriToiThieu = 0.0;
    private ?string $nhomHang = null;
    private ?string $doiTuong = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maDieuKien', $duLieu)) {
            $this->maDieuKien = isset($duLieu['maDieuKien']) && $duLieu['maDieuKien'] !== '' ? (int)$duLieu['maDieuKien'] : null;
        }
        if (array_key_exists('maKhuyenMai', $duLieu)) {
            $this->maKhuyenMai = isset($duLieu['maKhuyenMai']) && $duLieu['maKhuyenMai'] !== '' ? (int)$duLieu['maKhuyenMai'] : null;
        }
        if (array_key_exists('maVoucher', $duLieu)) {
            $this->maVoucher = isset($duLieu['maVoucher']) && $duLieu['maVoucher'] !== '' ? (string)$duLieu['maVoucher'] : null;
        }
        if (array_key_exists('giaTriToiThieu', $duLieu)) {
            $this->giaTriToiThieu = (float)($duLieu['giaTriToiThieu'] ?? 0.0);
        }
        if (array_key_exists('nhomHang', $duLieu)) {
            $this->nhomHang = isset($duLieu['nhomHang']) && $duLieu['nhomHang'] !== '' ? (string)$duLieu['nhomHang'] : null;
        }
        if (array_key_exists('doiTuong', $duLieu)) {
            $this->doiTuong = isset($duLieu['doiTuong']) && $duLieu['doiTuong'] !== '' ? (string)$duLieu['doiTuong'] : null;
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
            'maDieuKien' => $this->maDieuKien,
            'maKhuyenMai' => $this->maKhuyenMai,
            'maVoucher' => $this->maVoucher,
            'giaTriToiThieu' => $this->giaTriToiThieu,
            'nhomHang' => $this->nhomHang,
            'doiTuong' => $this->doiTuong,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function timTheoKhuyenMai(int $maKhuyenMai, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM DieuKienApDung WHERE maKhuyenMai = ? LIMIT 1");
        $stmt->execute([$maKhuyenMai]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function timTheoVoucher(string $maVoucher, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM DieuKienApDung WHERE maVoucher = ? LIMIT 1");
        $stmt->execute([strtoupper(trim($maVoucher))]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function luu(int $maKhuyenMai, float $giaTriToiThieu, ?string $nhomHang = 'Tất cả', ?string $doiTuong = 'Tất cả khách hàng', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO DieuKienApDung (maKhuyenMai, maVoucher, giaTriToiThieu, nhomHang, doiTuong) VALUES (?, NULL, ?, ?, ?)")
           ->execute([$maKhuyenMai, max(0, $giaTriToiThieu), $nhomHang ?: 'Tất cả', $doiTuong ?: 'Tất cả khách hàng']);
        return (int)$db->lastInsertId();
    }

    public static function capNhat(int $maKhuyenMai, float $giaTriToiThieu, ?string $nhomHang = 'Tất cả', ?string $doiTuong = 'Tất cả khách hàng', ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if (self::timTheoKhuyenMai($maKhuyenMai, $db)) {
            return $db->prepare("UPDATE DieuKienApDung SET giaTriToiThieu = ?, nhomHang = ?, doiTuong = ? WHERE maKhuyenMai = ?")
                      ->execute([max(0, $giaTriToiThieu), $nhomHang ?: 'Tất cả', $doiTuong ?: 'Tất cả khách hàng', $maKhuyenMai]);
        }
        self::luu($maKhuyenMai, $giaTriToiThieu, $nhomHang, $doiTuong, $db);
        return true;
    }

    public static function lienKet(string $maVoucher, array $dieuKien, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $ma = strtoupper(trim($maVoucher));
        $toiThieu = max(0, (float)($dieuKien['giaTriToiThieu'] ?? 0));
        $nhom = $dieuKien['nhomHang'] ?? 'Tất cả';
        $doiTuong = $dieuKien['doiTuong'] ?? 'Khách hàng đã đăng nhập';

        if (self::timTheoVoucher($ma, $db)) {
            return $db->prepare("UPDATE DieuKienApDung SET giaTriToiThieu = ?, nhomHang = ?, doiTuong = ? WHERE maVoucher = ?")
                      ->execute([$toiThieu, $nhom, $doiTuong, $ma]);
        }
        return $db->prepare("INSERT INTO DieuKienApDung (maKhuyenMai, maVoucher, giaTriToiThieu, nhomHang, doiTuong) VALUES (NULL, ?, ?, ?, ?)")
                  ->execute([$ma, $toiThieu, $nhom, $doiTuong]);
    }

    public static function kiemTraDieuKien(string $maVoucher, int $maGioHang, float $tongTamTinh, ?int $maKhachHang = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $dk = self::timTheoVoucher($maVoucher, $db);
        if (!$dk) {
            return ['hopLe' => true, 'thongBao' => 'Hợp lệ'];
        }
        if (!empty($dk['doiTuong']) && stripos($dk['doiTuong'], 'đăng nhập') !== false && $maKhachHang === null) {
            return ['hopLe' => false, 'thongBao' => 'Mã voucher này chỉ áp dụng cho khách hàng đã đăng nhập', 'maHttp' => 401];
        }
        if ($tongTamTinh < (float)$dk['giaTriToiThieu']) {
            $toiThieu = number_format((float)$dk['giaTriToiThieu'], 0, ',', '.');
            return ['hopLe' => false, 'thongBao' => "Tổng tiền hàng chưa đạt giá trị tối thiểu {$toiThieu}đ để áp dụng voucher", 'maHttp' => 400];
        }
        $nhomHang = trim((string)($dk['nhomHang'] ?? ''));
        if ($nhomHang !== '' && $nhomHang !== 'Tất cả') {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM ChiTietGioHang ct
                 JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai
                 LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                 WHERE ct.maGioHang = ? AND (d.hang = ? OR dm.ten = ?)"
            );
            $stmt->execute([$maGioHang, $nhomHang, $nhomHang]);
            if ((int)$stmt->fetchColumn() === 0) {
                return ['hopLe' => false, 'thongBao' => "Mã voucher chỉ áp dụng cho nhóm hàng: {$nhomHang}", 'maHttp' => 400];
            }
        }
        return ['hopLe' => true, 'thongBao' => 'Hợp lệ'];
    }

    public static function xoaTheoKhuyenMai(int $maKhuyenMai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("DELETE FROM DieuKienApDung WHERE maKhuyenMai = ?")->execute([$maKhuyenMai]);
    }

    public static function xoaTheoVoucher(string $maVoucher, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("DELETE FROM DieuKienApDung WHERE maVoucher = ?")->execute([strtoupper(trim($maVoucher))]);
    }

}
