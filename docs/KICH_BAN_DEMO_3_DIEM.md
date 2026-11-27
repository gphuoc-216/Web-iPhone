# KỊCH BẢN BẢO VỆ DEMO ĐỒ ÁN OOAD — TRỌN VẸN 3.0 / 3.0 ĐIỂM
**Đối chiếu 1–1 với Báo cáo cuối kỳ:** `docs/BaoCao_CuoiKi_Nhom08_OOAD_v4.docx`  
**Kiến trúc:** Boundary – Control – Entity (BCE) · **CSDL:** MySQL 8.0 chuẩn 3NF (30 bảng · 44 khóa ngoại)

---

## 0. KHỞI ĐỘNG NHANH & RESET DỮ LIỆU SẠCH TRONG 30 GIÂY (TRƯỚC GIỜ BẢO VỆ)

### 0.1. Lệnh khởi động MySQL 8.0, nạp lại dữ liệu mẫu sạch & bật Web Server
Mở **PowerShell** tại thư mục dự án `D:\PTTK` và chạy lần lượt 3 lệnh sau:

```powershell
# 1. Bật MySQL 8.0 cục bộ (nếu chưa chạy)
D:\mysql\start-mysql.bat

# 2. Reset sạch 30 bảng 3NF + nạp dữ liệu mẫu chuẩn kịch bản demo (0 lỗi)
cmd /c "`"D:\mysql\mysql-8.0.40-winx64\bin\mysql.exe`" --defaults-extra-file=D:\mysql\root.cnf --host=127.0.0.1 --port=3306 --default-character-set=utf8mb4 < `"D:\PTTK\database\01_schema.sql`""
cmd /c "`"D:\mysql\mysql-8.0.40-winx64\bin\mysql.exe`" --defaults-extra-file=D:\mysql\root.cnf --host=127.0.0.1 --port=3306 --default-character-set=utf8mb4 quan_ly_ban_dien_thoai < `"D:\PTTK\database\02_seed.sql`""

# 3. Khởi chạy máy chủ PHP 8.3 (tự động nhận cấu hình MySQL cục bộ qua api/db.php)
D:\PHP\php.exe -d extension_dir="D:\PHP\ext" -d extension=mbstring -d extension=pdo_mysql -S localhost:8080 router.php
```

- **Trang Khách hàng (Storefront):** `http://localhost:8080/`
- **Cổng Quản trị Nội bộ (Light Theme):** `http://localhost:8080/admin`
- **Kiểm tra nhanh kết nối API & CSDL:** `http://localhost:8080/api/he-thong`

### 0.2. Bộ tài khoản & Dữ liệu mẫu phục vụ Demo
*(Lưu ý: Mật khẩu đã được ẩn hoàn toàn khỏi giao diện HTML/JS theo chuẩn bảo mật báo cáo `v4`; khi demo chỉ bấm nút chọn nhanh định danh rồi tự gõ mật khẩu `123456`)*

| Vai trò (Actor kế thừa `Người dùng`) | Định danh đăng nhập | Mật khẩu | Dùng để Demo |
| :--- | :--- | :---: | :--- |
| **Khách hàng** (`#KH1`) | `nguyenvana@gmail.com` | `123456` | Phần a: Giỏ hàng, Địa chỉ, Voucher, Đặt hàng & Thanh toán (`UC22–30`) |
| **Ban Quản lý** (`#NV1`) | `admin@phonestore.vn` | `123456` | Phần b: Sản phẩm (`UC1–10`), Tài khoản (`UC48–52`), Mã giảm giá (`UC60–64`), Báo cáo (`UC53–56`), Duyệt phiếu nhập/kiểm kê (`UC13, UC19`) |
| **Nhân viên Kho** (`#NV2`) | `kho@phonestore.vn` | `123456` | Lập phiếu nhập (`UC12`), Xuất kho (`UC15–16`), Kiểm kê (`UC18`), Cảnh báo kho (`UC20`) |
| **Nhân viên Bán hàng / CSKH** (`#NV3`) | `banhang@phonestore.vn` | `123456` | Duyệt đơn & Tiến độ (`UC33–34`), Xử lý yêu cầu hủy & Hoàn tiền (`UC35–38`), Tra cứu KH (`UC47`) |

