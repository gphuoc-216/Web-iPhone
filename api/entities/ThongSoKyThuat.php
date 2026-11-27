<?php
// Entity BCE: ThongSoKyThuat — Bảng ThongSoKyThuat

class ThongSoKyThuat
{
    public const TEN_BANG = 'ThongSoKyThuat';
    public const KHOA_CHINH = 'maThongSo';

    private ?int $maThongSo = null;
    private ?int $maDienThoai = null;
    private ?string $manHinh = null;
    private ?string $chip = null;
    private int $ram = 8;
    private int $rom = 128;
    private ?string $camera = null;
    private int $dungLuongPin = 4500;
    private ?string $heDieuHanh = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maThongSo', $duLieu)) {
            $this->maThongSo = isset($duLieu['maThongSo']) && $duLieu['maThongSo'] !== '' ? (int)$duLieu['maThongSo'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('manHinh', $duLieu)) {
            $this->manHinh = isset($duLieu['manHinh']) && $duLieu['manHinh'] !== '' ? (string)$duLieu['manHinh'] : null;
        }
        if (array_key_exists('chip', $duLieu)) {
            $this->chip = isset($duLieu['chip']) && $duLieu['chip'] !== '' ? (string)$duLieu['chip'] : null;
        }
        if (array_key_exists('ram', $duLieu)) {
            $this->ram = (int)($duLieu['ram'] ?? 0);
        }
        if (array_key_exists('rom', $duLieu)) {
            $this->rom = (int)($duLieu['rom'] ?? 0);
        }
        if (array_key_exists('camera', $duLieu)) {
            $this->camera = isset($duLieu['camera']) && $duLieu['camera'] !== '' ? (string)$duLieu['camera'] : null;
        }
        if (array_key_exists('dungLuongPin', $duLieu)) {
            $this->dungLuongPin = (int)($duLieu['dungLuongPin'] ?? 0);
        }
        if (array_key_exists('heDieuHanh', $duLieu)) {
            $this->heDieuHanh = isset($duLieu['heDieuHanh']) && $duLieu['heDieuHanh'] !== '' ? (string)$duLieu['heDieuHanh'] : null;
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
            'maThongSo' => $this->maThongSo,
            'maDienThoai' => $this->maDienThoai,
            'manHinh' => $this->manHinh,
            'chip' => $this->chip,
            'ram' => $this->ram,
            'rom' => $this->rom,
            'camera' => $this->camera,
            'dungLuongPin' => $this->dungLuongPin,
            'heDieuHanh' => $this->heDieuHanh,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function timTheoDienThoai(int $maDienThoai, ?PDO $pdo = null): ?self
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM ThongSoKyThuat WHERE maDienThoai = ? LIMIT 1");
        $stmt->execute([$maDienThoai]);
        $row = $stmt->fetch();
        return $row ? self::tuMang($row) : null;
    }

    public static function locTheoThongSo(?int $ram, ?int $rom, ?int $dungLuongPin, ?PDO $pdo = null): array
    {
        $dk = ['1=1'];
        $ts = [];
        if ($ram !== null && $ram > 0) { $dk[] = "ram = ?"; $ts[] = $ram; }
        if ($rom !== null && $rom > 0) { $dk[] = "rom = ?"; $ts[] = $rom; }
        if ($dungLuongPin !== null && $dungLuongPin > 0) { $dk[] = "dungLuongPin >= ?"; $ts[] = $dungLuongPin; }
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM ThongSoKyThuat WHERE " . implode(' AND ', $dk));
        $stmt->execute($ts);
        return array_map(fn($r) => self::tuMang($r)->chuyenThanhMang(), $stmt->fetchAll());
    }

    public static function luu(int $maDienThoai, array $thongSo, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare(
            "INSERT INTO ThongSoKyThuat (maDienThoai, manHinh, chip, ram, rom, camera, dungLuongPin, heDieuHanh)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $maDienThoai,
            $thongSo['manHinh'] ?? '6.3 inches OLED',
            $thongSo['chip'] ?? 'Đang cập nhật',
            max(1, (int)($thongSo['ram'] ?? 8)),
            max(1, (int)($thongSo['rom'] ?? 128)),
            $thongSo['camera'] ?? '48MP',
            max(1, (int)($thongSo['dungLuongPin'] ?? 4500)),
            $thongSo['heDieuHanh'] ?? 'iOS / Android',
        ]);
        return (int)$db->lastInsertId();
    }

    public static function capNhat(int $maDienThoai, array $thongSo, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare(
            "UPDATE ThongSoKyThuat
             SET manHinh = COALESCE(?, manHinh),
                 chip = COALESCE(?, chip),
                 ram = COALESCE(?, ram),
                 rom = COALESCE(?, rom),
                 camera = COALESCE(?, camera),
                 dungLuongPin = COALESCE(?, dungLuongPin),
                 heDieuHanh = COALESCE(?, heDieuHanh)
             WHERE maDienThoai = ?"
        );
        return $stmt->execute([
            $thongSo['manHinh'] ?? null,
            $thongSo['chip'] ?? null,
            isset($thongSo['ram']) && $thongSo['ram'] !== '' ? max(1, (int)$thongSo['ram']) : null,
            isset($thongSo['rom']) && $thongSo['rom'] !== '' ? max(1, (int)$thongSo['rom']) : null,
            $thongSo['camera'] ?? null,
            isset($thongSo['dungLuongPin']) && $thongSo['dungLuongPin'] !== '' ? max(1, (int)$thongSo['dungLuongPin']) : null,
            $thongSo['heDieuHanh'] ?? null,
            $maDienThoai,
        ]);
    }

    public static function xoaTheoDienThoai(int $maDienThoai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("DELETE FROM ThongSoKyThuat WHERE maDienThoai = ?")->execute([$maDienThoai]);
    }

}
