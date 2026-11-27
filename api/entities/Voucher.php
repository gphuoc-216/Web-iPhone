<?php
// Entity BCE: Voucher — Bảng Voucher (Mục 3.4.3.29: Bảng 133, Bảng 134; RDM: Bảng 169)

class Voucher
{
    public const TEN_BANG = 'Voucher';
    public const KHOA_CHINH = 'maVoucher';

    private string $maVoucher = '';
    private string $loaiGiam = 'Số tiền';
    private float $giaTriGiam = 0.0;
    private int $soLuong = 0;
    private int $gioiHanSuDung = 1;
    private int $luotDaDung = 0;
    private ?string $ngayBatDau = null;
    private ?string $ngayKetThuc = null;
    private string $trangThai = 'Đang hiệu lực';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maVoucher', $duLieu)) {
            $this->maVoucher = (string)($duLieu['maVoucher'] ?? '');
        }
        if (array_key_exists('loaiGiam', $duLieu)) {
            $this->loaiGiam = (string)($duLieu['loaiGiam'] ?? '');
        }
        if (array_key_exists('giaTriGiam', $duLieu)) {
            $this->giaTriGiam = (float)($duLieu['giaTriGiam'] ?? 0.0);
        }
        if (array_key_exists('soLuong', $duLieu)) {
            $this->soLuong = (int)($duLieu['soLuong'] ?? 0);
        }
        if (array_key_exists('gioiHanSuDung', $duLieu)) {
            $this->gioiHanSuDung = (int)($duLieu['gioiHanSuDung'] ?? 0);
        }
        if (array_key_exists('luotDaDung', $duLieu)) {
            $this->luotDaDung = (int)($duLieu['luotDaDung'] ?? 0);
        }
        if (array_key_exists('ngayBatDau', $duLieu)) {
            $this->ngayBatDau = isset($duLieu['ngayBatDau']) && $duLieu['ngayBatDau'] !== '' ? (string)$duLieu['ngayBatDau'] : null;
        }
        if (array_key_exists('ngayKetThuc', $duLieu)) {
            $this->ngayKetThuc = isset($duLieu['ngayKetThuc']) && $duLieu['ngayKetThuc'] !== '' ? (string)$duLieu['ngayKetThuc'] : null;
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
            'maVoucher'     => $this->maVoucher,
            'loaiGiam'      => $this->loaiGiam,
            'giaTriGiam'    => $this->giaTriGiam,
            'soLuong'       => $this->soLuong,
            'gioiHanSuDung' => $this->gioiHanSuDung,
            'luotDaDung'    => $this->luotDaDung,
            'ngayBatDau'    => $this->ngayBatDau,
            'ngayKetThuc'   => $this->ngayKetThuc,
            'trangThai'     => $this->trangThai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC30, UC60, UC62 - Hình 38, Hình 68, Hình 70] Voucher::kiemTraHopLe(...) — Bảng 134 STT 5 & 6
    // Nạp chồng:
    // - 1 tham số chuỗi (UC30): kiemTraHopLe(maVoucher) kiểm tra mã tồn tại, đang hiệu lực, còn thời hạn, luotDaDung < soLuong
    // - 3+ tham số số (UC60, UC62): kiemTraHopLe(giaTriGiam, soLuong, gioiHanSuDung, ...)
    public static function kiemTraHopLe($maVoucherHoacGiaTriGiam, $soLuongHoacPdo = null, int $gioiHanSuDung = 1, string $loaiGiam = 'Số tiền', string $ngayBatDau = '', string $ngayKetThuc = ''): bool
    {
        if (is_string($maVoucherHoacGiaTriGiam) && ($soLuongHoacPdo === null || $soLuongHoacPdo instanceof PDO)) {
            return self::kiemTraMaHopLe($maVoucherHoacGiaTriGiam, $soLuongHoacPdo);
        }

        $giaTriGiam = (float)$maVoucherHoacGiaTriGiam;
        $soLuong = (int)$soLuongHoacPdo;
        if (!in_array($loaiGiam, ['Phần trăm', 'Số tiền'], true)) return false;
        if ($loaiGiam === 'Phần trăm' && $giaTriGiam > 100) return false;
        if ($giaTriGiam <= 0 || $soLuong <= 0 || $gioiHanSuDung <= 0) return false;
        if ($ngayBatDau !== '' && $ngayKetThuc !== '' && $ngayKetThuc < $ngayBatDau) return false;
        return true;
    }

    // [UC60 - Hình 68] Voucher::kiemTraTrungMa(maVoucher) — Bảng 134 STT 7
    public static function kiemTraTrungMa(string $maVoucher, ?PDO $pdo = null): bool
    {
        return self::timTheoMa($maVoucher, $pdo) !== null;
    }

    public static function kiemTraMaHopLe(string $maVoucher, ?PDO $pdo = null): bool
    {
        $v = self::timTheoMa($maVoucher, $pdo);
        if (!$v) return false;
        if ($v['trangThai'] !== 'Đang hiệu lực' || (int)$v['luotDaDung'] >= (int)$v['soLuong']) return false;
        $homNay = date('Y-m-d');
        return $homNay >= $v['ngayBatDau'] && $homNay <= $v['ngayKetThuc'];
    }

    public static function kiemTraLuotSuDung(string $maVoucher, int $maKhachHang, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $v = self::timTheoMa($maVoucher, $db);
        if (!$v) return false;
        return DonHang::demLuotDungVoucher($maKhachHang, $maVoucher, $db) < (int)$v['gioiHanSuDung'];
    }

    // [UC64 - Hình 72] Voucher::demLuotSuDung(maVoucher) — Bảng 134 STT 3
    public static function demLuotSuDung(string $maVoucher, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $ma = strtoupper(trim($maVoucher));
        $v = self::timTheoMa($ma, $db);
        $luotCot = $v ? (int)$v['luotDaDung'] : 0;
        $stmt = $db->prepare("SELECT COUNT(*) FROM DonHang WHERE maVoucher = ?");
        $stmt->execute([$ma]);
        $luotDon = (int)$stmt->fetchColumn();
        return max($luotCot, $luotDon);
    }

    // [UC60 - UC64 - Hình 68 - Hình 72] Voucher::timTatCa() — Bảng 134 STT 10
    public static function timTatCa(?string $trangThai = null, ?PDO $pdo = null): array
    {
        $sql = "SELECT v.*, dk.giaTriToiThieu, dk.nhomHang, dk.doiTuong
                FROM Voucher v
                LEFT JOIN DieuKienApDung dk ON dk.maVoucher = v.maVoucher";
        $ts = [];
        if ($trangThai !== null && $trangThai !== '') {
            $sql .= " WHERE v.trangThai = ?";
            $ts[] = $trangThai;
        }
        $sql .= " ORDER BY v.ngayBatDau DESC, v.maVoucher ASC";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($ts);
        $ketQua = [];
        foreach ($stmt->fetchAll() as $dong) {
            $muc = self::tuMang($dong)->chuyenThanhMang();
            $muc['dieuKienApDung'] = [
                'giaTriToiThieu' => (float)($dong['giaTriToiThieu'] ?? 0),
                'nhomHang'       => $dong['nhomHang'] ?? 'Tất cả',
                'doiTuong'       => $dong['doiTuong'] ?? 'Khách hàng đã đăng nhập',
            ];
            $ketQua[] = $muc;
        }
        return $ketQua;
    }

    // [UC61 - Hình 69] Voucher::timTheoTrangThai(trangThai) — Bảng 134 STT 12
    public static function timTheoTrangThai(string $trangThai, ?PDO $pdo = null): array
    {
        return self::timTatCa($trangThai, $pdo);
    }

    // [UC61, UC62 - Hình 69, Hình 70] Voucher::timTheoMa(maVoucher) — Bảng 134 STT 11
    public static function timTheoMa(string $maVoucher, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT v.*, dk.giaTriToiThieu, dk.nhomHang, dk.doiTuong
             FROM Voucher v
             LEFT JOIN DieuKienApDung dk ON dk.maVoucher = v.maVoucher
             WHERE v.maVoucher = ? LIMIT 1"
        );
        $stmt->execute([strtoupper(trim($maVoucher))]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    // [UC60 - Hình 68] Voucher::luu(maVoucher, loaiGiam, giaTriGiam, soLuong, gioiHanSuDung, ngayBatDau, ngayKetThuc) — Bảng 134 STT 8
    public static function luu(string $maVoucher, string $loaiGiam, float $giaTriGiam, int $soLuong, int $gioiHanSuDung, string $ngayBatDau, string $ngayKetThuc, ?PDO $pdo = null): bool
    {
        if (mb_strlen(trim($maVoucher)) > 30) {
            return false;
        }
        return self::pdo($pdo)->prepare(
            "INSERT INTO Voucher (maVoucher, loaiGiam, giaTriGiam, soLuong, gioiHanSuDung, luotDaDung, ngayBatDau, ngayKetThuc, trangThai)
             VALUES (?, ?, ?, ?, ?, 0, ?, ?, 'Đang hiệu lực')"
        )->execute([strtoupper(trim($maVoucher)), $loaiGiam, $giaTriGiam, $soLuong, max(1, $gioiHanSuDung), $ngayBatDau, $ngayKetThuc]);
    }

    // [UC62 - Hình 70] Voucher::capNhat(maVoucher, loaiGiam, giaTriGiam, soLuong, gioiHanSuDung, ngayBatDau, ngayKetThuc) — Bảng 134 STT 1
    public static function capNhat(string $maVoucher, string $loaiGiam, float $giaTriGiam, int $soLuong, int $gioiHanSuDung, string $ngayBatDau, string $ngayKetThuc, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare(
            "UPDATE Voucher SET loaiGiam = ?, giaTriGiam = ?, soLuong = ?, gioiHanSuDung = ?, ngayBatDau = ?, ngayKetThuc = ? WHERE maVoucher = ?"
        )->execute([$loaiGiam, $giaTriGiam, $soLuong, max(1, $gioiHanSuDung), $ngayBatDau, $ngayKetThuc, strtoupper(trim($maVoucher))]);
    }

    // [UC63 - Hình 71] Voucher::capNhatTrangThai(maVoucher, trangThai) — Bảng 134 STT 2
    public static function capNhatTrangThai(string $maVoucher, string $trangThai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE Voucher SET trangThai = ? WHERE maVoucher = ?")->execute([$trangThai, strtoupper(trim($maVoucher))]);
    }

    // [UC28 - Hình 36] Voucher::tangLuotDaDung(maVoucher) — Bảng 134 STT 9
    public static function tangLuotDaDung(string $maVoucher, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("UPDATE Voucher SET luotDaDung = luotDaDung + 1 WHERE maVoucher = ? AND luotDaDung < soLuong");
        $stmt->execute([strtoupper(trim($maVoucher))]);
        return $stmt->rowCount() > 0;
    }

    public static function ghiNhanLuotSuDung(string $maVoucher, ?int $maKhachHang = null, ?PDO $pdo = null): bool
    {
        return self::tangLuotDaDung($maVoucher, $pdo);
    }

    // [UC31, UC36 - Hình 39, Hình 44] Voucher::hoanLuotSuDung(maVoucher) — Bảng 134 STT 4
    public static function hoanLuotSuDung(?string $maVoucher, ?PDO $pdo = null): bool
    {
        if (empty($maVoucher)) return true;
        return self::pdo($pdo)->prepare("UPDATE Voucher SET luotDaDung = GREATEST(0, luotDaDung - 1) WHERE maVoucher = ?")
            ->execute([strtoupper(trim($maVoucher))]);
    }

    // [UC64 - Hình 72] Voucher::xoa(maVoucher) — Bảng 134 STT 13
    public static function xoa(string $maVoucher, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("DELETE FROM Voucher WHERE maVoucher = ?")->execute([strtoupper(trim($maVoucher))]);
    }
}