**Bộ 3 Mã giảm giá mẫu trong `02_seed.sql`:**
- **`VIP200K`**: Giảm `200.000 đ` (đơn từ `10.000.000 đ`, giới hạn **1 lượt/khách**). Khách `nguyenvana@gmail.com` đã dùng 1 lượt ở Đơn `#DH4` → Dùng để biểu diễn **chặn vượt giới hạn lượt dùng mỗi khách** qua `DonHang::demLuotDungVoucher` (`UC30`) và **chặn xóa voucher đã phát sinh lượt dùng** (`UC64`)!
- **`GIAM500K`**: Giảm `500.000 đ` (đơn từ `15.000.000 đ`, `luotDaDung = 0/100`, giới hạn `2 lượt/khách`) → Dùng để áp dụng thành công khi đặt hàng (`UC30`, `UC28`)!
- **`SALE5PT`**: Giảm `5%` cho nhóm hàng `Apple` (đơn từ `25.000.000 đ`, `luotDaDung = 0/50`).

---

## 1. PHẦN A (1.0 ĐIỂM) — CHỨC NĂNG NGHIỆP VỤ CHÍNH: ĐẶT HÀNG & THANH TOÁN (`UC28` + `UC22–26, UC29, UC30`)

> **Mục tiêu ăn trọn 1.0 điểm:** Chạy mượt đầu–cuối từ Khách vãng lai → Gộp giỏ tạm khi đăng nhập → Chọn/thêm địa chỉ & tính phí ship → Kiểm tra giới hạn Voucher → Giữ chỗ tạm & Thanh toán mô phỏng (cả nhánh Lỗi lẫn Thành công) → Lưu đơn `"Chờ duyệt"` kèm **bản chụp người nhận (`tenNguoiNhan`, `sdtNguoiNhan`, `diaChiNhan`)**, trừ tồn khả dụng (`tonThucTe - giuCho`), tăng `Voucher.luotDaDung`, làm sạch giỏ hàng.

### Kịch bản thao tác từng bước trên trình duyệt (3 phút)

1. **Bước 1 — Đối chiếu tồn kho ban đầu (`http://localhost:8080/admin`):**
   - Đăng nhập `admin@phonestore.vn` → mở **Quản lý điện thoại**.
   - Ghi nhận sản phẩm **`#1 iPhone 17 Pro Max 256GB | Chính hãng`**: Tồn thực tế = **`398`**, Giữ chỗ = **`0`**, Tồn khả dụng = **`398`**.
   - Mở **Quản lý mã giảm giá**: ghi nhận mã **`GIAM500K`** đang có lượt dùng **`0 / 100`**.

2. **Bước 2 — Khách vãng lai thêm giỏ hàng tạm & kiểm tra chặn chưa đăng nhập (`UC22, UC23, UC28`):**
   - Mở tab Khách hàng `http://localhost:8080/` (chưa đăng nhập).
   - Bấm **Chi tiết** (`#btnXemChiTiet`) vào **`iPhone 17 Pro Max 256GB | Chính hãng`** → thử nhập số lượng `500` (> `398` khả dụng) hoặc thêm vào giỏ rồi tăng quá tồn để thấy hệ thống chặn vượt `tonKhaDung`. Chọn số lượng `1` → bấm **Thêm vào giỏ hàng** (`#btnThemVaoGioHang`).
   - Mở **Giỏ hàng** → bấm **Tiến hành đặt hàng** (`#btnDatHang`) → Hệ thống gọi `DatHangCtrl::datHang` (`PhienDangNhap::kiemTraDangNhap`), giữ nguyên giỏ tạm và tự bật hộp thoại Đăng nhập.

3. **Bước 3 — Đăng nhập & tự động gộp giỏ hàng tạm (`UC40`):**
   - Chọn nhanh `nguyenvana@gmail.com`, nhập mật khẩu `123456` → bấm **Đăng nhập** (`#btnDangNhap`).
   - Hệ thống tự gọi `GioHang::gopGioHangTam`, gộp sản phẩm vào giỏ tài khoản và mở thẳng màn hình **Xác nhận Đặt hàng & Thanh toán**.

4. **Bước 4 — Địa chỉ giao hàng & Tính phí vận chuyển tự động (`UC25, UC26`):**
   - Trên `#cboDiaChi`, đổi thử giữa các địa chỉ hoặc chọn **+ Nhập địa chỉ giao hàng mới...**, điền `#txtNguoiNhan`, `#txtSoDienThoai`, `#txtDiaChi`, bấm **Lưu địa chỉ mới** (`#btnLuuDiaChiMoi`).
   - Quan sát `#lblThongTinNhanHang` cập nhật tức thời người nhận sẽ chụp snapshot vào đơn và `#lblPhiVanChuyen` tự tính phí theo khu vực / miễn phí khi vượt ngưỡng `nguongMienPhi` (`QuyTacPhiVanChuyen::tinhPhi`).

