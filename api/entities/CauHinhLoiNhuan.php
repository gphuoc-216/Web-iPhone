<?php
// Entity BCE: CauHinhLoiNhuan (Bảng CauHinhLoiNhuan)

class CauHinhLoiNhuan
{
    public const TEN_BANG = 'CauHinhLoiNhuan';
    public const KHOA_CHINH = 'maCauHinh';

    public ?int $maCauHinh = null;
    public string $phamVi = 'Toàn bộ sản phẩm';
    public float $tyLe = 0.0;
    public ?string $ngayApDung = null;
    public ?string $ghiChu = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maCauHinh', $duLieu)) {
            $this->maCauHinh = isset($duLieu['maCauHinh']) && $duLieu['maCauHinh'] !== '' ? (int)$duLieu['maCauHinh'] : null;
        }
        if (array_key_exists('phamVi', $duLieu)) {
            $this->phamVi = (string)($duLieu['phamVi'] ?? '');
        }
        if (array_key_exists('tyLe', $duLieu)) {
            $this->tyLe = (float)($duLieu['tyLe'] ?? 0.0);
        }
        if (array_key_exists('ngayApDung', $duLieu)) {
            $this->ngayApDung = isset($duLieu['ngayApDung']) && $duLieu['ngayApDung'] !== '' ? (string)$duLieu['ngayApDung'] : null;
        }
        if (array_key_exists('ghiChu', $duLieu)) {
            $this->ghiChu = isset($duLieu['ghiChu']) && $duLieu['ghiChu'] !== '' ? (string)$duLieu['ghiChu'] : null;
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maCauHinh' => $this->maCauHinh,
            'phamVi' => $this->phamVi,
            'tyLe' => $this->tyLe,
            'ngayApDung' => $this->ngayApDung,
            'ghiChu' => $this->ghiChu,
        ];
    }
}
