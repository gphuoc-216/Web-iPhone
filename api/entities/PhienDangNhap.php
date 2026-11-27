<?php
// Entity BCE: PhienDangNhap — Bảng PhienDangNhap (Mục 3.4.3.20: Bảng 115, Bảng 116; RDM: Bảng 160)

class PhienDangNhap
{
    public const TEN_BANG = 'PhienDangNhap';
    public const KHOA_CHINH = 'maPhien';

    private string $maPhien = '';
    private ?int $maTaiKhoan = null;
    private ?string $thoiDiemTao = null;
    private ?string $thoiDiemHetHan = null;
    private string $trangThai = 'Còn hiệu lực';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maPhien', $duLieu)) {
            $this->maPhien = (string)($duLieu['maPhien'] ?? '');
        }
        if (array_key_exists('maTaiKhoan', $duLieu)) {
            $this->maTaiKhoan = isset($duLieu['maTaiKhoan']) && $duLieu['maTaiKhoan'] !== '' ? (int)$duLieu['maTaiKhoan'] : null;
        }
        if (array_key_exists('thoiDiemTao', $duLieu)) {
            $this->thoiDiemTao = isset($duLieu['thoiDiemTao']) && $duLieu['thoiDiemTao'] !== '' ? (string)$duLieu['thoiDiemTao'] : null;
        }
        if (array_key_exists('thoiDiemHetHan', $duLieu)) {
            $this->thoiDiemHetHan = isset($duLieu['thoiDiemHetHan']) && $duLieu['thoiDiemHetHan'] !== '' ? (string)$duLieu['thoiDiemHetHan'] : null;
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
            'maPhien'        => $this->maPhien,
            'maTaiKhoan'     => $this->maTaiKhoan,
            'thoiDiemTao'    => $this->thoiDiemTao,
            'thoiDiemHetHan' => $this->thoiDiemHetHan,
            'trangThai'      => $this->trangThai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public function conHieuLuc(): bool
    {
        if ($this->trangThai !== 'Còn hiệu lực' || !$this->thoiDiemHetHan) {
            return false;
        }
        return strtotime($this->thoiDiemHetHan) > time();
    }

    // [UC40 - Hình 48] PhienDangNhap::taoPhien(maTaiKhoan) — Bảng 116 STT 5
    public static function taoPhien(int $maTaiKhoan, ?PDO $pdo = null): array
    {
        $maPhien = bin2hex(random_bytes(32));
        $thoiDiemHetHan = date('Y-m-d H:i:s', time() + 86400 * 7);
        self::pdo($pdo)->prepare(
            "INSERT INTO PhienDangNhap (maPhien, maTaiKhoan, thoiDiemTao, thoiDiemHetHan, trangThai)
             VALUES (?, ?, CURRENT_TIMESTAMP, ?, 'Còn hiệu lực')"
        )->execute([$maPhien, $maTaiKhoan, $thoiDiemHetHan]);
        return ['maPhien' => $maPhien, 'thoiDiemHetHan' => $thoiDiemHetHan];
    }

    // [UC41 - Hình 49] PhienDangNhap::huyPhien(maPhien) — Bảng 116 STT 1
    public static function huyPhien(string $maPhien, ?PDO $pdo = null): bool
    {
        if ($maPhien === '') return false;
        return self::pdo($pdo)->prepare("UPDATE PhienDangNhap SET trangThai = 'Đã hủy' WHERE maPhien = ?")->execute([$maPhien]);
    }

    // [UC42, UC49, UC52 - Hình 50, Hình 57, Hình 60] PhienDangNhap::huyPhienTheoTaiKhoan(maTaiKhoan) — Bảng 116 STT 2
    public static function huyPhienTheoTaiKhoan(int $maTaiKhoan, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE PhienDangNhap SET trangThai = 'Đã hủy' WHERE maTaiKhoan = ?")->execute([$maTaiKhoan]);
    }

    // [UC41 - Hình 49] PhienDangNhap::kiemTraConHieuLuc(maPhien) — Bảng 116 STT 3
    public static function kiemTraConHieuLuc(string $maPhien, ?PDO $pdo = null): bool
    {
        return self::layThongTinPhien($maPhien, $pdo) !== null;
    }

    // [UC28 - Hình 36] PhienDangNhap::kiemTraDangNhap(maPhien) — Bảng 116 STT 4
    public static function kiemTraDangNhap(string $maPhien, ?PDO $pdo = null): bool
    {
        return self::layThongTinPhien($maPhien, $pdo) !== null;
    }

    public static function layThongTinPhien(string $maPhien, ?PDO $pdo = null): ?array
    {
        if ($maPhien === '') return null;
        $stmt = self::pdo($pdo)->prepare(
            "SELECT p.*, tk.dinhDanh, tk.vaiTro, tk.trangThai AS trangThaiTaiKhoan
             FROM PhienDangNhap p
             JOIN TaiKhoan tk ON tk.maTaiKhoan = p.maTaiKhoan
             WHERE p.maPhien = ? LIMIT 1"
        );
        $stmt->execute([$maPhien]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $phien = self::tuMang($row);
        if (!$phien->conHieuLuc() || $row['trangThaiTaiKhoan'] !== 'Hoạt động') {
            return null;
        }
        return $row;
    }
}
