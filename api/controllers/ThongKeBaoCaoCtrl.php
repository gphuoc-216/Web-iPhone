<?php
// Controller BCE: ThongKeBaoCaoCtrl (UC53 - UC56) — Phụ lục B.1 Lan03

class ThongKeBaoCaoCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // UC53: layBaoCaoDoanhThuMacDinh() & layBaoCaoDoanhThu(tuNgay, denNgay) — CHỈ tính đơn 'Đã giao thành công'!
    public function layBaoCaoDoanhThuMacDinh(): array
    {
        return $this->layBaoCaoDoanhThu(date('Y-m-01'), date('Y-m-t'));
    }

    public function layBaoCaoDoanhThu(?string $tuNgay = null, ?string $denNgay = null): array
    {
        $tu = ($tuNgay !== null && $tuNgay !== '') ? trim($tuNgay) : date('Y-m-01');
        $den = ($denNgay !== null && $denNgay !== '') ? trim($denNgay) : date('Y-m-t');

        if (!BaoCao::kiemTraKhoangThoiGian($tu, $den)) {
            return ['thanhCong' => false, 'thongBao' => 'Khoảng thời gian thống kê không hợp lệ (Từ ngày phải nhỏ hơn hoặc bằng Đến ngày)', 'duLieu' => null, 'maHttp' => 400];
        }

        $cacDong = DonHang::timDonThanhCongTheoKhoang($tu, $den, $this->pdo);
        $tongDoanhThu = 0.0;
        $tongGiaVon = 0.0;
        $tongDon = 0;
        $chiTiet = [];

        foreach ($cacDong as $dong) {
            $dt = (float)$dong['doanhThu'];
            $gv = (float)$dong['giaVon'];
            $tongDoanhThu += $dt;
            $tongGiaVon += $gv;
            $tongDon += (int)$dong['soDonHang'];

            $chiTiet[] = [
                'ngay'      => $dong['ngay'],
                'soDonHang' => (int)$dong['soDonHang'],
                'doanhThu'  => $dt,
                'giaVon'    => $gv,
                'loiNhuan'  => $dt - $gv,
            ];
        }

        $baoCao = new BaoCao([
            'loaiBaoCao'     => 'Doanh thu - Lợi nhuận (Đơn đã giao thành công)',
            'tuNgay'         => $tu,
            'denNgay'        => $den,
            'tongDoanhThu'   => $tongDoanhThu,
            'tongGiaVon'     => $tongGiaVon,
            'tongLoiNhuan'   => $tongDoanhThu - $tongGiaVon,
            'tongSoDon'      => $tongDon,
            'soDonThanhCong' => $tongDon,
            'chiTiet'        => $chiTiet,
            'thoiDiemTao'    => date('Y-m-d H:i:s'),
        ]);

        return ['thanhCong' => true, 'thongBao' => 'Thống kê doanh thu thành công', 'duLieu' => $baoCao->chuyenThanhMang()];
    }

    // UC54: layBaoCaoDonHangMacDinh() & layBaoCaoDonHang(tuNgay, denNgay)
    public function layBaoCaoDonHangMacDinh(): array
    {
        return $this->layBaoCaoDonHang(null, null);
    }

    public function layBaoCaoDonHang(?string $tuNgay = null, ?string $denNgay = null): array
    {
        if ($tuNgay && $denNgay && !BaoCao::kiemTraKhoangThoiGian($tuNgay, $denNgay)) {
            return ['thanhCong' => false, 'thongBao' => 'Khoảng thời gian thống kê đơn hàng không hợp lệ (Từ ngày phải nhỏ hơn hoặc bằng Đến ngày)', 'duLieu' => null, 'maHttp' => 400];
        }

        $danhSachDon = DonHang::timDonTheoKhoang($tuNgay, $denNgay, $this->pdo);
        $baoCao = BaoCao::phanLoaiDonTheoTrangThai($danhSachDon);
        $baoCao->tuNgay = $tuNgay;
        $baoCao->denNgay = $denNgay;

        return ['thanhCong' => true, 'thongBao' => 'Thống kê đơn hàng theo trạng thái thành công', 'duLieu' => $baoCao->chuyenThanhMang()];
    }

    // UC55: layBaoCaoBanChay() & locBaoCaoBanChay(tuNgay, denNgay, maDanhMuc) — CHỈ tính đơn 'Đã giao thành công'!
    public function layBaoCaoBanChay(int $gioiHan = 10): array
    {
        return $this->locBaoCaoBanChay(null, null, null, $gioiHan);
    }

    public function locBaoCaoBanChay(?string $tuNgay = null, ?string $denNgay = null, ?int $maDanhMuc = null, int $gioiHan = 10): array
    {
        if ($tuNgay && $denNgay && !BaoCao::kiemTraKhoangThoiGian($tuNgay, $denNgay)) {
            return ['thanhCong' => false, 'thongBao' => 'Khoảng thời gian báo cáo bán chạy không hợp lệ (Từ ngày phải nhỏ hơn hoặc bằng Đến ngày)', 'duLieu' => null, 'maHttp' => 400];
        }

        $cacDong = ChiTietDonHang::tongHopSoLuongBanTheoDieuKien($tuNgay, $denNgay, $maDanhMuc, $gioiHan, $this->pdo);
        $chiTiet = [];
        foreach ($cacDong as $dong) {
            $chiTiet[] = [
                'maDienThoai'    => (int)$dong['maDienThoai'],
                'ten'            => $dong['ten'],
                'hang'           => $dong['hang'],
                'tongSoLuongBan' => (int)$dong['tongSoLuongBan'],
                'tongDoanhThu'   => (float)$dong['tongDoanhThu'],
            ];
        }
        $chiTiet = BaoCao::xepHangSanPhamBanChay($chiTiet);

        $baoCao = new BaoCao([
            'loaiBaoCao'  => 'Sản phẩm bán chạy (Đơn đã giao thành công)',
            'tuNgay'      => $tuNgay,
            'denNgay'     => $denNgay,
            'chiTiet'     => $chiTiet,
            'thoiDiemTao' => date('Y-m-d H:i:s'),
        ]);

        return ['thanhCong' => true, 'thongBao' => 'Báo cáo sản phẩm bán chạy thành công', 'duLieu' => $baoCao->chuyenThanhMang()];
    }

    // UC56: layBaoCaoHangSapHet()
    public function layBaoCaoHangSapHet(): array
    {
        $nguong = CanhBaoTonKho::layNguongCauHinh(null, $this->pdo);
        $chiTiet = TonKho::timTheoDieuKienCanhBao(null, $nguong, $this->pdo);

        $baoCao = new BaoCao([
            'loaiBaoCao'  => 'Cảnh báo hàng sắp hết / vượt ngưỡng',
            'chiTiet'     => $chiTiet,
            'thoiDiemTao' => date('Y-m-d H:i:s'),
        ]);

        return ['thanhCong' => true, 'thongBao' => 'Lấy báo cáo cảnh báo tồn kho thành công', 'duLieu' => $baoCao->chuyenThanhMang()];
    }
}
