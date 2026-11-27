<?php
// Controller BCE: DatHangCtrl (UC25 - UC29)
// Đồng bộ 1-1 Báo cáo v4: Bảng 34 - Bảng 38, Sequence Hình 33 - Hình 37

class DatHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // [UC25 - Hình 33] DatHangCtrl::layDanhSachDiaChi -> DiaChiGiaoHang::timTheoKhachHang
    public function layDanhSachDiaChi(int $maKhachHang): array
    {
        return [
            'thanhCong' => true,
            'thongBao'  => 'Lấy danh sách địa chỉ giao hàng thành công',
            'duLieu'    => DiaChiGiaoHang::timTheoKhachHang($maKhachHang, $this->pdo),
        ];
    }

    // [UC25 - Hình 33] DatHangCtrl::chonDiaChi -> DiaChiGiaoHang::timTheoMa -> GioHang::ghiNhanDiaChi
    public function chonDiaChi(int $maGioHangHoacDiaChi, ?int $maDiaChi = null, ?int $maKhachHang = null): array
    {
        if ($maDiaChi === null) {
            $maDc = $maGioHangHoacDiaChi;
            $maGh = 0;
        } else {
            $maGh = $maGioHangHoacDiaChi;
            $maDc = $maDiaChi;
        }

        $dc = DiaChiGiaoHang::timTheoMa($maDc, $this->pdo);
        if (!$dc || ($maKhachHang !== null && (int)$dc['maKhachHang'] !== $maKhachHang)) {
            return ['thanhCong' => false, 'thongBao' => 'Địa chỉ giao hàng không tồn tại hoặc không thuộc quyền sở hữu của bạn', 'duLieu' => null, 'maHttp' => 403];
        }
        if ($maGh > 0) {
            GioHang::ghiNhanDiaChi($maGh, $maDc, $this->pdo);
        }
        return ['thanhCong' => true, 'thongBao' => 'Đã ghi nhận địa chỉ giao hàng', 'duLieu' => $dc];
    }

    // [UC25 - Hình 33] DatHangCtrl::luuDiaChiMoi -> DiaChiGiaoHang::kiemTraHopLe -> DiaChiGiaoHang::luu -> GioHang::ghiNhanDiaChi
    public function luuDiaChiMoi(int $maKhachHang, $duLieuHoacNguoiNhan, ?string $soDienThoai = null, ?string $diaChi = null, int $maQuyTac = 1, int $maGioHang = 0): array
    {
        $luuVaoSo = true;
        $macDinh = false;
        if (is_array($duLieuHoacNguoiNhan)) {
            $d = $duLieuHoacNguoiNhan;
            $nguoiNhan = trim((string)($d['nguoiNhan'] ?? ''));
            $soDienThoai = trim((string)($d['soDienThoai'] ?? ''));
            $diaChi = trim((string)($d['diaChi'] ?? ''));
            $maQuyTac = (int)($d['maQuyTac'] ?? 1);
            $maGioHang = (int)($d['maGioHang'] ?? 0);
            $macDinh = !empty($d['macDinh']);
            $luuVaoSo = array_key_exists('luuVaoSoDiaChi', $d) ? (bool)$d['luuVaoSoDiaChi'] : true;
        } else {
            $nguoiNhan = trim((string)$duLieuHoacNguoiNhan);
            $soDienThoai = trim((string)$soDienThoai);
            $diaChi = trim((string)$diaChi);
        }

        if (!DiaChiGiaoHang::kiemTraHopLe($nguoiNhan, $soDienThoai, $diaChi, $maQuyTac)) {
            return ['thanhCong' => false, 'thongBao' => 'Địa chỉ nhập mới không hợp lệ hoặc thiếu thông tin. Vui lòng điền đầy đủ họ tên, số điện thoại và địa chỉ', 'duLieu' => null, 'maHttp' => 400];
        }

        $maDiaChi = DiaChiGiaoHang::luu($maKhachHang, $nguoiNhan, $soDienThoai, $diaChi, $maQuyTac, $luuVaoSo && $macDinh, $this->pdo);
        if ($maGioHang > 0) {
            GioHang::ghiNhanDiaChi($maGioHang, $maDiaChi, $this->pdo);
        }
        $dcMoi = DiaChiGiaoHang::timTheoMa($maDiaChi, $this->pdo);

        return [
            'thanhCong' => true,
            'thongBao'  => 'Lưu địa chỉ giao hàng mới thành công',
            'duLieu'    => $dcMoi ?: ['maDiaChi' => $maDiaChi],
            'maHttp'    => 201,
        ];
    }

    // [UC26 - Hình 34] DatHangCtrl::tinhPhiVanChuyen -> DiaChiGiaoHang::layMaQuyTac -> QuyTacPhiVanChuyen::timTheoMa -> layMucPhiMacDinh / tinhPhi -> GioHang::capNhatPhiVanChuyen
    public function tinhPhiVanChuyen(?int $maDiaChi, float $tongTienHang, ?int $maQuyTac = null, ?int $maGioHang = null): array
    {
        if ($maDiaChi !== null && $maDiaChi > 0) {
            $maQuyTac = DiaChiGiaoHang::layMaQuyTac($maDiaChi, $this->pdo);
        }

        $quyTac = ($maQuyTac !== null && $maQuyTac > 0) ? QuyTacPhiVanChuyen::timTheoMa($maQuyTac, $this->pdo) : null;
        if (!$quyTac) {
            $phiMacDinh = QuyTacPhiVanChuyen::layMucPhiMacDinh($this->pdo);
            $ketQuaPhi = [
                'maQuyTac'      => null,
                'khuVuc'        => 'Mặc định',
                'phiVanChuyen'  => $phiMacDinh,
                'mienPhi'       => false,
                'nguongMienPhi' => 0.0,
            ];
        } else {
            $ketQuaPhi = QuyTacPhiVanChuyen::tinhPhi((int)$quyTac['maQuyTac'], $tongTienHang, $this->pdo);
        }

        if ($maGioHang !== null && $maGioHang > 0) {
            $tongSauPhi = GioHang::capNhatPhiVanChuyen($maGioHang, (float)$ketQuaPhi['phiVanChuyen'], $this->pdo);
            $ketQuaPhi['tongThanhToan'] = $tongSauPhi;
        }

        return [
            'thanhCong' => true,
            'thongBao'  => 'Tính phí vận chuyển thành công',
            'duLieu'    => $ketQuaPhi,
        ];
    }

    // [UC27 - Hình 35] DatHangCtrl::layQuyTacPhi & luuQuyTacPhi
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
            return ['thanhCong' => false, 'thongBao' => 'Mức phí không hợp lệ (phải là số không âm và khu vực không được để trống)', 'duLieu' => null, 'maHttp' => 400];
        }

        $ma = QuyTacPhiVanChuyen::luu($khuVuc, $mucPhi, $mucPhiMacDinh, $nguongMienPhi, $maQuyTac > 0 ? $maQuyTac : null, $this->pdo);
        return [
            'thanhCong' => true,
            'thongBao'  => $maQuyTac > 0 ? 'Cập nhật quy tắc phí vận chuyển thành công' : 'Lưu cấu hình quy tắc phí vận chuyển thành công',
            'duLieu'    => ['maQuyTac' => $ma],
        ];
    }

    // [UC28 - Hình 36 bước 1-2] DatHangCtrl::datHang -> PhienDangNhap::kiemTraDangNhap
    public function datHang(int $maGioHang, string $maPhien = '', ?int $maKhachHang = null): array
    {
        if (!PhienDangNhap::kiemTraDangNhap($maPhien, $this->pdo) || $maKhachHang === null || $maKhachHang <= 0) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Vui lòng đăng nhập hoặc đăng ký để đặt hàng (giỏ hàng tạm vẫn được giữ nguyên)',
                'duLieu'    => ['chuaDangNhap' => true, 'maGioHang' => $maGioHang],
                'maHttp'    => 401,
            ];
        }
        $gio = GioHang::timTheoMa($maGioHang, false, $this->pdo);
        if (!$gio || (!empty($gio['maKhachHang']) && (int)$gio['maKhachHang'] !== $maKhachHang)) {
            return ['thanhCong' => false, 'thongBao' => 'Giỏ hàng không hợp lệ hoặc không thuộc quyền sở hữu của bạn', 'duLieu' => null, 'maHttp' => 403];
        }
        return ['thanhCong' => true, 'thongBao' => 'Sẵn sàng đặt hàng', 'duLieu' => $gio];
    }

    // [UC29 - Hình 37 bước 1] DatHangCtrl::layPhuongThucThanhToan -> ThanhToan::layTongTienCanThanhToan
    public function layPhuongThucThanhToan(int $maGioHang): array
    {
        return [
            'thanhCong' => true,
            'thongBao'  => 'Lấy phương thức thanh toán thành công',
            'duLieu'    => [
                'maGioHang'  => $maGioHang,
                'danhSach'   => ['Thanh toán khi nhận hàng', 'Thanh toán trực tuyến mô phỏng'],
                'tongCanTra' => ThanhToan::layTongTienCanThanhToan($maGioHang, $this->pdo),
            ],
        ];
    }

    // [UC28 - Hình 36] DatHangCtrl::xacNhanDatHang -> ChiTietGioHang::timTheoGioHang -> TonKho::tinhTonKhaDung -> GioHang::khoaGioHang -> TonKho::giuChoTam -> UC29 -> DonHang::demLuotDungVoucher -> DonHang::luu -> ThanhToan::ganDonHang -> ChiTietDonHang::luu -> TonKho::chuyenGiuChoChoDonHang -> Voucher::tangLuotDaDung -> GioHang::xoaSanPhamDaDat
    public function xacNhanDatHang(int $maKhachHang, array $duLieu, string $maPhien = ''): array
    {
        $maGioHang = (int)($duLieu['maGioHang'] ?? 0);
        $maDiaChi = (int)($duLieu['maDiaChi'] ?? 0);
        $phuongThuc = trim((string)($duLieu['phuongThuc'] ?? 'Thanh toán khi nhận hàng'));

        if ($maPhien !== '' && !PhienDangNhap::kiemTraDangNhap($maPhien, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại', 'duLieu' => null, 'maHttp' => 401];
        }
        if ($maGioHang <= 0 || $maKhachHang <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin giỏ hàng hoặc khách hàng không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        if (!ThanhToan::kiemTraPhuongThuc($phuongThuc)) {
            return ['thanhCong' => false, 'thongBao' => 'Phương thức thanh toán không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            batDauGiaoDich($this->pdo);

            $gio = GioHang::timTheoMa($maGioHang, true, $this->pdo);
            if (!$gio || $gio['trangThai'] !== 'Đang dùng') {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Giỏ hàng không tồn tại hoặc đang trong quá trình thanh toán', 'duLieu' => null, 'maHttp' => 400];
            }

            if (!empty($gio['maKhachHang']) && (int)$gio['maKhachHang'] !== $maKhachHang) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Bạn không có quyền đặt hàng trên giỏ hàng của người khác', 'duLieu' => null, 'maHttp' => 403];
            }

            // UC25: Xác định địa chỉ giao hàng & bản chụp người nhận
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
                    return ['thanhCong' => false, 'thongBao' => 'Vui lòng chọn địa chỉ đã lưu hoặc nhập đầy đủ họ tên, số điện thoại và địa chỉ giao hàng mới', 'duLieu' => null, 'maHttp' => 400];
                }
                $maDiaChi = DiaChiGiaoHang::luu($maKhachHang, $nguoiNhan, $soDienThoai, $diaChi, $maQuyTac, $macDinh, $this->pdo);
                $dc = DiaChiGiaoHang::timTheoMa($maDiaChi, $this->pdo);
            }

            GioHang::ghiNhanDiaChi($maGioHang, $maDiaChi, $this->pdo);
            GioHang::tinhLaiTongTien($maGioHang, $this->pdo);
            $gio = GioHang::timTheoMa($maGioHang, false, $this->pdo);

            $chiTiets = ChiTietGioHang::timTheoGioHang($maGioHang, $this->pdo);
            if (empty($chiTiets)) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Giỏ hàng đang trống, không thể đặt hàng', 'duLieu' => null, 'maHttp' => 400];
            }

            // Bước 6 (Hình 36): Kiểm tra tồn khả dụng từng sản phẩm trước khi giữ chỗ
            foreach ($chiTiets as $ct) {
                $tonKhaDung = TonKho::tinhTonKhaDung((int)$ct['maDienThoai'], $this->pdo);
                if ($tonKhaDung < (int)$ct['soLuong']) {
                    hoanTacGiaoDich($this->pdo);
                    return [
                        'thanhCong' => false,
                        'thongBao'  => "Sản phẩm '{$ct['tenDienThoai']}' chỉ còn {$tonKhaDung} máy khả dụng. Vui lòng cập nhật lại giỏ hàng",
                        'duLieu'    => ['maDienThoai' => (int)$ct['maDienThoai'], 'tonKhaDung' => $tonKhaDung],
                        'maHttp'    => 400,
                    ];
                }
            }

            // Bước 7 (Hình 36): Khóa giỏ hàng & giữ chỗ tạm thời
            if (!GioHang::khoaGioHang($maGioHang, $maKhachHang, $this->pdo)) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Không thể khóa giỏ hàng để đặt hàng', 'duLieu' => null, 'maHttp' => 400];
            }

            foreach ($chiTiets as $ct) {
                if (!TonKho::giuChoTam((int)$ct['maDienThoai'], (int)$ct['soLuong'], $this->pdo)) {
                    hoanTacGiaoDich($this->pdo);
                    return ['thanhCong' => false, 'thongBao' => "Sản phẩm '{$ct['tenDienThoai']}' không đủ tồn khả dụng để giữ chỗ tạm", 'duLieu' => null, 'maHttp' => 400];
                }
            }

            // UC26: Tính phí vận chuyển và tổng thanh toán cuối
            $kqPhi = $this->tinhPhiVanChuyen($maDiaChi, (float)$gio['tongTamTinh'], null, $maGioHang);
            $phiVanChuyen = (float)$kqPhi['duLieu']['phiVanChuyen'];
            $tongTienCuoi = max(0.0, (float)$gio['tongTien'] + $phiVanChuyen);

            // Bước 8 (UC29): Nếu thanh toán trực tuyến mô phỏng -> tạo giao dịch chờ phản hồi bước thanh toán
            if ($phuongThuc === 'Thanh toán trực tuyến mô phỏng') {
                $maThanhToan = ThanhToan::ghiNhanPhuongThuc($maGioHang, $phuongThuc, $tongTienCuoi, null, 'Chờ thanh toán', $this->pdo);
                xacNhanGiaoDich($this->pdo);
                return [
                    'thanhCong' => true,
                    'thongBao'  => 'Đã khóa giỏ hàng và giữ chỗ tạm thành công. Vui lòng xác nhận thanh toán trực tuyến',
                    'duLieu'    => [
                        'choThanhToan'       => true,
                        'maThanhToan'        => $maThanhToan,
                        'maGioHang'          => $maGioHang,
                        'maDiaChi'           => $maDiaChi,
                        'nguoiNhan'          => $dc['nguoiNhan'] ?? '',
                        'soDienThoai'        => $dc['soDienThoai'] ?? '',
                        'diaChiNhan'         => $dc['diaChi'] ?? '',
                        'phiVanChuyen'       => $phiVanChuyen,
                        'tongTien'           => $tongTienCuoi,
                        'trangThaiThanhToan' => 'Chờ thanh toán',
                    ],
                    'maHttp'    => 201,
                ];
            }

            // Với Thanh toán khi nhận hàng (COD): UC29 trả kết quả Thành công (chưa thu tiền) -> Bước 9 & 10 lưu đơn chính thức
            $maThanhToan = ThanhToan::ghiNhanPhuongThuc($maGioHang, $phuongThuc, $tongTienCuoi, null, 'Chưa thu tiền', $this->pdo);
            $kqHoanTat = $this->hoanTatDonHang($maKhachHang, $maGioHang, $maDiaChi, $dc, $gio, $chiTiets, $phiVanChuyen, $tongTienCuoi);
            if (!$kqHoanTat['thanhCong']) {
                // Nhả giữ chỗ tạm và mở khóa giỏ khi bước kiểm tra voucher cuối thất bại
                hoanTacGiaoDich($this->pdo);
                return $kqHoanTat;
            }
            $maDonHang = (int)$kqHoanTat['maDonHang'];
            ThanhToan::ganDonHang($maGioHang, $maDonHang, 'Chưa thu tiền', $this->pdo);

            xacNhanGiaoDich($this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Đặt hàng thành công! Đơn hàng đã được lưu ở trạng thái Chờ duyệt',
                'duLieu'    => [
                    'choThanhToan'       => false,
                    'maDonHang'          => $maDonHang,
                    'maThanhToan'        => $maThanhToan,
                    'tenNguoiNhan'       => $dc['nguoiNhan'] ?? '',
                    'sdtNguoiNhan'       => $dc['soDienThoai'] ?? '',
                    'diaChiNhan'         => $dc['diaChi'] ?? '',
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

    // [UC29 - Hình 37 & UC28 bước 9-10] DatHangCtrl::thanhToan -> GioHang::kiemTraGiuChoTam -> ThanhToan::kiemTraPhuongThuc -> thucHienThanhToanMoPhong / huyGiaoDich -> hoanTatDonHang
    public function thanhToan(int $maKhachHang, array $duLieu): array
    {
        $maThanhToan = (int)($duLieu['maThanhToan'] ?? 0);
        $maGioHangInput = (int)($duLieu['maGioHang'] ?? 0);
        $maDiaChi = (int)($duLieu['maDiaChi'] ?? 0);
        $ketQua = trim((string)($duLieu['ketQua'] ?? 'thanhCong'));

        try {
            batDauGiaoDich($this->pdo);

            $tt = $maThanhToan > 0
                ? ThanhToan::timTheoMa($maThanhToan, true, $this->pdo)
                : ThanhToan::timGiaoDichChoTheoGioHang($maGioHangInput, true, $this->pdo);

            if (!$tt || $tt['trangThai'] !== 'Chờ thanh toán' || $tt['maDonHang'] !== null) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Giao dịch thanh toán không hợp lệ hoặc đã được xử lý', 'duLieu' => null, 'maHttp' => 400];
            }

            $maThanhToan = (int)$tt['maThanhToan'];
            $maGioHang = (int)$tt['maGioHang'];
            $gio = GioHang::timTheoMa($maGioHang, true, $this->pdo);
            if ($maDiaChi <= 0 && !empty($gio['maDiaChi'])) {
                $maDiaChi = (int)$gio['maDiaChi'];
            }
            $dc = DiaChiGiaoHang::timTheoMa($maDiaChi, $this->pdo);

            if (!$gio || (int)$gio['maKhachHang'] !== $maKhachHang || !$dc || (int)$dc['maKhachHang'] !== $maKhachHang) {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Bạn không có quyền thực hiện giao dịch thanh toán này', 'duLieu' => null, 'maHttp' => 403];
            }

            $chiTiets = ChiTietGioHang::timTheoGioHang($maGioHang, $this->pdo);

            // Kiểm tra giữ chỗ tạm còn hiệu lực (Hình 37)
            if (!GioHang::kiemTraGiuChoTam($maGioHang, $this->pdo) || $ketQua === 'hetHan') {
                foreach ($chiTiets as $ct) {
                    TonKho::nhaGiuChoTam((int)$ct['maDienThoai'], (int)$ct['soLuong'], $this->pdo);
                }
                ThanhToan::huyGiaoDich($maGioHang, 'Đã hủy', $this->pdo);
                GioHang::moKhoaGioHang($maGioHang, $this->pdo);
                xacNhanGiaoDich($this->pdo);
                return [
                    'thanhCong' => false,
                    'thongBao'  => 'Giữ chỗ tạm đã hết thời hạn. Hệ thống đã nhả giữ chỗ và hủy giao dịch, vui lòng đặt hàng lại',
                    'duLieu'    => ['maThanhToan' => $maThanhToan, 'trangThaiThanhToan' => 'Đã hủy', 'daMoLaiGioHang' => true],
                    'maHttp'    => 400,
                ];
            }

            ThanhToan::thucHienThanhToanMoPhong($maGioHang, (string)$tt['phuongThuc']);

            // Luồng phụ 9.1: Thanh toán thất bại -> nhả giữ chỗ tạm, không tạo đơn
            if ($ketQua !== 'thanhCong') {
                foreach ($chiTiets as $ct) {
                    TonKho::nhaGiuChoTam((int)$ct['maDienThoai'], (int)$ct['soLuong'], $this->pdo);
                }
                ThanhToan::huyGiaoDich($maGioHang, 'Thất bại', $this->pdo);
                GioHang::moKhoaGioHang($maGioHang, $this->pdo);
                xacNhanGiaoDich($this->pdo);
                return [
                    'thanhCong' => false,
                    'thongBao'  => 'Thanh toán chưa thành công. Hệ thống đã nhả giữ chỗ tạm và mở lại giỏ hàng để bạn chọn lại phương thức',
                    'duLieu'    => ['maThanhToan' => $maThanhToan, 'trangThaiThanhToan' => 'Thất bại', 'daMoLaiGioHang' => true],
                    'maHttp'    => 400,
                ];
            }

            $phiVanChuyen = (float)($gio['phiVanChuyen'] ?? 0.0);
            $tongTienCuoi = (float)$tt['soTien'];

            $kqHoanTat = $this->hoanTatDonHang($maKhachHang, $maGioHang, $maDiaChi, $dc, $gio, $chiTiets, $phiVanChuyen, $tongTienCuoi);
            if (!$kqHoanTat['thanhCong']) {
                foreach ($chiTiets as $ct) {
                    TonKho::nhaGiuChoTam((int)$ct['maDienThoai'], (int)$ct['soLuong'], $this->pdo);
                }
                ThanhToan::huyGiaoDich($maGioHang, 'Đã hủy', $this->pdo);
                GioHang::moKhoaGioHang($maGioHang, $this->pdo);
                xacNhanGiaoDich($this->pdo);
                return $kqHoanTat;
            }

            $maDonHang = (int)$kqHoanTat['maDonHang'];
            ThanhToan::ganDonHang($maGioHang, $maDonHang, 'Thành công', $this->pdo);
            ThanhToan::layKetQuaThanhToan($maGioHang, $this->pdo);

            xacNhanGiaoDich($this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Thanh toán trực tuyến thành công! Đơn hàng đã được lưu ở trạng thái Chờ duyệt',
                'duLieu'    => [
                    'maDonHang'          => $maDonHang,
                    'maThanhToan'        => $maThanhToan,
                    'tenNguoiNhan'       => $dc['nguoiNhan'] ?? '',
                    'sdtNguoiNhan'       => $dc['soDienThoai'] ?? '',
                    'diaChiNhan'         => $dc['diaChi'] ?? '',
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

    // Bước 9 & 10 của UC28 (Hình 36): Kiểm tra lại lượt Voucher -> Lưu DonHang kèm snapshot người nhận -> Lưu ChiTietDonHang & chuyển giữ chỗ -> Tăng luotDaDung -> Xóa SP đã đặt khỏi giỏ
    private function hoanTatDonHang(
        int $maKhachHang,
        int $maGioHang,
        int $maDiaChi,
        array $diaChiRow,
        array $gio,
        array $chiTiets,
        float $phiVanChuyen,
        float $tongTienCuoi
    ): array {
        $maVoucher = !empty($gio['maVoucher']) ? strtoupper(trim((string)$gio['maVoucher'])) : null;

        if ($maVoucher !== null) {
            $voucher = Voucher::timTheoMa($maVoucher, $this->pdo);
            if (!$voucher || !Voucher::kiemTraHopLe($maVoucher, $this->pdo)) {
                return ['thanhCong' => false, 'thongBao' => 'Mã giảm giá đã hết lượt phát hành hoặc hết thời hạn hiệu lực', 'duLieu' => null, 'maHttp' => 400];
            }
            $soLuotDaDung = DonHang::demLuotDungVoucher($maKhachHang, $maVoucher, $this->pdo);
            if ($soLuotDaDung >= (int)$voucher['gioiHanSuDung']) {
                return ['thanhCong' => false, 'thongBao' => 'Mã giảm giá đã đạt giới hạn sử dụng của tài khoản', 'duLieu' => null, 'maHttp' => 400];
            }
        }

        $tenNguoiNhan = (string)($diaChiRow['nguoiNhan'] ?? '');
        $sdtNguoiNhan = (string)($diaChiRow['soDienThoai'] ?? '');
        $diaChiNhan = (string)($diaChiRow['diaChi'] ?? '');

        $maDonHang = DonHang::luu(
            $maKhachHang,
            $maDiaChi,
            $tenNguoiNhan,
            $sdtNguoiNhan,
            $diaChiNhan,
            $maVoucher,
            $phiVanChuyen,
            $tongTienCuoi,
            'Chờ duyệt',
            $this->pdo
        );

        foreach ($chiTiets as $ct) {
            ChiTietDonHang::luu($maDonHang, (int)$ct['maDienThoai'], (int)$ct['soLuong'], (float)$ct['donGia'], $this->pdo);
            TonKho::chuyenGiuChoChoDonHang((int)$ct['maDienThoai'], (int)$ct['soLuong'], $maDonHang, $this->pdo);
        }

        if ($maVoucher !== null) {
            if (!Voucher::tangLuotDaDung($maVoucher, $this->pdo)) {
                return ['thanhCong' => false, 'thongBao' => 'Mã giảm giá vừa hết tổng lượt phát hành', 'duLieu' => null, 'maHttp' => 400];
            }
        }

        GioHang::xoaSanPhamDaDat($maGioHang, $this->pdo);

        return ['thanhCong' => true, 'maDonHang' => $maDonHang];
    }
}
