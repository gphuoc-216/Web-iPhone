<?php
// Entity BCE: KhachHang — Bảng KhachHang

class KhachHang
{
    public const TEN_BANG = 'KhachHang';
    public const KHOA_CHINH = 'maKhachHang';

    private ?int $maKhachHang = null;
    private ?int $maTaiKhoan = null;
    private string $hoTen = '';
    private ?string $soDienThoai = null;
    private ?string $email = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maKhachHang', $duLieu)) {
            $this->maKhachHang = isset($duLieu['maKhachHang']) && $duLieu['maKhachHang'] !== '' ? (int)$duLieu['maKhachHang'] : null;
        }
        if (array_key_exists('maTaiKhoan', $duLieu)) {
            $this->maTaiKhoan = isset($duLieu['maTaiKhoan']) && $duLieu['maTaiKhoan'] !== '' ? (int)$duLieu['maTaiKhoan'] : null;
        }
        if (array_key_exists('hoTen', $duLieu)) {
            $this->hoTen = (string)($duLieu['hoTen'] ?? '');
        }
        if (array_key_exists('soDienThoai', $duLieu)) {
            $this->soDienThoai = isset($duLieu['soDienThoai']) && $duLieu['soDienThoai'] !== '' ? (string)$duLieu['soDienThoai'] : null;
        }
        if (array_key_exists('email', $duLieu)) {
            $this->email = isset($duLieu['email']) && $duLieu['email'] !== '' ? (string)$duLieu['email'] : null;
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
            'maKhachHang' => $this->maKhachHang,
            'maTaiKhoan' => $this->maTaiKhoan,
            'hoTen' => $this->hoTen,
            'soDienThoai' => $this->soDienThoai,
            'email' => $this->email,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraHopLe(string $hoTen, ?string $soDienThoai = null, ?string $email = null): bool
    {
        if (trim($hoTen) === '') return false;
        if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        return true;
    }

    public static function luu(int $maTaiKhoan, string $hoTen, ?string $soDienThoai = null, ?string $email = null, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO KhachHang (maTaiKhoan, hoTen, soDienThoai, email) VALUES (?, ?, ?, ?)")
           ->execute([$maTaiKhoan, trim($hoTen), $soDienThoai, $email]);
        return (int)$db->lastInsertId();
    }

    public static function capNhat(int $maKhachHang, string $hoTen, ?string $soDienThoai, ?string $email, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE KhachHang SET hoTen = ?, soDienThoai = ?, email = ? WHERE maKhachHang = ?")
            ->execute([trim($hoTen), $soDienThoai, $email, $maKhachHang]);
    }

    public static function timTheoMa(int $maKhachHang, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare(
            "SELECT kh.*, tk.dinhDanh, tk.vaiTro, tk.trangThai
             FROM KhachHang kh
             JOIN TaiKhoan tk ON tk.maTaiKhoan = kh.maTaiKhoan
             WHERE kh.maKhachHang = ? LIMIT 1"
        );
        $stmt->execute([$maKhachHang]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function timTheoTaiKhoan(int $maTaiKhoan, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT maKhachHang, hoTen, soDienThoai, email FROM KhachHang WHERE maTaiKhoan = ? LIMIT 1");
        $stmt->execute([$maTaiKhoan]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function timTheoTuKhoa(?string $tuKhoa = null, ?PDO $pdo = null): array
    {
        $sql = "SELECT kh.*, tk.dinhDanh, tk.trangThai,
                       COUNT(dh.maDonHang) AS tongSoDon,
                       COALESCE(SUM(CASE WHEN dh.trangThai <> 'Đã hủy' THEN dh.tongTien ELSE 0 END), 0) AS tongChiTieu
                FROM KhachHang kh
                JOIN TaiKhoan tk ON tk.maTaiKhoan = kh.maTaiKhoan
                LEFT JOIN DonHang dh ON dh.maKhachHang = kh.maKhachHang";
        $ts = [];
        $kw = trim((string)$tuKhoa);
        if ($kw !== '') {
            $sql .= " WHERE kh.hoTen LIKE ? OR kh.soDienThoai LIKE ? OR kh.email LIKE ?";
            $m = '%' . $kw . '%';
            $ts = [$m, $m, $m];
        }
        $sql .= " GROUP BY kh.maKhachHang ORDER BY kh.maKhachHang DESC";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($ts);
        return $stmt->fetchAll();
    }

}
