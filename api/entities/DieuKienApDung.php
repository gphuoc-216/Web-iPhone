<?php
// Entity BCE: DieuKienApDung (Bảng DieuKienApDung)

class DieuKienApDung
{
    public const TEN_BANG = 'DieuKienApDung';
    public const KHOA_CHINH = 'maDieuKien';

    public ?int $maDieuKien = null;
    public ?int $maKhuyenMai = null;
    public ?string $maVoucher = null;
    public float $giaTriToiThieu = 0.0;
    public ?string $nhomHang = null;
    public ?string $doiTuong = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maDieuKien', $duLieu)) {
            $this->maDieuKien = isset($duLieu['maDieuKien']) && $duLieu['maDieuKien'] !== '' ? (int)$duLieu['maDieuKien'] : null;
        }
        if (array_key_exists('maKhuyenMai', $duLieu)) {
            $this->maKhuyenMai = isset($duLieu['maKhuyenMai']) && $duLieu['maKhuyenMai'] !== '' ? (int)$duLieu['maKhuyenMai'] : null;
        }
        if (array_key_exists('maVoucher', $duLieu)) {
            $this->maVoucher = isset($duLieu['maVoucher']) && $duLieu['maVoucher'] !== '' ? (string)$duLieu['maVoucher'] : null;
        }
        if (array_key_exists('giaTriToiThieu', $duLieu)) {
            $this->giaTriToiThieu = (float)($duLieu['giaTriToiThieu'] ?? 0.0);
        }
        if (array_key_exists('nhomHang', $duLieu)) {
            $this->nhomHang = isset($duLieu['nhomHang']) && $duLieu['nhomHang'] !== '' ? (string)$duLieu['nhomHang'] : null;
        }
        if (array_key_exists('doiTuong', $duLieu)) {
            $this->doiTuong = isset($duLieu['doiTuong']) && $duLieu['doiTuong'] !== '' ? (string)$duLieu['doiTuong'] : null;
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maDieuKien' => $this->maDieuKien,
            'maKhuyenMai' => $this->maKhuyenMai,
            'maVoucher' => $this->maVoucher,
            'giaTriToiThieu' => $this->giaTriToiThieu,
            'nhomHang' => $this->nhomHang,
            'doiTuong' => $this->doiTuong,
        ];
    }
}
