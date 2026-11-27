<?php
// Entity BCE: DonHang — Bảng DonHang (Mục 3.4.3.14: Bảng 103, Bảng 104; RDM: Bảng 153)

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
    private string $tenNguoiNhan = '';
    private string $sdtNguoiNhan = '';
    private string $diaChiNhan = '';
    private ?string $lyDoHuy = null;
    private ?int $maNhanVienDuyet = null;
    private ?string $ngayDuyet = null;

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
        if (array_key_exists('tenNguoiNhan', $duLieu)) {
            $this->tenNguoiNhan = (string)($duLieu['tenNguoiNhan'] ?? '');
        }
        if (array_key_exists('sdtNguoiNhan', $duLieu)) {
            $this->sdtNguoiNhan = (string)($duLieu['sdtNguoiNhan'] ?? '');
        }
        if (array_key_exists('diaChiNhan', $duLieu)) {
            $this->diaChiNhan = (string)($duLieu['diaChiNhan'] ?? '');
        }
        if (array_key_exists('lyDoHuy', $duLieu)) {
            $this->lyDoHuy = isset($duLieu['lyDoHuy']) && $duLieu['lyDoHuy'] !== '' ? (string)$duLieu['lyDoHuy'] : null;
        }
        if (array_key_exists('maNhanVienDuyet', $duLieu)) {
            $this->maNhanVienDuyet = isset($duLieu['maNhanVienDuyet']) && $duLieu['maNhanVienDuyet'] !== '' ? (int)$duLieu['maNhanVienDuyet'] : null;
        }
        if (array_key_exists('ngayDuyet', $duLieu)) {
            $this->ngayDuyet = isset($duLieu['ngayDuyet']) && $duLieu['ngayDuyet'] !== '' ? (string)$duLieu['ngayDuyet'] : null;
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
            'maDonHang'       => $this->maDonHang,
            'maKhachHang'     => $this->maKhachHang,
            'maDiaChi'        => $this->maDiaChi,
            'maVoucher'       => $this->maVoucher,
            'ngayDat'         => $this->ngayDat,
            'phiVanChuyen'    => $this->phiVanChuyen,
            'tongTien'        => $this->tongTien,
            'trangThai'       => $this->trangThai,
            'tenNguoiNhan'    => $this->tenNguoiNhan,
            'sdtNguoiNhan'    => $this->sdtNguoiNhan,
            'diaChiNhan'      => $this->diaChiNhan,
            'lyDoHuy'         => $this->lyDoHuy,
            'maNhanVienDuyet' => $this->maNhanVienDuyet,
            'ngayDuyet'       => $this->ngayDuyet,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC6 - Hình 14] DonHang::demDonChuaHoanTatTheoDienThoai(maDienThoai) — Bảng 104 STT 3, Bảng 231 dòng 6
    public static function demDonChuaHoanTatTheoDienThoai(int $maDienThoai, ?PDO $pdo = null): int
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT COUNT(DISTINCT dh.maDonHang) FROM ChiTietDonHang ctdh
             JOIN DonHang dh ON dh.maDonHang = ctdh.maDonHang
             WHERE ctdh.maDienThoai = ?
               AND dh.trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đang đóng gói', 'Đã giao shipper', 'Đang giao')"
        );
        $stmt->execute([$maDienThoai]);
        return (int)$stmt->fetchColumn();
    }

    // [UC28, UC30 - Hình 36, Hình 38] DonHang::demLuotDungVoucher(maKhachHang, maVoucher) — Bảng 104 STT 4
    public static function demLuotDungVoucher(int $maKhachHang, string $maVoucher, ?PDO $pdo = null): int
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT COUNT(*) FROM DonHang
             WHERE maKhachHang = ? AND maVoucher = ? AND trangThai <> 'Đã hủy'"
        );
        $stmt->execute([$maKhachHang, trim($maVoucher)]);
        return (int)$stmt->fetchColumn();
    }

    // [UC46, UC47 - Hình 54, Hình 55] DonHang::timTheoMa(maDonHang) — Bảng 104 STT 14
    public static function timTheoMa(int $maDonHang, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT dh.*, kh.maTaiKhoan, kh.hoTen AS tenKhachHang,
                       COALESCE(NULLIF(dh.sdtNguoiNhan, ''), dc.soDienThoai, kh.soDienThoai) AS soDienThoai,
                       COALESCE(NULLIF(dh.diaChiNhan, ''), dc.diaChi) AS diaChiGiaoHang,
                       COALESCE(NULLIF(dh.tenNguoiNhan, ''), dc.nguoiNhan) AS nguoiNhan,
                       nv.hoTen AS tenNhanVienDuyet,
                       tt.phuongThuc, tt.trangThai AS trangThaiThanhToan
                FROM DonHang dh
                JOIN KhachHang kh ON kh.maKhachHang = dh.maKhachHang
                LEFT JOIN DiaChiGiaoHang dc ON dc.maDiaChi = dh.maDiaChi
                LEFT JOIN NhanVien nv ON nv.maNhanVien = dh.maNhanVienDuyet
                LEFT JOIN ThanhToan tt ON tt.maDonHang = dh.maDonHang
                WHERE dh.maDonHang = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maDonHang]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    // [UC33, UC34 - Hình 41, Hình 42] DonHang::timTheoTrangThai(trangThai) — Bảng 104 STT 15
    public static function timTheoTrangThai(?string $trangThai = null, ?PDO $pdo = null): array
    {
        $sql = "SELECT dh.*, kh.hoTen AS tenKhachHang,
                       COALESCE(NULLIF(dh.sdtNguoiNhan, ''), dc.soDienThoai, kh.soDienThoai) AS soDienThoai,
                       COALESCE(NULLIF(dh.diaChiNhan, ''), dc.diaChi) AS diaChiGiaoHang,
                       COALESCE(NULLIF(dh.tenNguoiNhan, ''), dc.nguoiNhan) AS nguoiNhan,
                       nv.hoTen AS tenNhanVienDuyet,
                       tt.phuongThuc, tt.trangThai AS trangThaiThanhToan,
                       (SELECT px.maPhieuXuat FROM PhieuXuat px WHERE px.maDonHang = dh.maDonHang AND px.trangThai <> 'Đã hủy' ORDER BY px.maPhieuXuat DESC LIMIT 1) AS maPhieuXuat,
                       (SELECT px.trangThai FROM PhieuXuat px WHERE px.maDonHang = dh.maDonHang AND px.trangThai <> 'Đã hủy' ORDER BY px.maPhieuXuat DESC LIMIT 1) AS trangThaiPhieuXuat
                FROM DonHang dh
                JOIN KhachHang kh ON kh.maKhachHang = dh.maKhachHang
                LEFT JOIN DiaChiGiaoHang dc ON dc.maDiaChi = dh.maDiaChi
                LEFT JOIN NhanVien nv ON nv.maNhanVien = dh.maNhanVienDuyet
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

    // [UC31, UC32, UC46, UC47] DonHang::timTheoKhachHang(maKhachHang) — Bảng 104 STT 13
    public static function timTheoKhachHang(int $maKhachHang, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT dh.*, tt.phuongThuc, tt.trangThai AS trangThaiThanhToan,
                    COALESCE(NULLIF(dh.tenNguoiNhan, ''), dc.nguoiNhan) AS nguoiNhan,
                    COALESCE(NULLIF(dh.sdtNguoiNhan, ''), dc.soDienThoai) AS soDienThoai,
                    COALESCE(NULLIF(dh.diaChiNhan, ''), dc.diaChi) AS diaChiGiaoHang,
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

    // [UC15 - Hình 23] DonHang::timDonDaDuyetChuaXuat() — Bảng 104 STT 10
    public static function timDonDaDuyetChuaXuat(?PDO $pdo = null): array
    {
        return self::pdo($pdo)->query(
            "SELECT dh.* FROM DonHang dh
             WHERE dh.trangThai IN ('Đã duyệt', 'Đang đóng gói')
               AND NOT EXISTS (SELECT 1 FROM PhieuXuat px WHERE px.maDonHang = dh.maDonHang AND px.trangThai <> 'Đã hủy')"
        )->fetchAll();
    }

    // [UC53 - Hình 61] DonHang::timDonThanhCongTheoKhoang(tuNgay, denNgay) — Bảng 104 STT 11
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

    // [UC54 - Hình 62] DonHang::timDonTheoKhoang(tuNgay, denNgay) — Bảng 104 STT 12
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

    // [UC31, UC32, UC35] DonHang::kiemTraTrangThai(maDonHang) — Bảng 104 STT 6
    public static function kiemTraTrangThai(int $maDonHang, ?PDO $pdo = null): ?string
    {
        $stmt = self::pdo($pdo)->prepare("SELECT trangThai FROM DonHang WHERE maDonHang = ? LIMIT 1");
        $stmt->execute([$maDonHang]);
        $tt = $stmt->fetchColumn();
        return $tt !== false ? (string)$tt : null;
    }

    // [UC15 - Hình 23] DonHang::kiemTraTrangThaiXuatKho(maDonHang) — Bảng 104 STT 7
    public static function kiemTraTrangThaiXuatKho(int $maDonHang, ?PDO $pdo = null): bool
    {
        $tt = self::kiemTraTrangThai($maDonHang, $pdo);
        return in_array($tt, ['Đã duyệt', 'Đang đóng gói'], true);
    }

    // [UC34 - Hình 42] DonHang::kiemTraChuyenTrangThai(maDonHang, trangThaiMoi) — Bảng 104 STT 5
    public static function kiemTraChuyenTrangThai($maDonHangHoacTrangThai, string $trangThaiMoi, ?PDO $pdo = null): bool
    {
        $trangThaiHienTai = is_numeric($maDonHangHoacTrangThai)
            ? (self::kiemTraTrangThai((int)$maDonHangHoacTrangThai, $pdo) ?? '')
            : (string)$maDonHangHoacTrangThai;

        $luongHopLe = [
            'Chờ duyệt'       => ['Đã duyệt'],
            'Đã duyệt'        => ['Đang đóng gói'],
            'Đang đóng gói'   => [],
            'Đã giao shipper' => ['Đang giao'],
            'Đang giao'       => ['Đã giao thành công'],
        ];
        return in_array($trangThaiMoi, $luongHopLe[$trangThaiHienTai] ?? [], true);
    }

    // [UC15 - Hình 23] DonHang::layChiTietDonHang(maDonHang) — Bảng 104 STT 8
    public static function layChiTietDonHang(int $maDonHang, ?PDO $pdo = null): array
    {
        return ChiTietDonHang::timTheoDonHang($maDonHang, null, $pdo);
    }

    // [UC28 - Hình 36] DonHang::luu(maKhachHang, maDiaChi, tenNguoiNhan, sdtNguoiNhan, diaChiNhan, maVoucher, phiVanChuyen, tongTien, trangThai) — Bảng 104 STT 9
    public static function luu(
        int $maKhachHang,
        int $maDiaChi,
        string $tenNguoiNhan,
        string $sdtNguoiNhan,
        string $diaChiNhan,
        ?string $maVoucher,
        float $phiVanChuyen,
        float $tongTien,
        string $trangThai = 'Chờ duyệt',
        ?PDO $pdo = null
    ): int {
        $db = self::pdo($pdo);
        $db->prepare(
            "INSERT INTO DonHang (maKhachHang, maDiaChi, tenNguoiNhan, sdtNguoiNhan, diaChiNhan, maVoucher, ngayDat, phiVanChuyen, tongTien, trangThai)
             VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?)"
        )->execute([
            $maKhachHang,
            $maDiaChi,
            trim($tenNguoiNhan),
            trim($sdtNguoiNhan),
            trim($diaChiNhan),
            $maVoucher !== null && trim($maVoucher) !== '' ? trim($maVoucher) : null,
            max(0.0, $phiVanChuyen),
            max(0.0, $tongTien),
            $trangThai,
        ]);
        return (int)$db->lastInsertId();
    }

    // [UC16, UC31, UC33, UC34, UC36] DonHang::capNhatTrangThai(...) — Bảng 104 STT 1 & 2
    public static function capNhatTrangThai(
        int $maDonHang,
        string $trangThai,
        $lyDoHuyHoacPdo = null,
        ?int $maNhanVienDuyet = null,
        ?PDO $pdo = null
    ): bool {
        if ($lyDoHuyHoacPdo instanceof PDO) {
            $pdo = $lyDoHuyHoacPdo;
            $lyDoHuy = null;
        } else {
            $lyDoHuy = $lyDoHuyHoacPdo !== null ? trim((string)$lyDoHuyHoacPdo) : null;
        }

        $db = self::pdo($pdo);
        if ($trangThai === 'Đã duyệt' && $maNhanVienDuyet !== null) {
            return $db->prepare("UPDATE DonHang SET trangThai = ?, maNhanVienDuyet = ?, ngayDuyet = CURRENT_TIMESTAMP WHERE maDonHang = ?")
                      ->execute([$trangThai, $maNhanVienDuyet, $maDonHang]);
        }
        if ($trangThai === 'Đã hủy') {
            if ($maNhanVienDuyet !== null) {
                return $db->prepare("UPDATE DonHang SET trangThai = ?, lyDoHuy = COALESCE(?, lyDoHuy), maNhanVienDuyet = COALESCE(maNhanVienDuyet, ?), ngayDuyet = COALESCE(ngayDuyet, CURRENT_TIMESTAMP) WHERE maDonHang = ?")
                          ->execute([$trangThai, $lyDoHuy, $maNhanVienDuyet, $maDonHang]);
            }
            return $db->prepare("UPDATE DonHang SET trangThai = ?, lyDoHuy = COALESCE(?, lyDoHuy) WHERE maDonHang = ?")
                      ->execute([$trangThai, $lyDoHuy, $maDonHang]);
        }
        return $db->prepare("UPDATE DonHang SET trangThai = ? WHERE maDonHang = ?")->execute([$trangThai, $maDonHang]);
    }
}
