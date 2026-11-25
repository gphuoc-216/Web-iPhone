<?php
// Controller BCE: QuanLyKhuyenMaiCtrl (UC57 - UC59)

class QuanLyKhuyenMaiCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDanhSachKhuyenMai(): array
    {
        $ds = $this->pdo->query(
            "SELECT km.*, dk.giaTriToiThieu, dk.nhomHang, dk.doiTuong
             FROM KhuyenMai km
             LEFT JOIN DieuKienApDung dk ON dk.maKhuyenMai = km.maKhuyenMai
             ORDER BY km.maKhuyenMai DESC"
        )->fetchAll();

        $spStmt = $this->pdo->query("SELECT maKhuyenMai, maDienThoai FROM KhuyenMai_DienThoai");
        $spTheoKm = [];
        foreach ($spStmt->fetchAll() as $dong) {
            $spTheoKm[(int)$dong['maKhuyenMai']][] = (int)$dong['maDienThoai'];
        }

        $ketQua = [];
        foreach ($ds as $km) {
            $muc = KhuyenMai::tuMang($km)->chuyenThanhMang();
            $muc['danhSachMaDienThoai'] = $spTheoKm[$muc['maKhuyenMai']] ?? [];
            $muc['dieuKienApDung'] = [
                'giaTriToiThieu' => (float)($km['giaTriToiThieu'] ?? 0),
                'nhomHang'       => $km['nhomHang'] ?? null,
                'doiTuong'       => $km['doiTuong'] ?? null,
            ];
            $ketQua[] = $muc;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách chương trình khuyến mãi thành công', 'duLieu' => $ketQua];
    }

    private function kiemTraXungDotKhuyenMai(array $danhSachMaDt, string $ngayBatDau, string $ngayKetThuc, ?int $boQuaMaKm = null): ?string
    {
        if (empty($danhSachMaDt)) {
            return null;
        }
        $placeholders = implode(',', array_fill(0, count($danhSachMaDt), '?'));
        $sql = "SELECT km.maKhuyenMai, km.ten, d.ten AS tenDienThoai
                FROM KhuyenMai km
                JOIN KhuyenMai_DienThoai kmdt ON kmdt.maKhuyenMai = km.maKhuyenMai
                JOIN DienThoai d ON d.maDienThoai = kmdt.maDienThoai
                WHERE km.trangThai = 'Đang áp dụng'
                  AND kmdt.maDienThoai IN ({$placeholders})
                  AND NOT (km.ngayKetThuc < ? OR km.ngayBatDau > ?)";
        $thamSo = array_map('intval', $danhSachMaDt);
        $thamSo[] = $ngayBatDau;
        $thamSo[] = $ngayKetThuc;

        if ($boQuaMaKm !== null) {
            $sql .= " AND km.maKhuyenMai <> ?";
            $thamSo[] = $boQuaMaKm;
        }
        $sql .= " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($thamSo);
        $xungDot = $stmt->fetch();
        if ($xungDot) {
            return "Xung đột thời gian ưu đãi: sản phẩm '{$xungDot['tenDienThoai']}' đang thuộc khuyến mãi '{$xungDot['ten']}' (#{$xungDot['maKhuyenMai']})";
        }
        return null;
    }

    public function taoKhuyenMai(array $duLieu): array
    {
        $ten = trim((string)($duLieu['ten'] ?? ''));
        $noiDung = isset($duLieu['noiDung']) ? trim((string)$duLieu['noiDung']) : null;
        $mucUuDai = (float)($duLieu['mucUuDai'] ?? 0);
        $ngayBatDau = trim((string)($duLieu['ngayBatDau'] ?? date('Y-m-d')));
        $ngayKetThuc = trim((string)($duLieu['ngayKetThuc'] ?? date('Y-m-d', strtotime('+30 days'))));
        $khungGio = isset($duLieu['khungGio']) ? trim((string)$duLieu['khungGio']) : '00:00-23:59';
        $danhSachMaDt = is_array($duLieu['danhSachMaDienThoai'] ?? null) ? $duLieu['danhSachMaDienThoai'] : [];

        if ($ten === '' || $mucUuDai <= 0 || $mucUuDai > 100 || $ngayKetThuc < $ngayBatDau) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin khuyến mãi không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        if ($loiXungDot = $this->kiemTraXungDotKhuyenMai($danhSachMaDt, $ngayBatDau, $ngayKetThuc)) {
            return ['thanhCong' => false, 'thongBao' => $loiXungDot, 'duLieu' => null, 'maHttp' => 409];
        }

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare(
                "INSERT INTO KhuyenMai (ten, noiDung, mucUuDai, ngayBatDau, ngayKetThuc, khungGio, trangThai)
                 VALUES (?, ?, ?, ?, ?, ?, 'Đang áp dụng')"
            )->execute([$ten, $noiDung, $mucUuDai, $ngayBatDau, $ngayKetThuc, $khungGio]);
            $maKhuyenMai = (int)$this->pdo->lastInsertId();

            $ganSp = $this->pdo->prepare("INSERT IGNORE INTO KhuyenMai_DienThoai (maKhuyenMai, maDienThoai) VALUES (?, ?)");
            foreach ($danhSachMaDt as $maDt) {
                if ((int)$maDt > 0) {
                    $ganSp->execute([$maKhuyenMai, (int)$maDt]);
                }
            }

            $dk = is_array($duLieu['dieuKienApDung'] ?? null) ? $duLieu['dieuKienApDung'] : $duLieu;
            $this->pdo->prepare(
                "INSERT INTO DieuKienApDung (maKhuyenMai, maVoucher, giaTriToiThieu, nhomHang, doiTuong)
                 VALUES (?, NULL, ?, ?, ?)"
            )->execute([$maKhuyenMai, max(0, (float)($dk['giaTriToiThieu'] ?? 0)), $dk['nhomHang'] ?? 'Tất cả', $dk['doiTuong'] ?? 'Tất cả khách hàng']);

            $this->pdo->commit();
            return ['thanhCong' => true, 'thongBao' => 'Tạo chương trình khuyến mãi thành công', 'duLieu' => ['maKhuyenMai' => $maKhuyenMai], 'maHttp' => 201];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi tạo khuyến mãi: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function capNhatKhuyenMai(int $maKhuyenMai, array $duLieu): array
    {
        $ten = trim((string)($duLieu['ten'] ?? ''));
        $mucUuDai = (float)($duLieu['mucUuDai'] ?? 0);
        $ngayBatDau = trim((string)($duLieu['ngayBatDau'] ?? date('Y-m-d')));
        $ngayKetThuc = trim((string)($duLieu['ngayKetThuc'] ?? date('Y-m-d')));
        $danhSachMaDt = is_array($duLieu['danhSachMaDienThoai'] ?? null) ? $duLieu['danhSachMaDienThoai'] : null;

        if ($maKhuyenMai <= 0 || $ten === '' || $mucUuDai <= 0 || $mucUuDai > 100 || $ngayKetThuc < $ngayBatDau) {
            return ['thanhCong' => false, 'thongBao' => 'Thông tin cập nhật khuyến mãi không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        if ($danhSachMaDt !== null) {
            if ($loiXungDot = $this->kiemTraXungDotKhuyenMai($danhSachMaDt, $ngayBatDau, $ngayKetThuc, $maKhuyenMai)) {
                return ['thanhCong' => false, 'thongBao' => $loiXungDot, 'duLieu' => null, 'maHttp' => 409];
            }
        }

        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare(
                "UPDATE KhuyenMai SET ten = ?, noiDung = ?, mucUuDai = ?, ngayBatDau = ?, ngayKetThuc = ?, khungGio = ? WHERE maKhuyenMai = ?"
            )->execute([$ten, $duLieu['noiDung'] ?? null, $mucUuDai, $ngayBatDau, $ngayKetThuc, $duLieu['khungGio'] ?? '00:00-23:59', $maKhuyenMai]);

            if ($danhSachMaDt !== null) {
                $this->pdo->prepare("DELETE FROM KhuyenMai_DienThoai WHERE maKhuyenMai = ?")->execute([$maKhuyenMai]);
                $ganSp = $this->pdo->prepare("INSERT IGNORE INTO KhuyenMai_DienThoai (maKhuyenMai, maDienThoai) VALUES (?, ?)");
                foreach ($danhSachMaDt as $maDt) {
                    if ((int)$maDt > 0) {
                        $ganSp->execute([$maKhuyenMai, (int)$maDt]);
                    }
                }
            }
            $this->pdo->commit();
            return ['thanhCong' => true, 'thongBao' => 'Cập nhật chương trình khuyến mãi thành công', 'duLieu' => ['maKhuyenMai' => $maKhuyenMai]];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi cập nhật khuyến mãi: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function capNhatTrangThaiKhuyenMai(int $maKhuyenMai, string $trangThai): array
    {
        $this->pdo->prepare("UPDATE KhuyenMai SET trangThai = ? WHERE maKhuyenMai = ?")->execute([$trangThai, $maKhuyenMai]);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật trạng thái khuyến mãi thành công', 'duLieu' => ['maKhuyenMai' => $maKhuyenMai, 'trangThai' => $trangThai]];
    }

    // UC59: Xóa khuyến mãi (chặn nếu đang diễn ra; xóa bảng phụ DieuKienApDung & KhuyenMai_DienThoai trong transaction để tránh lỗi FK #1451)
    public function xoaKhuyenMai(int $maKhuyenMai): array
    {
        if ($maKhuyenMai <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Mã khuyến mãi không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM KhuyenMai WHERE maKhuyenMai = ? LIMIT 1");
        $stmt->execute([$maKhuyenMai]);
        $km = $stmt->fetch();
        if (!$km) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy chương trình khuyến mãi', 'duLieu' => null, 'maHttp' => 404];
        }

        $homNay = date('Y-m-d');
        if ($km['trangThai'] === 'Đang áp dụng' && $homNay >= $km['ngayBatDau'] && $homNay <= $km['ngayKetThuc']) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Không thể xóa chương trình khuyến mãi đang diễn ra. Vui lòng chuyển sang Ngừng áp dụng trước khi xóa',
                'duLieu'    => null,
                'maHttp'    => 409,
            ];
        }

        try {
            $this->pdo->beginTransaction();
            // Xóa bản ghi liên kết ở 2 bảng phụ trước để tránh lỗi khóa ngoại MySQL #1451
            $this->pdo->prepare("DELETE FROM DieuKienApDung WHERE maKhuyenMai = ?")->execute([$maKhuyenMai]);
            $this->pdo->prepare("DELETE FROM KhuyenMai_DienThoai WHERE maKhuyenMai = ?")->execute([$maKhuyenMai]);
            $this->pdo->prepare("DELETE FROM KhuyenMai WHERE maKhuyenMai = ?")->execute([$maKhuyenMai]);
            $this->pdo->commit();

            return ['thanhCong' => true, 'thongBao' => 'Đã xóa chương trình khuyến mãi thành công', 'duLieu' => ['maKhuyenMai' => $maKhuyenMai]];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa chương trình khuyến mãi: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }
}
