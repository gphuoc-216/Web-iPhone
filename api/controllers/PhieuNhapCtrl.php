<?php
// Controller BCE: PhieuNhapCtrl (UC12 - UC14)

class PhieuNhapCtrl
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

    private function guiThongBaoTheoNhanVien(int $maNhanVien, string $noiDung): void
    {
        $stmt = $this->pdo->prepare("SELECT maTaiKhoan FROM NhanVien WHERE maNhanVien = ? LIMIT 1");
        $stmt->execute([$maNhanVien]);
        $maTaiKhoan = $stmt->fetchColumn();
        if ($maTaiKhoan) {
            $this->pdo->prepare("INSERT INTO ThongBao (maNguoiNhan, noiDung, thoiDiemTao, daDoc) VALUES (?, ?, CURRENT_TIMESTAMP, FALSE)")
                ->execute([$maTaiKhoan, $noiDung]);
        }
    }

    public function layDanhSachPhieuNhap(?string $trangThai = null): array
    {
        $sql = "SELECT pn.*, nv.hoTen AS tenNhanVien
                FROM PhieuNhap pn
                LEFT JOIN NhanVien nv ON nv.maNhanVien = pn.maNhanVien";
        $thamSo = [];
        if ($trangThai !== null && $trangThai !== '') {
            $sql .= " WHERE pn.trangThai = ?";
            $thamSo[] = $trangThai;
        }
        $sql .= " ORDER BY pn.maPhieuNhap DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($thamSo);
        $dsPhieu = $stmt->fetchAll();

        $ctStmt = $this->pdo->query(
            "SELECT ct.*, d.ten AS tenDienThoai
             FROM ChiTietPhieuNhap ct
             LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai"
        );
        $chiTietTheoPhieu = [];
        foreach ($ctStmt->fetchAll() as $ct) {
            $ma = (int)$ct['maPhieuNhap'];
            $muc = ChiTietPhieuNhap::tuMang($ct)->chuyenThanhMang();
            $muc['tenDienThoai'] = $ct['tenDienThoai'] ?? '';
            $chiTietTheoPhieu[$ma][] = $muc;
        }

        $ketQua = [];
        foreach ($dsPhieu as $p) {
            $phieu = PhieuNhap::tuMang($p)->chuyenThanhMang();
            $phieu['tenNhanVien'] = $p['tenNhanVien'] ?? '';
            $phieu['chiTiet'] = $chiTietTheoPhieu[$phieu['maPhieuNhap']] ?? [];
            $ketQua[] = $phieu;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách phiếu nhập thành công', 'duLieu' => $ketQua];
    }

    public function taoPhieuNhap(array $duLieu, int $maNhanVien): array
    {
        $nguonNhap = trim((string)($duLieu['nguonNhap'] ?? ''));
        $ngayNhap = trim((string)($duLieu['ngayNhap'] ?? date('Y-m-d')));
        $danhSachChiTiet = $duLieu['chiTiet'] ?? [];

        if ($nguonNhap === '' || !is_array($danhSachChiTiet) || empty($danhSachChiTiet)) {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập nguồn nhập và ít nhất 1 dòng chi tiết phiếu nhập', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare(
                "INSERT INTO PhieuNhap (nguonNhap, ngayNhap, trangThai, maNhanVien) VALUES (?, ?, 'Nháp', ?)"
            )->execute([$nguonNhap, $ngayNhap, $maNhanVien]);
            $maPhieuNhap = (int)$this->pdo->lastInsertId();

            $themCt = $this->pdo->prepare(
                "INSERT INTO ChiTietPhieuNhap (maPhieuNhap, maDienThoai, soLuong, donGiaNhap) VALUES (?, ?, ?, ?)"
            );
            foreach ($danhSachChiTiet as $dong) {
                $maDienThoai = (int)($dong['maDienThoai'] ?? 0);
                $soLuong = (int)($dong['soLuong'] ?? 0);
                $donGiaNhap = (float)($dong['donGiaNhap'] ?? 0);
                if ($maDienThoai <= 0 || $soLuong <= 0 || $donGiaNhap <= 0) {
                    throw new RuntimeException('Dữ liệu chi tiết phiếu nhập không hợp lệ (số lượng và đơn giá phải > 0)');
                }
                $themCt->execute([$maPhieuNhap, $maDienThoai, $soLuong, $donGiaNhap]);
            }

            $this->pdo->commit();
            return ['thanhCong' => true, 'thongBao' => 'Tạo phiếu nhập ở trạng thái Nháp thành công', 'duLieu' => ['maPhieuNhap' => $maPhieuNhap, 'trangThai' => 'Nháp'], 'maHttp' => 201];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Không thể tạo phiếu nhập: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 400];
        }
    }

    public function guiDuyetPhieuNhap(int $maPhieuNhap): array
    {
        $capNhat = $this->pdo->prepare("UPDATE PhieuNhap SET trangThai = 'Chờ duyệt' WHERE maPhieuNhap = ? AND trangThai = 'Nháp'");
        $capNhat->execute([$maPhieuNhap]);
        if ($capNhat->rowCount() === 0) {
            return ['thanhCong' => false, 'thongBao' => 'Phiếu nhập không tồn tại hoặc không ở trạng thái Nháp', 'duLieu' => null, 'maHttp' => 400];
        }

        $this->guiThongBaoTheoVaiTro('Ban Quản lý', "Có phiếu nhập kho #{$maPhieuNhap} mới đang chờ duyệt.");
        return ['thanhCong' => true, 'thongBao' => 'Đã gửi duyệt phiếu nhập và thông báo cho Ban Quản lý', 'duLieu' => ['maPhieuNhap' => $maPhieuNhap, 'trangThai' => 'Chờ duyệt']];
    }

    public function duyetPhieuNhap(int $maPhieuNhap): array
    {
        try {
            $this->pdo->beginTransaction();

            $phieuStmt = $this->pdo->prepare("SELECT * FROM PhieuNhap WHERE maPhieuNhap = ? FOR UPDATE");
            $phieuStmt->execute([$maPhieuNhap]);
            $phieu = $phieuStmt->fetch();

            if (!$phieu || $phieu['trangThai'] !== 'Chờ duyệt') {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Chỉ có thể duyệt phiếu nhập đang ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
            }

            $tyLeMacDinh = (float)($this->pdo->query("SELECT tyLe FROM CauHinhLoiNhuan ORDER BY maCauHinh DESC LIMIT 1")->fetchColumn() ?: 25.0);

            $ctStmt = $this->pdo->prepare("SELECT * FROM ChiTietPhieuNhap WHERE maPhieuNhap = ?");
            $ctStmt->execute([$maPhieuNhap]);
            $chiTiets = $ctStmt->fetchAll();

            foreach ($chiTiets as $ct) {
                $maDt = (int)$ct['maDienThoai'];
                $soLuongNhap = (int)$ct['soLuong'];
                $donGiaNhap = (float)$ct['donGiaNhap'];

                $tonStmt = $this->pdo->prepare("SELECT * FROM TonKho WHERE maDienThoai = ? AND maKho = 1 FOR UPDATE");
                $tonStmt->execute([$maDt]);
                $ton = $tonStmt->fetch();

                $dtStmt = $this->pdo->prepare("SELECT giaVonBinhQuan, hang FROM DienThoai WHERE maDienThoai = ? FOR UPDATE");
                $dtStmt->execute([$maDt]);
                $dtRow = $dtStmt->fetch();
                $giaVonCu = (float)($dtRow['giaVonBinhQuan'] ?? 0);

                // Tìm tỷ lệ lợi nhuận theo hãng trước, nếu không có dùng mặc định toàn bộ
                $tyLeStmt = $this->pdo->prepare("SELECT tyLe FROM CauHinhLoiNhuan WHERE phamVi = ? LIMIT 1");
                $tyLeStmt->execute([$dtRow['hang'] ?? '']);
                $tyLeApDung = (float)($tyLeStmt->fetchColumn() ?: $tyLeMacDinh);

                $tonCu = $ton ? (int)$ton['tonThucTe'] : 0;
                $tongSoLuongMoi = $tonCu + $soLuongNhap;
                $giaVonMoi = $tongSoLuongMoi > 0
                    ? round((($tonCu * $giaVonCu) + ($soLuongNhap * $donGiaNhap)) / $tongSoLuongMoi, 2)
                    : $donGiaNhap;
                $giaBanMoi = round($giaVonMoi * (1 + $tyLeApDung / 100), 2);

                if ($ton) {
                    $tonThucTeMoi = $tonCu + $soLuongNhap;
                    $tonKhaDungMoi = $tonThucTeMoi - (int)$ton['giuCho'];
                    $this->pdo->prepare("UPDATE TonKho SET tonThucTe = ?, tonKhaDung = ? WHERE maTonKho = ?")
                        ->execute([$tonThucTeMoi, $tonKhaDungMoi, $ton['maTonKho']]);
                } else {
                    $this->pdo->prepare("INSERT INTO TonKho (maDienThoai, maKho, tonThucTe, giuCho, tonKhaDung) VALUES (?, 1, ?, 0, ?)")
                        ->execute([$maDt, $soLuongNhap, $soLuongNhap]);
                }

                $this->pdo->prepare("UPDATE DienThoai SET giaVonBinhQuan = ?, giaBan = ? WHERE maDienThoai = ?")
                    ->execute([$giaVonMoi, $giaBanMoi, $maDt]);
            }

            $this->pdo->prepare("UPDATE PhieuNhap SET trangThai = 'Đã duyệt' WHERE maPhieuNhap = ?")->execute([$maPhieuNhap]);
            $this->guiThongBaoTheoNhanVien((int)$phieu['maNhanVien'], "Phiếu nhập kho #{$maPhieuNhap} đã được Ban Quản lý phê duyệt.");

            $this->pdo->commit();
            return ['thanhCong' => true, 'thongBao' => 'Duyệt phiếu nhập, cộng tồn kho và tính lại giá vốn bình quân thành công', 'duLieu' => ['maPhieuNhap' => $maPhieuNhap, 'trangThai' => 'Đã duyệt']];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi duyệt phiếu nhập: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function tuChoiPhieuNhap(int $maPhieuNhap, string $lyDo): array
    {
        $lyDo = trim($lyDo);
        if ($lyDo === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập lý do từ chối phiếu nhập', 'duLieu' => null, 'maHttp' => 400];
        }

        $phieuStmt = $this->pdo->prepare("SELECT maNhanVien, trangThai FROM PhieuNhap WHERE maPhieuNhap = ? LIMIT 1");
        $phieuStmt->execute([$maPhieuNhap]);
        $phieu = $phieuStmt->fetch();

        if (!$phieu || $phieu['trangThai'] !== 'Chờ duyệt') {
            return ['thanhCong' => false, 'thongBao' => 'Phiếu nhập không tồn tại hoặc không ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
        }

        $this->pdo->prepare("UPDATE PhieuNhap SET trangThai = 'Nháp' WHERE maPhieuNhap = ?")->execute([$maPhieuNhap]);
        $this->guiThongBaoTheoNhanVien((int)$phieu['maNhanVien'], "Phiếu nhập #{$maPhieuNhap} bị từ chối và chuyển về Nháp. Lý do: {$lyDo}");

        return [
            'thanhCong' => true,
            'thongBao'  => 'Đã từ chối phiếu nhập, chuyển về trạng thái Nháp và gửi thông báo cho Nhân viên Kho',
            'duLieu'    => ['maPhieuNhap' => $maPhieuNhap, 'trangThai' => 'Nháp', 'lyDo' => $lyDo],
        ];
    }
}
