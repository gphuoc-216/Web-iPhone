<?php
// Controller BCE: HuyDonHangCtrl (UC31 - UC32, UC35 - UC38)

class HuyDonHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function guiThongBaoTheoVaiTro(string $vaiTro, string $noiDung): void
    {
        $stmt = $this->pdo->prepare("SELECT maTaiKhoan FROM TaiKhoan WHERE vaiTro = ? AND trangThai = 'Hoạt động'");
        $stmt->execute([$vaiTro]);
        $themTb = $this->pdo->prepare("INSERT INTO ThongBao (maNguoiNhan, noiDung, thoiDiemTao, daDoc) VALUES (?, ?, CURRENT_TIMESTAMP, FALSE)");
        foreach ($stmt->fetchAll() as $tk) {
            $themTb->execute([$tk['maTaiKhoan'], $noiDung]);
        }
    }

    // UC31: Khách hàng tự hủy trực tiếp đơn hàng đang ở trạng thái 'Chờ duyệt'
    public function tuHuyDonHang(int $maDonHang, int $maKhachHang, string $lyDo = 'Khách hàng tự hủy đơn'): array
    {
        try {
            $this->pdo->beginTransaction();

            $dhStmt = $this->pdo->prepare("SELECT * FROM DonHang WHERE maDonHang = ? AND maKhachHang = ? FOR UPDATE");
            $dhStmt->execute([$maDonHang, $maKhachHang]);
            $donHang = $dhStmt->fetch();

            if (!$donHang) {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy đơn hàng của bạn', 'duLieu' => null, 'maHttp' => 404];
            }

            if ($donHang['trangThai'] !== 'Chờ duyệt') {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Chỉ có thể tự hủy trực tiếp khi đơn hàng đang ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
            }

            // Nhả giuCho về tonKhaDung
            $ctStmt = $this->pdo->prepare("SELECT maDienThoai, soLuong FROM ChiTietDonHang WHERE maDonHang = ?");
            $ctStmt->execute([$maDonHang]);
            foreach ($ctStmt->fetchAll() as $ct) {
                $tonStmt = $this->pdo->prepare("SELECT * FROM TonKho WHERE maDienThoai = ? AND maKho = 1 FOR UPDATE");
                $tonStmt->execute([$ct['maDienThoai']]);
                if ($ton = $tonStmt->fetch()) {
                    $giuChoMoi = max(0, (int)$ton['giuCho'] - (int)$ct['soLuong']);
                    $khaDungMoi = (int)$ton['tonThucTe'] - $giuChoMoi;
                    $this->pdo->prepare("UPDATE TonKho SET giuCho = ?, tonKhaDung = ? WHERE maTonKho = ?")
                        ->execute([$giuChoMoi, $khaDungMoi, $ton['maTonKho']]);
                }
            }

            // Hoàn lượt Voucher
            if (!empty($donHang['maVoucher'])) {
                $this->pdo->prepare("UPDATE Voucher SET luotDaDung = GREATEST(0, luotDaDung - 1) WHERE maVoucher = ?")
                    ->execute([$donHang['maVoucher']]);
            }

            $this->pdo->prepare("UPDATE DonHang SET trangThai = 'Đã hủy' WHERE maDonHang = ?")->execute([$maDonHang]);
            $this->pdo->prepare("INSERT INTO YeuCauHuyDon (maDonHang, lyDo, ngayGui, trangThai) VALUES (?, ?, CURRENT_TIMESTAMP, 'Đã xác nhận')")
                ->execute([$maDonHang, trim($lyDo) ?: 'Khách hàng tự hủy đơn']);

            $trangThaiTtMoi = 'Đã hủy';
            $ttStmt = $this->pdo->prepare("SELECT * FROM ThanhToan WHERE maDonHang = ? FOR UPDATE");
            $ttStmt->execute([$maDonHang]);
            if ($tt = $ttStmt->fetch()) {
                $trangThaiTtMoi = ($tt['trangThai'] === 'Thành công') ? 'Cần hoàn tiền' : 'Đã hủy';
                $this->pdo->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maThanhToan = ?")
                    ->execute([$trangThaiTtMoi, $tt['maThanhToan']]);
                if ($trangThaiTtMoi === 'Cần hoàn tiền') {
                    $this->guiThongBaoTheoVaiTro('Nhân viên Bán hàng / CSKH', "Đơn hàng #{$maDonHang} (đã thanh toán trực tuyến) vừa được khách tự hủy và cần hoàn tiền.");
                }
            }

            $this->pdo->commit();
            return [
                'thanhCong' => true,
                'thongBao'  => 'Đã hủy đơn hàng thành công' . ($trangThaiTtMoi === 'Cần hoàn tiền' ? '. Yêu cầu hoàn tiền đã được chuyển tới bộ phận CSKH' : ''),
                'duLieu'    => ['maDonHang' => $maDonHang, 'trangThai' => 'Đã hủy', 'trangThaiThanhToan' => $trangThaiTtMoi],
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi hủy đơn hàng: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC32: Gửi yêu cầu hủy đơn (khi đơn đang ở Đã duyệt -> Đang giao)
    public function guiYeuCauHuyDon(int $maDonHang, string $lyDo): array
    {
        $lyDo = trim($lyDo);
        if ($maDonHang <= 0 || $lyDo === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng cung cấp mã đơn hàng và lý do hủy', 'duLieu' => null, 'maHttp' => 400];
        }

        $dhStmt = $this->pdo->prepare("SELECT trangThai FROM DonHang WHERE maDonHang = ? LIMIT 1");
        $dhStmt->execute([$maDonHang]);
        $trangThai = $dhStmt->fetchColumn();

        if (!$trangThai || $trangThai === 'Đã hủy' || $trangThai === 'Đã giao thành công') {
            return ['thanhCong' => false, 'thongBao' => 'Đơn hàng không thể gửi yêu cầu hủy ở trạng thái hiện tại', 'duLieu' => null, 'maHttp' => 400];
        }

        $this->pdo->prepare("INSERT INTO YeuCauHuyDon (maDonHang, lyDo, ngayGui, trangThai) VALUES (?, ?, CURRENT_TIMESTAMP, 'Chờ xử lý')")
            ->execute([$maDonHang, $lyDo]);
        $maYeuCau = (int)$this->pdo->lastInsertId();

        $this->guiThongBaoTheoVaiTro('Nhân viên Bán hàng / CSKH', "Có yêu cầu hủy đơn hàng #{$maDonHang} mới (Mã YC #{$maYeuCau}). Lý do: {$lyDo}");

        return ['thanhCong' => true, 'thongBao' => 'Gửi yêu cầu hủy đơn hàng thành công', 'duLieu' => ['maYeuCau' => $maYeuCau], 'maHttp' => 201];
    }

    public function layDanhSachYeuCauHuy(): array
    {
        $ds = $this->pdo->query(
            "SELECT yc.*, dh.tongTien, dh.trangThai AS trangThaiDonHang, kh.hoTen AS tenKhachHang,
                    tt.phuongThuc, tt.trangThai AS trangThaiThanhToan, tt.soTien, tt.soTienHoan
             FROM YeuCauHuyDon yc
             JOIN DonHang dh ON dh.maDonHang = yc.maDonHang
             JOIN KhachHang kh ON kh.maKhachHang = dh.maKhachHang
             LEFT JOIN ThanhToan tt ON tt.maDonHang = dh.maDonHang
             ORDER BY yc.maYeuCau DESC"
        )->fetchAll();

        $ketQua = [];
        foreach ($ds as $dong) {
            $muc = YeuCauHuyDon::tuMang($dong)->chuyenThanhMang();
            $muc['tongTien'] = (float)$dong['tongTien'];
            $muc['trangThaiDonHang'] = $dong['trangThaiDonHang'];
            $muc['tenKhachHang'] = $dong['tenKhachHang'];
            $muc['phuongThucThanhToan'] = $dong['phuongThuc'] ?? '';
            $muc['trangThaiThanhToan'] = $dong['trangThaiThanhToan'] ?? '';
            $muc['soTienHoan'] = (float)($dong['soTienHoan'] ?? 0);
            $ketQua[] = $muc;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách yêu cầu hủy đơn thành công', 'duLieu' => $ketQua];
    }

    public function xuLyYeuCauHuyDon(int $maYeuCau, bool $chapNhan, ?string $lyDoTuChoi = null): array
    {
        try {
            $this->pdo->beginTransaction();

            $ycStmt = $this->pdo->prepare("SELECT * FROM YeuCauHuyDon WHERE maYeuCau = ? FOR UPDATE");
            $ycStmt->execute([$maYeuCau]);
            $yc = $ycStmt->fetch();

            if (!$yc || $yc['trangThai'] !== 'Chờ xử lý') {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Yêu cầu hủy đơn không tồn tại hoặc đã được xử lý', 'duLieu' => null, 'maHttp' => 400];
            }

            $maDonHang = (int)$yc['maDonHang'];
            $dhStmt = $this->pdo->prepare(
                "SELECT dh.*, kh.maTaiKhoan
                 FROM DonHang dh
                 JOIN KhachHang kh ON kh.maKhachHang = dh.maKhachHang
                 WHERE dh.maDonHang = ? FOR UPDATE"
            );
            $dhStmt->execute([$maDonHang]);
            $donHang = $dhStmt->fetch();

            if (!$donHang) {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy đơn hàng liên quan', 'duLieu' => null, 'maHttp' => 404];
            }

            $daXuatKho = in_array($donHang['trangThai'], ['Đã giao shipper', 'Đang giao', 'Đã giao thành công'], true);

            // UC37: Đơn đã xuất kho -> bắt buộc từ chối hủy
            if ($chapNhan && $daXuatKho) {
                $this->pdo->rollBack();
                return [
                    'thanhCong' => false,
                    'thongBao'  => "Đơn hàng #{$maDonHang} đã xuất kho ('{$donHang['trangThai']}'), không thể xác nhận hủy — vui lòng chọn Từ chối hủy",
                    'duLieu'    => null,
                    'maHttp'    => 400,
                ];
            }

            if (!$chapNhan) {
                $lyDo = trim((string)($lyDoTuChoi ?: ($daXuatKho ? 'Đơn hàng đã xuất kho bàn giao vận chuyển' : '')));
                if ($lyDo === '') {
                    $this->pdo->rollBack();
                    return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập lý do từ chối yêu cầu hủy đơn', 'duLieu' => null, 'maHttp' => 400];
                }
                $this->pdo->prepare("UPDATE YeuCauHuyDon SET trangThai = 'Đã từ chối' WHERE maYeuCau = ?")->execute([$maYeuCau]);
                $this->pdo->prepare("INSERT INTO ThongBao (maNguoiNhan, noiDung, thoiDiemTao, daDoc) VALUES (?, ?, CURRENT_TIMESTAMP, FALSE)")
                    ->execute([$donHang['maTaiKhoan'], "Yêu cầu hủy đơn hàng #{$maDonHang} đã bị từ chối. Lý do: {$lyDo}"]);
                $this->pdo->commit();
                return ['thanhCong' => true, 'thongBao' => 'Đã từ chối yêu cầu hủy đơn hàng', 'duLieu' => ['maYeuCau' => $maYeuCau, 'trangThai' => 'Đã từ chối', 'lyDo' => $lyDo]];
            }

            // UC36: Xác nhận hủy khi đơn chưa xuất kho (Chờ duyệt / Đã duyệt / Đang đóng gói)
            $ctStmt = $this->pdo->prepare("SELECT maDienThoai, soLuong FROM ChiTietDonHang WHERE maDonHang = ?");
            $ctStmt->execute([$maDonHang]);
            foreach ($ctStmt->fetchAll() as $ct) {
                $tonStmt = $this->pdo->prepare("SELECT * FROM TonKho WHERE maDienThoai = ? AND maKho = 1 FOR UPDATE");
                $tonStmt->execute([$ct['maDienThoai']]);
                if ($ton = $tonStmt->fetch()) {
                    $giuChoMoi = max(0, (int)$ton['giuCho'] - (int)$ct['soLuong']);
                    $khaDungMoi = (int)$ton['tonThucTe'] - $giuChoMoi;
                    $this->pdo->prepare("UPDATE TonKho SET giuCho = ?, tonKhaDung = ? WHERE maTonKho = ?")
                        ->execute([$giuChoMoi, $khaDungMoi, $ton['maTonKho']]);
                }
            }

            // Hủy phiếu xuất ở trạng thái Nháp (nếu có)
            $this->pdo->prepare("UPDATE PhieuXuat SET trangThai = 'Đã hủy' WHERE maDonHang = ? AND trangThai = 'Nháp'")
                ->execute([$maDonHang]);

            // Hoàn lượt sử dụng Voucher (nếu đơn có áp dụng Voucher)
            if (!empty($donHang['maVoucher'])) {
                $this->pdo->prepare("UPDATE Voucher SET luotDaDung = GREATEST(0, luotDaDung - 1) WHERE maVoucher = ?")
                    ->execute([$donHang['maVoucher']]);
            }

            $this->pdo->prepare("UPDATE DonHang SET trangThai = 'Đã hủy' WHERE maDonHang = ?")->execute([$maDonHang]);
            $this->pdo->prepare("UPDATE YeuCauHuyDon SET trangThai = 'Đã xác nhận' WHERE maYeuCau = ?")->execute([$maYeuCau]);

            $trangThaiTtMoi = 'Đã hủy';
            $ttStmt = $this->pdo->prepare("SELECT * FROM ThanhToan WHERE maDonHang = ? FOR UPDATE");
            $ttStmt->execute([$maDonHang]);
            if ($tt = $ttStmt->fetch()) {
                $trangThaiTtMoi = ($tt['trangThai'] === 'Thành công') ? 'Cần hoàn tiền' : 'Đã hủy';
                $this->pdo->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maThanhToan = ?")
                    ->execute([$trangThaiTtMoi, $tt['maThanhToan']]);
            }

            $thongDiepKhach = "Đơn hàng #{$maDonHang} đã được xác nhận hủy." . ($trangThaiTtMoi === 'Cần hoàn tiền' ? ' Bộ phận CSKH đang tiến hành hoàn tiền cho bạn.' : '');
            $this->pdo->prepare("INSERT INTO ThongBao (maNguoiNhan, noiDung, thoiDiemTao, daDoc) VALUES (?, ?, CURRENT_TIMESTAMP, FALSE)")
                ->execute([$donHang['maTaiKhoan'], $thongDiepKhach]);

            $this->pdo->commit();
            return [
                'thanhCong' => true,
                'thongBao'  => 'Đã xác nhận hủy đơn hàng, nhả giữ chỗ tồn kho và hoàn lượt voucher',
                'duLieu'    => [
                    'maYeuCau'           => $maYeuCau,
                    'maDonHang'          => $maDonHang,
                    'trangThaiThanhToan' => $trangThaiTtMoi,
                ],
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi xử lý hủy đơn hàng: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function xuLyHoanTien(int $maDonHang, bool $hoanThanhCong = true): array
    {
        $ttStmt = $this->pdo->prepare("SELECT * FROM ThanhToan WHERE maDonHang = ? LIMIT 1");
        $ttStmt->execute([$maDonHang]);
        $tt = $ttStmt->fetch();

        if (!$tt || !in_array($tt['trangThai'], ['Cần hoàn tiền', 'Hoàn tiền lỗi'], true)) {
            return ['thanhCong' => false, 'thongBao' => 'Giao dịch thanh toán không ở trạng thái cần hoàn tiền', 'duLieu' => null, 'maHttp' => 400];
        }

        if ($hoanThanhCong) {
            $this->pdo->prepare("UPDATE ThanhToan SET soTienHoan = soTien, trangThai = 'Đã hoàn tiền' WHERE maThanhToan = ?")
                ->execute([$tt['maThanhToan']]);
            return ['thanhCong' => true, 'thongBao' => 'Thực hiện hoàn tiền mô phỏng thành công', 'duLieu' => ['maDonHang' => $maDonHang, 'trangThai' => 'Đã hoàn tiền', 'soTienHoan' => (float)$tt['soTien']]];
        }

        $this->pdo->prepare("UPDATE ThanhToan SET trangThai = 'Hoàn tiền lỗi' WHERE maThanhToan = ?")
            ->execute([$tt['maThanhToan']]);
        return ['thanhCong' => false, 'thongBao' => 'Hoàn tiền thất bại, hệ thống đã ghi nhận trạng thái Hoàn tiền lỗi', 'duLieu' => ['maDonHang' => $maDonHang, 'trangThai' => 'Hoàn tiền lỗi'], 'maHttp' => 502];
    }
}
