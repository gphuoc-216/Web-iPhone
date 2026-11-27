<?php
// Controller BCE: QuanLyDanhMucCtrl (UC1 - UC3) — Phụ lục B.1 Lan03

class QuanLyDanhMucCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDanhSachDanhMuc(): array
    {
        return [
            'thanhCong' => true,
            'thongBao'  => 'Lấy danh sách danh mục thành công',
            'duLieu'    => DanhMuc::timTatCa($this->pdo),
        ];
    }

    public function layThongTinDanhMuc(int $maDanhMuc): array
    {
        $dm = DanhMuc::timTheoMa($maDanhMuc, $this->pdo);
        if (!$dm) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy danh mục', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin danh mục thành công', 'duLieu' => $dm->chuyenThanhMang()];
    }

    // UC1: luuDanhMuc(ten, moTa)
    public function luuDanhMuc(array $duLieu): array
    {
        $ten = trim((string)($duLieu['ten'] ?? ''));
        $moTa = isset($duLieu['moTa']) ? trim((string)$duLieu['moTa']) : null;

        if (!DanhMuc::kiemTraHopLe($ten)) {
            return ['thanhCong' => false, 'thongBao' => 'Tên danh mục không hợp lệ hoặc để trống', 'duLieu' => null, 'maHttp' => 400];
        }
        if (DanhMuc::kiemTraTrungTen($ten, null, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Tên danh mục đã tồn tại trong hệ thống', 'duLieu' => null, 'maHttp' => 409];
        }

        $maMoi = DanhMuc::luu($ten, $moTa, $this->pdo);
        $dm = new DanhMuc(['maDanhMuc' => $maMoi, 'ten' => $ten, 'moTa' => $moTa]);
        return ['thanhCong' => true, 'thongBao' => 'Thêm danh mục thành công', 'duLieu' => $dm->chuyenThanhMang(), 'maHttp' => 201];
    }

    // UC2: capNhatDanhMuc(maDanhMuc, ten, moTa)
    public function capNhatDanhMuc(int $maDanhMuc, array $duLieu): array
    {
        $ten = trim((string)($duLieu['ten'] ?? ''));
        $moTa = isset($duLieu['moTa']) ? trim((string)$duLieu['moTa']) : null;

        if ($maDanhMuc <= 0 || !DanhMuc::kiemTraHopLe($ten)) {
            return ['thanhCong' => false, 'thongBao' => 'Mã danh mục hoặc tên danh mục không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        if (!DanhMuc::kiemTraTonTai($maDanhMuc, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy danh mục cần cập nhật', 'duLieu' => null, 'maHttp' => 404];
        }
        if (DanhMuc::kiemTraTrungTen($ten, $maDanhMuc, $this->pdo)) {
            return ['thanhCong' => false, 'thongBao' => 'Tên danh mục đã trùng với danh mục khác', 'duLieu' => null, 'maHttp' => 409];
        }

        DanhMuc::capNhat($maDanhMuc, $ten, $moTa, $this->pdo);
        $dm = new DanhMuc(['maDanhMuc' => $maDanhMuc, 'ten' => $ten, 'moTa' => $moTa]);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật danh mục thành công', 'duLieu' => $dm->chuyenThanhMang()];
    }

    // UC3: xoaDanhMuc(maDanhMuc)
    public function xoaDanhMuc(int $maDanhMuc): array
    {
        if ($maDanhMuc <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Mã danh mục không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }
        if (DienThoai::demDienThoaiTheoDanhMuc($maDanhMuc, $this->pdo) > 0) {
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa danh mục đang có điện thoại thuộc danh mục này', 'duLieu' => null, 'maHttp' => 409];
        }

        DanhMuc::xoa($maDanhMuc, $this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Xóa danh mục thành công', 'duLieu' => ['maDanhMuc' => $maDanhMuc]];
    }
}
