<?php
// Entity BCE: ThongBao — Bảng ThongBao

class ThongBao
{
    public const TEN_BANG = 'ThongBao';
    public const KHOA_CHINH = 'maThongBao';

    private ?int $maThongBao = null;
    private ?int $maNguoiNhan = null;
    private string $noiDung = '';
    private ?string $thoiDiemTao = null;
    private bool $daDoc = false;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maThongBao', $duLieu)) {
            $this->maThongBao = isset($duLieu['maThongBao']) && $duLieu['maThongBao'] !== '' ? (int)$duLieu['maThongBao'] : null;
        }
        if (array_key_exists('maNguoiNhan', $duLieu)) {
            $this->maNguoiNhan = isset($duLieu['maNguoiNhan']) && $duLieu['maNguoiNhan'] !== '' ? (int)$duLieu['maNguoiNhan'] : null;
        }
        if (array_key_exists('noiDung', $duLieu)) {
            $this->noiDung = (string)($duLieu['noiDung'] ?? '');
        }
        if (array_key_exists('thoiDiemTao', $duLieu)) {
            $this->thoiDiemTao = isset($duLieu['thoiDiemTao']) && $duLieu['thoiDiemTao'] !== '' ? (string)$duLieu['thoiDiemTao'] : null;
        }
        if (array_key_exists('daDoc', $duLieu)) {
            $this->daDoc = (bool)($duLieu['daDoc'] ?? false);
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
            'maThongBao' => $this->maThongBao,
            'maNguoiNhan' => $this->maNguoiNhan,
            'noiDung' => $this->noiDung,
            'thoiDiemTao' => $this->thoiDiemTao,
            'daDoc' => $this->daDoc,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function taoThongBao(int $maNguoiNhan, string $noiDung, ?PDO $pdo = null): bool
    {
        if ($maNguoiNhan <= 0 || trim($noiDung) === '') return false;
        return self::pdo($pdo)->prepare(
            "INSERT INTO ThongBao (maNguoiNhan, noiDung, thoiDiemTao, daDoc) VALUES (?, ?, CURRENT_TIMESTAMP, FALSE)"
        )->execute([$maNguoiNhan, trim($noiDung)]);
    }

    public static function taoThongBaoTheoVaiTro(string $vaiTro, string $noiDung, ?PDO $pdo = null): void
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT maTaiKhoan FROM TaiKhoan WHERE vaiTro = ? AND trangThai = 'Hoạt động'");
        $stmt->execute([$vaiTro]);
        foreach ($stmt->fetchAll() as $tk) {
            self::taoThongBao((int)$tk['maTaiKhoan'], $noiDung, $db);
        }
    }

    public static function timTheoNguoiNhan(int $maTaiKhoan, ?PDO $pdo = null): array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM ThongBao WHERE maNguoiNhan = ? ORDER BY thoiDiemTao DESC LIMIT 50");
        $stmt->execute([$maTaiKhoan]);
        return array_map(fn($r) => self::tuMang($r)->chuyenThanhMang(), $stmt->fetchAll());
    }

    public static function danhDauDaDoc(int $maTaiKhoan, ?int $maThongBao = null, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if ($maThongBao !== null && $maThongBao > 0) {
            return $db->prepare("UPDATE ThongBao SET daDoc = TRUE WHERE maThongBao = ? AND maNguoiNhan = ?")
                      ->execute([$maThongBao, $maTaiKhoan]);
        }
        return $db->prepare("UPDATE ThongBao SET daDoc = TRUE WHERE maNguoiNhan = ?")->execute([$maTaiKhoan]);
    }

}
