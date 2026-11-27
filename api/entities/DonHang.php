<?php
// Entity BCE: DonHang — Bảng DonHang

class DonHang
{
    public const TEN_BANG = 'DonHang';
    public const KHOA_CHINH = 'maDonHang';

    private ?int $maDonHang = null;
    private ?int $maKhachHang = null;
    private ?int $maDiaChi = null;
    private ?string $maVoucher = null;
    private ?string $ngayDat = null;
    private float $phiVanChuyen = 0.0;
    private float $tongTien = 0.0;
    private string $trangThai = 'Chờ duyệt';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
        }
        if (array_key_exists('maKhachHang', $duLieu)) {
            $this->maKhachHang = isset($duLieu['maKhachHang']) && $duLieu['maKhachHang'] !== '' ? (int)$duLieu['maKhachHang'] : null;
        }
        if (array_key_exists('maDiaChi', $duLieu)) {
            $this->maDiaChi = isset($duLieu['maDiaChi']) && $duLieu['maDiaChi'] !== '' ? (int)$duLieu['maDiaChi'] : null;
        }
        if (array_key_exists('maVoucher', $duLieu)) {
            $this->maVoucher = isset($duLieu['maVoucher']) && $duLieu['maVoucher'] !== '' ? (string)$duLieu['maVoucher'] : null;
        }
        if (array_key_exists('ngayDat', $duLieu)) {
            $this->ngayDat = isset($duLieu['ngayDat']) && $duLieu['ngayDat'] !== '' ? (string)$duLieu['ngayDat'] : null;
        }
        if (array_key_exists('phiVanChuyen', $duLieu)) {
            $this->phiVanChuyen = (float)($duLieu['phiVanChuyen'] ?? 0.0);
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
            'maDonHang' => $this->maDonHang,
            'maKhachHang' => $this->maKhachHang,
            'maDiaChi' => $this->maDiaChi,
            'maVoucher' => $this->maVoucher,
            'ngayDat' => $this->ngayDat,
            'phiVanChuyen' => $this->phiVanChuyen,
            'tongTien' => $this->tongTien,
            'trangThai' => $this->trangThai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function demDonChuaHoanTatTheoDienThoai(int $maDienThoai, ?PDO $pdo = null): int
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT COUNT(*) FROM ChiTietDonHang ctdh
             JOIN DonHang dh ON dh.maDonHang = ctdh.maDonHang
             WHERE ctdh.maDienThoai = ?
               AND dh.trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đang đóng gói', 'Đã giao shipper', 'Đang giao')"
        );
        $stmt->execute([$maDienThoai]);
        return (int)$stmt->fetchColumn();
    }

    public static function timTheoMa(int $maDonHang, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT dh.*, kh.maTaiKhoan, kh.hoTen AS tenKhachHang, kh.soDienThoai,
                       dc.diaChi AS diaChiGiaoHang, dc.nguoiNhan,
                       tt.phuongThuc, tt.trangThai AS trangThaiThanhToan
                FROM DonHang dh
                JOIN KhachHang kh ON kh.maKhachHang = dh.maKhachHang
                LEFT JOIN DiaChiGiaoHang dc ON dc.maDiaChi = dh.maDiaChi
                LEFT JOIN ThanhToan tt ON tt.maDonHang = dh.maDonHang
                WHERE dh.maDonHang = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maDonHang]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function timTheoTrangThai(?string $trangThai = null, ?PDO $pdo = null): array
    {
        $sql = "SELECT dh.*, kh.hoTen AS tenKhachHang, kh.soDienThoai,
                       dc.diaChi AS diaChiGiaoHang, dc.nguoiNhan,
                       tt.phuongThuc, tt.trangThai AS trangThaiThanhToan,
                       (SELECT px.maPhieuXuat FROM PhieuXuat px WHERE px.maDonHang = dh.maDonHang AND px.trangThai <> 'Đã hủy' ORDER BY px.maPhieuXuat DESC LIMIT 1) AS maPhieuXuat,
                       (SELECT px.trangThai FROM PhieuXuat px WHERE px.maDonHang = dh.maDonHang AND px.trangThai <> 'Đã hủy' ORDER BY px.maPhieuXuat DESC LIMIT 1) AS trangThaiPhieuXuat
                FROM DonHang dh
                JOIN KhachHang kh ON kh.maKhachHang = dh.maKhachHang
                JOIN DiaChiGiaoHang dc ON dc.maDiaChi = dh.maDiaChi
                LEFT JOIN ThanhToan tt ON tt.maDonHang = dh.maDonHang";
        $ts = [];
        if ($trangThai !== null && $trangThai !== '') {
            $sql .= " WHERE dh.trangThai = ?";
            $ts[] = $trangThai;
        }
        $sql .= " ORDER BY dh.ngayDat DESC, dh.maDonHang DESC";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($ts);
        return $stmt->fetchAll();
    }

    public static function timTheoKhachHang(int $maKhachHang, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT dh.*, tt.phuongThuc, tt.trangThai AS trangThaiThanhToan,
                    dc.nguoiNhan, dc.soDienThoai, dc.diaChi AS diaChiGiaoHang,
                    (SELECT yc.trangThai FROM YeuCauHuyDon yc WHERE yc.maDonHang = dh.maDonHang ORDER BY yc.maYeuCau DESC LIMIT 1) AS trangThaiYeuCauHuy
             FROM DonHang dh
             LEFT JOIN ThanhToan tt ON tt.maDonHang = dh.maDonHang
             LEFT JOIN DiaChiGiaoHang dc ON dc.maDiaChi = dh.maDiaChi
             WHERE dh.maKhachHang = ?
             ORDER BY dh.ngayDat DESC"
        );
        $stmt->execute([$maKhachHang]);
        return $stmt->fetchAll();
    }

    public static function timDonDaDuyetChuaXuat(?PDO $pdo = null): array
    {
        return self::pdo($pdo)->query(
            "SELECT dh.* FROM DonHang dh
             WHERE dh.trangThai IN ('Đã duyệt', 'Đang đóng gói')
               AND NOT EXISTS (SELECT 1 FROM PhieuXuat px WHERE px.maDonHang = dh.maDonHang AND px.trangThai <> 'Đã hủy')"
        )->fetchAll();
    }

    public static function timDonThanhCongTheoKhoang(string $tuNgay, string $denNgay, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT DATE(dh.ngayDat) AS ngay,
                    COUNT(DISTINCT dh.maDonHang) AS soDonHang,
                    COALESCE(SUM(ct.soLuong * ct.donGia), 0) AS doanhThu,
                    COALESCE(SUM(ct.soLuong * d.giaVonBinhQuan), 0) AS giaVon
             FROM DonHang dh
             JOIN ChiTietDonHang ct ON ct.maDonHang = dh.maDonHang
             JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai
             WHERE dh.trangThai = 'Đã giao thành công'
               AND DATE(dh.ngayDat) BETWEEN ? AND ?
             GROUP BY DATE(dh.ngayDat)
             ORDER BY ngay ASC"
        );
        $stmt->execute([$tuNgay, $denNgay]);
        return $stmt->fetchAll();
    }

    public static function timDonTheoKhoang(?string $tuNgay = null, ?string $denNgay = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        if ($tuNgay && $denNgay) {
            $stmt = $db->prepare("SELECT * FROM DonHang WHERE DATE(ngayDat) BETWEEN ? AND ? ORDER BY ngayDat DESC");
            $stmt->execute([$tuNgay, $denNgay]);
            return $stmt->fetchAll();
        }
        return $db->query("SELECT * FROM DonHang ORDER BY ngayDat DESC")->fetchAll();
    }

    public static function timDonAnhHuongTheoDienThoai(int $maDienThoai, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT DISTINCT dh.maDonHang, dh.trangThai
             FROM DonHang dh
             JOIN ChiTietDonHang ctdh ON ctdh.maDonHang = dh.maDonHang
             WHERE ctdh.maDienThoai = ? AND dh.trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đang đóng gói')"
        );
        $stmt->execute([$maDienThoai]);
        return $stmt->fetchAll();
    }

    public static function kiemTraTrangThai(int $maDonHang, ?PDO $pdo = null): ?string
    {
        $stmt = self::pdo($pdo)->prepare("SELECT trangThai FROM DonHang WHERE maDonHang = ? LIMIT 1");
        $stmt->execute([$maDonHang]);
        $tt = $stmt->fetchColumn();
        return $tt !== false ? (string)$tt : null;
    }

    public static function kiemTraTrangThaiXuatKho(int $maDonHang, ?PDO $pdo = null): bool
    {
        $tt = self::kiemTraTrangThai($maDonHang, $pdo);
        return in_array($tt, ['Đã duyệt', 'Đang đóng gói'], true);
    }

    public static function kiemTraChuyenTrangThai(string $trangThaiHienTai, string $trangThaiMoi): bool
    {
        $luongHopLe = [
            'Chờ duyệt'       => ['Đã duyệt'],
            'Đã duyệt'        => ['Đang đóng gói'],
            'Đang đóng gói'   => [],
            'Đã giao shipper' => ['Đang giao'],
            'Đang giao'       => ['Đã giao thành công'],
        ];
        return in_array($trangThaiMoi, $luongHopLe[$trangThaiHienTai] ?? [], true);
    }

    public static function layChiTietDonHang(int $maDonHang, ?PDO $pdo = null): array
    {
        return ChiTietDonHang::timTheoDonHang($maDonHang, null, $pdo);
    }

    public static function luu(int $maKhachHang, int $maDiaChi, ?string $maVoucher, float $phiVanChuyen, float $tongTien, string $trangThai = 'Chờ duyệt', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare(
            "INSERT INTO DonHang (maKhachHang, maDiaChi, maVoucher, ngayDat, phiVanChuyen, tongTien, trangThai)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?)"
        )->execute([$maKhachHang, $maDiaChi, $maVoucher, $phiVanChuyen, $tongTien, $trangThai]);
        return (int)$db->lastInsertId();
    }

    public static function capNhatTrangThai(int $maDonHang, string $trangThaiMoi, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE DonHang SET trangThai = ? WHERE maDonHang = ?")->execute([$trangThaiMoi, $maDonHang]);
    }

    public static function capNhatPhiVanChuyen(int $maDonHang, float $phiVanChuyen, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE DonHang SET phiVanChuyen = ? WHERE maDonHang = ?")->execute([$phiVanChuyen, $maDonHang]);
    }

    public static function ghiNhanDiaChi(int $maDonHang, int $maDiaChi, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE DonHang SET maDiaChi = ? WHERE maDonHang = ?")->execute([$maDiaChi, $maDonHang]);
    }

}
