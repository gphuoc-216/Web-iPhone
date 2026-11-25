<?php
// Entity BCE: Voucher (Bảng Voucher)

class Voucher
{
    public const TEN_BANG = 'Voucher';
    public const KHOA_CHINH = 'maVoucher';

    public string $maVoucher = '';
    public string $loaiGiam = 'Số tiền';
    public float $giaTriGiam = 0.0;
    public int $soLuong = 0;
    public int $gioiHanSuDung = 1;
    public int $luotDaDung = 0;
    public ?string $ngayBatDau = null;
    public ?string $ngayKetThuc = null;
    public string $trangThai = 'Đang hiệu lực';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maVoucher', $duLieu)) {
            $this->maVoucher = (string)($duLieu['maVoucher'] ?? '');
        }
        if (array_key_exists('loaiGiam', $duLieu)) {
            $this->loaiGiam = (string)($duLieu['loaiGiam'] ?? '');
        }
        if (array_key_exists('giaTriGiam', $duLieu)) {
            $this->giaTriGiam = (float)($duLieu['giaTriGiam'] ?? 0.0);
        }
        if (array_key_exists('soLuong', $duLieu)) {
            $this->soLuong = (int)($duLieu['soLuong'] ?? 0);
        }
        if (array_key_exists('gioiHanSuDung', $duLieu)) {
            $this->gioiHanSuDung = (int)($duLieu['gioiHanSuDung'] ?? 0);
        }
        if (array_key_exists('luotDaDung', $duLieu)) {
            $this->luotDaDung = (int)($duLieu['luotDaDung'] ?? 0);
        }
        if (array_key_exists('ngayBatDau', $duLieu)) {
            $this->ngayBatDau = isset($duLieu['ngayBatDau']) && $duLieu['ngayBatDau'] !== '' ? (string)$duLieu['ngayBatDau'] : null;
        }
        if (array_key_exists('ngayKetThuc', $duLieu)) {
            $this->ngayKetThuc = isset($duLieu['ngayKetThuc']) && $duLieu['ngayKetThuc'] !== '' ? (string)$duLieu['ngayKetThuc'] : null;
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
            'maVoucher' => $this->maVoucher,
            'loaiGiam' => $this->loaiGiam,
            'giaTriGiam' => $this->giaTriGiam,
            'soLuong' => $this->soLuong,
            'gioiHanSuDung' => $this->gioiHanSuDung,
            'luotDaDung' => $this->luotDaDung,
            'ngayBatDau' => $this->ngayBatDau,
            'ngayKetThuc' => $this->ngayKetThuc,
            'trangThai' => $this->trangThai,
        ];
    }
    public function conHieuLuc(?string $ngayHienTai = null): bool
    {
        if ($this->trangThai !== 'Đang hiệu lực' || $this->luotDaDung >= $this->soLuong) {
            return false;
        }
        $homNay = $ngayHienTai ?? date('Y-m-d');
        if ($this->ngayBatDau && $homNay < $this->ngayBatDau) {
            return false;
        }
        if ($this->ngayKetThuc && $homNay > $this->ngayKetThuc) {
            return false;
        }
        return true;
    }
}