5. **Bước 5 — Áp dụng mã giảm giá & kiểm chứng giới hạn lượt dùng (`UC30`):**
   - Tại ô `#txtMaGiamGia`, nhập **`VIP200K`** → bấm **Áp dụng** (`#btnApDung`): Hệ thống từ chối với thông báo *"Tài khoản đã dùng hết số lượt cho phép của mã giảm giá này"* (do `DonHang::demLuotDungVoucher` đếm đơn chưa hủy `#DH4`).
   - Nhập tiếp **`GIAM500K`** → bấm **Áp dụng** (`#btnApDung`): Giảm ngay **`-500.000 đ`** trên `#lblTienGiamGia` và cập nhật `#lblTongThanhToan`.

6. **Bước 6 — Thanh toán trực tuyến mô phỏng: biểu diễn cả nhánh Thất bại & Thành công (`UC28, UC29`):**
   - Chọn `#cboPhuongThuc` = **Thanh toán trực tuyến mô phỏng** → bấm **Xác nhận đặt hàng** (`#btnXacNhanDatHang`): Giỏ hàng chuyển sang `'Đã khóa'` và tồn kho đã `giuChoTam`.
   - **Nhánh phụ (ăn điểm ACID):** Bấm **✖ Thanh toán thất bại** → Hệ thống tự động nhả giữ chỗ tạm (`TonKho::nhaGiuChoTam`), hủy giao dịch thanh toán và mở khóa lại giỏ hàng để đặt lại (không sinh đơn hàng rác!).
   - **Nhánh chính:** Mở lại Đặt hàng → bấm **Xác nhận đặt hàng** → bấm **✔ Thanh toán thành công** (`#btnThanhToan`):
     - Màn hình xác nhận hiển thị mã đơn mới `#DH20`, trạng thái **`Chờ duyệt`**, thanh toán **`Thành công`**, và khung **Thông tin người nhận đã lưu trên đơn hàng** (`tenNguoiNhan`, `sdtNguoiNhan`, `diaChiNhan`).
     - Biểu tượng Giỏ hàng trên header tự động về **`0`** (`GioHang::xoaSanPhamDaDat`).

7. **Bước 7 — Kiểm chứng kết quả trong Admin (10 giây chốt điểm):**
   - Quay sang tab `/admin` bấm tải lại:
     - **Quản lý điện thoại:** `#1 iPhone 17 Pro Max 256GB` cập nhật thành **`398 / 1 / 397`** (`giuCho` tăng `1`, `tonKhaDung` giảm `1`).
     - **Quản lý mã giảm giá:** `GIAM500K` tăng lượt dùng lên **`1 / 100`** (`Voucher::tangLuotDaDung`).

---

## 2. PHẦN B (2.0 ĐIỂM) — CHỨC NĂNG QUẢN LÝ & ĐỐI CHIẾU THIẾT KẾ ↔ CODE 1–1

### 2.1. LỰA CHỌN CHÍNH #1 (KHUYÊN DÙNG): PHÂN HỆ QUẢN LÝ SẢN PHẨM (`UC1 – UC10`)
> **Lý do chọn:** Đây là phân hệ đã được đặc tả **Bảng ánh xạ thiết kế sang mã nguồn (`Bảng 231`, Chương 4)** và **Kịch bản kiểm thử (`Bảng 232`)** ngay trong báo cáo `v4`. Toàn bộ tên lớp, tên hàm, tham số, kiểu trả về, câu thông báo và ID điều khiển UI khớp 100% từng ký tự!

#### Kịch bản click biểu diễn 0.5 điểm chạy đúng (`http://localhost:8080/admin` — Vai trò `Ban Quản lý`)
1. **Quản lý Danh mục (`UC1 – UC3`):**
   - Bấm **+ Thêm danh mục** (`#btnThemDanhMuc`): thử để trống bấm **Lưu danh mục** (`#btnLuu`) → báo *"Tên danh mục không được để trống"*; nhập `Apple` → báo *"Tên danh mục đã tồn tại"*; nhập `Điện thoại Gập AI` → báo *"Lưu thành công"*.
   - Bấm **Sửa** trên dòng vừa tạo → đổi tên thành `Flagship Màn Hình Gập` → bấm **Cập nhật** (`#btnCapNhat`).
   - Thử bấm **Xóa** danh mục `#1 Apple` (đang chứa 30 máy) → hệ thống **chặn xóa (HTTP 409)**; bấm **Xóa** danh mục rỗng `#4 OPPO` (hoặc danh mục vừa tạo) → *"Xóa thành công"*.
