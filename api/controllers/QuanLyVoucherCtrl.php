<?php
// Controller BCE: QuanLyVoucherCtrl (UC60 - UC64)

class QuanLyVoucherCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDanhSachVoucher(): array
    {
        $sql = "SELECT v.*, dk.giaTriToiThieu, dk.nhomHang, dk.doiTuong
                FROM Voucher v
                LEFT JOIN DieuKienApDung dk ON dk.maVoucher = v.maVoucher
                ORDER BY v.ngayBatDau DESC";

        $ketQua = [];
        foreach ($this->pdo->query($sql)->fetchAll() as $dong) {
            $muc = Voucher::tuMang($dong)->chuyenThanhMang();
            $muc['dieuKienApDung'] = [
                'giaTriToiThieu' => (float)($dong['giaTriToiThieu'] ?? 0),
                'nhomHang'       => $dong['nhomHang'] ?? null,
                'doiTuong'       => $dong['doiTuong'] ?? null,
            ];
            $ketQua[] = $muc;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách voucher thành công', 'duLieu' => $ketQua];
    }

    public function taoVoucher(array $duLieu): array
    {
        $maVoucher = strtoupper(trim((string)($duLieu['maVoucher'] ?? '')));
        $loaiGiam = trim((string)($duLieu['loaiGiam'] ?? 'Số tiền'));
        $giaTriGiam = (float)($duLieu['giaTriGiam'] ?? 0);
        $soLuong = (int)($duLieu['soLuong'] ?? 0);
        $gioiHanSuDung = (int)($duLieu['gioiHanSuDung'] ?? 1);
        $ngayBatDau = trim((string)($duLieu['ngayBatDau'] ?? date('Y-m-d')));
        $ngayKetThuc = trim((string)($duLieu['ngayKetThuc'] ?? date('Y-m-d', strtotime('+30 days'))));

        if ($maVoucher === '' || !in_array($loaiGiam, ['Phần trăm', 'Số tiền'], true) || $giaTriGiam <= 0 || $soLuong <= 0 || $ngayKetThuc < $ngayBatDau) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin voucher không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare(
                "INSERT INTO Voucher (maVoucher, loaiGiam, giaTriGiam, soLuong, gioiHanSuDung, luotDaDung, ngayBatDau, ngayKetThuc, trangThai)
                 VALUES (?, ?, ?, ?, ?, 0, ?, ?, 'Đang hiệu lực')"
            )->execute([$maVoucher, $loaiGiam, $giaTriGiam, $soLuong, max(1, $gioiHanSuDung), $ngayBatDau, $ngayKetThuc]);

            $dk = is_array($duLieu['dieuKienApDung'] ?? null) ? $duLieu['dieuKienApDung'] : $duLieu;
            $this->pdo->prepare(
                "INSERT INTO DieuKienApDung (maKhuyenMai, maVoucher, giaTriToiThieu, nhomHang, doiTuong)
                 VALUES (NULL, ?, ?, ?, ?)"
            )->execute([$maVoucher, max(0, (float)($dk['giaTriToiThieu'] ?? 0)), $dk['nhomHang'] ?? 'Tất cả', $dk['doiTuong'] ?? 'Khách hàng đã đăng nhập']);

            $this->pdo->commit();
            return ['thanhCong' => true, 'thongBao' => 'Tạo mã voucher thành công', 'duLieu' => ['maVoucher' => $maVoucher], 'maHttp' => 201];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Không thể tạo voucher (có thể mã đã tồn tại): ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 409];
        }
    }

    public function capNhatVoucher(string $maVoucher, array $duLieu): array
    {
        $maVoucher = strtoupper(trim($maVoucher));
        $stmt = $this->pdo->prepare("SELECT * FROM Voucher WHERE maVoucher = ? LIMIT 1");
        $stmt->execute([$maVoucher]);
        $v = $stmt->fetch();
        if (!$v) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy mã voucher', 'duLieu' => null, 'maHttp' => 404];
        }

        $soLuong = (int)($duLieu['soLuong'] ?? $v['soLuong']);
        if ($soLuong < (int)$v['luotDaDung']) {
            return ['thanhCong' => false, 'thongBao' => 'Số lượng phát hành không được nhỏ hơn số lượt đã dùng', 'duLieu' => null, 'maHttp' => 400];
        }

        $this->pdo->prepare(
            "UPDATE Voucher SET loaiGiam = ?, giaTriGiam = ?, soLuong = ?, gioiHanSuDung = ?, ngayBatDau = ?, ngayKetThuc = ? WHERE maVoucher = ?"
        )->execute([
            $duLieu['loaiGiam'] ?? $v['loaiGiam'],
            (float)($duLieu['giaTriGiam'] ?? $v['giaTriGiam']),
            $soLuong,
            max(1, (int)($duLieu['gioiHanSuDung'] ?? $v['gioiHanSuDung'])),
            $duLieu['ngayBatDau'] ?? $v['ngayBatDau'],
            $duLieu['ngayKetThuc'] ?? $v['ngayKetThuc'],
            $maVoucher,
        ]);

        return ['thanhCong' => true, 'thongBao' => 'Cập nhật voucher thành công', 'duLieu' => ['maVoucher' => $maVoucher]];
    }

    public function capNhatTrangThaiVoucher(string $maVoucher, string $trangThai): array
    {
        $maVoucher = strtoupper(trim($maVoucher));
        if (!in_array($trangThai, ['Đang hiệu lực', 'Hết hạn', 'Đã khóa'], true)) {
            return ['thanhCong' => false, 'thongBao' => 'Trạng thái voucher không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $this->pdo->prepare("UPDATE Voucher SET trangThai = ? WHERE maVoucher = ?")->execute([$trangThai, $maVoucher]);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật trạng thái voucher thành công', 'duLieu' => ['maVoucher' => $maVoucher, 'trangThai' => $trangThai]];
    }

    public function xoaVoucher(string $maVoucher): array
    {
        $maVoucher = strtoupper(trim($maVoucher));
        $stmt = $this->pdo->prepare("SELECT luotDaDung FROM Voucher WHERE maVoucher = ? LIMIT 1");
        $stmt->execute([$maVoucher]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy mã voucher', 'duLieu' => null, 'maHttp' => 404];
        }

        // UC64: Chỉ cho phép xóa khi luotDaDung == 0; nếu > 0 phải dùng UC63 (Khóa voucher)
        if ((int)$row['luotDaDung'] > 0) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Voucher đã phát sinh lượt sử dụng (> 0), không được xóa — vui lòng chọn chức năng Khóa voucher (UC63)',
                'duLieu'    => null,
                'maHttp'    => 409,
            ];
        }

        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("DELETE FROM DieuKienApDung WHERE maVoucher = ?")->execute([$maVoucher]);
            $this->pdo->prepare("DELETE FROM Voucher WHERE maVoucher = ?")->execute([$maVoucher]);
            $this->pdo->commit();
            return ['thanhCong' => true, 'thongBao' => 'Đã xóa mã voucher chưa sử dụng thành công', 'duLieu' => ['maVoucher' => $maVoucher]];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa voucher: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 409];
        }
    }
}
