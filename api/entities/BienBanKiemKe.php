<?php
// Entity BCE: BienBanKiemKe — Bảng BienBanKiemKe (Mục 3.4.3.2: Bảng 79, Bảng 80; RDM: Bảng 141 — không lưu maKho)

class BienBanKiemKe
{
    public const TEN_BANG = 'BienBanKiemKe';
    public const KHOA_CHINH = 'maBienBan';

    private ?int $maBienBan = null;
    private ?string $thoiDiem = null;
    private ?string $ghiChu = null;
    private string $trangThai = 'Nháp';
    private ?int $maNhanVien = null;
    private ?string $lyDoTuChoi = null;
    private ?int $maNguoiDuyet = null;
    private ?string $thoiDiemDuyet = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maBienBan', $duLieu)) {
            $this->maBienBan = isset($duLieu['maBienBan']) && $duLieu['maBienBan'] !== '' ? (int)$duLieu['maBienBan'] : null;
        }
        if (array_key_exists('thoiDiem', $duLieu)) {
            $this->thoiDiem = isset($duLieu['thoiDiem']) && $duLieu['thoiDiem'] !== '' ? (string)$duLieu['thoiDiem'] : null;
        }
        if (array_key_exists('ghiChu', $duLieu)) {
            $this->ghiChu = isset($duLieu['ghiChu']) && $duLieu['ghiChu'] !== '' ? (string)$duLieu['ghiChu'] : null;
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
        if (array_key_exists('thoiDiemDuyet', $duLieu)) {
            $this->thoiDiemDuyet = isset($duLieu['thoiDiemDuyet']) && $duLieu['thoiDiemDuyet'] !== '' ? (string)$duLieu['thoiDiemDuyet'] : null;
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
            'maBienBan'     => $this->maBienBan,
            'thoiDiem'      => $this->thoiDiem,
            'ghiChu'        => $this->ghiChu,
            'trangThai'     => $this->trangThai,
            'maNhanVien'    => $this->maNhanVien,
            'lyDoTuChoi'    => $this->lyDoTuChoi,
            'maNguoiDuyet'  => $this->maNguoiDuyet,
            'thoiDiemDuyet' => $this->thoiDiemDuyet,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC18 - Hình 26] BienBanKiemKe::timTatCa() — Bảng 80 STT 8
    public static function timTatCa(?PDO $pdo = null): array
    {
        return self::pdo($pdo)->query(
            "SELECT bb.*, nv.hoTen AS tenNhanVien, nvd.hoTen AS tenNguoiDuyet
             FROM BienBanKiemKe bb
             LEFT JOIN NhanVien nv ON nv.maNhanVien = bb.maNhanVien
             LEFT JOIN NhanVien nvd ON nvd.maNhanVien = bb.maNguoiDuyet
             ORDER BY bb.maBienBan DESC"
        )->fetchAll();
    }

    // [UC19 - Hình 27] BienBanKiemKe::timTheoTrangThai(trangThai) — Bảng 80 STT 9
    public static function timTheoTrangThai(string $trangThai, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT bb.*, nv.hoTen AS tenNhanVien, nvd.hoTen AS tenNguoiDuyet
             FROM BienBanKiemKe bb
             LEFT JOIN NhanVien nv ON nv.maNhanVien = bb.maNhanVien
             LEFT JOIN NhanVien nvd ON nvd.maNhanVien = bb.maNguoiDuyet
             WHERE bb.trangThai = ? ORDER BY bb.maBienBan DESC"
        );
        $stmt->execute([$trangThai]);
        return $stmt->fetchAll();
    }

    // [UC19 - Hình 27] BienBanKiemKe::timTheoMa(maBienBan) — Bảng 80 STT 8
    public static function timTheoMa(int $maBienBan, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT bb.*, nv.maTaiKhoan, nv.hoTen AS tenNhanVien, nvd.hoTen AS tenNguoiDuyet
                FROM BienBanKiemKe bb
                LEFT JOIN NhanVien nv ON nv.maNhanVien = bb.maNhanVien
                LEFT JOIN NhanVien nvd ON nvd.maNhanVien = bb.maNguoiDuyet
                WHERE bb.maBienBan = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maBienBan]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    // [UC18 - Hình 26] BienBanKiemKe::taoMoi(maNhanVien, thoiDiem) — Bảng 80 STT 7
    public static function taoMoi(int $maNhanVien, ?string $thoiDiem = null, ?string $ghiChu = null, string $trangThai = 'Nháp', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $td = ($thoiDiem !== null && trim($thoiDiem) !== '') ? trim($thoiDiem) : date('Y-m-d H:i:s');
        $db->prepare("INSERT INTO BienBanKiemKe (thoiDiem, ghiChu, trangThai, maNhanVien) VALUES (?, ?, ?, ?)")
           ->execute([$td, $ghiChu, $trangThai, $maNhanVien]);
        return (int)$db->lastInsertId();
    }

    // [UC18 - Hình 26] BienBanKiemKe::luu(maBienBan, ghiChu, trangThai) — Bảng 80 STT 4
    public static function luu(int $maBienBan, ?string $ghiChu, string $trangThai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE BienBanKiemKe SET ghiChu = ?, trangThai = ? WHERE maBienBan = ?")
            ->execute([$ghiChu, $trangThai, $maBienBan]);
    }

    // [UC18 - Hình 26] BienBanKiemKe::luuTienDo(maBienBan) — Bảng 80 STT 5
    public static function luuTienDo(int $maBienBan, ?PDO $pdo = null): bool
    {
        return self::capNhatTrangThai($maBienBan, 'Nháp', null, null, $pdo);
    }

    // [UC18, UC19 - Hình 26, Hình 27] BienBanKiemKe::capNhatTrangThai(...) — Bảng 80 STT 1 & 2
    public static function capNhatTrangThai(int $maBienBan, string $trangThai, $lyDoTuChoiHoacPdo = null, ?int $maNguoiDuyet = null, ?PDO $pdo = null): bool
    {
        if ($lyDoTuChoiHoacPdo instanceof PDO) {
            $pdo = $lyDoTuChoiHoacPdo;
            $lyDoTuChoi = null;
        } else {
            $lyDoTuChoi = $lyDoTuChoiHoacPdo !== null ? trim((string)$lyDoTuChoiHoacPdo) : null;
        }
        $db = self::pdo($pdo);
        if ($maNguoiDuyet !== null || $lyDoTuChoi !== null) {
            return $db->prepare(
                "UPDATE BienBanKiemKe
                 SET trangThai = ?, lyDoTuChoi = ?, maNguoiDuyet = ?, thoiDiemDuyet = CURRENT_TIMESTAMP
                 WHERE maBienBan = ?"
            )->execute([$trangThai, $lyDoTuChoi, $maNguoiDuyet, $maBienBan]);
        }
        return $db->prepare("UPDATE BienBanKiemKe SET trangThai = ? WHERE maBienBan = ?")->execute([$trangThai, $maBienBan]);
    }

    // [UC18 - Hình 26] BienBanKiemKe::kiemTraCoChenhLech(maBienBan) — Bảng 80 STT 3 (3NF: tính từ soLuongThucTe <> soLuongHeThong)
    public static function kiemTraCoChenhLech(int $maBienBan, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("SELECT COUNT(*) FROM ChiTietKiemKe WHERE maBienBan = ? AND soLuongThucTe <> soLuongHeThong");
        $stmt->execute([$maBienBan]);
        return ((int)$stmt->fetchColumn()) > 0;
    }
}
