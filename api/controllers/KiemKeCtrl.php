<?php
// Controller BCE: KiemKeCtrl (UC18 - UC21)

class KiemKeCtrl
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function guiThongBaoTheoVaiTro(string $vaiTro, string $noiDung): void
    {
        $stmt = $this->pdo->prepare("SELECT maTaiKhoan FROM TaiKhoan WHERE vaiTro = ? AND trangThai = 'Hoạt động'");
        $stmt->execute([$vaiTro]);
        $themTb = $this->pdo->prepare("INSERT INTO ThongBao (maNguoiNhan, noiDung, thoiDiemTao, daDoc) VALUES (?, ?, CURRENT_TIMESTAMP, FALSE)");
        foreach ($stmt->fetchAll() as $tk) {
            $themTb->execute([$tk['maTaiKhoan'], $noiDung]);
        }
    }

    public function layDanhSachBienBan(): array
    {
        $ds = $this->pdo->query(
            "SELECT bb.*, nv.hoTen AS tenNhanVien
             FROM BienBanKiemKe bb
             LEFT JOIN NhanVien nv ON nv.maNhanVien = bb.maNhanVien
             ORDER BY bb.maBienBan DESC"
        )->fetchAll();

        $ctStmt = $this->pdo->query(
            "SELECT ct.*, d.ten AS tenDienThoai
             FROM ChiTietKiemKe ct
             LEFT JOIN DienThoai d ON d.maDienThoai = ct.maDienThoai"
        );
        $chiTietTheoBb = [];
        foreach ($ctStmt->fetchAll() as $ct) {
            $ma = (int)$ct['maBienBan'];
            $muc = ChiTietKiemKe::tuMang($ct)->chuyenThanhMang();
            $muc['tenDienThoai'] = $ct['tenDienThoai'] ?? '';
            $chiTietTheoBb[$ma][] = $muc;
        }

        $ketQua = [];
        foreach ($ds as $bb) {
            $muc = BienBanKiemKe::tuMang($bb)->chuyenThanhMang();
            $muc['tenNhanVien'] = $bb['tenNhanVien'] ?? '';
            $muc['chiTiet'] = $chiTietTheoBb[$muc['maBienBan']] ?? [];
            $ketQua[] = $muc;
        }

        return ['thanhCong' => true, 'thongBao' => 'Lấy danh sách biên bản kiểm kê thành công', 'duLieu' => $ketQua];
    }

    public function taoBienBanKiemKe(array $duLieu, int $maNhanVien): array
    {
        $maKho = (int)($duLieu['maKho'] ?? 1);
        $ghiChu = trim((string)($duLieu['ghiChu'] ?? ''));
        $chiTiets = $duLieu['chiTiet'] ?? [];

        if (!is_array($chiTiets) || empty($chiTiets)) {
            return ['thanhCong' => false, 'thongBao' => 'Biên bản kiểm kê cần có ít nhất 1 sản phẩm', 'duLieu' => null, 'maHttp' => 400];
        }

        try {
            $layTon = $this->pdo->prepare("SELECT tonThucTe FROM TonKho WHERE maDienThoai = ? AND maKho = ? LIMIT 1");
            $coChenhLech = false;
            $danhSachDaTinh = [];

            foreach ($chiTiets as $dong) {
                $maDt = (int)($dong['maDienThoai'] ?? 0);
                $soLuongThucTe = (int)($dong['soLuongThucTe'] ?? -1);
                if ($maDt <= 0 || $soLuongThucTe < 0) {
                    return ['thanhCong' => false, 'thongBao' => 'Số lượng kiểm kê thực tế không hợp lệ', 'duLieu' => null, 'maHttp' => 400];
                }
                $layTon->execute([$maDt, $maKho]);
                $soLuongHeThong = (int)($layTon->fetchColumn() ?: 0);
                $chenhLech = $soLuongThucTe - $soLuongHeThong;
                if ($chenhLech !== 0) {
                    $coChenhLech = true;
                }
                $danhSachDaTinh[] = [
                    'maDienThoai'    => $maDt,
                    'soLuongHeThong' => $soLuongHeThong,
                    'soLuongThucTe'  => $soLuongThucTe,
                    'chenhLech'      => $chenhLech,
                ];
            }

            if ($coChenhLech && $ghiChu === '') {
                return ['thanhCong' => false, 'thongBao' => 'Phát hiện chênh lệch tồn kho, bắt buộc phải nhập ghi chú giải trình', 'duLieu' => null, 'maHttp' => 400];
            }

            $trangThaiBanDau = $coChenhLech ? 'Chờ duyệt' : 'Hoàn tất';

            $this->pdo->beginTransaction();

            $this->pdo->prepare(
                "INSERT INTO BienBanKiemKe (maKho, thoiDiem, ghiChu, trangThai, maNhanVien) VALUES (?, CURRENT_TIMESTAMP, ?, ?, ?)"
            )->execute([$maKho, $ghiChu !== '' ? $ghiChu : null, $trangThaiBanDau, $maNhanVien]);
            $maBienBan = (int)$this->pdo->lastInsertId();

            $themCt = $this->pdo->prepare(
                "INSERT INTO ChiTietKiemKe (maBienBan, maDienThoai, soLuongHeThong, soLuongThucTe, chenhLech) VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($danhSachDaTinh as $d) {
                $themCt->execute([$maBienBan, $d['maDienThoai'], $d['soLuongHeThong'], $d['soLuongThucTe'], $d['chenhLech']]);
            }

            if ($coChenhLech) {
                $this->guiThongBaoTheoVaiTro('Ban Quản lý', "Có biên bản kiểm kê #{$maBienBan} phát sinh chênh lệch đang chờ duyệt.");
            }

            $this->pdo->commit();
            return [
                'thanhCong' => true,
                'thongBao'  => $coChenhLech
                    ? 'Lập biên bản kiểm kê có chênh lệch, đã chuyển Chờ duyệt và thông báo Ban Quản lý'
                    : 'Kiểm kê khớp tồn hệ thống, đã lưu biên bản ở trạng thái Hoàn tất',
                'duLieu'    => ['maBienBan' => $maBienBan, 'trangThai' => $trangThaiBanDau, 'coChenhLech' => $coChenhLech],
                'maHttp'    => 201,
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi lập biên bản kiểm kê: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function duyetBienBanKiemKe(int $maBienBan, bool $pheDuyet = true, ?string $lyDo = null): array
    {
        try {
            $this->pdo->beginTransaction();

            $bbStmt = $this->pdo->prepare("SELECT * FROM BienBanKiemKe WHERE maBienBan = ? FOR UPDATE");
            $bbStmt->execute([$maBienBan]);
            $bb = $bbStmt->fetch();

            if (!$bb || $bb['trangThai'] !== 'Chờ duyệt') {
                $this->pdo->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Chỉ có thể duyệt biên bản kiểm kê đang ở trạng thái Chờ duyệt', 'duLieu' => null, 'maHttp' => 400];
            }

            if (!$pheDuyet) {
                $this->pdo->prepare("UPDATE BienBanKiemKe SET trangThai = 'Cần kiểm lại' WHERE maBienBan = ?")->execute([$maBienBan]);
                $nvTk = $this->pdo->prepare("SELECT maTaiKhoan FROM NhanVien WHERE maNhanVien = ? LIMIT 1");
                $nvTk->execute([$bb['maNhanVien']]);
                if ($maTk = $nvTk->fetchColumn()) {
                    $nd = "Biên bản kiểm kê #{$maBienBan} được yêu cầu kiểm lại." . ($lyDo ? " Lý do: {$lyDo}" : '');
                    $this->pdo->prepare("INSERT INTO ThongBao (maNguoiNhan, noiDung, thoiDiemTao, daDoc) VALUES (?, ?, CURRENT_TIMESTAMP, FALSE)")
                        ->execute([$maTk, $nd]);
                }
                $this->pdo->commit();
                return ['thanhCong' => true, 'thongBao' => 'Đã chuyển biên bản sang trạng thái Cần kiểm lại', 'duLieu' => ['maBienBan' => $maBienBan, 'trangThai' => 'Cần kiểm lại']];
            }

            $ctStmt = $this->pdo->prepare("SELECT * FROM ChiTietKiemKe WHERE maBienBan = ?");
            $ctStmt->execute([$maBienBan]);
            $canhBaoDonAnhHuong = [];

            foreach ($ctStmt->fetchAll() as $ct) {
                $maDt = (int)$ct['maDienThoai'];
                $thucTeMoi = (int)$ct['soLuongThucTe'];

                $tonStmt = $this->pdo->prepare("SELECT * FROM TonKho WHERE maDienThoai = ? AND maKho = ? FOR UPDATE");
                $tonStmt->execute([$maDt, $bb['maKho']]);
                $ton = $tonStmt->fetch();

                if ($ton) {
                    $giuCho = (int)$ton['giuCho'];
                    $khaDungMoi = $thucTeMoi - $giuCho;
                    $this->pdo->prepare("UPDATE TonKho SET tonThucTe = ?, tonKhaDung = ? WHERE maTonKho = ?")
                        ->execute([$thucTeMoi, $khaDungMoi, $ton['maTonKho']]);

                    if ($thucTeMoi < $giuCho) {
                        $donStmt = $this->pdo->prepare(
                            "SELECT DISTINCT dh.maDonHang, dh.trangThai
                             FROM DonHang dh
                             JOIN ChiTietDonHang ctdh ON ctdh.maDonHang = dh.maDonHang
                             WHERE ctdh.maDienThoai = ? AND dh.trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đang đóng gói')"
                        );
                        $donStmt->execute([$maDt]);
                        $donBiAnhHuong = $donStmt->fetchAll();
                        $canhBaoDonAnhHuong[] = [
                            'maDienThoai'   => $maDt,
                            'tonThucTeMoi'  => $thucTeMoi,
                            'giuChoHienTai' => $giuCho,
                            'donAnhHuong'   => $donBiAnhHuong,
                        ];
                        $this->guiThongBaoTheoVaiTro(
                            'Nhân viên Bán hàng / CSKH',
                            "Cảnh báo sau kiểm kê #{$maBienBan}: Sản phẩm mã #{$maDt} có tồn thực tế ({$thucTeMoi}) nhỏ hơn số lượng giữ chỗ ({$giuCho})."
                        );
                    }
                } else {
                    $this->pdo->prepare("INSERT INTO TonKho (maDienThoai, maKho, tonThucTe, giuCho, tonKhaDung) VALUES (?, ?, ?, 0, ?)")
                        ->execute([$maDt, $bb['maKho'], $thucTeMoi, $thucTeMoi]);
                }
            }

            $this->pdo->prepare("UPDATE BienBanKiemKe SET trangThai = 'Đã duyệt' WHERE maBienBan = ?")->execute([$maBienBan]);
            $this->pdo->commit();

            $thongBao = 'Duyệt biên bản kiểm kê và cập nhật tồn kho thành công';
            if (!empty($canhBaoDonAnhHuong)) {
                $thongBao .= '. Cảnh báo: phát hiện sản phẩm có tồn thực tế nhỏ hơn số lượng giữ chỗ của đơn hàng!';
            }

            return [
                'thanhCong' => true,
                'thongBao'  => $thongBao,
                'duLieu'    => [
                    'maBienBan'          => $maBienBan,
                    'trangThai'          => 'Đã duyệt',
                    'canhBaoDonAnhHuong' => $canhBaoDonAnhHuong,
                ],
            ];
        } catch (Throwable $ngoaiLe) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['thanhCong' => false, 'thongBao' => 'Lỗi duyệt kiểm kê: ' . $ngoaiLe->getMessage(), 'duLieu' => null, 'maHttp' => 500];
        }
    }

    public function layCanhBaoTonKho(): array
    {
        $nguong = $this->pdo->query("SELECT * FROM CanhBaoTonKho ORDER BY maNguong ASC LIMIT 1")->fetch();
        $nguongSom = (int)($nguong['nguongSom'] ?? 15);
        $nguongToiThieu = (int)($nguong['nguongToiThieu'] ?? 5);
        $nguongToiDa = (int)($nguong['nguongToiDa'] ?? 350);

        $stmt = $this->pdo->prepare(
            "SELECT tk.*, d.ten AS tenDienThoai, d.hang
             FROM TonKho tk
             JOIN DienThoai d ON d.maDienThoai = tk.maDienThoai
             WHERE tk.tonKhaDung <= ? OR tk.tonThucTe > ?
             ORDER BY tk.tonKhaDung ASC"
        );
        $stmt->execute([$nguongSom, $nguongToiDa]);

        $danhSachCanhBao = [];
        foreach ($stmt->fetchAll() as $dong) {
            $tonKhaDung = (int)$dong['tonKhaDung'];
            $tonThucTe = (int)$dong['tonThucTe'];

            $loaiCanhBao = 'Sắp hết';
            if ($tonKhaDung <= $nguongToiThieu) {
                $loaiCanhBao = 'Dưới tối thiểu';
            } elseif ($tonThucTe > $nguongToiDa) {
                $loaiCanhBao = 'Vượt tối đa';
            }

            $danhSachCanhBao[] = [
                'maDienThoai'  => (int)$dong['maDienThoai'],
                'tenDienThoai' => $dong['tenDienThoai'],
                'hang'         => $dong['hang'],
                'tonThucTe'    => $tonThucTe,
                'giuCho'       => (int)$dong['giuCho'],
                'tonKhaDung'   => $tonKhaDung,
                'mucDoCanhBao' => $loaiCanhBao,
            ];
        }

        return [
            'thanhCong' => true,
            'thongBao'  => 'Lấy danh sách cảnh báo tồn kho thành công',
            'duLieu'    => [
                'cauHinhNguong'   => $nguong ? CanhBaoTonKho::tuMang($nguong)->chuyenThanhMang() : null,
                'danhSachCanhBao' => $danhSachCanhBao,
            ],
        ];
    }

    public function capNhatNguongCanhBao(array $duLieu): array
    {
        $phamVi = trim((string)($duLieu['phamVi'] ?? 'Toàn bộ sản phẩm'));
        $nguongToiThieu = (int)($duLieu['nguongToiThieu'] ?? 5);
        $nguongSom = (int)($duLieu['nguongSom'] ?? 15);
        $nguongToiDa = (int)($duLieu['nguongToiDa'] ?? 350);

        if ($nguongToiThieu < 0 || !($nguongToiThieu < $nguongSom && $nguongSom < $nguongToiDa)) {
            return ['thanhCong' => false, 'thongBao' => 'Ngưỡng cảnh báo phải thỏa điều kiện: 0 <= ngưỡng tối thiểu < ngưỡng sớm < ngưỡng tối đa', 'duLieu' => null, 'maHttp' => 400];
        }

        $kiemTra = $this->pdo->prepare("SELECT maNguong FROM CanhBaoTonKho WHERE phamVi = ? LIMIT 1");
        $kiemTra->execute([$phamVi]);
        $maNguong = $kiemTra->fetchColumn();

        if ($maNguong) {
            $this->pdo->prepare("UPDATE CanhBaoTonKho SET nguongSom = ?, nguongToiThieu = ?, nguongToiDa = ? WHERE maNguong = ?")
                ->execute([$nguongSom, $nguongToiThieu, $nguongToiDa, $maNguong]);
        } else {
            $this->pdo->prepare("INSERT INTO CanhBaoTonKho (phamVi, nguongSom, nguongToiThieu, nguongToiDa) VALUES (?, ?, ?, ?)")
                ->execute([$phamVi, $nguongSom, $nguongToiThieu, $nguongToiDa]);
            $maNguong = (int)$this->pdo->lastInsertId();
        }

        return ['thanhCong' => true, 'thongBao' => 'Cập nhật ngưỡng cảnh báo tồn kho thành công', 'duLieu' => ['maNguong' => (int)$maNguong]];
    }
}
