<?php
// Entity BCE: LichSuCauHinhLoiNhuan (Bảng LichSuCauHinhLoiNhuan)

class LichSuCauHinhLoiNhuan
{
    public const TEN_BANG = 'LichSuCauHinhLoiNhuan';
    public const KHOA_CHINH = 'maLichSu';

    public ?int $maLichSu = null;
    public ?int $maCauHinh = null;
    public string $phamVi = '';
    public float $tyLe = 0.0;
    public ?string $ngayApDung = null;
    public ?string $thoiDiemGhi = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maLichSu', $duLieu)) {
            $this->maLichSu = isset($duLieu['maLichSu']) && $duLieu['maLichSu'] !== '' ? (int)$duLieu['maLichSu'] : null;
        }
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
        if (array_key_exists('thoiDiemGhi', $duLieu)) {
            $this->thoiDiemGhi = isset($duLieu['thoiDiemGhi']) && $duLieu['thoiDiemGhi'] !== '' ? (string)$duLieu['thoiDiemGhi'] : null;
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maLichSu' => $this->maLichSu,
            'maCauHinh' => $this->maCauHinh,
            'phamVi' => $this->phamVi,
            'tyLe' => $this->tyLe,
            'ngayApDung' => $this->ngayApDung,
            'thoiDiemGhi' => $this->thoiDiemGhi,
        ];
    }
}
