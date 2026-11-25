<?php
// Entity BCE: DonHang (Bảng DonHang)

class DonHang
{
    public const TEN_BANG = 'DonHang';
    public const KHOA_CHINH = 'maDonHang';

    public ?int $maDonHang = null;
    public ?int $maKhachHang = null;
    public ?int $maDiaChi = null;
    public ?string $maVoucher = null;
    public ?string $ngayDat = null;
    public float $phiVanChuyen = 0.0;
    public float $tongTien = 0.0;
    public string $trangThai = 'Chờ duyệt';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
        }
        if (array_key_exists('maKhachHang', $duLieu)) {
            $this->maKhachHang = isset($duLieu['maKhachHang']) && $duLieu['maKhachHang'] !== '' ? (int)$duLieu['maKhachHang'] : null;
        }
        if (array_key_exists('maDiaChi', $duLieu)) {
            $this->maDiaChi = isset($duLieu['maDiaChi']) && $duLieu['maDiaChi'] !== '' ? (int)$duLieu['maDiaChi'] : null;
        }
        if (array_key_exists('maVoucher', $duLieu)) {
            $this->maVoucher = isset($duLieu['maVoucher']) && $duLieu['maVoucher'] !== '' ? (string)$duLieu['maVoucher'] : null;
        }
        if (array_key_exists('ngayDat', $duLieu)) {
            $this->ngayDat = isset($duLieu['ngayDat']) && $duLieu['ngayDat'] !== '' ? (string)$duLieu['ngayDat'] : null;
        }
        if (array_key_exists('phiVanChuyen', $duLieu)) {
            $this->phiVanChuyen = (float)($duLieu['phiVanChuyen'] ?? 0.0);
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
            'maDonHang' => $this->maDonHang,
            'maKhachHang' => $this->maKhachHang,
            'maDiaChi' => $this->maDiaChi,
            'maVoucher' => $this->maVoucher,
            'ngayDat' => $this->ngayDat,
            'phiVanChuyen' => $this->phiVanChuyen,
            'tongTien' => $this->tongTien,
            'trangThai' => $this->trangThai,
        ];
    }
}
