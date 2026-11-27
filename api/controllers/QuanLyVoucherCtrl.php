<?php
// Controller BCE: QuanLyVoucherCtrl (UC60 - UC64) — Phụ lục B.1 Lan03

class QuanLyVoucherCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDanhSachVoucher(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách voucher thành công', 'duLieu' => Voucher::timTatCa(null, $this->pdo)];
    }

    // UC61: locVoucher(trangThai) & layChiTietVoucher(maVoucher)
    public function locVoucher(?string $trangThai = null): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Tra cứu danh sách voucher thành công', 'duLieu' => Voucher::timTatCa($trangThai, $this->pdo)];
    }

    public function layChiTietVoucher(string $maVoucher): array
    {
        $v = Voucher::timTheoMa($maVoucher, $this->pdo);
        if (!$v) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy mã voucher', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết voucher thành công', 'duLieu' => $v];
    }

    public function layThongTinVoucher(string $maVoucher): array
    {
        return $this->layChiTietVoucher($maVoucher);
    }

    // UC60: luuVoucher(maVoucher, loaiGiam, giaTriGiam, soLuong, gioiHanSuDung, thoiGian, dieuKien)
    public function luuVoucher(array $duLieu): array
    {
        $maVoucher = strtoupper(trim((string)($duLieu['maVoucher'] ?? '')));
        $loaiGiam = trim((string)($duLieu['loaiGiam'] ?? 'Số tiền'));
        $giaTriGiam = (float)($duLieu['giaTriGiam'] ?? 0);
        $soLuong = (int)($duLieu['soLuong'] ?? 0);
        $gioiHanSuDung = (int)($duLieu['gioiHanSuDung'] ?? 1);
        $ngayBatDau = trim((string)($duLieu['ngayBatDau'] ?? date('Y-m-d')));
        $ngayKetThuc = trim((string)($duLieu['ngayKetThuc'] ?? date('Y-m-d', strtotime('+30 days'))));

        if ($maVoucher === '' || !Voucher::kiemTraHopLe($giaTriGiam, $soLuong, $gioiHanSuDung, $loaiGiam, $ngayBatDau, $ngayKetThuc)) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin voucher không hợp lệ (kiểm tra loại giảm, giá trị, số lượng và ngày hiệu lực)', 'duLieu' => null, 'maHttp' => 400];
        }
        if (Voucher::kiemTraTrungMa($maVoucher, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Mã voucher đã tồn tại trong hệ thống', 'duLieu' => null, 'maHttp' => 409];
        }

        $dk = is_array($duLieu['dieuKienApDung'] ?? null) ? $duLieu['dieuKienApDung'] : $duLieu;

        try {
            batDauGiaoDich($this->pdo);
            Voucher::luu($maVoucher, $loaiGiam, $giaTriGiam, $soLuong, $gioiHanSuDung, $ngayBatDau, $ngayKetThuc, $this->pdo);
            DieuKienApDung::lienKet($maVoucher, $dk, $this->pdo);
            xacNhanGiaoDich($this->pdo);

            return ['thanhCong' => true, 'thongBao' => 'Tạo mã voucher thành công', 'duLieu' => ['maVoucher' => $maVoucher], 'maHttp' => 201];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('luuVoucher: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể tạo mã voucher', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC62: capNhatVoucher(...)
    public function capNhatVoucher(string $maVoucher, array $duLieu): array
    {
        $maVoucher = strtoupper(trim($maVoucher));
        $v = Voucher::timTheoMa($maVoucher, $this->pdo);
        if (!$v) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy mã voucher', 'duLieu' => null, 'maHttp' => 404];
        }

        $loaiGiam = trim((string)($duLieu['loaiGiam'] ?? $v['loaiGiam']));
        $giaTriGiam = (float)($duLieu['giaTriGiam'] ?? $v['giaTriGiam']);
        $soLuong = (int)($duLieu['soLuong'] ?? $v['soLuong']);
        $gioiHanSuDung = max(1, (int)($duLieu['gioiHanSuDung'] ?? $v['gioiHanSuDung']));
        $ngayBatDau = trim((string)($duLieu['ngayBatDau'] ?? $v['ngayBatDau']));
        $ngayKetThuc = trim((string)($duLieu['ngayKetThuc'] ?? $v['ngayKetThuc']));

        if ($soLuong < (int)$v['luotDaDung']) {
            return ['thanhCong' => false, 'thongBao' => 'Số lượng phát hành không được nhỏ hơn số lượt đã sử dụng', 'duLieu' => null, 'maHttp' => 400];
        }
        if (!Voucher::kiemTraHopLe($giaTriGiam, $soLuong, $gioiHanSuDung, $loaiGiam, $ngayBatDau, $ngayKetThuc)) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin cập nhật voucher không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $dk = is_array($duLieu['dieuKienApDung'] ?? null) ? $duLieu['dieuKienApDung'] : $duLieu;

        try {
            batDauGiaoDich($this->pdo);
            Voucher::capNhat($maVoucher, $loaiGiam, $giaTriGiam, $soLuong, $gioiHanSuDung, $ngayBatDau, $ngayKetThuc, $this->pdo);
            DieuKienApDung::lienKet($maVoucher, $dk, $this->pdo);
            xacNhanGiaoDich($this->pdo);

            return ['thanhCong' => true, 'thongBao' => 'Cập nhật mã voucher thành công', 'duLieu' => ['maVoucher' => $maVoucher]];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('capNhatVoucher: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể cập nhật mã voucher', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC63: khoaVoucher(maVoucher)
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
        if (!in_array($trangThai, ['Đang hiệu lực', 'Hết hạn', 'Đã khóa'], true)) {
            return ['thanhCong' => false, 'thongBao' => 'Trạng thái voucher không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        Voucher::capNhatTrangThai($maVoucher, $trangThai, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật trạng thái voucher thành công', 'duLieu' => ['maVoucher' => $maVoucher, 'trangThai' => $trangThai]];
    }

    // UC64: xoaVoucher(maVoucher)
    public function xoaVoucher(string $maVoucher): array
    {
        $maVoucher = strtoupper(trim($maVoucher));
        if (!Voucher::timTheoMa($maVoucher, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy mã voucher', 'duLieu' => null, 'maHttp' => 404];
        }
        if (Voucher::demLuotSuDung($maVoucher, $this->pdo) > 0) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Voucher đã phát sinh lượt sử dụng (> 0), không được xóa — vui lòng chọn chức năng Khóa voucher (UC63)',
                'duLieu'    => null,
                'maHttp'    => 409,
            ];
        }

        try {
            batDauGiaoDich($this->pdo);
            DieuKienApDung::xoaTheoVoucher($maVoucher, $this->pdo);
            Voucher::xoa($maVoucher, $this->pdo);
            xacNhanGiaoDich($this->pdo);
            return ['thanhCong' => true, 'thongBao' => 'Đã xóa mã voucher chưa sử dụng thành công', 'duLieu' => ['maVoucher' => $maVoucher]];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('xoaVoucher: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa mã voucher', 'duLieu' => null, 'maHttp' => 409];
        }
    }
}
