<?php
// Entity BCE: ThongSoKyThuat — Bảng ThongSoKyThuat (Mục 3.4.3.27: Bảng 129, Bảng 130; RDM: Bảng 167)

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
            'maThongSo'    => $this->maThongSo,
            'maDienThoai'  => $this->maDienThoai,
            'manHinh'      => $this->manHinh,
            'chip'         => $this->chip,
            'ram'          => $this->ram,
            'rom'          => $this->rom,
            'camera'       => $this->camera,
            'dungLuongPin' => $this->dungLuongPin,
            'heDieuHanh'   => $this->heDieuHanh,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC5, UC9 - Hình 13, Hình 17] ThongSoKyThuat::timTheoDienThoai(maDienThoai) — Bảng 130 STT 4
    public static function timTheoDienThoai(int $maDienThoai, ?PDO $pdo = null): ?self
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM ThongSoKyThuat WHERE maDienThoai = ? LIMIT 1");
        $stmt->execute([$maDienThoai]);
        $row = $stmt->fetch();
        return $row ? self::tuMang($row) : null;
    }

    // [UC8 - Hình 16] ThongSoKyThuat::locTheoThongSo(ram, rom, dungLuongPin) — Bảng 130 STT 2, Bảng 231 dòng 7
    public static function locTheoThongSo(?int $ram, ?int $rom, ?int $dungLuongPin, ?PDO $pdo = null): array
    {
        $dk = ['1=1'];
        $ts = [];
        if ($ram !== null && $ram > 0) { $dk[] = "ram = ?"; $ts[] = $ram; }
        if ($rom !== null && $rom > 0) { $dk[] = "rom = ?"; $ts[] = $rom; }
        if ($dungLuongPin !== null && $dungLuongPin > 0) { $dk[] = "dungLuongPin >= ?"; $ts[] = $dungLuongPin; }
        $stmt = self::pdo($pdo)->prepare("SELECT maDienThoai FROM ThongSoKyThuat WHERE " . implode(' AND ', $dk));
        $stmt->execute($ts);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // [UC4 - Hình 12] ThongSoKyThuat::luu(maDienThoai, thongSo) — Bảng 130 STT 3, Bảng 231 dòng 4
    public static function luu(int $maDienThoai, $thongSo, ?PDO $pdo = null): int
    {
        $ts = ($thongSo instanceof self) ? $thongSo->chuyenThanhMang() : (is_array($thongSo) ? $thongSo : []);
        $db = self::pdo($pdo);
        $stmt = $db->prepare(
            "INSERT INTO ThongSoKyThuat (maDienThoai, manHinh, chip, ram, rom, camera, dungLuongPin, heDieuHanh)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $maDienThoai,
            trim((string)($ts['manHinh'] ?? '')) !== '' ? trim((string)$ts['manHinh']) : '6.3 inches OLED',
            trim((string)($ts['chip'] ?? '')) !== '' ? trim((string)$ts['chip']) : 'Đang cập nhật',
            max(1, (int)($ts['ram'] ?? 8)),
            max(1, (int)($ts['rom'] ?? 128)),
            trim((string)($ts['camera'] ?? '')) !== '' ? trim((string)$ts['camera']) : '48MP',
            max(1, (int)($ts['dungLuongPin'] ?? 4500)),
            trim((string)($ts['heDieuHanh'] ?? '')) !== '' ? trim((string)$ts['heDieuHanh']) : 'iOS / Android',
        ]);
        return (int)$db->lastInsertId();
    }

    // [UC5 - Hình 13] ThongSoKyThuat::capNhat(maDienThoai, thongSo) — Bảng 130 STT 1, Bảng 231 dòng 5
    public static function capNhat(int $maDienThoai, $thongSo, ?PDO $pdo = null): bool
    {
        $ts = ($thongSo instanceof self) ? $thongSo->chuyenThanhMang() : (is_array($thongSo) ? $thongSo : []);
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
            isset($ts['manHinh']) && trim((string)$ts['manHinh']) !== '' ? trim((string)$ts['manHinh']) : null,
            isset($ts['chip']) && trim((string)$ts['chip']) !== '' ? trim((string)$ts['chip']) : null,
            isset($ts['ram']) && $ts['ram'] !== '' ? max(1, (int)$ts['ram']) : null,
            isset($ts['rom']) && $ts['rom'] !== '' ? max(1, (int)$ts['rom']) : null,
            isset($ts['camera']) && trim((string)$ts['camera']) !== '' ? trim((string)$ts['camera']) : null,
            isset($ts['dungLuongPin']) && $ts['dungLuongPin'] !== '' ? max(1, (int)$ts['dungLuongPin']) : null,
            isset($ts['heDieuHanh']) && trim((string)$ts['heDieuHanh']) !== '' ? trim((string)$ts['heDieuHanh']) : null,
            $maDienThoai,
        ]);
    }
}
