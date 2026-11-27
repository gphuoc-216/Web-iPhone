<?php
// Controller BCE: PhieuNhapCtrl (UC12 - UC14) — Phụ lục B.1 Lan03

class PhieuNhapCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function dinhKemChiTiet(array $dsPhieu): array
    {
        $tatCaCt = ChiTietPhieuNhap::timTheoPhieuNhap(null, $this->pdo);
        $ctTheoPhieu = [];
        foreach ($tatCaCt as $ct) {
            $ma = (int)$ct['maPhieuNhap'];
            $muc = ChiTietPhieuNhap::tuMang($ct)->chuyenThanhMang();
            $muc['tenDienThoai'] = $ct['tenDienThoai'] ?? '';
            $ctTheoPhieu[$ma][] = $muc;
        }
        $ketQua = [];
        foreach ($dsPhieu as $p) {
            $phieu = PhieuNhap::tuMang($p)->chuyenThanhMang();
            $phieu['tenNhanVien'] = $p['tenNhanVien'] ?? '';
            $phieu['chiTiet'] = $ctTheoPhieu[$phieu['maPhieuNhap']] ?? [];
            $ketQua[] = $phieu;
        }
        return $ketQua;
    }

    public function layDanhSachPhieuNhap(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách phiếu nhập thành công', 'duLieu' => $this->dinhKemChiTiet(PhieuNhap::timTatCa($this->pdo))];
    }

    public function layDanhSachPhieuChoDuyet(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách phiếu nhập chờ duyệt thành công', 'duLieu' => $this->dinhKemChiTiet(PhieuNhap::timTheoTrangThai('Chờ duyệt', $this->pdo))];
    }

    public function locPhieuNhap(?string $trangThai = null, ?string $tuNgay = null, ?string $denNgay = null): array
    {
        $ds = PhieuNhap::timTheoDieuKien($trangThai, $tuNgay, $denNgay, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lọc danh sách phiếu nhập thành công', 'duLieu' => $this->dinhKemChiTiet($ds)];
    }

    public function layChiTietPhieuNhap(int $maPhieuNhap): array
    {
        $p = PhieuNhap::timTheoMa($maPhieuNhap, false, $this->pdo);
        if (!$p) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy phiếu nhập', 'duLieu' => null, 'maHttp' => 404];
        }
        $phieu = PhieuNhap::tuMang($p)->chuyenThanhMang();
        $phieu['tenNhanVien'] = $p['tenNhanVien'] ?? '';
        $phieu['chiTiet'] = ChiTietPhieuNhap::timTheoPhieuNhap($maPhieuNhap, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết phiếu nhập thành công', 'duLieu' => $phieu];
    }

    // UC12: luuPhieuNhap(nguonNhap, ngayNhap, danhSachHang)
    public function luuPhieuNhap(array $duLieu, int $maNhanVien): array
    {
        $nguonNhap = trim((string)($duLieu['nguonNhap'] ?? ''));
        $ngayNhap = trim((string)($duLieu['ngayNhap'] ?? date('Y-m-d')));
        $danhSachHang = $duLieu['chiTiet'] ?? $duLieu['danhSachHang'] ?? [];

        if ($nguonNhap === '' || !is_array($danhSachHang) || empty($danhSachHang)) {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập nguồn nhập và ít nhất 1 dòng hàng chi tiết', 'duLieu' => null, 'maHttp' => 400];
        }

        foreach ($danhSachHang as $dong) {
            $maDt = (int)($dong['maDienThoai'] ?? 0);
            $sl = (int)($dong['soLuong'] ?? 0);
            $gia = (float)($dong['donGiaNhap'] ?? 0);
            if (!DienThoai::kiemTraTonTai($maDt, $this->pdo) || !ChiTietPhieuNhap::kiemTraHopLe($sl, $gia)) {
                return ['thanhCong' => false, 'thongBao' => 'Dữ liệu sản phẩm, số lượng hoặc đơn giá nhập không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
            }
        }

        try {
            batDauGiaoDich($this->pdo);
            $maPhieuNhap = PhieuNhap::luu($maNhanVien, $nguonNhap, $ngayNhap, 'Nháp', $this->pdo);
            foreach ($danhSachHang as $dong) {
                ChiTietPhieuNhap::luu($maPhieuNhap, (int)$dong['maDienThoai'], (int)$dong['soLuong'], (float)$dong['donGiaNhap'], $this->pdo);
            }
            xacNhanGiaoDich($this->pdo);
            return ['thanhCong' => true, 'thongBao' => 'Lưu phiếu nhập kho (Nháp) thành công', 'duLieu' => ['maPhieuNhap' => $maPhieuNhap, 'trangThai' => 'Nháp'], 'maHttp' => 201];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('luuPhieuNhap: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể tạo phiếu nhập kho', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function guiDuyetPhieuNhap(int $maPhieuNhap): array
    {
        if (!PhieuNhap::capNhatTrangThai($maPhieuNhap, 'Chờ duyệt', 'Nháp', $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Phiếu nhập không tồn tại hoặc không ở trạng thái Nháp', 'duLieu' => null, 'maHttp' => 400];
        }
        ThongBao::taoThongBaoTheoVaiTro('Ban Quản lý', "Có phiếu nhập kho #{$maPhieuNhap} mới đang chờ duyệt.", $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Đã gửi duyệt phiếu nhập và thông báo cho Ban Quản lý', 'duLieu' => ['maPhieuNhap' => $maPhieuNhap, 'trangThai' => 'Chờ duyệt']];
    }

    // UC13: duyetPhieuNhap(maPhieuNhap)
    public function duyetPhieuNhap(int $maPhieuNhap): array
    {
        try {
            batDauGiaoDich($this->pdo);

            $phieu = PhieuNhap::timTheoMa($maPhieuNhap, true, $this->pdo);
            if (!$phieu || $phieu['trangThai'] !== 'Chờ duyệt') {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Chỉ có thể duyệt phiếu nhập đang ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
            }

            $chiTiets = ChiTietPhieuNhap::timTheoPhieuNhap($maPhieuNhap, $this->pdo);
            foreach ($chiTiets as $ct) {
                $maDt = (int)$ct['maDienThoai'];
                $soLuongNhap = (int)$ct['soLuong'];
                $donGiaNhap = (float)$ct['donGiaNhap'];

                $dtRow = DienThoai::timTheoMa($maDt, $this->pdo);
                $tyLe = CauHinhLoiNhuan::layTyLeHienHanh($dtRow['hang'] ?? null, $this->pdo);

                $tonCu = TonKho::tangTonThucTe($maDt, $soLuongNhap, 1, $this->pdo);
                DienThoai::capNhatGiaVonBinhQuan($maDt, $tonCu, $soLuongNhap, $donGiaNhap, $this->pdo);
                DienThoai::tinhLaiGiaBan($maDt, $tyLe, $this->pdo);
            }

            PhieuNhap::capNhatTrangThai($maPhieuNhap, 'Đã duyệt', null, $this->pdo);
            $maTkNv = NhanVien::layMaTaiKhoan((int)$phieu['maNhanVien'], $this->pdo);
            if ($maTkNv) {
                ThongBao::taoThongBao($maTkNv, "Phiếu nhập kho #{$maPhieuNhap} đã được Ban Quản lý phê duyệt.", $this->pdo);
            }

            xacNhanGiaoDich($this->pdo);
            return ['thanhCong' => true, 'thongBao' => 'Duyệt phiếu nhập, cộng tồn kho và tính lại giá vốn bình quân thành công', 'duLieu' => ['maPhieuNhap' => $maPhieuNhap, 'trangThai' => 'Đã duyệt']];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('duyetPhieuNhap: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể duyệt phiếu nhập kho', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function tuChoiPhieuNhap(int $maPhieuNhap, string $lyDo): array
    {
        $lyDo = trim($lyDo);
        if ($lyDo === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập lý do từ chối phiếu nhập', 'duLieu' => null, 'maHttp' => 400];
        }
        if (!PhieuNhap::kiemTraChoDuyet($maPhieuNhap, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Phiếu nhập không tồn tại hoặc không ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
        }

        $phieu = PhieuNhap::timTheoMa($maPhieuNhap, false, $this->pdo);
        PhieuNhap::capNhatTrangThai($maPhieuNhap, 'Nháp', null, $this->pdo);

        $maTkNv = NhanVien::layMaTaiKhoan((int)$phieu['maNhanVien'], $this->pdo);
        if ($maTkNv) {
            ThongBao::taoThongBao($maTkNv, "Phiếu nhập #{$maPhieuNhap} bị từ chối và chuyển về Nháp. Lý do: {$lyDo}", $this->pdo);
        }

        return [
            'thanhCong' => true,
            'thongBao'  => 'Đã từ chối phiếu nhập, chuyển về trạng thái Nháp và gửi thông báo cho Nhân viên Kho',
            'duLieu'    => ['maPhieuNhap' => $maPhieuNhap, 'trangThai' => 'Nháp', 'lyDo' => $lyDo],
        ];
    }
}
