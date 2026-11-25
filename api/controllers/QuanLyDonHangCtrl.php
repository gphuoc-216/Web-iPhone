<?php
// Controller BCE: QuanLyDonHangCtrl (UC33 - UC34)

class QuanLyDonHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDanhSachDonHang(?string $trangThai = null): array
    {
        $sql = "SELECT dh.*, kh.hoTen AS tenKhachHang, kh.soDienThoai,
                       dc.diaChi AS diaChiGiaoHang, dc.nguoiNhan,
                       tt.phuongThuc, tt.trangThai AS trangThaiThanhToan,
                       px.maPhieuXuat, px.trangThai AS trangThaiPhieuXuat
                FROM DonHang dh
                JOIN KhachHang kh ON kh.maKhachHang = dh.maKhachHang
                JOIN DiaChiGiaoHang dc ON dc.maDiaChi = dh.maDiaChi
                LEFT JOIN ThanhToan tt ON tt.maDonHang = dh.maDonHang
                LEFT JOIN PhieuXuat px ON px.maDonHang = dh.maDonHang";
        $thamSo = [];
        if ($trangThai !== null && $trangThai !== '') {
            $sql .= " WHERE dh.trangThai = ?";
            $thamSo[] = $trangThai;
        }
        $sql .= " ORDER BY dh.ngayDat DESC, dh.maDonHang DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($thamSo);
        $dsDonHang = $stmt->fetchAll();

        $ctStmt = $this->pdo->query(
            "SELECT ct.*, d.ten AS tenDienThoai, d.hinhAnh
             FROM ChiTietDonHang ct
             LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai"
        );
        $chiTietTheoDon = [];
        foreach ($ctStmt->fetchAll() as $ct) {
            $ma = (int)$ct['maDonHang'];
            $muc = ChiTietDonHang::tuMang($ct)->chuyenThanhMang();
            $muc['tenDienThoai'] = $ct['tenDienThoai'] ?? '';
            $muc['hinhAnh'] = chuanHoaDuongDanAnh($ct['hinhAnh'] ?? '');
            $chiTietTheoDon[$ma][] = $muc;
        }

        $ketQua = [];
        foreach ($dsDonHang as $dh) {
            $muc = DonHang::tuMang($dh)->chuyenThanhMang();
            $muc['tenKhachHang'] = $dh['tenKhachHang'];
            $muc['soDienThoai'] = $dh['soDienThoai'];
            $muc['nguoiNhan'] = $dh['nguoiNhan'];
            $muc['diaChiGiaoHang'] = $dh['diaChiGiaoHang'];
            $muc['phuongThucThanhToan'] = $dh['phuongThuc'] ?? '';
            $muc['trangThaiThanhToan'] = $dh['trangThaiThanhToan'] ?? '';
            $muc['maPhieuXuat'] = isset($dh['maPhieuXuat']) ? (int)$dh['maPhieuXuat'] : null;
            $muc['trangThaiPhieuXuat'] = $dh['trangThaiPhieuXuat'] ?? null;
            $muc['chiTiet'] = $chiTietTheoDon[$muc['maDonHang']] ?? [];
            $ketQua[] = $muc;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách đơn hàng thành công', 'duLieu' => $ketQua];
    }

    public function capNhatTrangThaiDonHang(int $maDonHang, string $trangThaiMoi): array
    {
        $stmt = $this->pdo->prepare("SELECT trangThai FROM DonHang WHERE maDonHang = ? LIMIT 1");
        $stmt->execute([$maDonHang]);
        $trangThaiHienTai = $stmt->fetchColumn();

        if (!$trangThaiHienTai) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy đơn hàng', 'duLieu' => null, 'maHttp' => 404];
        }

        // Thứ tự trạng thái bắt buộc theo UC33 - UC34 (Đã giao shipper do UC16 PhieuXuatCtrl tự động chuyển)
        $luongHopLe = [
            'Chờ duyệt'       => ['Đã duyệt'],
            'Đã duyệt'        => ['Đang đóng gói'],
            'Đang đóng gói'   => [],
            'Đã giao shipper' => ['Đang giao'],
            'Đang giao'       => ['Đã giao thành công'],
        ];

        if ($trangThaiMoi === 'Đã giao shipper') {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Trạng thái Đã giao shipper được cập nhật tự động khi Nhân viên Kho xác nhận xuất kho (UC16)',
                'duLieu'    => null,
                'maHttp'    => 400,
            ];
        }

        $cacTrangThaiKeTiep = $luongHopLe[$trangThaiHienTai] ?? [];
        if (!in_array($trangThaiMoi, $cacTrangThaiKeTiep, true)) {
            return [
                'thanhCong' => false,
                'thongBao'  => "Không thể chuyển đơn hàng từ '{$trangThaiHienTai}' sang '{$trangThaiMoi}' (sai thứ tự vòng đời đơn hàng)",
                'duLieu'    => null,
                'maHttp'    => 400,
            ];
        }

        // UC34: Chặn chuyển sang 'Đang giao' nếu chưa có phiếu xuất 'Đã xuất'
        if ($trangThaiMoi === 'Đang giao') {
            $pxStmt = $this->pdo->prepare("SELECT trangThai FROM PhieuXuat WHERE maDonHang = ? AND trangThai = 'Đã xuất' LIMIT 1");
            $pxStmt->execute([$maDonHang]);
            if (!$pxStmt->fetch()) {
                return [
                    'thanhCong' => false,
                    'thongBao'  => 'Không thể chuyển sang Đang giao vì đơn hàng chưa có phiếu xuất kho ở trạng thái Đã xuất',
                    'duLieu'    => null,
                    'maHttp'    => 400,
                ];
            }
        }

        $this->pdo->prepare("UPDATE DonHang SET trangThai = ? WHERE maDonHang = ?")->execute([$trangThaiMoi, $maDonHang]);

        if ($trangThaiMoi === 'Đã giao thành công') {
            $this->pdo->prepare("UPDATE ThanhToan SET trangThai = 'Thành công' WHERE maDonHang = ? AND trangThai = 'Chưa thu tiền'")
                ->execute([$maDonHang]);
        }

        return [
            'thanhCong' => true,
            'thongBao'  => "Cập nhật trạng thái đơn hàng #{$maDonHang} sang '{$trangThaiMoi}' thành công",
            'duLieu'    => ['maDonHang' => $maDonHang, 'trangThai' => $trangThaiMoi],
        ];
    }
}
