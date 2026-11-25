-- ============================================================================
-- 02_seed.sql
-- Dữ liệu mẫu chuẩn hóa từ phone_store_backup.sql + phone_store_fixed_patch.sql
-- Ánh xạ sang 30 bảng RDM mới (Nhóm 08)
-- ============================================================================

USE quan_ly_ban_dien_thoai;

SET SQL_SAFE_UPDATES = 0;

START TRANSACTION;

-- ============================================================================
-- 1. DANH MỤC, CHÍNH SÁCH GIÁ & NGƯỠNG TỒN KHO
-- ============================================================================

INSERT INTO DanhMuc (maDanhMuc, ten, moTa) VALUES
  (1, 'Apple',   'Các dòng điện thoại iPhone chính hãng Apple'),
  (2, 'Xiaomi',  'Các dòng điện thoại Xiaomi và Redmi chính hãng'),
  (3, 'Samsung', 'Các dòng điện thoại Samsung Galaxy chính hãng'),
  (4, 'OPPO',    'Các dòng điện thoại OPPO chính hãng');

INSERT INTO CauHinhLoiNhuan (maCauHinh, phamVi, tyLe, ngayApDung, ghiChu) VALUES
  (1, 'Toàn bộ sản phẩm', 25.00, '2026-10-01', 'Tỷ lệ lợi nhuận định mức mặc định 25% trên giá vốn bình quân');

INSERT INTO LichSuCauHinhLoiNhuan (maCauHinh, phamVi, tyLe, ngayApDung, thoiDiemGhi) VALUES
  (1, 'Toàn bộ sản phẩm', 25.00, '2026-10-01', '2026-10-01 08:00:00');

INSERT INTO CanhBaoTonKho (phamVi, nguongSom, nguongToiThieu, nguongToiDa) VALUES
  ('Toàn bộ sản phẩm', 15, 5, 350);

-- ============================================================================
-- 2. TÀI KHOẢN, NHÂN VIÊN, KHÁCH HÀNG & QUY TẮC PHÍ VẬN CHUYỂN
-- Mật khẩu mẫu (BCrypt) cho các tài khoản test bên dưới: 123456
-- ============================================================================

INSERT INTO TaiKhoan (maTaiKhoan, dinhDanh, matKhauDaBam, vaiTro, trangThai, lyDoKhoa) VALUES
  (1, 'admin@phonestore.vn',    '$2y$10$NCnOOiGZKmj84mg0jiwtKu6rTJDrL7wlilaWQ4jgqY4aG1/s3R8Oq', 'Ban Quản lý',               'Hoạt động', NULL),
  (2, 'banhang@phonestore.vn',  '$2y$10$NCnOOiGZKmj84mg0jiwtKu6rTJDrL7wlilaWQ4jgqY4aG1/s3R8Oq', 'Nhân viên Bán hàng / CSKH', 'Hoạt động', NULL),
  (3, 'kho@phonestore.vn',      '$2y$10$NCnOOiGZKmj84mg0jiwtKu6rTJDrL7wlilaWQ4jgqY4aG1/s3R8Oq', 'Nhân viên Kho',             'Hoạt động', NULL),
  (4, 'nguyenvana@gmail.com',   '$2y$10$NCnOOiGZKmj84mg0jiwtKu6rTJDrL7wlilaWQ4jgqY4aG1/s3R8Oq', 'Khách hàng',                'Hoạt động', NULL),
  (5, 'tranthib@gmail.com',     '$2y$10$NCnOOiGZKmj84mg0jiwtKu6rTJDrL7wlilaWQ4jgqY4aG1/s3R8Oq', 'Khách hàng',                'Bị khóa',   'Vi phạm quy định đặt hàng ảo nhiều lần'),
  (6, '0333444555',             '$2y$10$NCnOOiGZKmj84mg0jiwtKu6rTJDrL7wlilaWQ4jgqY4aG1/s3R8Oq', 'Khách hàng',                'Hoạt động', NULL);

INSERT INTO NhanVien (maNhanVien, maTaiKhoan, hoTen, soDienThoai) VALUES
  (1, 1, 'Nguyễn Quản Lý', '0901000001'),
  (2, 2, 'Trần Bán Hàng',  '0901000002'),
  (3, 3, 'Phạm Thủ Kho',   '0901000003');

INSERT INTO KhachHang (maKhachHang, maTaiKhoan, hoTen, soDienThoai, email) VALUES
  (1, 4, 'Nguyễn Văn A', '0909123456', 'nguyenvana@gmail.com'),
  (2, 5, 'Trần Thị B',   '0988765432', 'tranthib@gmail.com'),
  (3, 6, 'Lê Văn C',     '0333444555', NULL);

INSERT INTO QuyTacPhiVanChuyen (maQuyTac, khuVuc, mucPhi, mucPhiMacDinh, nguongMienPhi) VALUES
  (1, 'Nội thành TP.HCM',   25000.00, 35000.00, 10000000.00),
  (2, 'Ngoại thành TP.HCM', 35000.00, 35000.00, 15000000.00),
  (3, 'Tỉnh thành khác',    50000.00, 35000.00, 20000000.00);

INSERT INTO DiaChiGiaoHang (maDiaChi, maKhachHang, nguoiNhan, soDienThoai, diaChi, maQuyTac, macDinh) VALUES
  (1, 1, 'Nguyễn Văn A', '0909123456', '123 Đường ABC, Quận 1, TP.HCM', 1, TRUE),
  (2, 2, 'Trần Thị B',   '0988765432', '456 Đường DEF, Quận 3, TP.HCM', 1, TRUE),
  (3, 3, 'Lê Văn C',     '0333444555', '789 Đường GHI, TP. Thủ Đức, TP.HCM', 2, TRUE);

