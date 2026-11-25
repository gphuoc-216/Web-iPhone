<?php
// Controller BCE: ThongKeBaoCaoCtrl (UC53 - UC56)

class ThongKeBaoCaoCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function thongKeDoanhThuLoiNhuan(?string $tuNgay = null, ?string $denNgay = null): array
    {
        $tu = $tuNgay ?: date('Y-m-01');
        $den = $denNgay ?: date('Y-m-d');

        $stmt = $this->pdo->prepare(
            "SELECT DATE(dh.ngayDat) AS ngay,
                    COUNT(DISTINCT dh.maDonHang) AS soDonHang,
                    COALESCE(SUM(ct.soLuong * ct.donGia), 0) AS doanhThu,
                    COALESCE(SUM(ct.soLuong * d.giaVonBinhQuan), 0) AS giaVon
             FROM DonHang dh
             JOIN ChiTietDonHang ct ON ct.maDonHang = dh.maDonHang
             JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai
             WHERE dh.trangThai <> 'Đã hủy'
               AND DATE(dh.ngayDat) BETWEEN ? AND ?
             GROUP BY DATE(dh.ngayDat)
             ORDER BY ngay ASC"
        );
        $stmt->execute([$tu, $den]);
        $cacDong = $stmt->fetchAll();

        $tongDoanhThu = 0.0;
        $tongGiaVon = 0.0;
        $tongDon = 0;
        $chiTiet = [];

        foreach ($cacDong as $dong) {
            $dt = (float)$dong['doanhThu'];
            $gv = (float)$dong['giaVon'];
            $ln = $dt - $gv;
            $tongDoanhThu += $dt;
            $tongGiaVon += $gv;
            $tongDon += (int)$dong['soDonHang'];

            $chiTiet[] = [
                'ngay'      => $dong['ngay'],
                'soDonHang' => (int)$dong['soDonHang'],
                'doanhThu'  => $dt,
                'giaVon'    => $gv,
                'loiNhuan'  => $ln,
            ];
        }

        $baoCao = new BaoCao([
            'loaiBaoCao'    => 'Doanh thu - Lợi nhuận',
            'tuNgay'        => $tu,
            'denNgay'       => $den,
            'tongDoanhThu'  => $tongDoanhThu,
            'tongGiaVon'    => $tongGiaVon,
            'tongLoiNhuan'  => $tongDoanhThu - $tongGiaVon,
            'tongSoDon'     => $tongDon,
            'chiTiet'       => $chiTiet,
            'thoiDiemTao'   => date('Y-m-d H:i:s'),
        ]);

        return ['thanhCong' => true, 'thongBao' => 'Thống kê doanh thu và lợi nhuận thành công', 'duLieu' => $baoCao->chuyenThanhMang()];
    }

    public function thongKeSanPhamBanChay(int $gioiHan = 10): array
    {
        $gioiHan = max(1, min(100, $gioiHan));
        $stmt = $this->pdo->prepare(
            "SELECT d.maDienThoai, d.ten, d.hang,
                    SUM(ct.soLuong) AS tongSoLuongBan,
                    SUM(ct.soLuong * ct.donGia) AS tongDoanhThu
             FROM ChiTietDonHang ct
             JOIN DonHang dh ON dh.maDonHang = ct.maDonHang
             JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai
             WHERE dh.trangThai <> 'Đã hủy'
             GROUP BY d.maDienThoai, d.ten, d.hang
             ORDER BY tongSoLuongBan DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $gioiHan, PDO::PARAM_INT);
        $stmt->execute();

        $chiTiet = [];
        foreach ($stmt->fetchAll() as $dong) {
            $chiTiet[] = [
                'maDienThoai'    => (int)$dong['maDienThoai'],
                'ten'            => $dong['ten'],
                'hang'           => $dong['hang'],
                'tongSoLuongBan' => (int)$dong['tongSoLuongBan'],
                'tongDoanhThu'   => (float)$dong['tongDoanhThu'],
            ];
        }

        $baoCao = new BaoCao([
            'loaiBaoCao'  => 'Sản phẩm bán chạy',
            'chiTiet'     => $chiTiet,
            'thoiDiemTao' => date('Y-m-d H:i:s'),
        ]);

        return ['thanhCong' => true, 'thongBao' => 'Thống kê sản phẩm bán chạy thành công', 'duLieu' => $baoCao->chuyenThanhMang()];
    }

    public function thongKeDonHangTheoTrangThai(): array
    {
        $cacDong = $this->pdo->query(
            "SELECT trangThai, COUNT(*) AS soLuong, COALESCE(SUM(tongTien), 0) AS tongGiaTri
             FROM DonHang
             GROUP BY trangThai"
        )->fetchAll();

        $tongDon = 0;
        $soDonThanhCong = 0;
        $soDonDaHuy = 0;
        $soDonDangXuLy = 0;
        $chiTiet = [];
        foreach ($cacDong as $dong) {
            $sl = (int)$dong['soLuong'];
            $tongDon += $sl;
            if ($dong['trangThai'] === 'Đã giao thành công') {
                $soDonThanhCong += $sl;
            } elseif ($dong['trangThai'] === 'Đã hủy') {
                $soDonDaHuy += $sl;
            } else {
                $soDonDangXuLy += $sl;
            }
            $chiTiet[] = [
                'trangThai'  => $dong['trangThai'],
                'soLuong'    => $sl,
                'tongGiaTri' => (float)$dong['tongGiaTri'],
            ];
        }

        $baoCao = new BaoCao([
            'loaiBaoCao'     => 'Đơn hàng theo trạng thái',
            'tongSoDon'      => $tongDon,
            'soDonThanhCong' => $soDonThanhCong,
            'soDonDaHuy'     => $soDonDaHuy,
            'soDonDangXuLy'  => $soDonDangXuLy,
            'chiTiet'        => $chiTiet,
            'thoiDiemTao'    => date('Y-m-d H:i:s'),
        ]);

        return ['thanhCong' => true, 'thongBao' => 'Thống kê đơn hàng theo trạng thái thành công', 'duLieu' => $baoCao->chuyenThanhMang()];
    }

    public function thongKeCanhBaoTonKho(): array
    {
        $kiemKeCtrl = new KiemKeCtrl($this->pdo);
        $ketQua = $kiemKeCtrl->layCanhBaoTonKho();
        $baoCao = new BaoCao([
            'loaiBaoCao'  => 'Cảnh báo tồn kho',
            'chiTiet'     => $ketQua['duLieu']['danhSachCanhBao'] ?? [],
            'thoiDiemTao' => date('Y-m-d H:i:s'),
        ]);

        return ['thanhCong' => true, 'thongBao' => 'Thống kê cảnh báo tồn kho thành công', 'duLieu' => $baoCao->chuyenThanhMang()];
    }
}
