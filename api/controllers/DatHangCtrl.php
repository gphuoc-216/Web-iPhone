<?php
// Controller BCE: DatHangCtrl (UC25 - UC29) — Phụ lục B.1 Lan03

class DatHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // UC25: layDanhSachDiaChi(maKhachHang), chonDiaChi(maDiaChi), luuDiaChiMoi(nguoiNhan, soDienThoai, diaChi)
    public function layDanhSachDiaChi(int $maKhachHang): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách địa chỉ giao hàng thành công', 'duLieu' => DiaChiGiaoHang::timTheoKhachHang($maKhachHang, $this->pdo)];
    }

    public function chonDiaChi(int $maDiaChi, int $maKhachHang): array
    {
        $dc = DiaChiGiaoHang::timTheoMa($maDiaChi, $this->pdo);
        if (!$dc || (int)$dc['maKhachHang'] !== $maKhachHang) {
            return ['thanhCong' => false, 'thongBao' => 'Địa chỉ giao hàng không tồn tại hoặc không thuộc quyền sở hữu của bạn', 'duLieu' => null, 'maHttp' => 403];
        }
        return ['thanhCong' => true, 'thongBao' => 'Chọn địa chỉ giao hàng thành công', 'duLieu' => $dc];
    }

    public function luuDiaChiMoi(int $maKhachHang, array $duLieu): array
    {
        $nguoiNhan = trim((string)($duLieu['nguoiNhan'] ?? ''));
        $soDienThoai = trim((string)($duLieu['soDienThoai'] ?? ''));
        $diaChi = trim((string)($duLieu['diaChi'] ?? ''));
        $maQuyTac = (int)($duLieu['maQuyTac'] ?? 1);
        $macDinh = !empty($duLieu['macDinh']);

        if (!DiaChiGiaoHang::kiemTraHopLe($nguoiNhan, $soDienThoai, $diaChi, $maQuyTac)) {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập đầy đủ người nhận, số điện thoại và địa chỉ', 'duLieu' => null, 'maHttp' => 400];
        }
        $maDiaChi = DiaChiGiaoHang::luu($maKhachHang, $nguoiNhan, $soDienThoai, $diaChi, $maQuyTac, $macDinh, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lưu địa chỉ giao hàng mới thành công', 'duLieu' => ['maDiaChi' => $maDiaChi], 'maHttp' => 201];
    }

    // UC26: tinhPhiVanChuyen(maDiaChi, tongTienHang)
    public function tinhPhiVanChuyen(?int $maDiaChi, float $tongTienHang, ?int $maQuyTac = null): array
    {
        if ($maDiaChi !== null && $maDiaChi > 0) {
            $maQuyTac = DiaChiGiaoHang::xacDinhKhuVuc($maDiaChi, $this->pdo);
        }
        return [
            'thanhCong' => true,
            'thongBao'  => 'Tính phí vận chuyển thành công',
            'duLieu'    => QuyTacPhiVanChuyen::tinhPhi($maQuyTac, $tongTienHang, $this->pdo),
        ];
    }

    // UC27: layQuyTacPhi() & luuQuyTacPhi(khuVuc, mucPhi, mucPhiMacDinh, nguongMienPhi)
    public function layQuyTacPhi(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách quy tắc phí vận chuyển thành công', 'duLieu' => QuyTacPhiVanChuyen::timTatCa($this->pdo)];
    }

    public function luuQuyTacPhi(array $duLieu): array
    {
        $maQuyTac = (int)($duLieu['maQuyTac'] ?? 0);
        $khuVuc = trim((string)($duLieu['khuVuc'] ?? ''));
        $mucPhi = (float)($duLieu['mucPhi'] ?? -1);
        $mucPhiMacDinh = (float)($duLieu['mucPhiMacDinh'] ?? 35000);
        $nguongMienPhi = (float)($duLieu['nguongMienPhi'] ?? 0);

        if (!QuyTacPhiVanChuyen::kiemTraHopLe($khuVuc, $mucPhi, $mucPhiMacDinh, $nguongMienPhi)) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin quy tắc phí vận chuyển không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $ma = QuyTacPhiVanChuyen::luu($khuVuc, $mucPhi, $mucPhiMacDinh, $nguongMienPhi, $maQuyTac > 0 ? $maQuyTac : null, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => $maQuyTac > 0 ? 'Cập nhật quy tắc phí vận chuyển thành công' : 'Thêm quy tắc phí vận chuyển thành công', 'duLieu' => ['maQuyTac' => $ma]];
    }

    public function datHang(int $maGioHang, int $maKhachHang): array
    {
        $gio = GioHang::timTheoMa($maGioHang, false, $this->pdo);
        if (!$gio || (!empty($gio['maKhachHang']) && (int)$gio['maKhachHang'] !== $maKhachHang)) {
            return ['thanhCong' => false, 'thongBao' => 'Giỏ hàng không hợp lệ hoặc không thuộc quyền sở hữu của bạn', 'duLieu' => null, 'maHttp' => 403];
        }
        return ['thanhCong' => true, 'thongBao' => 'Sẵn sàng đặt hàng', 'duLieu' => $gio];
    }

    public function layPhuongThucThanhToan(int $maGioHang): array
    {
        return [
            'thanhCong' => true,
            'thongBao'  => 'Lấy phương thức thanh toán thành công',
            'duLieu'    => [
                'maGioHang'   => $maGioHang,
                'danhSach'    => ['Thanh toán khi nhận hàng', 'Thanh toán trực tuyến mô phỏng'],
                'tongCanTra'  => ThanhToan::layTongTienCanThanhToan($maGioHang, $this->pdo),
            ],
        ];
    }

    // UC28: xacNhanDatHang(maGioHang) — Khóa giỏ, giữ chỗ tạm & kiểm tra sở hữu chống IDOR
    public function xacNhanDatHang(int $maKhachHang, array $duLieu, string $maPhien = ''): array
    {
        $maGioHang = (int)($duLieu['maGioHang'] ?? 0);
        $maDiaChi = (int)($duLieu['maDiaChi'] ?? 0);
        $phuongThuc = trim((string)($duLieu['phuongThuc'] ?? 'Thanh toán khi nhận hàng'));

        if ($maGioHang <= 0 || $maKhachHang <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin giỏ hàng hoặc khách hàng không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            batDauGiaoDich($this->pdo);

            $gio = GioHang::timTheoMa($maGioHang, true, $this->pdo);
            if (!$gio || $gio['trangThai'] !== 'Đang dùng') {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Giỏ hàng không tồn tại hoặc đang trong quá trình thanh toán', 'duLieu' => null, 'maHttp' => 400];
            }

            // Kiểm tra quyền sở hữu giỏ hàng (chống IDOR)
            if (!empty($gio['maKhachHang']) && (int)$gio['maKhachHang'] !== $maKhachHang) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Bạn không có quyền đặt hàng trên giỏ hàng của người khác', 'duLieu' => null, 'maHttp' => 403];
            }

            // Kiểm tra quyền sở hữu địa chỉ giao hàng hoặc tạo mới
            if ($maDiaChi > 0) {
                $dc = DiaChiGiaoHang::timTheoMa($maDiaChi, $this->pdo);
                if (!$dc || (int)$dc['maKhachHang'] !== $maKhachHang) {
                    hoanTacGiaoDich($this->pdo);
                    return ['thanhCong' => false, 'thongBao' => 'Địa chỉ giao hàng không thuộc quyền sở hữu của bạn', 'duLieu' => null, 'maHttp' => 403];
                }
            } else {
                $nguoiNhan = trim((string)($duLieu['nguoiNhan'] ?? ''));
                $soDienThoai = trim((string)($duLieu['soDienThoai'] ?? ''));
                $diaChi = trim((string)($duLieu['diaChi'] ?? ''));
                $maQuyTac = (int)($duLieu['maQuyTac'] ?? 1);
                $macDinh = !empty($duLieu['luuVaoSoDiaChi']) && !empty($duLieu['macDinh']);

                if (!DiaChiGiaoHang::kiemTraHopLe($nguoiNhan, $soDienThoai, $diaChi, $maQuyTac)) {
                    hoanTacGiaoDich($this->pdo);
                    return ['thanhCong' => false, 'thongBao' => 'Vui lòng chọn địa chỉ hoặc nhập đầy đủ thông tin địa chỉ giao hàng mới', 'duLieu' => null, 'maHttp' => 400];
                }
                $maDiaChi = DiaChiGiaoHang::luu($maKhachHang, $nguoiNhan, $soDienThoai, $diaChi, $maQuyTac, $macDinh, $this->pdo);
            }

            // Tính lại giỏ hàng để chắc chắn Voucher vẫn đủ điều kiện
            $tinhLai = GioHang::tinhLaiTongTien($maGioHang, $this->pdo);
            $gio = GioHang::timTheoMa($maGioHang, false, $this->pdo);
            $chiTiets = ChiTietGioHang::timTheoGioHang($maGioHang, $this->pdo);
            if (empty($chiTiets)) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Giỏ hàng đang trống, không thể đặt hàng', 'duLieu' => null, 'maHttp' => 400];
            }

            // Kiểm tra tồn khả dụng & giữ chỗ tạm
            foreach ($chiTiets as $ct) {
                if (!TonKho::giuChoTam((int)$ct['maDienThoai'], (int)$ct['soLuong'], $this->pdo)) {
                    hoanTacGiaoDich($this->pdo);
                    return ['thanhCong' => false, 'thongBao' => "Sản phẩm '{$ct['tenDienThoai']}' không đủ số lượng tồn khả dụng trong kho", 'duLieu' => null, 'maHttp' => 400];
                }
            }

            GioHang::khoaGioHang($maGioHang, $maKhachHang, $this->pdo);
            $kqPhi = $this->tinhPhiVanChuyen($maDiaChi, (float)$tinhLai['tongTamTinh']);
            $phiVanChuyen = (float)$kqPhi['duLieu']['phiVanChuyen'];
            $tongTienCuoi = GioHang::capNhatPhiVanChuyen($maGioHang, $phiVanChuyen, $this->pdo);

            if ($phuongThuc === 'Thanh toán trực tuyến mô phỏng') {
                $maThanhToan = ThanhToan::ghiNhanPhuongThuc($maGioHang, $phuongThuc, $tongTienCuoi, null, 'Chờ thanh toán', $this->pdo);
                xacNhanGiaoDich($this->pdo);
                return [
                    'thanhCong' => true,
                    'thongBao'  => 'Đã khóa giỏ hàng và giữ chỗ tạm thành công. Vui lòng hoàn tất thanh toán trực tuyến',
                    'duLieu'    => [
                        'choThanhToan'       => true,
                        'maThanhToan'        => $maThanhToan,
                        'maGioHang'          => $maGioHang,
                        'maDiaChi'           => $maDiaChi,
                        'phiVanChuyen'       => $phiVanChuyen,
                        'tongTien'           => $tongTienCuoi,
                        'trangThaiThanhToan' => 'Chờ thanh toán',
                    ],
                    'maHttp'    => 201,
                ];
            }

            // COD: hoàn tất tạo DonHang ngay
            $maDonHang = $this->hoanTatDonHang($maKhachHang, $maGioHang, $maDiaChi, $gio, $chiTiets, $phiVanChuyen, $tongTienCuoi);
            ThanhToan::ghiNhanPhuongThuc($maGioHang, $phuongThuc, $tongTienCuoi, $maDonHang, 'Chưa thu tiền', $this->pdo);

            xacNhanGiaoDich($this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Đặt hàng thành công (Thanh toán khi nhận hàng)',
                'duLieu'    => [
                    'choThanhToan'       => false,
                    'maDonHang'          => $maDonHang,
                    'phiVanChuyen'       => $phiVanChuyen,
                    'tongTien'           => $tongTienCuoi,
                    'trangThai'          => 'Chờ duyệt',
                    'trangThaiThanhToan' => 'Chưa thu tiền',
                ],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('xacNhanDatHang: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Đặt hàng thất bại. Vui lòng thử lại sau', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC29: thanhToan(maGioHang, phuongThuc / ketQua)
    public function thanhToan(int $maKhachHang, array $duLieu): array
    {
        $maThanhToan = (int)($duLieu['maThanhToan'] ?? 0);
        $maDiaChi = (int)($duLieu['maDiaChi'] ?? 0);
        $ketQua = trim((string)($duLieu['ketQua'] ?? 'thanhCong'));

        if ($maThanhToan <= 0 || $maDiaChi <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin giao dịch thanh toán không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            batDauGiaoDich($this->pdo);

            $tt = ThanhToan::timTheoMa($maThanhToan, true, $this->pdo);
            if (!$tt || $tt['trangThai'] !== 'Chờ thanh toán' || $tt['maDonHang'] !== null) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Giao dịch thanh toán không hợp lệ hoặc đã được xử lý', 'duLieu' => null, 'maHttp' => 400];
            }

            $maGioHang = (int)$tt['maGioHang'];
            $gio = GioHang::timTheoMa($maGioHang, true, $this->pdo);
            $dc = DiaChiGiaoHang::timTheoMa($maDiaChi, $this->pdo);

            // Chống IDOR: đối chiếu cả maGioHang lẫn maDiaChi thuộc về $maKhachHang
            if (!$gio || (int)$gio['maKhachHang'] !== $maKhachHang || !$dc || (int)$dc['maKhachHang'] !== $maKhachHang) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Bạn không có quyền thực hiện giao dịch thanh toán này', 'duLieu' => null, 'maHttp' => 403];
            }

            $chiTiets = ChiTietGioHang::timTheoGioHang($maGioHang, $this->pdo);

            if ($ketQua !== 'thanhCong') {
                foreach ($chiTiets as $ct) {
                    TonKho::nhaGiuChoTam((int)$ct['maDienThoai'], (int)$ct['soLuong'], $this->pdo);
                }
                $ttMoi = ($ketQua === 'hetHan') ? 'Đã hủy' : 'Thất bại';
                ThanhToan::huyGiaoDich($maThanhToan, $ttMoi, $this->pdo);
                GioHang::moKhoaGioHang($maGioHang, $this->pdo);
                xacNhanGiaoDich($this->pdo);

                return [
                    'thanhCong' => false,
                    'thongBao'  => $ketQua === 'hetHan'
                        ? 'Phiên giữ chỗ thanh toán đã hết hạn. Đã hoàn trả tồn giữ chỗ và mở lại giỏ hàng'
                        : 'Thanh toán trực tuyến thất bại. Đã hoàn trả tồn giữ chỗ và mở lại giỏ hàng',
                    'duLieu'    => ['maThanhToan' => $maThanhToan, 'trangThaiThanhToan' => $ttMoi, 'daMoLaiGioHang' => true],
                    'maHttp'    => 400,
                ];
            }

            $kqPhi = $this->tinhPhiVanChuyen($maDiaChi, (float)$gio['tongTamTinh']);
            $phiVanChuyen = (float)$kqPhi['duLieu']['phiVanChuyen'];
            $tongTienCuoi = (float)$tt['soTien'];

            $maDonHang = $this->hoanTatDonHang($maKhachHang, $maGioHang, $maDiaChi, $gio, $chiTiets, $phiVanChuyen, $tongTienCuoi);
            ThanhToan::ganDonHang($maThanhToan, $maDonHang, 'Thành công', $this->pdo);

            xacNhanGiaoDich($this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Thanh toán trực tuyến thành công! Đơn hàng đã được tạo và chờ duyệt',
                'duLieu'    => [
                    'maDonHang'          => $maDonHang,
                    'maThanhToan'        => $maThanhToan,
                    'phiVanChuyen'       => $phiVanChuyen,
                    'tongTien'           => $tongTienCuoi,
                    'trangThai'          => 'Chờ duyệt',
                    'trangThaiThanhToan' => 'Thành công',
                ],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('thanhToan: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể hoàn tất thanh toán. Vui lòng thử lại sau', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    private function hoanTatDonHang(int $maKhachHang, int $maGioHang, int $maDiaChi, array $gio, array $chiTiets, float $phiVanChuyen, float $tongTienCuoi): int
    {
        $maDonHang = DonHang::luu($maKhachHang, $maDiaChi, $gio['maVoucher'] ?? null, $phiVanChuyen, $tongTienCuoi, 'Chờ duyệt', $this->pdo);
        foreach ($chiTiets as $ct) {
            ChiTietDonHang::luu($maDonHang, (int)$ct['maDienThoai'], (int)$ct['soLuong'], (float)$ct['donGia'], $this->pdo);
            TonKho::chuyenGiuChoChoDonHang((int)$ct['maDienThoai'], (int)$ct['soLuong'], $maDonHang, $this->pdo);
        }
        if (!empty($gio['maVoucher'])) {
            Voucher::ghiNhanLuotSuDung($gio['maVoucher'], $maKhachHang, $this->pdo);
        }
        GioHang::xoaSanPhamDaDat($maGioHang, $this->pdo);
        return $maDonHang;
    }
}
