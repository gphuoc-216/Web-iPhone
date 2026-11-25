<?php
// Entity BCE: ChiTietPhieuNhap (Bảng ChiTietPhieuNhap)

class ChiTietPhieuNhap
{
    public const TEN_BANG = 'ChiTietPhieuNhap';
    public const KHOA_CHINH = 'maChiTiet';

    public ?int $maChiTiet = null;
    public ?int $maPhieuNhap = null;
    public ?int $maDienThoai = null;
    public int $soLuong = 0;
    public float $donGiaNhap = 0.0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maChiTiet', $duLieu)) {
            $this->maChiTiet = isset($duLieu['maChiTiet']) && $duLieu['maChiTiet'] !== '' ? (int)$duLieu['maChiTiet'] : null;
        }
        if (array_key_exists('maPhieuNhap', $duLieu)) {
            $this->maPhieuNhap = isset($duLieu['maPhieuNhap']) && $duLieu['maPhieuNhap'] !== '' ? (int)$duLieu['maPhieuNhap'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('soLuong', $duLieu)) {
            $this->soLuong = (int)($duLieu['soLuong'] ?? 0);
        }
        if (array_key_exists('donGiaNhap', $duLieu)) {
            $this->donGiaNhap = (float)($duLieu['donGiaNhap'] ?? 0.0);
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
            'maPhieuNhap' => $this->maPhieuNhap,
            'maDienThoai' => $this->maDienThoai,
            'soLuong' => $this->soLuong,
            'donGiaNhap' => $this->donGiaNhap,
        ];
    }
}
