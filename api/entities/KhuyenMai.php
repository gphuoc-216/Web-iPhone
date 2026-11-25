<?php
// Entity BCE: KhuyenMai (Bảng KhuyenMai)

class KhuyenMai
{
    public const TEN_BANG = 'KhuyenMai';
    public const KHOA_CHINH = 'maKhuyenMai';

    public ?int $maKhuyenMai = null;
    public string $ten = '';
    public ?string $noiDung = null;
    public float $mucUuDai = 0.0;
    public ?string $ngayBatDau = null;
    public ?string $ngayKetThuc = null;
    public ?string $khungGio = null;
    public string $trangThai = 'Đang áp dụng';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maKhuyenMai', $duLieu)) {
            $this->maKhuyenMai = isset($duLieu['maKhuyenMai']) && $duLieu['maKhuyenMai'] !== '' ? (int)$duLieu['maKhuyenMai'] : null;
        }
        if (array_key_exists('ten', $duLieu)) {
            $this->ten = (string)($duLieu['ten'] ?? '');
        }
        if (array_key_exists('noiDung', $duLieu)) {
            $this->noiDung = isset($duLieu['noiDung']) && $duLieu['noiDung'] !== '' ? (string)$duLieu['noiDung'] : null;
        }
        if (array_key_exists('mucUuDai', $duLieu)) {
            $this->mucUuDai = (float)($duLieu['mucUuDai'] ?? 0.0);
        }
        if (array_key_exists('ngayBatDau', $duLieu)) {
            $this->ngayBatDau = isset($duLieu['ngayBatDau']) && $duLieu['ngayBatDau'] !== '' ? (string)$duLieu['ngayBatDau'] : null;
        }
        if (array_key_exists('ngayKetThuc', $duLieu)) {
            $this->ngayKetThuc = isset($duLieu['ngayKetThuc']) && $duLieu['ngayKetThuc'] !== '' ? (string)$duLieu['ngayKetThuc'] : null;
        }
        if (array_key_exists('khungGio', $duLieu)) {
            $this->khungGio = isset($duLieu['khungGio']) && $duLieu['khungGio'] !== '' ? (string)$duLieu['khungGio'] : null;
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
            'maKhuyenMai' => $this->maKhuyenMai,
            'ten' => $this->ten,
            'noiDung' => $this->noiDung,
            'mucUuDai' => $this->mucUuDai,
            'ngayBatDau' => $this->ngayBatDau,
            'ngayKetThuc' => $this->ngayKetThuc,
            'khungGio' => $this->khungGio,
            'trangThai' => $this->trangThai,
        ];
    }
}
