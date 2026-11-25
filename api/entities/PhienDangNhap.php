<?php
// Entity BCE: PhienDangNhap (Bảng PhienDangNhap)

class PhienDangNhap
{
    public const TEN_BANG = 'PhienDangNhap';
    public const KHOA_CHINH = 'maPhien';

    public string $maPhien = '';
    public ?int $maTaiKhoan = null;
    public ?string $thoiDiemTao = null;
    public ?string $thoiDiemHetHan = null;
    public string $trangThai = 'Còn hiệu lực';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maPhien', $duLieu)) {
            $this->maPhien = (string)($duLieu['maPhien'] ?? '');
        }
        if (array_key_exists('maTaiKhoan', $duLieu)) {
            $this->maTaiKhoan = isset($duLieu['maTaiKhoan']) && $duLieu['maTaiKhoan'] !== '' ? (int)$duLieu['maTaiKhoan'] : null;
        }
        if (array_key_exists('thoiDiemTao', $duLieu)) {
            $this->thoiDiemTao = isset($duLieu['thoiDiemTao']) && $duLieu['thoiDiemTao'] !== '' ? (string)$duLieu['thoiDiemTao'] : null;
        }
        if (array_key_exists('thoiDiemHetHan', $duLieu)) {
            $this->thoiDiemHetHan = isset($duLieu['thoiDiemHetHan']) && $duLieu['thoiDiemHetHan'] !== '' ? (string)$duLieu['thoiDiemHetHan'] : null;
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
            'maPhien' => $this->maPhien,
            'maTaiKhoan' => $this->maTaiKhoan,
            'thoiDiemTao' => $this->thoiDiemTao,
            'thoiDiemHetHan' => $this->thoiDiemHetHan,
            'trangThai' => $this->trangThai,
        ];
    }
    public function conHieuLuc(): bool
    {
        if ($this->trangThai !== 'Còn hiệu lực' || !$this->thoiDiemHetHan) {
            return false;
        }
        return strtotime($this->thoiDiemHetHan) > time();
    }
}
