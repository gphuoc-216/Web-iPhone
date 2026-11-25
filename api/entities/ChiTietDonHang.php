<?php
// Entity BCE: ChiTietDonHang (Bảng ChiTietDonHang)

class ChiTietDonHang
{
    public const TEN_BANG = 'ChiTietDonHang';
    public const KHOA_CHINH = 'maChiTietDonHang';

    public ?int $maChiTietDonHang = null;
    public ?int $maDonHang = null;
    public ?int $maDienThoai = null;
    public int $soLuong = 0;
    public float $donGia = 0.0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maChiTietDonHang', $duLieu)) {
            $this->maChiTietDonHang = isset($duLieu['maChiTietDonHang']) && $duLieu['maChiTietDonHang'] !== '' ? (int)$duLieu['maChiTietDonHang'] : null;
        }
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
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
            'maChiTietDonHang' => $this->maChiTietDonHang,
            'maDonHang' => $this->maDonHang,
            'maDienThoai' => $this->maDienThoai,
            'soLuong' => $this->soLuong,
            'donGia' => $this->donGia,
        ];
    }
}
