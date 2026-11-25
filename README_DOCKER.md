# Hướng dẫn chạy Phone Store (BCE — 30 bảng MySQL) bằng Docker

## Yêu cầu trên máy người nhận
- Cài đặt **Docker Desktop** (Windows/macOS) hoặc **Docker Engine + Docker Compose** (Linux).
- Không cần cài PHP, Apache hay MySQL thủ công.

## Cách chạy chỉ với 1 lệnh
1. Giải nén (hoặc clone) thư mục dự án và mở terminal tại thư mục gốc chứa `docker-compose.yml`.
2. Chạy lệnh:
   ```bash
   docker compose up -d --build
   ```
3. Chờ khoảng 15–30 giây ở lần chạy đầu tiên để MySQL tự động nạp `database/01_schema.sql` (30 bảng) và `database/02_seed.sql` (dữ liệu mẫu).

## Đường dẫn truy cập
- **Trang Khách hàng**: http://localhost:8080/
- **Cổng Quản trị Nội bộ (3 Actor)**: http://localhost:8080/admin
- **Kiểm tra API & CSDL**: http://localhost:8080/api/he-thong
- **phpMyAdmin (xem 30 bảng MySQL)**: http://localhost:8081/ (`root` / `root123`)

## Tài khoản mẫu (Mật khẩu chung: `123456`)
| Vai trò | Định danh đăng nhập |
| --- | --- |
| Ban Quản lý | `admin@phonestore.vn` |
| Nhân viên Kho | `kho@phonestore.vn` |
| Nhân viên Bán hàng / CSKH | `banhang@phonestore.vn` |
| Khách hàng | `nguyenvana@gmail.com` |

## Các lệnh thường dùng
- Xem log: `docker compose logs -f`
- Dừng dự án (giữ dữ liệu DB): `docker compose down`
- Xóa sạch DB để nạp lại dữ liệu mẫu từ đầu: `docker compose down -v && docker compose up -d --build`
