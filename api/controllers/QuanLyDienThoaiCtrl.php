<?php
// Controller BCE: QuanLyDienThoaiCtrl (UC4 - UC6, UC10)

class QuanLyDienThoaiCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDanhSachDienThoai(?string $trangThai = null): array
    {
        $sql = "SELECT d.*, dm.ten AS tenDanhMuc,
                       ts.maThongSo, ts.manHinh, ts.chip, ts.ram, ts.rom, ts.camera, ts.dungLuongPin, ts.heDieuHanh,
                       COALESCE(tk.tonThucTe, 0) AS tonThucTe,
                       COALESCE(tk.giuCho, 0) AS giuCho,
                       COALESCE(tk.tonKhaDung, 0) AS tonKhaDung
                FROM DienThoai d
                LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                LEFT JOIN ThongSoKyThuat ts ON ts.maDienThoai = d.maDienThoai
                LEFT JOIN TonKho tk ON tk.maDienThoai = d.maDienThoai AND tk.maKho = 1";
        $thamSo = [];
        if ($trangThai !== null && $trangThai !== '') {
            $sql .= " WHERE d.trangThai = ?";
            $thamSo[] = $trangThai;
        }
        $sql .= " ORDER BY d.maDienThoai DESC";

        $cauLenh = $this->pdo->prepare($sql);
        $cauLenh->execute($thamSo);

        $danhSach = [];
        foreach ($cauLenh->fetchAll() as $dong) {
            $dienThoai = DienThoai::tuMang($dong)->chuyenThanhMang();
            $dienThoai['hinhAnh'] = chuanHoaDuongDanAnh($dienThoai['hinhAnh']);
            $dienThoai['tenDanhMuc'] = $dong['tenDanhMuc'] ?? '';
            $dienThoai['thongSoKyThuat'] = [
                'maThongSo'    => isset($dong['maThongSo']) ? (int)$dong['maThongSo'] : null,
                'manHinh'      => $dong['manHinh'] ?? null,
                'chip'         => $dong['chip'] ?? null,
                'ram'          => (int)($dong['ram'] ?? 0),
                'rom'          => (int)($dong['rom'] ?? 0),
                'camera'       => $dong['camera'] ?? null,
                'dungLuongPin' => (int)($dong['dungLuongPin'] ?? 0),
                'heDieuHanh'   => $dong['heDieuHanh'] ?? null,
            ];
            $dienThoai['tonKho'] = [
                'tonThucTe'  => (int)$dong['tonThucTe'],
                'giuCho'     => (int)$dong['giuCho'],
                'tonKhaDung' => (int)$dong['tonKhaDung'],
            ];
            $danhSach[] = $dienThoai;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách điện thoại thành công', 'duLieu' => $danhSach];
    }

    public function themDienThoai(array $duLieu, ?array $tepAnh = null): array
    {
        $ten = trim((string)($duLieu['ten'] ?? ''));
        $maDanhMuc = (int)($duLieu['maDanhMuc'] ?? 0);
        $hang = trim((string)($duLieu['hang'] ?? ''));
        $moTa = isset($duLieu['moTa']) ? trim((string)$duLieu['moTa']) : null;
        $giaVonBinhQuan = (float)($duLieu['giaVonBinhQuan'] ?? 0);
        $giaBan = (float)($duLieu['giaBan'] ?? 0);
        $trangThai = trim((string)($duLieu['trangThai'] ?? 'Đang kinh doanh'));

        if ($ten === '' || $maDanhMuc <= 0 || $hang === '') {
            return ['thanhCong' => false, 'thongBao' => 'Vui lòng nhập đầy đủ tên, danh mục và hãng điện thoại', 'duLieu' => null, 'maHttp' => 400];
        }

        $duongDanAnh = $this->luuAnhSanPham($tepAnh) ?? trim((string)($duLieu['hinhAnh'] ?? 'storage/shop-assets/img/iphone17.webp'));

        try {
            $this->pdo->beginTransaction();

            $themDt = $this->pdo->prepare(
                "INSERT INTO DienThoai (ten, maDanhMuc, hang, moTa, hinhAnh, giaVonBinhQuan, giaBan, trangThai)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $themDt->execute([$ten, $maDanhMuc, $hang, $moTa, ltrim($duongDanAnh, '/'), max(0, $giaVonBinhQuan), max(0, $giaBan), $trangThai]);
            $maDienThoai = (int)$this->pdo->lastInsertId();

            $thongSo = is_array($duLieu['thongSoKyThuat'] ?? null) ? $duLieu['thongSoKyThuat'] : $duLieu;
            $themTs = $this->pdo->prepare(
                "INSERT INTO ThongSoKyThuat (maDienThoai, manHinh, chip, ram, rom, camera, dungLuongPin, heDieuHanh)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $themTs->execute([
                $maDienThoai,
                $thongSo['manHinh'] ?? '6.3 inches OLED',
                $thongSo['chip'] ?? 'Đang cập nhật',
                max(1, (int)($thongSo['ram'] ?? 8)),
                max(1, (int)($thongSo['rom'] ?? 128)),
                $thongSo['camera'] ?? '48MP',
                max(1, (int)($thongSo['dungLuongPin'] ?? 4500)),
                $thongSo['heDieuHanh'] ?? 'iOS / Android',
            ]);

            $themTon = $this->pdo->prepare(
                "INSERT INTO TonKho (maDienThoai, maKho, tonThucTe, giuCho, tonKhaDung) VALUES (?, 1, 0, 0, 0)"
            );
            $themTon->execute([$maDienThoai]);

            $this->pdo->commit();

            return [
                'thanhCong' => true,
                'thongBao'  => 'Thêm điện thoại mới thành công',
                'duLieu'    => ['maDienThoai' => $maDienThoai, 'hinhAnh' => chuanHoaDuongDanAnh($duongDanAnh)],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Không thể thêm điện thoại: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function capNhatDienThoai(int $maDienThoai, array $duLieu, ?array $tepAnh = null): array
    {
        if ($maDienThoai <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Mã điện thoại không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $duongDanAnhMoi = $this->luuAnhSanPham($tepAnh);
        $cotCapNhat = [];
        $thamSo = [];

        foreach (['ten', 'maDanhMuc', 'hang', 'moTa', 'giaVonBinhQuan', 'giaBan', 'trangThai'] as $truong) {
            if (array_key_exists($truong, $duLieu)) {
                $cotCapNhat[] = "{$truong} = ?";
                $thamSo[] = $duLieu[$truong];
            }
        }
        if ($duongDanAnhMoi !== null) {
            $cotCapNhat[] = "hinhAnh = ?";
            $thamSo[] = $duongDanAnhMoi;
        } elseif (isset($duLieu['hinhAnh'])) {
            $cotCapNhat[] = "hinhAnh = ?";
            $thamSo[] = ltrim((string)$duLieu['hinhAnh'], '/');
        }

        try {
            $this->pdo->beginTransaction();

            if (!empty($cotCapNhat)) {
                $thamSo[] = $maDienThoai;
                $sql = "UPDATE DienThoai SET " . implode(', ', $cotCapNhat) . " WHERE maDienThoai = ?";
                $this->pdo->prepare($sql)->execute($thamSo);
            }

            if (is_array($duLieu['thongSoKyThuat'] ?? null)) {
                $ts = $duLieu['thongSoKyThuat'];
                $capNhatTs = $this->pdo->prepare(
                    "UPDATE ThongSoKyThuat
                     SET manHinh = COALESCE(?, manHinh),
                         chip = COALESCE(?, chip),
                         ram = COALESCE(?, ram),
                         rom = COALESCE(?, rom),
                         camera = COALESCE(?, camera),
                         dungLuongPin = COALESCE(?, dungLuongPin),
                         heDieuHanh = COALESCE(?, heDieuHanh)
                     WHERE maDienThoai = ?"
                );
                $capNhatTs->execute([
                    $ts['manHinh'] ?? null,
                    $ts['chip'] ?? null,
                    isset($ts['ram']) ? (int)$ts['ram'] : null,
                    isset($ts['rom']) ? (int)$ts['rom'] : null,
                    $ts['camera'] ?? null,
                    isset($ts['dungLuongPin']) ? (int)$ts['dungLuongPin'] : null,
                    $ts['heDieuHanh'] ?? null,
                    $maDienThoai,
                ]);
            }

            $this->pdo->commit();
            return ['thanhCong' => true, 'thongBao' => 'Cập nhật thông tin điện thoại thành công', 'duLieu' => ['maDienThoai' => $maDienThoai]];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi cập nhật điện thoại: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function capNhatTrangThaiDienThoai(int $maDienThoai, string $trangThai): array
    {
        if (!in_array($trangThai, ['Đang kinh doanh', 'Ngừng kinh doanh'], true)) {
            return ['thanhCong' => false, 'thongBao' => 'Trạng thái kinh doanh không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $cauLenh = $this->pdo->prepare("UPDATE DienThoai SET trangThai = ? WHERE maDienThoai = ?");
        $cauLenh->execute([$trangThai, $maDienThoai]);

        return ['thanhCong' => true, 'thongBao' => 'Cập nhật trạng thái kinh doanh thành công', 'duLieu' => ['maDienThoai' => $maDienThoai, 'trangThai' => $trangThai]];
    }

    public function xoaDienThoai(int $maDienThoai): array
    {
        if ($maDienThoai <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Mã điện thoại không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        // UC6: Chặn nếu đang nằm trong đơn hàng chưa hoàn tất (Chờ duyệt -> Đang giao)
        $donChuaXong = $this->pdo->prepare(
            "SELECT COUNT(*) FROM ChiTietDonHang ctdh
             JOIN DonHang dh ON dh.maDonHang = ctdh.maDonHang
             WHERE ctdh.maDienThoai = ?
               AND dh.trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đang đóng gói', 'Đã giao shipper', 'Đang giao')"
        );
        $donChuaXong->execute([$maDienThoai]);
        if ((int)$donChuaXong->fetchColumn() > 0) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Không thể xóa điện thoại đang nằm trong đơn hàng chưa hoàn tất',
                'duLieu'    => null,
                'maHttp'    => 409,
            ];
        }

        // Kiểm tra đã từng phát sinh giao dịch (đơn hàng / phiếu nhập / phiếu xuất)
        $daPhatSinh = 0;
        foreach (['ChiTietDonHang', 'ChiTietPhieuNhap', 'ChiTietPhieuXuat'] as $bang) {
            $dem = $this->pdo->prepare("SELECT COUNT(*) FROM {$bang} WHERE maDienThoai = ?");
            $dem->execute([$maDienThoai]);
            $daPhatSinh += (int)$dem->fetchColumn();
        }

        if ($daPhatSinh > 0) {
            $this->pdo->prepare("UPDATE DienThoai SET trangThai = 'Ngừng kinh doanh' WHERE maDienThoai = ?")->execute([$maDienThoai]);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Sản phẩm đã phát sinh giao dịch nên được xóa mềm (chuyển sang Ngừng kinh doanh)',
                'duLieu'    => ['maDienThoai' => $maDienThoai, 'trangThai' => 'Ngừng kinh doanh', 'kieuXoa' => 'xoaMem'],
            ];
        }

        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("DELETE FROM TonKho WHERE maDienThoai = ?")->execute([$maDienThoai]);
            $this->pdo->prepare("DELETE FROM ThongSoKyThuat WHERE maDienThoai = ?")->execute([$maDienThoai]);
            $this->pdo->prepare("DELETE FROM DienThoai WHERE maDienThoai = ?")->execute([$maDienThoai]);
            $this->pdo->commit();
            return [
                'thanhCong' => true,
                'thongBao'  => 'Đã xóa cứng điện thoại khỏi hệ thống',
                'duLieu'    => ['maDienThoai' => $maDienThoai, 'kieuXoa' => 'xoaCung'],
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->pdo->prepare("UPDATE DienThoai SET trangThai = 'Ngừng kinh doanh' WHERE maDienThoai = ?")->execute([$maDienThoai]);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Sản phẩm có dữ liệu liên kết nên được chuyển sang Ngừng kinh doanh',
                'duLieu'    => ['maDienThoai' => $maDienThoai, 'trangThai' => 'Ngừng kinh doanh', 'kieuXoa' => 'xoaMem'],
            ];
        }
    }

    public function khoiPhucDienThoai(int $maDienThoai): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.maDienThoai, dm.maDanhMuc
             FROM DienThoai d
             LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
             WHERE d.maDienThoai = ? LIMIT 1"
        );
        $stmt->execute([$maDienThoai]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy điện thoại cần khôi phục', 'duLieu' => null, 'maHttp' => 404];
        }
        if (empty($row['maDanhMuc'])) {
            return ['thanhCong' => false, 'thongBao' => 'Không thể khôi phục vì danh mục của sản phẩm không còn tồn tại', 'duLieu' => null, 'maHttp' => 400];
        }

        $this->pdo->prepare("UPDATE DienThoai SET trangThai = 'Đang kinh doanh' WHERE maDienThoai = ?")->execute([$maDienThoai]);
        return [
            'thanhCong' => true,
            'thongBao'  => 'Khôi phục kinh doanh điện thoại thành công',
            'duLieu'    => ['maDienThoai' => $maDienThoai, 'trangThai' => 'Đang kinh doanh'],
        ];
    }

    public function luuAnhSanPham(?array $tepAnh): ?string
    {
        if (!$tepAnh || ($tepAnh['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $duoiHopLe = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $duoiTep = strtolower(pathinfo($tepAnh['name'], PATHINFO_EXTENSION));
        if (!in_array($duoiTep, $duoiHopLe, true)) {
            return null;
        }

        $thuMucUpload = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($thuMucUpload)) {
            @mkdir($thuMucUpload, 0777, true);
        }

        $tenGoc = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($tepAnh['name'], PATHINFO_FILENAME));
        $tenMoi = (trim($tenGoc, '_') ?: 'dienthoai') . '_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $duoiTep;
        $dichDen = $thuMucUpload . DIRECTORY_SEPARATOR . $tenMoi;

        if (move_uploaded_file($tepAnh['tmp_name'], $dichDen)) {
            return 'uploads/' . $tenMoi;
        }
        return null;
    }
}
