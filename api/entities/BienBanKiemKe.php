<?php
// Entity BCE: BienBanKiemKe (Bảng BienBanKiemKe)

class BienBanKiemKe
{
    public const TEN_BANG = 'BienBanKiemKe';
    public const KHOA_CHINH = 'maBienBan';

    public ?int $maBienBan = null;
    public int $maKho = 1;
    public ?string $thoiDiem = null;
    public ?string $ghiChu = null;
    public string $trangThai = 'Nháp';
    public ?int $maNhanVien = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maBienBan', $duLieu)) {
            $this->maBienBan = isset($duLieu['maBienBan']) && $duLieu['maBienBan'] !== '' ? (int)$duLieu['maBienBan'] : null;
        }
        if (array_key_exists('maKho', $duLieu)) {
            $this->maKho = (int)($duLieu['maKho'] ?? 0);
        }
        if (array_key_exists('thoiDiem', $duLieu)) {
            $this->thoiDiem = isset($duLieu['thoiDiem']) && $duLieu['thoiDiem'] !== '' ? (string)$duLieu['thoiDiem'] : null;
        }
        if (array_key_exists('ghiChu', $duLieu)) {
            $this->ghiChu = isset($duLieu['ghiChu']) && $duLieu['ghiChu'] !== '' ? (string)$duLieu['ghiChu'] : null;
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
        }
        if (array_key_exists('maNhanVien', $duLieu)) {
            $this->maNhanVien = isset($duLieu['maNhanVien']) && $duLieu['maNhanVien'] !== '' ? (int)$duLieu['maNhanVien'] : null;
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maBienBan' => $this->maBienBan,
            'maKho' => $this->maKho,
            'thoiDiem' => $this->thoiDiem,
            'ghiChu' => $this->ghiChu,
            'trangThai' => $this->trangThai,
            'maNhanVien' => $this->maNhanVien,
        ];
    }
}
