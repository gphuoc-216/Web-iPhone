<?php
// Entity BCE: TonKho (Bảng TonKho)

class TonKho
{
    public const TEN_BANG = 'TonKho';
    public const KHOA_CHINH = 'maTonKho';

    public ?int $maTonKho = null;
    public ?int $maDienThoai = null;
    public int $maKho = 1;
    public int $tonThucTe = 0;
    public int $giuCho = 0;
    public int $tonKhaDung = 0;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maTonKho', $duLieu)) {
            $this->maTonKho = isset($duLieu['maTonKho']) && $duLieu['maTonKho'] !== '' ? (int)$duLieu['maTonKho'] : null;
        }
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('maKho', $duLieu)) {
            $this->maKho = (int)($duLieu['maKho'] ?? 0);
        }
        if (array_key_exists('tonThucTe', $duLieu)) {
            $this->tonThucTe = (int)($duLieu['tonThucTe'] ?? 0);
        }
        if (array_key_exists('giuCho', $duLieu)) {
            $this->giuCho = (int)($duLieu['giuCho'] ?? 0);
        }
        if (array_key_exists('tonKhaDung', $duLieu)) {
            $this->tonKhaDung = (int)($duLieu['tonKhaDung'] ?? 0);
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maTonKho' => $this->maTonKho,
            'maDienThoai' => $this->maDienThoai,
            'maKho' => $this->maKho,
            'tonThucTe' => $this->tonThucTe,
            'giuCho' => $this->giuCho,
            'tonKhaDung' => $this->tonKhaDung,
        ];
    }
    public function capNhatTonKhaDung(): int
    {
        $this->tonKhaDung = $this->tonThucTe - $this->giuCho;
        return $this->tonKhaDung;
    }
}