-- ============================================================================
-- 3. 38 SẢN PHẨM ĐIỆN THOẠI & THÔNG SỐ KỸ THUẬT
-- (Giữ nguyên đường dẫn img/... và áp dụng bản vá hãng/màn hình/ROM)
-- Giá vốn bình quân = giá bán / 1.25 (khớp tỷ lệ lợi nhuận 25%)
-- ============================================================================

INSERT INTO DienThoai (maDienThoai, ten, maDanhMuc, hang, moTa, hinhAnh, giaVonBinhQuan, giaBan, trangThai) VALUES
  (1,  'iPhone 17 Pro Max 256GB | Chính hãng',   1, 'Apple',   'iPhone 17 Pro Max 256GB Dynamic Island, tần số quét 120Hz', 'storage/shop-assets/img/iphone17.webp',                         30392000, 37990000, 'Đang kinh doanh'),
  (2,  'iPhone 17 Pro 512GB | Chính hãng',       1, 'Apple',   'iPhone 17 Pro 512GB Dynamic Island, tần số quét 120Hz',     'storage/shop-assets/img/iphone-17-pro-max-1_1.png',             33192000, 41490000, 'Đang kinh doanh'),
  (3,  'iPhone 17 256GB | Chính hãng',           1, 'Apple',   'iPhone 17 256GB Dynamic Island, tần số quét 120Hz',         'storage/shop-assets/img/iphone_17_256gb-3_3.jpg',               19992000, 24990000, 'Đang kinh doanh'),
  (4,  'iPhone Air 256GB | Chính hãng',          1, 'Apple',   'iPhone Air 256GB siêu mỏng, Dynamic Island 120Hz',          'storage/shop-assets/img/iphone_air-3_2.jpg',                    22392000, 27990000, 'Đang kinh doanh'),
  (5,  'iPhone 16 Pro Max 256GB | Chính hãng',   1, 'Apple',   'iPhone 16 Pro Max 256GB Dynamic Island 120Hz',              'storage/shop-assets/img/iphone-16-pro-max.png',                 24720000, 30900000, 'Đang kinh doanh'),
  (6,  'iPhone 16 Pro 128GB | Chính hãng',       1, 'Apple',   'iPhone 16 Pro 128GB Dynamic Island 120Hz',                  'storage/shop-assets/img/iphone-16-den-128.webp',                19912000, 24890000, 'Đang kinh doanh'),
  (7,  'iPhone 16 Plus 128GB | Chính hãng',      1, 'Apple',   'iPhone 16 Plus 128GB Dynamic Island',                       'storage/shop-assets/img/iphone-16-1.png',                       17512000, 21890000, 'Đang kinh doanh'),
  (8,  'iPhone 16 256GB | Chính hãng',           1, 'Apple',   'iPhone 16 256GB Dynamic Island',                            'storage/shop-assets/img/iphone-16-plus-hong_1.webp',            17512000, 21890000, 'Đang kinh doanh'),
  (9,  'iPhone 16e 256GB | Chính hãng',          1, 'Apple',   'iPhone 16e 256GB gọn nhẹ, pin bền bỉ',                      'storage/shop-assets/img/iphone-16e-256gb.webp',                 14392000, 17990000, 'Đang kinh doanh'),
  (10, 'iPhone 15 Pro Max 256GB | Chính hãng',   1, 'Apple',   'iPhone 15 Pro Max 256GB khung Titan, Dynamic Island 120Hz', 'storage/shop-assets/img/iphone15-pro-max-titan-xanh.webp',      30392000, 37990000, 'Đang kinh doanh'),
  (11, 'iPhone 15 Pro 256GB | Chính hãng',       1, 'Apple',   'iPhone 15 Pro 256GB khung Titan, Dynamic Island 120Hz',     'storage/shop-assets/img/iphone-15-pro-256gb.webp',              21992000, 27490000, 'Đang kinh doanh'),
  (12, 'iPhone 15 Plus 256GB | Chính hãng',      1, 'Apple',   'iPhone 15 Plus 256GB màn hình lớn 6.7 inch',                'storage/shop-assets/img/iphone-15-128gb-vang.webp',             17592000, 21990000, 'Đang kinh doanh'),
  (13, 'iPhone 15 256GB | Chính hãng',           1, 'Apple',   'iPhone 15 256GB cổng USB-C, Dynamic Island',                'storage/shop-assets/img/iphone-15-128gb-xanh-la.webp',          14712000, 18390000, 'Đang kinh doanh'),
  (14, 'iPhone 14 Pro Max 256GB | Chính hãng',   1, 'Apple',   'iPhone 14 Pro Max 256GB Dynamic Island 120Hz',              'storage/shop-assets/img/iphone-14-pro_2__5.png',                20472000, 25590000, 'Đang kinh doanh'),
  (15, 'iPhone 14 Pro 256GB | Chính hãng',       1, 'Apple',   'iPhone 14 Pro 256GB Dynamic Island 120Hz',                  'storage/shop-assets/img/iphone14-pro-256.png',                  19992000, 24990000, 'Đang kinh doanh'),
  (16, 'iPhone 14 Plus 256GB | Chính hãng',      1, 'Apple',   'iPhone 14 Plus 256GB màn hình lớn 6.7 inch',                'storage/shop-assets/img/ip14_red.webp',                         14392000, 17990000, 'Đang kinh doanh'),
  (17, 'iPhone 14 256GB | Chính hãng',           1, 'Apple',   'iPhone 14 256GB chính hãng VN/A',                           'storage/shop-assets/img/ip14_gray.webp',                        12552000, 15690000, 'Đang kinh doanh'),
  (18, 'iPhone 13 Pro Max 256GB | Chính hãng',   1, 'Apple',   'iPhone 13 Pro Max 256GB màn hình ProMotion 120Hz',          'storage/shop-assets/img/600_iphone-13-pro-256gb-xanh-la_4.webp',19192000, 23990000, 'Đang kinh doanh'),
  (19, 'iPhone 13 Pro 256GB | Chính hãng',       1, 'Apple',   'iPhone 13 Pro 256GB cụm 3 camera chuyên nghiệp',            'storage/shop-assets/img/vanggg_4.webp',                         19592000, 24490000, 'Đang kinh doanh'),
  (20, 'iPhone 13 256GB | Chính hãng',           1, 'Apple',   'iPhone 13 256GB màn hình Super Retina XDR 6.1 inch',        'storage/shop-assets/img/ip13_blue.webp',                        15992000, 19990000, 'Đang kinh doanh'),
  (21, 'iPhone 13 mini 256GB | Chính hãng',      1, 'Apple',   'iPhone 13 mini 256GB thiết kế nhỏ gọn 5.4 inch',            'storage/shop-assets/img/ip13_blue.webp',                        15992000, 19990000, 'Đang kinh doanh'),
  (22, 'iPhone 11 128GB | Chính hãng',           1, 'Apple',   'iPhone 11 128GB màn hình Liquid Retina 6.1 inch',           'storage/shop-assets/img/2_242.jpg',                             15992000, 19990000, 'Đang kinh doanh'),
  (23, 'iPhone 11 64GB | Chính hãng',            1, 'Apple',   'iPhone 11 64GB màn hình Liquid Retina 6.1 inch',            'storage/shop-assets/img/6_130.webp',                            15992000, 19990000, 'Đang kinh doanh'),
  (24, 'iPhone 12 256GB | Chính hãng',           1, 'Apple',   'iPhone 12 256GB màn hình OLED 6.1 inch, hỗ trợ 5G',         'storage/shop-assets/img/ip12_light.jpg',                        15992000, 19990000, 'Đang kinh doanh'),
  (25, 'iPhone 12 512GB | Chính hãng',           1, 'Apple',   'iPhone 12 512GB dung lượng lớn, hỗ trợ 5G',                 'storage/shop-assets/img/iphone12_green.webp',                   15992000, 19990000, 'Đang kinh doanh'),
  (26, 'iPhone 12 Pro Max 512GB | Chính hãng',   1, 'Apple',   'iPhone 12 Pro Max 512GB màn hình lớn 6.7 inch',             'storage/shop-assets/img/ip12_blue.webp',                        15992000, 19990000, 'Đang kinh doanh'),
  (27, 'iPhone 17 Pro Max 512GB | Chính hãng',   1, 'Apple',   'iPhone 17 Pro Max 512GB hiệu năng đỉnh cao',                'storage/shop-assets/img/iphone17.webp',                         36720000, 45900000, 'Đang kinh doanh'),
  (28, 'iPhone 17 Pro Max 1TB | Chính hãng',     1, 'Apple',   'iPhone 17 Pro Max 1TB lưu trữ chuyên nghiệp',               'storage/shop-assets/img/iphone17.webp',                         40000000, 50000000, 'Đang kinh doanh'),
  (29, 'iPhone 11 128GB (Bản tiêu chuẩn)',       1, 'Apple',   'iPhone 11 128GB camera góc rộng & siêu rộng',               'storage/shop-assets/img/5_158.jpg',                             14400000, 18000000, 'Đang kinh doanh'),
  (30, 'iPhone 11 256GB',                        1, 'Apple',   'iPhone 11 256GB camera góc rộng & siêu rộng',               'storage/shop-assets/img/iphone-11.webp',                        16800000, 21000000, 'Đang kinh doanh'),
  (31, 'Xiaomi 14 Ultra 512GB',                  2, 'Xiaomi',  'Xiaomi 14 Ultra 512GB ống kính Leica chuyên nghiệp',        'storage/shop-assets/img/xiaomi-14-ultra.webp',                  23192000, 28990000, 'Đang kinh doanh'),
  (32, 'Xiaomi 14 Pro 256GB',                    2, 'Xiaomi',  'Xiaomi 14 Pro 256GB màn hình AMOLED 120Hz',                 'storage/shop-assets/img/xiaomi-14-pro.webp',                    19192000, 23990000, 'Đang kinh doanh'),
  (33, 'Xiaomi 13T Pro 512GB',                   2, 'Xiaomi',  'Xiaomi 13T Pro 512GB màn hình 144Hz, sạc nhanh 120W',       'storage/shop-assets/img/xiaomi-13t-pro.webp',                   15192000, 18990000, 'Đang kinh doanh'),
  (34, 'Xiaomi 13 256GB',                        2, 'Xiaomi',  'Xiaomi 13 256GB nhỏ gọn, camera Leica',                     'storage/shop-assets/img/xiaomi-13.webp',                        13592000, 16990000, 'Đang kinh doanh'),
  (35, 'Xiaomi Redmi Note 13 Pro+ 512GB',        2, 'Xiaomi',  'Redmi Note 13 Pro+ 512GB màn hình cong AMOLED 120Hz',       'storage/shop-assets/img/redmi-note-13-pro-plus.webp',            8792000, 10990000, 'Đang kinh doanh'),
  (36, 'Xiaomi Redmi Note 12 256GB',             2, 'Xiaomi',  'Redmi Note 12 256GB giá phổ thông, màn hình AMOLED 120Hz',  'storage/shop-assets/img/redmi-note-12.webp',                     5592000,  6990000, 'Đang kinh doanh'),
  (37, 'Samsung Galaxy S23 Ultra 256GB',         3, 'Samsung', 'Galaxy S23 Ultra 256GB bút S-Pen, camera zoom 100x',        'storage/shop-assets/img/galaxy-s23-ultra.webp',                 23192000, 28990000, 'Đang kinh doanh'),
  (38, 'Samsung Galaxy A54 128GB',               3, 'Samsung', 'Galaxy A54 128GB Super AMOLED 120Hz, kháng nước IP67',      'storage/shop-assets/img/galaxy-a54.webp',                        7592000,  9490000, 'Đang kinh doanh');

