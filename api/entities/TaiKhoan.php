<?php
// Entity BCE: TaiKhoan — Bảng TaiKhoan

class TaiKhoan
{
    public const TEN_BANG = 'TaiKhoan';
    public const KHOA_CHINH = 'maTaiKhoan';

    private ?int $maTaiKhoan = null;
    private string $dinhDanh = '';
    private string $matKhauDaBam = '';
    private string $vaiTro = 'Khách hàng';
    private string $trangThai = 'Hoạt động';
    private ?string $lyDoKhoa = null;
    private ?string $maXacThuc = null;
    private ?string $hanMaXacThuc = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maTaiKhoan', $duLieu)) {
            $this->maTaiKhoan = isset($duLieu['maTaiKhoan']) && $duLieu['maTaiKhoan'] !== '' ? (int)$duLieu['maTaiKhoan'] : null;
        }
        if (array_key_exists('dinhDanh', $duLieu)) {
            $this->dinhDanh = (string)($duLieu['dinhDanh'] ?? '');
        }
        if (array_key_exists('matKhauDaBam', $duLieu)) {
            $this->matKhauDaBam = (string)($duLieu['matKhauDaBam'] ?? '');
        }
        if (array_key_exists('vaiTro', $duLieu)) {
            $this->vaiTro = (string)($duLieu['vaiTro'] ?? '');
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
        }
        if (array_key_exists('lyDoKhoa', $duLieu)) {
            $this->lyDoKhoa = isset($duLieu['lyDoKhoa']) && $duLieu['lyDoKhoa'] !== '' ? (string)$duLieu['lyDoKhoa'] : null;
        }
        if (array_key_exists('maXacThuc', $duLieu)) {
            $this->maXacThuc = isset($duLieu['maXacThuc']) && $duLieu['maXacThuc'] !== '' ? (string)$duLieu['maXacThuc'] : null;
        }
        if (array_key_exists('hanMaXacThuc', $duLieu)) {
            $this->hanMaXacThuc = isset($duLieu['hanMaXacThuc']) && $duLieu['hanMaXacThuc'] !== '' ? (string)$duLieu['hanMaXacThuc'] : null;
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
            'maTaiKhoan' => $this->maTaiKhoan,
            'dinhDanh' => $this->dinhDanh,
            'matKhauDaBam' => $this->matKhauDaBam,
            'vaiTro' => $this->vaiTro,
            'trangThai' => $this->trangThai,
            'lyDoKhoa' => $this->lyDoKhoa,
            'maXacThuc' => $this->maXacThuc,
            'hanMaXacThuc' => $this->hanMaXacThuc,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public function dangHoatDong(): bool
    {
        return $this->trangThai === 'Hoạt động';
    }

    public function chuyenThanhMangAnToan(): array
    {
        $mang = $this->chuyenThanhMang();
        unset($mang['matKhauDaBam'], $mang['maXacThuc'], $mang['hanMaXacThuc']);
        return $mang;
    }

    public static function kiemTraHopLe(string $dinhDanh, string $matKhau, ?string $xacNhanMatKhau = null): bool
    {
        if (trim($dinhDanh) === '' || strlen($matKhau) < 6) return false;
        if ($xacNhanMatKhau !== null && $matKhau !== $xacNhanMatKhau) return false;
        return true;
    }

    public static function kiemTraHopLeNhanVien(string $dinhDanh, string $vaiTro, string $matKhauTam): bool
    {
        $vaiTroNoiBo = ['Ban Quản lý', 'Nhân viên Bán hàng / CSKH', 'Nhân viên Kho'];
        return trim($dinhDanh) !== '' && strlen($matKhauTam) >= 6 && in_array($vaiTro, $vaiTroNoiBo, true);
    }

    public static function kiemTraHopLeCapNhat(int $maTaiKhoan, string $vaiTro): bool
    {
        $vaiTroNoiBo = ['Ban Quản lý', 'Nhân viên Bán hàng / CSKH', 'Nhân viên Kho'];
        return $maTaiKhoan > 0 && in_array($vaiTro, $vaiTroNoiBo, true);
    }

    public static function kiemTraQuyenKhoa(int $maTaiKhoanThucHien, int $maTaiKhoan): bool
    {
        return $maTaiKhoanThucHien !== $maTaiKhoan && $maTaiKhoan > 0;
    }

    public static function kiemTraTonTai(string $dinhDanh, ?PDO $pdo = null): bool
    {
        return self::timTheoDinhDanh($dinhDanh, $pdo) !== null;
    }

    public static function kiemTraTrungThongTin(int $maKhachHang, ?string $soDienThoai, ?string $email, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if ($soDienThoai) {
            $stmt = $db->prepare("SELECT 1 FROM KhachHang WHERE soDienThoai = ? AND maKhachHang <> ? LIMIT 1");
            $stmt->execute([$soDienThoai, $maKhachHang]);
            if ($stmt->fetchColumn()) return true;
        }
        if ($email) {
            $stmt = $db->prepare("SELECT 1 FROM KhachHang WHERE email = ? AND maKhachHang <> ? LIMIT 1");
            $stmt->execute([$email, $maKhachHang]);
            if ($stmt->fetchColumn()) return true;
        }
        return false;
    }

    public static function timTheoDinhDanh(string $dinhDanh, ?PDO $pdo = null): ?self
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM TaiKhoan WHERE dinhDanh = ? LIMIT 1");
        $stmt->execute([trim($dinhDanh)]);
        $r = $stmt->fetch();
        return $r ? self::tuMang($r) : null;
    }

    public static function timTheoMa(int $maTaiKhoan, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT tk.maTaiKhoan, tk.dinhDanh, tk.vaiTro, tk.trangThai, tk.lyDoKhoa,
                    COALESCE(kh.hoTen, nv.hoTen) AS hoTen,
                    COALESCE(kh.soDienThoai, nv.soDienThoai) AS soDienThoai,
                    kh.email
             FROM TaiKhoan tk
             LEFT JOIN KhachHang kh ON kh.maTaiKhoan = tk.maTaiKhoan
             LEFT JOIN NhanVien nv ON nv.maTaiKhoan = tk.maTaiKhoan
             WHERE tk.maTaiKhoan = ? LIMIT 1"
        );
        $stmt->execute([$maTaiKhoan]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function timTatCa(?PDO $pdo = null): array
    {
        return self::timTheoTuKhoa('', $pdo);
    }

    public static function timTheoTuKhoa(string $tuKhoa = '', ?PDO $pdo = null): array
    {
        $sql = "SELECT tk.maTaiKhoan, tk.dinhDanh, tk.vaiTro, tk.trangThai, tk.lyDoKhoa,
                       COALESCE(kh.hoTen, nv.hoTen) AS hoTen,
                       COALESCE(kh.soDienThoai, nv.soDienThoai) AS soDienThoai,
                       kh.email
                FROM TaiKhoan tk
                LEFT JOIN KhachHang kh ON kh.maTaiKhoan = tk.maTaiKhoan
                LEFT JOIN NhanVien nv ON nv.maTaiKhoan = tk.maTaiKhoan";
        $ts = [];
        $kw = trim($tuKhoa);
        if ($kw !== '') {
            $sql .= " WHERE tk.dinhDanh LIKE ? OR kh.hoTen LIKE ? OR nv.hoTen LIKE ? OR tk.vaiTro LIKE ?";
            $m = '%' . $kw . '%';
            $ts = [$m, $m, $m, $m];
        }
        $sql .= " ORDER BY tk.maTaiKhoan DESC";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($ts);
        return $stmt->fetchAll();
    }

    public static function kiemTraMatKhau(string $dinhDanh, string $matKhau, ?PDO $pdo = null): bool
    {
        $tk = self::timTheoDinhDanh($dinhDanh, $pdo);
        return $tk !== null && password_verify($matKhau, $tk->matKhauDaBam);
    }

    public static function layVaiTro(int $maTaiKhoan, ?PDO $pdo = null): ?string
    {
        $stmt = self::pdo($pdo)->prepare("SELECT vaiTro FROM TaiKhoan WHERE maTaiKhoan = ? LIMIT 1");
        $stmt->execute([$maTaiKhoan]);
        $v = $stmt->fetchColumn();
        return $v !== false ? (string)$v : null;
    }

    public static function luu(string $dinhDanh, string $matKhauDaBam, string $vaiTro = 'Khách hàng', string $trangThai = 'Hoạt động', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO TaiKhoan (dinhDanh, matKhauDaBam, vaiTro, trangThai) VALUES (?, ?, ?, ?)")
           ->execute([trim($dinhDanh), $matKhauDaBam, $vaiTro, $trangThai]);
        return (int)$db->lastInsertId();
    }

    public static function capNhatVaiTro(int $maTaiKhoan, string $vaiTro, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE TaiKhoan SET vaiTro = ? WHERE maTaiKhoan = ?")->execute([$vaiTro, $maTaiKhoan]);
    }

    public static function capNhatTrangThai(int $maTaiKhoan, string $trangThai, ?string $lyDoKhoa = null, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE TaiKhoan SET trangThai = ?, lyDoKhoa = ? WHERE maTaiKhoan = ?")
            ->execute([$trangThai, $lyDoKhoa, $maTaiKhoan]);
    }

    public static function taoMaXacThuc(int $maTaiKhoan, ?PDO $pdo = null): array
    {
        $maXacThuc = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hanMa = date('Y-m-d H:i:s', time() + 900);
        self::pdo($pdo)->prepare("UPDATE TaiKhoan SET maXacThuc = ?, hanMaXacThuc = ? WHERE maTaiKhoan = ?")
            ->execute([$maXacThuc, $hanMa, $maTaiKhoan]);
        return ['maXacThucMoPhong' => $maXacThuc, 'hanMaXacThuc' => $hanMa];
    }

    public static function kiemTraMaXacThuc(string $dinhDanh, string $maXacThuc, ?PDO $pdo = null): ?self
    {
        $tk = self::timTheoDinhDanh($dinhDanh, $pdo);
        if (!$tk || !$tk->maXacThuc || $tk->maXacThuc !== trim($maXacThuc)) {
            return null;
        }
        if (!$tk->hanMaXacThuc || strtotime($tk->hanMaXacThuc) < time()) {
            return null;
        }
        return $tk;
    }

    public static function capNhatMatKhau(int $maTaiKhoan, string $matKhauMoiDaBam, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE TaiKhoan SET matKhauDaBam = ?, maXacThuc = NULL, hanMaXacThuc = NULL WHERE maTaiKhoan = ?")
            ->execute([$matKhauMoiDaBam, $maTaiKhoan]);
    }

}
