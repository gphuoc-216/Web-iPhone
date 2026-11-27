<?php
// Controller BCE: KiemKeCtrl (UC18 - UC21)
// Đồng bộ 1-1 Báo cáo v4: Bảng 27 - Bảng 30, Sequence Hình 26 - Hình 29 (Mô hình 1 kho duy nhất, 3NF)

class KiemKeCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function dinhKemChiTiet(array $ds): array
    {
        $tatCaCt = ChiTietKiemKe::timTheoBienBan(null, $this->pdo);
        $ctTheoBb = [];
        foreach ($tatCaCt as $ct) {
            $ma = (int)$ct['maBienBan'];
            $muc = ChiTietKiemKe::tuMang($ct)->chuyenThanhMang();
            $muc['tenDienThoai'] = $ct['tenDienThoai'] ?? '';
            $ctTheoBb[$ma][] = $muc;
        }
        $ketQua = [];
        foreach ($ds as $bb) {
            $muc = BienBanKiemKe::tuMang($bb)->chuyenThanhMang();
            $muc['tenNhanVien'] = $bb['tenNhanVien'] ?? '';
            $muc['tenNguoiDuyet'] = $bb['tenNguoiDuyet'] ?? '';
            $muc['chiTiet'] = $ctTheoBb[$muc['maBienBan']] ?? [];
            $ketQua[] = $muc;
        }
        return $ketQua;
    }

    // [UC18 - Hình 26] KiemKeCtrl::layDanhSachBienBanKiemKe -> BienBanKiemKe::timTatCa
    public function layDanhSachBienBanKiemKe(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách biên bản kiểm kê thành công', 'duLieu' => $this->dinhKemChiTiet(BienBanKiemKe::timTatCa($this->pdo))];
    }

    // [UC19 - Hình 27] KiemKeCtrl::layDanhSachBienBanChoDuyet -> BienBanKiemKe::timTheoTrangThai
    public function layDanhSachBienBanChoDuyet(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách biên bản chờ duyệt thành công', 'duLieu' => $this->dinhKemChiTiet(BienBanKiemKe::timTheoTrangThai('Chờ duyệt', $this->pdo))];
    }

    // [UC19 - Hình 27] KiemKeCtrl::layChiTietBienBan -> BienBanKiemKe::timTheoMa -> ChiTietKiemKe::timTheoBienBan
    public function layChiTietBienBan(int $maBienBan): array
    {
        $bb = BienBanKiemKe::timTheoMa($maBienBan, false, $this->pdo);
        if (!$bb) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy biên bản kiểm kê', 'duLieu' => null, 'maHttp' => 404];
        }
        $muc = BienBanKiemKe::tuMang($bb)->chuyenThanhMang();
        $muc['tenNhanVien'] = $bb['tenNhanVien'] ?? '';
        $muc['tenNguoiDuyet'] = $bb['tenNguoiDuyet'] ?? '';
        $muc['chiTiet'] = ChiTietKiemKe::timTheoBienBan($maBienBan, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết biên bản kiểm kê thành công', 'duLieu' => $muc];
    }

    // [UC18 - Hình 26] KiemKeCtrl::taoDotKiemKe -> BienBanKiemKe::taoMoi -> TonKho::layTonKhoHienHanh
    public function taoDotKiemKe(int $maNhanVien): array
    {
        $maBienBan = BienBanKiemKe::taoMoi($maNhanVien, null, null, 'Nháp', $this->pdo);
        return [
            'thanhCong' => true,
            'thongBao'  => 'Tạo đợt kiểm kê thành công',
            'duLieu'    => [
                'maBienBan'     => $maBienBan,
                'tonKhoHienTai' => TonKho::layTonKhoHienHanh($this->pdo),
            ],
            'maHttp'    => 201,
        ];
    }

    // [UC18 - Hình 26] KiemKeCtrl::ghiNhanSoLuongThucTe -> ChiTietKiemKe::kiemTraHopLe -> ChiTietKiemKe::tinhChenhLech -> ChiTietKiemKe::luu
    public function ghiNhanSoLuongThucTe(int $maBienBan, array $danhSachSoLuong): array
    {
        if (!ChiTietKiemKe::kiemTraHopLe($danhSachSoLuong)) {
            return ['thanhCong' => false, 'thongBao' => 'Danh sách số lượng kiểm kê thực tế không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        foreach ($danhSachSoLuong as $dong) {
            $kq = ChiTietKiemKe::tinhChenhLech((int)$dong['maDienThoai'], (int)$dong['soLuongThucTe'], $this->pdo);
            ChiTietKiemKe::luu($maBienBan, (int)$dong['maDienThoai'], $kq['soLuongHeThong'], $kq['soLuongThucTe'], $this->pdo);
        }
        return ['thanhCong' => true, 'thongBao' => 'Ghi nhận số lượng thực tế thành công', 'duLieu' => ['maBienBan' => $maBienBan]];
    }

    // [UC18 - Hình 26] KiemKeCtrl::luuNhapKiemKe -> BienBanKiemKe::luuTienDo
    public function luuNhapKiemKe(int $maBienBan): array
    {
        BienBanKiemKe::luuTienDo($maBienBan, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lưu nháp tiến độ kiểm kê thành công', 'duLieu' => ['maBienBan' => $maBienBan]];
    }

    // [UC18 - Hình 26] KiemKeCtrl::hoanTatKiemKe -> ChiTietKiemKe::kiemTraHopLe -> ChiTietKiemKe::tinhChenhLech -> BienBanKiemKe::taoMoi -> ChiTietKiemKe::luu -> BienBanKiemKe::kiemTraCoChenhLech
    public function hoanTatKiemKe(array $duLieu, int $maNhanVien): array
    {
        $ghiChu = trim((string)($duLieu['ghiChu'] ?? ''));
        $chiTiets = $duLieu['chiTiet'] ?? $duLieu['danhSachSoLuong'] ?? [];

        if (!ChiTietKiemKe::kiemTraHopLe($chiTiets)) {
            return ['thanhCong' => false, 'thongBao' => 'Biên bản kiểm kê cần có ít nhất 1 sản phẩm với số lượng hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $coChenhLech = false;
        $danhSachDaTinh = [];
        foreach ($chiTiets as $dong) {
            $maDt = (int)$dong['maDienThoai'];
            $kq = ChiTietKiemKe::tinhChenhLech($maDt, (int)$dong['soLuongThucTe'], $this->pdo);
            if ($kq['chenhLech'] !== 0) {
                $coChenhLech = true;
            }
            $danhSachDaTinh[] = array_merge(['maDienThoai' => $maDt], $kq);
        }

        if ($coChenhLech && $ghiChu === '') {
            return ['thanhCong' => false, 'thongBao' => 'Phát hiện chênh lệch tồn kho, bắt buộc phải nhập ghi chú giải trình', 'duLieu' => null, 'maHttp' => 400];
        }

        $trangThai = $coChenhLech ? 'Chờ duyệt' : 'Hoàn tất';

        try {
            batDauGiaoDich($this->pdo);
            $maBienBan = BienBanKiemKe::taoMoi($maNhanVien, null, $ghiChu !== '' ? $ghiChu : null, $trangThai, $this->pdo);
            foreach ($danhSachDaTinh as $d) {
                ChiTietKiemKe::luu($maBienBan, $d['maDienThoai'], $d['soLuongHeThong'], $d['soLuongThucTe'], $this->pdo);
            }
            $coChenhLechDb = BienBanKiemKe::kiemTraCoChenhLech($maBienBan, $this->pdo);
            if ($coChenhLechDb) {
                ThongBao::taoThongBaoTheoVaiTro('Ban Quản lý', "Có biên bản kiểm kê #{$maBienBan} phát sinh chênh lệch đang chờ duyệt.", $this->pdo);
            }
            xacNhanGiaoDich($this->pdo);

            return [
                'thanhCong' => true,
                'thongBao'  => $coChenhLechDb
                    ? 'Lập biên bản kiểm kê có chênh lệch, đã chuyển Chờ duyệt và thông báo Ban Quản lý'
                    : 'Kiểm kê khớp tồn hệ thống, đã lưu biên bản ở trạng thái Hoàn tất',
                'duLieu'    => ['maBienBan' => $maBienBan, 'trangThai' => $trangThai, 'coChenhLech' => $coChenhLechDb],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('hoanTatKiemKe: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể lập biên bản kiểm kê', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // [UC19 - Hình 27] KiemKeCtrl::duyetBienBan -> BienBanKiemKe::timTheoMa -> ChiTietKiemKe::timTheoBienBan -> TonKho::dieuChinhTonThucTe -> BienBanKiemKe::capNhatTrangThai -> TonKho::kiemTraTonThieuGiuCho
    public function duyetBienBan(int $maBienBan, ?int $maNguoiDuyet = null): array
    {
        try {
            batDauGiaoDich($this->pdo);

            $bb = BienBanKiemKe::timTheoMa($maBienBan, true, $this->pdo);
            if (!$bb || $bb['trangThai'] !== 'Chờ duyệt') {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Chỉ có thể duyệt biên bản kiểm kê đang ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
            }

            foreach (ChiTietKiemKe::timTheoBienBan($maBienBan, $this->pdo) as $ct) {
                $maDt = (int)$ct['maDienThoai'];
                $thucTeMoi = (int)$ct['soLuongThucTe'];
                TonKho::dieuChinhTonThucTe($maDt, $thucTeMoi, $this->pdo);
            }

            BienBanKiemKe::capNhatTrangThai($maBienBan, 'Đã duyệt', null, $maNguoiDuyet, $this->pdo);
            $donBiAnhHuong = TonKho::kiemTraTonThieuGiuCho($this->pdo);

            if (!empty($donBiAnhHuong)) {
                $dsMaDon = implode(', ', array_map(fn($d) => '#DH' . $d['maDonHang'], $donBiAnhHuong));
                ThongBao::taoThongBaoTheoVaiTro(
                    'Nhân viên Bán hàng / CSKH',
                    "Cảnh báo sau kiểm kê #{$maBienBan}: Phát hiện tồn thực tế nhỏ hơn số lượng giữ chỗ trên đơn {$dsMaDon}.",
                    $this->pdo
                );
            }

            if (!empty($bb['maTaiKhoan'])) {
                ThongBao::taoThongBao((int)$bb['maTaiKhoan'], "Biên bản kiểm kê #{$maBienBan} đã được Ban Quản lý phê duyệt.", $this->pdo);
            }

            xacNhanGiaoDich($this->pdo);

            $thongBao = 'Duyệt biên bản kiểm kê và cập nhật tồn kho thành công';
            if (!empty($donBiAnhHuong)) {
                $thongBao .= '. Cảnh báo: phát hiện sản phẩm có tồn thực tế nhỏ hơn số lượng giữ chỗ của đơn hàng!';
            }

            return [
                'thanhCong' => true,
                'thongBao'  => $thongBao,
                'duLieu'    => ['maBienBan' => $maBienBan, 'trangThai' => 'Đã duyệt', 'canhBaoDonAnhHuong' => $donBiAnhHuong],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('duyetBienBan: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể duyệt biên bản kiểm kê', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // [UC19 - Hình 27 luồng phụ 5.1] KiemKeCtrl::tuChoiBienBan -> BienBanKiemKe::capNhatTrangThai -> ThongBao::taoThongBao
    public function tuChoiBienBan(int $maBienBan, ?string $lyDo = null, ?int $maNguoiDuyet = null): array
    {
        $lyDoSach = trim((string)($lyDo ?? ''));
        if ($lyDoSach === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập lý do từ chối / yêu cầu kiểm kê lại', 'duLieu' => null, 'maHttp' => 400];
        }
        $bb = BienBanKiemKe::timTheoMa($maBienBan, false, $this->pdo);
        if (!$bb || $bb['trangThai'] !== 'Chờ duyệt') {
            return ['thanhCong' => false, 'thongBao' => 'Biên bản kiểm kê không ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
        }
        BienBanKiemKe::capNhatTrangThai($maBienBan, 'Cần kiểm lại', $lyDoSach, $maNguoiDuyet, $this->pdo);
        if (!empty($bb['maTaiKhoan'])) {
            ThongBao::taoThongBao((int)$bb['maTaiKhoan'], "Biên bản kiểm kê #{$maBienBan} được yêu cầu kiểm lại. Lý do: {$lyDoSach}", $this->pdo);
        }
        return ['thanhCong' => true, 'thongBao' => 'Đã chuyển biên bản sang trạng thái Cần kiểm lại', 'duLieu' => ['maBienBan' => $maBienBan, 'trangThai' => 'Cần kiểm lại', 'lyDoTuChoi' => $lyDoSach]];
    }

    // [UC20 - Hình 28] KiemKeCtrl::layDanhSachCanhBao -> CanhBaoTonKho::layNguongCauHinh -> TonKho::timTheoDieuKienCanhBao
    public function layDanhSachCanhBao(?string $loaiCanhBao = null): array
    {
        $nguong = CanhBaoTonKho::layNguongCauHinh(null, $this->pdo);
        $danhSachCanhBao = TonKho::timTheoDieuKienCanhBao($loaiCanhBao, $nguong, $this->pdo);
        return [
            'thanhCong' => true,
            'thongBao'  => 'Lấy danh sách cảnh báo tồn kho thành công',
            'duLieu'    => [
                'cauHinhNguong'   => $nguong,
                'danhSachCanhBao' => $danhSachCanhBao,
            ],
        ];
    }

    // [UC20 - Hình 28] KiemKeCtrl::layChiTietTonKho -> TonKho::timTheoDienThoai
    public function layChiTietTonKho(int $maDienThoai): array
    {
        $ton = TonKho::timTheoDienThoai($maDienThoai, false, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết tồn kho thành công', 'duLieu' => $ton];
    }

    // [UC21 - Hình 29] KiemKeCtrl::layDanhSachNguong -> CanhBaoTonKho::timTatCa
    public function layDanhSachNguong(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách ngưỡng tồn kho thành công', 'duLieu' => CanhBaoTonKho::timTatCa($this->pdo)];
    }

    // [UC21 - Hình 29] KiemKeCtrl::luuNguongTonKho -> CanhBaoTonKho::kiemTraHopLeNguong -> CanhBaoTonKho::luuNguong
    public function luuNguongTonKho(array $duLieu): array
    {
        $phamVi = trim((string)($duLieu['phamVi'] ?? 'Toàn bộ sản phẩm'));
        $nguongToiThieu = (int)($duLieu['nguongToiThieu'] ?? 5);
        $nguongSom = (int)($duLieu['nguongSom'] ?? 15);
        $nguongToiDa = (int)($duLieu['nguongToiDa'] ?? 350);
        $maDanhMuc = !empty($duLieu['maDanhMuc']) ? (int)$duLieu['maDanhMuc'] : null;
        $maDienThoai = !empty($duLieu['maDienThoai']) ? (int)$duLieu['maDienThoai'] : null;

        if (!CanhBaoTonKho::kiemTraHopLeNguong($nguongSom, $nguongToiThieu, $nguongToiDa)) {
            return ['thanhCong' => false, 'thongBao' => 'Ngưỡng cảnh báo phải thỏa điều kiện: 0 <= ngưỡng tối thiểu < ngưỡng sớm < ngưỡng tối đa', 'duLieu' => null, 'maHttp' => 400];
        }

        $maNguong = CanhBaoTonKho::luuNguong($phamVi, $nguongSom, $nguongToiThieu, $nguongToiDa, $maDanhMuc, $maDienThoai, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật ngưỡng cảnh báo tồn kho thành công', 'duLieu' => ['maNguong' => $maNguong]];
    }
}
