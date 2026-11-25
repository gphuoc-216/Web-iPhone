<?php
// Entity BCE: ChiTietKiemKe (Bảng ChiTietKiemKe)

class ChiTietKiemKe
{
    public const TEN_BANG = 'ChiTietKiemKe';
    public const KHOA_CHINH = 'maChiTiet';

    public ?int $maChiTiet = null;
    public ?int $maBienBan = null;
    public ?int $maDienThoai = null;
    public int $soLuongHeThong = 0;
    public int $soLuongThucTe = 0;
    public int $chenhLech = 0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maChiTiet', $duLieu)) {
            $this->maChiTiet = isset($duLieu['maChiTiet']) && $duLieu['maChiTiet'] !== '' ? (int)$duLieu['maChiTiet'] : null;
        }
        if (array_key_exists('maBienBan', $duLieu)) {
            $this->maBienBan = isset($duLieu['maBienBan']) && $duLieu['maBienBan'] !== '' ? (int)$duLieu['maBienBan'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('soLuongHeThong', $duLieu)) {
            $this->soLuongHeThong = (int)($duLieu['soLuongHeThong'] ?? 0);
        }
        if (array_key_exists('soLuongThucTe', $duLieu)) {
            $this->soLuongThucTe = (int)($duLieu['soLuongThucTe'] ?? 0);
        }
        if (array_key_exists('chenhLech', $duLieu)) {
            $this->chenhLech = (int)($duLieu['chenhLech'] ?? 0);
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maChiTiet' => $this->maChiTiet,
            'maBienBan' => $this->maBienBan,
            'maDienThoai' => $this->maDienThoai,
            'soLuongHeThong' => $this->soLuongHeThong,
            'soLuongThucTe' => $this->soLuongThucTe,
            'chenhLech' => $this->chenhLech,
        ];
    }
    public function tinhChenhLech(): int
    {
        $this->chenhLech = $this->soLuongThucTe - $this->soLuongHeThong;
        return $this->chenhLech;
    }
}
