<?php
// Entity BCE: GioHang (Bảng GioHang)

class GioHang
{
    public const TEN_BANG = 'GioHang';
    public const KHOA_CHINH = 'maGioHang';

    public ?int $maGioHang = null;
    public ?int $maKhachHang = null;
    public string $maPhien = '';
    public ?string $maVoucher = null;
    public float $tongTamTinh = 0.0;
    public float $tienGiamGia = 0.0;
    public float $tongTien = 0.0;
    public string $trangThai = 'Đang dùng';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maGioHang', $duLieu)) {
            $this->maGioHang = isset($duLieu['maGioHang']) && $duLieu['maGioHang'] !== '' ? (int)$duLieu['maGioHang'] : null;
        }
        if (array_key_exists('maKhachHang', $duLieu)) {
            $this->maKhachHang = isset($duLieu['maKhachHang']) && $duLieu['maKhachHang'] !== '' ? (int)$duLieu['maKhachHang'] : null;
        }
        if (array_key_exists('maPhien', $duLieu)) {
            $this->maPhien = (string)($duLieu['maPhien'] ?? '');
        }
        if (array_key_exists('maVoucher', $duLieu)) {
            $this->maVoucher = isset($duLieu['maVoucher']) && $duLieu['maVoucher'] !== '' ? (string)$duLieu['maVoucher'] : null;
        }
        if (array_key_exists('tongTamTinh', $duLieu)) {
            $this->tongTamTinh = (float)($duLieu['tongTamTinh'] ?? 0.0);
        }
        if (array_key_exists('tienGiamGia', $duLieu)) {
            $this->tienGiamGia = (float)($duLieu['tienGiamGia'] ?? 0.0);
        }
        if (array_key_exists('tongTien', $duLieu)) {
            $this->tongTien = (float)($duLieu['tongTien'] ?? 0.0);
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
            'maGioHang' => $this->maGioHang,
            'maKhachHang' => $this->maKhachHang,
            'maPhien' => $this->maPhien,
            'maVoucher' => $this->maVoucher,
            'tongTamTinh' => $this->tongTamTinh,
            'tienGiamGia' => $this->tienGiamGia,
            'tongTien' => $this->tongTien,
            'trangThai' => $this->trangThai,
        ];
    }
    public function tinhLaiTongTien(): float
    {
        $this->tongTien = max(0.0, $this->tongTamTinh - $this->tienGiamGia);
        return $this->tongTien;
    }
}
