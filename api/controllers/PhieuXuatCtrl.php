<?php
// Controller BCE: PhieuXuatCtrl (UC15 - UC17)

class PhieuXuatCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDanhSachPhieuXuat(): array
    {
        $sql = "SELECT px.*, nv.hoTen AS tenNhanVien, dh.trangThai AS trangThaiDonHang
                FROM PhieuXuat px
                LEFT JOIN NhanVien nv ON nv.maNhanVien = px.maNhanVien
                LEFT JOIN DonHang dh ON dh.maDonHang = px.maDonHang
                ORDER BY px.maPhieuXuat DESC";
        $dsPhieu = $this->pdo->query($sql)->fetchAll();

        $ctStmt = $this->pdo->query(
            "SELECT ct.*, d.ten AS tenDienThoai
             FROM ChiTietPhieuXuat ct
             LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai"
        );
        $chiTietTheoPhieu = [];
        foreach ($ctStmt->fetchAll() as $ct) {
            $ma = (int)$ct['maPhieuXuat'];
            $muc = ChiTietPhieuXuat::tuMang($ct)->chuyenThanhMang();
            $muc['tenDienThoai'] = $ct['tenDienThoai'] ?? '';
            $chiTietTheoPhieu[$ma][] = $muc;
        }

        $ketQua = [];
        foreach ($dsPhieu as $p) {
            $phieu = PhieuXuat::tuMang($p)->chuyenThanhMang();
            $phieu['tenNhanVien'] = $p['tenNhanVien'] ?? '';
            $phieu['trangThaiDonHang'] = $p['trangThaiDonHang'] ?? '';
            $phieu['chiTiet'] = $chiTietTheoPhieu[$phieu['maPhieuXuat']] ?? [];
            $ketQua[] = $phieu;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách phiếu xuất thành công', 'duLieu' => $ketQua];
    }

    public function taoPhieuXuatTuDonHang(int $maDonHang, int $maNhanVien, ?string $ghiChu = null): array
    {
        try {
            $this->pdo->beginTransaction();

            $dhStmt = $this->pdo->prepare("SELECT * FROM DonHang WHERE maDonHang = ? FOR UPDATE");
            $dhStmt->execute([$maDonHang]);
            $donHang = $dhStmt->fetch();

            if (!$donHang || !in_array($donHang['trangThai'], ['Đã duyệt', 'Đang đóng gói'], true)) {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Chỉ có thể lập phiếu xuất cho đơn hàng ở trạng thái Đã duyệt hoặc Đang đóng gói', 'duLieu' => null, 'maHttp' => 400];
            }

            // Mỗi đơn hàng tối đa 1 phiếu xuất
            $kiemTraTonTai = $this->pdo->prepare("SELECT maPhieuXuat FROM PhieuXuat WHERE maDonHang = ? LIMIT 1");
            $kiemTraTonTai->execute([$maDonHang]);
            if ($kiemTraTonTai->fetch()) {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Đơn hàng này đã có phiếu xuất kho, mỗi đơn hàng chỉ được lập tối đa 1 phiếu xuất', 'duLieu' => null, 'maHttp' => 409];
            }

            $this->pdo->prepare(
                "INSERT INTO PhieuXuat (maDonHang, ngayLap, ghiChu, trangThai, maNhanVien) VALUES (?, CURDATE(), ?, 'Nháp', ?)"
            )->execute([$maDonHang, $ghiChu, $maNhanVien]);
            $maPhieuXuat = (int)$this->pdo->lastInsertId();

            $this->pdo->prepare(
                "INSERT INTO ChiTietPhieuXuat (maPhieuXuat, maDienThoai, soLuong)
                 SELECT ?, maDienThoai, soLuong FROM ChiTietDonHang WHERE maDonHang = ?"
            )->execute([$maPhieuXuat, $maDonHang]);

            $this->pdo->prepare("UPDATE DonHang SET trangThai = 'Đang đóng gói' WHERE maDonHang = ?")->execute([$maDonHang]);

            $this->pdo->commit();
            return ['thanhCong' => true, 'thongBao' => 'Tạo phiếu xuất kho (Nháp) thành công', 'duLieu' => ['maPhieuXuat' => $maPhieuXuat, 'trangThai' => 'Nháp'], 'maHttp' => 201];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Không thể lập phiếu xuất kho: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 400];
        }
    }

    public function xacNhanXuatKho(int $maPhieuXuat): array
    {
        try {
            $this->pdo->beginTransaction();

            $pxStmt = $this->pdo->prepare("SELECT * FROM PhieuXuat WHERE maPhieuXuat = ? FOR UPDATE");
            $pxStmt->execute([$maPhieuXuat]);
            $phieu = $pxStmt->fetch();

            if (!$phieu || $phieu['trangThai'] !== 'Nháp') {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Phiếu xuất không tồn tại hoặc không ở trạng thái Nháp', 'duLieu' => null, 'maHttp' => 400];
            }

            $ctStmt = $this->pdo->prepare("SELECT * FROM ChiTietPhieuXuat WHERE maPhieuXuat = ?");
            $ctStmt->execute([$maPhieuXuat]);
            foreach ($ctStmt->fetchAll() as $ct) {
                $tonStmt = $this->pdo->prepare("SELECT * FROM TonKho WHERE maDienThoai = ? AND maKho = 1 FOR UPDATE");
                $tonStmt->execute([$ct['maDienThoai']]);
                $ton = $tonStmt->fetch();

                $sl = (int)$ct['soLuong'];
                if (!$ton || (int)$ton['tonThucTe'] < $sl) {
                    throw new RuntimeException('Tồn thực tế trong kho không đủ để xuất sản phẩm mã ' . $ct['maDienThoai']);
                }

                // Trừ tonThucTe và giảm giuCho (không trừ tonKhaDung lần 2 vì tonKhaDung = tonThucTe - giuCho)
                $tonThucTeMoi = (int)$ton['tonThucTe'] - $sl;
                $giuChoMoi = max(0, (int)$ton['giuCho'] - $sl);
                $tonKhaDungMoi = $tonThucTeMoi - $giuChoMoi;

                $this->pdo->prepare("UPDATE TonKho SET tonThucTe = ?, giuCho = ?, tonKhaDung = ? WHERE maTonKho = ?")
                    ->execute([$tonThucTeMoi, $giuChoMoi, $tonKhaDungMoi, $ton['maTonKho']]);
            }

            $this->pdo->prepare("UPDATE PhieuXuat SET trangThai = 'Đã xuất' WHERE maPhieuXuat = ?")->execute([$maPhieuXuat]);
            $this->pdo->prepare("UPDATE DonHang SET trangThai = 'Đã giao shipper' WHERE maDonHang = ?")->execute([$phieu['maDonHang']]);

            $this->pdo->commit();
            return [
                'thanhCong' => true,
                'thongBao'  => 'Xác nhận xuất kho thành công và tự động chuyển đơn hàng sang Đã giao shipper',
                'duLieu'    => ['maPhieuXuat' => $maPhieuXuat, 'maDonHang' => (int)$phieu['maDonHang'], 'trangThai' => 'Đã xuất'],
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi xác nhận xuất kho: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 400];
        }
    }

    public function huyPhieuXuat(int $maPhieuXuat): array
    {
        $capNhat = $this->pdo->prepare("UPDATE PhieuXuat SET trangThai = 'Đã hủy' WHERE maPhieuXuat = ? AND trangThai = 'Nháp'");
        $capNhat->execute([$maPhieuXuat]);
        if ($capNhat->rowCount() === 0) {
            return ['thanhCong' => false, 'thongBao' => 'Chỉ được hủy phiếu xuất ở trạng thái Nháp', 'duLieu' => null, 'maHttp' => 400];
        }
        return ['thanhCong' => true, 'thongBao' => 'Hủy phiếu xuất thành công', 'duLieu' => ['maPhieuXuat' => $maPhieuXuat, 'trangThai' => 'Đã hủy']];
    }
}
