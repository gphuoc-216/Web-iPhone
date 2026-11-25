<?php
// Entity BCE: ThongSoKyThuat (Bảng ThongSoKyThuat)

class ThongSoKyThuat
{
    public const TEN_BANG = 'ThongSoKyThuat';
    public const KHOA_CHINH = 'maThongSo';

    public ?int $maThongSo = null;
    public ?int $maDienThoai = null;
    public ?string $manHinh = null;
    public ?string $chip = null;
    public int $ram = 0;
    public int $rom = 0;
    public ?string $camera = null;
    public int $dungLuongPin = 0;
    public ?string $heDieuHanh = null;

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
}
