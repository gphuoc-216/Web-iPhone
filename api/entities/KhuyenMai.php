<?php
// Entity BCE: KhuyenMai — Bảng KhuyenMai & KhuyenMai_DienThoai

class KhuyenMai
{
    public const TEN_BANG = 'KhuyenMai';
    public const KHOA_CHINH = 'maKhuyenMai';

    private ?int $maKhuyenMai = null;
    private string $ten = '';
    private ?string $noiDung = null;
    private float $mucUuDai = 0.0;
    private ?string $ngayBatDau = null;
    private ?string $ngayKetThuc = null;
    private ?string $khungGio = null;
    private string $trangThai = 'Đang áp dụng';

    public function __construct(array $duLieu = [])
    {
        if (array_key_exists('maKhuyenMai', $duLieu)) {
            $this->maKhuyenMai = isset($duLieu['maKhuyenMai']) && $duLieu['maKhuyenMai'] !== '' ? (int)$duLieu['maKhuyenMai'] : null;
        }
        if (array_key_exists('ten', $duLieu)) {
            $this->ten = (string)($duLieu['ten'] ?? '');
        }
        if (array_key_exists('noiDung', $duLieu)) {
            $this->noiDung = isset($duLieu['noiDung']) && $duLieu['noiDung'] !== '' ? (string)$duLieu['noiDung'] : null;
        }
        if (array_key_exists('mucUuDai', $duLieu)) {
            $this->mucUuDai = (float)($duLieu['mucUuDai'] ?? 0.0);
        }
        if (array_key_exists('ngayBatDau', $duLieu)) {
            $this->ngayBatDau = isset($duLieu['ngayBatDau']) && $duLieu['ngayBatDau'] !== '' ? (string)$duLieu['ngayBatDau'] : null;
        }
        if (array_key_exists('ngayKetThuc', $duLieu)) {
            $this->ngayKetThuc = isset($duLieu['ngayKetThuc']) && $duLieu['ngayKetThuc'] !== '' ? (string)$duLieu['ngayKetThuc'] : null;
        }
        if (array_key_exists('khungGio', $duLieu)) {
            $this->khungGio = isset($duLieu['khungGio']) && $duLieu['khungGio'] !== '' ? (string)$duLieu['khungGio'] : null;
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
            'maKhuyenMai' => $this->maKhuyenMai,
            'ten' => $this->ten,
            'noiDung' => $this->noiDung,
            'mucUuDai' => $this->mucUuDai,
            'ngayBatDau' => $this->ngayBatDau,
            'ngayKetThuc' => $this->ngayKetThuc,
            'khungGio' => $this->khungGio,
            'trangThai' => $this->trangThai,
        ];
    }

    protected static function pdo(?PDO $pdo = null): PDO
    {
        return $pdo ?? layKetNoiCsdl(true);
    }

    public static function kiemTraHopLe(string $ten, float $mucUuDai, string $ngayBatDau, string $ngayKetThuc): bool
    {
        return trim($ten) !== '' && $mucUuDai > 0 && $mucUuDai <= 100 && $ngayBatDau !== '' && $ngayKetThuc >= $ngayBatDau;
    }

    public static function kiemTraDangSuDung(int $maKhuyenMai, ?PDO $pdo = null): bool
    {
        $km = self::timTheoMa($maKhuyenMai, $pdo);
        if (!$km) return false;
        $homNay = date('Y-m-d');
        return $km['trangThai'] === 'Đang áp dụng' && $homNay >= $km['ngayBatDau'] && $homNay <= $km['ngayKetThuc'];
    }

