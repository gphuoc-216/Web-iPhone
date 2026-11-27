<?php
// Entity BCE: TaiKhoan — Bảng TaiKhoan (Mục 3.4.3.24: Bảng 123, Bảng 124; RDM: Bảng 164)

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
            'maTaiKhoan'   => $this->maTaiKhoan,
            'dinhDanh'     => $this->dinhDanh,
            'matKhauDaBam' => $this->matKhauDaBam,
            'vaiTro'       => $this->vaiTro,
            'trangThai'    => $this->trangThai,
            'lyDoKhoa'     => $this->lyDoKhoa,
            'maXacThuc'    => $this->maXacThuc,
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

    // [UC39, UC51 - Hình 47, Hình 59] TaiKhoan::kiemTraHopLe(...) — Bảng 124 STT 4 & 5 (nạp chồng cho đăng ký KH & tạo nhân viên)
    public static function kiemTraHopLe(string $dinhDanhHoacEmail, string $thamSo2, ?string $thamSo3 = null, ?string $matKhauTam = null): bool
    {
        // Nhánh 4 tham số (UC51): kiemTraHopLe(email, soDienThoai, vaiTro, matKhauTam)
        if ($matKhauTam !== null) {
            $vaiTroHopLe = ['Nhân viên Bán hàng / CSKH', 'Nhân viên Kho', 'Ban Quản lý'];
            $sdt = trim($thamSo2);
            if (trim($dinhDanhHoacEmail) === '' || strlen($matKhauTam) < 6) {
                return false;
            }
            if ($sdt !== '' && !preg_match('/^[0-9]{9,15}$/', $sdt)) {
                return false;
            }
            return in_array(trim((string)$thamSo3), $vaiTroHopLe, true);
        }

        // Nhánh 3 tham số (UC39): kiemTraHopLe(dinhDanh, matKhau, xacNhanMatKhau)
        if (trim($dinhDanhHoacEmail) === '' || strlen($thamSo2) < 6) {
            return false;
        }
        if ($thamSo3 !== null && $thamSo2 !== $thamSo3) {
            return false;
        }
        return true;
    }

    public static function kiemTraHopLeNhanVien(string $dinhDanh, string $vaiTro, string $matKhauTam, string $soDienThoai = ''): bool
    {
        return self::kiemTraHopLe($dinhDanh, $soDienThoai, $vaiTro, $matKhauTam);
    }

    // [UC52 - Hình 60] TaiKhoan::kiemTraHopLeCapNhat(maTaiKhoan, soDienThoai, vaiTro) — Bảng 124 STT 6
    public static function kiemTraHopLeCapNhat(int $maTaiKhoan, string $soDienThoaiHoacVaiTro, ?string $vaiTro = null, ?PDO $pdo = null): bool
    {
        $vaiTroNoiBo = ['Nhân viên Bán hàng / CSKH', 'Nhân viên Kho', 'Ban Quản lý'];
        if ($vaiTro === null) {
            return $maTaiKhoan > 0 && in_array(trim($soDienThoaiHoacVaiTro), $vaiTroNoiBo, true);
        }

        $sdt = trim($soDienThoaiHoacVaiTro);
        if ($maTaiKhoan <= 0 || !in_array(trim($vaiTro), $vaiTroNoiBo, true)) {
            return false;
        }
        if ($sdt !== '' && !preg_match('/^[0-9]{9,15}$/', $sdt)) {
            return false;
        }
        if ($sdt !== '') {
            $db = self::pdo($pdo);
            $stmtNv = $db->prepare("SELECT 1 FROM NhanVien WHERE soDienThoai = ? AND maTaiKhoan <> ? LIMIT 1");
            $stmtNv->execute([$sdt, $maTaiKhoan]);
            if ($stmtNv->fetchColumn()) {
                return false;
            }
            $stmtTk = $db->prepare("SELECT 1 FROM TaiKhoan WHERE dinhDanh = ? AND maTaiKhoan <> ? LIMIT 1");
            $stmtTk->execute([$sdt, $maTaiKhoan]);
            if ($stmtTk->fetchColumn()) {
                return false;
            }
        }
        return true;
    }

    // [UC49 - Hình 57] TaiKhoan::kiemTraQuyenKhoa(maTaiKhoanThucHien, maTaiKhoan) — Bảng 124 STT 9, Bảng 58 luồng phụ 2.1
    public static function kiemTraQuyenKhoa(int $maTaiKhoanThucHien, int $maTaiKhoan, ?PDO $pdo = null): bool
    {
        if ($maTaiKhoan <= 0 || $maTaiKhoanThucHien === $maTaiKhoan) {
            return false;
        }
        $vaiTroMucTieu = self::layVaiTro($maTaiKhoan, $pdo);
        if ($vaiTroMucTieu === null || $vaiTroMucTieu === 'Ban Quản lý') {
            return false;
        }
        return true;
    }

    // [UC39, UC51 - Hình 47, Hình 59] TaiKhoan::kiemTraTonTai(dinhDanh) — Bảng 124 STT 10
    public static function kiemTraTonTai(string $dinhDanh, ?string $soDienThoai = null, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if (self::timTheoDinhDanh($dinhDanh, $db) !== null) {
            return true;
        }
        $dd = trim($dinhDanh);
        $stmtEmail = $db->prepare("SELECT 1 FROM KhachHang WHERE email = ? OR soDienThoai = ? LIMIT 1");
        $stmtEmail->execute([$dd, $dd]);
        if ($stmtEmail->fetchColumn()) {
            return true;
        }
        if ($soDienThoai !== null && trim($soDienThoai) !== '') {
            $sdt = trim($soDienThoai);
            if (self::timTheoDinhDanh($sdt, $db) !== null) {
                return true;
            }
            $stmtNv = $db->prepare("SELECT 1 FROM NhanVien WHERE soDienThoai = ? LIMIT 1");
            $stmtNv->execute([$sdt]);
            if ($stmtNv->fetchColumn()) {
                return true;
            }
            $stmtKh = $db->prepare("SELECT 1 FROM KhachHang WHERE soDienThoai = ? LIMIT 1");
            $stmtKh->execute([$sdt]);
            if ($stmtKh->fetchColumn()) {
                return true;
            }
        }
        return false;
    }

    // [UC44 - Hình 52] TaiKhoan::kiemTraTrungThongTin(maKhachHang, soDienThoai, email) — Bảng 124 STT 11
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

    // [UC40, UC42 - Hình 48, Hình 50] TaiKhoan::timTheoDinhDanh(dinhDanh) — Bảng 124 STT 17
    public static function timTheoDinhDanh(string $dinhDanh, ?PDO $pdo = null): ?self
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM TaiKhoan WHERE dinhDanh = ? LIMIT 1");
        $stmt->execute([trim($dinhDanh)]);
        $r = $stmt->fetch();
        return $r ? self::tuMang($r) : null;
    }

    // [UC48 - Hình 56] TaiKhoan::timTheoMa(maTaiKhoan) — Bảng 124 STT 18
    public static function timTheoMa(int $maTaiKhoan, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT tk.maTaiKhoan, tk.dinhDanh, tk.vaiTro, tk.trangThai, tk.lyDoKhoa,
                    COALESCE(kh.hoTen, nv.hoTen) AS hoTen,
                    COALESCE(NULLIF(kh.soDienThoai, ''), NULLIF(nv.soDienThoai, '')) AS soDienThoai,
                    COALESCE(kh.email, CASE WHEN tk.dinhDanh LIKE '%@%' THEN tk.dinhDanh ELSE NULL END) AS email
             FROM TaiKhoan tk
             LEFT JOIN KhachHang kh ON kh.maTaiKhoan = tk.maTaiKhoan
             LEFT JOIN NhanVien nv ON nv.maTaiKhoan = tk.maTaiKhoan
             WHERE tk.maTaiKhoan = ? LIMIT 1"
        );
        $stmt->execute([$maTaiKhoan]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    // [UC48, UC49, UC50 - Hình 56 - Hình 58] TaiKhoan::timTatCa() — Bảng 124 STT 16
    public static function timTatCa(?PDO $pdo = null): array
    {
        return self::timTheoTuKhoa('', $pdo);
    }

    // [UC48 - Hình 56] TaiKhoan::timTheoTuKhoa(tuKhoa) — Bảng 124 STT 19
    public static function timTheoTuKhoa(string $tuKhoa = '', ?PDO $pdo = null): array
    {
        $sql = "SELECT tk.maTaiKhoan, tk.dinhDanh, tk.vaiTro, tk.trangThai, tk.lyDoKhoa,
                       COALESCE(kh.hoTen, nv.hoTen) AS hoTen,
                       COALESCE(NULLIF(kh.soDienThoai, ''), NULLIF(nv.soDienThoai, '')) AS soDienThoai,
                       COALESCE(kh.email, CASE WHEN tk.dinhDanh LIKE '%@%' THEN tk.dinhDanh ELSE NULL END) AS email
                FROM TaiKhoan tk
                LEFT JOIN KhachHang kh ON kh.maTaiKhoan = tk.maTaiKhoan
                LEFT JOIN NhanVien nv ON nv.maTaiKhoan = tk.maTaiKhoan";
        $ts = [];
        $kw = trim($tuKhoa);
        if ($kw !== '') {
            $sql .= " WHERE tk.dinhDanh LIKE ? OR kh.hoTen LIKE ? OR nv.hoTen LIKE ?
                      OR kh.soDienThoai LIKE ? OR nv.soDienThoai LIKE ? OR kh.email LIKE ? OR tk.vaiTro LIKE ?";
            $m = '%' . $kw . '%';
            $ts = [$m, $m, $m, $m, $m, $m, $m];
        }
        $sql .= " ORDER BY tk.maTaiKhoan DESC";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($ts);
        return $stmt->fetchAll();
    }

    // [UC40 - Hình 48] TaiKhoan::kiemTraMatKhau(dinhDanh, matKhau) — Bảng 124 STT 8
    public static function kiemTraMatKhau(string $dinhDanh, string $matKhau, ?PDO $pdo = null): bool
    {
        $tk = self::timTheoDinhDanh($dinhDanh, $pdo);
        return $tk !== null && password_verify($matKhau, $tk->matKhauDaBam);
    }

    // [UC40, UC52 - Hình 48, Hình 60] TaiKhoan::layVaiTro(maTaiKhoan) — Bảng 124 STT 12
    public static function layVaiTro(int $maTaiKhoan, ?PDO $pdo = null): ?string
    {
        $stmt = self::pdo($pdo)->prepare("SELECT vaiTro FROM TaiKhoan WHERE maTaiKhoan = ? LIMIT 1");
        $stmt->execute([$maTaiKhoan]);
        $v = $stmt->fetchColumn();
        return $v !== false ? (string)$v : null;
    }

    // [UC39, UC51 - Hình 47, Hình 59] TaiKhoan::luu(...) — Bảng 124 STT 13 & 14
    public static function luu(string $dinhDanh, string $matKhauDaBam, string $vaiTro = 'Khách hàng', $trangThaiHoacPdo = 'Hoạt động', ?PDO $pdo = null): int
    {
        if ($trangThaiHoacPdo instanceof PDO) {
            $pdo = $trangThaiHoacPdo;
            $trangThai = 'Hoạt động';
        } else {
            $trangThai = (string)$trangThaiHoacPdo;
        }
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO TaiKhoan (dinhDanh, matKhauDaBam, vaiTro, trangThai) VALUES (?, ?, ?, ?)")
           ->execute([trim($dinhDanh), $matKhauDaBam, $vaiTro, $trangThai]);
        return (int)$db->lastInsertId();
    }

    // [UC52 - Hình 60] TaiKhoan::capNhatVaiTro(maTaiKhoan, vaiTro) — Bảng 124 STT 3
    public static function capNhatVaiTro(int $maTaiKhoan, string $vaiTro, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE TaiKhoan SET vaiTro = ? WHERE maTaiKhoan = ?")->execute([$vaiTro, $maTaiKhoan]);
    }

    // [UC49, UC50 - Hình 57, Hình 58] TaiKhoan::capNhatTrangThai(maTaiKhoan, trangThai, lyDoKhoa) — Bảng 124 STT 2
    public static function capNhatTrangThai(int $maTaiKhoan, string $trangThai, ?string $lyDoKhoa = null, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE TaiKhoan SET trangThai = ?, lyDoKhoa = ? WHERE maTaiKhoan = ?")
            ->execute([$trangThai, $lyDoKhoa, $maTaiKhoan]);
    }

    // [UC42 - Hình 50] TaiKhoan::taoMaXacThuc(maTaiKhoan) — Bảng 124 STT 15
    public static function taoMaXacThuc(int $maTaiKhoan, ?PDO $pdo = null): array
    {
        $maXacThuc = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hanMa = date('Y-m-d H:i:s', time() + 900);
        self::pdo($pdo)->prepare("UPDATE TaiKhoan SET maXacThuc = ?, hanMaXacThuc = ? WHERE maTaiKhoan = ?")
            ->execute([$maXacThuc, $hanMa, $maTaiKhoan]);
        return ['maXacThucMoPhong' => $maXacThuc, 'hanMaXacThuc' => $hanMa];
    }

    // [UC42 - Hình 50] TaiKhoan::kiemTraMaXacThuc(dinhDanh, maXacThuc) — Bảng 124 STT 7
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

    // [UC42 - Hình 50] TaiKhoan::capNhatMatKhau(maTaiKhoan, matKhauMoiDaBam) — Bảng 124 STT 1
    public static function capNhatMatKhau(int $maTaiKhoan, string $matKhauMoiDaBam, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE TaiKhoan SET matKhauDaBam = ?, maXacThuc = NULL, hanMaXacThuc = NULL WHERE maTaiKhoan = ?")
            ->execute([$matKhauMoiDaBam, $maTaiKhoan]);
    }
}
