<?php
// Entity BCE: PhieuNhap (Bảng PhieuNhap)

class PhieuNhap
{
    public const TEN_BANG = 'PhieuNhap';
    public const KHOA_CHINH = 'maPhieuNhap';

    public ?int $maPhieuNhap = null;
    public string $nguonNhap = '';
    public ?string $ngayNhap = null;
    public string $trangThai = 'Nháp';
    public ?int $maNhanVien = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maPhieuNhap', $duLieu)) {
            $this->maPhieuNhap = isset($duLieu['maPhieuNhap']) && $duLieu['maPhieuNhap'] !== '' ? (int)$duLieu['maPhieuNhap'] : null;
        }
        if (array_key_exists('nguonNhap', $duLieu)) {
            $this->nguonNhap = (string)($duLieu['nguonNhap'] ?? '');
        }
        if (array_key_exists('ngayNhap', $duLieu)) {
            $this->ngayNhap = isset($duLieu['ngayNhap']) && $duLieu['ngayNhap'] !== '' ? (string)$duLieu['ngayNhap'] : null;
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
            'maPhieuNhap' => $this->maPhieuNhap,
            'nguonNhap' => $this->nguonNhap,
            'ngayNhap' => $this->ngayNhap,
            'trangThai' => $this->trangThai,
            'maNhanVien' => $this->maNhanVien,
        ];
    }
}
