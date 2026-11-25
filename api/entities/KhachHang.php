<?php
// Entity BCE: KhachHang (Bảng KhachHang)

class KhachHang
{
    public const TEN_BANG = 'KhachHang';
    public const KHOA_CHINH = 'maKhachHang';

    public ?int $maKhachHang = null;
    public ?int $maTaiKhoan = null;
    public string $hoTen = '';
    public ?string $soDienThoai = null;
    public ?string $email = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maKhachHang', $duLieu)) {
            $this->maKhachHang = isset($duLieu['maKhachHang']) && $duLieu['maKhachHang'] !== '' ? (int)$duLieu['maKhachHang'] : null;
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
        if (array_key_exists('email', $duLieu)) {
            $this->email = isset($duLieu['email']) && $duLieu['email'] !== '' ? (string)$duLieu['email'] : null;
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maKhachHang' => $this->maKhachHang,
            'maTaiKhoan' => $this->maTaiKhoan,
            'hoTen' => $this->hoTen,
            'soDienThoai' => $this->soDienThoai,
            'email' => $this->email,
        ];
    }
}
