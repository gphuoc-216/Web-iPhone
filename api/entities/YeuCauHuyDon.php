<?php
// Entity BCE: YeuCauHuyDon — Bảng YeuCauHuyDon (Mục 3.4.3.30: Bảng 135, Bảng 136; RDM: Bảng 170)

class YeuCauHuyDon
{
    public const TEN_BANG = 'YeuCauHuyDon';
    public const KHOA_CHINH = 'maYeuCau';

    private ?int $maYeuCau = null;
    private ?int $maDonHang = null;
    private string $lyDo = '';
    private ?string $ngayGui = null;
    private string $trangThai = 'Chờ xử lý';
    private ?string $lyDoTuChoi = null;
    private ?int $maNhanVienXuLy = null;
    private ?string $ngayXuLy = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maYeuCau', $duLieu)) {
            $this->maYeuCau = isset($duLieu['maYeuCau']) && $duLieu['maYeuCau'] !== '' ? (int)$duLieu['maYeuCau'] : null;
        }
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
        }
        if (array_key_exists('lyDo', $duLieu)) {
            $this->lyDo = (string)($duLieu['lyDo'] ?? '');
        }
        if (array_key_exists('ngayGui', $duLieu)) {
            $this->ngayGui = isset($duLieu['ngayGui']) && $duLieu['ngayGui'] !== '' ? (string)$duLieu['ngayGui'] : null;
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
        }
        if (array_key_exists('lyDoTuChoi', $duLieu)) {
            $this->lyDoTuChoi = isset($duLieu['lyDoTuChoi']) && $duLieu['lyDoTuChoi'] !== '' ? (string)$duLieu['lyDoTuChoi'] : null;
        }
        if (array_key_exists('maNhanVienXuLy', $duLieu)) {
            $this->maNhanVienXuLy = isset($duLieu['maNhanVienXuLy']) && $duLieu['maNhanVienXuLy'] !== '' ? (int)$duLieu['maNhanVienXuLy'] : null;
        }
        if (array_key_exists('ngayXuLy', $duLieu)) {
            $this->ngayXuLy = isset($duLieu['ngayXuLy']) && $duLieu['ngayXuLy'] !== '' ? (string)$duLieu['ngayXuLy'] : null;
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
            'maYeuCau'       => $this->maYeuCau,
            'maDonHang'      => $this->maDonHang,
            'lyDo'           => $this->lyDo,
            'ngayGui'        => $this->ngayGui,
            'trangThai'      => $this->trangThai,
            'lyDoTuChoi'     => $this->lyDoTuChoi,
            'maNhanVienXuLy' => $this->maNhanVienXuLy,
            'ngayXuLy'       => $this->ngayXuLy,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC32 - Hình 40] YeuCauHuyDon::kiemTraDaCoYeuCau(maDonHang) — Bảng 136 STT 2
    public static function kiemTraDaCoYeuCau(int $maDonHang, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("SELECT 1 FROM YeuCauHuyDon WHERE maDonHang = ? AND trangThai = 'Chờ xử lý' LIMIT 1");
        $stmt->execute([$maDonHang]);
        return (bool)$stmt->fetchColumn();
    }

    // [UC32 - Hình 40] YeuCauHuyDon::luu(maDonHang, lyDo, trangThai) — Bảng 136 STT 3
    public static function luu(int $maDonHang, string $lyDo, string $trangThai = 'Chờ xử lý', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO YeuCauHuyDon (maDonHang, lyDo, ngayGui, trangThai) VALUES (?, ?, CURRENT_TIMESTAMP, ?)")
           ->execute([$maDonHang, trim($lyDo), $trangThai]);
        return (int)$db->lastInsertId();
    }

    // [UC35 - Hình 43] YeuCauHuyDon::timTatCa() — Bảng 136 STT 4
    public static function timTatCa(?PDO $pdo = null): array
    {
        return self::pdo($pdo)->query(
            "SELECT yc.*, dh.tongTien, dh.trangThai AS trangThaiDonHang, kh.hoTen AS tenKhachHang,
                    nv.hoTen AS tenNhanVienXuLy,
                    tt.phuongThuc, tt.trangThai AS trangThaiThanhToan, tt.soTien, tt.soTienHoan
             FROM YeuCauHuyDon yc
             JOIN DonHang dh ON dh.maDonHang = yc.maDonHang
             JOIN KhachHang kh ON kh.maKhachHang = dh.maKhachHang
             LEFT JOIN NhanVien nv ON nv.maNhanVien = yc.maNhanVienXuLy
             LEFT JOIN ThanhToan tt ON tt.maDonHang = dh.maDonHang
             ORDER BY yc.maYeuCau DESC"
        )->fetchAll();
    }

    // [UC35 - Hình 43] YeuCauHuyDon::timTheoMa(maYeuCau) — Bảng 136 STT 5
    public static function timTheoMa(int $maYeuCau, bool $khoaDong = false, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT * FROM YeuCauHuyDon WHERE maYeuCau = ? LIMIT 1" . ($khoaDong ? " FOR UPDATE" : "");
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maYeuCau]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    // [UC36, UC37 - Hình 44, Hình 45] YeuCauHuyDon::capNhatTrangThai(maYeuCau, trangThai, lyDoTuChoi, maNhanVienXuLy) — Bảng 136 STT 1
    public static function capNhatTrangThai(
        int $maYeuCau,
        string $trangThai,
        $lyDoTuChoiHoacPdo = null,
        ?int $maNhanVienXuLy = null,
        ?PDO $pdo = null
    ): bool {
        if ($lyDoTuChoiHoacPdo instanceof PDO) {
            $pdo = $lyDoTuChoiHoacPdo;
            $lyDoTuChoi = null;
        } else {
            $lyDoTuChoi = $lyDoTuChoiHoacPdo !== null ? trim((string)$lyDoTuChoiHoacPdo) : null;
        }
        return self::pdo($pdo)->prepare(
            "UPDATE YeuCauHuyDon
             SET trangThai = ?, lyDoTuChoi = ?, maNhanVienXuLy = ?, ngayXuLy = CURRENT_TIMESTAMP
             WHERE maYeuCau = ?"
        )->execute([$trangThai, $lyDoTuChoi, $maNhanVienXuLy, $maYeuCau]);
    }
}
