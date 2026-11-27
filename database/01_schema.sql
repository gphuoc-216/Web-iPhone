-- ============================================================================
-- 01_schema.sql
-- Hệ thống quản lý bán điện thoại (Nhóm 08)
-- Chuẩn MySQL 8.0+ | 30 bảng | 36 khóa ngoại | Charset utf8mb4
-- ============================================================================

CREATE DATABASE IF NOT EXISTS quan_ly_ban_dien_thoai
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE quan_ly_ban_dien_thoai;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS YeuCauHuyDon;
DROP TABLE IF EXISTS ThanhToan;
DROP TABLE IF EXISTS ChiTietPhieuXuat;
DROP TABLE IF EXISTS PhieuXuat;
DROP TABLE IF EXISTS ChiTietDonHang;
DROP TABLE IF EXISTS DonHang;
DROP TABLE IF EXISTS ChiTietGioHang;
DROP TABLE IF EXISTS GioHang;
DROP TABLE IF EXISTS DieuKienApDung;
DROP TABLE IF EXISTS KhuyenMai_DienThoai;
DROP TABLE IF EXISTS Voucher;
DROP TABLE IF EXISTS KhuyenMai;
DROP TABLE IF EXISTS DiaChiGiaoHang;
DROP TABLE IF EXISTS QuyTacPhiVanChuyen;
DROP TABLE IF EXISTS ChiTietKiemKe;
DROP TABLE IF EXISTS BienBanKiemKe;
DROP TABLE IF EXISTS ChiTietPhieuNhap;
DROP TABLE IF EXISTS PhieuNhap;
DROP TABLE IF EXISTS CanhBaoTonKho;
DROP TABLE IF EXISTS TonKho;
DROP TABLE IF EXISTS ThongBao;
DROP TABLE IF EXISTS PhienDangNhap;
DROP TABLE IF EXISTS NhanVien;
DROP TABLE IF EXISTS KhachHang;
DROP TABLE IF EXISTS TaiKhoan;
DROP TABLE IF EXISTS ThongSoKyThuat;
DROP TABLE IF EXISTS DienThoai;
DROP TABLE IF EXISTS LichSuCauHinhLoiNhuan;
DROP TABLE IF EXISTS CauHinhLoiNhuan;
DROP TABLE IF EXISTS DanhMuc;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- CỤM 1: SẢN PHẨM VÀ CHÍNH SÁCH GIÁ
-- ============================================================================

-- 1. DanhMuc
CREATE TABLE DanhMuc (
  maDanhMuc INT AUTO_INCREMENT PRIMARY KEY,
  ten       VARCHAR(100) NOT NULL,
  moTa      TEXT,
  CONSTRAINT uq_DanhMuc_ten UNIQUE (ten)
) ENGINE=InnoDB;

-- 2. CauHinhLoiNhuan
CREATE TABLE CauHinhLoiNhuan (
  maCauHinh  INT AUTO_INCREMENT PRIMARY KEY,
  phamVi     VARCHAR(100) NOT NULL,
  tyLe       DECIMAL(5,2) NOT NULL,
  ngayApDung DATE NOT NULL,
  ghiChu     TEXT,
  CONSTRAINT chk_CauHinhLoiNhuan_tyLe CHECK (tyLe >= 0 AND tyLe <= 500)
) ENGINE=InnoDB;