2. **Quản lý Điện thoại & Khôi phục ngừng kinh doanh (`UC4 – UC6, UC10`):**
   - Bấm **+ Thêm điện thoại** (`#btnThemDienThoai`): điền `#txtTenSanPham` = `Xiaomi 16 Ultra 512GB`, chọn `#cboDanhMuc`, `#cboHang`, `#numRAM`, `#numROM`, `#numDungLuongPin`, `#cboHeDieuHanh` → bấm **Lưu điện thoại mới** (`#btnLuu`).
   - Bấm **Sửa** sản phẩm vừa thêm → đổi ROM lên `1024` → bấm **Cập nhật điện thoại** (`#btnCapNhat`).
   - **Biểu diễn 3 nhánh Xóa điện thoại (`UC6` — điểm nhấn ăn trọn điểm thiết kế):**
     - **Nhánh 1 (Chặn xóa — Luồng phụ 6.2):** Bấm **Xóa** sản phẩm **`#8 iPhone 16 256GB | Chính hãng`** (đang nằm trong Đơn `#DH1 - Đã duyệt`) → Hệ thống **chặn xóa** và liệt kê rõ mã đơn hàng liên quan!
     - **Nhánh 2 (Xóa mềm — Luồng phụ 6.1):** Bấm **Xóa** sản phẩm **`#2 iPhone 17 Pro 512GB | Chính hãng`** (đã có lịch sử phiếu nhập nhưng 0 đơn đang xử lý) → Hệ thống **không xóa vật lý**, tự động chuyển sang **`Ngừng kinh doanh`**!
     - **Nhánh 3 (Xóa cứng — Luồng chính):** Bấm **Xóa** sản phẩm `Xiaomi 16 Ultra 512GB` vừa tạo (chưa phát sinh bất kỳ giao dịch nào) → Hệ thống **xóa cứng khỏi CSDL**!
   - **Khôi phục ngừng kinh doanh (`UC10`):** Tích ô **Hiển thị sản phẩm ngừng kinh doanh** (`#chkNgungKinhDoanh`) → bấm **Khôi phục kinh doanh** trên **`#23 iPhone 11 64GB | Chính hãng`** → xác nhận → sản phẩm quay trở lại danh sách đang kinh doanh!
3. **Tìm kiếm, Lọc đa điều kiện & Chi tiết sản phẩm (`UC7 – UC9` trên trang khách hàng `/`):**
   - Gõ `Snapdragon` hoặc `iPhone 17` vào `#txtTuKhoa` → bấm `#btnTimKiem`.
   - Nhập `#numGiaTu = 40000000`, `#numGiaDen = 20000000` → bấm `#btnLoc` → báo lỗi khoảng giá không hợp lệ. Chọn `#cboHang = Apple`, `#cboRAM = 8`, `#cboROM = 256` → danh sách lọc tức thời.
   - Bấm **Chi tiết** (`#btnXemChiTiet`) → mở F12 Network tab chỉ vào JSON trả về: **không chứa `giaVonBinhQuan`**, có `tonKhaDung` tính động và khuyến mãi đang áp dụng.

#### Bảng Đối chiếu Thiết kế ↔ Mã nguồn 1–1 cho `UC1 – UC10` (1.5 điểm — Mở file đúng dòng trong 5 giây)

