<?php
// Controller BCE: QuanLyVoucherCtrl (UC60 - UC64)
// Đồng bộ 1-1 Báo cáo v4: Hình 8, Bảng 69 - Bảng 73, Sequence Hình 68 - Hình 72, Phụ lục A (Bảng 250)

class QuanLyVoucherCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // [UC60 - UC64 - Hình 68 - Hình 72] QuanLyVoucherCtrl::layDanhSachVoucher -> Voucher::timTatCa
    public function layDanhSachVoucher(): array
    {
        $ds = Voucher::timTatCa(null, $this->pdo);
        $thongBao = empty($ds) ? 'Chưa có mã giảm giá' : 'Lấy danh sách mã giảm giá thành công';
        return ['thanhCong' => true, 'thongBao' => $thongBao, 'duLieu' => $ds];
    }

    // [UC61 - Hình 69] QuanLyVoucherCtrl::locVoucher -> Voucher::timTheoTrangThai
    public function locVoucher(?string $trangThai = null): array
    {
        $ds = ($trangThai !== null && trim($trangThai) !== '')
            ? Voucher::timTheoTrangThai(trim($trangThai), $this->pdo)
            : Voucher::timTatCa(null, $this->pdo);
        $thongBao = empty($ds) ? 'Không tìm thấy mã giảm giá phù hợp' : 'Tra cứu danh sách mã giảm giá thành công';
        return ['thanhCong' => true, 'thongBao' => $thongBao, 'duLieu' => $ds];
    }

    // [UC61 - Hình 69] QuanLyVoucherCtrl::layChiTietVoucher -> Voucher::timTheoMa -> DieuKienApDung::timTheoVoucher
    public function layChiTietVoucher(string $maVoucher): array
    {
        $v = Voucher::timTheoMa($maVoucher, $this->pdo);
        $dieuKien = DieuKienApDung::timTheoVoucher($maVoucher, $this->pdo);
        if (!$v) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy mã giảm giá', 'duLieu' => null, 'maHttp' => 404];
        }
        $v['dieuKienApDung'] = $dieuKien ? $dieuKien->chuyenThanhMang() : [
            'giaTriToiThieu' => (float)($v['giaTriToiThieu'] ?? 0),
            'nhomHang'       => $v['nhomHang'] ?? 'Tất cả',
            'doiTuong'       => $v['doiTuong'] ?? 'Khách hàng đã đăng nhập',
        ];
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết mã giảm giá thành công', 'duLieu' => $v];
    }

    // [UC62 - Hình 70] QuanLyVoucherCtrl::layThongTinVoucher -> Voucher::timTheoMa
    public function layThongTinVoucher(string $maVoucher): array
    {
        return $this->layChiTietVoucher($maVoucher);
    }

    // [UC60 - Hình 68] QuanLyVoucherCtrl::luuVoucher -> Voucher::kiemTraTrungMa -> Voucher::kiemTraHopLe -> Voucher::luu -> DieuKienApDung::lienKet -> Voucher::timTatCa
    public function luuVoucher(
        $maVoucherHoacDuLieu,
        ?string $loaiGiam = null,
        ?float $giaTriGiam = null,
        ?int $soLuong = null,
        ?int $gioiHanSuDung = null,
        $thoiGianHoacNgayBatDau = null,
        $dieuKienHoacNgayKetThuc = null,
        $dieuKien = null
    ): array {
        if (is_array($maVoucherHoacDuLieu)) {
            $d = $maVoucherHoacDuLieu;
            $maVoucher = strtoupper(trim((string)($d['maVoucher'] ?? '')));
            $loaiGiam = trim((string)($d['loaiGiam'] ?? 'Số tiền'));
            $giaTriGiam = (float)($d['giaTriGiam'] ?? 0);
            $soLuong = (int)($d['soLuong'] ?? 0);
            $gioiHanSuDung = (int)($d['gioiHanSuDung'] ?? 1);
            $ngayBatDau = trim((string)($d['ngayBatDau'] ?? date('Y-m-d')));
            $ngayKetThuc = trim((string)($d['ngayKetThuc'] ?? date('Y-m-d', strtotime('+30 days'))));
            $dk = is_array($d['dieuKienApDung'] ?? null) ? $d['dieuKienApDung'] : $d;
        } else {
            $maVoucher = strtoupper(trim((string)$maVoucherHoacDuLieu));
            $loaiGiam = trim((string)($loaiGiam ?? 'Số tiền'));
            $giaTriGiam = (float)($giaTriGiam ?? 0);
            $soLuong = (int)($soLuong ?? 0);
            $gioiHanSuDung = (int)($gioiHanSuDung ?? 1);
            if (is_array($thoiGianHoacNgayBatDau)) {
                $ngayBatDau = trim((string)($thoiGianHoacNgayBatDau['ngayBatDau'] ?? date('Y-m-d')));
                $ngayKetThuc = trim((string)($thoiGianHoacNgayBatDau['ngayKetThuc'] ?? date('Y-m-d', strtotime('+30 days'))));
                $dk = is_array($dieuKienHoacNgayKetThuc) ? $dieuKienHoacNgayKetThuc : [];
            } else {
                $ngayBatDau = trim((string)($thoiGianHoacNgayBatDau ?? date('Y-m-d')));
                $ngayKetThuc = trim((string)($dieuKienHoacNgayKetThuc ?? date('Y-m-d', strtotime('+30 days'))));
                $dk = is_array($dieuKien) ? $dieuKien : [];
            }
        }

        // Thứ tự kiểm tra khớp Hình 68: Voucher::kiemTraTrungMa -> Voucher::kiemTraHopLe
        $daTonTai = $maVoucher !== '' && Voucher::kiemTraTrungMa($maVoucher, $this->pdo);
        $ketQuaHopLe = $maVoucher !== '' && mb_strlen($maVoucher) <= 30
            && Voucher::kiemTraHopLe($giaTriGiam, $soLuong, $gioiHanSuDung, $loaiGiam, $ngayBatDau, $ngayKetThuc);

        if ($daTonTai) {
            return ['thanhCong' => false, 'thongBao' => 'Mã giảm giá đã tồn tại. Vui lòng nhập mã khác', 'duLieu' => null, 'maHttp' => 409];
        }
        if (!$ketQuaHopLe) {
            return ['thanhCong' => false, 'thongBao' => 'Giá trị giảm, số lượng phát hành, giới hạn sử dụng hoặc thời gian hiệu lực không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            batDauGiaoDich($this->pdo);
            Voucher::luu($maVoucher, $loaiGiam, $giaTriGiam, $soLuong, $gioiHanSuDung, $ngayBatDau, $ngayKetThuc, $this->pdo);
            DieuKienApDung::lienKet($maVoucher, $dk, $this->pdo);
            xacNhanGiaoDich($this->pdo);

            $danhSachMoi = Voucher::timTatCa(null, $this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Tạo mã giảm giá thành công',
                'duLieu'    => ['maVoucher' => $maVoucher, 'danhSachVoucher' => $danhSachMoi],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('luuVoucher: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể tạo mã giảm giá', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // [UC62 - Hình 70] QuanLyVoucherCtrl::capNhatVoucher -> Voucher::kiemTraHopLe -> Voucher::capNhat -> Voucher::timTatCa
    public function capNhatVoucher(
        string $maVoucher,
        $loaiGiamHoacDuLieu,
        ?float $giaTriGiam = null,
        ?int $soLuong = null,
        ?int $gioiHanSuDung = null,
        $thoiGianHoacNgayBatDau = null,
        ?string $ngayKetThuc = null
    ): array {
        $maVoucher = strtoupper(trim($maVoucher));
        $v = Voucher::timTheoMa($maVoucher, $this->pdo);
        if (!$v) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy mã giảm giá', 'duLieu' => null, 'maHttp' => 404];
        }

        $dk = null;
        if (is_array($loaiGiamHoacDuLieu)) {
            $d = $loaiGiamHoacDuLieu;
            $loaiGiam = trim((string)($d['loaiGiam'] ?? $v['loaiGiam']));
            $giaTriGiam = (float)($d['giaTriGiam'] ?? $v['giaTriGiam']);
            $soLuong = (int)($d['soLuong'] ?? $v['soLuong']);
            $gioiHanSuDung = (int)($d['gioiHanSuDung'] ?? $v['gioiHanSuDung']);
            $ngayBatDau = trim((string)($d['ngayBatDau'] ?? $v['ngayBatDau']));
            $ngayKetThuc = trim((string)($d['ngayKetThuc'] ?? $v['ngayKetThuc']));
            if (isset($d['dieuKienApDung']) || isset($d['giaTriToiThieu']) || isset($d['nhomHang']) || isset($d['doiTuong'])) {
                $dk = is_array($d['dieuKienApDung'] ?? null) ? $d['dieuKienApDung'] : $d;
            }
        } else {
            $loaiGiam = trim((string)$loaiGiamHoacDuLieu);
            $giaTriGiam = (float)($giaTriGiam ?? $v['giaTriGiam']);
            $soLuong = (int)($soLuong ?? $v['soLuong']);
            $gioiHanSuDung = (int)($gioiHanSuDung ?? $v['gioiHanSuDung']);
            if (is_array($thoiGianHoacNgayBatDau)) {
                $ngayBatDau = trim((string)($thoiGianHoacNgayBatDau['ngayBatDau'] ?? $v['ngayBatDau']));
                $ngayKetThuc = trim((string)($thoiGianHoacNgayBatDau['ngayKetThuc'] ?? $v['ngayKetThuc']));
            } else {
                $ngayBatDau = trim((string)($thoiGianHoacNgayBatDau ?? $v['ngayBatDau']));
                $ngayKetThuc = trim((string)($ngayKetThuc ?? $v['ngayKetThuc']));
            }
        }

        if ($soLuong < (int)$v['luotDaDung']) {
            return ['thanhCong' => false, 'thongBao' => 'Số lượng phát hành không được nhỏ hơn số lượt đã sử dụng', 'duLieu' => null, 'maHttp' => 400];
        }
        if (!Voucher::kiemTraHopLe($giaTriGiam, $soLuong, $gioiHanSuDung, $loaiGiam, $ngayBatDau, $ngayKetThuc)) {
            return ['thanhCong' => false, 'thongBao' => 'Giá trị giảm, số lượng hoặc giới hạn sử dụng không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            batDauGiaoDich($this->pdo);
            Voucher::capNhat($maVoucher, $loaiGiam, $giaTriGiam, $soLuong, $gioiHanSuDung, $ngayBatDau, $ngayKetThuc, $this->pdo);
            if ($dk !== null) {
                DieuKienApDung::lienKet($maVoucher, $dk, $this->pdo);
            }
            xacNhanGiaoDich($this->pdo);

            $danhSachMoi = Voucher::timTatCa(null, $this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Cập nhật mã giảm giá thành công',
                'duLieu'    => ['maVoucher' => $maVoucher, 'danhSachVoucher' => $danhSachMoi],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('capNhatVoucher: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể cập nhật mã giảm giá', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // [UC63 - Hình 71] QuanLyVoucherCtrl::khoaVoucher -> Voucher::capNhatTrangThai -> Voucher::timTatCa
    public function khoaVoucher(string $maVoucher): array
    {
        return $this->capNhatTrangThaiVoucher($maVoucher, 'Đã khóa');
    }

    public function moKhoaVoucher(string $maVoucher): array
    {
        return $this->capNhatTrangThaiVoucher($maVoucher, 'Đang hiệu lực');
    }

    public function capNhatTrangThaiVoucher(string $maVoucher, string $trangThai): array
    {
        $maVoucher = strtoupper(trim($maVoucher));
        if (!Voucher::timTheoMa($maVoucher, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy mã giảm giá', 'duLieu' => null, 'maHttp' => 404];
        }
        if (!in_array($trangThai, ['Đang hiệu lực', 'Hết hạn', 'Đã khóa'], true)) {
            return ['thanhCong' => false, 'thongBao' => 'Trạng thái mã giảm giá không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        Voucher::capNhatTrangThai($maVoucher, $trangThai, $this->pdo);
        $danhSachMoi = Voucher::timTatCa(null, $this->pdo);

        return [
            'thanhCong' => true,
            'thongBao'  => $trangThai === 'Đã khóa' ? 'Khóa mã giảm giá thành công' : 'Cập nhật trạng thái mã giảm giá thành công',
            'duLieu'    => ['maVoucher' => $maVoucher, 'trangThai' => $trangThai, 'danhSachVoucher' => $danhSachMoi],
        ];
    }

    // [UC64 - Hình 72] QuanLyVoucherCtrl::xoaVoucher -> Voucher::demLuotSuDung -> Voucher::xoa -> Voucher::timTatCa
    public function xoaVoucher(string $maVoucher): array
    {
        $maVoucher = strtoupper(trim($maVoucher));
        if (!Voucher::timTheoMa($maVoucher, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy mã giảm giá', 'duLieu' => null, 'maHttp' => 404];
        }
        if (Voucher::demLuotSuDung($maVoucher, $this->pdo) > 0) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Mã giảm giá đã phát sinh lượt sử dụng nên không thể xóa. Vui lòng chuyển sang trạng thái khóa để ngừng áp dụng',
                'duLieu'    => null,
                'maHttp'    => 409,
            ];
        }

        try {
            batDauGiaoDich($this->pdo);
            DieuKienApDung::xoaTheoVoucher($maVoucher, $this->pdo);
            Voucher::xoa($maVoucher, $this->pdo);
            xacNhanGiaoDich($this->pdo);

            $danhSachMoi = Voucher::timTatCa(null, $this->pdo);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Xóa mã giảm giá thành công',
                'duLieu'    => ['maVoucher' => $maVoucher, 'danhSachVoucher' => $danhSachMoi],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('xoaVoucher: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa mã giảm giá', 'duLieu' => null, 'maHttp' => 409];
        }
    }
}
