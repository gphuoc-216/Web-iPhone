<?php
// Entity BCE: YeuCauHuyDon (Bảng YeuCauHuyDon)

class YeuCauHuyDon
{
    public const TEN_BANG = 'YeuCauHuyDon';
    public const KHOA_CHINH = 'maYeuCau';

    public ?int $maYeuCau = null;
    public ?int $maDonHang = null;
    public string $lyDo = '';
    public ?string $ngayGui = null;
    public string $trangThai = 'Chờ xử lý';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maYeuCau', $duLieu)) {
            $this->maYeuCau = isset($duLieu['maYeuCau']) && $duLieu['maYeuCau'] !== '' ? (int)$duLieu['maYeuCau'] : null;
        }
        if (array_key_exists('maDonHang', $duLieu)) {
            $this->maDonHang = isset($duLieu['maDonHang']) && $duLieu['maDonHang'] !== '' ? (int)$duLieu['maDonHang'] : null;
        }
        if (array_key_exists('lyDo', $duLieu)) {
            $this->lyDo = (string)($duLieu['lyDo'] ?? '');
        }
        if (array_key_exists('ngayGui', $duLieu)) {
            $this->ngayGui = isset($duLieu['ngayGui']) && $duLieu['ngayGui'] !== '' ? (string)$duLieu['ngayGui'] : null;
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
            'maYeuCau' => $this->maYeuCau,
            'maDonHang' => $this->maDonHang,
            'lyDo' => $this->lyDo,
            'ngayGui' => $this->ngayGui,
            'trangThai' => $this->trangThai,
        ];
    }
}