INSERT INTO ThongSoKyThuat (maDienThoai, manHinh, chip, ram, rom, camera, dungLuongPin, heDieuHanh) VALUES
  (1,  '6.9 inches OLED 120Hz',       'Apple A19 Pro',           12,  256, 'Chụp Macro, Quay Video 4K',             4800, 'iOS 19'),
  (2,  '6.3 inches OLED 120Hz',       'Apple A19 Pro',           12,  512, 'Chụp Macro, Quay Video 4K',             4200, 'iOS 19'),
  (3,  '6.3 inches OLED 120Hz',       'Apple A19',                8,  256, 'Chụp Macro, Quay Video 4K',             3600, 'iOS 19'),
  (4,  '6.5 inches OLED 120Hz',       'Apple A19',                8,  256, 'Chụp Macro',                            3500, 'iOS 19'),
  (5,  '6.9 inches OLED 120Hz',       'Apple A18 Pro',           12,  256, 'Chụp Macro, Quay Video 4K',             4685, 'iOS 18'),
  (6,  '6.3 inches OLED 120Hz',       'Apple A18 Pro',           12,  128, 'Chụp Macro, Quay Video 4K',             3582, 'iOS 18'),
  (7,  '6.7 inches OLED 120Hz',       'Apple A18',                8,  128, 'Chụp Macro',                            4674, 'iOS 18'),
  (8,  '6.1 inches OLED 120Hz',       'Apple A18',                8,  256, 'Quay Video 4K',                         3561, 'iOS 18'),
  (9,  '6.1 inches OLED 120Hz',       'Apple A18',                6,  256, 'Chụp Macro, Quay Video 4K',             3961, 'iOS 18'),
  (10, '6.7 inches OLED 120Hz',       'Apple A17 Pro',           12,  256, 'Chụp Macro, Quay Video 4K',             4422, 'iOS 17'),
  (11, '6.1 inches OLED 120Hz',       'Apple A17 Pro',           12,  256, 'Chụp Macro, Quay Video 4K',             3274, 'iOS 17'),
  (12, '6.7 inches OLED 120Hz',       'Apple A16 Bionic',         8,  256, 'Chụp Macro, Quay Video 4K',             4383, 'iOS 17'),
  (13, '6.1 inches OLED 120Hz',       'Apple A16 Bionic',         6,  256, 'Chụp Macro, Quay Video 4K',             3349, 'iOS 17'),
  (14, '6.7 inches OLED 120Hz',       'Apple A16 Bionic',        12,  256, 'Chụp Macro, Quay Video 4K',             4323, 'iOS 16'),
  (15, '6.1 inches OLED 120Hz',       'Apple A16 Bionic',        12,  256, 'Chụp Macro, Quay Video 4K',             3200, 'iOS 16'),
  (16, '6.7 inches OLED 120Hz',       'Apple A15 Bionic',         8,  256, 'Chụp Macro, Quay Video 4K',             4325, 'iOS 16'),
  (17, '6.1 inches OLED 120Hz',       'Apple A15 Bionic',         6,  256, 'Chụp Macro, Quay Video 4K',             3279, 'iOS 16'),
  (18, '6.7 inches OLED 120Hz',       'Apple A15 Bionic',        12,  256, 'Chụp Macro, Quay Video 4K',             4352, 'iOS 15'),
  (19, '6.1 inches OLED 60Hz',        'Apple A15 Bionic',        12,  256, 'Quay Video 4K',                         3095, 'iOS 15'),
  (20, '6.1 inches OLED 60Hz',        'Apple A15 Bionic',         6,  256, 'Quay Video 4K',                         3240, 'iOS 15'),
  (21, '5.4 inches OLED 60Hz',        'Apple A15 Bionic',         6,  256, 'Chụp Macro, Quay Video 4K',             2438, 'iOS 15'),
  (22, '6.1 inches IPS LCD 60Hz',     'Apple A13 Bionic',         6,  128, 'Chụp Macro, Quay Video 4K',             3110, 'iOS 15'),
  (23, '6.1 inches IPS LCD 60Hz',     'Apple A13 Bionic',         6,   64, 'Chụp Macro, Quay Video 4K',             3110, 'iOS 15'),
  (24, '6.1 inches OLED 60Hz',        'Apple A14 Bionic',         6,  256, 'Chụp Macro, Quay Video 4K',             2815, 'iOS 15'),
  (25, '6.1 inches OLED 60Hz',        'Apple A14 Bionic',         6,  512, 'Chụp Macro, Quay Video 4K',             2815, 'iOS 15'),
  (26, '6.7 inches OLED 60Hz',        'Apple A14 Bionic',         6,  512, 'Chụp Macro',                            3687, 'iOS 15'),
  (27, '6.9 inches OLED 120Hz',       'Apple A19 Pro',           12,  512, 'Chụp Macro',                            4800, 'iOS 19'),
  (28, '6.9 inches OLED 120Hz',       'Apple A19 Pro',           12, 1024, 'Chụp Macro',                            4800, 'iOS 19'),
  (29, '6.1 inches IPS LCD 60Hz',     'Apple A13 Bionic',         4,  128, 'Chụp Góc Rộng, Chụp Góc Siêu Rộng',     3110, 'iOS 15'),
  (30, '6.1 inches IPS LCD 60Hz',     'Apple A13 Bionic',         4,  256, 'Chụp Góc Rộng, Chụp Góc Siêu Rộng',     3110, 'iOS 15'),
  (31, '6.73 inches AMOLED 120Hz',    'Snapdragon 8 Gen 3',      16,  512, 'Chụp Góc Rộng, Siêu Rộng, Tele',        5000, 'Android 14 (HyperOS)'),
  (32, '6.73 inches AMOLED 120Hz',    'Snapdragon 8 Gen 3',      12,  256, 'Chụp Góc Rộng, Siêu Rộng, Tele',        4880, 'Android 14 (HyperOS)'),
  (33, '6.67 inches AMOLED 144Hz',    'MediaTek Dimensity 9200+',12,  512, 'Chụp Góc Rộng, Siêu Rộng, Macro',       5000, 'Android 14 (HyperOS)'),
  (34, '6.36 inches AMOLED 120Hz',    'Snapdragon 8 Gen 2',       8,  256, 'Chụp Góc Rộng, Siêu Rộng, Tele',        4500, 'Android 14 (HyperOS)'),
  (35, '6.67 inches AMOLED 120Hz',    'Dimensity 7200-Ultra',    12,  512, 'Chụp Góc Rộng, Siêu Rộng, Macro',       5000, 'Android 14 (HyperOS)'),
  (36, '6.67 inches AMOLED 120Hz',    'Snapdragon 685',           8,  256, 'Chụp Góc Rộng, Chụp Góc Siêu Rộng',     5000, 'Android 13 (MIUI 14)'),
  (37, '6.8 inches Dynamic AMOLED',   'Snapdragon 8 Gen 2',      12,  256, 'Chụp Góc Rộng, Siêu Rộng, Tele',        5000, 'Android 14 (One UI 6)'),
  (38, '6.4 inches Super AMOLED',     'Exynos 1380',              8,  128, 'Chụp Góc Rộng, Chụp Góc Siêu Rộng',     5000, 'Android 14 (One UI 6)');

