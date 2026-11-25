<?php
// Controller BCE: GioHangCtrl (UC22 - UC24)

class GioHangCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Lấy giá bán cuối của sản phẩm sau khi trừ khuyến mãi đang hiệu lực
    private function layGiaBanSauKhuyenMai(int $maDienThoai, float $giaBanGoc): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT MAX(km.mucUuDai)
             FROM KhuyenMai km
             JOIN KhuyenMai_DienThoai kmdt ON kmdt.maKhuyenMai = km.maKhuyenMai
             WHERE kmdt.maDienThoai = ? AND km.trangThai = 'Đang áp dụng'
               AND CURDATE() BETWEEN km.ngayBatDau AND km.ngayKetThuc"
        );
        $stmt->execute([$maDienThoai]);
        $mucUuDai = (float)($stmt->fetchColumn() ?: 0);
        return $mucUuDai > 0 ? round($giaBanGoc * (1 - $mucUuDai / 100), 2) : $giaBanGoc;
    }

    public function timHoacTaoGioHang(?int $maKhachHang, string $maPhien): array
    {
        if ($maKhachHang !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM GioHang WHERE maKhachHang = ? AND trangThai = 'Đang dùng' ORDER BY maGioHang DESC LIMIT 1");
            $stmt->execute([$maKhachHang]);
            $gio = $stmt->fetch();
            if ($gio) {
                return $gio;
            }
        }

        $stmt = $this->pdo->prepare("SELECT * FROM GioHang WHERE maPhien = ? AND trangThai = 'Đang dùng' ORDER BY maGioHang DESC LIMIT 1");
        $stmt->execute([$maPhien]);
        $gio = $stmt->fetch();
        if ($gio) {
            return $gio;
        }

        $this->pdo->prepare(
            "INSERT INTO GioHang (maKhachHang, maPhien, maVoucher, tongTamTinh, tienGiamGia, tongTien, trangThai)
             VALUES (?, ?, NULL, 0, 0, 0, 'Đang dùng')"
        )->execute([$maKhachHang, $maPhien]);

        $maGioHang = (int)$this->pdo->lastInsertId();
        return [
            'maGioHang'   => $maGioHang,
            'maKhachHang' => $maKhachHang,
            'maPhien'     => $maPhien,
            'maVoucher'   => null,
            'tongTamTinh' => 0.0,
            'tienGiamGia' => 0.0,
            'tongTien'    => 0.0,
            'trangThai'   => 'Đang dùng',
        ];
    }

    // Gộp giỏ hàng tạm của khách vãng lai (maPhienTam) vào giỏ hàng của khách hàng vừa đăng nhập/đăng ký
    public function gopGioHangTam(string $maPhienTam, int $maKhachHang, string $maPhienMoi): void
    {
        $tamStmt = $this->pdo->prepare("SELECT * FROM GioHang WHERE maPhien = ? AND maKhachHang IS NULL AND trangThai = 'Đang dùng' ORDER BY maGioHang DESC LIMIT 1");
        $tamStmt->execute([$maPhienTam]);
        $gioTam = $tamStmt->fetch();
        if (!$gioTam) {
            return;
        }

        $gioKhach = $this->timHoacTaoGioHang($maKhachHang, $maPhienMoi);
        $maGioDich = (int)$gioKhach['maGioHang'];
        $maGioNguon = (int)$gioTam['maGioHang'];
        if ($maGioDich === $maGioNguon) {
            $this->pdo->prepare("UPDATE GioHang SET maKhachHang = ?, maPhien = ? WHERE maGioHang = ?")
                ->execute([$maKhachHang, $maPhienMoi, $maGioDich]);
            return;
        }

        $ctStmt = $this->pdo->prepare("SELECT * FROM ChiTietGioHang WHERE maGioHang = ?");
        $ctStmt->execute([$maGioNguon]);
        foreach ($ctStmt->fetchAll() as $ct) {
            $maDt = (int)$ct['maDienThoai'];
            $tonStmt = $this->pdo->prepare("SELECT COALESCE(tonKhaDung, 0) FROM TonKho WHERE maDienThoai = ? AND maKho = 1 LIMIT 1");
            $tonStmt->execute([$maDt]);
            $tonKhaDung = (int)$tonStmt->fetchColumn();
            if ($tonKhaDung <= 0) {
                continue;
            }

            $hienCoStmt = $this->pdo->prepare("SELECT * FROM ChiTietGioHang WHERE maGioHang = ? AND maDienThoai = ? LIMIT 1");
            $hienCoStmt->execute([$maGioDich, $maDt]);
            $hienCo = $hienCoStmt->fetch();

            if ($hienCo) {
                $slMoi = min($tonKhaDung, (int)$hienCo['soLuong'] + (int)$ct['soLuong']);
                $this->pdo->prepare("UPDATE ChiTietGioHang SET soLuong = ?, donGia = ? WHERE maChiTietGioHang = ?")
                    ->execute([$slMoi, $ct['donGia'], $hienCo['maChiTietGioHang']]);
            } else {
                $slMoi = min($tonKhaDung, (int)$ct['soLuong']);
                $this->pdo->prepare("INSERT INTO ChiTietGioHang (maGioHang, maDienThoai, soLuong, donGia) VALUES (?, ?, ?, ?)")
                    ->execute([$maGioDich, $maDt, $slMoi, $ct['donGia']]);
            }
        }

        if (!empty($gioTam['maVoucher']) && empty($gioKhach['maVoucher'])) {
            $this->pdo->prepare("UPDATE GioHang SET maVoucher = ? WHERE maGioHang = ?")->execute([$gioTam['maVoucher'], $maGioDich]);
        }

        $this->pdo->prepare("DELETE FROM ChiTietGioHang WHERE maGioHang = ?")->execute([$maGioNguon]);
        $this->pdo->prepare("UPDATE GioHang SET trangThai = 'Đã khóa' WHERE maGioHang = ?")->execute([$maGioNguon]);
        $this->tinhLaiGioHang($maGioDich);
    }

    public function tinhLaiGioHang(int $maGioHang): array
    {
        $tongStmt = $this->pdo->prepare("SELECT COALESCE(SUM(soLuong * donGia), 0) FROM ChiTietGioHang WHERE maGioHang = ?");
        $tongStmt->execute([$maGioHang]);
        $tongTamTinh = (float)$tongStmt->fetchColumn();

        $gioStmt = $this->pdo->prepare("SELECT * FROM GioHang WHERE maGioHang = ?");
        $gioStmt->execute([$maGioHang]);
        $gio = $gioStmt->fetch();
        $tienGiamGia = 0.0;

        if ($gio && !empty($gio['maVoucher'])) {
            $vStmt = $this->pdo->prepare("SELECT * FROM Voucher WHERE maVoucher = ? AND trangThai = 'Đang hiệu lực' LIMIT 1");
            $vStmt->execute([$gio['maVoucher']]);
            $voucher = $vStmt->fetch();
            if ($voucher) {
                $tienGiamGia = $voucher['loaiGiam'] === 'Phần trăm'
                    ? round($tongTamTinh * ((float)$voucher['giaTriGiam'] / 100), 2)
                    : min($tongTamTinh, (float)$voucher['giaTriGiam']);
            }
        }

        $tongTien = max(0.0, $tongTamTinh - $tienGiamGia);
        $this->pdo->prepare("UPDATE GioHang SET tongTamTinh = ?, tienGiamGia = ?, tongTien = ? WHERE maGioHang = ?")
            ->execute([$tongTamTinh, $tienGiamGia, $tongTien, $maGioHang]);

        return ['tongTamTinh' => $tongTamTinh, 'tienGiamGia' => $tienGiamGia, 'tongTien' => $tongTien];
    }

    public function layGioHang(?int $maKhachHang, string $maPhien): array
    {
        $gio = $this->timHoacTaoGioHang($maKhachHang, $maPhien);
        $this->tinhLaiGioHang((int)$gio['maGioHang']);

        $gioCapNhat = $this->pdo->prepare("SELECT * FROM GioHang WHERE maGioHang = ?");
        $gioCapNhat->execute([$gio['maGioHang']]);
        $gioMoi = GioHang::tuMang($gioCapNhat->fetch())->chuyenThanhMang();

        $ctStmt = $this->pdo->prepare(
            "SELECT ct.*, d.ten AS tenDienThoai, d.hinhAnh, d.giaBan AS giaGoc, COALESCE(tk.tonKhaDung, 0) AS tonKhaDung
             FROM ChiTietGioHang ct
             JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai
             LEFT JOIN TonKho tk ON tk.maDienThoai = ct.maDienThoai AND tk.maKho = 1
             WHERE ct.maGioHang = ?"
        );
        $ctStmt->execute([$gio['maGioHang']]);
        $chiTiet = [];
        foreach ($ctStmt->fetchAll() as $dong) {
            $muc = ChiTietGioHang::tuMang($dong)->chuyenThanhMang();
            $muc['tenDienThoai'] = $dong['tenDienThoai'];
            $muc['hinhAnh'] = chuanHoaDuongDanAnh($dong['hinhAnh']);
            $muc['giaGoc'] = (float)$dong['giaGoc'];
            $muc['tonKhaDung'] = (int)$dong['tonKhaDung'];
            $chiTiet[] = $muc;
        }
        $gioMoi['chiTiet'] = $chiTiet;

        return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin giỏ hàng thành công', 'duLieu' => $gioMoi];
    }

    // UC22: Thêm vào giỏ (không giữ chỗ ở bước giỏ hàng, nhưng luôn kiểm tra soLuong <= tonKhaDung)
    public function themVaoGioHang(?int $maKhachHang, string $maPhien, int $maDienThoai, int $soLuong = 1): array
    {
        if ($maDienThoai <= 0 || $soLuong <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Sản phẩm hoặc số lượng không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $spStmt = $this->pdo->prepare(
            "SELECT d.maDienThoai, d.ten, d.giaBan, d.trangThai, COALESCE(tk.tonKhaDung, 0) AS tonKhaDung
             FROM DienThoai d
             LEFT JOIN TonKho tk ON tk.maDienThoai = d.maDienThoai AND tk.maKho = 1
             WHERE d.maDienThoai = ? LIMIT 1"
        );
        $spStmt->execute([$maDienThoai]);
        $sp = $spStmt->fetch();

        if (!$sp || $sp['trangThai'] !== 'Đang kinh doanh') {
            return ['thanhCong' => false, 'thongBao' => 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh', 'duLieu' => null, 'maHttp' => 404];
        }

        $gio = $this->timHoacTaoGioHang($maKhachHang, $maPhien);
        $maGioHang = (int)$gio['maGioHang'];

        $ctStmt = $this->pdo->prepare("SELECT * FROM ChiTietGioHang WHERE maGioHang = ? AND maDienThoai = ? LIMIT 1");
        $ctStmt->execute([$maGioHang, $maDienThoai]);
        $hienCo = $ctStmt->fetch();

        $tongSoLuong = $soLuong + ($hienCo ? (int)$hienCo['soLuong'] : 0);
        if ($tongSoLuong > (int)$sp['tonKhaDung']) {
            return ['thanhCong' => false, 'thongBao' => "Số lượng vượt quá tồn khả dụng ({$sp['tonKhaDung']} sản phẩm)", 'duLieu' => null, 'maHttp' => 400];
        }

        $donGiaCuoi = $this->layGiaBanSauKhuyenMai($maDienThoai, (float)$sp['giaBan']);

        if ($hienCo) {
            $this->pdo->prepare("UPDATE ChiTietGioHang SET soLuong = ?, donGia = ? WHERE maChiTietGioHang = ?")
                ->execute([$tongSoLuong, $donGiaCuoi, $hienCo['maChiTietGioHang']]);
        } else {
            $this->pdo->prepare("INSERT INTO ChiTietGioHang (maGioHang, maDienThoai, soLuong, donGia) VALUES (?, ?, ?, ?)")
                ->execute([$maGioHang, $maDienThoai, $soLuong, $donGiaCuoi]);
        }

        $this->tinhLaiGioHang($maGioHang);
        return $this->layGioHang($maKhachHang, $maPhien);
    }

    // UC23: Cập nhật số lượng trong giỏ hàng
    public function capNhatSoLuong(?int $maKhachHang, string $maPhien, int $maDienThoai, int $soLuong): array
    {
        $gio = $this->timHoacTaoGioHang($maKhachHang, $maPhien);
        $maGioHang = (int)$gio['maGioHang'];

        if ($soLuong <= 0) {
            return $this->xoaKhoiGioHang($maKhachHang, $maPhien, $maDienThoai);
        }

        $tonStmt = $this->pdo->prepare("SELECT COALESCE(tonKhaDung, 0) FROM TonKho WHERE maDienThoai = ? AND maKho = 1 LIMIT 1");
        $tonStmt->execute([$maDienThoai]);
        $tonKhaDung = (int)$tonStmt->fetchColumn();

        if ($soLuong > $tonKhaDung) {
            return ['thanhCong' => false, 'thongBao' => "Kho chỉ còn {$tonKhaDung} sản phẩm khả dụng", 'duLieu' => null, 'maHttp' => 400];
        }

        $this->pdo->prepare("UPDATE ChiTietGioHang SET soLuong = ? WHERE maGioHang = ? AND maDienThoai = ?")
            ->execute([$soLuong, $maGioHang, $maDienThoai]);
        $this->tinhLaiGioHang($maGioHang);

        return $this->layGioHang($maKhachHang, $maPhien);
    }

    // UC24: Xóa sản phẩm khỏi giỏ hàng
    public function xoaKhoiGioHang(?int $maKhachHang, string $maPhien, int $maDienThoai): array
    {
        $gio = $this->timHoacTaoGioHang($maKhachHang, $maPhien);
        $maGioHang = (int)$gio['maGioHang'];

        $this->pdo->prepare("DELETE FROM ChiTietGioHang WHERE maGioHang = ? AND maDienThoai = ?")
            ->execute([$maGioHang, $maDienThoai]);
        $this->tinhLaiGioHang($maGioHang);

        return $this->layGioHang($maKhachHang, $maPhien);
    }
}
