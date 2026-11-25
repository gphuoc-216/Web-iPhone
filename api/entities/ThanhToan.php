<?php
// Entity BCE: ThanhToan (Bảng ThanhToan)

class ThanhToan
{
    public const TEN_BANG = 'ThanhToan';
    public const KHOA_CHINH = 'maThanhToan';

    public ?int $maThanhToan = null;
    public ?int $maGioHang = null;
    public ?int $maDonHang = null;
    public string $phuongThuc = 'Thanh toán khi nhận hàng';
    public float $soTien = 0.0;
    public float $soTienHoan = 0.0;
    public string $trangThai = 'Chờ thanh toán';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maThanhToan', $duLieu)) {
            $this->maThanhToan = isset($duLieu['maThanhToan']) && $duLieu['maThanhToan'] !== '' ? (int)$duLieu['maThanhToan'] : null;
        }
        if (array_key_exists('maGioHang', $duLieu)) {
            $this->maGioHang = isset($duLieu['maGioHang']) && $duLieu['maGioHang'] !== '' ? (int)$duLieu['maGioHang'] : null;
        }
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
        }
        if (array_key_exists('phuongThuc', $duLieu)) {
            $this->phuongThuc = (string)($duLieu['phuongThuc'] ?? '');
        }
        if (array_key_exists('soTien', $duLieu)) {
            $this->soTien = (float)($duLieu['soTien'] ?? 0.0);
        }
        if (array_key_exists('soTienHoan', $duLieu)) {
            $this->soTienHoan = (float)($duLieu['soTienHoan'] ?? 0.0);
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maThanhToan' => $this->maThanhToan,
            'maGioHang' => $this->maGioHang,
            'maDonHang' => $this->maDonHang,
            'phuongThuc' => $this->phuongThuc,
            'soTien' => $this->soTien,
            'soTienHoan' => $this->soTienHoan,
            'trangThai' => $this->trangThai,
        ];
    }
}
