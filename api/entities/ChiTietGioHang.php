<?php
// Entity BCE: ChiTietGioHang (Bảng ChiTietGioHang)

class ChiTietGioHang
{
    public const TEN_BANG = 'ChiTietGioHang';
    public const KHOA_CHINH = 'maChiTietGioHang';

    public ?int $maChiTietGioHang = null;
    public ?int $maGioHang = null;
    public ?int $maDienThoai = null;
    public int $soLuong = 0;
    public float $donGia = 0.0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maChiTietGioHang', $duLieu)) {
            $this->maChiTietGioHang = isset($duLieu['maChiTietGioHang']) && $duLieu['maChiTietGioHang'] !== '' ? (int)$duLieu['maChiTietGioHang'] : null;
        }
        if (array_key_exists('maGioHang', $duLieu)) {
            $this->maGioHang = isset($duLieu['maGioHang']) && $duLieu['maGioHang'] !== '' ? (int)$duLieu['maGioHang'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('soLuong', $duLieu)) {
            $this->soLuong = (int)($duLieu['soLuong'] ?? 0);
        }
        if (array_key_exists('donGia', $duLieu)) {
            $this->donGia = (float)($duLieu['donGia'] ?? 0.0);
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maChiTietGioHang' => $this->maChiTietGioHang,
            'maGioHang' => $this->maGioHang,
            'maDienThoai' => $this->maDienThoai,
            'soLuong' => $this->soLuong,
            'donGia' => $this->donGia,
        ];
    }
}
