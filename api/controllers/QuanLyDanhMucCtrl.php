<?php
// Controller BCE: QuanLyDanhMucCtrl (UC1 - UC3)

class QuanLyDanhMucCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function layDanhSachDanhMuc(): array
    {
        $cauLenh = $this->pdo->query("SELECT maDanhMuc, ten, moTa FROM DanhMuc ORDER BY maDanhMuc ASC");
        $ketQua = [];
        foreach ($cauLenh->fetchAll() as $dong) {
            $ketQua[] = DanhMuc::tuMang($dong)->chuyenThanhMang();
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách danh mục thành công', 'duLieu' => $ketQua];
    }

    public function themDanhMuc(array $duLieu): array
    {
        $ten = trim((string)($duLieu['ten'] ?? ''));
        $moTa = isset($duLieu['moTa']) ? trim((string)$duLieu['moTa']) : null;

        if ($ten === '') {
            return ['thanhCong' => false, 'thongBao' => 'Tên danh mục không được để trống', 'duLieu' => null, 'maHttp' => 400];
        }

        $kiemTra = $this->pdo->prepare("SELECT maDanhMuc FROM DanhMuc WHERE ten = ? LIMIT 1");
        $kiemTra->execute([$ten]);
        if ($kiemTra->fetch()) {
            return ['thanhCong' => false, 'thongBao' => 'Tên danh mục đã tồn tại trong hệ thống', 'duLieu' => null, 'maHttp' => 409];
        }

        $them = $this->pdo->prepare("INSERT INTO DanhMuc (ten, moTa) VALUES (?, ?)");
        $them->execute([$ten, $moTa]);

        $danhMuc = new DanhMuc([
            'maDanhMuc' => (int)$this->pdo->lastInsertId(),
            'ten'       => $ten,
            'moTa'      => $moTa,
        ]);

        return ['thanhCong' => true, 'thongBao' => 'Thêm danh mục thành công', 'duLieu' => $danhMuc->chuyenThanhMang(), 'maHttp' => 201];
    }

    public function capNhatDanhMuc(int $maDanhMuc, array $duLieu): array
    {
        $ten = trim((string)($duLieu['ten'] ?? ''));
        $moTa = isset($duLieu['moTa']) ? trim((string)$duLieu['moTa']) : null;

        if ($maDanhMuc <= 0 || $ten === '') {
            return ['thanhCong' => false, 'thongBao' => 'Mã danh mục và tên danh mục không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $kiemTraTrung = $this->pdo->prepare("SELECT maDanhMuc FROM DanhMuc WHERE ten = ? AND maDanhMuc <> ? LIMIT 1");
        $kiemTraTrung->execute([$ten, $maDanhMuc]);
        if ($kiemTraTrung->fetch()) {
            return ['thanhCong' => false, 'thongBao' => 'Tên danh mục đã trùng với danh mục khác', 'duLieu' => null, 'maHttp' => 409];
        }

        $capNhat = $this->pdo->prepare("UPDATE DanhMuc SET ten = ?, moTa = ? WHERE maDanhMuc = ?");
        $capNhat->execute([$ten, $moTa, $maDanhMuc]);

        $danhMuc = new DanhMuc(['maDanhMuc' => $maDanhMuc, 'ten' => $ten, 'moTa' => $moTa]);
        return ['thanhCong' => true, 'thongBao' => 'Cập nhật danh mục thành công', 'duLieu' => $danhMuc->chuyenThanhMang()];
    }

    public function xoaDanhMuc(int $maDanhMuc): array
    {
        if ($maDanhMuc <= 0) {
            return ['thanhCong' => false, 'thongBao' => 'Mã danh mục không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
        }

        $demSanPham = $this->pdo->prepare("SELECT COUNT(*) FROM DienThoai WHERE maDanhMuc = ?");
        $demSanPham->execute([$maDanhMuc]);
        if ((int)$demSanPham->fetchColumn() > 0) {
            return ['thanhCong' => false, 'thongBao' => 'Không thể xóa danh mục đang có điện thoại thuộc danh mục này', 'duLieu' => null, 'maHttp' => 409];
        }

        $xoa = $this->pdo->prepare("DELETE FROM DanhMuc WHERE maDanhMuc = ?");
        $xoa->execute([$maDanhMuc]);

        return ['thanhCong' => true, 'thongBao' => 'Xóa danh mục thành công', 'duLieu' => ['maDanhMuc' => $maDanhMuc]];
    }
}
