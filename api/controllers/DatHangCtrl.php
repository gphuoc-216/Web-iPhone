<?php
// Controller BCE: DatHangCtrl (UC25 - UC29)

class DatHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // UC26: Tính phí vận chuyển dựa trên maDiaChi hoặc maQuyTac + nguongMienPhi (dùng mucPhiMacDinh nếu chưa có quy tắc riêng)
    public function tinhPhiVanChuyen(?int $maDiaChi, float $tongTamTinh, ?int $maQuyTac = null): array
    {
        $quyTac = null;
        if ($maDiaChi !== null && $maDiaChi > 0) {
            $stmt = $this->pdo->prepare(
                "SELECT qt.* FROM DiaChiGiaoHang dc
                 JOIN QuyTacPhiVanChuyen qt ON qt.maQuyTac = dc.maQuyTac
                 WHERE dc.maDiaChi = ? LIMIT 1"
            );
            $stmt->execute([$maDiaChi]);
            $quyTac = $stmt->fetch();
        } elseif ($maQuyTac !== null && $maQuyTac > 0) {
            $stmt = $this->pdo->prepare("SELECT * FROM QuyTacPhiVanChuyen WHERE maQuyTac = ? LIMIT 1");
            $stmt->execute([$maQuyTac]);
            $quyTac = $stmt->fetch();
        }

        if (!$quyTac) {
            // Khu vực chưa có quy tắc riêng -> dùng mucPhiMacDinh
            $macDinh = (float)($this->pdo->query("SELECT mucPhiMacDinh FROM QuyTacPhiVanChuyen ORDER BY maQuyTac ASC LIMIT 1")->fetchColumn() ?: 35000.0);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Áp dụng mức phí vận chuyển mặc định',
                'duLieu'    => [
                    'khuVuc'        => 'Mặc định',
                    'phiVanChuyen'  => $macDinh,
                    'nguongMienPhi' => 0.0,
                ],
            ];
        }

        $nguongMienPhi = (float)$quyTac['nguongMienPhi'];
        $phiVanChuyen = ($nguongMienPhi > 0 && $tongTamTinh >= $nguongMienPhi) ? 0.0 : (float)$quyTac['mucPhi'];

        return [
            'thanhCong' => true,
            'thongBao'  => 'Tính phí vận chuyển thành công',
            'duLieu'    => [
                'khuVuc'        => $quyTac['khuVuc'],
                'phiVanChuyen'  => $phiVanChuyen,
                'nguongMienPhi' => $nguongMienPhi,
            ],
        ];
    }

    // UC28 & UC29 Bước 1: Khóa giỏ hàng + Giữ chỗ tạm + Tạo ThanhToan (COD tạo đơn ngay; Trực tuyến chờ kết quả thanh toán)
    public function datHang(int $maKhachHang, array $duLieu): array
    {
        $maGioHang = (int)($duLieu['maGioHang'] ?? 0);
        $maDiaChi = (int)($duLieu['maDiaChi'] ?? 0);
        $phuongThuc = trim((string)($duLieu['phuongThuc'] ?? 'Thanh toán khi nhận hàng'));

        if ($maGioHang <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Mã giỏ hàng không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            $this->pdo->beginTransaction();

            // UC25: Hỗ trợ nhập địa chỉ mới trực tiếp khi đặt hàng (kèm tùy chọn lưu sổ địa chỉ)
            if ($maDiaChi <= 0) {
                $nguoiNhan = trim((string)($duLieu['nguoiNhan'] ?? ''));
                $soDienThoai = trim((string)($duLieu['soDienThoai'] ?? ''));
                $diaChi = trim((string)($duLieu['diaChi'] ?? ''));
                $maQuyTac = (int)($duLieu['maQuyTac'] ?? 1);
                $macDinh = !empty($duLieu['luuVaoSoDiaChi']) && !empty($duLieu['macDinh']);

                if ($nguoiNhan === '' || $soDienThoai === '' || $diaChi === '') {
                    $this->pdo->rollBack();
                    return ['thanhCong' => false, 'thongBao' => 'Vui lòng chọn địa chỉ giao hàng hoặc nhập đầy đủ thông tin địa chỉ mới', 'duLieu' => null, 'maHttp' => 400];
                }
                if ($macDinh) {
                    $this->pdo->prepare("UPDATE DiaChiGiaoHang SET macDinh = FALSE WHERE maKhachHang = ?")->execute([$maKhachHang]);
                }
                $this->pdo->prepare(
                    "INSERT INTO DiaChiGiaoHang (maKhachHang, nguoiNhan, soDienThoai, diaChi, maQuyTac, macDinh)
                     VALUES (?, ?, ?, ?, ?, ?)"
                )->execute([$maKhachHang, $nguoiNhan, $soDienThoai, $diaChi, $maQuyTac, $macDinh ? 1 : 0]);
                $maDiaChi = (int)$this->pdo->lastInsertId();
            }

            $gioStmt = $this->pdo->prepare("SELECT * FROM GioHang WHERE maGioHang = ? AND trangThai = 'Đang dùng' FOR UPDATE");
            $gioStmt->execute([$maGioHang]);
            $gio = $gioStmt->fetch();
            if (!$gio) {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Giỏ hàng không tồn tại hoặc đang trong quá trình thanh toán', 'duLieu' => null, 'maHttp' => 400];
            }

            $ctStmt = $this->pdo->prepare("SELECT * FROM ChiTietGioHang WHERE maGioHang = ?");
            $ctStmt->execute([$maGioHang]);
            $chiTiets = $ctStmt->fetchAll();
            if (empty($chiTiets)) {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Giỏ hàng đang trống, không thể đặt hàng', 'duLieu' => null, 'maHttp' => 400];
            }

            // Kiểm tra lại tonKhaDung & giữ chỗ tạm (TonKho.giuCho += soLuong, tonKhaDung -= soLuong)
            foreach ($chiTiets as $ct) {
                $tonStmt = $this->pdo->prepare("SELECT * FROM TonKho WHERE maDienThoai = ? AND maKho = 1 FOR UPDATE");
                $tonStmt->execute([$ct['maDienThoai']]);
                $ton = $tonStmt->fetch();
                $sl = (int)$ct['soLuong'];
                if (!$ton || (int)$ton['tonKhaDung'] < $sl) {
                    throw new RuntimeException('Sản phẩm mã #' . $ct['maDienThoai'] . ' không đủ tồn kho khả dụng');
                }
                $giuChoMoi = (int)$ton['giuCho'] + $sl;
                $tonKhaDungMoi = (int)$ton['tonThucTe'] - $giuChoMoi;
                $this->pdo->prepare("UPDATE TonKho SET giuCho = ?, tonKhaDung = ? WHERE maTonKho = ?")
                    ->execute([$giuChoMoi, $tonKhaDungMoi, $ton['maTonKho']]);
            }

            // Khóa giỏ hàng trong khi giữ chỗ thanh toán
            $this->pdo->prepare("UPDATE GioHang SET trangThai = 'Đã khóa', maKhachHang = ? WHERE maGioHang = ?")
                ->execute([$maKhachHang, $maGioHang]);

            $ketQuaPhi = $this->tinhPhiVanChuyen($maDiaChi, (float)$gio['tongTamTinh']);
            $phiVanChuyen = (float)$ketQuaPhi['duLieu']['phiVanChuyen'];
            $tongTienCuoi = max(0.0, (float)$gio['tongTien'] + $phiVanChuyen);

            // Nếu là Thanh toán trực tuyến mô phỏng -> CHƯA tạo DonHang, chờ kết quả thanh toán (UC29)
            if ($phuongThuc === 'Thanh toán trực tuyến mô phỏng') {
                $this->pdo->prepare(
                    "INSERT INTO ThanhToan (maGioHang, maDonHang, phuongThuc, soTien, soTienHoan, trangThai)
                     VALUES (?, NULL, ?, ?, 0, 'Chờ thanh toán')"
                )->execute([$maGioHang, $phuongThuc, $tongTienCuoi]);
                $maThanhToan = (int)$this->pdo->lastInsertId();

                $this->pdo->commit();
                return [
                    'thanhCong' => true,
                    'thongBao'  => 'Đã khóa giỏ hàng và giữ chỗ tạm thành công. Vui lòng hoàn tất thanh toán trực tuyến',
                    'duLieu'    => [
                        'choThanhToan'       => true,
                        'maThanhToan'        => $maThanhToan,
                        'maGioHang'          => $maGioHang,
                        'maDiaChi'           => $maDiaChi,
                        'phiVanChuyen'       => $phiVanChuyen,
                        'tongTien'           => $tongTienCuoi,
                        'trangThaiThanhToan' => 'Chờ thanh toán',
                    ],
                    'maHttp'    => 201,
                ];
            }

            // Nếu là COD (Thanh toán khi nhận hàng) -> hoàn tất tạo DonHang ngay
            $maDonHang = $this->hoanTatTaoDonHang($maKhachHang, $maGioHang, $maDiaChi, $gio, $chiTiets, $phiVanChuyen, $tongTienCuoi);

            $this->pdo->prepare(
                "INSERT INTO ThanhToan (maGioHang, maDonHang, phuongThuc, soTien, soTienHoan, trangThai)
                 VALUES (?, ?, ?, ?, 0, 'Chưa thu tiền')"
            )->execute([$maGioHang, $maDonHang, $phuongThuc, $tongTienCuoi]);

            $this->pdo->commit();
            return [
                'thanhCong' => true,
                'thongBao'  => 'Đặt hàng thành công (Thanh toán khi nhận hàng)',
                'duLieu'    => [
                    'choThanhToan'       => false,
                    'maDonHang'          => $maDonHang,
                    'phiVanChuyen'       => $phiVanChuyen,
                    'tongTien'           => $tongTienCuoi,
                    'trangThai'          => 'Chờ duyệt',
                    'trangThaiThanhToan' => 'Chưa thu tiền',
                ],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Đặt hàng thất bại: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 400];
        }
    }

    // UC29 Bước 2: Xử lý kết quả thanh toán trực tuyến mô phỏng (thanhCong / thatBai / hetHan)
    public function thanhToan(int $maKhachHang, array $duLieu): array
    {
        $maThanhToan = (int)($duLieu['maThanhToan'] ?? 0);
        $maDiaChi = (int)($duLieu['maDiaChi'] ?? 0);
        $ketQua = trim((string)($duLieu['ketQua'] ?? 'thanhCong')); // thanhCong | thatBai | hetHan

        if ($maThanhToan <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Mã giao dịch thanh toán không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            $this->pdo->beginTransaction();

            $ttStmt = $this->pdo->prepare("SELECT * FROM ThanhToan WHERE maThanhToan = ? FOR UPDATE");
            $ttStmt->execute([$maThanhToan]);
            $tt = $ttStmt->fetch();
            if (!$tt || $tt['trangThai'] !== 'Chờ thanh toán' || $tt['maDonHang'] !== null) {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Giao dịch thanh toán không hợp lệ hoặc đã được xử lý', 'duLieu' => null, 'maHttp' => 400];
            }

            $maGioHang = (int)$tt['maGioHang'];
            $gioStmt = $this->pdo->prepare("SELECT * FROM GioHang WHERE maGioHang = ? FOR UPDATE");
            $gioStmt->execute([$maGioHang]);
            $gio = $gioStmt->fetch();

            $ctStmt = $this->pdo->prepare("SELECT * FROM ChiTietGioHang WHERE maGioHang = ?");
            $ctStmt->execute([$maGioHang]);
            $chiTiets = $ctStmt->fetchAll();

            // Thanh toán Thất bại / Hủy / Hết hạn giữ chỗ -> nhả toàn bộ giữ chỗ tạm, mở lại giỏ hàng, KHÔNG tạo DonHang
            if ($ketQua !== 'thanhCong') {
                foreach ($chiTiets as $ct) {
                    $tonStmt = $this->pdo->prepare("SELECT * FROM TonKho WHERE maDienThoai = ? AND maKho = 1 FOR UPDATE");
                    $tonStmt->execute([$ct['maDienThoai']]);
                    if ($ton = $tonStmt->fetch()) {
                        $giuChoMoi = max(0, (int)$ton['giuCho'] - (int)$ct['soLuong']);
                        $khaDungMoi = (int)$ton['tonThucTe'] - $giuChoMoi;
                        $this->pdo->prepare("UPDATE TonKho SET giuCho = ?, tonKhaDung = ? WHERE maTonKho = ?")
                            ->execute([$giuChoMoi, $khaDungMoi, $ton['maTonKho']]);
                    }
                }

                $trangThaiTtMoi = ($ketQua === 'hetHan') ? 'Đã hủy' : 'Thất bại';
                $this->pdo->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maThanhToan = ?")->execute([$trangThaiTtMoi, $maThanhToan]);
                $this->pdo->prepare("UPDATE GioHang SET trangThai = 'Đang dùng' WHERE maGioHang = ?")->execute([$maGioHang]);

                $this->pdo->commit();
                return [
                    'thanhCong' => false,
                    'thongBao'  => $ketQua === 'hetHan'
                        ? 'Phiên giữ chỗ thanh toán đã hết hạn. Đã hoàn trả tồn giữ chỗ và mở lại giỏ hàng'
                        : 'Thanh toán trực tuyến thất bại. Đã hoàn trả tồn giữ chỗ và mở lại giỏ hàng',
                    'duLieu'    => ['maThanhToan' => $maThanhToan, 'trangThaiThanhToan' => $trangThaiTtMoi, 'daMoLaiGioHang' => true],
                    'maHttp'    => 400,
                ];
            }

            // Thanh toán Thành công -> Tạo DonHang (Chờ duyệt), gán maDonHang vào ThanhToan, tăng Voucher.luotDaDung, xóa sản phẩm khỏi giỏ
            $ketQuaPhi = $this->tinhPhiVanChuyen($maDiaChi, (float)$gio['tongTamTinh']);
            $phiVanChuyen = (float)$ketQuaPhi['duLieu']['phiVanChuyen'];
            $tongTienCuoi = (float)$tt['soTien'];

            $maDonHang = $this->hoanTatTaoDonHang($maKhachHang, $maGioHang, $maDiaChi, $gio, $chiTiets, $phiVanChuyen, $tongTienCuoi);

            $this->pdo->prepare("UPDATE ThanhToan SET maDonHang = ?, trangThai = 'Thành công' WHERE maThanhToan = ?")
                ->execute([$maDonHang, $maThanhToan]);

            $this->pdo->commit();
            return [
                'thanhCong' => true,
                'thongBao'  => 'Thanh toán trực tuyến thành công! Đơn hàng đã được tạo và chờ duyệt',
                'duLieu'    => [
                    'maDonHang'          => $maDonHang,
                    'maThanhToan'        => $maThanhToan,
                    'phiVanChuyen'       => $phiVanChuyen,
                    'tongTien'           => $tongTienCuoi,
                    'trangThai'          => 'Chờ duyệt',
                    'trangThaiThanhToan' => 'Thành công',
                ],
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi xử lý thanh toán: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    private function hoanTatTaoDonHang(int $maKhachHang, int $maGioHang, int $maDiaChi, array $gio, array $chiTiets, float $phiVanChuyen, float $tongTienCuoi): int
    {
        $this->pdo->prepare(
            "INSERT INTO DonHang (maKhachHang, maDiaChi, maVoucher, ngayDat, phiVanChuyen, tongTien, trangThai)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, ?, 'Chờ duyệt')"
        )->execute([$maKhachHang, $maDiaChi, $gio['maVoucher'], $phiVanChuyen, $tongTienCuoi]);
        $maDonHang = (int)$this->pdo->lastInsertId();

        $themCtdh = $this->pdo->prepare(
            "INSERT INTO ChiTietDonHang (maDonHang, maDienThoai, soLuong, donGia) VALUES (?, ?, ?, ?)"
        );
        foreach ($chiTiets as $ct) {
            $themCtdh->execute([$maDonHang, $ct['maDienThoai'], $ct['soLuong'], $ct['donGia']]);
        }

        if (!empty($gio['maVoucher'])) {
            $this->pdo->prepare("UPDATE Voucher SET luotDaDung = luotDaDung + 1 WHERE maVoucher = ? AND luotDaDung < soLuong")
                ->execute([$gio['maVoucher']]);
        }

        // Xóa sản phẩm đã đặt khỏi giỏ hàng
        $this->pdo->prepare("DELETE FROM ChiTietGioHang WHERE maGioHang = ?")->execute([$maGioHang]);
        $this->pdo->prepare("UPDATE GioHang SET maVoucher = NULL, tongTamTinh = 0, tienGiamGia = 0, tongTien = 0, trangThai = 'Đã khóa' WHERE maGioHang = ?")
            ->execute([$maGioHang]);

        return $maDonHang;
    }

    public function xacNhanThanhToanTrucTuyen(int $maDonHang, bool $thanhCong = true): array
    {
        $trangThaiMoi = $thanhCong ? 'Thành công' : 'Thất bại';
        $this->pdo->prepare("UPDATE ThanhToan SET trangThai = ? WHERE maDonHang = ?")->execute([$trangThaiMoi, $maDonHang]);
        return [
            'thanhCong' => true,
            'thongBao'  => $thanhCong ? 'Thanh toán trực tuyến thành công' : 'Thanh toán trực tuyến thất bại',
            'duLieu'    => ['maDonHang' => $maDonHang, 'trangThaiThanhToan' => $trangThaiMoi],
        ];
    }

    // UC27: Quản lý quy tắc phí vận chuyển (Ban Quản lý)
    public function layDanhSachQuyTacPhi(): array
    {
        $ds = $this->pdo->query("SELECT * FROM QuyTacPhiVanChuyen ORDER BY maQuyTac ASC")->fetchAll();
        return [
            'thanhCong' => true,
            'thongBao'  => 'Lấy danh sách quy tắc phí vận chuyển thành công',
            'duLieu'    => array_map(fn($d) => QuyTacPhiVanChuyen::tuMang($d)->chuyenThanhMang(), $ds),
        ];
    }

    public function luuQuyTacPhi(array $duLieu): array
    {
        $maQuyTac = (int)($duLieu['maQuyTac'] ?? 0);
        $khuVuc = trim((string)($duLieu['khuVuc'] ?? ''));
        $mucPhi = (float)($duLieu['mucPhi'] ?? 0);
        $mucPhiMacDinh = (float)($duLieu['mucPhiMacDinh'] ?? 35000);
        $nguongMienPhi = (float)($duLieu['nguongMienPhi'] ?? 0);

        if ($khuVuc === '' || $mucPhi < 0 || $mucPhiMacDinh < 0 || $nguongMienPhi < 0) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin quy tắc phí vận chuyển không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        if ($maQuyTac > 0) {
            $this->pdo->prepare("UPDATE QuyTacPhiVanChuyen SET khuVuc = ?, mucPhi = ?, mucPhiMacDinh = ?, nguongMienPhi = ? WHERE maQuyTac = ?")
                ->execute([$khuVuc, $mucPhi, $mucPhiMacDinh, $nguongMienPhi, $maQuyTac]);
            return ['thanhCong' => true, 'thongBao' => 'Cập nhật quy tắc phí vận chuyển thành công', 'duLieu' => ['maQuyTac' => $maQuyTac]];
        }

        $this->pdo->prepare("INSERT INTO QuyTacPhiVanChuyen (khuVuc, mucPhi, mucPhiMacDinh, nguongMienPhi) VALUES (?, ?, ?, ?)")
            ->execute([$khuVuc, $mucPhi, $mucPhiMacDinh, $nguongMienPhi]);
        return ['thanhCong' => true, 'thongBao' => 'Thêm quy tắc phí vận chuyển thành công', 'duLieu' => ['maQuyTac' => (int)$this->pdo->lastInsertId()], 'maHttp' => 201];
    }
}