| UC | Thành phần Thiết kế trong Báo cáo `v4` | File & Dòng thực tế trong Dự án | Chuỗi phương thức khớp 1–1 Sequence & Class Diagram |
| :-: | :--- | :--- | :--- |
| **CSDL** | RDM `Bảng 149, 151, 167, 168` (`DanhMuc`, `DienThoai`, `ThongSoKyThuat`, `TonKho` — 3NF, `UNIQUE(ten)`, bỏ `maKho` & `tonKhaDung`) | `database/01_schema.sql` (`L53–L60`, `L85–L126`, `L205–L217`) | Chuẩn 3NF, `DienThoai.ten UNIQUE`, `TonKho.maDienThoai UNIQUE`, tính động `(tonThucTe - giuCho) AS tonKhaDung` |
| **UI** | Mục 3.6.1–3.6.4 (`Bảng 173–180`): `#grdDanhMuc`, `#txtTenDanhMuc`, `#grdDienThoai`, `#chkNgungKinhDoanh`, `#txtTenSanPham`, `#cboDanhMuc`, `#cboHang`, `#numRAM`, `#numROM`, `#numDungLuongPin`, `#cboHeDieuHanh`, `#uplHinhAnh` | `admin/js/admin.js` (`L290–L560`)<br>`index.html` (`L20–L102`)<br>`assets/main.js` (`L265–L405`) | Đầy đủ ID điều khiển & sự kiện `Load` / `Click` / `OnChange` đúng `Bảng 173–180` |
| **UC1** | Thêm danh mục — UC `Bảng 10`, Sequence **`Hình 9`**, Class `Bảng 96`, Ánh xạ `Bảng 231 dòng 1` | `api/controllers/QuanLyDanhMucCtrl.php` (`L15, L35–L65`)<br>`api/entities/DanhMuc.php` (`L63, L77, L91, L107`) | `layDanhSachDanhMuc()` → `DanhMuc::timTatCa()`<br>`luuDanhMuc(ten, moTa)` → `DanhMuc::kiemTraHopLe` → `DanhMuc::kiemTraTrungTen` → `DanhMuc::luu` → `DanhMuc::timTatCa` |
| **UC2** | Sửa danh mục — UC `Bảng 11`, Sequence **`Hình 10`**, Class `Bảng 96`, Ánh xạ `Bảng 231 dòng 2` | `api/controllers/QuanLyDanhMucCtrl.php` (`L25–L32, L68–L101`)<br>`api/entities/DanhMuc.php` (`L63, L77, L98, L116`) | `layThongTinDanhMuc(maDanhMuc)` → `DanhMuc::timTheoMa`<br>`capNhatDanhMuc(maDanhMuc, ten, moTa)` → `kiemTraHopLe` → `kiemTraTrungTen` → `capNhat` → `timTatCa` |
| **UC3** | Xóa danh mục — UC `Bảng 12`, Sequence **`Hình 11`**, Class `Bảng 96 & 100`, Ánh xạ `Bảng 231 dòng 3` | `api/controllers/QuanLyDanhMucCtrl.php` (`L104–L127`)<br>`api/entities/DienThoai.php` (`L142`)<br>`api/entities/DanhMuc.php` (`L123`) | `xoaDanhMuc(maDanhMuc)` → `DienThoai::demDienThoaiTheoDanhMuc` (`> 0` chặn HTTP 409) → `DanhMuc::xoa` → `DanhMuc::timTatCa` |
| **UC4** | Thêm điện thoại — UC `Bảng 13`, Sequence **`Hình 12`**, Class `Bảng 100 & 130`, Ánh xạ `Bảng 231 dòng 4` | `api/controllers/QuanLyDienThoaiCtrl.php` (`L44, L50, L93–L165`)<br>`api/entities/DienThoai.php` (`L93, L111, L361`)<br>`api/entities/ThongSoKyThuat.php` (`L115`) | `luuDienThoai(...)` → `DanhMuc::kiemTraTonTai` → `DienThoai::kiemTraHopLe` → `DienThoai::kiemTraTrungTen` → Transaction (`DienThoai::luu` + `ThongSoKyThuat::luu` + `TonKho::khoiTaoTonKho`) → `DienThoai::timDangKinhDoanh` |
| **UC5** | Sửa điện thoại — UC `Bảng 14`, Sequence **`Hình 13`**, Class `Bảng 100 & 130`, Ánh xạ `Bảng 231 dòng 5` | `api/controllers/QuanLyDienThoaiCtrl.php` (`L74–L90, L168–L246`)<br>`api/entities/DienThoai.php` (`L218, L380`)<br>`api/entities/ThongSoKyThuat.php` (`L93, L137`) | `layThongTinDienThoai(maDienThoai)` → `DienThoai::timTheoMa` + `ThongSoKyThuat::timTheoDienThoai` + `DanhMuc::timTatCa`<br>`capNhatDienThoai(...)` → `DanhMuc::kiemTraTonTai` → `kiemTraHopLe` → `kiemTraTrungTen` → `DienThoai::capNhat` + `ThongSoKyThuat::capNhat` |
| **UC6** | Xóa điện thoại (Xóa cứng / Xóa mềm / Chặn xóa) — UC `Bảng 15`, Sequence **`Hình 14`**, Ánh xạ `Bảng 231 dòng 6` | `api/controllers/QuanLyDienThoaiCtrl.php` (`L258–L325`)<br>`api/entities/DonHang.php` (`L118`)<br>`api/entities/DienThoai.php` (`L150, L417, L458`) | `xoaDienThoai(maDienThoai)` → `DonHang::demDonChuaHoanTatTheoDienThoai` (chặn xóa) → `DienThoai::kiemTraPhatSinhGiaoDich` (`true` → `chuyenNgungKinhDoanh`; `false` → `DienThoai::xoa`) → `timDangKinhDoanh` |
| **UC7** | Tìm kiếm sản phẩm — UC `Bảng 16`, Sequence **`Hình 15`**, Ánh xạ `Bảng 231 dòng 7` | `api/controllers/TimKiemSanPhamCtrl.php` (`L48, L57–L64`)<br>`api/entities/DienThoai.php` (`L254`) | `timKiemSanPham(tuKhoa)` → `DienThoai::timTheoTuKhoa` (ẩn `giaVonBinhQuan`, gắn khuyến mãi) |
| **UC8** | Lọc sản phẩm đa điều kiện — UC `Bảng 17`, Sequence **`Hình 16`**, Ánh xạ `Bảng 231 dòng 7` | `api/controllers/TimKiemSanPhamCtrl.php` (`L67–L119`)<br>`api/entities/DienThoai.php` (`L125, L280`)<br>`api/entities/ThongSoKyThuat.php` (`L102`) | `locSanPham(...)` → `DienThoai::kiemTraKhoangGia` → `DienThoai::timTheoDieuKienLoc` → `ThongSoKyThuat::locTheoThongSo` |
| **UC9** | Xem chi tiết sản phẩm — UC `Bảng 18`, Sequence **`Hình 17`**, Class `Bảng 100, 112, 130, 132` | `api/controllers/TimKiemSanPhamCtrl.php` (`L122–L156`)<br>`api/entities/TonKho.php` (`L93`)<br>`api/entities/KhuyenMai.php` (`L171`) | `layChiTietSanPham(maDienThoai)` → `DienThoai::timTheoMa` → `ThongSoKyThuat::timTheoDienThoai` → `KhuyenMai::timKhuyenMaiDangApDung` → `TonKho::tinhTonKhaDung` |
| **UC10** | Khôi phục sản phẩm ngừng kinh doanh — UC `Bảng 19`, Sequence **`Hình 18`**, Ánh xạ `Bảng 231 dòng 8` | `api/controllers/QuanLyDienThoaiCtrl.php` (`L64–L71, L328–L379`)<br>`api/entities/DienThoai.php` (`L212, L218, L411`) | `layDanhSachNgungKinhDoanh()` → `DienThoai::timNgungKinhDoanh`<br>`khoiPhucDienThoai` (`DienThoai::timTheoMa` + `DanhMuc::kiemTraTonTai`) → `xacNhanKhoiPhuc` → `DienThoai::capNhatTrangThai('Đang kinh doanh')` |

