<?php
// Entity BCE: QuyTacPhiVanChuyen (Bảng QuyTacPhiVanChuyen)

class QuyTacPhiVanChuyen
{
    public const TEN_BANG = 'QuyTacPhiVanChuyen';
    public const KHOA_CHINH = 'maQuyTac';

    public ?int $maQuyTac = null;
    public string $khuVuc = '';
    public float $mucPhi = 0.0;
    public float $mucPhiMacDinh = 35000.0;
    public float $nguongMienPhi = 0.0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maQuyTac', $duLieu)) {
            $this->maQuyTac = isset($duLieu['maQuyTac']) && $duLieu['maQuyTac'] !== '' ? (int)$duLieu['maQuyTac'] : null;
        }
        if (array_key_exists('khuVuc', $duLieu)) {
            $this->khuVuc = (string)($duLieu['khuVuc'] ?? '');
        }
        if (array_key_exists('mucPhi', $duLieu)) {
            $this->mucPhi = (float)($duLieu['mucPhi'] ?? 0.0);
        }
        if (array_key_exists('mucPhiMacDinh', $duLieu)) {
            $this->mucPhiMacDinh = (float)($duLieu['mucPhiMacDinh'] ?? 0.0);
        }
        if (array_key_exists('nguongMienPhi', $duLieu)) {
            $this->nguongMienPhi = (float)($duLieu['nguongMienPhi'] ?? 0.0);
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maQuyTac' => $this->maQuyTac,
            'khuVuc' => $this->khuVuc,
            'mucPhi' => $this->mucPhi,
            'mucPhiMacDinh' => $this->mucPhiMacDinh,
            'nguongMienPhi' => $this->nguongMienPhi,
        ];
    }
}
