<?php
// Entity BCE: TaiKhoan (Bảng TaiKhoan)

class TaiKhoan
{
    public const TEN_BANG = 'TaiKhoan';
    public const KHOA_CHINH = 'maTaiKhoan';

    public ?int $maTaiKhoan = null;
    public string $dinhDanh = '';
    public string $matKhauDaBam = '';
    public string $vaiTro = 'Khách hàng';
    public string $trangThai = 'Hoạt động';
    public ?string $lyDoKhoa = null;
    public ?string $maXacThuc = null;
    public ?string $hanMaXacThuc = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maTaiKhoan', $duLieu)) {
            $this->maTaiKhoan = isset($duLieu['maTaiKhoan']) && $duLieu['maTaiKhoan'] !== '' ? (int)$duLieu['maTaiKhoan'] : null;
        }
        if (array_key_exists('dinhDanh', $duLieu)) {
            $this->dinhDanh = (string)($duLieu['dinhDanh'] ?? '');
        }
        if (array_key_exists('matKhauDaBam', $duLieu)) {
            $this->matKhauDaBam = (string)($duLieu['matKhauDaBam'] ?? '');
        }
        if (array_key_exists('vaiTro', $duLieu)) {
            $this->vaiTro = (string)($duLieu['vaiTro'] ?? '');
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
        }
        if (array_key_exists('lyDoKhoa', $duLieu)) {
            $this->lyDoKhoa = isset($duLieu['lyDoKhoa']) && $duLieu['lyDoKhoa'] !== '' ? (string)$duLieu['lyDoKhoa'] : null;
        }
        if (array_key_exists('maXacThuc', $duLieu)) {
            $this->maXacThuc = isset($duLieu['maXacThuc']) && $duLieu['maXacThuc'] !== '' ? (string)$duLieu['maXacThuc'] : null;
        }
        if (array_key_exists('hanMaXacThuc', $duLieu)) {
            $this->hanMaXacThuc = isset($duLieu['hanMaXacThuc']) && $duLieu['hanMaXacThuc'] !== '' ? (string)$duLieu['hanMaXacThuc'] : null;
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maTaiKhoan' => $this->maTaiKhoan,
            'dinhDanh' => $this->dinhDanh,
            'matKhauDaBam' => $this->matKhauDaBam,
            'vaiTro' => $this->vaiTro,
            'trangThai' => $this->trangThai,
            'lyDoKhoa' => $this->lyDoKhoa,
            'maXacThuc' => $this->maXacThuc,
            'hanMaXacThuc' => $this->hanMaXacThuc,
        ];
    }
    public function dangHoatDong(): bool
    {
        return $this->trangThai === 'Hoạt động';
    }

    public function chuyenThanhMangAnToan(): array
    {
        $mang = $this->chuyenThanhMang();
        unset($mang['matKhauDaBam'], $mang['maXacThuc'], $mang['hanMaXacThuc']);
        return $mang;
    }
}
