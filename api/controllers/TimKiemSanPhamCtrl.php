<?php
// Controller BCE: TimKiemSanPhamCtrl (UC7 - UC9)

class TimKiemSanPhamCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Lấy mức ưu đãi (%) cao nhất đang hiệu lực cho từng sản phẩm
    private function layBanDoKhuyenMaiHieuLuc(): array
    {
        $sql = "SELECT kmdt.maDienThoai, MAX(km.mucUuDai) AS mucUuDaiCaoNhat, MAX(km.ten) AS tenKhuyenMai
                FROM KhuyenMai_DienThoai kmdt
                JOIN KhuyenMai km ON km.maKhuyenMai = kmdt.maKhuyenMai
                WHERE km.trangThai = 'Đang áp dụng'
                  AND CURDATE() BETWEEN km.ngayBatDau AND km.ngayKetThuc
                GROUP BY kmdt.maDienThoai";
        $banDo = [];
        foreach ($this->pdo->query($sql)->fetchAll() as $dong) {
            $banDo[(int)$dong['maDienThoai']] = [
                'mucUuDai'     => (float)$dong['mucUuDaiCaoNhat'],
                'tenKhuyenMai' => $dong['tenKhuyenMai'],
            ];
        }
        return $banDo;
    }

    // UC7 & UC8: Tìm kiếm theo từ khóa & lọc đa điều kiện (hãng, khoảng giá, RAM, ROM, pin)
    public function timKiemVaLocDienThoai(array $boLoc = []): array
    {
        if (isset($boLoc['giaTu'], $boLoc['giaDen']) && $boLoc['giaTu'] !== '' && $boLoc['giaDen'] !== '') {
            if ((float)$boLoc['giaTu'] > (float)$boLoc['giaDen']) {
                return ['thanhCong' => false, 'thongBao' => 'Khoảng giá không hợp lệ (Giá từ phải nhỏ hơn hoặc bằng Giá đến)', 'duLieu' => null, 'maHttp' => 400];
            }
        }

        $dieuKien = ["d.trangThai = 'Đang kinh doanh'"];
        $thamSo = [];

        $tuKhoa = trim((string)($boLoc['tuKhoa'] ?? ''));
        if ($tuKhoa !== '') {
            $dieuKien[] = "(d.ten LIKE ? OR d.hang LIKE ? OR ts.chip LIKE ?)";
            $mau = '%' . $tuKhoa . '%';
            $thamSo[] = $mau;
            $thamSo[] = $mau;
            $thamSo[] = $mau;
        }

        if (!empty($boLoc['maDanhMuc'])) {
            $dieuKien[] = "d.maDanhMuc = ?";
            $thamSo[] = (int)$boLoc['maDanhMuc'];
        }
        if (!empty($boLoc['hang'])) {
            $dieuKien[] = "d.hang = ?";
            $thamSo[] = trim((string)$boLoc['hang']);
        }
        if (isset($boLoc['giaTu']) && $boLoc['giaTu'] !== '') {
            $dieuKien[] = "d.giaBan >= ?";
            $thamSo[] = (float)$boLoc['giaTu'];
        }
        if (isset($boLoc['giaDen']) && $boLoc['giaDen'] !== '') {
            $dieuKien[] = "d.giaBan <= ?";
            $thamSo[] = (float)$boLoc['giaDen'];
        }
        if (!empty($boLoc['ram'])) {
            $dieuKien[] = "ts.ram = ?";
            $thamSo[] = (int)$boLoc['ram'];
        }
        if (!empty($boLoc['rom'])) {
            $dieuKien[] = "ts.rom = ?";
            $thamSo[] = (int)$boLoc['rom'];
        }
        if (!empty($boLoc['pinTu'])) {
            $dieuKien[] = "ts.dungLuongPin >= ?";
            $thamSo[] = (int)$boLoc['pinTu'];
        }

        $sapXepHopLe = [
            'giaTangDan' => 'd.giaBan ASC',
            'giaGiamDan' => 'd.giaBan DESC',
            'moiNhat'    => 'd.maDienThoai DESC',
        ];
        $sapXep = $sapXepHopLe[$boLoc['sapXep'] ?? ''] ?? 'd.maDienThoai DESC';

        $sql = "SELECT d.*, dm.ten AS tenDanhMuc,
                       ts.manHinh, ts.chip, ts.ram, ts.rom, ts.camera, ts.dungLuongPin, ts.heDieuHanh,
                       COALESCE(tk.tonThucTe, 0) AS tonThucTe,
                       COALESCE(tk.giuCho, 0) AS giuCho,
                       COALESCE(tk.tonKhaDung, 0) AS tonKhaDung
                FROM DienThoai d
                LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                LEFT JOIN ThongSoKyThuat ts ON ts.maDienThoai = d.maDienThoai
                LEFT JOIN TonKho tk ON tk.maDienThoai = d.maDienThoai AND tk.maKho = 1
                WHERE " . implode(' AND ', $dieuKien) . "
                ORDER BY {$sapXep}";

        $cauLenh = $this->pdo->prepare($sql);
        $cauLenh->execute($thamSo);

        $banDoKm = $this->layBanDoKhuyenMaiHieuLuc();
        $ketQua = [];

        foreach ($cauLenh->fetchAll() as $dong) {
            $sp = DienThoai::tuMang($dong)->chuyenThanhMang();
            $maDt = (int)$sp['maDienThoai'];
            $km = $banDoKm[$maDt] ?? null;
            $mucUuDai = $km ? $km['mucUuDai'] : 0.0;
            $giaBanCuoi = $mucUuDai > 0 ? round($sp['giaBan'] * (1 - $mucUuDai / 100), 2) : $sp['giaBan'];
            $tonKhaDung = (int)$dong['tonKhaDung'];

            $sp['hinhAnh'] = chuanHoaDuongDanAnh($sp['hinhAnh']);
            $sp['tenDanhMuc'] = $dong['tenDanhMuc'] ?? '';
            $sp['giaGoc'] = $sp['giaBan'];
            $sp['mucUuDaiKhuyenMai'] = $mucUuDai;
            $sp['tenKhuyenMai'] = $km ? $km['tenKhuyenMai'] : null;
            $sp['giaBanCuoi'] = $giaBanCuoi;
            $sp['tonKhaDung'] = $tonKhaDung;
            $sp['trangThaiTonKho'] = $tonKhaDung > 0 ? 'Còn hàng' : 'Hết hàng';
            $sp['thongSoKyThuat'] = [
                'manHinh'      => $dong['manHinh'] ?? null,
                'chip'         => $dong['chip'] ?? null,
                'ram'          => (int)($dong['ram'] ?? 0),
                'rom'          => (int)($dong['rom'] ?? 0),
                'camera'       => $dong['camera'] ?? null,
                'dungLuongPin' => (int)($dong['dungLuongPin'] ?? 0),
                'heDieuHanh'   => $dong['heDieuHanh'] ?? null,
            ];
            $ketQua[] = $sp;
        }

        return ['thanhCong' => true, 'thongBao' => 'Tìm kiếm sản phẩm thành công', 'duLieu' => $ketQua];
    }

    // UC9: Xem chi tiết sản phẩm (giá gốc, giá bán cuối trừ KhuyenMai, trạng thái Còn hàng/Hết hàng)
    public function xemChiTietDienThoai(int $maDienThoai): array
    {
        $sql = "SELECT d.*, dm.ten AS tenDanhMuc,
                       ts.maThongSo, ts.manHinh, ts.chip, ts.ram, ts.rom, ts.camera, ts.dungLuongPin, ts.heDieuHanh,
                       COALESCE(tk.tonThucTe, 0) AS tonThucTe,
                       COALESCE(tk.giuCho, 0) AS giuCho,
                       COALESCE(tk.tonKhaDung, 0) AS tonKhaDung
                FROM DienThoai d
                LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                LEFT JOIN ThongSoKyThuat ts ON ts.maDienThoai = d.maDienThoai
                LEFT JOIN TonKho tk ON tk.maDienThoai = d.maDienThoai AND tk.maKho = 1
                WHERE d.maDienThoai = ? AND d.trangThai = 'Đang kinh doanh'
                LIMIT 1";
        $cauLenh = $this->pdo->prepare($sql);
        $cauLenh->execute([$maDienThoai]);
        $dong = $cauLenh->fetch();

        if (!$dong) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy sản phẩm điện thoại', 'duLieu' => null, 'maHttp' => 404];
        }

        $kmStmt = $this->pdo->prepare(
            "SELECT km.* FROM KhuyenMai km
             JOIN KhuyenMai_DienThoai kmdt ON kmdt.maKhuyenMai = km.maKhuyenMai
             WHERE kmdt.maDienThoai = ? AND km.trangThai = 'Đang áp dụng'
               AND CURDATE() BETWEEN km.ngayBatDau AND km.ngayKetThuc
             ORDER BY km.mucUuDai DESC"
        );
        $kmStmt->execute([$maDienThoai]);
        $khuyenMaiApDung = [];
        $mucUuDaiCaoNhat = 0.0;
        foreach ($kmStmt->fetchAll() as $km) {
            $mangKm = KhuyenMai::tuMang($km)->chuyenThanhMang();
            $khuyenMaiApDung[] = $mangKm;
            if ($mangKm['mucUuDai'] > $mucUuDaiCaoNhat) {
                $mucUuDaiCaoNhat = $mangKm['mucUuDai'];
            }
        }

        $chiTiet = DienThoai::tuMang($dong)->chuyenThanhMang();
        $tonKhaDung = (int)$dong['tonKhaDung'];
        $giaBanCuoi = $mucUuDaiCaoNhat > 0
            ? round($chiTiet['giaBan'] * (1 - $mucUuDaiCaoNhat / 100), 2)
            : $chiTiet['giaBan'];

        $chiTiet['hinhAnh'] = chuanHoaDuongDanAnh($chiTiet['hinhAnh']);
        $chiTiet['tenDanhMuc'] = $dong['tenDanhMuc'] ?? '';
        $chiTiet['giaGoc'] = $chiTiet['giaBan'];
        $chiTiet['mucUuDaiKhuyenMai'] = $mucUuDaiCaoNhat;
        $chiTiet['giaBanCuoi'] = $giaBanCuoi;
        $chiTiet['trangThaiTonKho'] = $tonKhaDung > 0 ? 'Còn hàng' : 'Hết hàng';
        $chiTiet['thongSoKyThuat'] = ThongSoKyThuat::tuMang($dong)->chuyenThanhMang();
        $chiTiet['tonKho'] = [
            'tonThucTe'  => (int)$dong['tonThucTe'],
            'giuCho'     => (int)$dong['giuCho'],
            'tonKhaDung' => $tonKhaDung,
        ];
        $chiTiet['khuyenMaiApDung'] = $khuyenMaiApDung;

        return ['thanhCong' => true, 'thongBao' => 'Lấy chi tiết điện thoại thành công', 'duLieu' => $chiTiet];
    }
}
