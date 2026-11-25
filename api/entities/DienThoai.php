<?php
// Entity BCE: DienThoai (Bảng DienThoai)

class DienThoai
{
    public const TEN_BANG = 'DienThoai';
    public const KHOA_CHINH = 'maDienThoai';

    public ?int $maDienThoai = null;
    public string $ten = '';
    public ?int $maDanhMuc = null;
    public string $hang = '';
    public ?string $moTa = null;
    public ?string $hinhAnh = null;
    public float $giaVonBinhQuan = 0.0;
    public float $giaBan = 0.0;
    public string $trangThai = 'Đang kinh doanh';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('ten', $duLieu)) {
            $this->ten = (string)($duLieu['ten'] ?? '');
        }
        if (array_key_exists('maDanhMuc', $duLieu)) {
            $this->maDanhMuc = isset($duLieu['maDanhMuc']) && $duLieu['maDanhMuc'] !== '' ? (int)$duLieu['maDanhMuc'] : null;
        }
        if (array_key_exists('hang', $duLieu)) {
            $this->hang = (string)($duLieu['hang'] ?? '');
        }
        if (array_key_exists('moTa', $duLieu)) {
            $this->moTa = isset($duLieu['moTa']) && $duLieu['moTa'] !== '' ? (string)$duLieu['moTa'] : null;
        }
        if (array_key_exists('hinhAnh', $duLieu)) {
            $this->hinhAnh = isset($duLieu['hinhAnh']) && $duLieu['hinhAnh'] !== '' ? (string)$duLieu['hinhAnh'] : null;
        }
        if (array_key_exists('giaVonBinhQuan', $duLieu)) {
            $this->giaVonBinhQuan = (float)($duLieu['giaVonBinhQuan'] ?? 0.0);
        }
        if (array_key_exists('giaBan', $duLieu)) {
            $this->giaBan = (float)($duLieu['giaBan'] ?? 0.0);
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maDienThoai' => $this->maDienThoai,
            'ten' => $this->ten,
            'maDanhMuc' => $this->maDanhMuc,
            'hang' => $this->hang,
            'moTa' => $this->moTa,
            'hinhAnh' => $this->hinhAnh,
            'giaVonBinhQuan' => $this->giaVonBinhQuan,
            'giaBan' => $this->giaBan,
            'trangThai' => $this->trangThai,
        ];
    }
    public function tinhGiaBanTheoTyLe(float $tyLeLoiNhuan): float
    {
        return round($this->giaVonBinhQuan * (1 + $tyLeLoiNhuan / 100), 2);
    }
}
