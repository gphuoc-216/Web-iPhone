<?php
// Controller BCE: ChinhSachGiaCtrl (UC11)

class ChinhSachGiaCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layCauHinhLoiNhuan(): array
    {
        $cauHinh = $this->pdo->query("SELECT * FROM CauHinhLoiNhuan ORDER BY maCauHinh DESC")->fetchAll();
        $lichSu = $this->pdo->query("SELECT * FROM LichSuCauHinhLoiNhuan ORDER BY thoiDiemGhi DESC LIMIT 50")->fetchAll();

        return [
            'thanhCong' => true,
            'thongBao'  => 'Lấy chính sách cấu hình lợi nhuận thành công',
            'duLieu'    => [
                'danhSachCauHinh' => array_map(fn($d) => CauHinhLoiNhuan::tuMang($d)->chuyenThanhMang(), $cauHinh),
                'lichSuThayDoi'   => array_map(fn($d) => LichSuCauHinhLoiNhuan::tuMang($d)->chuyenThanhMang(), $lichSu),
            ],
        ];
    }

    public function capNhatChinhSachLoiNhuan(array $duLieu): array
    {
        $phamVi = trim((string)($duLieu['phamVi'] ?? 'Toàn bộ sản phẩm'));
        $tyLe = (float)($duLieu['tyLe'] ?? -1);
        $ngayApDung = trim((string)($duLieu['ngayApDung'] ?? date('Y-m-d')));
        $ghiChu = isset($duLieu['ghiChu']) ? trim((string)$duLieu['ghiChu']) : null;
        $tuDongCapNhatGiaBan = (bool)($duLieu['tuDongCapNhatGiaBan'] ?? true);

        if ($tyLe < 0 || $tyLe > 500) {
            return ['thanhCong' => false, 'thongBao' => 'Tỷ lệ lợi nhuận phải nằm trong khoảng từ 0% đến 500%', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            $this->pdo->beginTransaction();

            $kiemTra = $this->pdo->prepare("SELECT maCauHinh FROM CauHinhLoiNhuan WHERE phamVi = ? LIMIT 1");
            $kiemTra->execute([$phamVi]);
            $hienCo = $kiemTra->fetch();

            if ($hienCo) {
                $maCauHinh = (int)$hienCo['maCauHinh'];
                $this->pdo->prepare(
                    "UPDATE CauHinhLoiNhuan SET tyLe = ?, ngayApDung = ?, ghiChu = ? WHERE maCauHinh = ?"
                )->execute([$tyLe, $ngayApDung, $ghiChu, $maCauHinh]);
            } else {
                $this->pdo->prepare(
                    "INSERT INTO CauHinhLoiNhuan (phamVi, tyLe, ngayApDung, ghiChu) VALUES (?, ?, ?, ?)"
                )->execute([$phamVi, $tyLe, $ngayApDung, $ghiChu]);
                $maCauHinh = (int)$this->pdo->lastInsertId();
            }

            $this->pdo->prepare(
                "INSERT INTO LichSuCauHinhLoiNhuan (maCauHinh, phamVi, tyLe, ngayApDung, thoiDiemGhi)
                 VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)"
            )->execute([$maCauHinh, $phamVi, $tyLe, $ngayApDung]);

            $soSanPhamCapNhat = 0;
            $danhSachChuaTinhDuocGia = [];

            if ($tuDongCapNhatGiaBan) {
                // Tìm các sản phẩm trong phạm vi chưa có giá vốn bình quân (chưa từng duyệt phiếu nhập)
                if ($phamVi === 'Toàn bộ sản phẩm') {
                    $chuaCoVonStmt = $this->pdo->query(
                        "SELECT maDienThoai, ten, giaBan FROM DienThoai WHERE giaVonBinhQuan <= 0"
                    );
                    $danhSachChuaTinhDuocGia = $chuaCoVonStmt->fetchAll();

                    $heSo = 1 + ($tyLe / 100);
                    $stmt = $this->pdo->prepare("UPDATE DienThoai SET giaBan = ROUND(giaVonBinhQuan * ?, 2) WHERE giaVonBinhQuan > 0");
                    $stmt->execute([$heSo]);
                    $soSanPhamCapNhat = $stmt->rowCount();
                } else {
                    $chuaCoVonStmt = $this->pdo->prepare(
                        "SELECT d.maDienThoai, d.ten, d.giaBan
                         FROM DienThoai d
                         LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                         WHERE d.giaVonBinhQuan <= 0 AND (d.hang = ? OR dm.ten = ?)"
                    );
                    $chuaCoVonStmt->execute([$phamVi, $phamVi]);
                    $danhSachChuaTinhDuocGia = $chuaCoVonStmt->fetchAll();

                    $heSo = 1 + ($tyLe / 100);
                    $stmt = $this->pdo->prepare(
                        "UPDATE DienThoai d
                         LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                         SET d.giaBan = ROUND(d.giaVonBinhQuan * ?, 2)
                         WHERE d.giaVonBinhQuan > 0 AND (d.hang = ? OR dm.ten = ?)"
                    );
                    $stmt->execute([$heSo, $phamVi, $phamVi]);
                    $soSanPhamCapNhat = $stmt->rowCount();
                }
            }

            $this->pdo->commit();

            $chuaTinhSach = array_map(fn($r) => [
                'maDienThoai' => (int)$r['maDienThoai'],
                'ten'         => $r['ten'],
                'giaBan'      => (float)$r['giaBan'],
            ], $danhSachChuaTinhDuocGia);

            $thongBao = 'Cập nhật cấu hình lợi nhuận và ghi lịch sử thành công';
            if (!empty($chuaTinhSach)) {
                $thongBao .= '. Lưu ý: có ' . count($chuaTinhSach) . ' sản phẩm chưa có giá vốn bình quân nên giữ nguyên giá bán cũ';
            }

            return [
                'thanhCong' => true,
                'thongBao'  => $thongBao,
                'duLieu'    => [
                    'maCauHinh'               => $maCauHinh,
                    'phamVi'                  => $phamVi,
                    'tyLe'                    => $tyLe,
                    'soSanPhamCapNhat'        => $soSanPhamCapNhat,
                    'danhSachChuaTinhDuocGia' => $chuaTinhSach,
                ],
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi cập nhật chính sách giá: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }
}