-- ============================================================================
-- 4. KHUYẾN MÃI, VOUCHER & ĐIỀU KIỆN ÁP DỤNG (HIỆU LỰC THÁNG 11-12/2026)
-- ============================================================================

INSERT INTO KhuyenMai (maKhuyenMai, ten, noiDung, mucUuDai, ngayBatDau, ngayKetThuc, khungGio, trangThai) VALUES
  (1, 'Ưu đãi cuối năm 2026', 'Giảm tự động 5% cho dòng iPhone 17 & Flagship Android', 5.00,  '2026-11-01', '2026-12-31', '00:00-23:59', 'Đang áp dụng'),
  (2, 'Flash Sale cuối tuần', 'Giảm tự động 10% cho sản phẩm tầm trung',              10.00, '2026-12-01', '2026-12-15', '09:00-21:00', 'Ngừng áp dụng');

INSERT INTO KhuyenMai_DienThoai (maKhuyenMai, maDienThoai) VALUES
  (1, 1), (1, 2), (1, 3), (1, 31), (1, 37);

INSERT INTO Voucher (maVoucher, loaiGiam, giaTriGiam, soLuong, gioiHanSuDung, luotDaDung, ngayBatDau, ngayKetThuc, trangThai) VALUES
  ('GIAM500K', 'Số tiền',   500000.00, 100, 2, 0, '2026-11-01', '2026-12-31', 'Đang hiệu lực'),
  ('SALE5PT',  'Phần trăm',      5.00,  50, 1, 0, '2026-11-01', '2026-12-31', 'Đang hiệu lực');

