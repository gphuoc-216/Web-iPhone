<?php
// Entity BCE: DieuKienApDung — Bảng DieuKienApDung (Mục 3.4.3.13: Bảng 101, Bảng 102; RDM: Bảng 152)

class DieuKienApDung implements ArrayAccess, JsonSerializable
{
    public const TEN_BANG = 'DieuKienApDung';
    public const KHOA_CHINH = 'maDieuKien';

    private ?int $maDieuKien = null;
    private ?int $maKhuyenMai = null;
    private ?string $maVoucher = null;
    private float $giaTriToiThieu = 0.0;
    private string $nhomHang = 'Tất cả';
    private string $doiTuong = 'Tất cả khách hàng';

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
        if (array_key_exists('nhomHang', $duLieu) && $duLieu['nhomHang'] !== null && $duLieu['nhomHang'] !== '') {
            $this->nhomHang = (string)$duLieu['nhomHang'];
        }
        if (array_key_exists('doiTuong', $duLieu) && $duLieu['doiTuong'] !== null && $duLieu['doiTuong'] !== '') {
            $this->doiTuong = (string)$duLieu['doiTuong'];
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

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) && property_exists($this, $offset) ? $this->$offset : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_string($offset) && property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
    }

    public function jsonSerialize(): array
    {
        return $this->chuyenThanhMang();
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maDieuKien'     => $this->maDieuKien,
            'maKhuyenMai'    => $this->maKhuyenMai,
            'maVoucher'      => $this->maVoucher,
            'giaTriToiThieu' => $this->giaTriToiThieu,
            'nhomHang'       => $this->nhomHang,
            'doiTuong'       => $this->doiTuong,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC58 - Hình 66] DieuKienApDung::timTheoKhuyenMai(maKhuyenMai) — Bảng 102 STT 5
    public static function timTheoKhuyenMai(int $maKhuyenMai, ?PDO $pdo = null): ?self
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM DieuKienApDung WHERE maKhuyenMai = ? LIMIT 1");
        $stmt->execute([$maKhuyenMai]);
        $r = $stmt->fetch();
        return $r ? self::tuMang($r) : null;
    }

    // [UC61 - Hình 69] DieuKienApDung::timTheoVoucher(maVoucher) — Bảng 102 STT 6 (trả về 1 đối tượng DieuKienApDung)
    public static function timTheoVoucher(string $maVoucher, ?PDO $pdo = null): ?self
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM DieuKienApDung WHERE maVoucher = ? LIMIT 1");
        $stmt->execute([strtoupper(trim($maVoucher))]);
        $r = $stmt->fetch();
        return $r ? self::tuMang($r) : null;
    }

    // [UC57 - Hình 65] DieuKienApDung::luu(maKhuyenMai, giaTriToiThieu, nhomHang, doiTuong) — Bảng 102 STT 4
    public static function luu(int $maKhuyenMai, float $giaTriToiThieu, ?string $nhomHang = 'Tất cả', ?string $doiTuong = 'Tất cả khách hàng', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO DieuKienApDung (maKhuyenMai, maVoucher, giaTriToiThieu, nhomHang, doiTuong) VALUES (?, NULL, ?, ?, ?)")
           ->execute([$maKhuyenMai, max(0, $giaTriToiThieu), $nhomHang ?: 'Tất cả', $doiTuong ?: 'Tất cả khách hàng']);
        return (int)$db->lastInsertId();
    }

    // [UC58 - Hình 66] DieuKienApDung::capNhat(maKhuyenMai, giaTriToiThieu, nhomHang, doiTuong) — Bảng 102 STT 1
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

    // [UC60 - Hình 68] DieuKienApDung::lienKet(maVoucher, dieuKien) — Bảng 102 STT 3
    public static function lienKet(string $maVoucher, $dieuKien, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $ma = strtoupper(trim($maVoucher));
        $dk = ($dieuKien instanceof self) ? $dieuKien->chuyenThanhMang() : (is_array($dieuKien) ? $dieuKien : []);
        $toiThieu = max(0, (float)($dk['giaTriToiThieu'] ?? 0));
        $nhom = trim((string)($dk['nhomHang'] ?? '')) ?: 'Tất cả';
        $doiTuong = trim((string)($dk['doiTuong'] ?? '')) ?: 'Khách hàng đã đăng nhập';

        if (self::timTheoVoucher($ma, $db)) {
            return $db->prepare("UPDATE DieuKienApDung SET giaTriToiThieu = ?, nhomHang = ?, doiTuong = ? WHERE maVoucher = ?")
                      ->execute([$toiThieu, $nhom, $doiTuong, $ma]);
        }
        return $db->prepare("INSERT INTO DieuKienApDung (maKhuyenMai, maVoucher, giaTriToiThieu, nhomHang, doiTuong) VALUES (NULL, ?, ?, ?, ?)")
                  ->execute([$ma, $toiThieu, $nhom, $doiTuong]);
    }

    // [UC30 - Hình 38] DieuKienApDung::kiemTraDieuKien(maVoucher, maGioHang) — Bảng 102 STT 2 (trả về boolean đúng thiết kế lớp)
    public static function kiemTraDieuKien(string $maVoucher, int $maGioHang, ?float $tongTamTinh = null, ?int $maKhachHang = null, ?PDO $pdo = null): bool
    {
        $kq = self::kiemTraDieuKienChiTiet($maVoucher, $maGioHang, $tongTamTinh, $maKhachHang, $pdo);
        return (bool)$kq['hopLe'];
    }

    public static function kiemTraDieuKienChiTiet(string $maVoucher, int $maGioHang, ?float $tongTamTinh = null, ?int $maKhachHang = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $dk = self::timTheoVoucher($maVoucher, $db);
        if (!$dk) {
            return ['hopLe' => true, 'thongBao' => 'Hợp lệ'];
        }
        if ($tongTamTinh === null) {
            $tongTamTinh = GioHang::tinhTongTamTinh($maGioHang, $db);
        }
        if ($maKhachHang === null) {
            $gio = GioHang::timTheoMa($maGioHang, false, $db);
            $maKhachHang = !empty($gio['maKhachHang']) ? (int)$gio['maKhachHang'] : null;
        }
        if (!empty($dk['doiTuong']) && stripos($dk['doiTuong'], 'đăng nhập') !== false && $maKhachHang === null) {
            return ['hopLe' => false, 'thongBao' => 'Đơn hàng chưa đạt điều kiện áp dụng: Mã giảm giá này yêu cầu đăng nhập tài khoản khách hàng', 'maHttp' => 401];
        }
        if ($tongTamTinh < (float)$dk['giaTriToiThieu']) {
            $toiThieu = number_format((float)$dk['giaTriToiThieu'], 0, ',', '.');
            return ['hopLe' => false, 'thongBao' => "Đơn hàng chưa đạt điều kiện áp dụng: Giá trị đơn hàng tối thiểu phải từ {$toiThieu}đ", 'maHttp' => 400];
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
                return ['hopLe' => false, 'thongBao' => "Đơn hàng chưa đạt điều kiện áp dụng: Mã giảm giá chỉ áp dụng cho nhóm sản phẩm {$nhomHang}", 'maHttp' => 400];
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
