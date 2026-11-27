<?php
// Entity BCE: PhieuNhap — Bảng PhieuNhap (Mục 3.4.3.21: Bảng 117, Bảng 118; RDM: Bảng 161)

class PhieuNhap
{
    public const TEN_BANG = 'PhieuNhap';
    public const KHOA_CHINH = 'maPhieuNhap';

    private ?int $maPhieuNhap = null;
    private string $nguonNhap = '';
    private ?string $ngayNhap = null;
    private string $trangThai = 'Nháp';
    private ?int $maNhanVien = null;
    private ?string $lyDoTuChoi = null;
    private ?int $maNguoiDuyet = null;
    private ?string $ngayDuyet = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maPhieuNhap', $duLieu)) {
            $this->maPhieuNhap = isset($duLieu['maPhieuNhap']) && $duLieu['maPhieuNhap'] !== '' ? (int)$duLieu['maPhieuNhap'] : null;
        }
        if (array_key_exists('nguonNhap', $duLieu)) {
            $this->nguonNhap = (string)($duLieu['nguonNhap'] ?? '');
        }
        if (array_key_exists('ngayNhap', $duLieu)) {
            $this->ngayNhap = isset($duLieu['ngayNhap']) && $duLieu['ngayNhap'] !== '' ? (string)$duLieu['ngayNhap'] : null;
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
        }
        if (array_key_exists('maNhanVien', $duLieu)) {
            $this->maNhanVien = isset($duLieu['maNhanVien']) && $duLieu['maNhanVien'] !== '' ? (int)$duLieu['maNhanVien'] : null;
        }
        if (array_key_exists('lyDoTuChoi', $duLieu)) {
            $this->lyDoTuChoi = isset($duLieu['lyDoTuChoi']) && $duLieu['lyDoTuChoi'] !== '' ? (string)$duLieu['lyDoTuChoi'] : null;
        }
        if (array_key_exists('maNguoiDuyet', $duLieu)) {
            $this->maNguoiDuyet = isset($duLieu['maNguoiDuyet']) && $duLieu['maNguoiDuyet'] !== '' ? (int)$duLieu['maNguoiDuyet'] : null;
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
            'maPhieuNhap'  => $this->maPhieuNhap,
            'nguonNhap'    => $this->nguonNhap,
            'ngayNhap'     => $this->ngayNhap,
            'trangThai'    => $this->trangThai,
            'maNhanVien'   => $this->maNhanVien,
            'lyDoTuChoi'   => $this->lyDoTuChoi,
            'maNguoiDuyet' => $this->maNguoiDuyet,
            'ngayDuyet'    => $this->ngayDuyet,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC12, UC14 - Hình 20, Hình 22] PhieuNhap::timTatCa() — Bảng 118 STT 5
    public static function timTatCa(?PDO $pdo = null): array
    {
        return self::timTheoDieuKien(null, null, null, $pdo);
    }

    // [UC13 - Hình 21] PhieuNhap::timTheoTrangThai(trangThai) — Bảng 118 STT 8
    public static function timTheoTrangThai(string $trangThai, ?PDO $pdo = null): array
    {
        return self::timTheoDieuKien($trangThai, null, null, $pdo);
    }

    // [UC14 - Hình 22] PhieuNhap::timTheoDieuKien(trangThai, tuNgay, denNgay) — Bảng 118 STT 6
    public static function timTheoDieuKien(?string $trangThai = null, ?string $tuNgay = null, ?string $denNgay = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $dk = ['1=1'];
        $ts = [];
        if ($trangThai !== null && $trangThai !== '') { $dk[] = "pn.trangThai = ?"; $ts[] = $trangThai; }
        if ($tuNgay !== null && $tuNgay !== '') { $dk[] = "pn.ngayNhap >= ?"; $ts[] = $tuNgay; }
        if ($denNgay !== null && $denNgay !== '') { $dk[] = "pn.ngayNhap <= ?"; $ts[] = $denNgay; }

        $sql = "SELECT pn.*, nv.hoTen AS tenNhanVien, nvd.hoTen AS tenNguoiDuyet
                FROM PhieuNhap pn
                LEFT JOIN NhanVien nv ON nv.maNhanVien = pn.maNhanVien
                LEFT JOIN NhanVien nvd ON nvd.maNhanVien = pn.maNguoiDuyet
                WHERE " . implode(' AND ', $dk) . "
                ORDER BY pn.maPhieuNhap DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($ts);
        return $stmt->fetchAll();
    }

    // [UC13, UC14 - Hình 21, Hình 22] PhieuNhap::timTheoMa(maPhieuNhap) — Bảng 118 STT 7
    public static function timTheoMa(int $maPhieuNhap, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT pn.*, nv.hoTen AS tenNhanVien, nv.maTaiKhoan, nvd.hoTen AS tenNguoiDuyet
                FROM PhieuNhap pn
                LEFT JOIN NhanVien nv ON nv.maNhanVien = pn.maNhanVien
                LEFT JOIN NhanVien nvd ON nvd.maNhanVien = pn.maNguoiDuyet
                WHERE pn.maPhieuNhap = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maPhieuNhap]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    // [UC13 - Hình 21] PhieuNhap::kiemTraChoDuyet(maPhieuNhap) — Bảng 118 STT 3
    // [UC52 - Hình 60 luồng phụ 5.2] Đếm phiếu nhập Nháp/Chờ duyệt của nhân viên (cảnh báo khi đổi vai trò)
    public static function demPhieuDangXuLyTheoNhanVien(int $maNhanVien, ?PDO $pdo = null): int
    {
        $stmt = self::pdo($pdo)->prepare("SELECT COUNT(*) FROM PhieuNhap WHERE maNhanVien = ? AND trangThai IN ('Nháp', 'Chờ duyệt')");
        $stmt->execute([$maNhanVien]);
        return (int)$stmt->fetchColumn();
    }

    public static function kiemTraChoDuyet(int $maPhieuNhap, ?PDO $pdo = null): bool
    {
        $r = self::timTheoMa($maPhieuNhap, false, $pdo);
        return $r !== null && $r['trangThai'] === 'Chờ duyệt';
    }

    // [UC12 - Hình 20] PhieuNhap::luu(maNhanVien, nguonNhap, ngayNhap, trangThai) — Bảng 118 STT 4
    public static function luu(int $maNhanVien, string $nguonNhap, string $ngayNhap, string $trangThai = 'Nháp', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO PhieuNhap (nguonNhap, ngayNhap, trangThai, maNhanVien) VALUES (?, ?, ?, ?)")
           ->execute([trim($nguonNhap), $ngayNhap, $trangThai, $maNhanVien]);
        return (int)$db->lastInsertId();
    }

    // [UC12, UC13 - Hình 20, Hình 21] PhieuNhap::capNhatTrangThai(...) — Bảng 118 STT 1 & 2
    public static function capNhatTrangThai(
        int $maPhieuNhap,
        string $trangThai,
        $lyDoTuChoiHoacPdo = null,
        ?int $maNguoiDuyet = null,
        ?PDO $pdo = null
    ): bool {
        if ($lyDoTuChoiHoacPdo instanceof PDO) {
            $pdo = $lyDoTuChoiHoacPdo;
            $lyDoTuChoi = null;
        } else {
            $lyDoTuChoi = $lyDoTuChoiHoacPdo !== null ? trim((string)$lyDoTuChoiHoacPdo) : null;
        }
        $db = self::pdo($pdo);

        // Khi Ban Quản lý duyệt hoặc từ chối phiếu nhập (UC13):
        // Theo Bảng 22 & Hình 21: khi từ chối, lưu lyDoTuChoi, maNguoiDuyet, ngayDuyet và phiếu quay về 'Nháp' để NV Kho sửa gửi lại
        if ($trangThai === 'Từ chối') {
            $trangThaiLuu = 'Nháp';
            $stmt = $db->prepare(
                "UPDATE PhieuNhap
                 SET trangThai = ?, lyDoTuChoi = ?, maNguoiDuyet = ?, ngayDuyet = CURRENT_TIMESTAMP
                 WHERE maPhieuNhap = ? AND trangThai = 'Chờ duyệt'"
            );
            $stmt->execute([$trangThaiLuu, $lyDoTuChoi, $maNguoiDuyet, $maPhieuNhap]);
            return $stmt->rowCount() > 0;
        }

        if ($trangThai === 'Đã duyệt') {
            $stmt = $db->prepare(
                "UPDATE PhieuNhap
                 SET trangThai = 'Đã duyệt', lyDoTuChoi = NULL, maNguoiDuyet = ?, ngayDuyet = CURRENT_TIMESTAMP
                 WHERE maPhieuNhap = ? AND trangThai = 'Chờ duyệt'"
            );
            $stmt->execute([$maNguoiDuyet, $maPhieuNhap]);
            return $stmt->rowCount() > 0;
        }

        return $db->prepare("UPDATE PhieuNhap SET trangThai = ? WHERE maPhieuNhap = ?")->execute([$trangThai, $maPhieuNhap]);
    }
}
