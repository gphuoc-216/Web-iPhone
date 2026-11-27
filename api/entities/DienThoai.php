<?php
// Entity BCE: DienThoai — Bảng DienThoai (Mục 3.4.3.12: Bảng 99, Bảng 100; RDM: Bảng 151)

class DienThoai
{
    public const TEN_BANG = 'DienThoai';
    public const KHOA_CHINH = 'maDienThoai';

    private ?int $maDienThoai = null;
    private string $ten = '';
    private ?int $maDanhMuc = null;
    private string $hang = '';
    private ?string $moTa = null;
    private ?string $hinhAnh = null;
    private float $giaVonBinhQuan = 0.0;
    private float $giaBan = 0.0;
    private string $trangThai = 'Đang kinh doanh';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maDienThoai', $duLieu)) {
            $this->maDienThoai = isset($duLieu['maDienThoai']) && $duLieu['maDienThoai'] !== '' ? (int)$duLieu['maDienThoai'] : null;
        }
        if (array_key_exists('ten', $duLieu)) {
            $this->ten = (string)($duLieu['ten'] ?? '');
        }
        if (array_key_exists('maDanhMuc', $duLieu)) {
            $this->maDanhMuc = isset($duLieu['maDanhMuc']) && $duLieu['maDanhMuc'] !== '' ? (int)$duLieu['maDanhMuc'] : null;
        }
        if (array_key_exists('hang', $duLieu)) {
            $this->hang = (string)($duLieu['hang'] ?? '');
        }
        if (array_key_exists('moTa', $duLieu)) {
            $this->moTa = isset($duLieu['moTa']) && $duLieu['moTa'] !== '' ? (string)$duLieu['moTa'] : null;
        }
        if (array_key_exists('hinhAnh', $duLieu)) {
            $this->hinhAnh = isset($duLieu['hinhAnh']) && $duLieu['hinhAnh'] !== '' ? (string)$duLieu['hinhAnh'] : null;
        }
        if (array_key_exists('giaVonBinhQuan', $duLieu)) {
            $this->giaVonBinhQuan = (float)($duLieu['giaVonBinhQuan'] ?? 0.0);
        }
        if (array_key_exists('giaBan', $duLieu)) {
            $this->giaBan = (float)($duLieu['giaBan'] ?? 0.0);
        }
        if (array_key_exists('trangThai', $duLieu)) {
            $this->trangThai = (string)($duLieu['trangThai'] ?? '');
        }
    }

    public function __get(string $tenThuocTinh)
    {
        return property_exists($this, $tenThuocTinh) ? $this->$tenThuocTinh : null;
    }

    public function __set(string $tenThuocTinh, $giaTri): void
    {
        if (property_exists($this, $tenThuocTinh)) {
            $this->$tenThuocTinh = $giaTri;
        }
    }

    public function __isset(string $tenThuocTinh): bool
    {
        return property_exists($this, $tenThuocTinh) && $this->$tenThuocTinh !== null;
    }

    public static function tuMang(array $duLieu): self
    {
        return new self($duLieu);
    }

    public function chuyenThanhMang(): array
    {
        return [
            'maDienThoai'    => $this->maDienThoai,
            'ten'            => $this->ten,
            'maDanhMuc'      => $this->maDanhMuc,
            'hang'           => $this->hang,
            'moTa'           => $this->moTa,
            'hinhAnh'        => $this->hinhAnh,
            'giaVonBinhQuan' => $this->giaVonBinhQuan,
            'giaBan'         => $this->giaBan,
            'trangThai'      => $this->trangThai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    // [UC4, UC5 - Hình 12, Hình 13] DienThoai::kiemTraHopLe(ten, thongSo) — Bảng 100 STT 7, Bảng 231 dòng 4
    public static function kiemTraHopLe(string $ten, $thongSo = []): bool
    {
        if (trim($ten) === '' || mb_strlen(trim($ten)) > 100) {
            return false;
        }
        $ts = ($thongSo instanceof ThongSoKyThuat) ? $thongSo->chuyenThanhMang() : (is_array($thongSo) ? $thongSo : []);
        if (!empty($ts)) {
            $ram = isset($ts['ram']) && $ts['ram'] !== '' ? (int)$ts['ram'] : 8;
            $rom = isset($ts['rom']) && $ts['rom'] !== '' ? (int)$ts['rom'] : 128;
            $pin = isset($ts['dungLuongPin']) && $ts['dungLuongPin'] !== '' ? (int)$ts['dungLuongPin'] : 4000;
            if ($ram <= 0 || $rom <= 0 || $pin <= 0) {
                return false;
            }
        }
        return true;
    }

    // [UC4, UC5 - Hình 12, Hình 13] DienThoai::kiemTraTrungTen(ten, maDienThoai) — Bảng 100 STT 11, Bảng 231 dòng 4-5
    public static function kiemTraTrungTen(string $ten, ?int $maDienThoai = 0, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if ($maDienThoai !== null && $maDienThoai > 0) {
            $stmt = $db->prepare("SELECT 1 FROM DienThoai WHERE ten = ? AND maDienThoai <> ? LIMIT 1");
            $stmt->execute([trim($ten), $maDienThoai]);
        } else {
            $stmt = $db->prepare("SELECT 1 FROM DienThoai WHERE ten = ? LIMIT 1");
            $stmt->execute([trim($ten)]);
        }
        return (bool)$stmt->fetchColumn();
    }

    // [UC8 - Hình 16] DienThoai::kiemTraKhoangGia(giaTu, giaDen) — Bảng 100 STT 8, Bảng 231 dòng 7
    public static function kiemTraKhoangGia(?float $giaTu, ?float $giaDen): bool
    {
        if ($giaTu !== null && $giaTu < 0) return false;
        if ($giaDen !== null && $giaDen < 0) return false;
        if ($giaTu !== null && $giaDen !== null && $giaTu > $giaDen) return false;
        return true;
    }

    // [UC12 - Hình 20] DienThoai::kiemTraTonTai(maDienThoai) — Bảng 100 STT 10
    public static function kiemTraTonTai(int $maDienThoai, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("SELECT 1 FROM DienThoai WHERE maDienThoai = ? LIMIT 1");
        $stmt->execute([$maDienThoai]);
        return (bool)$stmt->fetchColumn();
    }

    // [UC3 - Hình 11] DienThoai::demDienThoaiTheoDanhMuc(maDanhMuc) — Bảng 100 STT 5, Bảng 231 dòng 3
    public static function demDienThoaiTheoDanhMuc(int $maDanhMuc, ?PDO $pdo = null): int
    {
        $stmt = self::pdo($pdo)->prepare("SELECT COUNT(*) FROM DienThoai WHERE maDanhMuc = ?");
        $stmt->execute([$maDanhMuc]);
        return (int)$stmt->fetchColumn();
    }

    // [UC6 - Hình 14] DienThoai::kiemTraPhatSinhGiaoDich(maDienThoai) — Bảng 100 STT 9, Bảng 231 dòng 6
    public static function kiemTraPhatSinhGiaoDich(int $maDienThoai, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        foreach (['ChiTietDonHang', 'ChiTietPhieuNhap', 'ChiTietPhieuXuat', 'ChiTietKiemKe'] as $bang) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$bang} WHERE maDienThoai = ?");
            $stmt->execute([$maDienThoai]);
            if ((int)$stmt->fetchColumn() > 0) {
                return true;
            }
        }
        return false;
    }

    // [UC11 - Hình 19] DienThoai::kiemTraCoGiaVon(maDienThoai) — Bảng 100 STT 6
    public static function kiemTraCoGiaVon(int $maDienThoai, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("SELECT giaVonBinhQuan FROM DienThoai WHERE maDienThoai = ? LIMIT 1");
        $stmt->execute([$maDienThoai]);
        return ((float)$stmt->fetchColumn()) > 0;
    }

    // [UC11 - Hình 19] DienThoai::layGiaVonBinhQuan() — Bảng 100 STT 12
    public static function layGiaVonBinhQuan(?PDO $pdo = null): array
    {
        $rows = self::pdo($pdo)->query("SELECT maDienThoai, ten, hang, maDanhMuc, giaVonBinhQuan, giaBan FROM DienThoai")->fetchAll();
        $ketQua = [];
        foreach ($rows as $r) {
            $ketQua[(int)$r['maDienThoai']] = $r;
        }
        return $ketQua;
    }

    public static function timTatCa(?string $trangThai = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $sql = "SELECT d.*, dm.ten AS tenDanhMuc,
                       ts.maThongSo, ts.manHinh, ts.chip, ts.ram, ts.rom, ts.camera, ts.dungLuongPin, ts.heDieuHanh,
                       COALESCE(tk.tonThucTe, 0) AS tonThucTe,
                       COALESCE(tk.giuCho, 0) AS giuCho,
                       COALESCE(tk.tonThucTe - tk.giuCho, 0) AS tonKhaDung
                FROM DienThoai d
                LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                LEFT JOIN ThongSoKyThuat ts ON ts.maDienThoai = d.maDienThoai
                LEFT JOIN TonKho tk ON tk.maDienThoai = d.maDienThoai";
        $thamSo = [];
        if ($trangThai !== null && $trangThai !== '') {
            $sql .= " WHERE d.trangThai = ?";
            $thamSo[] = $trangThai;
        }
        $sql .= " ORDER BY d.maDienThoai DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($thamSo);
        return $stmt->fetchAll();
    }

    // [UC4 - UC9 - Hình 12 - Hình 17] DienThoai::timDangKinhDoanh() — Bảng 100 STT 15
    public static function timDangKinhDoanh(?PDO $pdo = null): array
    {
        return self::timTatCa('Đang kinh doanh', $pdo);
    }

    // [UC10 - Hình 18] DienThoai::timNgungKinhDoanh() — Bảng 100 STT 16
    public static function timNgungKinhDoanh(?PDO $pdo = null): array
    {
        return self::timTatCa('Ngừng kinh doanh', $pdo);
    }

    // [UC5, UC9, UC10 - Hình 13, Hình 17, Hình 18] DienThoai::timTheoMa(maDienThoai) — Bảng 100 STT 18, Bảng 231 dòng 5, 8
    public static function timTheoMa(int $maDienThoai, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT d.*, dm.ten AS tenDanhMuc,
                       ts.maThongSo, ts.manHinh, ts.chip, ts.ram, ts.rom, ts.camera, ts.dungLuongPin, ts.heDieuHanh,
                       COALESCE(tk.tonThucTe, 0) AS tonThucTe,
                       COALESCE(tk.giuCho, 0) AS giuCho,
                       COALESCE(tk.tonThucTe - tk.giuCho, 0) AS tonKhaDung
                FROM DienThoai d
                LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                LEFT JOIN ThongSoKyThuat ts ON ts.maDienThoai = d.maDienThoai
                LEFT JOIN TonKho tk ON tk.maDienThoai = d.maDienThoai
                WHERE d.maDienThoai = ? LIMIT 1";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maDienThoai]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // [UC11 - Hình 19] DienThoai::timTheoPhamVi(phamVi) — Bảng 100 STT 19
    public static function timTheoPhamVi(string $phamVi, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $pv = trim($phamVi);
        if ($pv === '' || $pv === 'Toàn bộ sản phẩm') {
            return $db->query("SELECT * FROM DienThoai ORDER BY maDienThoai ASC")->fetchAll();
        }
        $stmt = $db->prepare(
            "SELECT d.* FROM DienThoai d
             LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
             WHERE d.hang = ? OR dm.ten = ? OR CONCAT('Hãng ', d.hang) = ? OR CONCAT('Danh mục ', dm.ten) = ?"
        );
        $stmt->execute([$pv, $pv, $pv, $pv]);
        return $stmt->fetchAll();
    }

    // [UC7 - Hình 15] DienThoai::timTheoTuKhoa(tuKhoa) — Bảng 100 STT 20, Bảng 231 dòng 7
    public static function timTheoTuKhoa(string $tuKhoa, ?PDO $pdo = null): array
    {
        $tk = trim($tuKhoa);
        $sql = "SELECT d.*, dm.ten AS tenDanhMuc,
                       ts.manHinh, ts.chip, ts.ram, ts.rom, ts.camera, ts.dungLuongPin, ts.heDieuHanh,
                       COALESCE(tkh.tonThucTe, 0) AS tonThucTe,
                       COALESCE(tkh.giuCho, 0) AS giuCho,
                       COALESCE(tkh.tonThucTe - tkh.giuCho, 0) AS tonKhaDung
                FROM DienThoai d
                LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                LEFT JOIN ThongSoKyThuat ts ON ts.maDienThoai = d.maDienThoai
                LEFT JOIN TonKho tkh ON tkh.maDienThoai = d.maDienThoai
                WHERE d.trangThai = 'Đang kinh doanh'";
        $thamSo = [];
        if ($tk !== '') {
            $sql .= " AND (d.ten LIKE ? OR d.hang LIKE ? OR ts.chip LIKE ?)";
            $mau = '%' . $tk . '%';
            $thamSo = [$mau, $mau, $mau];
        }
        $sql .= " ORDER BY d.maDienThoai DESC";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($thamSo);
        return $stmt->fetchAll();
    }

    // [UC8 - Hình 16] DienThoai::timTheoDieuKienLoc(hang, giaTu, giaDen) — Bảng 100 STT 17, Bảng 231 dòng 7
    public static function timTheoDieuKienLoc($hangHoacBoLoc = null, ?float $giaTu = null, ?float $giaDen = null, ?PDO $pdo = null): array
    {
        if (is_array($hangHoacBoLoc)) {
            $boLoc = $hangHoacBoLoc;
            if ($giaTu instanceof PDO) {
                $pdo = $giaTu;
            }
        } else {
            $boLoc = [
                'hang'   => $hangHoacBoLoc,
                'giaTu'  => $giaTu,
                'giaDen' => $giaDen,
            ];
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
        if (isset($boLoc['hang']) && trim((string)$boLoc['hang']) !== '') {
            $dieuKien[] = "d.hang = ?";
            $thamSo[] = trim((string)$boLoc['hang']);
        }
        if (isset($boLoc['giaTu']) && $boLoc['giaTu'] !== '' && $boLoc['giaTu'] !== null) {
            $dieuKien[] = "d.giaBan >= ?";
            $thamSo[] = (float)$boLoc['giaTu'];
        }
        if (isset($boLoc['giaDen']) && $boLoc['giaDen'] !== '' && $boLoc['giaDen'] !== null) {
            $dieuKien[] = "d.giaBan <= ?";
            $thamSo[] = (float)$boLoc['giaDen'];
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
                       COALESCE(tk.tonThucTe - tk.giuCho, 0) AS tonKhaDung
                FROM DienThoai d
                LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                LEFT JOIN ThongSoKyThuat ts ON ts.maDienThoai = d.maDienThoai
                LEFT JOIN TonKho tk ON tk.maDienThoai = d.maDienThoai
                WHERE " . implode(' AND ', $dieuKien) . "
                ORDER BY {$sapXep}";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($thamSo);
        return $stmt->fetchAll();
    }

    // [UC55 - Hình 63] DienThoai::layThongTinHienThi(danhSachMaDienThoai) — Bảng 100 STT 13
    public static function layThongTinHienThi(array $danhSachMaDienThoai, ?PDO $pdo = null): array
    {
        if (empty($danhSachMaDienThoai)) return [];
        $ph = implode(',', array_fill(0, count($danhSachMaDienThoai), '?'));
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM DienThoai WHERE maDienThoai IN ({$ph})");
        $stmt->execute(array_map('intval', $danhSachMaDienThoai));
        $ketQua = [];
        foreach ($stmt->fetchAll() as $r) {
            $ketQua[(int)$r['maDienThoai']] = $r;
        }
        return $ketQua;
    }

    // [UC4 - Hình 12] DienThoai::luu(ten, maDanhMuc, hang, moTa, hinhAnh) — Bảng 100 STT 14, Bảng 231 dòng 4
    // Giá vốn và giá bán mặc định 0, được tính khi duyệt phiếu nhập (UC13) theo cấu hình lợi nhuận (UC11)
    public static function luu(string $ten, int $maDanhMuc, string $hang, ?string $moTa, ?string $hinhAnh, ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $anhSach = ($hinhAnh !== null && trim($hinhAnh) !== '') ? ltrim(trim($hinhAnh), '/') : 'storage/shop-assets/img/iphone17.webp';
        $db->prepare("INSERT INTO DienThoai (ten, maDanhMuc, hang, moTa, hinhAnh) VALUES (?, ?, ?, ?, ?)")
           ->execute([trim($ten), $maDanhMuc, trim($hang), $moTa, $anhSach]);
        return (int)$db->lastInsertId();
    }

    // [UC5 - Hình 13] DienThoai::capNhat(maDienThoai, ten, maDanhMuc, hang, moTa, hinhAnh) — Bảng 100 STT 1, Bảng 231 dòng 5
    // hinhAnh = null nghĩa là giữ ảnh hiện tại (người dùng không tải ảnh mới)
    public static function capNhat(int $maDienThoai, string $ten, int $maDanhMuc, string $hang, ?string $moTa, ?string $hinhAnh, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare(
            "UPDATE DienThoai SET ten = ?, maDanhMuc = ?, hang = ?, moTa = ?, hinhAnh = COALESCE(?, hinhAnh) WHERE maDienThoai = ?"
        )->execute([trim($ten), $maDanhMuc, trim($hang), $moTa, $hinhAnh !== null ? ltrim($hinhAnh, '/') : null, $maDienThoai]);
    }

    // [UC10 - Hình 18] DienThoai::capNhatTrangThai(maDienThoai, trangThai) — Bảng 100 STT 3, Bảng 231 dòng 8
    public static function capNhatTrangThai(int $maDienThoai, string $trangThai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE DienThoai SET trangThai = ? WHERE maDienThoai = ?")->execute([$trangThai, $maDienThoai]);
    }

    // [UC6 - Hình 14] DienThoai::chuyenNgungKinhDoanh(maDienThoai) — Bảng 100 STT 4, Bảng 231 dòng 6
    public static function chuyenNgungKinhDoanh(int $maDienThoai, ?PDO $pdo = null): bool
    {
        return self::capNhatTrangThai($maDienThoai, 'Ngừng kinh doanh', $pdo);
    }

    // [UC13 - Hình 21] DienThoai::capNhatGiaVonBinhQuan(maDienThoai, soLuong, donGiaNhap) — Bảng 100 STT 2
    public static function capNhatGiaVonBinhQuan(int $maDienThoai, int $soLuong, float $donGiaNhap, ?PDO $pdo = null): float
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT giaVonBinhQuan FROM DienThoai WHERE maDienThoai = ? FOR UPDATE");
        $stmt->execute([$maDienThoai]);
        $giaVonCu = (float)($stmt->fetchColumn() ?: 0);

        // Lưu ý: Trong Hình 21 (UC13), TonKho::tangTonThucTe đã cộng soLuong vào tonThucTe ngay trước bước này
        $tonHienTai = TonKho::timTheoDienThoai($maDienThoai, false, $db);
        $tongSauNhap = $tonHienTai ? max($soLuong, (int)$tonHienTai['tonThucTe']) : $soLuong;
        $tonTruocNhap = max(0, $tongSauNhap - $soLuong);

        $giaVonMoi = $tongSauNhap > 0
            ? round((($tonTruocNhap * $giaVonCu) + ($soLuong * $donGiaNhap)) / $tongSauNhap, 2)
            : $donGiaNhap;
        $db->prepare("UPDATE DienThoai SET giaVonBinhQuan = ? WHERE maDienThoai = ?")->execute([$giaVonMoi, $maDienThoai]);
        return $giaVonMoi;
    }

    // [UC11, UC13 - Hình 19, Hình 21] DienThoai::tinhLaiGiaBan(maDienThoai, tyLe) — Bảng 100 STT 21
    public static function tinhLaiGiaBan(int $maDienThoai, float $tyLe, ?PDO $pdo = null): float
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT giaVonBinhQuan, giaBan FROM DienThoai WHERE maDienThoai = ? LIMIT 1");
        $stmt->execute([$maDienThoai]);
        $row = $stmt->fetch();
        if (!$row || (float)$row['giaVonBinhQuan'] <= 0) {
            return (float)($row['giaBan'] ?? 0);
        }
        $giaBanMoi = round(((float)$row['giaVonBinhQuan']) * (1 + $tyLe / 100), 2);
        $db->prepare("UPDATE DienThoai SET giaBan = ? WHERE maDienThoai = ?")->execute([$giaBanMoi, $maDienThoai]);
        return $giaBanMoi;
    }

    // [UC6 - Hình 14] DienThoai::xoa(maDienThoai) — Bảng 100 STT 22, Bảng 231 dòng 6
    // Chỉ gọi khi kiemTraPhatSinhGiaoDich = false: dọn bản ghi phụ thuộc có FK RESTRICT (TonKho 1-0..1, dòng giỏ hàng chưa đặt);
    // ThongSoKyThuat và KhuyenMai_DienThoai tự xóa theo ON DELETE CASCADE
    public static function xoa(int $maDienThoai, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $db->prepare("DELETE FROM TonKho WHERE maDienThoai = ?")->execute([$maDienThoai]);
        $db->prepare("DELETE FROM ChiTietGioHang WHERE maDienThoai = ?")->execute([$maDienThoai]);
        return $db->prepare("DELETE FROM DienThoai WHERE maDienThoai = ?")->execute([$maDienThoai]);
    }
}