---

### 2.2. ĐỐI CHIẾU NHANH CHO PHẦN A (`UC22 – UC30`) — DÙNG KHI GIẢNG VIÊN HỎI SÂU CODE ĐẶT HÀNG

| UC | Thiết kế trong Báo cáo `v4` | File & Dòng thực tế | Phương thức thực thi chính |
| :-: | :--- | :--- | :--- |
| **UC22–24** | Giỏ hàng (`Hình 30–32`, `Bảng 94, 106`) | `api/controllers/GioHangCtrl.php` (`L26, L51, L82, L121`)<br>`api/entities/GioHang.php` (`L121, L131, L141, L189`) | `themVaoGioHang`, `capNhatSoLuong`, `xoaKhoiGioHang` → kiểm tra `TonKho::tinhTonKhaDung`, tính động `tinhTongTamTinh` (không lưu cột dư thừa) |
| **UC25–26** | Địa chỉ & Tính phí vận chuyển (`Hình 33–34`, `Bảng 98, 122`) | `api/controllers/DatHangCtrl.php` (`L15, L25, L46, L84`)<br>`api/entities/QuyTacPhiVanChuyen.php` (`L86, L102, L108`) | `chonDiaChi`, `luuDiaChiMoi` → `GioHang::ghiNhanDiaChi`; `tinhPhiVanChuyen` → `DiaChiGiaoHang::layMaQuyTac` → `QuyTacPhiVanChuyen::tinhPhi` → `GioHang::capNhatPhiVanChuyen` |
| **UC30** | Áp dụng mã giảm giá (`Hình 38`, `Bảng 102, 104, 134`) | `api/controllers/ApDungVoucherCtrl.php` (`L15, L97`)<br>`api/entities/DonHang.php` (`L131`)<br>`api/entities/DieuKienApDung.php` (`L161`) | `apDungVoucher(maVoucher, maGioHang, maKhachHang)` → `Voucher::kiemTraHopLe` → `DonHang::demLuotDungVoucher` → `DieuKienApDung::kiemTraDieuKien` → `GioHang::tinhGiamGia` → `tinhLaiTongTien` |
| **UC28–29** | Đặt hàng & Thanh toán ACID (`Hình 36–37`, `Bảng 104, 126, 132, 153`) | `api/controllers/DatHangCtrl.php` (`L143, L175–L326, L329–L493`)<br>`api/entities/DonHang.php` (`L304`)<br>`api/entities/TonKho.php` (`L146, L160, L177`)<br>`api/entities/Voucher.php` (`L221`) | `xacNhanDatHang` + `thanhToan` + `hoanTatDonHang`: `GioHang::khoaGioHang` → `TonKho::giuChoTam` → `ThanhToan::ghiNhanPhuongThuc` → `DonHang::luu` (snapshot `tenNguoiNhan, sdtNguoiNhan, diaChiNhan`) → `ChiTietDonHang::luu` → `Voucher::tangLuotDaDung` → `GioHang::xoaSanPhamDaDat` |

