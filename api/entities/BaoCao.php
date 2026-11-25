<?php
// Entity BCE: BaoCao (Thực thể tổng hợp tạm cho Thống kê - Báo cáo, chuẩn Lan04)

class BaoCao
{
    public const LA_THUC_THE_TAM = true;

    public string $loaiBaoCao = '';
    public ?string $tuNgay = null;
    public ?string $denNgay = null;
    public float $tongDoanhThu = 0.0;
    public float $tongGiaVon = 0.0;
    public float $tongLoiNhuan = 0.0;
    public int $tongSoDon = 0;
    public int $soDonThanhCong = 0;
    public int $soDonDaHuy = 0;
    public int $soDonDangXuLy = 0;
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
        if (array_key_exists('tongSoDon', $duLieu)) {
            $this->tongSoDon = (int)($duLieu['tongSoDon'] ?? 0);
        }
        if (array_key_exists('soDonThanhCong', $duLieu)) {
            $this->soDonThanhCong = (int)($duLieu['soDonThanhCong'] ?? 0);
        }
        if (array_key_exists('soDonDaHuy', $duLieu)) {
            $this->soDonDaHuy = (int)($duLieu['soDonDaHuy'] ?? 0);
        }
        if (array_key_exists('soDonDangXuLy', $duLieu)) {
            $this->soDonDangXuLy = (int)($duLieu['soDonDangXuLy'] ?? 0);
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
            'loaiBaoCao'     => $this->loaiBaoCao,
            'tuNgay'         => $this->tuNgay,
            'denNgay'        => $this->denNgay,
            'tongDoanhThu'   => $this->tongDoanhThu,
            'tongGiaVon'     => $this->tongGiaVon,
            'tongLoiNhuan'   => $this->tongLoiNhuan,
            'tongSoDon'      => $this->tongSoDon,
            'soDonThanhCong' => $this->soDonThanhCong,
            'soDonDaHuy'     => $this->soDonDaHuy,
            'soDonDangXuLy'  => $this->soDonDangXuLy,
            'chiTiet'        => $this->chiTiet,
            'thoiDiemTao'    => $this->thoiDiemTao,
        ];
    }
}