INSERT INTO DieuKienApDung (maKhuyenMai, maVoucher, giaTriToiThieu, nhomHang, doiTuong) VALUES
  (1,    NULL,       0.00,        'Flagship',       'Tất cả khách hàng'),
  (NULL, 'GIAM500K', 15000000.00, 'Tất cả',         'Khách hàng đã đăng nhập'),
  (NULL, 'SALE5PT',  25000000.00, 'Apple',  'Khách hàng đã đăng nhập');

-- ============================================================================
-- 5. 20 PHIẾU NHẬP KHO ĐÃ DUYỆT (LẬP BỞI NHÂN VIÊN KHO maNhanVien = 3)
-- ============================================================================

INSERT INTO PhieuNhap (maPhieuNhap, nguonNhap, ngayNhap, trangThai, maNhanVien) VALUES
  (1,  'Apple Việt Nam',   '2026-10-19', 'Đã duyệt', 3),
  (2,  'Apple Việt Nam',   '2026-10-20', 'Đã duyệt', 3),
  (3,  'Apple Việt Nam',   '2026-10-21', 'Đã duyệt', 3),
  (4,  'Apple Việt Nam',   '2026-10-22', 'Đã duyệt', 3),
  (5,  'Apple Việt Nam',   '2026-10-25', 'Đã duyệt', 3),
  (6,  'Apple Việt Nam',   '2026-10-27', 'Đã duyệt', 3),
  (7,  'Apple Việt Nam',   '2026-10-29', 'Đã duyệt', 3),
  (8,  'Apple Việt Nam',   '2026-11-01', 'Đã duyệt', 3),
  (9,  'Apple Việt Nam',   '2026-11-02', 'Đã duyệt', 3),
  (10, 'Apple Việt Nam',   '2026-11-03', 'Đã duyệt', 3),
  (11, 'Apple Việt Nam',   '2026-11-03', 'Đã duyệt', 3),
  (12, 'Xiaomi Việt Nam',  '2026-11-04', 'Đã duyệt', 3),
  (13, 'Xiaomi Việt Nam',  '2026-11-04', 'Đã duyệt', 3),
  (14, 'Xiaomi Việt Nam',  '2026-11-05', 'Đã duyệt', 3),
  (15, 'Samsung Việt Nam', '2026-11-06', 'Đã duyệt', 3),
  (16, 'Samsung Việt Nam', '2026-11-07', 'Đã duyệt', 3),
  (17, 'Xiaomi Việt Nam',  '2026-11-08', 'Đã duyệt', 3),
  (18, 'Apple Việt Nam',   '2026-11-09', 'Đã duyệt', 3),
  (19, 'Apple Việt Nam',   '2026-11-10', 'Đã duyệt', 3),
  (20, 'Apple Việt Nam',   '2026-11-11', 'Đã duyệt', 3);

