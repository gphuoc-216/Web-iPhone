<?php
// Entity BCE: LichSuCauHinhLoiNhuan — Bảng LichSuCauHinhLoiNhuan

class LichSuCauHinhLoiNhuan
{
    public const TEN_BANG = 'LichSuCauHinhLoiNhuan';
    public const KHOA_CHINH = 'maLichSu';

    private ?int $maLichSu = null;
    private ?int $maCauHinh = null;
    private string $phamVi = '';
    private float $tyLe = 0.0;
    private ?string $ngayApDung = null;
    private ?string $thoiDiemGhi = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maLichSu', $duLieu)) {
            $this->maLichSu = isset($duLieu['maLichSu']) && $duLieu['maLichSu'] !== '' ? (int)$duLieu['maLichSu'] : null;
        }
        if (array_key_exists('maCauHinh', $duLieu)) {
            $this->maCauHinh = isset($duLieu['maCauHinh']) && $duLieu['maCauHinh'] !== '' ? (int)$duLieu['maCauHinh'] : null;
        }
        if (array_key_exists('phamVi', $duLieu)) {
            $this->phamVi = (string)($duLieu['phamVi'] ?? '');
        }
        if (array_key_exists('tyLe', $duLieu)) {
            $this->tyLe = (float)($duLieu['tyLe'] ?? 0.0);
        }
        if (array_key_exists('ngayApDung', $duLieu)) {
            $this->ngayApDung = isset($duLieu['ngayApDung']) && $duLieu['ngayApDung'] !== '' ? (string)$duLieu['ngayApDung'] : null;
        }
        if (array_key_exists('thoiDiemGhi', $duLieu)) {
            $this->thoiDiemGhi = isset($duLieu['thoiDiemGhi']) && $duLieu['thoiDiemGhi'] !== '' ? (string)$duLieu['thoiDiemGhi'] : null;
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
            'maLichSu' => $this->maLichSu,
            'maCauHinh' => $this->maCauHinh,
            'phamVi' => $this->phamVi,
            'tyLe' => $this->tyLe,
            'ngayApDung' => $this->ngayApDung,
            'thoiDiemGhi' => $this->thoiDiemGhi,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function ghiLichSu(int $maCauHinh, string $phamVi, float $tyLe, string $ngayApDung, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare(
            "INSERT INTO LichSuCauHinhLoiNhuan (maCauHinh, phamVi, tyLe, ngayApDung, thoiDiemGhi)
             VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)"
        );
        return $stmt->execute([$maCauHinh, $phamVi, $tyLe, $ngayApDung]);
    }

    public static function timTatCa(?PDO $pdo = null): array
    {
        $rows = self::pdo($pdo)->query("SELECT * FROM LichSuCauHinhLoiNhuan ORDER BY thoiDiemGhi DESC LIMIT 50")->fetchAll();
        return array_map(fn($r) => self::tuMang($r)->chuyenThanhMang(), $rows);
    }

}
