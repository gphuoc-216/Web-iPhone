<?php
// Entity BCE: ThongBao (Bảng ThongBao)

class ThongBao
{
    public const TEN_BANG = 'ThongBao';
    public const KHOA_CHINH = 'maThongBao';

    public ?int $maThongBao = null;
    public ?int $maNguoiNhan = null;
    public string $noiDung = '';
    public ?string $thoiDiemTao = null;
    public bool $daDoc = false;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maThongBao', $duLieu)) {
            $this->maThongBao = isset($duLieu['maThongBao']) && $duLieu['maThongBao'] !== '' ? (int)$duLieu['maThongBao'] : null;
        }
        if (array_key_exists('maNguoiNhan', $duLieu)) {
            $this->maNguoiNhan = isset($duLieu['maNguoiNhan']) && $duLieu['maNguoiNhan'] !== '' ? (int)$duLieu['maNguoiNhan'] : null;
        }
        if (array_key_exists('noiDung', $duLieu)) {
            $this->noiDung = (string)($duLieu['noiDung'] ?? '');
        }
        if (array_key_exists('thoiDiemTao', $duLieu)) {
            $this->thoiDiemTao = isset($duLieu['thoiDiemTao']) && $duLieu['thoiDiemTao'] !== '' ? (string)$duLieu['thoiDiemTao'] : null;
        }
        if (array_key_exists('daDoc', $duLieu)) {
            $this->daDoc = (bool)($duLieu['daDoc'] ?? false);
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maThongBao' => $this->maThongBao,
            'maNguoiNhan' => $this->maNguoiNhan,
            'noiDung' => $this->noiDung,
            'thoiDiemTao' => $this->thoiDiemTao,
            'daDoc' => $this->daDoc,
        ];
    }
}
