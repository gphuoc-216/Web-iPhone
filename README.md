# 📱 Phone Store — Hệ thống Quản lý Bán Điện Thoại (Kiến trúc BCE & RDM 30 bảng)

Hệ thống thương mại điện tử và quản trị vận hành cửa hàng bán điện thoại xây dựng bằng **PHP 8.3 (PDO MySQL)**, **MySQL 8.0** và **HTML5 / CSS3 / Vanilla JavaScript**, thiết kế chuẩn theo mô hình **Boundary – Control – Entity (BCE)**, **Mô hình dữ liệu quan hệ (RDM) 30 bảng vật lý** và **Phân quyền RBAC chặt chẽ cho 4 nhóm Actor**.

---

## ✨ Điểm nổi bật của hệ thống

- **Kiến trúc BCE chuẩn hóa**:
  - **Boundary**: Giao diện Khách hàng (`index.html`, `assets/`) và Cổng Quản trị Nội bộ 3 Actor (`admin/`).
  - **Control**: Đầy đủ **18 lớp Controller** (`api/controllers/`) + Bộ định tuyến tập trung & Middleware phân quyền RBAC (`api/index.php`).
  - **Entity**: **30 lớp Entity** (`api/entities/` — 29 thực thể lưu trữ + thực thể tổng hợp `BaoCao.php`).
- **Cơ sở dữ liệu MySQL 8.0 chuẩn RDM (30 bảng, 36 khóa ngoại)**:
  - Toàn bộ tên bảng, cột, lớp, hàm, tham số và key JSON tuân thủ **tiếng Việt không dấu** (`PascalCase` / `camelCase`, khóa tiền tố `ma...`, trạng thái `trangThai`).
- **Lưu trữ cục bộ 100% (Standalone Localhost & Docker)**:
  - Upload ảnh sản phẩm trực tiếp vào `uploads/` bằng `move_uploaded_file()` và phục vụ kho ảnh sẵn có tại `storage/shop-assets/img/`.
- **Đóng gói sẵn Docker Compose**: Chạy trọn bộ Web (Apache + PHP 8.3) + MySQL 8.0 (tự nạp schema & dữ liệu mẫu) + phpMyAdmin chỉ với 1 lệnh.

---

## 🗂️ Cấu trúc thư mục dự án

```text
Web-iPhone/
├── admin/                        # Cổng Quản trị Nội bộ (Ban Quản lý, Kho, CSKH)
│   ├── index.html
│   ├── css/admin.css
│   └── js/admin.js
├── api/                          # Backend PHP theo mô hình BCE
│   ├── index.php                 # Router tập trung + Kiểm tra phiên & phân quyền RBAC
│   ├── db.php                    # Kết nối PDO MySQL + Hàm chuẩn hóa JSON & đường dẫn ảnh
│   ├── autoload.php              # Tự động nạp Entity & Controller
│   ├── storage.php               # Phục vụ ảnh cục bộ (tương thích đường dẫn cũ)
│   ├── upload.php                # Upload ảnh cục bộ vào thư mục uploads/
│   ├── entities/                 # 30 lớp Entity (29 bảng + BaoCao.php)
│   └── controllers/              # 18 lớp Controller xử lý nghiệp vụ UC1–UC64
├── assets/                       # Giao diện Khách hàng
│   ├── main.js
│   └── style.css
├── database/
│   ├── 01_schema.sql             # 30 bảng RDM, khóa chính, khóa ngoại, CHECK constraint
│   └── 02_seed.sql               # Dữ liệu mẫu (4 vai trò, 38 điện thoại, 20 phiếu nhập, 19 đơn hàng)
├── storage/shop-assets/img/      # 89 ảnh sản phẩm & giao diện cục bộ
├── uploads/                      # Thư mục lưu ảnh sản phẩm tải lên mới
├── index.html                    # Trang chủ Khách hàng
├── config.js / custom.js / custom.css
├── router.php                    # Router hỗ trợ chạy nhanh bằng PHP Built-in Server
├── .htaccess / web.config        # Cấu hình Rewrite cho Apache (XAMPP/Laragon/Docker) & IIS
├── Dockerfile                    # Đóng gói PHP 8.3 + Apache
├── docker-compose.yml            # Khởi chạy đồng bộ Web + MySQL 8.0 + phpMyAdmin
└── README_DOCKER.md              # Hướng dẫn tóm tắt Docker
```