    public static function kiemTraXungDotThoiGian(array $danhSachMaDt, string $ngayBatDau, string $ngayKetThuc, ?int $boQuaMaKm = null, ?PDO $pdo = null): ?string
    {
        if (empty($danhSachMaDt)) return null;
        $ph = implode(',', array_fill(0, count($danhSachMaDt), '?'));
        $sql = "SELECT km.maKhuyenMai, km.ten, d.ten AS tenDienThoai
                FROM KhuyenMai km
                JOIN KhuyenMai_DienThoai kmdt ON kmdt.maKhuyenMai = km.maKhuyenMai
                JOIN DienThoai d ON d.maDienThoai = kmdt.maDienThoai
                WHERE km.trangThai = 'Đang áp dụng'
                  AND kmdt.maDienThoai IN ({$ph})
                  AND NOT (km.ngayKetThuc < ? OR km.ngayBatDau > ?)";
        $ts = array_map('intval', $danhSachMaDt);
        $ts[] = $ngayBatDau;
        $ts[] = $ngayKetThuc;
        if ($boQuaMaKm !== null) {
            $sql .= " AND km.maKhuyenMai <> ?";
            $ts[] = $boQuaMaKm;
        }
        $sql .= " LIMIT 1";
        $stmt = self::pdo($pdo)->prepare($sql);
        $stmt->execute($ts);
        $xungDot = $stmt->fetch();
        if ($xungDot) {
            return "Xung đột thời gian ưu đãi: sản phẩm '{$xungDot['tenDienThoai']}' đang thuộc khuyến mãi '{$xungDot['ten']}' (#{$xungDot['maKhuyenMai']})";
        }
        return null;
    }

    public static function kiemTraXungDotUuDai(int $maKhuyenMai, array $dieuKien = []): bool
    {
        return false;
    }

    public static function timTatCa(?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        $ds = $db->query(
            "SELECT km.*, dk.giaTriToiThieu, dk.nhomHang, dk.doiTuong
             FROM KhuyenMai km
             LEFT JOIN DieuKienApDung dk ON dk.maKhuyenMai = km.maKhuyenMai
             ORDER BY km.maKhuyenMai DESC"
        )->fetchAll();
        $spTheoKm = [];
        foreach ($db->query("SELECT maKhuyenMai, maDienThoai FROM KhuyenMai_DienThoai")->fetchAll() as $r) {
            $spTheoKm[(int)$r['maKhuyenMai']][] = (int)$r['maDienThoai'];
        }
        $ketQua = [];
        foreach ($ds as $km) {
            $muc = self::tuMang($km)->chuyenThanhMang();
            $muc['danhSachMaDienThoai'] = $spTheoKm[$muc['maKhuyenMai']] ?? [];
            $muc['dieuKienApDung'] = [
                'giaTriToiThieu' => (float)($km['giaTriToiThieu'] ?? 0),
                'nhomHang'       => $km['nhomHang'] ?? 'Tất cả',
                'doiTuong'       => $km['doiTuong'] ?? 'Tất cả khách hàng',
            ];
            $ketQua[] = $muc;
        }
        return $ketQua;
    }

