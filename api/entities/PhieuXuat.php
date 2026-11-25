<?php
// Entity BCE: PhieuXuat (Bảng PhieuXuat)

class PhieuXuat
{
    public const TEN_BANG = 'PhieuXuat';
    public const KHOA_CHINH = 'maPhieuXuat';

    public ?int $maPhieuXuat = null;
    public ?int $maDonHang = null;
    public ?string $ngayLap = null;
    public ?string $ghiChu = null;
    public string $trangThai = 'Nháp';
    public ?int $maNhanVien = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maPhieuXuat', $duLieu)) {
            $this->maPhieuXuat = isset($duLieu['maPhieuXuat']) && $duLieu['maPhieuXuat'] !== '' ? (int)$duLieu['maPhieuXuat'] : null;
        }
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
        }
        if (array_key_exists('ngayLap', $duLieu)) {
            $this->ngayLap = isset($duLieu['ngayLap']) && $duLieu['ngayLap'] !== '' ? (string)$duLieu['ngayLap'] : null;
        }
        if (array_key_exists('ghiChu', $duLieu)) {
            $this->ghiChu = isset($duLieu['ghiChu']) && $duLieu['ghiChu'] !== '' ? (string)$duLieu['ghiChu'] : null;
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
        }
        if (array_key_exists('maNhanVien', $duLieu)) {
            $this->maNhanVien = isset($duLieu['maNhanVien']) && $duLieu['maNhanVien'] !== '' ? (int)$duLieu['maNhanVien'] : null;
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maPhieuXuat' => $this->maPhieuXuat,
            'maDonHang' => $this->maDonHang,
            'ngayLap' => $this->ngayLap,
            'ghiChu' => $this->ghiChu,
            'trangThai' => $this->trangThai,
            'maNhanVien' => $this->maNhanVien,
        ];
    }
}