---

## 👥 Phân quyền 4 Actor & Chức năng (UC1 – UC64)

| Actor | Nhóm chức năng được phép | Chức năng bị chặn |
| --- | --- | --- |
| **Ban Quản lý** | Danh mục & Điện thoại (UC1–6, UC10), Chính sách giá & Lợi nhuận (UC11), Duyệt/Từ chối Phiếu nhập (UC13), Duyệt Kiểm kê & Ngưỡng tồn kho (UC19, UC21), Phí vận chuyển (UC27), Quản lý Tài khoản & Vai trò (UC48–52), 4 Báo cáo thống kê (UC53–56), Khuyến mãi & Voucher (UC57–64) | Không được lập phiếu nhập, lập/xác nhận phiếu xuất, lập biên bản kiểm kê |
| **Nhân viên Kho** | **Actor duy nhất** lập & gửi duyệt Phiếu nhập (UC12, UC14), lập/xác nhận/hủy Phiếu xuất (UC15–17), lập Biên bản kiểm kê (UC18), xem Cảnh báo tồn kho 3 mức (UC20) | Không được duyệt phiếu nhập, duyệt kiểm kê, đổi giá bán hay quản lý tài khoản |
| **Nhân viên Bán hàng / CSKH** | Duyệt đơn & cập nhật tiến độ giao hàng (UC33–34), Tiếp nhận/Xác nhận/Từ chối hủy đơn & Hoàn tiền mô phỏng (UC35–38), Tra cứu Khách hàng & Lịch sử mua chỉ xem (UC47) | Không được thao tác kho, sản phẩm, giá hay tài khoản |
| **Khách hàng** | Tìm kiếm & lọc đa điều kiện (UC7–9), Giỏ hàng vãng lai & tự gộp khi đăng nhập (UC22–24), Đặt hàng, tính phí ship, áp Voucher & Thanh toán 2 pha (UC25–30), Tự hủy đơn `Chờ duyệt` / Gửi yêu cầu hủy (UC31–32), Hồ sơ, Sổ địa chỉ, Lịch sử đơn hàng (UC43–46) | Không được truy cập Cổng Quản trị (`/admin`) |

*Cả 4 Actor đều dùng chung hệ thống xác thực qua `XacThucCtrl` (UC39–UC42: Đăng ký, Đăng nhập, Đăng xuất, Quên/Đặt lại mật khẩu) và nhận thông báo thời gian thực qua bảng `ThongBao`.*

---

## ⚙️ Các quy tắc nghiệp vụ cốt lõi

1. **Mô hình Tồn kho & Giá bán**:
   - `tonKhaDung = tonThucTe - giuCho`.
   - Duyệt phiếu nhập (UC13): cộng `tonThucTe` & `tonKhaDung`, tính bình quân gia quyền `giaVonBinhQuan`, tự động tính lại `giaBan = giaVonBinhQuan * (1 + tyLe / 100)`.
   - Xác nhận xuất kho (UC16): trừ `tonThucTe` và giảm `giuCho` (không trừ `tonKhaDung` lần 2), tự động chuyển đơn hàng sang `Đã giao shipper`.
   - Kiểm kê kho (UC18–19): khớp tồn → `Hoàn tất`; có chênh lệch → bắt buộc nhập ghi chú → `Chờ duyệt`; khi duyệt nếu `tonThucTe < giuCho` sẽ phát cảnh báo đơn bị ảnh hưởng tới CSKH.