---

### 2.3. PHƯƠNG ÁN DỰ PHÒNG #2: QUẢN LÝ TÀI KHOẢN (`UC48 – UC52`)
*(Dùng khi giảng viên yêu cầu chọn một phân hệ quản lý khác ngoài Sản phẩm)*

- **Điểm ăn tiền khi demo trên `/admin` → Quản lý tài khoản:**
  1. Tìm tài khoản theo tên/email/SĐT (`#txtTuKhoa`, `#btnTimKiem` — `UC48`).
  2. Thử bấm **Khóa** tài khoản `Ban Quản lý` (`#1`) → hệ thống **chặn HTTP 403** (`TaiKhoan::kiemTraQuyenKhoa` chặn tự khóa và chặn khóa Ban Quản lý!).
  3. Bấm **Khóa** tài khoản khách hàng `#4`, nhập lý do vào `#txtLyDoKhoa` → lưu thật `lyDoKhoa` xuống CSDL và thu hồi toàn bộ phiên đăng nhập (`PhienDangNhap::huyPhienTheoTaiKhoan` — `UC49`); bấm **Mở khóa** (`UC50`).
  4. Bấm **+ Tạo tài khoản nhân viên** (`#btnTaoNhanVien`) tạo nhân viên mới (`UC51`); bấm **Sửa nhân viên** đổi vai trò từ `Nhân viên Kho` sang `Nhân viên Bán hàng / CSKH` (`#btnCapNhatNhanVien` — `UC52`, có cảnh báo nếu nhân viên đang có phiếu nhập nháp/chờ duyệt và tự thu hồi phiên).

| UC | Sequence & Class trong `v4` | File & Dòng thực tế | Chuỗi phương thức khớp 1–1 |
| :-: | :--- | :--- | :--- |
| **UC48** | Tìm kiếm tài khoản (`Hình 56`, `Bảng 124`) | `api/controllers/QuanLyTaiKhoanCtrl.php` (`L29, L36, L44`)<br>`api/entities/TaiKhoan.php` (`L233, L251, L257`) | `layDanhSachTaiKhoan` → `TaiKhoan::timTatCa`; `timKiemTaiKhoan` → `TaiKhoan::timTheoTuKhoa`; `layChiTietTaiKhoan` → `TaiKhoan::timTheoMa` |
| **UC49** | Khóa tài khoản (`Hình 57`, `Bảng 124`) | `api/controllers/QuanLyTaiKhoanCtrl.php` (`L54–L79`)<br>`api/entities/TaiKhoan.php` (`L162, L318`) | `khoaTaiKhoan` → `TaiKhoan::kiemTraQuyenKhoa` → `TaiKhoan::capNhatTrangThai('Bị khóa', lyDoKhoa)` → `PhienDangNhap::huyPhienTheoTaiKhoan` |
| **UC50** | Mở khóa tài khoản (`Hình 58`, `Bảng 124`) | `api/controllers/QuanLyTaiKhoanCtrl.php` (`L82–L99`) | `moKhoaTaiKhoan` → `TaiKhoan::capNhatTrangThai('Hoạt động', null)` → `TaiKhoan::timTatCa` |
| **UC51** | Tạo tài khoản nhân viên (`Hình 59`, `Bảng 114, 124`) | `api/controllers/QuanLyTaiKhoanCtrl.php` (`L108–L158`)<br>`api/entities/NhanVien.php` (`L68, L86`) | `taoTaiKhoanNhanVien` → `TaiKhoan::kiemTraHopLe` → `TaiKhoan::kiemTraTonTai` → Transaction (`TaiKhoan::luu` + `NhanVien::luu`) → `NhanVien::timTatCa` |
| **UC52** | Cập nhật nhân viên & vai trò (`Hình 60`, `Bảng 114, 124`) | `api/controllers/QuanLyTaiKhoanCtrl.php` (`L161–L230`)<br>`api/entities/NhanVien.php` (`L78, L97`) | `layThongTinNhanVien` → `NhanVien::timTheoTaiKhoan` + `TaiKhoan::layVaiTro`; `capNhatNhanVien` → `TaiKhoan::kiemTraHopLeCapNhat` → `NhanVien::capNhat` → `TaiKhoan::capNhatVaiTro` → `PhienDangNhap::huyPhienTheoTaiKhoan` |

