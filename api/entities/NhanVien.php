<?php
// Entity BCE: NhanVien (Bảng NhanVien)

class NhanVien
{
    public const TEN_BANG = 'NhanVien';
    public const KHOA_CHINH = 'maNhanVien';

    public ?int $maNhanVien = null;
    public ?int $maTaiKhoan = null;
    public string $hoTen = '';
    public ?string $soDienThoai = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maNhanVien', $duLieu)) {
            $this->maNhanVien = isset($duLieu['maNhanVien']) && $duLieu['maNhanVien'] !== '' ? (int)$duLieu['maNhanVien'] : null;
        }
        if (array_key_exists('maTaiKhoan', $duLieu)) {
            $this->maTaiKhoan = isset($duLieu['maTaiKhoan']) && $duLieu['maTaiKhoan'] !== '' ? (int)$duLieu['maTaiKhoan'] : null;
        }
        if (array_key_exists('hoTen', $duLieu)) {
            $this->hoTen = (string)($duLieu['hoTen'] ?? '');
        }
        if (array_key_exists('soDienThoai', $duLieu)) {
            $this->soDienThoai = isset($duLieu['soDienThoai']) && $duLieu['soDienThoai'] !== '' ? (string)$duLieu['soDienThoai'] : null;
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maNhanVien' => $this->maNhanVien,
            'maTaiKhoan' => $this->maTaiKhoan,
            'hoTen' => $this->hoTen,
            'soDienThoai' => $this->soDienThoai,
        ];
    }
}
