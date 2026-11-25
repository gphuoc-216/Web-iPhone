<?php
// Entity BCE: DanhMuc (Bảng DanhMuc)

class DanhMuc
{
    public const TEN_BANG = 'DanhMuc';
    public const KHOA_CHINH = 'maDanhMuc';

    public ?int $maDanhMuc = null;
    public string $ten = '';
    public ?string $moTa = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maDanhMuc', $duLieu)) {
            $this->maDanhMuc = isset($duLieu['maDanhMuc']) && $duLieu['maDanhMuc'] !== '' ? (int)$duLieu['maDanhMuc'] : null;
        }
        if (array_key_exists('ten', $duLieu)) {
            $this->ten = (string)($duLieu['ten'] ?? '');
        }
        if (array_key_exists('moTa', $duLieu)) {
            $this->moTa = isset($duLieu['moTa']) && $duLieu['moTa'] !== '' ? (string)$duLieu['moTa'] : null;
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maDanhMuc' => $this->maDanhMuc,
            'ten' => $this->ten,
            'moTa' => $this->moTa,
        ];
    }
}