---

### 2.4. PHƯƠNG ÁN DỰ PHÒNG #3: QUẢN LÝ MÃ GIẢM GIÁ (`UC60 – UC64`)

- **Điểm ăn tiền khi demo trên `/admin` → Quản lý mã giảm giá:**
  1. Lọc theo trạng thái trên `#cboTrangThai` / `#btnLocXem` (`UC61`).
  2. Bấm **+ Tạo mã giảm giá** (`#btnTao`) tạo mã `TET2027` kèm điều kiện đơn tối thiểu & nhóm hàng (`DieuKienApDung::lienKet` — `UC60`).
  3. Bấm **Sửa** mã `TET2027` cập nhật giá trị giảm / số lượng phát hành (`#btnCapNhat` — `UC62`, có chặn giảm `soLuong < luotDaDung`).
  4. Bấm **Khóa** chuyển trạng thái sang `'Đã khóa'` (`UC63`).
  5. Thử bấm **Xóa** mã **`VIP200K`** (đã có `luotDaDung = 1`) → hệ thống **chặn xóa (HTTP 409)** và gợi ý khóa mã; bấm **Xóa** mã **`TET2027`** (`luotDaDung = 0`) → xóa thành công cả `DieuKienApDung` lẫn `Voucher` (`UC64`)!

| UC | Sequence & Class trong `v4` | File & Dòng thực tế | Chuỗi phương thức khớp 1–1 |
| :-: | :--- | :--- | :--- |
| **UC60** | Tạo mã giảm giá (`Hình 68`, `Bảng 102, 134`) | `api/controllers/QuanLyVoucherCtrl.php` (`L55–L122`)<br>`api/entities/Voucher.php` (`L96, L112, L195`)<br>`api/entities/DieuKienApDung.php` (`L143`) | `luuVoucher` → `Voucher::kiemTraTrungMa` → `Voucher::kiemTraHopLe` → Transaction (`Voucher::luu` + `DieuKienApDung::lienKet`) → `Voucher::timTatCa` |
| **UC61** | Tra cứu & lọc mã giảm giá (`Hình 69`, `Bảng 102, 134`) | `api/controllers/QuanLyVoucherCtrl.php` (`L15, L23, L33`)<br>`api/entities/DieuKienApDung.php` (`L113`) | `locVoucher` → `Voucher::timTheoTrangThai`; `layChiTietVoucher` → `Voucher::timTheoMa` + `DieuKienApDung::timTheoVoucher` (trả về 1 đối tượng `?DieuKienApDung`) |
| **UC62** | Cập nhật mã giảm giá (`Hình 70`, `Bảng 134`) | `api/controllers/QuanLyVoucherCtrl.php` (`L49, L125–L192`)<br>`api/entities/Voucher.php` (`L207`) | `layThongTinVoucher` → `capNhatVoucher` → `Voucher::kiemTraHopLe` → `Voucher::capNhat` → `Voucher::timTatCa` |
| **UC63** | Khóa mã giảm giá (`Hình 71`, `Bảng 134`) | `api/controllers/QuanLyVoucherCtrl.php` (`L195, L205–L222`)<br>`api/entities/Voucher.php` (`L215`) | `khoaVoucher` → `Voucher::capNhatTrangThai('Đã khóa')` → `Voucher::timTatCa` |
| **UC64** | Xóa mã giảm giá (`Hình 72`, `Bảng 134`) | `api/controllers/QuanLyVoucherCtrl.php` (`L225–L257`)<br>`api/entities/Voucher.php` (`L135, L242`) | `xoaVoucher` → `Voucher::demLuotSuDung` (`> 0` chặn HTTP 409) → `DieuKienApDung::xoaTheoVoucher` + `Voucher::xoa` → `Voucher::timTatCa` |
