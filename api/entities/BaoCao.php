<?php
// Entity BCE: BaoCao — Lớp tổng hợp tạm cho Thống kê - Báo cáo (Chuẩn Lan04)

class BaoCao
{
    public const LA_THUC_THE_TAM = true;

    private string $loaiBaoCao = '';
    private ?string $tuNgay = null;
    private ?string $denNgay = null;
    private float $tongDoanhThu = 0.0;
    private float $tongGiaVon = 0.0;
    private float $tongLoiNhuan = 0.0;
    private int $tongSoDon = 0;
    private int $soDonThanhCong = 0;
    private int $soDonDaHuy = 0;
    private int $soDonDangXuLy = 0;
    private array $chiTiet = [];
    private ?string $thoiDiemTao = null;

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('loaiBaoCao', $duLieu)) {
            $this->loaiBaoCao = (string)($duLieu['loaiBaoCao'] ?? '');
        }
        if (array_key_exists('tuNgay', $duLieu)) {
            $this->tuNgay = isset($duLieu['tuNgay']) && $duLieu['tuNgay'] !== '' ? (string)$duLieu['tuNgay'] : null;
        }
        if (array_key_exists('denNgay', $duLieu)) {
            $this->denNgay = isset($duLieu['denNgay']) && $duLieu['denNgay'] !== '' ? (string)$duLieu['denNgay'] : null;
        }
        if (array_key_exists('tongDoanhThu', $duLieu)) {
            $this->tongDoanhThu = (float)($duLieu['tongDoanhThu'] ?? 0.0);
        }
        if (array_key_exists('tongGiaVon', $duLieu)) {
            $this->tongGiaVon = (float)($duLieu['tongGiaVon'] ?? 0.0);
        }
        if (array_key_exists('tongLoiNhuan', $duLieu)) {
            $this->tongLoiNhuan = (float)($duLieu['tongLoiNhuan'] ?? 0.0);
        }
        if (array_key_exists('tongSoDon', $duLieu)) {
            $this->tongSoDon = (int)($duLieu['tongSoDon'] ?? 0);
        }
        if (array_key_exists('soDonThanhCong', $duLieu)) {
            $this->soDonThanhCong = (int)($duLieu['soDonThanhCong'] ?? 0);
        }
        if (array_key_exists('soDonDaHuy', $duLieu)) {
            $this->soDonDaHuy = (int)($duLieu['soDonDaHuy'] ?? 0);
        }
        if (array_key_exists('soDonDangXuLy', $duLieu)) {
            $this->soDonDangXuLy = (int)($duLieu['soDonDangXuLy'] ?? 0);
        }
        if (array_key_exists('chiTiet', $duLieu)) {
            $this->chiTiet = is_array($duLieu['chiTiet'] ?? null) ? $duLieu['chiTiet'] : [];
        }
        if (array_key_exists('thoiDiemTao', $duLieu)) {
            $this->thoiDiemTao = isset($duLieu['thoiDiemTao']) && $duLieu['thoiDiemTao'] !== '' ? (string)$duLieu['thoiDiemTao'] : null;
        }
    }

    public function __get(string $tenThuocTinh)
    {
        if (property_exists($this, $tenThuocTinh)) {
            return $this->$tenThuocTinh;
        }
        return null;
    }

    public function __set(string $tenThuocTinh, $giaTri): void
    {
        if (property_exists($this, $tenThuocTinh)) {
            $this->$tenThuocTinh = $giaTri;
        }
    }

    public function __isset(string $tenThuocTinh): bool
    {
        return property_exists($this, $tenThuocTinh) && $this->$tenThuocTinh !== null;
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'loaiBaoCao' => $this->loaiBaoCao,
            'tuNgay' => $this->tuNgay,
            'denNgay' => $this->denNgay,
            'tongDoanhThu' => $this->tongDoanhThu,
            'tongGiaVon' => $this->tongGiaVon,
            'tongLoiNhuan' => $this->tongLoiNhuan,
            'tongSoDon' => $this->tongSoDon,
            'soDonThanhCong' => $this->soDonThanhCong,
            'soDonDaHuy' => $this->soDonDaHuy,
            'soDonDangXuLy' => $this->soDonDangXuLy,
            'chiTiet' => $this->chiTiet,
            'thoiDiemTao' => $this->thoiDiemTao,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraKhoangThoiGian(string $tuNgay, string $denNgay): bool
    {
        if ($tuNgay === '' || $denNgay === '') {
            return false;
        }
        $tsTu = strtotime($tuNgay);
        $tsDen = strtotime($denNgay);
        return $tsTu !== false && $tsDen !== false && $tsTu <= $tsDen;
    }

    public static function tinhDoanhThu(array $danhSachDonHang): float
    {
        $tong = 0.0;
        foreach ($danhSachDonHang as $dh) {
            $trangThai = is_array($dh) ? ($dh['trangThai'] ?? '') : $dh->trangThai;
            $tien = is_array($dh) ? (float)($dh['tongTien'] ?? 0) : (float)$dh->tongTien;
            if ($trangThai === 'Đã giao thành công') {
                $tong += $tien;
            }
        }
        return round($tong, 2);
    }

    public static function tinhDoanhThuThangHienTai(?PDO $pdo = null): float
    {
        $db = self::pdo($pdo);
        $tu = date('Y-m-01');
        $den = date('Y-m-t');
        $stmt = $db->prepare("SELECT COALESCE(SUM(tongTien), 0) FROM DonHang WHERE trangThai = 'Đã giao thành công' AND DATE(ngayDat) BETWEEN ? AND ?");
        $stmt->execute([$tu, $den]);
        return (float)$stmt->fetchColumn();
    }

    public static function demDonThangHienTai(?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $tu = date('Y-m-01');
        $den = date('Y-m-t');
        $stmt = $db->prepare("SELECT COUNT(*) FROM DonHang WHERE DATE(ngayDat) BETWEEN ? AND ?");
        $stmt->execute([$tu, $den]);
        return (int)$stmt->fetchColumn();
    }

    public static function phanLoaiDonTheoTrangThai(array $danhSachDonHang): self
    {
        $tongSoDon = 0;
        $soDonThanhCong = 0;
        $soDonDaHuy = 0;
        $soDonDangXuLy = 0;
        $nhom = [];

        foreach ($danhSachDonHang as $dh) {
            $tt = is_array($dh) ? ($dh['trangThai'] ?? '') : $dh->trangThai;
            $tien = is_array($dh) ? (float)($dh['tongTien'] ?? 0) : (float)$dh->tongTien;
            $tongSoDon++;
            if ($tt === 'Đã giao thành công') {
                $soDonThanhCong++;
            } elseif ($tt === 'Đã hủy') {
                $soDonDaHuy++;
            } else {
                $soDonDangXuLy++;
            }
            if (!isset($nhom[$tt])) {
                $nhom[$tt] = ['trangThai' => $tt, 'soLuong' => 0, 'tongGiaTri' => 0.0];
            }
            $nhom[$tt]['soLuong']++;
            $nhom[$tt]['tongGiaTri'] += $tien;
        }

        return new self([
            'loaiBaoCao'     => 'Đơn hàng theo trạng thái',
            'tongSoDon'      => $tongSoDon,
            'soDonThanhCong' => $soDonThanhCong,
            'soDonDaHuy'     => $soDonDaHuy,
            'soDonDangXuLy'  => $soDonDangXuLy,
            'chiTiet'        => array_values($nhom),
            'thoiDiemTao'    => date('Y-m-d H:i:s'),
        ]);
    }

    public static function xepHangSanPhamBanChay(array $danhSachBanChay): array
    {
        usort($danhSachBanChay, function ($a, $b) {
            $slA = (int)($a['tongSoLuongBan'] ?? 0);
            $slB = (int)($b['tongSoLuongBan'] ?? 0);
            if ($slB !== $slA) {
                return $slB <=> $slA;
            }
            return ((float)($b['tongDoanhThu'] ?? 0)) <=> ((float)($a['tongDoanhThu'] ?? 0));
        });
        return $danhSachBanChay;
    }

    public static function tongHopHangSapHet(array $danhSachTon, int $nguongSom = 15, int $nguongToiThieu = 5, int $nguongToiDa = 350): array
    {
        $ketQua = [];
        foreach ($danhSachTon as $dong) {
            $tonKhaDung = (int)($dong['tonKhaDung'] ?? 0);
            $tonThucTe = (int)($dong['tonThucTe'] ?? 0);
            if ($tonKhaDung <= $nguongSom || $tonThucTe > $nguongToiDa) {
                $muc = 'Sắp hết';
                if ($tonKhaDung <= $nguongToiThieu) {
                    $muc = 'Dưới tối thiểu';
                } elseif ($tonThucTe > $nguongToiDa) {
                    $muc = 'Vượt tối đa';
                }
                $dong['mucDoCanhBao'] = $muc;
                $ketQua[] = $dong;
            }
        }
        return $ketQua;
    }

}