-- Nạp chi tiết phiếu nhập (donGiaNhap lấy đúng từ DienThoai.giaVonBinhQuan)
INSERT INTO ChiTietPhieuNhap (maPhieuNhap, maDienThoai, soLuong, donGiaNhap)
SELECT src.maPhieuNhap, d.maDienThoai, src.soLuong, d.giaVonBinhQuan
FROM (
  SELECT 1  AS maPhieuNhap, 1  AS maDienThoai, 200 AS soLuong UNION ALL
  SELECT 1,  2,  180 UNION ALL
  SELECT 2,  3,  200 UNION ALL
  SELECT 2,  4,  150 UNION ALL
  SELECT 3,  5,  220 UNION ALL
  SELECT 3,  6,  180 UNION ALL
  SELECT 3,  7,  200 UNION ALL
  SELECT 4,  8,  150 UNION ALL
  SELECT 4,  9,  160 UNION ALL
  SELECT 4,  10, 120 UNION ALL
  SELECT 5,  11, 200 UNION ALL
  SELECT 5,  12, 180 UNION ALL
  SELECT 5,  13, 150 UNION ALL
  SELECT 6,  14, 200 UNION ALL
  SELECT 6,  15, 160 UNION ALL
  SELECT 7,  16, 180 UNION ALL
  SELECT 7,  17, 150 UNION ALL
  SELECT 7,  18, 120 UNION ALL
  SELECT 8,  19, 150 UNION ALL
  SELECT 8,  20, 120 UNION ALL
  SELECT 8,  21, 200 UNION ALL
  SELECT 9,  22, 150 UNION ALL
  SELECT 9,  23, 120 UNION ALL
  SELECT 9,  24, 100 UNION ALL
  SELECT 10, 25, 200 UNION ALL
  SELECT 10, 1,  200 UNION ALL  -- Tổng SP 1 = 400 (> ngưỡng tối đa 350 để test cảnh báo vượt trần)
  SELECT 10, 2,  180 UNION ALL  -- Tổng SP 2 = 360 (> ngưỡng tối đa 350)
  SELECT 11, 25, 160 UNION ALL  -- Tổng SP 25 = 360+ (> ngưỡng tối đa 350)
  SELECT 11, 30, 5   UNION ALL
  SELECT 12, 31, 2   UNION ALL
  SELECT 12, 32, 120 UNION ALL
  SELECT 13, 28, 8   UNION ALL  -- SP 28 = 8 (hàng sắp hết: 5 < 8 <= 15)
  SELECT 13, 33, 200 UNION ALL
  SELECT 14, 34, 7   UNION ALL  -- SP 34 = 7 (hàng sắp hết)
  SELECT 14, 35, 150 UNION ALL
  SELECT 15, 36, 3   UNION ALL  -- SP 36 = 3 (dưới ngưỡng tối thiểu <= 5)
  SELECT 15, 37, 100 UNION ALL
  SELECT 16, 38, 250 UNION ALL
  SELECT 17, 29, 5   UNION ALL  -- SP 29 = 5 (dưới ngưỡng tối thiểu <= 5)
  SELECT 17, 31, 130 UNION ALL
  SELECT 18, 27, 2   UNION ALL  -- SP 27 = 2 (dưới ngưỡng tối thiểu <= 5)
  SELECT 18, 30, 150 UNION ALL
  SELECT 19, 26, 6   UNION ALL  -- SP 26 = 6 (hàng sắp hết)
  SELECT 19, 35, 2   UNION ALL
  SELECT 20, 25, 1
) AS src
JOIN DienThoai d ON d.maDienThoai = src.maDienThoai;

-- ============================================================================
-- 6. GIỎ HÀNG, 19 ĐƠN HÀNG, PHIẾU XUẤT, THANH TOÁN & YÊU CẦU HỦY
-- ============================================================================

INSERT INTO GioHang (maGioHang, maKhachHang, maPhien, maVoucher, tongTamTinh, tienGiamGia, tongTien, trangThai) VALUES
  (1, 1, 'SESS_KH01_ACTIVE', NULL, 37990000.00, 0.00, 37990000.00, 'Đang dùng'),
  (2, 2, 'SESS_KH02_ACTIVE', NULL,        0.00, 0.00,        0.00, 'Đang dùng'),
  (3, 3, 'SESS_KH03_ACTIVE', NULL,        0.00, 0.00,        0.00, 'Đang dùng');

-- Thêm sẵn 1 sản phẩm trong giỏ của Khách hàng 1 để test nhanh giao diện Giỏ hàng
INSERT INTO ChiTietGioHang (maGioHang, maDienThoai, soLuong, donGia) VALUES
  (1, 1, 1, 37990000.00);

