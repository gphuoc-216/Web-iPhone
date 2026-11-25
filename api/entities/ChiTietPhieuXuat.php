<?php
// Entity BCE: ChiTietPhieuXuat (Bảng ChiTietPhieuXuat)

class ChiTietPhieuXuat
{
    public const TEN_BANG = 'ChiTietPhieuXuat';
    public const KHOA_CHINH = 'maChiTiet';

    public ?int $maChiTiet = null;
    public ?int $maPhieuXuat = null;
    public ?int $maDienThoai = null;
    public int $soLuong = 0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maChiTiet', $duLieu)) {
            $this->maChiTiet = isset($duLieu['maChiTiet']) && $duLieu['maChiTiet'] !== '' ? (int)$duLieu['maChiTiet'] : null;
        }
        if (array_key_exists('maPhieuXuat', $duLieu)) {
            $this->maPhieuXuat = isset($duLieu['maPhieuXuat']) && $duLieu['maPhieuXuat'] !== '' ? (int)$duLieu['maPhieuXuat'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('soLuong', $duLieu)) {
            $this->soLuong = (int)($duLieu['soLuong'] ?? 0);
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
            'maPhieuXuat' => $this->maPhieuXuat,
            'maDienThoai' => $this->maDienThoai,
            'soLuong' => $this->soLuong,
        ];
    }
}
