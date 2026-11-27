<?php
// Controller BCE: HuyDonHangCtrl (UC31 - UC32, UC35 - UC38) — Phụ lục B.1 Lan03

class HuyDonHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDonHangCuaToi(int $maKhachHang): array
    {
        return (new HoSoKhachHangCtrl($this->pdo))->layLichSuMuaHang($maKhachHang);
    }

    // UC31: tuHuyDonHang(maDonHang, lyDo) — Khách tự hủy trực tiếp đơn Chờ duyệt
    public function tuHuyDonHang(int $maDonHang, int $maKhachHang, string $lyDo = 'Khách hàng tự hủy đơn'): array
    {
        try {
            batDauGiaoDich($this->pdo);

            $donHang = DonHang::timTheoMa($maDonHang, true, $this->pdo);
            if (!$donHang || (int)$donHang['maKhachHang'] !== $maKhachHang) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy đơn hàng thuộc quyền sở hữu của bạn', 'duLieu' => null, 'maHttp' => 403];
            }

            if ($donHang['trangThai'] !== 'Chờ duyệt') {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Chỉ có thể tự hủy trực tiếp khi đơn hàng đang ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
            }

            foreach (ChiTietDonHang::timTheoDonHang($maDonHang, null, $this->pdo) as $ct) {
                TonKho::nhaGiuCho((int)$ct['maDienThoai'], (int)$ct['soLuong'], $this->pdo);
            }
            Voucher::hoanLuotSuDung($donHang['maVoucher'] ?? null, $this->pdo);
            DonHang::capNhatTrangThai($maDonHang, 'Đã hủy', $this->pdo);
            YeuCauHuyDon::luu($maDonHang, trim($lyDo) ?: 'Khách hàng tự hủy đơn', 'Đã xác nhận', $this->pdo);

            $trangThaiTtMoi = ThanhToan::danhDauCanHoanTien($maDonHang, $this->pdo);
            if ($trangThaiTtMoi === 'Cần hoàn tiền') {
                ThongBao::taoThongBaoTheoVaiTro('Nhân viên Bán hàng / CSKH', "Đơn hàng #{$maDonHang} (đã thanh toán trực tuyến) vừa được khách tự hủy và cần hoàn tiền.", $this->pdo);
            }

            xacNhanGiaoDich($this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Đã hủy đơn hàng thành công' . ($trangThaiTtMoi === 'Cần hoàn tiền' ? '. Yêu cầu hoàn tiền đã được chuyển tới bộ phận CSKH' : ''),
                'duLieu'    => ['maDonHang' => $maDonHang, 'trangThai' => 'Đã hủy', 'trangThaiThanhToan' => $trangThaiTtMoi],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('tuHuyDonHang: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể hủy đơn hàng', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function kiemTraDieuKienHuy(int $maDonHang, int $maKhachHang): array
    {
        $donHang = DonHang::timTheoMa($maDonHang, false, $this->pdo);
        if (!$donHang || (int)$donHang['maKhachHang'] !== $maKhachHang) {
            return ['hopLe' => false, 'thongBao' => 'Không tìm thấy đơn hàng thuộc quyền sở hữu của bạn', 'maHttp' => 403];
        }
        $tt = $donHang['trangThai'];
        if ($tt === 'Chờ duyệt') {
            return ['hopLe' => false, 'thongBao' => 'Đơn hàng đang ở trạng thái Chờ duyệt, bạn có thể bấm Hủy đơn trực tiếp (UC31)', 'maHttp' => 400];
        }
        if (!in_array($tt, ['Đã duyệt', 'Đang đóng gói', 'Đã giao shipper', 'Đang giao'], true)) {
            return ['hopLe' => false, 'thongBao' => 'Đơn hàng không thuộc trạng thái cho phép gửi yêu cầu hủy', 'maHttp' => 400];
        }
        if (YeuCauHuyDon::kiemTraDaCoYeuCau($maDonHang, $this->pdo)) {
            return ['hopLe' => false, 'thongBao' => 'Đơn hàng này đang có một yêu cầu hủy chờ xử lý, không thể gửi trùng', 'maHttp' => 409];
        }
        return ['hopLe' => true, 'thongBao' => 'Đủ điều kiện gửi yêu cầu hủy'];
    }

    // UC32: guiYeuCauHuy(maDonHang, lyDo) — Chặn Chờ duyệt, chặn trùng Chờ xử lý & kiểm tra chủ sở hữu chống IDOR
    public function guiYeuCauHuy(int $maDonHang, string $lyDo, int $maKhachHang): array
    {
        $lyDo = trim($lyDo);
        if ($maDonHang <= 0 || $lyDo === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập lý do yêu cầu hủy đơn hàng', 'duLieu' => null, 'maHttp' => 400];
        }

        $kiemTra = $this->kiemTraDieuKienHuy($maDonHang, $maKhachHang);
        if (!$kiemTra['hopLe']) {
            return ['thanhCong' => false, 'thongBao' => $kiemTra['thongBao'], 'duLieu' => null, 'maHttp' => $kiemTra['maHttp'] ?? 400];
        }

        $maYeuCau = YeuCauHuyDon::luu($maDonHang, $lyDo, 'Chờ xử lý', $this->pdo);
        ThongBao::taoThongBaoTheoVaiTro('Nhân viên Bán hàng / CSKH', "Có yêu cầu hủy đơn hàng #{$maDonHang} mới (Mã YC #{$maYeuCau}). Lý do: {$lyDo}", $this->pdo);

        return ['thanhCong' => true, 'thongBao' => 'Gửi yêu cầu hủy đơn hàng thành công', 'duLieu' => ['maYeuCau' => $maYeuCau], 'maHttp' => 201];
    }

    // UC35: layDanhSachYeuCauHuy() & kiemTraTinhTrangXuLy(maYeuCau)
    public function layDanhSachYeuCauHuy(): array
    {
        $ketQua = [];
        foreach (YeuCauHuyDon::timTatCa($this->pdo) as $dong) {
            $muc = YeuCauHuyDon::tuMang($dong)->chuyenThanhMang();
            $muc['tongTien'] = (float)$dong['tongTien'];
            $muc['trangThaiDonHang'] = $dong['trangThaiDonHang'];
            $muc['tenKhachHang'] = $dong['tenKhachHang'];
            $muc['phuongThucThanhToan'] = $dong['phuongThuc'] ?? '';
            $muc['trangThaiThanhToan'] = $dong['trangThaiThanhToan'] ?? '';
            $muc['soTienThanhToan'] = (float)($dong['soTien'] ?? $dong['tongTien']);
            $muc['soTienHoan'] = (float)($dong['soTienHoan'] ?? 0);
            $ketQua[] = $muc;
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách yêu cầu hủy đơn thành công', 'duLieu' => $ketQua];
    }

    public function kiemTraTinhTrangXuLy(int $maYeuCau): array
    {
        $yc = YeuCauHuyDon::timTheoMa($maYeuCau, false, $this->pdo);
        if (!$yc) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy yêu cầu hủy đơn', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy tình trạng yêu cầu hủy thành công', 'duLieu' => $yc];
    }

    // UC36: xacNhanHuyDon(maYeuCau) & huyDonChuDong(maDonHang, lyDo)
    public function xacNhanHuyDon(int $maYeuCau): array
    {
        try {
            batDauGiaoDich($this->pdo);

            $yc = YeuCauHuyDon::timTheoMa($maYeuCau, true, $this->pdo);
            if (!$yc || $yc['trangThai'] !== 'Chờ xử lý') {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Yêu cầu hủy đơn không tồn tại hoặc đã được xử lý', 'duLieu' => null, 'maHttp' => 400];
            }

            $maDonHang = (int)$yc['maDonHang'];
            $donHang = DonHang::timTheoMa($maDonHang, true, $this->pdo);
            if (!$donHang) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy đơn hàng liên quan', 'duLieu' => null, 'maHttp' => 404];
            }

            if (PhieuXuat::kiemTraDaXacNhan($maDonHang, $this->pdo) || in_array($donHang['trangThai'], ['Đã giao shipper', 'Đang giao', 'Đã giao thành công'], true)) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => "Đơn hàng #{$maDonHang} đã xuất kho ('{$donHang['trangThai']}'), không thể xác nhận hủy — vui lòng chọn Từ chối hủy (UC37)", 'duLieu' => null, 'maHttp' => 400];
            }

            foreach (ChiTietDonHang::timTheoDonHang($maDonHang, null, $this->pdo) as $ct) {
                TonKho::nhaGiuCho((int)$ct['maDienThoai'], (int)$ct['soLuong'], $this->pdo);
            }
            PhieuXuat::huyPhieuXuatTheoDon($maDonHang, $this->pdo);
            Voucher::hoanLuotSuDung($donHang['maVoucher'] ?? null, $this->pdo);

            DonHang::capNhatTrangThai($maDonHang, 'Đã hủy', $this->pdo);
            YeuCauHuyDon::capNhatTrangThai($maYeuCau, 'Đã xác nhận', $this->pdo);
            $trangThaiTtMoi = ThanhToan::danhDauCanHoanTien($maDonHang, $this->pdo);

            $nd = "Đơn hàng #{$maDonHang} đã được xác nhận hủy." . ($trangThaiTtMoi === 'Cần hoàn tiền' ? ' Bộ phận CSKH đang tiến hành hoàn tiền cho bạn.' : '');
            ThongBao::taoThongBao((int)$donHang['maTaiKhoan'], $nd, $this->pdo);

            xacNhanGiaoDich($this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Đã xác nhận hủy đơn hàng, nhả giữ chỗ tồn kho và hoàn lượt voucher',
                'duLieu'    => ['maYeuCau' => $maYeuCau, 'maDonHang' => $maDonHang, 'trangThaiThanhToan' => $trangThaiTtMoi],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('xacNhanHuyDon: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể xử lý xác nhận hủy đơn hàng', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function huyDonChuDong(int $maDonHang, string $lyDo): array
    {
        $maYeuCau = YeuCauHuyDon::luu($maDonHang, trim($lyDo) ?: 'CSKH chủ động hủy đơn', 'Chờ xử lý', $this->pdo);
        return $this->xacNhanHuyDon($maYeuCau);
    }

    // UC37: tuChoiYeuCauHuy(maYeuCau, lyDo)
    public function tuChoiYeuCauHuy(int $maYeuCau, ?string $lyDo = null): array
    {
        $yc = YeuCauHuyDon::timTheoMa($maYeuCau, false, $this->pdo);
        if (!$yc || $yc['trangThai'] !== 'Chờ xử lý') {
            return ['thanhCong' => false, 'thongBao' => 'Yêu cầu hủy đơn không tồn tại hoặc đã được xử lý', 'duLieu' => null, 'maHttp' => 400];
        }
        $donHang = DonHang::timTheoMa((int)$yc['maDonHang'], false, $this->pdo);
        $lyDoSach = trim((string)$lyDo);
        if ($lyDoSach === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập lý do từ chối yêu cầu hủy đơn', 'duLieu' => null, 'maHttp' => 400];
        }

        YeuCauHuyDon::capNhatTrangThai($maYeuCau, 'Đã từ chối', $this->pdo);
        if ($donHang && !empty($donHang['maTaiKhoan'])) {
            ThongBao::taoThongBao((int)$donHang['maTaiKhoan'], "Yêu cầu hủy đơn hàng #{$yc['maDonHang']} đã bị từ chối. Lý do: {$lyDoSach}", $this->pdo);
        }

        return ['thanhCong' => true, 'thongBao' => 'Đã từ chối yêu cầu hủy đơn hàng và thông báo cho khách hàng', 'duLieu' => ['maYeuCau' => $maYeuCau, 'trangThai' => 'Đã từ chối', 'lyDo' => $lyDoSach]];
    }

    // UC38: layThongTinHoanTien(maDonHang) & hoanTien(maDonHang, soTienHoan, lyDo)
    public function layThongTinHoanTien(int $maDonHang): array
    {
        $tt = ThanhToan::timTheoDonHang($maDonHang, false, $this->pdo);
        if (!$tt) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy giao dịch thanh toán của đơn hàng', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin hoàn tiền thành công', 'duLieu' => $tt];
    }

    public function hoanTien(int $maDonHang, ?float $soTienHoan = null, ?string $lyDo = null, bool $hoanThanhCong = true): array
    {
        $tt = ThanhToan::timTheoDonHang($maDonHang, false, $this->pdo);
        if (!$tt || !in_array($tt['trangThai'], ['Cần hoàn tiền', 'Hoàn tiền lỗi'], true)) {
            return ['thanhCong' => false, 'thongBao' => 'Giao dịch thanh toán không ở trạng thái Cần hoàn tiền', 'duLieu' => null, 'maHttp' => 400];
        }

        $tienHoan = ($soTienHoan !== null && $soTienHoan > 0) ? $soTienHoan : (float)$tt['soTien'];
        if (!ThanhToan::kiemTraSoTienHoan($maDonHang, $tienHoan, $this->pdo)) {
            $toiDa = number_format((float)$tt['soTien'], 0, ',', '.');
            return [
                'thanhCong' => false,
                'thongBao'  => "Số tiền hoàn không hợp lệ (phải lớn hơn 0 và không vượt quá số tiền khách đã thanh toán: {$toiDa}đ)",
                'duLieu'    => null,
                'maHttp'    => 400,
            ];
        }

        $donHang = DonHang::timTheoMa($maDonHang, false, $this->pdo);
        ThanhToan::thucHienHoanTienMoPhong($maDonHang, $tienHoan, $hoanThanhCong, $this->pdo);

        if ($hoanThanhCong) {
            if ($donHang && !empty($donHang['maTaiKhoan'])) {
                $tienFmt = number_format($tienHoan, 0, ',', '.');
                $ghiChu = $lyDo ? " Ghi chú: {$lyDo}" : '';
                ThongBao::taoThongBao((int)$donHang['maTaiKhoan'], "Đơn hàng #{$maDonHang} đã được hoàn tiền thành công số tiền {$tienFmt}đ.{$ghiChu}", $this->pdo);
            }
            return [
                'thanhCong' => true,
                'thongBao'  => 'Thực hiện hoàn tiền mô phỏng và gửi thông báo cho khách hàng thành công',
                'duLieu'    => ['maDonHang' => $maDonHang, 'trangThai' => 'Đã hoàn tiền', 'soTienHoan' => $tienHoan],
            ];
        }

        return [
            'thanhCong' => false,
            'thongBao'  => 'Hoàn tiền thất bại, hệ thống đã ghi nhận trạng thái Hoàn tiền lỗi',
            'duLieu'    => ['maDonHang' => $maDonHang, 'trangThai' => 'Hoàn tiền lỗi'],
            'maHttp'    => 502,
        ];
    }
}