-- 3. LichSuCauHinhLoiNhuan (FK #6)
CREATE TABLE LichSuCauHinhLoiNhuan (
  maLichSu    INT AUTO_INCREMENT PRIMARY KEY,
  maCauHinh   INT NOT NULL,
  phamVi      VARCHAR(100) NOT NULL,
  tyLe        DECIMAL(5,2) NOT NULL,
  ngayApDung  DATE NOT NULL,
  thoiDiemGhi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_LichSuLoiNhuan_CauHinh
    FOREIGN KEY (maCauHinh) REFERENCES CauHinhLoiNhuan(maCauHinh)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. DienThoai (FK #1)
CREATE TABLE DienThoai (
  maDienThoai    INT AUTO_INCREMENT PRIMARY KEY,
  ten            VARCHAR(255) NOT NULL,
  maDanhMuc      INT NOT NULL,
  hang           VARCHAR(100) NOT NULL,
  moTa           TEXT,
  hinhAnh        VARCHAR(255),
  giaVonBinhQuan DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  giaBan         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  trangThai      VARCHAR(50) NOT NULL DEFAULT 'Đang kinh doanh',
  CONSTRAINT fk_DienThoai_DanhMuc
    FOREIGN KEY (maDanhMuc) REFERENCES DanhMuc(maDanhMuc)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_DienThoai_gia CHECK (giaVonBinhQuan >= 0 AND giaBan >= 0),
  CONSTRAINT chk_DienThoai_trangThai CHECK (trangThai IN ('Đang kinh doanh', 'Ngừng kinh doanh')),
  INDEX idx_DienThoai_maDanhMuc (maDanhMuc),
  INDEX idx_DienThoai_hang (hang),
  INDEX idx_DienThoai_trangThai (trangThai)
) ENGINE=InnoDB;

-- 5. ThongSoKyThuat (FK #2)
CREATE TABLE ThongSoKyThuat (
  maThongSo    INT AUTO_INCREMENT PRIMARY KEY,
  maDienThoai  INT NOT NULL,
  manHinh      VARCHAR(100),
  chip         VARCHAR(100),
  ram          INT NOT NULL,
  rom          INT NOT NULL,
  camera       VARCHAR(255),
  dungLuongPin INT NOT NULL,
  heDieuHanh   VARCHAR(100),
  CONSTRAINT uq_ThongSoKyThuat_DienThoai UNIQUE (maDienThoai),
  CONSTRAINT fk_ThongSo_DienThoai
    FOREIGN KEY (maDienThoai) REFERENCES DienThoai(maDienThoai)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_ThongSo_duong CHECK (ram > 0 AND rom > 0 AND dungLuongPin > 0)
) ENGINE=InnoDB;

-- ============================================================================
-- CỤM 4 (PHẦN TÀI KHOẢN & THÔNG BÁO - TẠO TRƯỚC ĐỂ LÀM CHA CHO CỤM 2 & 3)
-- ============================================================================

-- 6. TaiKhoan
CREATE TABLE TaiKhoan (
  maTaiKhoan   INT AUTO_INCREMENT PRIMARY KEY,
  dinhDanh     VARCHAR(150) NOT NULL,
  matKhauDaBam VARCHAR(255) NOT NULL,
  vaiTro       VARCHAR(50) NOT NULL,
  trangThai    VARCHAR(30) NOT NULL DEFAULT 'Hoạt động',
  lyDoKhoa     TEXT,
  maXacThuc    VARCHAR(20),
  hanMaXacThuc DATETIME,
  CONSTRAINT uq_TaiKhoan_dinhDanh UNIQUE (dinhDanh),
  CONSTRAINT chk_TaiKhoan_vaiTro CHECK (
    vaiTro IN ('Khách hàng', 'Nhân viên Bán hàng / CSKH', 'Nhân viên Kho', 'Ban Quản lý')
  ),
  CONSTRAINT chk_TaiKhoan_trangThai CHECK (trangThai IN ('Hoạt động', 'Bị khóa'))
) ENGINE=InnoDB;

-- 7. KhachHang (FK #29)
CREATE TABLE KhachHang (
  maKhachHang INT AUTO_INCREMENT PRIMARY KEY,
  maTaiKhoan  INT NOT NULL,
  hoTen       VARCHAR(150) NOT NULL,
  soDienThoai VARCHAR(20),
  email       VARCHAR(150),
  CONSTRAINT uq_KhachHang_TaiKhoan UNIQUE (maTaiKhoan),
  CONSTRAINT fk_KhachHang_TaiKhoan
    FOREIGN KEY (maTaiKhoan) REFERENCES TaiKhoan(maTaiKhoan)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_KhachHang_sdt (soDienThoai),
  INDEX idx_KhachHang_email (email)
) ENGINE=InnoDB;

-- 8. NhanVien (FK #30)
CREATE TABLE NhanVien (
  maNhanVien  INT AUTO_INCREMENT PRIMARY KEY,
  maTaiKhoan  INT NOT NULL,
  hoTen       VARCHAR(150) NOT NULL,
  soDienThoai VARCHAR(20),
  CONSTRAINT uq_NhanVien_TaiKhoan UNIQUE (maTaiKhoan),
  CONSTRAINT fk_NhanVien_TaiKhoan
    FOREIGN KEY (maTaiKhoan) REFERENCES TaiKhoan(maTaiKhoan)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 9. PhienDangNhap (FK #27)
CREATE TABLE PhienDangNhap (
  maPhien        VARCHAR(64) PRIMARY KEY,
  maTaiKhoan     INT NOT NULL,
  thoiDiemTao    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  thoiDiemHetHan DATETIME NOT NULL,
  trangThai      VARCHAR(30) NOT NULL DEFAULT 'Còn hiệu lực',
  CONSTRAINT fk_Phien_TaiKhoan
    FOREIGN KEY (maTaiKhoan) REFERENCES TaiKhoan(maTaiKhoan)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_Phien_trangThai CHECK (trangThai IN ('Còn hiệu lực', 'Đã hủy')),
  INDEX idx_Phien_TaiKhoan (maTaiKhoan)
) ENGINE=InnoDB;

-- 10. ThongBao (FK #28)
CREATE TABLE ThongBao (
  maThongBao  INT AUTO_INCREMENT PRIMARY KEY,
  maNguoiNhan INT NOT NULL,
  noiDung     TEXT NOT NULL,
  thoiDiemTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  daDoc       BOOLEAN NOT NULL DEFAULT FALSE,
  CONSTRAINT fk_ThongBao_TaiKhoan
    FOREIGN KEY (maNguoiNhan) REFERENCES TaiKhoan(maTaiKhoan)
    ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX idx_ThongBao_NguoiNhan (maNguoiNhan, daDoc)
) ENGINE=InnoDB;

-- ============================================================================
-- CỤM 2: KHO HÀNG VÀ KIỂM KÊ (TRỪ PHIẾU XUẤT TẠO SAU ĐƠN HÀNG)
-- ============================================================================

-- 11. TonKho (FK #3)
CREATE TABLE TonKho (
  maTonKho    INT AUTO_INCREMENT PRIMARY KEY,
  maDienThoai INT NOT NULL,
  maKho       INT NOT NULL DEFAULT 1,
  tonThucTe   INT NOT NULL DEFAULT 0,
  giuCho      INT NOT NULL DEFAULT 0,
  tonKhaDung  INT NOT NULL DEFAULT 0,
  CONSTRAINT uq_TonKho_DienThoai_Kho UNIQUE (maDienThoai, maKho),
  CONSTRAINT fk_TonKho_DienThoai
    FOREIGN KEY (maDienThoai) REFERENCES DienThoai(maDienThoai)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_TonKho_soLuong CHECK (
    tonThucTe >= 0 AND giuCho >= 0 AND tonKhaDung = (tonThucTe - giuCho)
  )
) ENGINE=InnoDB;

-- 12. CanhBaoTonKho
CREATE TABLE CanhBaoTonKho (
  maNguong       INT AUTO_INCREMENT PRIMARY KEY,
  phamVi         VARCHAR(150) NOT NULL,
  nguongSom      INT NOT NULL,
  nguongToiThieu INT NOT NULL,
  nguongToiDa    INT NOT NULL,
  CONSTRAINT uq_CanhBao_phamVi UNIQUE (phamVi),
  CONSTRAINT chk_CanhBao_nguong CHECK (
    nguongToiThieu >= 0 AND nguongToiThieu < nguongSom AND nguongSom < nguongToiDa
  )
) ENGINE=InnoDB;

-- 13. PhieuNhap (FK #33)
CREATE TABLE PhieuNhap (
  maPhieuNhap INT AUTO_INCREMENT PRIMARY KEY,
  nguonNhap   VARCHAR(200) NOT NULL,
  ngayNhap    DATE NOT NULL,
  trangThai   VARCHAR(30) NOT NULL DEFAULT 'Nháp',
  maNhanVien  INT NOT NULL,
  CONSTRAINT fk_PhieuNhap_NhanVien
    FOREIGN KEY (maNhanVien) REFERENCES NhanVien(maNhanVien)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_PhieuNhap_trangThai CHECK (trangThai IN ('Nháp', 'Chờ duyệt', 'Đã duyệt')),
  INDEX idx_PhieuNhap_ngay (ngayNhap),
  INDEX idx_PhieuNhap_trangThai (trangThai)
) ENGINE=InnoDB;

-- 14. ChiTietPhieuNhap (FK #7, #8)
CREATE TABLE ChiTietPhieuNhap (
  maChiTiet   INT AUTO_INCREMENT PRIMARY KEY,
  maPhieuNhap INT NOT NULL,
  maDienThoai INT NOT NULL,
  soLuong     INT NOT NULL,
  donGiaNhap  DECIMAL(15,2) NOT NULL,
  CONSTRAINT fk_CTPN_PhieuNhap
    FOREIGN KEY (maPhieuNhap) REFERENCES PhieuNhap(maPhieuNhap)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_CTPN_DienThoai
    FOREIGN KEY (maDienThoai) REFERENCES DienThoai(maDienThoai)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_CTPN_hopLe CHECK (soLuong > 0 AND donGiaNhap > 0),
  INDEX idx_CTPN_PhieuNhap (maPhieuNhap),
  INDEX idx_CTPN_DienThoai (maDienThoai)
) ENGINE=InnoDB;

-- 15. BienBanKiemKe (FK #35)
CREATE TABLE BienBanKiemKe (
  maBienBan  INT AUTO_INCREMENT PRIMARY KEY,
  maKho      INT NOT NULL DEFAULT 1,
  thoiDiem   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ghiChu     TEXT,
  trangThai  VARCHAR(30) NOT NULL DEFAULT 'Nháp',
  maNhanVien INT NOT NULL,
  CONSTRAINT fk_BienBanKiemKe_NhanVien
    FOREIGN KEY (maNhanVien) REFERENCES NhanVien(maNhanVien)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_BienBanKiemKe_trangThai CHECK (
    trangThai IN ('Nháp', 'Hoàn tất', 'Chờ duyệt', 'Đã duyệt', 'Cần kiểm lại')
  )
) ENGINE=InnoDB;

-- 16. ChiTietKiemKe (FK #12, #13)
CREATE TABLE ChiTietKiemKe (
  maChiTiet      INT AUTO_INCREMENT PRIMARY KEY,
  maBienBan      INT NOT NULL,
  maDienThoai    INT NOT NULL,
  soLuongHeThong INT NOT NULL,
  soLuongThucTe  INT NOT NULL,
  chenhLech      INT NOT NULL,
  CONSTRAINT fk_CTKK_BienBan
    FOREIGN KEY (maBienBan) REFERENCES BienBanKiemKe(maBienBan)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_CTKK_DienThoai
    FOREIGN KEY (maDienThoai) REFERENCES DienThoai(maDienThoai)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_CTKK_hopLe CHECK (
    soLuongHeThong >= 0 AND soLuongThucTe >= 0 AND chenhLech = (soLuongThucTe - soLuongHeThong)
  )
) ENGINE=InnoDB;

-- ============================================================================
-- CỤM 4 (PHẦN KHUYẾN MÃI & VOUCHER - TẠO TRƯỚC ĐỂ LÀM CHA CHO GIỎ/ĐƠN HÀNG)
-- ============================================================================

-- 17. KhuyenMai
CREATE TABLE KhuyenMai (
  maKhuyenMai INT AUTO_INCREMENT PRIMARY KEY,
  ten         VARCHAR(150) NOT NULL,
  noiDung     TEXT,
  mucUuDai    DECIMAL(5,2) NOT NULL,
  ngayBatDau  DATE NOT NULL,
  ngayKetThuc DATE NOT NULL,
  khungGio    VARCHAR(50),
  trangThai   VARCHAR(30) NOT NULL DEFAULT 'Đang áp dụng',
  CONSTRAINT chk_KhuyenMai_uuDai CHECK (mucUuDai > 0 AND mucUuDai <= 100),
  CONSTRAINT chk_KhuyenMai_thoiGian CHECK (ngayKetThuc >= ngayBatDau)
) ENGINE=InnoDB;

-- 18. KhuyenMai_DienThoai (FK #4, #5)
CREATE TABLE KhuyenMai_DienThoai (
  maKhuyenMai INT NOT NULL,
  maDienThoai INT NOT NULL,
  PRIMARY KEY (maKhuyenMai, maDienThoai),
  CONSTRAINT fk_KMDT_KhuyenMai
    FOREIGN KEY (maKhuyenMai) REFERENCES KhuyenMai(maKhuyenMai)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_KMDT_DienThoai
    FOREIGN KEY (maDienThoai) REFERENCES DienThoai(maDienThoai)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- 19. Voucher
CREATE TABLE Voucher (
  maVoucher     VARCHAR(50) PRIMARY KEY,
  loaiGiam      VARCHAR(30) NOT NULL,
  giaTriGiam    DECIMAL(15,2) NOT NULL,
  soLuong       INT NOT NULL,
  gioiHanSuDung INT NOT NULL DEFAULT 1,
  luotDaDung    INT NOT NULL DEFAULT 0,
  ngayBatDau    DATE NOT NULL,
  ngayKetThuc   DATE NOT NULL,
  trangThai     VARCHAR(30) NOT NULL DEFAULT 'Đang hiệu lực',
  CONSTRAINT chk_Voucher_loaiGiam CHECK (loaiGiam IN ('Phần trăm', 'Số tiền')),
  CONSTRAINT chk_Voucher_hopLe CHECK (
    giaTriGiam > 0 AND soLuong > 0 AND gioiHanSuDung > 0
    AND luotDaDung >= 0 AND luotDaDung <= soLuong
    AND ngayKetThuc >= ngayBatDau
  ),
  CONSTRAINT chk_Voucher_trangThai CHECK (trangThai IN ('Đang hiệu lực', 'Hết hạn', 'Đã khóa'))
) ENGINE=InnoDB;

-- 20. DieuKienApDung (FK #31, #32 - XOR thuộc KhuyenMai hoặc Voucher)
CREATE TABLE DieuKienApDung (
  maDieuKien     INT AUTO_INCREMENT PRIMARY KEY,
  maKhuyenMai    INT NULL,
  maVoucher      VARCHAR(50) NULL,
  giaTriToiThieu DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  nhomHang       VARCHAR(150),
  doiTuong       VARCHAR(150),
  CONSTRAINT uq_DieuKien_Voucher UNIQUE (maVoucher),
  CONSTRAINT fk_DieuKien_KhuyenMai
    FOREIGN KEY (maKhuyenMai) REFERENCES KhuyenMai(maKhuyenMai)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_DieuKien_Voucher
    FOREIGN KEY (maVoucher) REFERENCES Voucher(maVoucher)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT chk_DieuKien_giaTri CHECK (giaTriToiThieu >= 0),
  CONSTRAINT chk_DieuKien_xor CHECK (
    (maKhuyenMai IS NOT NULL AND maVoucher IS NULL)
    OR (maKhuyenMai IS NULL AND maVoucher IS NOT NULL)
  )
) ENGINE=InnoDB;

-- ============================================================================
-- CỤM 3: BÁN HÀNG, ĐƠN HÀNG, THANH TOÁN & PHIẾU XUẤT KHO
-- ============================================================================

-- 21. QuyTacPhiVanChuyen
CREATE TABLE QuyTacPhiVanChuyen (
  maQuyTac      INT AUTO_INCREMENT PRIMARY KEY,
  khuVuc        VARCHAR(150) NOT NULL,
  mucPhi        DECIMAL(15,2) NOT NULL,
  mucPhiMacDinh DECIMAL(15,2) NOT NULL DEFAULT 35000.00,
  nguongMienPhi DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT uq_QuyTacPhi_khuVuc UNIQUE (khuVuc),
  CONSTRAINT chk_QuyTacPhi_duong CHECK (mucPhi >= 0 AND mucPhiMacDinh >= 0 AND nguongMienPhi >= 0)
) ENGINE=InnoDB;

-- 22. DiaChiGiaoHang (FK #18, #19)
CREATE TABLE DiaChiGiaoHang (
  maDiaChi    INT AUTO_INCREMENT PRIMARY KEY,
  maKhachHang INT NOT NULL,
  nguoiNhan   VARCHAR(150) NOT NULL,
  soDienThoai VARCHAR(20) NOT NULL,
  diaChi      TEXT NOT NULL,
  maQuyTac    INT NOT NULL,
  macDinh     BOOLEAN NOT NULL DEFAULT FALSE,
  CONSTRAINT fk_DiaChi_KhachHang
    FOREIGN KEY (maKhachHang) REFERENCES KhachHang(maKhachHang)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_DiaChi_QuyTacPhi
    FOREIGN KEY (maQuyTac) REFERENCES QuyTacPhiVanChuyen(maQuyTac)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_DiaChi_KhachHang (maKhachHang)
) ENGINE=InnoDB;

-- 23. GioHang (FK #14, #15)
CREATE TABLE GioHang (
  maGioHang   INT AUTO_INCREMENT PRIMARY KEY,
  maKhachHang INT NULL,
  maPhien     VARCHAR(128) NOT NULL,
  maVoucher   VARCHAR(50) NULL,
  tongTamTinh DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  tienGiamGia DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  tongTien    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  trangThai   VARCHAR(30) NOT NULL DEFAULT 'Đang dùng',
  CONSTRAINT fk_GioHang_KhachHang
    FOREIGN KEY (maKhachHang) REFERENCES KhachHang(maKhachHang)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_GioHang_Voucher
    FOREIGN KEY (maVoucher) REFERENCES Voucher(maVoucher)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_GioHang_tien CHECK (tongTamTinh >= 0 AND tienGiamGia >= 0 AND tongTien >= 0),
  CONSTRAINT chk_GioHang_trangThai CHECK (trangThai IN ('Đang dùng', 'Đã khóa')),
  INDEX idx_GioHang_KhachHang (maKhachHang),
  INDEX idx_GioHang_Phien (maPhien)
) ENGINE=InnoDB;

-- 24. ChiTietGioHang (FK #16, #17)
CREATE TABLE ChiTietGioHang (
  maChiTietGioHang INT AUTO_INCREMENT PRIMARY KEY,
  maGioHang        INT NOT NULL,
  maDienThoai      INT NOT NULL,
  soLuong          INT NOT NULL,
  donGia           DECIMAL(15,2) NOT NULL,
  CONSTRAINT uq_CTGH_GioHang_DienThoai UNIQUE (maGioHang, maDienThoai),
  CONSTRAINT fk_CTGH_GioHang
    FOREIGN KEY (maGioHang) REFERENCES GioHang(maGioHang)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_CTGH_DienThoai
    FOREIGN KEY (maDienThoai) REFERENCES DienThoai(maDienThoai)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_CTGH_hopLe CHECK (soLuong > 0 AND donGia >= 0)
) ENGINE=InnoDB;

-- 25. DonHang (FK #20, #21, #36)
CREATE TABLE DonHang (
  maDonHang    INT AUTO_INCREMENT PRIMARY KEY,
  maKhachHang  INT NOT NULL,
  maDiaChi     INT NOT NULL,
  maVoucher    VARCHAR(50) NULL,
  ngayDat      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  phiVanChuyen DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  tongTien     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  trangThai    VARCHAR(30) NOT NULL DEFAULT 'Chờ duyệt',
  CONSTRAINT fk_DonHang_KhachHang
    FOREIGN KEY (maKhachHang) REFERENCES KhachHang(maKhachHang)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_DonHang_DiaChi
    FOREIGN KEY (maDiaChi) REFERENCES DiaChiGiaoHang(maDiaChi)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_DonHang_Voucher
    FOREIGN KEY (maVoucher) REFERENCES Voucher(maVoucher)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_DonHang_tien CHECK (phiVanChuyen >= 0 AND tongTien >= 0),
  CONSTRAINT chk_DonHang_trangThai CHECK (
    trangThai IN (
      'Chờ duyệt', 'Đã duyệt', 'Đang đóng gói',
      'Đã giao shipper', 'Đang giao', 'Đã giao thành công', 'Đã hủy'
    )
  ),
  INDEX idx_DonHang_KhachHang (maKhachHang),
  INDEX idx_DonHang_ngayDat (ngayDat),
  INDEX idx_DonHang_trangThai (trangThai)
) ENGINE=InnoDB;

-- 26. ChiTietDonHang (FK #22, #23)
CREATE TABLE ChiTietDonHang (
  maChiTietDonHang INT AUTO_INCREMENT PRIMARY KEY,
  maDonHang        INT NOT NULL,
  maDienThoai      INT NOT NULL,
  soLuong          INT NOT NULL,
  donGia           DECIMAL(15,2) NOT NULL,
  CONSTRAINT fk_CTDH_DonHang
    FOREIGN KEY (maDonHang) REFERENCES DonHang(maDonHang)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_CTDH_DienThoai
    FOREIGN KEY (maDienThoai) REFERENCES DienThoai(maDienThoai)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_CTDH_hopLe CHECK (soLuong > 0 AND donGia >= 0),
  INDEX idx_CTDH_DonHang (maDonHang),
  INDEX idx_CTDH_DienThoai (maDienThoai)
) ENGINE=InnoDB;

-- 27. PhieuXuat (FK #11, #34)
CREATE TABLE PhieuXuat (
  maPhieuXuat INT AUTO_INCREMENT PRIMARY KEY,
  maDonHang   INT NOT NULL,
  ngayLap     DATE NOT NULL,
  ghiChu      TEXT,
  trangThai   VARCHAR(30) NOT NULL DEFAULT 'Nháp',
  maNhanVien  INT NOT NULL,
  CONSTRAINT fk_PhieuXuat_DonHang
    FOREIGN KEY (maDonHang) REFERENCES DonHang(maDonHang)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_PhieuXuat_NhanVien
    FOREIGN KEY (maNhanVien) REFERENCES NhanVien(maNhanVien)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_PhieuXuat_trangThai CHECK (trangThai IN ('Nháp', 'Đã xuất', 'Đã hủy')),
  INDEX idx_PhieuXuat_DonHang (maDonHang),
  INDEX idx_PhieuXuat_ngayLap (ngayLap)
) ENGINE=InnoDB;

-- 28. ChiTietPhieuXuat (FK #9, #10)
CREATE TABLE ChiTietPhieuXuat (
  maChiTiet   INT AUTO_INCREMENT PRIMARY KEY,
  maPhieuXuat INT NOT NULL,
  maDienThoai INT NOT NULL,
  soLuong     INT NOT NULL,
  CONSTRAINT fk_CTPX_PhieuXuat
    FOREIGN KEY (maPhieuXuat) REFERENCES PhieuXuat(maPhieuXuat)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_CTPX_DienThoai
    FOREIGN KEY (maDienThoai) REFERENCES DienThoai(maDienThoai)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_CTPX_soLuong CHECK (soLuong > 0),
  INDEX idx_CTPX_PhieuXuat (maPhieuXuat),
  INDEX idx_CTPX_DienThoai (maDienThoai)
) ENGINE=InnoDB;

-- 29. ThanhToan (FK #24, #25)
CREATE TABLE ThanhToan (
  maThanhToan INT AUTO_INCREMENT PRIMARY KEY,
  maGioHang   INT NOT NULL,
  maDonHang   INT NULL,
  phuongThuc  VARCHAR(50) NOT NULL,
  soTien      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  soTienHoan  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  trangThai   VARCHAR(30) NOT NULL DEFAULT 'Chờ thanh toán',
  CONSTRAINT uq_ThanhToan_DonHang UNIQUE (maDonHang),
  CONSTRAINT fk_ThanhToan_GioHang
    FOREIGN KEY (maGioHang) REFERENCES GioHang(maGioHang)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_ThanhToan_DonHang
    FOREIGN KEY (maDonHang) REFERENCES DonHang(maDonHang)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_ThanhToan_tien CHECK (soTien >= 0 AND soTienHoan >= 0 AND soTienHoan <= soTien),
  CONSTRAINT chk_ThanhToan_trangThai CHECK (
    trangThai IN (
      'Chờ thanh toán', 'Chưa thu tiền', 'Thành công',
      'Thất bại', 'Đã hủy', 'Cần hoàn tiền', 'Hoàn tiền lỗi', 'Đã hoàn tiền'
    )
  )
) ENGINE=InnoDB;

-- 30. YeuCauHuyDon (FK #26)
CREATE TABLE YeuCauHuyDon (
  maYeuCau  INT AUTO_INCREMENT PRIMARY KEY,
  maDonHang INT NOT NULL,
  lyDo      TEXT NOT NULL,
  ngayGui   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  trangThai VARCHAR(30) NOT NULL DEFAULT 'Chờ xử lý',
  CONSTRAINT fk_YeuCauHuy_DonHang
    FOREIGN KEY (maDonHang) REFERENCES DonHang(maDonHang)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_YeuCauHuy_trangThai CHECK (
    trangThai IN ('Chờ xử lý', 'Đã xác nhận', 'Đã từ chối')
  ),
  INDEX idx_YeuCauHuy_DonHang (maDonHang)
) ENGINE=InnoDB;
