<?php
// Controller BCE: QuanLyDanhMucCtrl (UC1 - UC3)
// Khớp 1-1 Sequence Diagram Hình 9 - Hình 11 và Bảng 231 dòng 1 - 3 (Báo cáo v4)

class QuanLyDanhMucCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // [UC1, UC2, UC3 - Hình 9] layDanhSachDanhMuc() -> DanhMuc::timTatCa()
    public function layDanhSachDanhMuc(): array
    {
        $danhSachDanhMuc = DanhMuc::timTatCa($this->pdo);
        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách danh mục thành công', 'duLieu' => $danhSachDanhMuc];
    }

    // [UC2 - Hình 10] layThongTinDanhMuc(maDanhMuc) -> DanhMuc::timTheoMa(maDanhMuc)
    public function layThongTinDanhMuc(int $maDanhMuc): array
    {
        $danhMuc = DanhMuc::timTheoMa($maDanhMuc, $this->pdo);
        if (!$danhMuc) {
            return ['thanhCong' => false, 'thongBao' => 'Không tìm thấy danh mục', 'duLieu' => null, 'maHttp' => 404];
        }
        return ['thanhCong' => true, 'thongBao' => 'Lấy thông tin danh mục thành công', 'duLieu' => $danhMuc->chuyenThanhMang()];
    }

    // [UC1 - Hình 9] luuDanhMuc(ten, moTa) -> kiemTraHopLe(ten) -> kiemTraTrungTen(ten, 0) -> luu(ten, moTa) -> timTatCa()
    public function luuDanhMuc(string $ten, ?string $moTa = null): array
    {
        $ten = trim($ten);
        $ketQuaKiemTra = DanhMuc::kiemTraHopLe($ten);
        if (!$ketQuaKiemTra) {
            return ['thanhCong' => false, 'thongBao' => 'Tên danh mục không được để trống', 'duLieu' => null, 'maHttp' => 400];
        }

        $daTrungTen = DanhMuc::kiemTraTrungTen($ten, 0, $this->pdo);
        if ($daTrungTen) {
            return ['thanhCong' => false, 'thongBao' => 'Tên danh mục đã tồn tại', 'duLieu' => null, 'maHttp' => 409];
        }

        $maDanhMuc = DanhMuc::luu($ten, $moTa, $this->pdo);
        $danhSachDanhMuc = DanhMuc::timTatCa($this->pdo);
        return [
            'thanhCong' => true,
            'thongBao'  => 'Lưu thành công',
            'duLieu'    => ['maDanhMuc' => $maDanhMuc, 'danhSachDanhMuc' => $danhSachDanhMuc],
            'maHttp'    => 201,
        ];
    }

    // [UC2 - Hình 10] capNhatDanhMuc(maDanhMuc, ten, moTa) -> kiemTraHopLe(ten) -> kiemTraTrungTen(ten, maDanhMuc) -> capNhat(maDanhMuc, ten, moTa) -> timTatCa()
    public function capNhatDanhMuc(int $maDanhMuc, string $ten, ?string $moTa = null): array
    {
        $ten = trim($ten);
        $ketQuaKiemTra = DanhMuc::kiemTraHopLe($ten);
        if (!$ketQuaKiemTra) {
            return ['thanhCong' => false, 'thongBao' => 'Tên danh mục không được để trống', 'duLieu' => null, 'maHttp' => 400];
        }

        $daTrungTen = DanhMuc::kiemTraTrungTen($ten, $maDanhMuc, $this->pdo);
        if ($daTrungTen) {
            return ['thanhCong' => false, 'thongBao' => 'Tên danh mục đã tồn tại', 'duLieu' => null, 'maHttp' => 409];
        }

        DanhMuc::capNhat($maDanhMuc, $ten, $moTa, $this->pdo);
        $danhSachDanhMuc = DanhMuc::timTatCa($this->pdo);
        return [
            'thanhCong' => true,
            'thongBao'  => 'Lưu thành công',
            'duLieu'    => ['maDanhMuc' => $maDanhMuc, 'danhSachDanhMuc' => $danhSachDanhMuc],
        ];
    }

    // [UC3 - Hình 11] xoaDanhMuc(maDanhMuc) -> DienThoai::demDienThoaiTheoDanhMuc(maDanhMuc) -> DanhMuc::xoa(maDanhMuc) -> DanhMuc::timTatCa()
    public function xoaDanhMuc(int $maDanhMuc): array
    {
        $soLuong = DienThoai::demDienThoaiTheoDanhMuc($maDanhMuc, $this->pdo);
        if ($soLuong > 0) {
            return [
                'thanhCong' => false,
                'thongBao'  => 'Không thể xóa danh mục đang chứa sản phẩm. Vui lòng chuyển sản phẩm sang danh mục khác trước.',
                'duLieu'    => null,
                'maHttp'    => 409,
            ];
        }

        DanhMuc::xoa($maDanhMuc, $this->pdo);
        $danhSachDanhMuc = DanhMuc::timTatCa($this->pdo);
        return [
            'thanhCong' => true,
            'thongBao'  => 'Xóa thành công',
            'duLieu'    => ['maDanhMuc' => $maDanhMuc, 'danhSachDanhMuc' => $danhSachDanhMuc],
        ];
    }
}
