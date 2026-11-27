<?php
// Controller BCE: KiemKeCtrl (UC18 - UC21) — Phụ lục B.1 Lan03

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
            $muc['chiTiet'] = $ctTheoBb[$muc['maBienBan']] ?? [];
            $ketQua[] = $muc;
        }
        return $ketQua;
    }

    public function layDanhSachBienBanKiemKe(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách biên bản kiểm kê thành công', 'duLieu' => $this->dinhKemChiTiet(BienBanKiemKe::timTatCa($this->pdo))];
    }

    public function layDanhSachBienBanChoDuyet(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách biên bản chờ duyệt thành công', 'duLieu' => $this->dinhKemChiTiet(BienBanKiemKe::timTheoTrangThai('Chờ duyệt', $this->pdo))];
    }

    public function layChiTietBienBan(int $maBienBan): array
    {
        $bb = BienBanKiemKe::timTheoMa($maBienBan, false, $this->pdo);
        if (!$bb) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy biên bản kiểm kê', 'duLieu' => null, 'maHttp' => 404];
        }
        $muc = BienBanKiemKe::tuMang($bb)->chuyenThanhMang();
        $muc['chiTiet'] = ChiTietKiemKe::timTheoBienBan($maBienBan, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết biên bản kiểm kê thành công', 'duLieu' => $muc];
    }

    public function taoDotKiemKe(int $maKho, int $maNhanVien): array
    {
        $maBienBan = BienBanKiemKe::taoMoi($maKho, $maNhanVien, null, 'Nháp', $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Tạo đợt kiểm kê thành công', 'duLieu' => ['maBienBan' => $maBienBan, 'tonKhoHienTai' => TonKho::layTonTheoKho($maKho, $this->pdo)], 'maHttp' => 201];
    }

    public function ghiNhanSoLuongThucTe(int $maBienBan, array $danhSachSoLuong, int $maKho = 1): array
    {
        if (!ChiTietKiemKe::kiemTraHopLe($danhSachSoLuong)) {
            return ['thanhCong' => false, 'thongBao' => 'Danh sách số lượng kiểm kê thực tế không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        foreach ($danhSachSoLuong as $dong) {
            $kq = ChiTietKiemKe::tinhChenhLech((int)$dong['maDienThoai'], (int)$dong['soLuongThucTe'], $maKho, $this->pdo);
            ChiTietKiemKe::luu($maBienBan, (int)$dong['maDienThoai'], $kq['soLuongHeThong'], $kq['soLuongThucTe'], $kq['chenhLech'], $this->pdo);
        }
        return ['thanhCong' => true, 'thongBao' => 'Ghi nhận số lượng thực tế thành công', 'duLieu' => ['maBienBan' => $maBienBan]];
    }

    public function luuNhapKiemKe(int $maBienBan): array
    {
        BienBanKiemKe::luuTienDo($maBienBan, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lưu nháp tiến độ kiểm kê thành công', 'duLieu' => ['maBienBan' => $maBienBan]];
    }

    public function hoanTatKiemKe(array $duLieu, int $maNhanVien): array
    {
        $maKho = (int)($duLieu['maKho'] ?? 1);
        $ghiChu = trim((string)($duLieu['ghiChu'] ?? ''));
        $chiTiets = $duLieu['chiTiet'] ?? $duLieu['danhSachSoLuong'] ?? [];

        if (!ChiTietKiemKe::kiemTraHopLe($chiTiets)) {
            return ['thanhCong' => false, 'thongBao' => 'Biên bản kiểm kê cần có ít nhất 1 sản phẩm với số lượng hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $coChenhLech = false;
        $danhSachDaTinh = [];
        foreach ($chiTiets as $dong) {
            $maDt = (int)$dong['maDienThoai'];
            $kq = ChiTietKiemKe::tinhChenhLech($maDt, (int)$dong['soLuongThucTe'], $maKho, $this->pdo);
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
            $maBienBan = BienBanKiemKe::taoMoi($maKho, $maNhanVien, $ghiChu !== '' ? $ghiChu : null, $trangThai, $this->pdo);
            foreach ($danhSachDaTinh as $d) {
                ChiTietKiemKe::luu($maBienBan, $d['maDienThoai'], $d['soLuongHeThong'], $d['soLuongThucTe'], $d['chenhLech'], $this->pdo);
            }
            if ($coChenhLech) {
                ThongBao::taoThongBaoTheoVaiTro('Ban Quản lý', "Có biên bản kiểm kê #{$maBienBan} phát sinh chênh lệch đang chờ duyệt.", $this->pdo);
            }
            xacNhanGiaoDich($this->pdo);

            return [
                'thanhCong' => true,
                'thongBao'  => $coChenhLech
                    ? 'Lập biên bản kiểm kê có chênh lệch, đã chuyển Chờ duyệt và thông báo Ban Quản lý'
                    : 'Kiểm kê khớp tồn hệ thống, đã lưu biên bản ở trạng thái Hoàn tất',
                'duLieu'    => ['maBienBan' => $maBienBan, 'trangThai' => $trangThai, 'coChenhLech' => $coChenhLech],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('hoanTatKiemKe: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể lập biên bản kiểm kê', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC19: duyetBienBan(maBienBan)
    public function duyetBienBan(int $maBienBan): array
    {
        try {
            batDauGiaoDich($this->pdo);

            $bb = BienBanKiemKe::timTheoMa($maBienBan, true, $this->pdo);
            if (!$bb || $bb['trangThai'] !== 'Chờ duyệt') {
                hoanTacGiaoDich($this->pdo);
                return ['thanhCong' => false, 'thongBao' => 'Chỉ có thể duyệt biên bản kiểm kê đang ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
            }

            $canhBaoDonAnhHuong = [];
            foreach (ChiTietKiemKe::timTheoBienBan($maBienBan, $this->pdo) as $ct) {
                $maDt = (int)$ct['maDienThoai'];
                $thucTeMoi = (int)$ct['soLuongThucTe'];
                $kq = TonKho::dieuChinhTonThucTe($maDt, $thucTeMoi, (int)$bb['maKho'], $this->pdo);
                if ($kq['thieuGiuCho']) {
                    $donBiAnhHuong = DonHang::timDonAnhHuongTheoDienThoai($maDt, $this->pdo);
                    $canhBaoDonAnhHuong[] = [
                        'maDienThoai'   => $maDt,
                        'tonThucTeMoi'  => $thucTeMoi,
                        'giuChoHienTai' => $kq['giuCho'],
                        'donAnhHuong'   => $donBiAnhHuong,
                    ];
                    ThongBao::taoThongBaoTheoVaiTro(
                        'Nhân viên Bán hàng / CSKH',
                        "Cảnh báo sau kiểm kê #{$maBienBan}: Sản phẩm mã #{$maDt} có tồn thực tế ({$thucTeMoi}) nhỏ hơn số lượng giữ chỗ ({$kq['giuCho']}).",
                        $this->pdo
                    );
                }
            }

            BienBanKiemKe::capNhatTrangThai($maBienBan, 'Đã duyệt', $this->pdo);
            xacNhanGiaoDich($this->pdo);

            $thongBao = 'Duyệt biên bản kiểm kê và cập nhật tồn kho thành công';
            if (!empty($canhBaoDonAnhHuong)) {
                $thongBao .= '. Cảnh báo: phát hiện sản phẩm có tồn thực tế nhỏ hơn số lượng giữ chỗ của đơn hàng!';
            }

            return [
                'thanhCong' => true,
                'thongBao'  => $thongBao,
                'duLieu'    => ['maBienBan' => $maBienBan, 'trangThai' => 'Đã duyệt', 'canhBaoDonAnhHuong' => $canhBaoDonAnhHuong],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('duyetBienBan: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể duyệt biên bản kiểm kê', 'duLieu' => null, 'maHttp' => 500];
        }
    }

    // UC19: tuChoiBienBan(maBienBan, lyDo)
    public function tuChoiBienBan(int $maBienBan, ?string $lyDo = null): array
    {
        $bb = BienBanKiemKe::timTheoMa($maBienBan, false, $this->pdo);
        if (!$bb || $bb['trangThai'] !== 'Chờ duyệt') {
            return ['thanhCong' => false, 'thongBao' => 'Biên bản kiểm kê không ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
        }
        BienBanKiemKe::capNhatTrangThai($maBienBan, 'Cần kiểm lại', $this->pdo);
        if (!empty($bb['maTaiKhoan'])) {
            ThongBao::taoThongBao((int)$bb['maTaiKhoan'], "Biên bản kiểm kê #{$maBienBan} được yêu cầu kiểm lại." . ($lyDo ? " Lý do: {$lyDo}" : ''), $this->pdo);
        }
        return ['thanhCong' => true, 'thongBao' => 'Đã chuyển biên bản sang trạng thái Cần kiểm lại', 'duLieu' => ['maBienBan' => $maBienBan, 'trangThai' => 'Cần kiểm lại']];
    }

    // UC20: layDanhSachCanhBao(loaiCanhBao) & layChiTietTonKho(maDienThoai)
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

    public function layChiTietTonKho(int $maDienThoai): array
    {
        $ton = TonKho::timTheoDienThoai($maDienThoai, 1, false, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết tồn kho thành công', 'duLieu' => $ton];
    }

    // UC21: layDanhSachNguong() & luuNguongTonKho(phamVi, nguongSom, nguongToiThieu, nguongToiDa)
    public function layDanhSachNguong(): array
    {
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách ngưỡng tồn kho thành công', 'duLieu' => CanhBaoTonKho::timTatCa($this->pdo)];
    }

    public function luuNguongTonKho(array $duLieu): array
    {
        $phamVi = trim((string)($duLieu['phamVi'] ?? 'Toàn bộ sản phẩm'));
        $nguongToiThieu = (int)($duLieu['nguongToiThieu'] ?? 5);
        $nguongSom = (int)($duLieu['nguongSom'] ?? 15);
        $nguongToiDa = (int)($duLieu['nguongToiDa'] ?? 350);

        if (!CanhBaoTonKho::kiemTraHopLeNguong($nguongSom, $nguongToiThieu, $nguongToiDa)) {
            return ['thanhCong' => false, 'thongBao' => 'Ngưỡng cảnh báo phải thỏa điều kiện: 0 <= ngưỡng tối thiểu < ngưỡng sớm < ngưỡng tối đa', 'duLieu' => null, 'maHttp' => 400];
        }

        $maNguong = CanhBaoTonKho::luuNguong($phamVi, $nguongSom, $nguongToiThieu, $nguongToiDa, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật ngưỡng cảnh báo tồn kho thành công', 'duLieu' => ['maNguong' => $maNguong]];
    }
}
