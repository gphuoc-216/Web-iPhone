<?php
// Entity BCE: BaoCao (Thực thể tổng hợp tạm cho Thống kê - Báo cáo)

class BaoCao
{
    public const LA_THUC_THE_TAM = true;

    public string $loaiBaoCao = '';
    public ?string $tuNgay = null;
    public ?string $denNgay = null;
    public float $tongDoanhThu = 0.0;
    public float $tongGiaVon = 0.0;
    public float $tongLoiNhuan = 0.0;
    public int $tongSoDonHang = 0;
    public array $chiTiet = [];
    public ?string $thoiDiemTao = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('loaiBaoCao', $duLieu)) {
            $this->loaiBaoCao = (string)($duLieu['loaiBaoCao'] ?? '');
        }
        if (array_key_exists('tuNgay', $duLieu)) {
            $this->tuNgay = isset($duLieu['tuNgay']) && $duLieu['tuNgay'] !== '' ? (string)$duLieu['tuNgay'] : null;
        }
        if (array_key_exists('denNgay', $duLieu)) {
            $this->denNgay = isset($duLieu['denNgay']) && $duLieu['denNgay'] !== '' ? (string)$duLieu['denNgay'] : null;
        }
        if (array_key_exists('tongDoanhThu', $duLieu)) {
            $this->tongDoanhThu = (float)($duLieu['tongDoanhThu'] ?? 0.0);
        }
        if (array_key_exists('tongGiaVon', $duLieu)) {
            $this->tongGiaVon = (float)($duLieu['tongGiaVon'] ?? 0.0);
        }
        if (array_key_exists('tongLoiNhuan', $duLieu)) {
            $this->tongLoiNhuan = (float)($duLieu['tongLoiNhuan'] ?? 0.0);
        }
        if (array_key_exists('tongSoDonHang', $duLieu)) {
            $this->tongSoDonHang = (int)($duLieu['tongSoDonHang'] ?? 0);
        }
        if (array_key_exists('chiTiet', $duLieu)) {
            $this->chiTiet = is_array($duLieu['chiTiet'] ?? null) ? $duLieu['chiTiet'] : [];
        }
        if (array_key_exists('thoiDiemTao', $duLieu)) {
            $this->thoiDiemTao = isset($duLieu['thoiDiemTao']) && $duLieu['thoiDiemTao'] !== '' ? (string)$duLieu['thoiDiemTao'] : null;
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'loaiBaoCao' => $this->loaiBaoCao,
            'tuNgay' => $this->tuNgay,
            'denNgay' => $this->denNgay,
            'tongDoanhThu' => $this->tongDoanhThu,
            'tongGiaVon' => $this->tongGiaVon,
            'tongLoiNhuan' => $this->tongLoiNhuan,
            'tongSoDonHang' => $this->tongSoDonHang,
            'chiTiet' => $this->chiTiet,
            'thoiDiemTao' => $this->thoiDiemTao,
        ];
    }
}
