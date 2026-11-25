<?php
// Entity BCE: DiaChiGiaoHang (Bảng DiaChiGiaoHang)

class DiaChiGiaoHang
{
    public const TEN_BANG = 'DiaChiGiaoHang';
    public const KHOA_CHINH = 'maDiaChi';

    public ?int $maDiaChi = null;
    public ?int $maKhachHang = null;
    public string $nguoiNhan = '';
    public string $soDienThoai = '';
    public string $diaChi = '';
    public ?int $maQuyTac = null;
    public bool $macDinh = false;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maDiaChi', $duLieu)) {
            $this->maDiaChi = isset($duLieu['maDiaChi']) && $duLieu['maDiaChi'] !== '' ? (int)$duLieu['maDiaChi'] : null;
        }
        if (array_key_exists('maKhachHang', $duLieu)) {
            $this->maKhachHang = isset($duLieu['maKhachHang']) && $duLieu['maKhachHang'] !== '' ? (int)$duLieu['maKhachHang'] : null;
        }
        if (array_key_exists('nguoiNhan', $duLieu)) {
            $this->nguoiNhan = (string)($duLieu['nguoiNhan'] ?? '');
        }
        if (array_key_exists('soDienThoai', $duLieu)) {
            $this->soDienThoai = (string)($duLieu['soDienThoai'] ?? '');
        }
        if (array_key_exists('diaChi', $duLieu)) {
            $this->diaChi = (string)($duLieu['diaChi'] ?? '');
        }
        if (array_key_exists('maQuyTac', $duLieu)) {
            $this->maQuyTac = isset($duLieu['maQuyTac']) && $duLieu['maQuyTac'] !== '' ? (int)$duLieu['maQuyTac'] : null;
        }
        if (array_key_exists('macDinh', $duLieu)) {
            $this->macDinh = (bool)($duLieu['macDinh'] ?? false);
        }
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maDiaChi' => $this->maDiaChi,
            'maKhachHang' => $this->maKhachHang,
            'nguoiNhan' => $this->nguoiNhan,
            'soDienThoai' => $this->soDienThoai,
            'diaChi' => $this->diaChi,
            'maQuyTac' => $this->maQuyTac,
            'macDinh' => $this->macDinh,
        ];
    }
}
