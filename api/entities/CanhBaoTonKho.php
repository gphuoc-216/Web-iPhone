<?php
// Entity BCE: CanhBaoTonKho (Bảng CanhBaoTonKho)

class CanhBaoTonKho
{
    public const TEN_BANG = 'CanhBaoTonKho';
    public const KHOA_CHINH = 'maNguong';

    public ?int $maNguong = null;
    public string $phamVi = 'Toàn bộ sản phẩm';
    public int $nguongSom = 15;
    public int $nguongToiThieu = 5;
    public int $nguongToiDa = 350;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maNguong', $duLieu)) {
            $this->maNguong = isset($duLieu['maNguong']) && $duLieu['maNguong'] !== '' ? (int)$duLieu['maNguong'] : null;
        }
        if (array_key_exists('phamVi', $duLieu)) {
            $this->phamVi = (string)($duLieu['phamVi'] ?? '');
        }
        if (array_key_exists('nguongSom', $duLieu)) {
            $this->nguongSom = (int)($duLieu['nguongSom'] ?? 0);
        }
        if (array_key_exists('nguongToiThieu', $duLieu)) {
            $this->nguongToiThieu = (int)($duLieu['nguongToiThieu'] ?? 0);
        }
        if (array_key_exists('nguongToiDa', $duLieu)) {
            $this->nguongToiDa = (int)($duLieu['nguongToiDa'] ?? 0);
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maNguong' => $this->maNguong,
            'phamVi' => $this->phamVi,
            'nguongSom' => $this->nguongSom,
            'nguongToiThieu' => $this->nguongToiThieu,
            'nguongToiDa' => $this->nguongToiDa,
        ];
    }
}