INSERT INTO DonHang (maDonHang, maKhachHang, maDiaChi, maVoucher, ngayDat, phiVanChuyen, tongTien, trangThai) VALUES
  (1,  1, 1, NULL, '2026-10-24 09:30:00', 0.00, 0.00, 'Đã duyệt'),
  (2,  2, 2, NULL, '2026-10-24 14:15:00', 0.00, 0.00, 'Đã duyệt'),
  (3,  3, 3, NULL, '2026-10-25 10:00:00', 0.00, 0.00, 'Đã giao thành công'),
  (4,  1, 1, NULL, '2026-10-26 11:20:00', 0.00, 0.00, 'Đã giao thành công'),
  (5,  2, 2, NULL, '2026-10-27 16:45:00', 0.00, 0.00, 'Đã giao thành công'),
  (6,  3, 3, NULL, '2026-10-28 08:50:00', 0.00, 0.00, 'Đã hủy'),
  (7,  1, 1, NULL, '2026-11-01 09:10:00', 0.00, 0.00, 'Chờ duyệt'),
  (8,  2, 2, NULL, '2026-11-02 10:30:00', 0.00, 0.00, 'Chờ duyệt'),
  (9,  3, 3, NULL, '2026-11-03 13:00:00', 0.00, 0.00, 'Chờ duyệt'),
  (10, 3, 3, NULL, '2026-11-05 15:20:00', 0.00, 0.00, 'Chờ duyệt'),
  (11, 3, 3, NULL, '2026-11-07 11:40:00', 0.00, 0.00, 'Chờ duyệt'),
  (12, 3, 3, NULL, '2026-11-10 14:05:00', 0.00, 0.00, 'Chờ duyệt'),
  (13, 2, 2, NULL, '2026-11-12 09:50:00', 0.00, 0.00, 'Chờ duyệt'),
  (14, 1, 1, NULL, '2026-11-14 16:10:00', 0.00, 0.00, 'Chờ duyệt'),
  (15, 2, 2, NULL, '2026-11-16 17:30:00', 0.00, 0.00, 'Chờ duyệt'),
  (16, 1, 1, NULL, '2026-11-18 10:15:00', 0.00, 0.00, 'Chờ duyệt'),
  (17, 1, 1, NULL, '2026-11-20 12:45:00', 0.00, 0.00, 'Chờ duyệt'),
  (18, 2, 2, NULL, '2026-11-22 18:00:00', 0.00, 0.00, 'Chờ duyệt'),
  (19, 1, 1, NULL, '2026-11-24 09:00:00', 0.00, 0.00, 'Chờ duyệt');

-- Nạp chi tiết đơn hàng từ dữ liệu cũ (lấy đơn giá đúng theo DienThoai.giaBan)
INSERT INTO ChiTietDonHang (maDonHang, maDienThoai, soLuong, donGia)
SELECT src.maDonHang, d.maDienThoai, src.soLuong, d.giaBan
FROM (
  SELECT 1  AS maDonHang, 8  AS maDienThoai, 1 AS soLuong UNION ALL
  SELECT 2,  9,  2 UNION ALL
  SELECT 3,  10, 1 UNION ALL
  SELECT 4,  1,  2 UNION ALL
  SELECT 5,  2,  1 UNION ALL
  SELECT 6,  3,  1 UNION ALL
  SELECT 6,  4,  1 UNION ALL
  SELECT 7,  5,  1 UNION ALL
  SELECT 8,  6,  1 UNION ALL
  SELECT 9,  7,  2 UNION ALL
  SELECT 10, 7,  2 UNION ALL
  SELECT 11, 7,  2 UNION ALL
  SELECT 12, 7,  2 UNION ALL
  SELECT 13, 7,  2 UNION ALL
  SELECT 14, 7,  2 UNION ALL
  SELECT 15, 7,  2 UNION ALL
  SELECT 16, 7,  2 UNION ALL
  SELECT 17, 7,  2 UNION ALL
  SELECT 18, 7,  2 UNION ALL
  SELECT 19, 7,  2
) AS src
JOIN DienThoai d ON d.maDienThoai = src.maDienThoai;

-- Cập nhật chính xác tổng tiền đơn hàng = SUM(soLuong * donGia) + phiVanChuyen
UPDATE DonHang dh
JOIN (
  SELECT maDonHang, SUM(soLuong * donGia) AS tongHang
  FROM ChiTietDonHang
  GROUP BY maDonHang
) ct ON ct.maDonHang = dh.maDonHang
SET dh.tongTien = ct.tongHang + dh.phiVanChuyen;