2. **Đặt hàng 2 pha & Thanh toán (UC28–UC29)**:
   - Khách bấm **Xác nhận đặt hàng** → khóa `GioHang` (`Đã khóa`) và giữ chỗ tạm (`giuCho += soLuong`).
   - **COD**: tạo `DonHang` (`Chờ duyệt`) ngay lập tức.
   - **Thanh toán trực tuyến mô phỏng**: hiển thị 3 nút mô phỏng (**Thành công** / **Thất bại** / **Hết hạn giữ chỗ**). Nếu thất bại hoặc hết hạn → nhả toàn bộ `giuCho`, mở lại giỏ hàng và **không** tạo `DonHang`.
3. **Vòng đời Đơn hàng & Hủy đơn (UC31–UC38)**:
   - Thứ tự bắt buộc: `Chờ duyệt` → `Đã duyệt` → `Đang đóng gói` → `Đã giao shipper` *(tự động từ UC16)* → `Đang giao` *(chặn nếu chưa có phiếu xuất `Đã xuất`)* → `Đã giao thành công`.
   - Khách tự hủy trực tiếp khi `Chờ duyệt` (UC31); từ `Đã duyệt` đến `Đang giao` gửi yêu cầu hủy cho CSKH (UC32). Đơn đã xuất kho bắt buộc từ chối hủy (UC37).
4. **Khuyến mãi & Voucher (UC57–UC64)**:
   - `KhuyenMai` trừ trực tiếp vào `giaBan` và kiểm tra xung đột thời gian theo sản phẩm.
   - `Voucher` kiểm tra điều kiện `DieuKienApDung` & `gioiHanSuDung`, chỉ giảm trên tiền hàng (không giảm phí vận chuyển); chỉ cho xóa (UC64) khi `luotDaDung == 0`, nếu đã dùng phải dùng chức năng Khóa (UC63).

---

## 🚀 Hướng dẫn cài đặt & chạy dự án

### Cách 1: Chạy nhanh bằng Docker (Khuyên dùng — Không cần cài PHP/MySQL)

1. Cài đặt **Docker Desktop** (Windows/macOS) hoặc **Docker Compose** (Linux).
2. Clone dự án và khởi chạy:
   ```bash
   git clone https://github.com/gphuoc-216/Web-iPhone.git
   cd Web-iPhone
   docker compose up -d --build
   ```
3. Đợi khoảng 15–30 giây lần đầu để MySQL tự nạp `01_schema.sql` và `02_seed.sql`, sau đó mở trình duyệt:
   - **Trang Khách hàng**: http://localhost:8080/
   - **Cổng Quản trị Nội bộ**: http://localhost:8080/admin
   - **Kiểm tra trạng thái API & CSDL**: http://localhost:8080/api/he-thong
   - **phpMyAdmin**: http://localhost:8081/ (`root` / `root123`)

> Muốn reset lại dữ liệu mẫu ban đầu:
> ```bash
> docker compose down -v && docker compose up -d --build
> ```

---

### Cách 2: Chạy trực tiếp trên Localhost (XAMPP / Laragon / PHP Built-in Server)

1. Bật **MySQL 8.0+** (hoặc MariaDB) và import lần lượt 2 file:
   - `database/01_schema.sql`
   - `database/02_seed.sql`
2. Kiểm tra thông tin kết nối trong `api/db.php` (mặc định: `127.0.0.1:3306`, DB `quan_ly_ban_dien_thoai`, user `root`, password `''`).
3. Khởi chạy Web Server:
   - **XAMPP / Laragon / IIS**: Trỏ DocumentRoot vào thư mục gốc dự án rồi mở `http://localhost/` và `http://localhost/admin`.
   - **Hoặc PHP Built-in Server**:
     ```bash
     php -S 127.0.0.1:8080 router.php
     ```
     Sau đó mở `http://127.0.0.1:8080/` và `http://127.0.0.1:8080/admin`.

