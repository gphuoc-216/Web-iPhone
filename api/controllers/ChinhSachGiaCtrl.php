<?php
// Controller BCE: ChinhSachGiaCtrl (UC11) — Phụ lục B.1 Lan03

class ChinhSachGiaCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layCauHinhLoiNhuan(): array
    {
        return [
            'thanhCong' => true,
            'thongBao'  => 'Lấy chính sách cấu hình lợi nhuận thành công',
            'duLieu'    => [
                'danhSachCauHinh' => CauHinhLoiNhuan::timTatCa($this->pdo),
                'lichSuThayDoi'   => LichSuCauHinhLoiNhuan::timTatCa($this->pdo),
            ],
        ];
    }

    // UC11: luuCauHinhLoiNhuan(phamVi, tyLe, ngayApDung, ghiChu)
    public function luuCauHinhLoiNhuan(array $duLieu): array
    {
        $phamVi = trim((string)($duLieu['phamVi'] ?? 'Toàn bộ sản phẩm'));
        $tyLe = (float)($duLieu['tyLe'] ?? -1);
        $ngayApDung = trim((string)($duLieu['ngayApDung'] ?? date('Y-m-d')));
        $ghiChu = isset($duLieu['ghiChu']) ? trim((string)$duLieu['ghiChu']) : null;

        if (!CauHinhLoiNhuan::kiemTraHopLe($tyLe, $ngayApDung)) {
            return ['thanhCong' => false, 'thongBao' => 'Tỷ lệ lợi nhuận phải nằm trong khoảng từ 0% đến 500% và ngày áp dụng hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            batDauGiaoDich($this->pdo);

            $maCauHinh = CauHinhLoiNhuan::luu($phamVi, $tyLe, $ngayApDung, $ghiChu, $this->pdo);
            LichSuCauHinhLoiNhuan::ghiLichSu($maCauHinh, $phamVi, $tyLe, $ngayApDung, $this->pdo);

            $soSanPhamCapNhat = 0;
            $danhSachChuaTinhDuocGia = [];

            $tatCaGiaVon = DienThoai::layGiaVonBinhQuan($this->pdo);
            $danhMucMap = [];
            foreach (DanhMuc::timTatCa($this->pdo) as $dm) {
                $danhMucMap[(int)$dm['maDanhMuc']] = $dm['ten'];
            }

            foreach ($tatCaGiaVon as $maDt => $thongTin) {
                $tenDm = $danhMucMap[(int)$thongTin['maDanhMuc']] ?? '';
                if ($phamVi !== 'Toàn bộ sản phẩm' && $thongTin['hang'] !== $phamVi && $tenDm !== $phamVi) {
                    continue;
                }
                if (!DienThoai::kiemTraCoGiaVon($maDt, $this->pdo)) {
                    $danhSachChuaTinhDuocGia[] = [
                        'maDienThoai' => $maDt,
                        'ten'         => $thongTin['ten'],
                        'giaBan'      => (float)$thongTin['giaBan'],
                    ];
                } else {
                    DienThoai::tinhLaiGiaBan($maDt, $tyLe, $this->pdo);
                    $soSanPhamCapNhat++;
                }
            }

            xacNhanGiaoDich($this->pdo);

            $thongBao = 'Cập nhật cấu hình lợi nhuận và ghi lịch sử thành công';
            if (!empty($danhSachChuaTinhDuocGia)) {
                $thongBao .= '. Lưu ý: có ' . count($danhSachChuaTinhDuocGia) . ' sản phẩm chưa có giá vốn bình quân nên giữ nguyên giá bán cũ';
            }

            return [
                'thanhCong' => true,
                'thongBao'  => $thongBao,
                'duLieu'    => [
                    'maCauHinh'               => $maCauHinh,
                    'phamVi'                  => $phamVi,
                    'tyLe'                    => $tyLe,
                    'soSanPhamCapNhat'        => $soSanPhamCapNhat,
                    'danhSachChuaTinhDuocGia' => $danhSachChuaTinhDuocGia,
                ],
            ];
        } catch (Throwable $ngoaiLe) {
            hoanTacGiaoDich($this->pdo);
            error_log('luuCauHinhLoiNhuan: ' . $ngoaiLe->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không thể cập nhật chính sách giá', 'duLieu' => null, 'maHttp' => 500];
        }
    }
}