-- Tạo phiếu xuất kho cho 3 đơn đã giao thành công (#3, #4, #5)
INSERT INTO PhieuXuat (maPhieuXuat, maDonHang, ngayLap, ghiChu, trangThai, maNhanVien) VALUES
  (1, 3, '2026-10-25', 'Đã đóng gói và bàn giao vận chuyển', 'Đã xuất', 3),
  (2, 4, '2026-10-26', 'Đã đóng gói và bàn giao vận chuyển', 'Đã xuất', 3),
  (3, 5, '2026-10-27', 'Đã đóng gói và bàn giao vận chuyển', 'Đã xuất', 3);

INSERT INTO ChiTietPhieuXuat (maPhieuXuat, maDienThoai, soLuong)
SELECT px.maPhieuXuat, ct.maDienThoai, ct.soLuong
FROM PhieuXuat px
JOIN ChiTietDonHang ct ON ct.maDonHang = px.maDonHang;

-- Tạo giao dịch thanh toán tương ứng cho 19 đơn hàng
INSERT INTO ThanhToan (maGioHang, maDonHang, phuongThuc, soTien, soTienHoan, trangThai)
SELECT
  dh.maKhachHang AS maGioHang,
  dh.maDonHang,
  CASE
    WHEN dh.maDonHang IN (3, 5, 6) THEN 'Thanh toán trực tuyến mô phỏng'
    ELSE 'Thanh toán khi nhận hàng'
  END AS phuongThuc,
  dh.tongTien AS soTien,
  CASE WHEN dh.maDonHang = 6 THEN dh.tongTien ELSE 0.00 END AS soTienHoan,
  CASE
    WHEN dh.maDonHang = 6 THEN 'Đã hoàn tiền'
    WHEN dh.maDonHang IN (3, 4, 5) THEN 'Thành công'
    ELSE 'Chưa thu tiền'
  END AS trangThai
FROM DonHang dh;

-- Ghi nhận yêu cầu hủy cho đơn #6 đã hủy
INSERT INTO YeuCauHuyDon (maDonHang, lyDo, ngayGui, trangThai) VALUES
  (6, 'Khách hàng thay đổi nhu cầu mua sắm trước khi xuất kho', '2026-10-28 09:30:00', 'Đã xác nhận');

-- ============================================================================
-- 7. TỰ ĐỘNG TÍNH & KHỞI TẠO BẢNG TonKho TỪ NHẬP - XUẤT - GIỮ CHỖ
-- ============================================================================

INSERT INTO TonKho (maDienThoai, maKho, tonThucTe, giuCho, tonKhaDung)
SELECT
  d.maDienThoai,
  1 AS maKho,
  (COALESCE(nhap.tongNhap, 0) - COALESCE(xuat.tongXuat, 0)) AS tonThucTe,
  COALESCE(giu.tongGiuCho, 0) AS giuCho,
  (COALESCE(nhap.tongNhap, 0) - COALESCE(xuat.tongXuat, 0) - COALESCE(giu.tongGiuCho, 0)) AS tonKhaDung
FROM DienThoai d
LEFT JOIN (
  SELECT ct.maDienThoai, SUM(ct.soLuong) AS tongNhap
  FROM ChiTietPhieuNhap ct
  JOIN PhieuNhap pn ON pn.maPhieuNhap = ct.maPhieuNhap
  WHERE pn.trangThai = 'Đã duyệt'
  GROUP BY ct.maDienThoai
) nhap ON nhap.maDienThoai = d.maDienThoai
LEFT JOIN (
  SELECT ct.maDienThoai, SUM(ct.soLuong) AS tongXuat
  FROM ChiTietPhieuXuat ct
  JOIN PhieuXuat px ON px.maPhieuXuat = ct.maPhieuXuat
  WHERE px.trangThai = 'Đã xuất'
  GROUP BY ct.maDienThoai
) xuat ON xuat.maDienThoai = d.maDienThoai
LEFT JOIN (
  SELECT ct.maDienThoai, SUM(ct.soLuong) AS tongGiuCho
  FROM ChiTietDonHang ct
  JOIN DonHang dh ON dh.maDonHang = ct.maDonHang
  WHERE dh.trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đang đóng gói')
  GROUP BY ct.maDienThoai
) giu ON giu.maDienThoai = d.maDienThoai;

-- Thêm 1 thông báo mẫu cho Ban Quản lý
INSERT INTO ThongBao (maNguoiNhan, noiDung, thoiDiemTao, daDoc) VALUES
  (1, 'Hệ thống đã khởi tạo dữ liệu mẫu thành công: 38 sản phẩm, 20 phiếu nhập, 19 đơn hàng.', CURRENT_TIMESTAMP, FALSE);

COMMIT;

SET SQL_SAFE_UPDATES = 1;

-- ============================================================================
-- 8. TRUY VẤN KIỂM TRA ĐỐI SOÁT SAU KHI NẠP DỮ LIỆU
-- ============================================================================

-- Kiểm tra tổng số bản ghi của các bảng chính
SELECT 'DanhMuc' AS bang, COUNT(*) AS soBanGhi FROM DanhMuc
UNION ALL SELECT 'DienThoai', COUNT(*) FROM DienThoai
UNION ALL SELECT 'ThongSoKyThuat', COUNT(*) FROM ThongSoKyThuat
UNION ALL SELECT 'TonKho', COUNT(*) FROM TonKho
UNION ALL SELECT 'PhieuNhap', COUNT(*) FROM PhieuNhap
UNION ALL SELECT 'PhieuXuat', COUNT(*) FROM PhieuXuat
UNION ALL SELECT 'DonHang', COUNT(*) FROM DonHang
UNION ALL SELECT 'ThanhToan', COUNT(*) FROM ThanhToan;

-- Kiểm tra các mặt hàng đang nằm trong diện cảnh báo tồn kho (test UC20 / UC56)
SELECT d.maDienThoai, d.ten, tk.tonThucTe, tk.giuCho, tk.tonKhaDung
FROM TonKho tk
JOIN DienThoai d ON d.maDienThoai = tk.maDienThoai
WHERE tk.tonKhaDung <= 15 OR tk.tonThucTe > 350
ORDER BY tk.tonKhaDung ASC;

-- Kiểm tra tổng tiền đơn hàng phải khớp tổng chi tiết + phí vận chuyển (trả về 0 dòng là chuẩn)
SELECT dh.maDonHang, dh.tongTien,
       COALESCE(SUM(ct.soLuong * ct.donGia), 0) + dh.phiVanChuyen AS tongTinhLai
FROM DonHang dh
LEFT JOIN ChiTietDonHang ct ON ct.maDonHang = dh.maDonHang
GROUP BY dh.maDonHang, dh.tongTien, dh.phiVanChuyen
HAVING dh.tongTien <> COALESCE(SUM(ct.soLuong * ct.donGia), 0) + dh.phiVanChuyen;
