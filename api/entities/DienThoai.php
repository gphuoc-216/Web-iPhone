<?php
// Entity BCE: DienThoai — Bảng DienThoai

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
        if (property_exists($this, $tenThuocTinh)) {
            return $this->$tenThuocTinh;
        }
        return null;
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
            'maDienThoai' => $this->maDienThoai,
            'ten' => $this->ten,
            'maDanhMuc' => $this->maDanhMuc,
            'hang' => $this->hang,
            'moTa' => $this->moTa,
            'hinhAnh' => $this->hinhAnh,
            'giaVonBinhQuan' => $this->giaVonBinhQuan,
            'giaBan' => $this->giaBan,
            'trangThai' => $this->trangThai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraHopLe(string $ten, array $thongSo = []): bool
    {
        if (trim($ten) === '') {
            return false;
        }
        if (!empty($thongSo)) {
            $ram = (int)($thongSo['ram'] ?? 8);
            $rom = (int)($thongSo['rom'] ?? 128);
            $pin = (int)($thongSo['dungLuongPin'] ?? 4000);
            if ($ram <= 0 || $rom <= 0 || $pin <= 0) {
                return false;
            }
        }
        return true;
    }

    public static function kiemTraKhoangGia(?float $giaTu, ?float $giaDen): bool
    {
        if ($giaTu !== null && $giaTu < 0) return false;
        if ($giaDen !== null && $giaDen < 0) return false;
        if ($giaTu !== null && $giaDen !== null && $giaTu > $giaDen) return false;
        return true;
    }

    public static function kiemTraTonTai(int $maDienThoai, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("SELECT 1 FROM DienThoai WHERE maDienThoai = ? LIMIT 1");
        $stmt->execute([$maDienThoai]);
        return (bool)$stmt->fetchColumn();
    }

    public static function demDienThoaiTheoDanhMuc(int $maDanhMuc, ?PDO $pdo = null): int
    {
        $stmt = self::pdo($pdo)->prepare("SELECT COUNT(*) FROM DienThoai WHERE maDanhMuc = ?");
        $stmt->execute([$maDanhMuc]);
        return (int)$stmt->fetchColumn();
    }

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

    public static function kiemTraCoGiaVon(int $maDienThoai, ?PDO $pdo = null): bool
    {
        $stmt = self::pdo($pdo)->prepare("SELECT giaVonBinhQuan FROM DienThoai WHERE maDienThoai = ? LIMIT 1");
        $stmt->execute([$maDienThoai]);
        return ((float)$stmt->fetchColumn()) > 0;
    }

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
        $stmt = $db->prepare($sql);
        $stmt->execute($thamSo);
        return $stmt->fetchAll();
    }

    public static function timDangKinhDoanh(?PDO $pdo = null): array
    {
        return self::timTatCa('Đang kinh doanh', $pdo);
    }

    public static function timNgungKinhDoanh(?PDO $pdo = null): array
    {
        return self::timTatCa('Ngừng kinh doanh', $pdo);
    }

    public static function timTheoMa(int $maDienThoai, ?PDO $pdo = null): ?array
    {
        $sql = "SELECT d.*, dm.ten AS tenDanhMuc,
                       ts.maThongSo, ts.manHinh, ts.chip, ts.ram, ts.rom, ts.camera, ts.dungLuongPin, ts.heDieuHanh,
                       COALESCE(tk.tonThucTe, 0) AS tonThucTe,
                       COALESCE(tk.giuCho, 0) AS giuCho,
                       COALESCE(tk.tonKhaDung, 0) AS tonKhaDung
                FROM DienThoai d
                LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                LEFT JOIN ThongSoKyThuat ts ON ts.maDienThoai = d.maDienThoai
                LEFT JOIN TonKho tk ON tk.maDienThoai = d.maDienThoai AND tk.maKho = 1
                WHERE d.maDienThoai = ? LIMIT 1";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute([$maDienThoai]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function timTheoTuKhoa(string $tuKhoa, ?PDO $pdo = null): array
    {
        return self::timTheoDieuKienLoc(['tuKhoa' => $tuKhoa], $pdo);
    }

    public static function timTheoDieuKienLoc(array $boLoc = [], ?PDO $pdo = null): array
    {
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
        if (!empty($boLoc['hang'])) {
            $dieuKien[] = "d.hang = ?";
            $thamSo[] = trim((string)$boLoc['hang']);
        }
        if (isset($boLoc['giaTu']) && $boLoc['giaTu'] !== '') {
            $dieuKien[] = "d.giaBan >= ?";
            $thamSo[] = (float)$boLoc['giaTu'];
        }
        if (isset($boLoc['giaDen']) && $boLoc['giaDen'] !== '') {
            $dieuKien[] = "d.giaBan <= ?";
            $thamSo[] = (float)$boLoc['giaDen'];
        }
        if (!empty($boLoc['ram'])) {
            $dieuKien[] = "ts.ram = ?";
            $thamSo[] = (int)$boLoc['ram'];
        }
        if (!empty($boLoc['rom'])) {
            $dieuKien[] = "ts.rom = ?";
            $thamSo[] = (int)$boLoc['rom'];
        }
        if (!empty($boLoc['pinTu']) || !empty($boLoc['dungLuongPin'])) {
            $dieuKien[] = "ts.dungLuongPin >= ?";
            $thamSo[] = (int)($boLoc['pinTu'] ?? $boLoc['dungLuongPin']);
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
                       COALESCE(tk.tonKhaDung, 0) AS tonKhaDung
                FROM DienThoai d
                LEFT JOIN DanhMuc dm ON dm.maDanhMuc = d.maDanhMuc
                LEFT JOIN ThongSoKyThuat ts ON ts.maDienThoai = d.maDienThoai
                LEFT JOIN TonKho tk ON tk.maDienThoai = d.maDienThoai AND tk.maKho = 1
                WHERE " . implode(' AND ', $dieuKien) . "
                ORDER BY {$sapXep}";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($thamSo);
        return $stmt->fetchAll();
    }

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

    public static function luu(string $ten, int $maDanhMuc, string $hang, ?string $moTa, string $hinhAnh, float $giaVonBinhQuan = 0.0, float $giaBan = 0.0, string $trangThai = 'Đang kinh doanh', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare(
            "INSERT INTO DienThoai (ten, maDanhMuc, hang, moTa, hinhAnh, giaVonBinhQuan, giaBan, trangThai)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([trim($ten), $maDanhMuc, trim($hang), $moTa, ltrim($hinhAnh, '/'), max(0, $giaVonBinhQuan), max(0, $giaBan), $trangThai]);
        return (int)$db->lastInsertId();
    }

    public static function capNhat(int $maDienThoai, array $duLieu, ?PDO $pdo = null): bool
    {
        $cot = [];
        $thamSo = [];
        foreach (['ten', 'maDanhMuc', 'hang', 'moTa', 'hinhAnh', 'giaVonBinhQuan', 'giaBan', 'trangThai'] as $k) {
            if (array_key_exists($k, $duLieu) && $duLieu[$k] !== null) {
                $cot[] = "{$k} = ?";
                $thamSo[] = ($k === 'hinhAnh') ? ltrim((string)$duLieu[$k], '/') : $duLieu[$k];
            }
        }
        if (empty($cot)) return true;
        $thamSo[] = $maDienThoai;
        $sql = "UPDATE DienThoai SET " . implode(', ', $cot) . " WHERE maDienThoai = ?";
        return self::pdo($pdo)->prepare($sql)->execute($thamSo);
    }

    public static function capNhatTrangThai(int $maDienThoai, string $trangThai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE DienThoai SET trangThai = ? WHERE maDienThoai = ?")->execute([$trangThai, $maDienThoai]);
    }

    public static function chuyenNgungKinhDoanh(int $maDienThoai, ?PDO $pdo = null): bool
    {
        return self::capNhatTrangThai($maDienThoai, 'Ngừng kinh doanh', $pdo);
    }

    public static function capNhatGiaVonBinhQuan(int $maDienThoai, int $tonCu, int $soLuongNhap, float $donGiaNhap, ?PDO $pdo = null): float
    {
        $db = self::pdo($pdo);
        $stmt = $db->prepare("SELECT giaVonBinhQuan FROM DienThoai WHERE maDienThoai = ? FOR UPDATE");
        $stmt->execute([$maDienThoai]);
        $giaVonCu = (float)($stmt->fetchColumn() ?: 0);
        $tongMoi = max(0, $tonCu) + $soLuongNhap;
        $giaVonMoi = $tongMoi > 0 ? round(((max(0, $tonCu) * $giaVonCu) + ($soLuongNhap * $donGiaNhap)) / $tongMoi, 2) : $donGiaNhap;
        $db->prepare("UPDATE DienThoai SET giaVonBinhQuan = ? WHERE maDienThoai = ?")->execute([$giaVonMoi, $maDienThoai]);
        return $giaVonMoi;
    }

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

    public static function xoa(int $maDienThoai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("DELETE FROM DienThoai WHERE maDienThoai = ?")->execute([$maDienThoai]);
    }

}