    public static function timTheoMa(int $maKhuyenMai, ?PDO $pdo = null): ?array
    {
        $stmt = self::pdo($pdo)->prepare("SELECT * FROM KhuyenMai WHERE maKhuyenMai = ? LIMIT 1");
        $stmt->execute([$maKhuyenMai]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function timKhuyenMaiDangApDung(?int $maDienThoai = null, ?PDO $pdo = null): array
    {
        $db = self::pdo($pdo);
        if ($maDienThoai !== null) {
            $stmt = $db->prepare(
                "SELECT km.* FROM KhuyenMai km
                 JOIN KhuyenMai_DienThoai kmdt ON kmdt.maKhuyenMai = km.maKhuyenMai
                 WHERE kmdt.maDienThoai = ? AND km.trangThai = 'Đang áp dụng'
                   AND CURDATE() BETWEEN km.ngayBatDau AND km.ngayKetThuc
                 ORDER BY km.mucUuDai DESC"
            );
            $stmt->execute([$maDienThoai]);
            return array_map(fn($r) => self::tuMang($r)->chuyenThanhMang(), $stmt->fetchAll());
        }
        $rows = $db->query(
            "SELECT kmdt.maDienThoai, MAX(km.mucUuDai) AS mucUuDaiCaoNhat, MAX(km.ten) AS tenKhuyenMai
             FROM KhuyenMai_DienThoai kmdt
             JOIN KhuyenMai km ON km.maKhuyenMai = kmdt.maKhuyenMai
             WHERE km.trangThai = 'Đang áp dụng'
               AND CURDATE() BETWEEN km.ngayBatDau AND km.ngayKetThuc
             GROUP BY kmdt.maDienThoai"
        )->fetchAll();
        $banDo = [];
        foreach ($rows as $r) {
            $banDo[(int)$r['maDienThoai']] = [
                'mucUuDai'     => (float)$r['mucUuDaiCaoNhat'],
                'tenKhuyenMai' => $r['tenKhuyenMai'],
            ];
        }
        return $banDo;
    }

    public static function luu(string $ten, ?string $noiDung, float $mucUuDai, string $ngayBatDau, string $ngayKetThuc, string $khungGio = '00:00-23:59', string $trangThai = 'Đang áp dụng', ?PDO $pdo = null): int
    {
        $db = self::pdo($pdo);
        $db->prepare("INSERT INTO KhuyenMai (ten, noiDung, mucUuDai, ngayBatDau, ngayKetThuc, khungGio, trangThai) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([trim($ten), $noiDung, $mucUuDai, $ngayBatDau, $ngayKetThuc, $khungGio, $trangThai]);
        return (int)$db->lastInsertId();
    }

    public static function ganDanhSachSanPham(int $maKhuyenMai, array $danhSachMaDt, ?PDO $pdo = null): void
    {
        $db = self::pdo($pdo);
        $db->prepare("DELETE FROM KhuyenMai_DienThoai WHERE maKhuyenMai = ?")->execute([$maKhuyenMai]);
        $gan = $db->prepare("INSERT IGNORE INTO KhuyenMai_DienThoai (maKhuyenMai, maDienThoai) VALUES (?, ?)");
        foreach ($danhSachMaDt as $maDt) {
            if ((int)$maDt > 0) {
                $gan->execute([$maKhuyenMai, (int)$maDt]);
            }
        }
    }

    public static function capNhat(int $maKhuyenMai, string $ten, ?string $noiDung, float $mucUuDai, string $ngayBatDau, string $ngayKetThuc, string $khungGio = '00:00-23:59', ?string $trangThai = null, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        if ($trangThai !== null) {
            return $db->prepare("UPDATE KhuyenMai SET ten = ?, noiDung = ?, mucUuDai = ?, ngayBatDau = ?, ngayKetThuc = ?, khungGio = ?, trangThai = ? WHERE maKhuyenMai = ?")
                      ->execute([trim($ten), $noiDung, $mucUuDai, $ngayBatDau, $ngayKetThuc, $khungGio, $trangThai, $maKhuyenMai]);
        }
        return $db->prepare("UPDATE KhuyenMai SET ten = ?, noiDung = ?, mucUuDai = ?, ngayBatDau = ?, ngayKetThuc = ?, khungGio = ? WHERE maKhuyenMai = ?")
                  ->execute([trim($ten), $noiDung, $mucUuDai, $ngayBatDau, $ngayKetThuc, $khungGio, $maKhuyenMai]);
    }

    public static function capNhatTrangThai(int $maKhuyenMai, string $trangThai, ?PDO $pdo = null): bool
    {
        return self::pdo($pdo)->prepare("UPDATE KhuyenMai SET trangThai = ? WHERE maKhuyenMai = ?")->execute([$trangThai, $maKhuyenMai]);
    }

    public static function xoa(int $maKhuyenMai, ?PDO $pdo = null): bool
    {
        $db = self::pdo($pdo);
        $db->prepare("DELETE FROM KhuyenMai_DienThoai WHERE maKhuyenMai = ?")->execute([$maKhuyenMai]);
        return $db->prepare("DELETE FROM KhuyenMai WHERE maKhuyenMai = ?")->execute([$maKhuyenMai]);
    }

}