---

## 🔑 Tài khoản mẫu dùng để kiểm thử

Mật khẩu chung cho tất cả tài khoản mẫu là: **`123456`**

| Vai trò | Định danh đăng nhập | Giao diện sau khi đăng nhập |
| --- | --- | --- |
| **Ban Quản lý** | `admin@phonestore.vn` | Cổng Quản trị (`/admin`) |
| **Nhân viên Kho** | `kho@phonestore.vn` | Cổng Quản trị (`/admin`) |
| **Nhân viên Bán hàng / CSKH** | `banhang@phonestore.vn` | Cổng Quản trị (`/admin`) |
| **Khách hàng** | `nguyenvana@gmail.com` | Trang bán hàng (`/`) |
| **Khách hàng (Bị khóa — test UC40)** | `tranthib@gmail.com` | Bị chặn đăng nhập (`Tài khoản đã bị khóa`) |

**Mã Voucher mẫu (UC30)**: `GIAM500K` (giảm 500.000đ cho đơn từ 15 triệu), `SALE5PT` (giảm 5% cho dòng Apple từ 25 triệu).

---

## 🔌 Nhóm API Endpoint chính (`/api/...`)

Mọi request cần xác thực gửi kèm header `X-Ma-Phien: <maPhien>` (hoặc `Authorization: Bearer <maPhien>`).

| Nhóm API | Đường dẫn | Controller |
| --- | --- | --- |
| Hệ thống | `GET /api/he-thong` | `db.php` |
| Xác thực | `POST /api/xac-thuc/{dang-ky, dang-nhap, dang-xuat, quen-mat-khau, dat-lai-mat-khau}` | `XacThucCtrl` |
| Tìm kiếm & Chi tiết | `GET /api/tim-kiem`, `GET /api/tim-kiem/chi-tiet?maDienThoai=...` | `TimKiemSanPhamCtrl` |
| Danh mục & Điện thoại | `GET/POST/PUT/DELETE /api/danh-muc`, `/api/dien-thoai`, `POST /api/dien-thoai/khoi-phuc` | `QuanLyDanhMucCtrl`, `QuanLyDienThoaiCtrl` |
| Chính sách giá | `GET/POST /api/chinh-sach-gia` | `ChinhSachGiaCtrl` |
| Kho hàng & Kiểm kê | `/api/phieu-nhap`, `/api/phieu-xuat`, `/api/kiem-ke`, `/api/kiem-ke/canh-bao` | `PhieuNhapCtrl`, `PhieuXuatCtrl`, `KiemKeCtrl` |
| Giỏ hàng & Voucher | `/api/gio-hang`, `/api/ap-dung-voucher` | `GioHangCtrl`, `ApDungVoucherCtrl` |
| Đặt hàng & Thanh toán | `POST /api/dat-hang`, `POST /api/dat-hang/thanh-toan`, `/api/phi-van-chuyen` | `DatHangCtrl` |
| Đơn hàng & Hủy đơn | `/api/don-hang`, `/api/huy-don/{tu-huy, gui-yeu-cau, xu-ly, hoan-tien}` | `QuanLyDonHangCtrl`, `HuyDonHangCtrl` |
| Hồ sơ, CSKH & Thông báo | `/api/ho-so`, `/api/khach-hang`, `/api/thong-bao` | `HoSoKhachHangCtrl` |
| Tài khoản & Báo cáo | `/api/tai-khoan`, `/api/bao-cao/{san-pham-ban-chay, don-hang, ton-kho}` | `QuanLyTaiKhoanCtrl`, `ThongKeBaoCaoCtrl` |
| Khuyến mãi & Voucher | `/api/khuyen-mai`, `/api/voucher` | `QuanLyKhuyenMaiCtrl`, `QuanLyVoucherCtrl` |
