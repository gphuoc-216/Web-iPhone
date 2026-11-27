# KẾ HOẠCH TỔNG THỂ SỬA BÁO CÁO ĐỒ ÁN OOAD – NHÓM 08 (CHẠY SONG SONG 5 THÀNH VIÊN)

- **Đề tài:** Hệ thống quản lý bán điện thoại
- **Học phần:** Phân tích Thiết kế Hướng đối tượng (HK1 – Năm học 2026–2027) – GVHD: Thầy Hoàng Mạnh Hà
- **Nguồn đối chiếu chuẩn:**
  1. Quy chế chấm điểm & nộp bài của giảng viên: `docs/261_BaoCaoCuoiKi.docx` (7,0đ Báo cáo + 3,0đ Demo)
  2. Tổng hợp lỗi sơ đồ & đặc tả cần tránh của giảng viên: `docs/Lỗi.docx`
  3. Tài liệu rà soát & quyết định thiết kế của nhóm: `docs/HuongDan_SuaBaoCao_Nhom088.docx`
  4. Bản báo cáo hiện tại (218 trang): `docs/BaoCao_CuoiKi_Nhom08_OOAD.docx`
- **Mục tiêu chất lượng:** Đạt **100% tiêu chí quy chế giảng viên**, sạch 100% bẫy lỗi trong `Lỗi.docx`, đồng bộ truy vết 1–1 xuyên suốt **BFD → Use Case → Đặc tả UC → Sequence BCE → Class Diagram → RDM → Giao diện UI → Code Demo (UC28 & UC1–UC10)**, hoàn thành và nộp trước **23:59 ngày 04/12/2026** (hạn hệ thống: 05/12/2026).

---

## PHẦN 1: BƯỚC 0 – CHỐT KẾT QUẢ ĐÍCH & HỢP ĐỒNG ĐỒNG BỘ (LÀM TRƯỚC KHI CHIA VIỆC)

> **Nguyên tắc sống còn để 5 người làm SONG SONG không phải chờ nhau:**
> Thay vì làm tuần tự (người vẽ Class phải chờ người vẽ Sequence xong mới biết tên hàm, người làm UI phải chờ RDM xong mới biết cột), **toàn bộ chữ ký hàm thay đổi, thuộc tính thêm/bớt, khóa ngoại mới, kiểu dữ liệu SQL và tên control UI được KHÓA CỨNG ngay tại Bước 0 này ("Single Source of Truth")**. Cả 5 thành viên bắt tay làm đồng thời từ Ngày 1 theo đúng bản hợp đồng dưới đây; khi ghép lại sẽ khớp 100% từng ký tự.

---

### 1.1. Bản đồ hình dung kết quả đích của từng bước (Target End-State)

| Phần nội dung trong báo cáo | Kết quả đích cuối cùng phải đạt được (Target End-State) | Thỏa mãn tiêu chí nào trong `261_BaoCaoCuoiKi.docx` | Né được các bẫy lỗi nào trong `Lỗi.docx` |
| :--- | :--- | :--- | :--- |
| **1. Hình thức KLTN & Chương 1 – 2 (Mở đầu, Tổng quan, BFD, Yêu cầu)** | - Có Bìa chính + Bìa phụ chuẩn KLTN (logo trường, khung viền, đủ 5 thành viên, GVHD), Lời cảm ơn & Lời mở đầu tách riêng, Mục lục/Danh sách bảng/Danh sách hình cập nhật tự động, **100% khổ giấy A4** (0 trang A3, 0 chỗ `[Nhóm điền]`, 0 chữ tô vàng).<br>- Ch.1 chốt rõ phạm vi 6 phân hệ cốt lõi (64 UC), chưa quản lý theo từng IMEI/Serial.<br>- Ch.2 có **Hình 1 (BFD)** nền trắng chữ ≥ 9pt khớp tên UC; có mục **Mô tả 5 quy trình nghiệp vụ cốt lõi**; **Mục 2.3.2** là bảng Yêu cầu chức năng (`FR01–FR22`) phủ đủ 64 UC; **Mục 2.3.3** là bảng Yêu cầu phi chức năng (`NFR01–NFR08`) định lượng đo được. | - Hình thức trình bày tương tự Khóa luận tốt nghiệp (bìa, mục lục...).<br>- Giới thiệu tổng quan, lý do chọn đề tài, mục tiêu.<br>- Mô tả các hoạt động nghiệp vụ của hệ thống → xác định yêu cầu chức năng và yêu cầu phi chức năng. | - Đồng bộ định dạng font chữ, cỡ chữ, khoảng cách số mục lục xuyên suốt các chương.<br>- Sơ đồ độ phân giải cao, nền trắng, không lòe loẹt, không tràn lề. |
| **2. Mô hình Use Case & 64 Bảng Đặc tả (`Mục 3.1 – 3.2`)** | - **Hình 2 (UC tổng quát):** chỉ vẽ **6 UC mức nhóm phân hệ** + 3 UC chung (`Đăng nhập`, `Đăng xuất`, `Quên mật khẩu`), đặt toàn bộ Actor chính (`Người dùng` + 4 Actor con kế thừa) bên trái khung System Boundary.<br>- **Hình 3 – Hình 8 (6 UC phân rã):** đặt ngay trước từng nhóm đặc tả, phủ đúng và đủ 64 UC chi tiết; chiều `<<include>>` / `<<extend>>` chuẩn UML; `UC42 Quên mật khẩu` là UC độc lập nối thẳng Actor `Người dùng`.<br>- **Bảng 8 – Bảng 71 (64 đặc tả):** có đủ Mã + Tên UC, dòng **Tác nhân (Actor)**, dòng **Quan hệ**, căn đều Justify; `Alternative Flow` đánh số bám sát bước `Main Flow` (3.1, 5.1...), **đã dọn sạch lỗi mất kết nối CSDL và thao tác Hủy vụn vặt**. | - Xây dựng Use Case diagram tổng quát → Phân rã từng Use Case lớn thành các Use Case chi tiết → Xây dựng đặc tả cho từng Use Case chi tiết theo mẫu quy định. | - Không tách vụn bước kiểm tra thành oval UC; không vẽ `Quên mật khẩu <<extend>> Đăng nhập`.<br>- Đúng chiều `<<extend>>` (từ UC mở rộng trỏ về UC cơ sở), có khung System Boundary, Actor chính bên trái.<br>- UC tổng quát chỉ vẽ mức nhóm; mọi UC đặc tả đều có trên hình phân rã.<br>- Đặc tả có Tên UC, đánh số nhánh phụ khớp bước chính, **không đưa lỗi mất kết nối CSDL vào dòng sự kiện phụ**. |
| **3. Sequence Diagram BCE & Phụ lục A (`Mục 3.3`, `Hình 9 – 72`)** | - Đủ **64 Sequence Diagram** tương ứng 1–1 với 64 UC đặc tả, tách rạch ròi `Actor → Boundary → Control → Entity`.<br>- Đầy đủ thanh Activation bar; **không có dấu `X` (`destroy`) trên Actor/Boundary**; mọi khung `alt` đều có message gọi Entity kiểm tra ngay trước đó; **không vẽ thao tác Hủy giao diện hay lỗi kết nối CSDL**; các SD dài dùng `ref` gọn trong 1 trang A4 chữ ≥ 8pt.<br>- **Bảng 72** sạch ghi chú nội bộ; **Phụ lục A** khớp 100% mọi lời gọi hàm trên 64 SD. | - Ứng với từng đặc tả UC, phân rã hệ thống thành mô hình Boundary – Control – Entity, vẽ Sequence diagram cho từng UC (số lượng SD = 64 = số UC đặc tả). | - Không gộp CSDL vào Controller; không nhầm message Actor với self-message.<br>- Luôn có message truy vấn Entity trước `alt`; đặt `alt` ngay tại bước kiểm tra (không dồn cuối hình); `loop` rõ điều kiện.<br>- **Bỏ thao tác Hủy/xác nhận vụn vặt và lỗi mất kết nối CSDL**; `Đăng xuất` kết thúc ngay tại xóa phiên; không gộp Xem danh sách + Xem chi tiết quá dài; không mất viền khung `alt`. |
| **4. Class Diagram & Đặc tả 30 Lớp (`Mục 3.4`, `Hình 73 – 77`, `Bảng 74 – 135`)** | - Chỉ gồm **30 lớp dữ liệu** (29 Entity lưu trữ + lớp tổng hợp tạm `BaoCao`), tuyệt đối không có lớp `...Ctrl`.<br>- **Hình 73** là sơ đồ lớp tổng quan rút gọn (chỉ tên lớp + loại quan hệ `──`, `◇──`, `◆──` + bội số 2 đầu) trên 1 trang A4 ngang; **Hình 74 – 77** là 4 sơ đồ cụm chi tiết có đủ **3 ngăn (Tên lớp – Thuộc tính – Phương thức)**.<br>- **0 đường Dependency (`..>`)**, **0 mũi tên chiều điều hướng (`-->`, `▸`, `◂`)**.<br>- **Bảng 75 – 134** mô tả đủ thuộc tính (có cột kiểu dữ liệu riêng) và phương thức khớp **100%** với Phụ lục A & Sequence Diagram. | - Tổng hợp các Entity xây dựng Class diagram.<br>- Thể hiện rõ loại association (`aggregation` / `composition`), **không thể hiện chiều quan hệ, không thể hiện quan hệ dependency**.<br>- Mô tả chi tiết từng class: thuộc tính và phương thức. | - Đủ bản chất Association / Aggregation / Composition và bội số 2 đầu; không dùng `..>`, bỏ chiều mũi tên, không vẽ `...Ctrl`.<br>- Không để class trống phương thức; tên hàm + tham số + kiểu trả về khớp 100% với message gửi tới Entity trong SD.<br>- Sơ đồ tổng quan rút gọn 1 trang + tách 4 sơ đồ cụm chi tiết. |
| **5. Mô hình Dữ liệu Quan hệ RDM (`Mục 3.5`, `Hình 78 – 82`, `Bảng 136 – 168`)** | - **Mục 3.5.1** trình bày rõ bảng phân tích chọn lọc/loại bỏ thuộc tính dẫn xuất (`tonKhaDung`, `chenhLech`, `tongTamTinh`, `GioHang.tongTien`, `maKho`) đạt chuẩn **3NF**, ánh xạ thành **30 bảng vật lý**.<br>- **Hình 78 – 82** chỉ hiển thị tên bảng, tên cột, kiểu dữ liệu SQL có độ dài, ký hiệu `PK`/`FK` và đường nối Crow's Foot (gồm đủ các FK mới của `NhanVien`, `GioHang`, `CanhBaoTonKho`), nằm gọn trên trang A4.<br>- **Bảng 138 – 167** dùng kiểu SQL chuẩn MySQL (`VARCHAR(n)`, `DECIMAL(15,2)`, `DATETIME`...) và gộp thành cột **Ràng buộc toàn vẹn (RBTV)**. | - Phân tích, chọn lựa/loại bỏ thuộc tính cần lưu trữ lâu dài → xây dựng mô hình dữ liệu lưu trữ thực tế.<br>- Vẽ mô hình dữ liệu quan hệ và mô tả chi tiết thông tin từng bảng dữ liệu. | - Đúng thứ tự chương: Class Diagram → RDM.<br>- Mọi cột `VARCHAR` đều có độ dài cụ thể (`VARCHAR(100)`, `VARCHAR(255)`...).<br>- Không nhét giải thích chi tiết vào trong hình vẽ RDM.<br>- Gộp các cột khóa rời rạc thành một cột **Ràng buộc toàn vẹn (RBTV)** (`PK`, `FK`, `UNIQUE`, `NOT NULL`, `DEFAULT`, `CHECK`). |
| **6. Thiết kế Giao diện 28 Màn hình (`Mục 3.6`, `Hình 83 – 110`, `Bảng 169 – 225`)** | - Phủ trọn vẹn **64 UC trên 28 màn hình**.<br>- **Hình 83 – 110** chụp giao diện sáng (light theme), rõ nét, kèm ảnh biểu mẫu con (Thêm/Sửa, Quên mật khẩu), không gắn nhãn `(UC...)` giả tạo, không lộ mật khẩu test.<br>- **Bảng 170 – 225** sạch 100% lỗi tên control mất dấu kèm số (`btnLu4` → `btnLuu`), có đủ ô nhập cho mọi cột dữ liệu mới (`cboHang`, 7 ô thông số kỹ thuật, dòng hàng nhập, lý do từ chối...), đủ biến cố `Load`, `Click`, `OnChange` gọi đúng hàm Controller ở SD. | - Thiết kế giao diện cho toàn bộ các chức năng đã xây dựng.<br>- Mỗi giao diện mô tả rõ các **control** có trong giao diện và các **event** có thể được kích hoạt bên trong giao diện đó. | - Khớp 1–1 tên Boundary ở Bảng 73, tên hàm Controller ở SD và các thuộc tính nhập liệu ở Class/RDM; không sót câu nháp wireframe trong văn bản. |
| **7. Chương 4 – 5: Demo, Kiểm thử, Kết luận & Đóng gói (`tr. 201 – 218`)** | - **Chương 4:** Điền đầy đủ môi trường thực tế (`PHP 8.x`, `MySQL 8.0`, `HTML5/CSS3/JS`), sơ đồ cây thư mục BCE (`admin/`, `assets/`, `api/controllers/`, `api/entities/`, `database/`), kịch bản + ảnh chụp thực tế **Phần A – Nghiệp vụ chính (`UC28 Đặt hàng`)** và truy vết code 1–1 **Phần B – Chức năng quản lý (`Quản lý Danh mục UC1–3` & `Quản lý Điện thoại UC4–6, UC10`)**, cùng **Bảng 228** mở rộng 18 ca kiểm thử thực tế đạt 100%.<br>- **Chương 5, TLTK, Phụ lục B:** Tổng kết đúng số liệu (64 UC, 64 SD, 30 lớp, 30 bảng, 28 màn hình), đối ứng hạn chế ↔ hướng phát triển, 6–8 tài liệu tham khảo chuẩn IEEE, đủ tỷ lệ đóng góp 5 thành viên (100%). | - Demo 1 chức năng nghiệp vụ chính chạy đúng, đủ (1,0đ).<br>- Demo 1 chức năng quản lý bất kỳ chạy đúng (0,5đ) + code khớp thiết kế UC → Đặc tả → SD → Class (1,5đ).<br>- Tổng hợp kết quả đạt được, ưu/khuyết điểm, hướng phát triển; nộp `.zip` + in 1 quyển đóng bìa + 5 SV ký tên. | - Xóa sạch 100% placeholder `[Nhóm điền]`, `[điền]`, `[ ]`, chữ tô vàng và metadata rác trước khi xuất PDF & in ấn. |

---

### 1.2. Các điểm `⚠️ LƯU Ý XUNG ĐỘT` giữa Hướng dẫn cũ và Quy định của Giảng viên (BẮT BUỘC ĐỌC KỸ)

Khi đối chiếu chéo `HuongDan_SuaBaoCao_Nhom088.docx` với 2 văn bản gốc của thầy Hoàng Mạnh Hà (`261_BaoCaoCuoiKi.docx` và `Lỗi.docx`), có **05 điểm xung đột trực tiếp**. Theo nguyên tắc tối thượng, **nhóm bắt buộc tuân theo Quy chế & File `Lỗi.docx` của Giảng viên**:

> [!CAUTION]
> **`⚠️ LƯU Ý XUNG ĐỘT #1 (NGHIÊM TRỌNG NHẤT): Nhánh "Người dùng chọn Hủy" và "Lỗi kết nối CSDL" (Q3)`**
> - **Trong `HuongDan_SuaBaoCao_Nhom088.docx` (Q3, Mục 3.1, Phụ lục P2.3 & P2.4):** Khuyên nhóm chèn thêm khối `alt Người dùng chọn "Hủy"` và `alt Lỗi kết nối cơ sở dữ liệu` vào cả 64 Sequence Diagram để khớp với bảng đặc tả.
> - **Trong `Lỗi.docx` của Giảng viên (cấm tuyệt đối ở cả 2 nơi):**
>   - *Dòng 24–25 (Lỗi Bảng Đặc tả UC):* **"Đặc tả thừa các lỗi kỹ thuật / runtime không kiểm soát được: Không đưa các ngoại lệ về hạ tầng, bảo mật hệ thống hay lỗi mất kết nối cơ sở dữ liệu (CSDL) vào dòng sự kiện phụ của nghiệp vụ."**
>   - *Dòng 39–41 (Lỗi Sequence Diagram):* **"Đưa các thao tác dư thừa vào SD: Bỏ các thao tác xác nhận giao diện vụn vặt (như chọn Hủy, chọn Đồng ý, xác nhận xóa, actor nhập lại thông tin sau khi sai). Bỏ các ngoại lệ kỹ thuật/runtime không kiểm soát được (lỗi mất kết nối CSDL, lỗi lưu dữ liệu hệ thống)."**
> - **QUYẾT ĐỊNH CHỐT THEO GIẢNG VIÊN:**
>   1. **Ở 64 Sequence Diagram (TV2 & TV3):** **TUYỆT ĐỐI KHÔNG vẽ thêm khối `alt Hủy` hay `alt Lỗi kết nối CSDL`** (bỏ qua mẫu P2.3, P2.4 trong file hướng dẫn cũ!). Chỉ vẽ các khối `alt` kiểm tra dữ liệu & quy tắc nghiệp vụ thực sự (trùng tên, rỗng dữ liệu, vượt tồn khả dụng, sai trạng thái, đã phát sinh giao dịch...).
>   2. **Ở 64 Bảng Đặc tả UC (TV1 – Bảng 8 đến Bảng 71):** **Xóa toàn bộ dòng sự kiện phụ ghi *"Lỗi kết nối cơ sở dữ liệu..."*** và các dòng *"hủy thao tác / không xác nhận xóa"* giao diện đơn thuần; đánh số lại các dòng sự kiện phụ nghiệp vụ còn lại cho liền mạch. (Cũng xóa câu *"Hệ thống kết nối được với cơ sở dữ liệu"* ở Tiền điều kiện cho gọn sạch).
>   3. **Ở Bảng 72 – Quy ước SD (TV2):** **Xóa hẳn dòng "Nhánh lược bỏ"** (vì Đặc tả và SD nay đã khớp nhau 100%, không còn nhánh nào bị lược bỏ nữa!).

> [!WARNING]
> **`⚠️ LƯU Ý XUNG ĐỘT #2: Quan hệ của `UC42 Quên mật khẩu` (Hình 6) & Bố cục `Hình 2 Use Case Tổng quát`**
> - **Trong `HuongDan_SuaBaoCao_Nhom088.docx` (Mục 2.1 & 2.2):**
>   - Khuyên Hình 6 giữ mũi tên `<<extend>>` từ `Quên mật khẩu (UC42)` vào `Đăng nhập (UC40)` và bỏ đường nối trực tiếp từ Actor.
>   - Khuyên Hình 2 có thể đặt Actor `Ban Quản lý` sang **bên phải** khung hệ thống để đỡ cắt dây.
> - **Trong `Lỗi.docx` của Giảng viên:**
>   - *Dòng 4:* **"Không vẽ các liên kết điều hướng giao diện thành UC phụ (ví dụ: Quên mật khẩu là một UC độc lập, không nối `<<extend>>` vào Đăng nhập; yêu cầu 'phải đăng nhập trước' thì ghi ở Pre-condition chứ không vẽ mũi tên `<<include>>` tới UC Đăng nhập)."**
>   - *Dòng 8 & Dòng 12, 50:* **"Đặt Actor chính ở bên trái khung ranh giới (nếu có tác nhân phụ như cổng thanh toán thì đặt bên phải)."** & **"Biểu đồ Use Case tổng quát chỉ thể hiện mức nhóm chức năng (như Quản lý Sản phẩm, Quản lý Bán hàng & Đơn hàng...), không vẽ chi tiết."**
> - **QUYẾT ĐỊNH CHỐT THEO GIẢNG VIÊN (TV1 thực hiện):**
>   1. **Trên Hình 6 & Bảng 47, 49 (`UC40`, `UC42`):** Xóa mũi tên `<<extend>>` giữa `UC42 Quên mật khẩu` và `UC40 Đăng nhập`! Vẽ `UC42 Quên mật khẩu` là **Use Case độc lập**, nối trực tiếp từ Actor `Người dùng`. Trong Bảng 47 (`UC40`) và Bảng 49 (`UC42`), dòng `Quan hệ` đều ghi `"Không có"`, đồng thời bỏ nhánh phụ `3.4` điều hướng sang Quên mật khẩu ở Bảng 47.
>   2. **Trên Hình 2 (`Use Case tổng quát`, tr. 11):** Chỉ vẽ **6 hình oval mức nhóm phân hệ cốt lõi** (`Quản lý sản phẩm`, `Quản lý kho hàng`, `Quản lý bán hàng và đơn hàng`, `Quản lý khách hàng và tài khoản`, `Quản lý báo cáo`, `Quản lý khuyến mãi và mã giảm giá`) + cụm 3 UC kế thừa của `Người dùng` (`Đăng nhập`, `Đăng xuất`, `Quên mật khẩu`). Giữ **toàn bộ Actor chính ở bên trái** khung chữ nhật System Boundary (vì chỉ có 9 oval nên để bên trái cực kỳ thoáng, không cắt dây).

> [!WARNING]
> **`⚠️ LƯU Ý XUNG ĐỘT #3: Controller tự kiểm tra trùng dữ liệu mà không gọi Entity & Gộp luồng trên SD**
> - **Trong `Lỗi.docx` (dòng 33–34 & 42–44):**
>   - *Dòng 34:* **"Controller không thể tự kiểm tra dữ liệu đã tồn tại hay chưa (ví dụ: kiểm tra trùng tên danh mục/sản phẩm khi Thêm/Sửa, kiểm tra thông tin đăng nhập) nếu không gửi message truy vấn sang Entity."** → Kiểm tra Bảng 94 (`DanhMuc`) và Bảng 98 (`DienThoai`) hiện mới chỉ có `kiemTraHopLe(...)`, chưa tách rõ hàm truy vấn kiểm tra trùng tên trên Entity!
>   - *Dòng 42–44:* `UC41 Đăng xuất` phải kết thúc ngay tại `huyPhien(maPhien)`, không vẽ nối luồng đăng nhập lại; các SD xử lý nghiệp vụ phía sau (`UC2, UC5, UC13, UC16, UC19, UC33...`) bắt đầu trực tiếp từ thao tác chọn đối tượng cần xử lý, không vẽ gộp toàn bộ luồng Xem danh sách + Xem chi tiết ở đầu hình làm SD bị dài chữ bé!
> - **QUYẾT ĐỊNH CHỐT THEO GIẢNG VIÊN (TV2, TV3, TV4, TV5):**
>   1. Bổ sung rõ phương thức truy vấn trùng tên trên Entity `DanhMuc`: `kiemTraTrungTen(ten: String, maDanhMuc: int): boolean` (gọi ở `UC1` với `maDanhMuc = 0`, `UC2` với `maDanhMuc` hiện tại) và trên `DienThoai`: `kiemTraTrungTen(ten: String, maDienThoai: int): boolean` (gọi ở `UC4`, `UC5` ngay trước khung `alt Tên đã tồn tại`). *(Đã cài sẵn trong code PHP của nhóm!)*
>   2. Ở các SD xử lý (`UC2, UC3, UC5, UC6, UC13, UC16, UC19, UC33, UC36, UC37, UC49, UC50, UC58, UC59, UC62, UC63, UC64`): Lược bỏ bước mở trang tải toàn bộ danh sách ở đầu SD nếu làm hình quá cao, bắt đầu thẳng từ bước Actor chọn đối tượng / gửi biểu mẫu để mọi SD hiển thị to, rõ chữ `≥ 8pt` trên 1 trang A4 dọc.

> [!IMPORTANT]
> **`⚠️ LƯU Ý XUNG ĐỘT #4: Chuỗi giá trị trạng thái/vai trò SQL, Kiểu tiền tệ & Cột `Voucher.luotDaDung` trong RDM**
> - **Vấn đề 1 (Chuỗi có dấu vs không dấu):** `HuongDan_SuaBaoCao_Nhom088.docx` (Mục 5.3, dòng 255–261) viết ví dụ `CHECK IN ('KhachHang','NVBanHang','NVKho','QuanLy')`, `DEFAULT 'HoatDong'`, `'ChoDuyet'` (không dấu), trong khi toàn bộ Đặc tả UC, 64 Sequence Diagram (`"Đã hủy"`, `"Bị khóa"`, `"Chờ duyệt"`, `"Đã xuất"`) và file `database/01_schema.sql` của nhóm đều dùng **tiếng Việt có dấu**!
>   - **CHỐT:** Thống nhất dùng **chuỗi tiếng Việt có dấu** trong RDM (`DEFAULT 'Hoạt động'`, `DEFAULT 'Chờ duyệt'`, `DEFAULT 'Đang kinh doanh'`, vai trò `'Khách hàng'`, `'Nhân viên Bán hàng / CSKH'`, `'Nhân viên Kho'`, `'Ban Quản lý'`) để khớp 100% với Đặc tả, Sequence Diagram và `database/01_schema.sql`!
> - **Vấn đề 2 (Kiểu tiền tệ SQL):** `HuongDan` ghi `DECIMAL(15,0)`, còn `database/01_schema.sql` đang dùng `DECIMAL(15,2)`.
>   - **CHỐT:** Dùng thống nhất **`DECIMAL(15,2)`** cho toàn bộ các cột tiền tệ (`giaVonBinhQuan`, `giaBan`, `donGiaNhap`, `donGia`, `phiVanChuyen`, `tongTien`, `tienGiamGia`, `soTien`, `soTienHoan`, `mucPhi`, `mucPhiMacDinh`, `nguongMienPhi`, `giaTriToiThieu`, `mucUuDai`, `giaTriGiam`) để khớp 100% giữa RDM và file `database/01_schema.sql` nộp kèm trong `.zip`!
> - **Vấn đề 3 (`Voucher.luotDaDung` đang bỏ lửng "Nhóm quyết định" ở dòng 216 `HuongDan`):**
>   - **CHỐT DỨT KHOÁT:** **GIỮ LẠI thuộc tính & cột `Voucher.luotDaDung` (`INT NOT NULL DEFAULT 0`)**. Giải trình rõ trong Bảng 3.5.1: *"Giữ lại có kiểm soát để đối chiếu nhanh tổng số lượt phát hành (`luotDaDung < soLuong`) khi tra cứu danh sách mã (UC61) và áp mã (UC30) mà không phải `COUNT` toàn bảng `DonHang`; được tăng bằng `Voucher.tangLuotDaDung(maVoucher)` khi tạo đơn (UC28) và giảm bằng `Voucher.hoanLuotSuDung(maVoucher)` khi hủy đơn (UC31, UC36)."* Trong khi đó, giới hạn lượt dùng **trên từng khách hàng** (`gioiHanSuDung`) được kiểm tra chính xác bằng `DonHang.demLuotDungVoucher(maKhachHang, maVoucher)`.

---

### 1.3. Hợp đồng đồng bộ xuyên suốt (Master Sync Contract) cho 5 thành viên

#### 1.3.1. Quy ước ký hiệu bắt buộc cho từng loại sơ đồ

1. **Sơ đồ BFD (`Hình 1`, TV1):**
   - Nền trắng 100%, viền khung đen hoặc xanh đậm (`#1E3A8A`), chữ đen cỡ `≥ 9pt` khi in trên giấy A4.
   - Chỉ dùng hình chữ nhật bo góc nhẹ + đường nối phân cấp cây (không có mũi tên luồng dữ liệu). Tên các nút lá của 6 phân hệ cốt lõi phải trùng khớp tên Use Case trong `Bảng 4`.
2. **Sơ đồ Use Case (`Hình 2 – Hình 8`, TV1):**
   - Bắt buộc có khung chữ nhật **System Boundary** ghi tên hệ thống/phân hệ ở trên cùng.
   - Toàn bộ Actor chính đặt **bên trái** khung ranh giới. Quan hệ kế thừa Actor dùng mũi tên tam giác rỗng nét liền (`--|>`): 4 Actor (`Khách hàng`, `Nhân viên Bán hàng / CSKH`, `Nhân viên Kho`, `Ban Quản lý`) trỏ về Actor cha `Người dùng`.
   - Đường nối Actor – Use Case là nét liền **không có đầu mũi tên** (`--`).
   - Quan hệ giữa các UC:
     - `<<include>>`: mũi tên nét đứt từ **UC cơ sở → UC bị gọi** (`UC28 ..> UC25 : <<include>>`, `UC28 ..> UC26 : <<include>>`, `UC28 ..> UC29 : <<include>>`, `UC13 ..> UC14 : <<include>>` *(nếu giữ, hoặc bỏ nếu UC13 độc lập — chốt: chỉ giữ đúng các quan hệ thực tế có khung `ref`/`opt` trên SD: `UC28 <<include>> UC25, UC26, UC29`; `UC30 <<extend>> UC28`; `UC38 <<extend>> UC36`)*).
     - `<<extend>>`: mũi tên nét đứt từ **UC mở rộng → UC cơ sở** (`UC30 ..> UC28 : <<extend>>`, `UC38 ..> UC36 : <<extend>>`). **Tuyệt đối không vẽ `UC42 <<extend>> UC40`!**
3. **Sequence Diagram BCE (`Hình 9 – Hình 72`, TV2 & TV3):**
   - Dùng đúng header PlantUML chuẩn dưới đây cho cả 64 file `.puml`:
     ```plantuml
     @startuml
     skinparam backgroundColor #FFFFFF
     skinparam sequenceMessageAlign center
     skinparam responseMessageBelowArrow true
     skinparam ParticipantPadding 25
     skinparam BoxPadding 10
     skinparam maxMessageSize 180
     skinparam sequence {
       LifeLineBorderColor #334155
       ActivationBackgroundColor #DBEAFE
       ActivationBorderColor #1D4ED8
       GroupBorderColor #1E293B
       GroupBorderThickness 1.5
     }
     hide footbox
     title UCxx - <Tên Use Case khớp chính xác Bảng đặc tả>
     ```
   - Thứ tự lifeline từ trái sang phải: `actor "<Tên Actor>"` → `boundary "Màn hình <Tên chuẩn Bảng 73>"` → `control <TenCtrl>` → các `entity <TenEntity>`.
   - Đầy đủ `activate` / `deactivate` trên Boundary, Control và Entity trong mỗi lời gọi; **tuyệt đối không có lệnh `destroy` (`X`) ở bất kỳ lifeline nào**.
   - Trước mỗi `alt` bắt buộc có lời gọi hàm kiểm tra sang Entity và nhận biến kết quả trả về. Điều kiện `alt` / `else` / `loop` ghi tiếng Việt có dấu rõ nghĩa, xuống dòng bằng `\n` nếu dài để không đè lên thanh activation.
4. **Class Diagram (`Hình 73 – Hình 77`, TV4):**
   - Chỉ vẽ 30 lớp (29 Entity + `BaoCao`), dùng `hide circle` và `skinparam classAttributeIconSize 0` (`-` cho thuộc tính private, `+` cho phương thức public).
   - **Cấm tuyệt đối:** lớp `...Ctrl`, đường phụ thuộc nét đứt (`..>`), mũi tên chỉ chiều (`-->`, `<--`, ký tự `>`, `<`, `▸`, `◂`, `▾` cạnh tên quan hệ).
   - Chỉ dùng đúng 3 loại đường nối có bội số ở 2 đầu:
     - Kết hợp (Association): `LopA "1" -- "0..*" LopB : tên quan hệ`
     - Tập hợp (Aggregation): `DanhMuc "1" o-- "0..*" DienThoai : phân loại`
     - Hợp thành (Composition): `DonHang "1" *-- "1..*" ChiTietDonHang : gồm`
   - `Hình 73` ẩn thuộc tính & phương thức (`hide members`), đặt trên 1 trang A4 ngang. `Hình 74 – 77` hiển thị đầy đủ cả **3 ngăn** (Tên lớp, Thuộc tính, Phương thức). Lớp `BaoCao` trên `Hình 77` đứng độc lập kèm `note bottom of BaoCao : Lớp tổng hợp tạm phục vụ thống kê,\nkhông lưu trữ trong CSDL và không nối FK`.
5. **Mô hình Dữ liệu Quan hệ RDM (`Hình 78 – Hình 82`, TV4):**
   - Ký hiệu Crow's Foot nền trắng, mỗi bảng chỉ hiển thị: `Tên bảng`, danh sách `Tên cột : Kiểu SQL(độ dài)` và nhãn `[PK]`, `[FK]`, `[PK, FK]`. Không viết mô tả tiếng Việt dài dòng bên trong hộp bảng trên hình.
   - Mọi khóa ngoại trong `Bảng 168` đều phải xuất hiện thành dòng cột `[FK]` trong hộp bảng con và có đường nối Crow's Foot sang bảng cha.

---

#### 1.3.2. Bảng danh mục thay đổi dây chuyền (Master Ripple-Effect Table)

Bảng này khóa cứng **16 thay đổi xuyên suốt** từ trên xuống dưới. Bất kỳ thành viên nào làm phần của mình chỉ cần chiếu đúng cột tương ứng:

| # | Nội dung thay đổi | Ảnh hưởng ở UC & Đặc tả (TV1) | Ảnh hưởng ở Sequence Diagram (TV2 & TV3) | Ảnh hưởng ở Class Diagram (TV4) | Ảnh hưởng ở RDM (TV4) | Ảnh hưởng ở Giao diện UI (TV5) |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- |
| **R01** | **Bỏ `maKho`** (cửa hàng chỉ có 1 kho duy nhất – Q1) | **Bảng 25 (`UC18`):** Bỏ chữ "chọn kho / mã kho", chỉ ghi *"tạo đợt kiểm kê tại thời điểm kiểm kê"*. | **Hình 26 (`UC18`):** Đổi `taoDotKiemKe(maKho, thoiDiem)` → `taoDotKiemKe(thoiDiem)`; `BienBanKiemKe.taoMoi(maNhanVien, thoiDiem)`; đổi `TonKho.layTonTheoKho(maKho)` → `TonKho.layTonKhoHienHanh()` (đã có sẵn trên lớp `TonKho`!). | **Bảng 77, 78 (`BienBanKiemKe`):** Xóa thuộc tính `maKho`; sửa `taoMoi(maNhanVien: int, thoiDiem: Date): int`.<br>**Bảng 129, 130 (`TonKho`):** Xóa thuộc tính `maKho`; xóa hẳn hàm `layTonTheoKho(maKho)`. | **Bảng 138 (`BienBanKiemKe`) & Bảng 165 (`TonKho`), Hình 80:** Xóa cột `maKho` khỏi cả 2 bảng. | **Bảng 184, 185 (`Kiểm kê tồn kho`):** Không có ô chọn kho; sửa event STT 2 từ `taoDotKiemKe(maKho, thoiDiem)` → `taoDotKiemKe(thoiDiem)`. |
| **R02** | **Bỏ câu "ghi nhận lịch sử thay đổi" không có bảng lưu** (Q2 – phần 1) | **Bảng 28 (`UC21`), Bảng 34 (`UC27`), Bảng 59 (`UC52`):** Xóa cụm *"và ghi nhận lịch sử thay đổi"* ở Dòng chính & Hậu điều kiện (chỉ giữ ở `UC11` vì có bảng `LichSuCauHinhLoiNhuan`). | `UC21, UC27, UC52` hiện không gọi bảng lịch sử → giữ nguyên luồng lưu cấu hình/nhân viên. | Giữ nguyên (chỉ có 1 lớp lịch sử duy nhất là `LichSuCauHinhLoiNhuan`). | Giữ nguyên 30 bảng. | Giữ nguyên. |
| **R03** | **Lưu đầy đủ lý do từ chối/hủy/khóa & người duyệt/xác nhận + thời điểm** (Q2 – phần 2) | **Bảng 20 (`UC13`), 23 (`UC16`), 26 (`UC19`), 38 (`UC31`), 40 (`UC33`), 44 (`UC37`), 56 (`UC49`):** Giữ rõ yêu cầu ghi nhận lý do, người thực hiện và thời điểm duyệt/xác nhận trên chứng từ. | - **UC13 (Hình 21):** `PhieuNhap.capNhatTrangThai(maPhieuNhap, trangThai, lyDoTuChoi, maNguoiDuyet)`<br>- **UC16 (Hình 24):** `PhieuXuat.capNhatTrangThai(maPhieuXuat, "Đã xuất", maNguoiXacNhan)`<br>- **UC19 (Hình 27):** `BienBanKiemKe.capNhatTrangThai(maBienBan, trangThai, lyDoTuChoi, maNguoiDuyet)`<br>- **UC31 (Hình 39):** `DonHang.capNhatTrangThaiHuy(maDonHang, "Đã hủy", lyDoHuy)`<br>- **UC33 (Hình 41):** `DonHang.capNhatDuyetDon(maDonHang, "Đã duyệt", maNhanVienDuyet)`<br>- **UC37 (Hình 45):** `YeuCauHuyDon.capNhatTrangThai(maYeuCau, "Từ chối", lyDoTuChoi, maNhanVienXuLy)` (`UC36` truyền `lyDoTuChoi = null`)<br>- **UC49 (Hình 57):** `TaiKhoan.capNhatTrangThai(maTaiKhoan, "Bị khóa", lyDoKhoa)` (`UC50` truyền `lyDoKhoa = null`). | Thêm thuộc tính & sửa chữ ký hàm tương ứng:<br>- `PhieuNhap`: thêm `lyDoTuChoi: String`, `ngayDuyet: Date`; thêm Association `NhanVien 0..1 -- 0..* PhieuNhap : duyệt`<br>- `PhieuXuat`: thêm `thoiDiemXacNhan: Date`; thêm Association `NhanVien 0..1 -- 0..* PhieuXuat : xác nhận`<br>- `BienBanKiemKe`: thêm `lyDoTuChoi: String`, `thoiDiemDuyet: Date`; thêm Association `NhanVien 0..1 -- 0..* BienBanKiemKe : duyệt`<br>- `DonHang`: thêm `lyDoHuy: String`, `ngayDuyet: Date`; thêm Association `NhanVien 0..1 -- 0..* DonHang : duyệt`<br>- `YeuCauHuyDon`: thêm `lyDoTuChoi: String`, `ngayXuLy: Date`; thêm Association `NhanVien 0..1 -- 0..* YeuCauHuyDon : xử lý`. | Thêm các cột & 5 khóa ngoại `NULL` tham chiếu `NhanVien(maNhanVien)` vào **Bảng 138, 150, 158, 159, 167** và cập nhật **Bảng 168 + Hình 80, 81**:<br>- `PhieuNhap`: `lyDoTuChoi VARCHAR(255) NULL`, `maNguoiDuyet INT FK NULL`, `ngayDuyet DATETIME NULL`<br>- `PhieuXuat`: `maNguoiXacNhan INT FK NULL`, `thoiDiemXacNhan DATETIME NULL`<br>- `BienBanKiemKe`: `lyDoTuChoi VARCHAR(255) NULL`, `maNguoiDuyet INT FK NULL`, `thoiDiemDuyet DATETIME NULL`<br>- `DonHang`: `lyDoHuy VARCHAR(255) NULL`, `maNhanVienDuyet INT FK NULL`, `ngayDuyet DATETIME NULL`<br>- `YeuCauHuyDon`: `lyDoTuChoi VARCHAR(255) NULL`, `maNhanVienXuLy INT FK NULL`, `ngayXuLy DATETIME NULL`. | Cập nhật cột hiển thị trên Grid và ô nhập `txtLyDoTuChoi` / `txtLyDoKhoa` ở các màn hình: **Phiếu nhập (Bảng 180)**, **Phiếu xuất (Bảng 182)**, **Kiểm kê (Bảng 184)**, **Quản lý đơn hàng (Bảng 198)**, **Yêu cầu hủy đơn (Bảng 200)**, **Quản lý tài khoản (Bảng 212)**. |
| **R04** | **Dọn sạch nhánh `Hủy` giao diện & `Lỗi kết nối CSDL`** (Theo `Lỗi.docx` – Xung đột #1) | **Bảng 8 – Bảng 71:** Xóa mọi dòng sự kiện phụ *"Lỗi kết nối cơ sở dữ liệu"* và dòng *"không xác nhận / chọn Hủy"* thuần giao diện; đánh lại số thứ tự các nhánh phụ nghiệp vụ. | **Hình 9 – Hình 72:** Không vẽ khối `alt Hủy` hay `alt Lỗi kết nối CSDL`. **Bảng 72:** Xóa dòng `"Nhánh lược bỏ"`. | Không sinh hàm rác. | Không ảnh hưởng. | Hộp thoại xác nhận xóa chỉ mô tả ngắn trong cột `Xử lý` của bảng Event, không đẩy thành nhánh SD. |
| **R05** | **Đồng bộ kiểu trả về của 4 hàm đang lệch giữa SD và Class** (Q4) | Không đổi đặc tả. | Giữ nguyên trên SD `UC8, UC13, UC19, UC31, UC32, UC35`. | - **Bảng 98 (`DienThoai.capNhatGiaVonBinhQuan`):** đổi `boolean` → `double`<br>- **Bảng 102 (`DonHang.kiemTraTrangThai`):** đổi `boolean` → `String`<br>- **Bảng 128 (`ThongSoKyThuat.locTheoThongSo`):** đổi `List<ThongSoKyThuat>` → `List<Integer>`<br>- **Bảng 130 (`TonKho.kiemTraTonThieuGiuCho`):** đổi `boolean` → `List<DonHang>`. | Không ảnh hưởng. | Không ảnh hưởng. |
| **R06** | **Snapshot thông tin người nhận vào `DonHang` khi đặt hàng** (Q5) | **Bảng 35 (`UC28`):** Bước lưu đơn ghi rõ *"Hệ thống chụp và lưu kèm họ tên người nhận, số điện thoại, địa chỉ giao hàng tại thời điểm đặt vào đơn hàng"*. | **Hình 36 (`UC28`):** Trước khi lưu đơn, gọi `DiaChiGiaoHang.timTheoMa(maDiaChi) --> diaChiNhan`; sau đó gọi `DonHang.luu(maKhachHang, maDiaChi, tenNguoiNhan, sdtNguoiNhan, diaChiNhan, maVoucher, phiVanChuyen, tongTien, trangThai) --> maDonHang`. | **Bảng 101, 102 (`DonHang`):** Thêm 3 thuộc tính `tenNguoiNhan: String`, `sdtNguoiNhan: String`, `diaChiNhan: String`; cập nhật chữ ký `DonHang.luu(...)` 9 tham số. (Vẫn giữ Association với `DiaChiGiaoHang`). | **Bảng 150 (`DonHang`) & Hình 81:** Thêm 3 cột `NOT NULL`: `tenNguoiNhan VARCHAR(100)`, `sdtNguoiNhan VARCHAR(15)`, `diaChiNhan VARCHAR(255)` (vẫn giữ `maDiaChi INT FK NOT NULL`). Giải trình giữ lại ở **Bảng 3.5.1**. | **Bảng 192 (`Màn hình Đặt hàng`) & Bảng 196 (`Đơn hàng của tôi`):** Hiển thị rõ thông tin người nhận, SĐT, địa chỉ giao trên tóm tắt đơn / chi tiết đơn. |
| **R07** | **Đếm lượt dùng Voucher theo từng khách qua `DonHang` & quản lý `luotDaDung`** (Q6 + Xung đột #4) | **Bảng 35 (`UC28`) & Bảng 37 (`UC30`):** Kiểm tra tổng lượt phát hành (`luotDaDung < soLuong`) và số lượt khách đã dùng trên các đơn chưa hủy (`< gioiHanSuDung`). | - **Hình 38 (`UC30`):** Thêm lifeline `DonHang`; đổi `Voucher.kiemTraLuotSuDung` → gọi `DonHang.demLuotDungVoucher(maKhachHang, maVoucher) --> soLuotDaDung`, rồi mở `alt soLuotDaDung >= gioiHanSuDung`.<br>- **Hình 36 (`UC28`):** Trong `opt Có áp dụng mã giảm giá`, gọi lại `DonHang.demLuotDungVoucher(maKhachHang, maVoucher)` trước khi lưu đơn; sau khi lưu đơn gọi `Voucher.tangLuotDaDung(maVoucher)`.<br>- **Hình 39 (`UC31`) & Hình 44 (`UC36`):** Đổi `Voucher.hoanLuotSuDung(maDonHang)` → `Voucher.hoanLuotSuDung(maVoucher)`. | - **Bảng 102 (`DonHang`):** Thêm `+demLuotDungVoucher(maKhachHang: int, maVoucher: String): int` (ghi rõ chỉ đếm đơn có `trangThai <> "Đã hủy"`).<br>- **Bảng 132 (`Voucher`):** Xóa `kiemTraLuotSuDung` và `ghiNhanLuotSuDung`; thêm `+tangLuotDaDung(maVoucher: String): boolean`; sửa tham số `+hoanLuotSuDung(maVoucher: String): boolean`. | **Bảng 166 (`Voucher`):** Giữ cột `luotDaDung INT NOT NULL DEFAULT 0` (có giải trình tại Bảng 3.5.1). Không thêm bảng phụ mới. | **Bảng 193 (`Đặt hàng`):** Event `btnApDung` gọi `apDungVoucher(maVoucher, maGioHang)`. |
| **R08** | **Bỏ Dependency ở `CanhBaoTonKho` & `BaoCao`** (Q7 + Quy chế thầy cấm Dependency) | Không ảnh hưởng. | Không ảnh hưởng. | **Hình 73, 74, 75, 77 & Bảng 135:**<br>- Xóa toàn bộ **7 dòng Phụ thuộc (`..>`)** (dòng 7, 8, 29, 30, 40, 41, 42 ở Bảng 135).<br>- Thay dòng 29, 30 bằng **2 Association**: `DanhMuc "0..1" -- "0..*" CanhBaoTonKho : áp dụng cho` và `DienThoai "0..1" -- "0..*" CanhBaoTonKho : áp dụng cho`.<br>- `BaoCao` không nối đường nào, đặt ghi chú dưới hình. | **Bảng 139 (`CanhBaoTonKho`), Bảng 168 & Hình 80:** Thêm 2 cột `maDanhMuc INT FK NULL` và `maDienThoai INT FK NULL` (tham chiếu `DanhMuc` và `DienThoai`), giúp bảng `CanhBaoTonKho` hết bị cô lập trên RDM! | **Bảng 188 (`Cấu hình ngưỡng tồn kho`):** Thêm `cboDanhMuc`, `cboDienThoai` (chọn khi `cboPhamVi` là theo danh mục hoặc theo sản phẩm). |
| **R09** | **Loại bỏ 4 thuộc tính dẫn xuất khi ánh xạ từ Class sang RDM** (Mục 3.5.1 – chuẩn 3NF) | Giữ nguyên quy tắc tính ở Mục 3.1.2.1. | Giữ nguyên các hàm tính toán trên Entity (`tinhTonKhaDung`, `tinhChenhLech`, `tinhTongTamTinh`, `tinhLaiTongTien`). | **Giữ nguyên 4 thuộc tính trên Class** (`TonKho.tonKhaDung`, `ChiTietKiemKe.chenhLech`, `GioHang.tongTamTinh`, `GioHang.tongTien`) vì đối tượng trong bộ nhớ vẫn có thuộc tính này. | **Mục 3.5.1:** Lập bảng chọn lọc loại bỏ 4 cột này.<br>**Bảng 143 (`ChiTietKiemKe`), Bảng 151 (`GioHang`), Bảng 165 (`TonKho`) & Hình 80, 81:** Xóa 4 cột `tonKhaDung`, `chenhLech`, `tongTamTinh`, `GioHang.tongTien` khỏi RDM! | Các cột dẫn xuất này vẫn hiển thị dạng `Label` / cột `DataGridView` chỉ đọc trên giao diện. |
| **R10** | **Đồng bộ thuộc tính `hang` (hãng sản xuất) của `DienThoai` ở UC4 & UC5** | **Bảng 11 (`UC4`) & Bảng 12 (`UC5`):** Bước 5 bổ sung *"nhập/chọn hãng sản xuất"*. | **Hình 12 (`UC4`) & Hình 13 (`UC5`) + Phụ lục A:** Đảm bảo chữ ký Controller `luuDienThoai(ten, maDanhMuc, hang, moTa, thongSo, hinhAnh)` & `capNhatDienThoai(maDienThoai, ten, maDanhMuc, hang, moTa, thongSo, hinhAnh)`, Entity `DienThoai.luu(ten, maDanhMuc, hang, moTa, hinhAnh)` & `DienThoai.capNhat(maDienThoai, ten, maDanhMuc, hang, moTa, hinhAnh)`. | **Bảng 97, 98 (`DienThoai`):** Đã có sẵn `hang: String` và tham số `hang` trong `luu`/`capNhat` → giữ nguyên! | **Bảng 148 (`DienThoai`):** Đã có cột `hang` → đổi kiểu thành `VARCHAR(50) NOT NULL`. | **Bảng 172, 173 (`Quản lý điện thoại`):** Thêm `cboHang` (ComboBox chọn hãng) + tách 7 control thông số kỹ thuật; cập nhật tham số `hang` trong event `btnLuu` và `btnCapNhat`. |
| **R11** | **Đồng bộ `GioHang` lưu địa chỉ tạm, phí vận chuyển & bội số với `ThanhToan`** | Không đổi đặc tả. | `UC25` đã gọi `GioHang.ghiNhanDiaChi(maGioHang, maDiaChi)`; `UC26` đã gọi `GioHang.capNhatPhiVanChuyen(maGioHang, phiVanChuyen)` → **cập nhật Phụ lục A** cho khớp! | - **Bảng 103 (`GioHang`):** Thêm thuộc tính `phiVanChuyen: double`.<br>- **Bảng 135 & Hình 73, 76:** Thêm Association `DiaChiGiaoHang "0..1" -- "0..*" GioHang : giao tạm đến`; sửa bội số dòng 12 từ `GioHang 1 – 0..1 ThanhToan` thành **`GioHang 1 – 0..* ThanhToan`** (1 giỏ hàng của khách dùng qua nhiều lần thanh toán). | - **Bảng 151 (`GioHang`) & Hình 81:** Thêm `maDiaChi INT FK NULL` (→ `DiaChiGiaoHang`) và `phiVanChuyen DECIMAL(15,2) NOT NULL DEFAULT 0`.<br>- **Bảng 168:** Thêm FK `GioHang.maDiaChi` và sửa bội số `ThanhToan.maGioHang` thành `1 – 0..*`. | Giữ nguyên trên Màn hình Đặt hàng. |
| **R12** | **Chuyển `kiemTraDangNhap(maPhien)` từ `GioHang` về đúng trách nhiệm của `PhienDangNhap`** | Không đổi đặc tả `UC28`. | **Hình 36 (`UC28`) & Phụ lục A:** Thêm lifeline `PhienDangNhap` (hoặc thay chỗ kiểm tra đầu SD): gọi `PhienDangNhap.kiemTraDangNhap(maPhien)` thay vì `GioHang.kiemTraDangNhap(maPhien)`. | - **Bảng 104 (`GioHang`):** Xóa phương thức `kiemTraDangNhap(maPhien)`.<br>- **Bảng 114 (`PhienDangNhap`):** Thêm `+kiemTraDangNhap(maPhien: String): boolean`. | Không ảnh hưởng. | Không ảnh hưởng. |
| **R13** | **Bổ sung truy vấn Entity kiểm tra trùng tên (`UC1, UC2, UC4, UC5`) & truy vấn thiếu trước `alt`/`loop` (`UC10, UC11, UC23`)** | Không đổi đặc tả. | - **UC1, UC2 (Hình 9, 10):** Gọi `DanhMuc.kiemTraHopLe(ten)` rồi `DanhMuc.kiemTraTrungTen(ten, maDanhMuc)` trước `alt`.<br>- **UC4, UC5 (Hình 12, 13):** Gọi `DienThoai.kiemTraTrungTen(ten, maDienThoai)` trước `alt`.<br>- **UC10 (Hình 18):** Gọi `DienThoai.timTheoMa(maDienThoai)` lấy `maDanhMuc` trước khi gọi `DanhMuc.kiemTraTonTai(maDanhMuc)`.<br>- **UC11 (Hình 19):** Thêm `DienThoai.timTheoPhamVi(phamVi) --> danhSachDienThoai` trước `loop`.<br>- **UC23 (Hình 31):** Sửa biến có dấu `maChiTietGioHàng` → `maChiTietGioHang`; thêm `ChiTietGioHang.timTheoMa(maChiTietGioHang) --> chiTiet` trước khi gọi `TonKho.tinhTonKhaDung(maDienThoai)`. | - **Bảng 94 (`DanhMuc`):** Thêm `+kiemTraTrungTen(ten: String, maDanhMuc: int): boolean`.<br>- **Bảng 98 (`DienThoai`):** Thêm `+kiemTraTrungTen(ten: String, maDienThoai: int): boolean` và `+timTheoPhamVi(phamVi: String): List<DienThoai>`.<br>- **Bảng 86 (`ChiTietGioHang`):** Thêm `+timTheoMa(maChiTietGioHang: int): ChiTietGioHang`. | **Bảng 146 (`DanhMuc`) & Bảng 148 (`DienThoai`):** Cột `ten` có ràng buộc `NOT NULL, UNIQUE`. | Không ảnh hưởng. |
| **R14** | **Đặt hàm kiểm tra ra TRƯỚC khung `alt` ở `UC24, UC30, UC34, UC37`** | Không đổi đặc tả. | **Hình 32 (`UC24`), Hình 38 (`UC30`), Hình 42 (`UC34`), Hình 45 (`UC37`):** Đưa các lời gọi `ChiTietGioHang.demTheoGioHang`, `DonHang.demLuotDungVoucher`, `DieuKienApDung.kiemTraDieuKien`, `PhieuXuat.kiemTraDaXacNhan` ra **ngay trước** khung `alt` tương ứng; dùng `alt` lồng nhau cho nhiều điều kiện liên tiếp. | Không đổi chữ ký hàm. | Không ảnh hưởng. | Không ảnh hưởng. |
| **R15** | **Đồng bộ `DieuKienApDung` giữa SD `UC57, UC58, UC60, UC61` và Class** | Không đổi đặc tả. | - **Hình 66 (`UC58`):** Khi cập nhật khuyến mãi, gọi `DieuKienApDung.capNhat(maKhuyenMai, giaTriToiThieu, nhomHang, doiTuong)` (thay vì gọi nhầm `luu`), và khi nạp form sửa `UC58` gọi `DieuKienApDung.timTheoKhuyenMai(maKhuyenMai)`. | **Bảng 100 (`DieuKienApDung`):** Giữ đủ 6 hàm (nay đã có SD gọi 100%!); sửa kiểu trả về của `timTheoVoucher(maVoucher: String)` từ `List<DieuKienApDung>` thành **`DieuKienApDung`** (khớp bội số `0..1 – 0..1`). | Giữ nguyên **Bảng 149 (`DieuKienApDung`)**. | Giữ nguyên. |
| **R16** | **Tách `UC42 Quên mật khẩu` độc lập & gọn `UC41 Đăng xuất`** (Theo `Lỗi.docx`) | **Hình 6, Bảng 47 (`UC40`), Bảng 48 (`UC41`), Bảng 49 (`UC42`):** Bỏ `<<extend>>` giữa `UC42` và `UC40`; `UC42` nối thẳng `Người dùng`. `UC41` kết thúc ngay khi hủy phiên. | **Hình 49 (`UC41`):** Sau `PhienDangNhap.huyPhien(maPhien)` → trả kết quả đăng xuất thành công về Boundary/Actor và **kết thúc ngay** (không vẽ nối luồng đăng nhập lại). | Không ảnh hưởng. | Không ảnh hưởng. | **Bảng 206 (`Đăng nhập`) & Bảng 208 (`Tài khoản của tôi`):** Chụp thêm ảnh tab/form Quên mật khẩu; ghi chú `btnDangXuat` nằm trên thanh điều hướng dùng chung cho cả 4 vai trò. |

---

#### 1.3.3. Quy chuẩn đặt tên thống nhất toàn báo cáo

1. **Tên 5 Actor (viết đúng từng chữ hoa/thường và dấu gạch chéo):**
   - `Người dùng` *(Actor tổng quát)*
   - `Khách hàng`
   - `Nhân viên Bán hàng / CSKH`
   - `Nhân viên Kho`
   - `Ban Quản lý`
2. **Định danh Mã Use Case & Tiêu đề:**
   - Mã UC viết liền không số 0 ở đầu cho UC1–UC9 trong văn bản & bảng: `UC1` đến `UC64` (tránh chỗ ghi `UC01`, chỗ ghi `UC1`).
   - Tiêu đề Bảng đặc tả: `Bảng <số>. Đặc tả UC<số> – <Tên Use Case>`.
   - Tiêu đề Sequence Diagram: `UC<số> - <Tên Use Case>` (trên ảnh) và caption Word: `Hình <số>. Sequence Diagram UC<số> – <Tên Use Case>`.
3. **Định danh Lớp, Phương thức, Tham số, Biến trả về:**
   - **Boundary:** `"Màn hình <Tên màn hình khớp chính xác 100% Bảng 73 & Bảng 169>"` (28 màn hình).
   - **Controller (18 lớp):** PascalCase kết thúc bằng `Ctrl` (`QuanLyDanhMucCtrl`, `QuanLyDienThoaiCtrl`, `TimKiemSanPhamCtrl`, `ChinhSachGiaCtrl`, `PhieuNhapCtrl`, `PhieuXuatCtrl`, `KiemKeCtrl`, `GioHangCtrl`, `DatHangCtrl`, `ApDungVoucherCtrl`, `HuyDonHangCtrl`, `QuanLyDonHangCtrl`, `XacThucCtrl`, `HoSoKhachHangCtrl`, `QuanLyTaiKhoanCtrl`, `ThongKeBaoCaoCtrl`, `QuanLyKhuyenMaiCtrl`, `QuanLyVoucherCtrl`).
   - **Entity (30 lớp):** PascalCase tiếng Việt không dấu (`BienBanKiemKe`, ..., `YeuCauHuyDon`, `BaoCao`).
   - **Phương thức & Tham số:** camelCase tiếng Việt không dấu (`maDanhMuc`, `maDienThoai`, `trangThai`, `lyDoTuChoi`...). Tuyệt đối không còn ký tự tiếng Việt có dấu trong tên biến (như lỗi `maChiTietGioHàng` ở Hình 31) và không dùng từ tiếng Anh (`id`, `status`, `userId`).
4. **Quy chuẩn Kiểu dữ liệu Class vs. Kiểu dữ liệu SQL trong RDM:**
   - **Trong Class Diagram (`Mục 3.4`):** Dùng kiểu OOP chuẩn: `int`, `double`, `boolean`, `String`, `Date`, `List<T>`, `Map<K, V>`.
   - **Trong RDM (`Mục 3.5` – tiêu đề bảng bắt buộc: `STT | Tên cột | Kiểu dữ liệu | Ràng buộc toàn vẹn (RBTV) | Mô tả`):**
     - Khóa chính số nguyên tự tăng: `INT` | RBTV: `PK, AUTO_INCREMENT`
     - Khóa ngoại số nguyên: `INT` | RBTV: `FK, NOT NULL` (hoặc `FK, NULL` cho quan hệ `0..1`)
     - Mã chuỗi (`maVoucher`, `maPhien`): `VARCHAR(30)` / `VARCHAR(64)` | RBTV: `PK`
     - Tên danh mục, họ tên, tên sản phẩm, nguồn nhập, khu vực: `VARCHAR(100)`
     - Hãng sản xuất: `VARCHAR(50)` | Số điện thoại: `VARCHAR(15)` | Mã OTP: `VARCHAR(10)`
     - Trạng thái, vai trò, loại giảm, phương thức, phạm vi, khung giờ: `VARCHAR(30)` / `VARCHAR(50)`
     - Mô tả, địa chỉ, nội dung, lý do, ghi chú, đường dẫn ảnh, mật khẩu băm: `VARCHAR(255)` (riêng `DienThoai.moTa`, `KhuyenMai.noiDung` có thể dùng `VARCHAR(500)` hoặc `TEXT`)
     - Tiền tệ (`giaBan`, `giaVonBinhQuan`, `tongTien`, `phiVanChuyen`, `donGia`...): `DECIMAL(15,2)`
     - Tỷ lệ phần trăm (`tyLe`): `DECIMAL(5,2)`
     - Ngày không giờ (`ngayApDung`, `ngayBatDau`, `ngayKetThuc`, `ngayNhap`, `ngayLap`): `DATE`
     - Thời điểm có giờ (`ngayDat`, `thoiDiem`, `thoiDiemTao`, `thoiDiemHetHan`, `hanMaXacThuc`, `ngayDuyet`, `thoiDiemXacNhan`, `thoiDiemDuyet`, `ngayGui`, `ngayXuLy`): `DATETIME`
     - Cờ logic (`macDinh`, `daDoc`): `TINYINT(1)`
5. **Quy chuẩn Tiền tố Tên Control Giao diện (`Mục 3.6`):**
   - `btn` (Button), `txt` (TextBox / TextArea), `cbo` (ComboBox), `dtp` (DatePicker), `num` (NumericBox), `chk` (CheckBox), `rdo` (RadioButton), `pwd` (PasswordBox), `upl` (Upload), `lbl` (Label), `grd` (DataGridView).
   - Sau tiền tố là danh từ/động từ **PascalCase tiếng Việt không dấu, KHÔNG gắn số thứ tự ở đuôi** (ví dụ: `btnLuu`, `btnCapNhat`, `grdDanhMuc` — xem bảng đổi tên đủ 78 control tại cuối tài liệu này).

---

## PHẦN 2: CHIA CÁC BƯỚC SỬA CHI TIẾT CHO 5 THÀNH VIÊN (LÀM SONG SONG)

Bảng phân chia tổng quan 5 Gói công việc độc lập (cân đối khối lượng ~20%/người, không chồng lấn trang/file):

| Gói việc | Thành viên đề xuất (theo Bảng 229) | Khối nội dung phụ trách độc lập | Trang hiện tại | Đầu ra bàn giao để ghép |
| :-: | :--- | :--- | :---: | :--- |
| **GÓI 1** | **TV1 – Nguyễn Anh Sỹ** (`3124410306`) | Bìa, Lời cảm ơn/Lời mở đầu, Chương 1, Chương 2 (`Hình 1 BFD`), Sơ đồ Use Case (`Hình 2–8`) & 64 Bảng đặc tả (`Bảng 8–71`) | Bìa → tr. 56 | Phần Word `01_MoDau_Ch1_Ch2_UC31_32.docx` + ảnh `Hình 1 – Hình 8` nền trắng |
| **GÓI 2** | **TV2 – Quách Thanh Phú** (`3124410278`) | `Bảng 72–73` + **32 Sequence Diagram nửa đầu (`UC1 – UC32`, `Hình 9 – Hình 40`)** + rà soát hàm nửa đầu Phụ lục A | tr. 57 → tr. 92 | Phần Word `02_SD_UC01_UC32.docx` + 32 file `.puml` & `.png` (`UC01–UC32`) |
| **GÓI 3** | **TV3 – Phạm Duy Phương** (`3124410290`) | **32 Sequence Diagram nửa sau (`UC33 – UC64`, `Hình 41 – Hình 72`)** + tổng hợp **Phụ lục A (`tr. 206–217`)** | tr. 93 → tr. 124 & tr. 206–217 | Phần Word `03_SD_UC33_UC64_PhuLucA.docx` + 32 file `.puml` & `.png` (`UC33–UC64`) |
| **GÓI 4** | **TV4 – Hoàng Trần Gia Phước** (`3124410289`) | **Class Diagram (`Mục 3.4`, `Hình 73–77`, `Bảng 74–135`)** + **RDM (`Mục 3.5`, `Hình 78–82`, `Bảng 136–168`)** + kiểm tra khớp `database/01_schema.sql` & code Demo | tr. 124 → tr. 170 | Phần Word `04_Class_RDM_34_35.docx` + file nguồn & ảnh `Hình 73–82` + `01_schema.sql` chuẩn |
| **GÓI 5** | **TV5 – Dương Thành Phát** (`3124410261`) | **Thiết kế Giao diện 28 màn hình (`Mục 3.6`, `Hình 83–110`, `Bảng 169–225`)** + **Chương 4 Demo & Test (`Bảng 226–228`)** + **Chương 5, TLTK, Phụ lục B (`Bảng 229`)** | tr. 171 → tr. 205 & tr. 218 | Phần Word `05_UI_Ch4_Ch5_TLTK_PhuLucB.docx` + bộ ảnh chụp UI sáng & ảnh Demo |

*(Lưu ý: Nếu nhóm muốn đổi người nhận giữa các Gói 1–5 thì đổi tên người, nhưng **giữ nguyên ranh giới 5 Gói việc** để không đụng file nhau).*

---

### GÓI VIỆC 1 – THÀNH VIÊN 1: HÌNH THỨC ĐẦU QUYỂN, CHƯƠNG 1–2, BFD, SƠ ĐỒ USE CASE & 64 ĐẶC TẢ

#### 1. Phạm vi phụ trách chính xác
- **Các mục văn bản:** Trang bìa chính, Trang bìa phụ (bìa lót), Lời cảm ơn, Lời mở đầu, Danh mục từ viết tắt, Chương 1 (`Mục 1.1 – 1.6`, tr. 1–3), Chương 2 (`Mục 2.1 – 2.5`, tr. 4–7), Chương 3 phần Use Case (`Mục 3.1 – 3.2.6`, tr. 8–56).
- **Các Hình vẽ (8 hình):** `Hình 1` (Sơ đồ BFD), `Hình 2` (UC tổng quát), `Hình 3` (UC sản phẩm), `Hình 4` (UC kho hàng), `Hình 5` (UC bán hàng & đơn hàng), `Hình 6` (UC khách hàng & tài khoản), `Hình 7` (UC báo cáo), `Hình 8` (UC khuyến mãi).
- **Các Bảng biểu (71 bảng + 2 bảng mới ở Ch.2):** `Bảng 1 – Bảng 7` và **`Bảng 8 – Bảng 71` (64 bảng đặc tả `UC1 – UC64`)**.

#### 2. Hình dung kết quả đầu ra của bước này
Bàn giao 56 trang đầu của quyển báo cáo đạt chuẩn Khóa luận tốt nghiệp: BFD nền trắng rõ nét khớp tên UC; Chương 2 có mô tả 5 quy trình nghiệp vụ thực tế và 2 bảng `FR`/`NFR` phủ đủ 64 UC; `Hình 2` là UC tổng quát đúng nghĩa (chỉ 6 nhóm chức năng + 3 UC chung, Actor bên trái); `Hình 3–8` không cắt đè chữ, `UC42 Quên mật khẩu` đứng độc lập; cả 64 bảng đặc tả có đủ dòng `Tác nhân`, `Quan hệ`, căn lề Justify và sạch bóng lỗi kết nối CSDL.

#### 3. Checklist sửa chữa chi tiết đến từng dòng / hình / bảng

**A. Phần Mở đầu & Chương 1 – 2:**
- [ ] **Trang bìa chính & Bìa phụ (đầu quyển)** → Đang thiếu logo trường, thiếu khung viền KLTN, ghi cụt `"Giảng viên: Hoàng Mạnh Hà"`, bảng thành viên chữ nhỏ → Thiết kế khung viền đôi chuẩn KLTN ĐH Sài Gòn, chèn Logo Trường ĐH Sài Gòn ở trên tên trường, đổi thành `"Giảng viên hướng dẫn: ThS. Hoàng Mạnh Hà"` *(kiểm tra đúng học vị thầy trên đề cương môn học)*, thêm dòng `Mã lớp học phần: ...`, phóng cỡ chữ bảng 5 thành viên lên `13pt`, tạo thêm 1 trang Bìa phụ (bìa lót giấy thường in giống bìa chính).
- [ ] **Lời nói đầu (tr. ii)** → Đang gộp chung cảm ơn và giới thiệu, đồng thời lộ câu nội bộ *"Báo cáo là bản tổng hợp các sản phẩm nhóm đã hoàn thành qua từng tuần (Lần 01 đến Lần 04)..."* → Tách thành **2 trang riêng**: trang `LỜI CẢM ƠN` và trang `LỜI MỞ ĐẦU`; **xóa bỏ hoàn toàn** câu *"Lần 01 đến Lần 04"*.
- [ ] **Mục 1.1 – Lý do chọn đề tài (tr. 1)** → Mới viết lý thuyết chung chung → Bổ sung 1 đoạn ngắn (4–5 dòng) nêu thực trạng khảo sát tại các cửa hàng bán lẻ điện thoại quy mô vừa (quản lý 100–300 mẫu máy, bán đa kênh tại quầy và trực tuyến, dễ xảy ra tình trạng 2 khách cùng đặt 1 máy cuối cùng dẫn đến bán âm kho).
- [ ] **Mục 1.2 – Mục tiêu của đề tài (tr. 1)** → Đang ghi *"đồng thời nghiên cứu, mô hình hóa quy trình bảo hành/đổi trả"* dễ bị hiểu nhầm là có thiết kế UC/Class bảo hành → Sửa thành: *"đồng thời bước đầu khảo sát nghiệp vụ bảo hành/đổi trả ở mức định hướng mở rộng (chưa đưa vào phạm vi thiết kế chi tiết và cài đặt của đồ án)"*.
- [ ] **Mục 1.3 – Phạm vi và giới hạn đề tài (tr. 1)** → Chưa nói rõ cách quản lý tồn kho → Thêm 1 gạch đầu dòng rõ ràng: *"Hệ thống hoạt động theo mô hình 01 kho trung tâm của cửa hàng, quản lý tồn kho theo số lượng từng mẫu điện thoại (`maDienThoai`), chưa quản lý định danh từng máy lẻ theo mã IMEI/Serial (nội dung này được định hướng ở Chương 5)."*
- [ ] **Hình 1 – Sơ đồ phân rã chức năng BFD (tr. 4)** → Đang nền đen, chữ trắng nhỏ khó đọc khi in đen trắng; nhánh Kho hàng chỉ có 4 lá gộp chung khác tên UC → Vẽ lại trên draw.io/PlantUML **nền trắng 100%, khung viền xanh đậm/đen, chữ ≥ 9pt**; tách các lá của 6 nhánh cốt lõi khớp tên gọi nhóm UC ở Chương 3 (đặc biệt nhánh 2 Kho hàng tách rõ: *Thiết lập % lợi nhuận; Lập/Duyệt/Tra cứu phiếu nhập; Lập/Xác nhận/Tra cứu phiếu xuất; Kiểm kê/Duyệt biên bản kiểm kê; Xem cảnh báo/Cấu hình ngưỡng tồn kho*).
- [ ] **Mục 2.2 (tr. 4–5) – Thêm mục mới "Mô tả hoạt động nghiệp vụ của hệ thống"** → Quy chế `261_BaoCaoCuoiKi.docx` bắt buộc *"Mô tả các hoạt động nghiệp vụ của hệ thống, từ đó xác định yêu cầu"* nhưng hiện mới chỉ gạch đầu dòng theo vai trò → Chèn tiểu mục mới ngay trước phân tích vai trò, trình bày **5 quy trình nghiệp vụ cốt lõi** (mỗi quy trình 5–7 bước đánh số rõ ai thực hiện, chứng từ vào/ra): (1) Quy trình mua hàng & đặt hàng trực tuyến (có giữ chỗ tồn kho), (2) Quy trình nhập kho & tính lại giá vốn bình quân/giá bán, (3) Quy trình xuất kho & giao hàng, (4) Quy trình kiểm kê & điều chỉnh tồn kho, (5) Quy trình hủy đơn, hoàn tiền & áp dụng ưu đãi.
- [ ] **Cuối Mục 2.2.4 (tr. 5) & Bảng 2 (tr. 7)** → Lỗi chính tả dấu câu → Thêm dấu chấm cuối câu *"thêm, sửa, xóa điện thoại)."* ở cuối 2.2.4; trong **Bảng 2** dòng 1 sửa `"kinh doanh,quản lý"` → `"kinh doanh; quản lý"`, dòng 4 sửa `"trạng thái đơn:gửi"` → `"trạng thái đơn; gửi"`.
- [ ] **Mục 2.3.2 – Yêu cầu chức năng (tr. 6)** → Đang viết văn xuôi và **bỏ sót 5 chức năng** (`UC10 Khôi phục SP ngừng kinh doanh`, `UC27 Cấu hình quy tắc phí vận chuyển`, `UC38 Yêu cầu hoàn tiền`, `UC47 Tra cứu thông tin & lịch sử mua của KH`, `UC51–52 Tạo/Cập nhật tài khoản nhân viên`) → Thay danh sách gạch đầu dòng bằng **Bảng Yêu cầu chức năng (`Mã FR | Phân hệ | Yêu cầu chức năng | Actor thực hiện | Truy vết Use Case`)** gồm khoảng 22 dòng (`FR01` – `FR22`), cột cuối liệt kê phủ kín không sót một UC nào từ `UC1` đến `UC64` (kèm 2 dòng cuối ghi chú phân hệ Hạ tầng & Bảo hành chỉ dừng ở mức khảo sát định hướng).
- [ ] **Mục 2.3.3 – Yêu cầu phi chức năng (tr. 6)** → Đang viết 3 đoạn chung chung không đo kiểm được → Thay bằng **Bảng Yêu cầu phi chức năng (`Mã NFR | Nhóm tiêu chí | Nội dung yêu cầu định lượng cụ thể`)** gồm 8 dòng (`NFR01` Hiệu năng phản hồi ≤ 2 giây với 1.000 SP; `NFR02` Tải đồng thời ≥ 50 phiên; `NFR03` Mật khẩu băm `bcrypt` & phân quyền RBAC 4 vai trò; `NFR04` Phiên đăng nhập hết hạn sau 30 phút không thao tác, mã xác thực OTP hết hạn sau 5 phút; `NFR05` Toàn vẹn giao dịch ACID khi đặt hàng & giữ chỗ tồn kho không bán âm; `NFR06` Sao lưu CSDL định kỳ hằng ngày; `NFR07` Khả dụng responsive từ màn hình `360px` đến `1920px` trên Chrome/Edge; `NFR08` Kiến trúc mã nguồn phân tầng BCE đồng bộ tên lớp/hàm với thiết kế).

**B. Sơ đồ Use Case (`Hình 2 – Hình 8`, tr. 11–51):**
- [ ] **Hình 2 – Biểu đồ Use Case tổng quát (tr. 11)** → Đang nhồi chi tiết, khác font Hình 3–8, mũi tên kế thừa đè chữ Actor `Người dùng` → Vẽ lại cùng style PlantUML/draw.io nền trắng với Hình 3–8: bên trái khung System Boundary đặt Actor cha `Người dùng` và 4 Actor con kế thừa (`Khách hàng`, `Nhân viên Bán hàng / CSKH`, `Nhân viên Kho`, `Ban Quản lý`); bên trong khung System Boundary **chỉ vẽ 6 Use Case mức nhóm phân hệ** (`Quản lý sản phẩm`, `Quản lý kho hàng`, `Quản lý bán hàng và đơn hàng`, `Quản lý khách hàng và tài khoản`, `Quản lý báo cáo`, `Quản lý khuyến mãi và mã giảm giá`) + 3 UC chung nối với `Người dùng` (`Đăng nhập`, `Đăng xuất`, `Quên mật khẩu`) — tuân thủ tuyệt đối `Lỗi.docx` dòng 8, 12, 50!
- [ ] **Hình 3, 4, 7, 8 (tr. 12, 18, 48, 51)** → Rà soát khung System Boundary, font chữ đồng nhất, mọi oval UC khớp chính xác từng chữ với tiêu đề Bảng đặc tả bên dưới (`UC1–UC10` trên Hình 3; `UC11–UC21` trên Hình 4; `UC53–UC56` trên Hình 7; `UC57–UC64` trên Hình 8). Kiểm tra `Hình 4`: nếu có vẽ `UC13 <<include>> UC14` mà trong SD `UC13` không có khung `ref UC14` thì bỏ mũi tên `<<include>>` đó đi để khớp 100% với SD!
- [ ] **Hình 5 – Use Case quản lý bán hàng & đơn hàng (tr. 28)** → Nhãn `<<include>>` / `<<extend>>` đang đè chồng lên nhau → Giãn khoảng cách giữa các oval, kiểm tra chiều mũi tên nét đứt: `UC28 (Đặt hàng)` trỏ `<<include>>` tới `UC25`, `UC26`, `UC29`; `UC30 (Áp dụng mã giảm giá)` trỏ `<<extend>>` về `UC28`; `UC38 (Yêu cầu hoàn tiền)` trỏ `<<extend>>` về `UC36 (Xác nhận hủy đơn)`.
- [ ] **Hình 6 – Use Case quản lý khách hàng & tài khoản (tr. 40)** → Đường kế thừa cắt ngang đường nối; `UC42 Quên mật khẩu` vừa `<<extend>>` vào `UC40 Đăng nhập` vừa nối Actor (vi phạm `Lỗi.docx` dòng 4!) → Chỉnh lại bố cục không cắt dây; **xóa mũi tên `<<extend>>` giữa `UC42` và `UC40`**, chỉ giữ đường nối trực tiếp từ Actor `Người dùng` tới `UC42 Quên mật khẩu`.

**C. 64 Bảng Đặc tả Use Case (`Bảng 8 – Bảng 71`, tr. 12–56):**
- [ ] **Cấu trúc chung cả 64 bảng (`Bảng 8 – Bảng 71`)** → Hiện thiếu dòng `Tác nhân (Actor)`, thiếu dòng `Quan hệ`, chưa căn đều 2 bên (Justify) → Chèn thêm 2 hàng ngay dưới dòng `UseCase ID: UCxx — <Tên UC>`:
  - Hàng **`Tác nhân (Actor)`**: ghi đúng tên Actor theo `Bảng 4` (riêng `UC40, UC41, UC42` ghi `Người dùng (Khách hàng, Nhân viên Bán hàng / CSKH, Nhân viên Kho, Ban Quản lý)`).
  - Hàng **`Quan hệ`**: ghi rõ ở `UC28` (*"Include: UC25, UC26, UC29; Được mở rộng bởi (Extend): UC30"*), `UC25/26/29` (*"Được gọi bởi (Include): UC28"*), `UC30` (*"Extend: UC28"*), `UC36` (*"Được mở rộng bởi (Extend): UC38"*), `UC38` (*"Extend: UC36"*); toàn bộ các UC còn lại ghi `"Không có"`.
  - Bôi toàn bộ nội dung 64 bảng chọn căn lề **Justify (Ctrl+J)**, font Times New Roman cỡ `11pt` hoặc `12pt` đồng bộ.
- [ ] **Dọn sạch lỗi kỹ thuật & nút Hủy giao diện trên cả 64 bảng (`Bảng 8 – Bảng 71` – theo `Lỗi.docx` dòng 24–25)** → Đang có dòng *"Lỗi kết nối cơ sở dữ liệu..."* và *"Người dùng hủy thao tác / không xác nhận..."* ở hầu hết 64 bảng → **Xóa toàn bộ** các dòng sự kiện phụ về lỗi kết nối CSDL và thao tác bấm Hủy giao diện đơn thuần; xóa câu *"Hệ thống kết nối được với cơ sở dữ liệu"* trong ô Tiền điều kiện; đánh lại số thứ tự các nhánh sự kiện phụ nghiệp vụ (`3.1`, `5.1`, `6.1`...) bám sát đúng số thứ tự bước kiểm tra tương ứng ở `Dòng sự kiện chính`.
- [ ] **Bảng 8, 9 (`UC1, UC2` – tr. 12–13) & Bảng 11, 12 (`UC4, UC5` – tr. 14–15)** → Đảm bảo ở `Dòng sự kiện chính` bước kiểm tra ghi rõ *"Hệ thống kiểm tra tính hợp lệ và kiểm tra không trùng tên..."* để có cơ sở logic cho nhánh phụ *"Tên danh mục / Tên điện thoại đã tồn tại"* (`Lỗi.docx` dòng 23). Riêng **Bảng 11 (`UC4`) & Bảng 12 (`UC5`)** bước 5 thêm từ **"hãng sản xuất"** (*"nhập tên điện thoại, chọn danh mục, hãng sản xuất, mô tả, thông số kỹ thuật và tải hình ảnh"* — khớp R10).
- [ ] **Bảng 25 (`UC18 – Kiểm kê tồn kho`, tr. 24)** → Theo R01 (bỏ `maKho`): xóa mọi từ ngữ nhắc đến *"chọn kho / mã kho"*, chỉ ghi *"Nhân viên Kho chọn tạo đợt kiểm kê tại thời điểm hiện tại"*.
- [ ] **Bảng 28 (`UC21`, tr. 26), Bảng 34 (`UC27`, tr. 31), Bảng 59 (`UC52`, tr. 48)** → Theo R02: xóa cụm *"và ghi nhận lịch sử thay đổi"* ở cả `Dòng sự kiện chính` và `Hậu điều kiện`.
- [ ] **Bảng 35 (`UC28 – Đặt hàng`, tr. 31)** → Theo R06 & R07: tại bước kiểm tra trước khi lưu đơn, ghi rõ *"nếu có áp dụng mã giảm giá, hệ thống kiểm tra lại giới hạn lượt dùng của khách hàng trên các đơn chưa hủy"*; tại bước tạo đơn hàng, ghi rõ *"Hệ thống lưu đơn hàng mới kèm bản chụp thông tin người nhận (họ tên, số điện thoại, địa chỉ giao hàng) tại thời điểm đặt"*.
- [ ] **Bảng 46 (`UC39 – Đăng ký`, tr. 40) & Bảng 47 (`UC40 – Đăng nhập`, tr. 41)** → Bổ sung dòng sự kiện phụ nghiệp vụ: *"Nếu phiên truy cập hiện tại đang có giỏ hàng tạm, hệ thống tự động gộp các sản phẩm từ giỏ hàng tạm vào giỏ hàng của tài khoản khách hàng"* (khớp khối `opt` gọi `GioHang.gopGioHangTam` trên Hình 47, 48). Đồng thời ở **Bảng 47 (`UC40`)** xóa nhánh phụ điều hướng sang Quên mật khẩu (theo Xung đột #2).

#### 4. Điểm bắt buộc khớp với Bước 0 (Sync Guardrails)
- Đếm tổng số Use Case trong `Bảng 4` = tổng số oval chi tiết trên `Hình 3–8` = tổng số bảng đặc tả (`Bảng 8–71`) = tổng số UC truy vết trong bảng `FR` ở `Mục 2.3.2` = **đúng 64 Use Case**.
- Tên của cả 64 Use Case không được tự ý sửa khác với `Bảng 73` để TV2 & TV3 giữ nguyên tiêu đề 64 Sequence Diagram.
- Không còn bất kỳ dòng `"Lỗi kết nối cơ sở dữ liệu"` nào trong `Bảng 8 – Bảng 71`.

---

### GÓI VIỆC 2 – THÀNH VIÊN 2: BẢNG QUY ƯỚC BCE & 32 SEQUENCE DIAGRAM NỬA ĐẦU (`UC1 – UC32`)

#### 1. Phạm vi phụ trách chính xác
- **Các mục văn bản & bảng:** `Mục 3.3.1 – 3.3.5.11` (tr. 57–92), gồm `Bảng 72` (Quy ước xây dựng SD) và `Bảng 73` (Đối chiếu UC – Boundary – Controller).
- **Các Hình vẽ & file nguồn `.puml` (32 sơ đồ):** `Hình 9 – Hình 40` tương ứng **`UC1 – UC32`**:
  - Cụm Quản lý sản phẩm (`UC1 – UC10`, `Hình 9 – 18`) — **Cụm trọng tâm chấm Demo Phần B (2,0 điểm)!**
  - Cụm Quản lý kho hàng (`UC11 – UC21`, `Hình 19 – 29`)
  - Cụm Mua hàng, Giỏ hàng, Đặt hàng, Thanh toán, Mã giảm giá & Khách tự hủy/gửi yêu cầu hủy (`UC22 – UC32`, `Hình 30 – 40`) — **Gồm `UC28 Đặt hàng` chấm Demo Phần A (1,0 điểm)!**
- **Phối hợp Phụ lục A:** Xuất danh sách chính xác mọi lời gọi hàm Controller & Entity của `UC1 – UC32` gửi cho TV3 ghép vào Phụ lục A.

#### 2. Hình dung kết quả đầu ra của bước này
32 Sequence Diagram đầu tiên (`Hình 9 – 40`) sạch 100% dấu `X` trên Actor/Boundary, không có nhánh `Hủy`/`Lỗi CSDL` dư thừa, mọi khối `alt` đều có bước gọi Entity kiểm tra đứng ngay trước, không có nhãn đè lên thanh activation, các hình dài (`UC12, UC16, UC18, UC28`) dùng `ref`/rút gọn bước mở trang để nằm trọn vẹn trên 1 trang A4 dọc với cỡ chữ rõ nét `≥ 8pt`; đặc biệt cụm `UC1–UC10` khớp từng tên hàm và tham số với code PHP của nhóm.

#### 3. Checklist sửa chữa chi tiết đến từng dòng / hình / bảng

**A. Bảng 72 & Bảng 73 (tr. 57–60):**
- [ ] **Bảng 72 – Quy ước xây dựng Sequence Diagram (tr. 57)** → Đang lộ hàng loạt ghi chú prompt nội bộ (*"Quy tắc 2.1"*, *"từ điển Quy tắc 3"*, *"@startuml UC<số 2 chữ số>"*, dòng *"Tên file .puml/.png"*, dòng *"Nhánh lược bỏ"*, và câu *"cuối sơ đồ có dấu X ở Actor và Boundary"*) → Sửa sạch chuyên nghiệp:
  - Dòng `Công cụ, bố cục`: *"Vẽ bằng công cụ UML (PlantUML) theo đúng ký hiệu UML 2.5, nền trắng, hiển thị đầy đủ thanh kích hoạt (Activation bar)."* (Xóa *"Quy tắc 2.1"*, *"@startuml..."*).
  - Dòng `Khối kết hợp`: *"Dùng `alt/else` ngay sau thông điệp kiểm tra cho các nhánh rẽ điều kiện nghiệp vụ; `opt` cho bước tùy chọn; `loop` kèm điều kiện lặp rõ ràng; `ref` để tham chiếu Use Case liên kết (`include`/`extend`)."*
  - **Xóa hẳn 3 dòng:** dòng `Nhánh lược bỏ`, dòng `Tên Controller, Entity` (*"từ điển Quy tắc 3"*), và dòng `Tên file` (`.puml/.png`).
  - Dòng `Activation, kết thúc`: sửa thành *"Lifeline được kích hoạt (`activate`) khi nhận thông điệp gọi hàm và kết thúc kích hoạt (`deactivate`) khi trả kết quả về; không đặt ký hiệu hủy đối tượng (`X`) trên Actor và các lớp tồn tại xuyên suốt phiên."*
- [ ] **Bảng 73 (tr. 58–60)** → Rà soát lại 64 dòng đảm bảo tên `Boundary` và `Controller` khớp 100% với Bảng 169 (UI).
- [ ] **Đầu mỗi tiểu mục 3.3.3.x – 3.3.5.x** → Đảm bảo chỉ có tiêu đề tiểu mục + hình SD + caption dưới hình, không viết đoạn văn thuyết minh dài dòng trước mỗi SD (`Lỗi.docx` dòng 55).

**B. Sửa đồng loạt cho cả 32 file `.puml` (`UC01 – UC32`):**
- [ ] **Toàn bộ `Hình 9 – Hình 40` (`UC1 – UC32`)** → Đang có dấu `X` (`destroy`) ở cuối lifeline Actor và Boundary; một số hình bị đè nhãn `alt` lên thanh activation (`Hình 14, 18, 20, 26, 36`) → Áp dụng chung bộ header PlantUML chuẩn ở **Mục 1.3.1** (`ParticipantPadding 25`, `BoxPadding 10`, `maxMessageSize 180`, `hide footbox`), **xóa toàn bộ dòng `destroy ...`**, ngắt dòng các điều kiện `alt` dài bằng `\n`, kiểm tra xuất ảnh PNG độ phân giải cao (`dpi 300` hoặc SVG/PNG sắc nét) không bị mất viền phải/dưới của khung `alt`.
- [ ] **Toàn bộ `Hình 9 – Hình 40`** → Kiểm tra tuyệt đối **không chèn** `alt Hủy` hay `alt Lỗi kết nối CSDL` (theo Xung đột #1 – `Lỗi.docx` dòng 39–41).

**C. Sửa chi tiết từng Sequence Diagram bị lỗi logic / lệch hợp đồng (`UC1 – UC32`):**
- [ ] **`Hình 9` (`UC1 – Thêm danh mục`) & `Hình 10` (`UC2 – Sửa danh mục`)** → Theo `Lỗi.docx` dòng 34 & R13 (kiểm tra trùng tên phải gọi Entity, và khớp code Demo Phần B):
  - Trong `UC1`: `QuanLyDanhMucCtrl.luuDanhMuc(ten, moTa)` → gọi `DanhMuc.kiemTraHopLe(ten)` → `alt Tên danh mục để trống` → báo lỗi `"Tên danh mục không được để trống"` / `else Tên hợp lệ` → gọi **`DanhMuc.kiemTraTrungTen(ten, 0)`** → `alt Tên danh mục đã tồn tại` → báo lỗi `"Tên danh mục đã tồn tại"` / `else Không trùng` → gọi `DanhMuc.luu(ten, moTa)` → trả kết quả thành công.
  - Trong `UC2`: Bắt đầu từ thao tác chọn Sửa (`layThongTinDanhMuc(maDanhMuc)` → `DanhMuc.timTheoMa(maDanhMuc)`) và gửi Cập nhật (`capNhatDanhMuc(maDanhMuc, ten, moTa)` → `DanhMuc.kiemTraHopLe(ten)` → `DanhMuc.kiemTraTrungTen(ten, maDanhMuc)` → `DanhMuc.capNhat(maDanhMuc, ten, moTa)`).
- [ ] **`Hình 12` (`UC4 – Thêm điện thoại`) & `Hình 13` (`UC5 – Sửa điện thoại`)** → Theo R10 & R13:
  - Chữ ký hàm Controller phải có đủ tham số **`hang`**: `luuDienThoai(ten, maDanhMuc, hang, moTa, thongSo, hinhAnh)` (UC4) và `capNhatDienThoai(maDienThoai, ten, maDanhMuc, hang, moTa, thongSo, hinhAnh)` (UC5).
  - Sau `DienThoai.kiemTraHopLe(ten, thongSo)` và `DanhMuc.kiemTraTonTai(maDanhMuc)`, gọi **`DienThoai.kiemTraTrungTen(ten, 0)`** (ở UC4) hoặc **`DienThoai.kiemTraTrungTen(ten, maDienThoai)`** (ở UC5) trước khung `alt Tên điện thoại đã tồn tại`, rồi mới gọi `DienThoai.luu(ten, maDanhMuc, hang, moTa, hinhAnh)` / `DienThoai.capNhat(...)` và `ThongSoKyThuat.luu(maDienThoai, thongSo)` / `ThongSoKyThuat.capNhat(maDienThoai, thongSo)`.
  - Ở `UC5`, lược bỏ bước mở màn hình tải toàn bộ danh sách nếu làm hình quá dài; bắt đầu từ `layThongTinDienThoai(maDienThoai)` (`DienThoai.timTheoMa` + `ThongSoKyThuat.timTheoDienThoai`).
- [ ] **`Hình 14` (`UC6 – Xóa điện thoại`)** → Nhãn `alt` đang đè thanh activation → Nới padding, kiểm tra đúng chuỗi gọi khớp code Demo: `DienThoai.demDonChuaHoanTatTheoDienThoai(maDienThoai)` (hoặc `DonHang.demDonChuaHoanTatTheoDienThoai(maDienThoai)` — **chú ý: giữ đúng trên `DonHang.demDonChuaHoanTatTheoDienThoai(maDienThoai)` khớp Bảng 102 Class `DonHang`!** Xóa dòng ghi nhầm ở Phụ lục A `DienThoai` dòng 3782 nếu trùng, chỉ đặt hàm này ở `DonHang`!), tiếp theo `DienThoai.kiemTraPhatSinhGiaoDich(maDienThoai)` → rẽ nhánh xóa mềm `DienThoai.chuyenNgungKinhDoanh(maDienThoai)` hoặc xóa cứng `DienThoai.xoa(maDienThoai)`.
- [ ] **`Hình 18` (`UC10 – Khôi phục sản phẩm ngừng kinh doanh`)** → Đang gọi `DanhMuc.kiemTraTonTai(maDanhMuc)` khi chưa lấy thông tin điện thoại → Sửa thứ tự trong `khoiPhucDienThoai(maDienThoai)`: gọi **`DienThoai.timTheoMa(maDienThoai) --> dienThoai`** trước để lấy `maDanhMuc`, sau đó mới gọi `DanhMuc.kiemTraTonTai(maDanhMuc) --> tonTai` → `alt Danh mục không còn tồn tại` / `else Danh mục hợp lệ` → `DienThoai.capNhatTrangThai(maDienThoai, "Đang kinh doanh")`.
- [ ] **`Hình 19` (`UC11 – Thiết lập % lợi nhuận`)** → Đang nhảy vào `loop Với mỗi sản phẩm thuộc phạm vi` mà chưa lấy danh sách sản phẩm → Chèn trước `loop`: `ChinhSachGiaCtrl -> DienThoai : timTheoPhamVi(phamVi)` trả về `danhSachDienThoai`.
- [ ] **`Hình 20` (`UC12 – Lập phiếu nhập kho`)** → Hình đang quá cao, chữ nhỏ 5pt + đè nhãn `alt` → Bỏ bước tải danh sách phiếu nhập lúc mới mở màn hình ở đầu SD (đã có `UC14 Tra cứu phiếu nhập` đảm nhiệm!), bắt đầu trực tiếp từ bước Nhân viên Kho nhập thông tin và bấm Lưu phiếu (`luuPhieuNhap`) + Gửi duyệt (`guiDuyetPhieuNhap`) để hình to rõ chữ `≥ 8pt`.
- [ ] **`Hình 21` (`UC13 – Duyệt phiếu nhập kho`)** → Nhánh từ chối đang gọi `PhieuNhap.capNhatTrangThai(maPhieuNhap, "Từ chối")` làm mất biến `lyDo` và không lưu người duyệt; hình hơi dài → Bắt đầu từ bước Ban Quản lý chọn phiếu chờ duyệt; sửa cả 2 nhánh duyệt/từ chối dùng thống nhất chữ ký 4 tham số theo R03:
  - Khi duyệt: `PhieuNhap.capNhatTrangThai(maPhieuNhap, "Đã duyệt", null, maNguoiDuyet)`
  - Khi từ chối: `PhieuNhap.capNhatTrangThai(maPhieuNhap, "Từ chối", lyDoTuChoi, maNguoiDuyet)`
- [ ] **`Hình 24` (`UC16 – Xác nhận xuất kho`)** → Chưa lưu người xác nhận xuất kho; hình quá dài → Bắt đầu từ bước Nhân viên Kho chọn phiếu xuất (`layChiTietPhieuXuat(maPhieuXuat)`) và bấm Xác nhận xuất kho (`xacNhanXuatKho(maPhieuXuat)`); đổi lời gọi cập nhật phiếu xuất theo R03 thành **`PhieuXuat.capNhatTrangThai(maPhieuXuat, "Đã xuất", maNguoiXacNhan)`** (thời điểm xác nhận Entity tự ghi `NOW()` hoặc truyền `thoiDiemXacNhan` — thống nhất 4 tham số **`PhieuXuat.capNhatTrangThai(maPhieuXuat, "Đã xuất", maNguoiXacNhan, thoiDiemXacNhan)`** khớp R03!).
- [ ] **`Hình 26` (`UC18 – Kiểm kê tồn kho`)** → Đang dùng `maKho` (vi phạm R01) và hình rất dài chữ nhỏ → Sửa:
  - Đổi `taoDotKiemKe(maKho, thoiDiem)` → **`taoDotKiemKe(thoiDiem)`**
  - Đổi `BienBanKiemKe.taoMoi(maKho, maNhanVien, thoiDiem)` → **`BienBanKiemKe.taoMoi(maNhanVien, thoiDiem)`**
  - Đổi `TonKho.layTonTheoKho(maKho)` → **`TonKho.layTonKhoHienHanh()`**
  - Lược bỏ bước mở màn hình tải danh sách biên bản cũ ở đầu hình để chữ in A4 ≥ 8pt.
- [ ] **`Hình 27` (`UC19 – Duyệt biên bản kiểm kê`)** → Bỏ mất `lyDo` và người duyệt → Sửa lời gọi cập nhật trạng thái ở cả nhánh Duyệt và Từ chối theo R03 thành **`BienBanKiemKe.capNhatTrangThai(maBienBan, trangThai, lyDoTuChoi, maNguoiDuyet)`** (khi duyệt truyền `lyDoTuChoi = null`).
- [ ] **`Hình 31` (`UC23 – Sửa số lượng sản phẩm trong giỏ hàng`)** → Sai 2 lỗi: tên biến có dấu tiếng Việt `"maChiTietGioHàng"` và gọi `TonKho.tinhTonKhaDung(maDienThoai)` khi chưa biết `maDienThoai` → Sửa tên biến thành `maChiTietGioHang`; ngay đầu `capNhatSoLuong(maChiTietGioHang, soLuong)` thêm lời gọi **`ChiTietGioHang.timTheoMa(maChiTietGioHang) --> chiTiet`** (lấy `maDienThoai`, `maGioHang`), sau đó mới gọi `TonKho.tinhTonKhaDung(maDienThoai)`.
- [ ] **`Hình 32` (`UC24 – Xóa sản phẩm khỏi giỏ hàng`)** → Hàm kiểm tra `ChiTietGioHang.demTheoGioHang(maGioHang)` đang bị nhét vào bên trong nhánh `alt` → Đưa lời gọi `ChiTietGioHang.xoa(maChiTietGioHang)` rồi gọi **`ChiTietGioHang.demTheoGioHang(maGioHang) --> soSanPhamConLai`** ra **trước** khung `alt soSanPhamConLai == 0` (hiển thị giỏ trống) / `else Còn sản phẩm` (`GioHang.tinhTongTamTinh(maGioHang)`).
- [ ] **`Hình 36` (`UC28 – Đặt hàng` – Sơ đồ Demo Phần A)** → Đang mắc 4 lỗi lớn (gọi nhầm `GioHang.kiemTraDangNhap`, chưa chụp địa chỉ nhận Q5, gọi `Voucher.ghiNhanLuotSuDung` Q6, và hình quá dài chữ 4–5pt) → Sửa triệt để như sau:
  1. Đổi `GioHang.kiemTraDangNhap(maPhien)` → gọi lifeline **`PhienDangNhap.kiemTraDangNhap(maPhien) --> daDangNhap`** trước khung `alt Chưa đăng nhập`.
  2. Dùng khung tham chiếu chuẩn UML **`ref over KH, UI, DatHangCtrl : UC25 - Chọn địa chỉ giao hàng & UC26 - Tính phí vận chuyển`**, **`opt Khách hàng nhập mã giảm giá` → `ref ... : UC30 - Áp dụng mã giảm giá`**, và sau bước giữ chỗ tạm (`TonKho.giuChoTam`) dùng **`ref over KH, UI, DatHangCtrl, ThanhToan : UC29 - Thanh toán`** để sơ đồ cực kỳ gọn gàng, chữ to rõ ≥ 8pt trên 1 trang A4!
  3. Trước khi lưu đơn chính thức:
     - Thêm `opt Giỏ hàng có áp dụng mã giảm giá`: gọi **`DonHang.demLuotDungVoucher(maKhachHang, maVoucher) --> soLuotDaDung`** → kiểm tra `< gioiHanSuDung`.
     - Gọi **`DiaChiGiaoHang.timTheoMa(maDiaChi) --> thongTinDiaChi`** để lấy bản chụp người nhận.
     - Gọi **`DonHang.luu(maKhachHang, maDiaChi, tenNguoiNhan, sdtNguoiNhan, diaChiNhan, maVoucher, phiVanChuyen, tongTien, trangThai) --> maDonHang`** (đúng 9 tham số R06).
     - Gọi `ThanhToan.ganDonHang(maGioHang, maDonHang)`, lặp lưu `ChiTietDonHang.luu(...)`, `TonKho.chuyenGiuChoChoDonHang(...)`, nếu có `maVoucher` thì gọi **`Voucher.tangLuotDaDung(maVoucher)`**, cuối cùng `GioHang.xoaSanPhamDaDat(maGioHang)`.
- [ ] **`Hình 38` (`UC30 – Áp dụng mã giảm giá`)** → Các hàm kiểm tra đang nằm bên trong nhánh lỗi của `alt` + đang gọi `Voucher.kiemTraLuotSuDung` (bị xóa theo R07) → Vẽ lại đúng cấu trúc **kiểm tra trước, rẽ nhánh `alt` lồng nhau sau**:
  1. `Voucher.kiemTraHopLe(maVoucher) --> hopLe` → `alt Mã không tồn tại, hết hạn, hết số lượng hoặc bị khóa` / `else Mã còn hiệu lực`:
  2. `DonHang.demLuotDungVoucher(maKhachHang, maVoucher) --> soLuotDaDung` → `alt soLuotDaDung >= gioiHanSuDung` / `else Còn lượt sử dụng`:
  3. `DieuKienApDung.kiemTraDieuKien(maVoucher, maGioHang) --> datDieuKien` → `alt Chưa đạt điều kiện áp dụng` / `else Đạt điều kiện`:
  4. `GioHang.tinhGiamGia(maGioHang, maVoucher) --> soTienGiam` & `GioHang.tinhLaiTongTien(maGioHang) --> tongThanhToan`.
- [ ] **`Hình 39` (`UC31 – Tự hủy đơn hàng chờ duyệt`)** → Đang làm rơi mất `lyDo` khi cập nhật đơn và truyền `maDonHang` vào `hoanLuotSuDung` → Sửa thành: gọi **`DonHang.capNhatTrangThaiHuy(maDonHang, "Đã hủy", lyDo)`** (hoặc dùng hàm nạp chồng `DonHang.capNhatTrangThai(maDonHang, "Đã hủy", lyDo, null)` — **CHỐT DÙNG THỐNG NHẤT 1 CHỮ KÝ ĐA NĂNG hoặc NẠP CHỒNG RÕ RÀNG ở Bảng khóa hàm bên dưới: `DonHang.capNhatTrangThai(maDonHang, "Đã hủy", lyDo, null)`** để không đẻ thêm nhiều tên hàm lạ!), và trong `opt Đơn hàng có dùng mã giảm giá` gọi **`Voucher.hoanLuotSuDung(maVoucher)`**.

#### 4. Điểm bắt buộc khớp với Bước 0 (Sync Guardrails)
- Kiểm tra `Ctrl+F` trong cả 32 file `.puml` (`UC01–UC32`): `destroy` = **0**, `maKho` = **0**, `layTonTheoKho` = **0**, `kiemTraLuotSuDung` = **0**, `ghiNhanLuotSuDung` = **0**, `maChiTietGioHàng` = **0**, `loiKetNoi` = **0**.
- Mọi lời gọi hàm vào Entity trong `UC01–UC32` phải trùng khớp từng tên hàm và số lượng tham số với Gói 4 (Class Diagram) và được ghi chép gửi sang TV3 để tổng hợp Phụ lục A.

---

### GÓI VIỆC 3 – THÀNH VIÊN 3: 32 SEQUENCE DIAGRAM NỬA SAU (`UC33 – UC64`) & TỔNG HỢP PHỤ LỤC A

#### 1. Phạm vi phụ trách chính xác
- **Các mục văn bản:** `Mục 3.3.5.12 – 3.3.8.8` (tr. 93–124) và toàn bộ **`PHỤ LỤC A. CÁC HÀM ĐƯỢC GỌI TRONG SEQUENCE DIAGRAM`** (tr. 206–217).
- **Các Hình vẽ & file nguồn `.puml` (32 sơ đồ):** `Hình 41 – Hình 72` tương ứng **`UC33 – UC64`**:
  - Cụm Xử lý đơn hàng, Hủy đơn & Hoàn tiền phía Nhân viên (`UC33 – UC38`, `Hình 41 – 46`)
  - Cụm Khách hàng & Quản lý tài khoản (`UC39 – UC52`, `Hình 47 – 60`)
  - Cụm Quản lý báo cáo thống kê (`UC53 – UC56`, `Hình 61 – 64`)
  - Cụm Quản lý khuyến mãi & Mã giảm giá (`UC57 – UC64`, `Hình 65 – 72`) — **Cụm dự phòng chấm Demo Phần B!**

#### 2. Hình dung kết quả đầu ra của bước này
32 Sequence Diagram nửa sau (`Hình 41 – 72`) chuẩn mực BCE, không còn dấu `X` trên Actor/Boundary, không nhét hàm kiểm tra vào trong nhánh lỗi `alt`, truyền đủ lý do/người duyệt xuống Entity, `UC41 Đăng xuất` gọn chuẩn; đi kèm **Phụ lục A** mới chính xác tuyệt đối 100% đối với toàn bộ 18 Controller và 30 Entity trên cả 64 SD (khớp 1–1 với ngăn phương thức Class Diagram của TV4).

#### 3. Checklist sửa chữa chi tiết đến từng dòng / hình / bảng

**A. Sửa đồng loạt cho cả 32 file `.puml` (`UC33 – UC64`):**
- [ ] **Toàn bộ `Hình 41 – Hình 72` (`UC33 – UC64`)** → Đang có dấu `X` (`destroy`) trên Actor/Boundary; các hình `Hình 44, 46, 57, 60, 65, 66` bị nhãn `alt` đè lên thanh activation → Thay bộ header PlantUML chuẩn ở **Mục 1.3.1** (`ParticipantPadding 25`, `BoxPadding 10`, `maxMessageSize 180`, `hide footbox`), **xóa sạch mọi lệnh `destroy`**, xuống dòng `\n` cho nhãn điều kiện dài, xuất lại PNG sắc nét nằm gọn trong 1 trang A4 (chữ ≥ 8pt), không mất viền khung `alt`.
- [ ] **Toàn bộ `Hình 41 – Hình 72`** → Tuyệt đối **không thêm** nhánh `alt Hủy` giao diện hay `alt Lỗi kết nối CSDL` (theo Xung đột #1 – `Lỗi.docx`).

**B. Sửa chi tiết từng Sequence Diagram bị lỗi logic / lệch hợp đồng (`UC33 – UC64`):**
- [ ] **`Hình 41` (`UC33 – Duyệt đơn hàng`)** → Chưa lưu mã nhân viên duyệt đơn → Sửa lời gọi cập nhật trạng thái đơn theo R03 thành: **`DonHang.capNhatTrangThai(maDonHang, "Đã duyệt", null, maNhanVienDuyet)`** (dùng chung chữ ký nạp chồng 4 tham số `(maDonHang, trangThai, lyDoHuy, maNhanVienDuyet)` với `UC31`).
- [ ] **`Hình 42` (`UC34 – Cập nhật tiến độ giao hàng`)** → Hàm kiểm tra `PhieuXuat.kiemTraDaXacNhan(maDonHang)` (khi chuyển sang `"Đang giao"` / `"Đã giao thành công"`) và/hoặc `DonHang.kiemTraChuyenTrangThai(maDonHang, trangThaiMoi)` đang đặt sai bên trong nhánh `alt` → Đưa lời gọi **`DonHang.kiemTraChuyenTrangThai(maDonHang, trangThaiMoi) --> hopLe`** ra **trước** `alt Chuyển trạng thái không hợp lệ`; bên trong nhánh hợp lệ, nếu trạng thái mới yêu cầu đã xuất kho thì gọi **`PhieuXuat.kiemTraDaXacNhan(maDonHang) --> daXuatKho`** rồi mới mở `alt Chưa xác nhận xuất kho` / `else Hợp lệ` → `DonHang.capNhatTrangThai(maDonHang, trangThaiMoi)`.
- [ ] **`Hình 44` (`UC36 – Xác nhận hủy đơn`)** → Sửa 2 chỗ đồng bộ R03 & R07:
  - Khi cập nhật yêu cầu hủy đơn: gọi **`YeuCauHuyDon.capNhatTrangThai(maYeuCau, "Đã xác nhận", null, maNhanVienXuLy)`**.
  - Trong `opt Đơn hàng có dùng mã giảm giá`: đổi `Voucher.hoanLuotSuDung(maDonHang)` thành **`Voucher.hoanLuotSuDung(maVoucher)`**.
- [ ] **`Hình 45` (`UC37 – Từ chối yêu cầu hủy đơn`)** → Mắc 2 lỗi: hàm `PhieuXuat.kiemTraDaXacNhan(maDonHang)` nằm bên trong nhánh lỗi `alt` VÀ khi gọi `YeuCauHuyDon.capNhatTrangThai` bỏ rơi mất `lyDoTuChoi` & `maNhanVienXuLy` → Sửa:
  1. Gọi `YeuCauHuyDon.timTheoMa(maYeuCau)` lấy `maDonHang`, kiểm tra `lyDoTuChoi` không rỗng, gọi **`PhieuXuat.kiemTraDaXacNhan(maDonHang) --> daXuatKho`** **trước** khung `alt` kiểm tra điều kiện từ chối.
  2. Gọi **`YeuCauHuyDon.capNhatTrangThai(maYeuCau, "Từ chối", lyDoTuChoi, maNhanVienXuLy)`** (đủ 4 tham số theo R03).
- [ ] **`Hình 47` (`UC39 – Đăng ký`) & `Hình 48` (`UC40 – Đăng nhập`)** → Kiểm tra khối `opt Phiên hiện tại có giỏ hàng tạm` gọi đúng **`GioHang.gopGioHangTam(maPhien, maKhachHang)`** và ghi nhận hàm này vào Phụ lục A (hiện Phụ lục A đang bỏ quên!).
- [ ] **`Hình 49` (`UC41 – Đăng xuất`)** → Kiểm tra theo `Lỗi.docx` dòng 42: `XacThucCtrl.dangXuat(maPhien)` → `PhienDangNhap.kiemTraConHieuLuc(maPhien)` → `PhienDangNhap.huyPhien(maPhien)` → trả kết quả đăng xuất thành công về Màn hình → Actor và **kết thúc ngay lập tức** (đảm bảo không vẽ nối luồng hiển thị/nhập lại form đăng nhập phía sau).
- [ ] **`Hình 57` (`UC49 – Khóa tài khoản`) & `Hình 58` (`UC50 – Mở khóa tài khoản`)** → Ở `UC49` đang bỏ rơi biến `lyDo` khi gọi `TaiKhoan.capNhatTrangThai` → Sửa chữ ký theo R03 thành **`TaiKhoan.capNhatTrangThai(maTaiKhoan, "Bị khóa", lyDoKhoa)`** ở `UC49` và **`TaiKhoan.capNhatTrangThai(maTaiKhoan, "Hoạt động", null)`** ở `UC50`.
- [ ] **`Hình 66` (`UC58 – Sửa khuyến mãi`)** → Theo R15: Kiểm tra ở bước nạp thông tin khuyến mãi sửa (`layThongTinKhuyenMai(maKhuyenMai)`) có gọi `KhuyenMai.timTheoMa(maKhuyenMai)` + **`DieuKienApDung.timTheoKhuyenMai(maKhuyenMai)`**; và ở bước lưu cập nhật (`capNhatKhuyenMai(...)`) gọi **`DieuKienApDung.capNhat(maKhuyenMai, giaTriToiThieu, nhomHang, doiTuong)`** (không gọi nhầm `DieuKienApDung.luu` của UC57) để khớp 100% với Bảng 100 của lớp `DieuKienApDung`!
- [ ] **`Hình 68 – Hình 72` (`UC60 – UC64 – Quản lý mã giảm giá`)** → Kiểm tra chữ ký `Voucher.luu(maVoucher, loaiGiam, giaTriGiam, soLuong, gioiHanSuDung, ngayBatDau, ngayKetThuc)` và `Voucher.capNhat(...)` thống nhất với Bảng 132 của TV4; ở `UC61 (Hình 69)` gọi `DieuKienApDung.timTheoVoucher(maVoucher) --> dieuKien` (trả về 1 đối tượng `DieuKienApDung`).

**C. Tổng hợp lại toàn bộ `PHỤ LỤC A` (`tr. 206–217`):**
- [ ] **Phụ lục A – Hàm của 18 Controller & 30 Entity (tr. 206–217)** → Đang có hàng loạt chỗ lệch với SD và Class → Rà soát và cập nhật toàn bộ các bảng trong Phụ lục A theo đúng Hợp đồng Bước 0:
  - **Ở bảng `KiemKeCtrl` (tr. 207):** Đổi `taoDotKiemKe(maKho, thoiDiem)` → `taoDotKiemKe(thoiDiem)`.
  - **Ở bảng `QuanLyDienThoaiCtrl` (tr. 208):** Bổ sung tham số `hang` vào `luuDienThoai(ten, maDanhMuc, hang, moTa, thongSo, hinhAnh)` và `capNhatDienThoai(maDienThoai, ten, maDanhMuc, hang, moTa, thongSo, hinhAnh)`.
  - **Ở bảng Entity `BienBanKiemKe` (tr. 210):** Đổi `taoMoi(maKho, thoiDiem)` → `taoMoi(maNhanVien, thoiDiem)`; bổ sung overload/chữ ký `capNhatTrangThai(maBienBan, trangThai, lyDoTuChoi, maNguoiDuyet)` (UC19).
  - **Ở bảng Entity `ChiTietGioHang` (tr. 211):** Thêm `timTheoMa(maChiTietGioHang)` (UC23).
  - **Ở bảng Entity `DanhMuc` (tr. 211):** Thêm `kiemTraTrungTen(ten, maDanhMuc)` (UC1, UC2).
  - **Ở bảng Entity `DiaChiGiaoHang` (tr. 212):** Đổi `xacDinhKhuVuc(maDiaChi)` (đang ghi sai!) thành **`layMaQuyTac(maDiaChi)`** (UC26); bổ sung tham số `maQuyTac` vào `kiemTraHopLe`, `luu`, `capNhat` cho khớp Bảng 96.
  - **Ở bảng Entity `DienThoai` (tr. 212):** Bổ sung tham số `hang` vào `luu(...)` và `capNhat(...)`; thêm `kiemTraTrungTen(ten, maDienThoai)` (UC4, UC5) và `timTheoPhamVi(phamVi)` (UC11).
  - **Ở bảng Entity `DieuKienApDung` (tr. 212):** Thêm `capNhat(maKhuyenMai, giaTriToiThieu, nhomHang, doiTuong)` (UC58) và `timTheoKhuyenMai(maKhuyenMai)` (UC58); sửa `luu(...)` chỉ dùng ở `UC57`.
  - **Ở bảng Entity `DonHang` (tr. 213):** **Xóa 2 hàm ghi nhầm lớp** là `capNhatPhiVanChuyen(phiVanChuyen)` và `ghiNhanDiaChi(maDiaChi)` (vì chúng thuộc lớp `GioHang`!); sửa `luu(...)` thành 9 tham số `(maKhachHang, maDiaChi, tenNguoiNhan, sdtNguoiNhan, diaChiNhan, maVoucher, phiVanChuyen, tongTien, trangThai)`; thêm **`demLuotDungVoucher(maKhachHang, maVoucher)`** (`UC28, UC30`); cập nhật `capNhatTrangThai(maDonHang, trangThai, lyDoHuy, maNhanVienDuyet)` (`UC31, UC33`).
  - **Ở bảng Entity `GioHang` (tr. 213):** **Xóa** `kiemTraDangNhap(maPhien)`; **thêm 3 hàm đang thiếu** là `ghiNhanDiaChi(maGioHang, maDiaChi)` (UC25), `capNhatPhiVanChuyen(maGioHang, phiVanChuyen)` (UC26), và `gopGioHangTam(maPhien, maKhachHang)` (UC39, UC40).
  - **Ở bảng Entity `PhienDangNhap` (tr. 214):** Thêm **`kiemTraDangNhap(maPhien)`** (UC28).
  - **Ở bảng Entity `PhieuNhap` (tr. 214):** Cập nhật `capNhatTrangThai(maPhieuNhap, trangThai, lyDoTuChoi, maNguoiDuyet)` (UC13) và `luu(maNhanVien, nguonNhap, ngayNhap, trangThai)` (UC12).
  - **Ở bảng Entity `PhieuXuat` (tr. 215):** Cập nhật `capNhatTrangThai(maPhieuXuat, trangThai, maNguoiXacNhan, thoiDiemXacNhan)` (UC16) và `luu(maDonHang, maNhanVien, ghiChu, trangThai)` (UC15).
  - **Ở bảng Entity `QuyTacPhiVanChuyen` (tr. 215):** Đổi `timTheoKhuVuc(khuVuc)` (ghi sai!) thành **`timTheoMa(maQuyTac)`** (UC26) và sửa `tinhPhi(maQuyTac, tongTienHang)` khớp Bảng 120.
  - **Ở bảng Entity `TaiKhoan` (tr. 215):** Cập nhật `capNhatTrangThai(maTaiKhoan, trangThai, lyDoKhoa)` (UC49, UC50).
  - **Ở bảng Entity `ThanhToan` (tr. 216):** Thêm **`ganDonHang(maGioHang, maDonHang)`** (UC28) đang bị thiếu!
  - **Ở bảng Entity `TonKho` (tr. 216):** **Xóa** `layTonTheoKho(maKho)`.
  - **Ở bảng Entity `Voucher` (tr. 217):** **Xóa** `kiemTraLuotSuDung` và `ghiNhanLuotSuDung`; thêm **`tangLuotDaDung(maVoucher)`** (UC28); sửa `hoanLuotSuDung(maDonHang)` → **`hoanLuotSuDung(maVoucher)`** (UC31, UC36).
  - **Ở bảng Entity `YeuCauHuyDon` (tr. 217):** Cập nhật `capNhatTrangThai(maYeuCau, trangThai, lyDoTuChoi, maNhanVienXuLy)` (UC36, UC37).

#### 4. Điểm bắt buộc khớp với Bước 0 (Sync Guardrails)
- Tập hợp tất cả các hàm thuộc mục **"Hàm của các lớp Entity"** trong `Phụ lục A` sau khi sửa phải **trùng khớp 1–1 (100% không thừa 1 hàm, không thiếu 1 hàm)** với `Bảng 76 – Bảng 134` của Gói 4 (TV4).

---

### GÓI VIỆC 4 – THÀNH VIÊN 4: CLASS DIAGRAM (`MỤC 3.4`), MÔ HÌNH RDM (`MỤC 3.5`) & ĐỒNG BỘ SQL/CODE

#### 1. Phạm vi phụ trách chính xác
- **Phần Thiết kế Lớp đối tượng (`Mục 3.4`, tr. 124–148):**
  - Văn bản quy ước `Mục 3.4.1`, Danh sách lớp `Mục 3.4.2` (`Bảng 74`), Mô tả chi tiết 30 lớp `Mục 3.4.3` (**`Bảng 75 – Bảng 134`**), Tổng hợp quan hệ `Mục 3.4.4` (**`Bảng 135`**).
  - **5 Hình sơ đồ lớp:** `Hình 73` (Tổng quan), `Hình 74` (Cụm 1: Sản phẩm & chính sách giá), `Hình 75` (Cụm 2: Kho hàng & kiểm kê), `Hình 76` (Cụm 3: Bán hàng, đơn hàng & thanh toán), `Hình 77` (Cụm 4: Tài khoản, khuyến mãi, báo cáo & thông báo).
- **Phần Thiết kế Cơ sở dữ liệu RDM (`Mục 3.5`, tr. 149–170):**
  - Văn bản chọn lọc thuộc tính `Mục 3.5.1` (`Bảng 136` + thêm bảng chọn lọc thuộc tính mới), Danh sách 30 bảng `Mục 3.5.3` (`Bảng 137`), Mô tả chi tiết 30 bảng `Mục 3.5.4` (**`Bảng 138 – Bảng 167`**), Danh sách khóa ngoại `Mục 3.5.5` (**`Bảng 168`**).
  - **5 Hình sơ đồ RDM:** `Hình 78` (RDM tổng quan), `Hình 79` (RDM Cụm 1), `Hình 80` (RDM Cụm 2), `Hình 81` (RDM Cụm 3), `Hình 82` (RDM Cụm 4).
- **Kiểm tra đồng bộ file `database/01_schema.sql` & code PHP của nhóm:** Cập nhật nhẹ các cột bổ sung nếu cần để file `.sql` nộp kèm khớp 100% RDM.

#### 2. Hình dung kết quả đầu ra của bước này
Nhờ Hợp đồng Bước 0 đã khóa cứng mọi chữ ký hàm và cột mới, **TV4 làm ngay từ Ngày 1 mà không cần đợi TV2/TV3!** Đầu ra là bộ Class Diagram sạch 100% đường Dependency (`..>`) và mũi tên chiều (`-->`), Hình 73 rút gọn rõ nét trên 1 trang A4 ngang, Hình 74–77 có đủ 3 ngăn (Tên – Thuộc tính – Phương thức), 30 lớp khớp 100% Phụ lục A; nối tiếp sang RDM có bảng chọn lọc thuộc tính chuẩn 3NF, 30 bảng dùng kiểu SQL có độ dài cụ thể kèm cột **Ràng buộc toàn vẹn (RBTV)** và sơ đồ Crow's Foot hiện đầy đủ `[PK]`/`[FK]` không còn bảng cô lập.

#### 3. Checklist sửa chữa chi tiết đến từng dòng / hình / bảng

**A. Phần Class Diagram (`Mục 3.4`, tr. 124–148):**
- [ ] **Đoạn văn quy ước `Mục 3.4.1` (tr. 124)** → Đang tự viết *"đường nét đứt có mũi tên (- - ->) biểu diễn quan hệ phụ thuộc (dependency). Mọi đường nối đều có nhãn tên quan hệ (kèm mũi tên chỉ chiều đọc)"* — **trái ngược hoàn toàn yêu cầu của thầy trong `261_BaoCaoCuoiKi.docx` dòng 20 & `Lỗi.docx` dòng 59–60!** → Xóa bỏ câu về dependency và mũi tên chỉ chiều đọc; viết lại: *"Sơ đồ lớp thể hiện rõ 3 bản chất quan hệ: hợp thành (composition – hình thoi đặc ◆), tập hợp (aggregation – hình thoi rỗng ◇) và kết hợp (association – đường nét liền) kèm bội số ở hai đầu; lược bỏ chiều điều hướng và không sử dụng quan hệ phụ thuộc (dependency). Hình 73 trình bày sơ đồ lớp tổng quan dạng rút gọn (chỉ hiển thị tên lớp, bản chất quan hệ và bội số) trên một trang A4 ngang; bốn sơ đồ cụm chi tiết (Hình 74 – Hình 77) hiển thị đầy đủ cả ba ngăn: tên lớp, thuộc tính và phương thức."*
- [ ] **`Hình 73` – Sơ đồ lớp tổng quan (đặt lại trên 1 trang A4 ngang)** → Đang rộng `4184px` chữ bé li ti, có 7 đường `..>`, có ký hiệu chiều `▸ ◂ ▾`, nhãn bội số đè lên hình thoi → Vẽ lại bằng PlantUML (`hide circle`, `hide members`):
  - Xóa sạch cả 7 đường `..>` và mọi mũi tên chiều `-->`, `▸`, `◂`, `▾`.
  - Thêm 8 đường Association mới theo Hợp đồng Bước 0: `CanhBaoTonKho "0..*" -- "0..1" DanhMuc`, `CanhBaoTonKho "0..*" -- "0..1" DienThoai`, `GioHang "0..*" -- "0..1" DiaChiGiaoHang`, `NhanVien "0..1" -- "0..*" DonHang : duyệt`, `NhanVien "0..1" -- "0..*" PhieuNhap : duyệt`, `NhanVien "0..1" -- "0..*" PhieuXuat : xác nhận`, `NhanVien "0..1" -- "0..*" BienBanKiemKe : duyệt`, `NhanVien "0..1" -- "0..*" YeuCauHuyDon : xử lý`.
  - Sửa bội số `GioHang "1" -- "0..*" ThanhToan`.
  - Lớp `BaoCao` không nối dây với lớp nào, gắn ghi chú lớp tổng hợp tạm. Căn chỉnh khoảng cách để bội số không đè vào hình thoi `◆`/`◇`.
- [ ] **`Hình 74 – Hình 77` – 4 Sơ đồ lớp theo cụm (tr. 127–130)** → Đang chỉ có 2 ngăn (thiếu ngăn Phương thức – vi phạm `Lỗi.docx` dòng 63–64!) và có mũi tên chiều / dependency → Bổ sung đầy đủ **ngăn thứ 3 (Phương thức `+tenHam(thamSo: Kieu): KieuTraVe`)** khớp 100% với `Bảng 76 – 134`, xóa mọi mũi tên chiều và đường `..>`, bố cục rõ nét trên từng trang A4.
- [ ] **Cập nhật Thuộc tính & Phương thức trong `Bảng 75 – Bảng 134` (tr. 131–145):**
  - [ ] **`Bảng 77 & Bảng 78` (`BienBanKiemKe`):** Xóa thuộc tính `maKho` (Q1); thêm `lyDoTuChoi: String` và `thoiDiemDuyet: Date` (R03); ở Bảng 78 sửa `taoMoi(maNhanVien: int, thoiDiem: Date): int` và cập nhật `capNhatTrangThai(maBienBan: int, trangThai: String, lyDoTuChoi: String, maNguoiDuyet: int): boolean` (ghi chú: ở UC18 khi gửi duyệt truyền `lyDoTuChoi = null, maNguoiDuyet = 0/null` hoặc liệt kê rõ 2 chữ ký overload `capNhatTrangThai(maBienBan: int, trangThai: String): boolean` cho UC18 và `capNhatTrangThai(maBienBan: int, trangThai: String, lyDoTuChoi: String, maNguoiDuyet: int): boolean` cho UC19 — **khuyến nghị dùng nạp chồng 2 chữ ký rõ ràng vì UML/OOP hỗ trợ overloading rất chuẩn, khớp 100% cả UC18 lẫn UC19!**).
  - [ ] **`Bảng 86` (`ChiTietGioHang`):** Thêm phương thức `timTheoMa(maChiTietGioHang: int): ChiTietGioHang` (khớp UC23 – R13).
  - [ ] **`Bảng 94` (`DanhMuc`):** Thêm phương thức `kiemTraTrungTen(ten: String, maDanhMuc: int): boolean` (khớp UC1, UC2 – R13).
  - [ ] **`Bảng 98` (`DienThoai`):**
    - Đổi kiểu trả về `capNhatGiaVonBinhQuan(...)` từ `boolean` → **`double`** (Q4 – R05).
    - Thêm `kiemTraTrungTen(ten: String, maDienThoai: int): boolean` (UC4, UC5 – R13).
    - Thêm `timTheoPhamVi(phamVi: String): List<DienThoai>` (UC11 – R13).
  - [ ] **`Bảng 100` (`DieuKienApDung`):** Đổi kiểu trả về `timTheoVoucher(maVoucher: String)` từ `List<DieuKienApDung>` → **`DieuKienApDung`** (R15).
  - [ ] **`Bảng 101 & Bảng 102` (`DonHang`):**
    - Ở Bảng 101: thêm **5 thuộc tính mới**: `tenNguoiNhan: String`, `sdtNguoiNhan: String`, `diaChiNhan: String` (R06), `lyDoHuy: String`, `ngayDuyet: Date` (R03).
    - Ở Bảng 102:
      - Đổi kiểu trả về `kiemTraTrangThai(maDonHang: int)` từ `boolean` → **`String`** (Q4 – R05).
      - Sửa chữ ký `luu(maKhachHang: int, maDiaChi: int, tenNguoiNhan: String, sdtNguoiNhan: String, diaChiNhan: String, maVoucher: String, phiVanChuyen: double, tongTien: double, trangThai: String): int` (9 tham số – R06).
      - Thêm **`demLuotDungVoucher(maKhachHang: int, maVoucher: String): int`** với mô tả rõ: *"Đếm số đơn hàng có `trangThai` khác 'Đã hủy' của khách hàng đã áp dụng mã giảm giá này"* (Q6 – R07).
      - Giữ `capNhatTrangThai(maDonHang: int, trangThai: String): boolean` (cho UC16, UC34, UC36) và thêm nạp chồng `capNhatTrangThai(maDonHang: int, trangThai: String, lyDoHuy: String, maNhanVienDuyet: int): boolean` (cho UC31 và UC33 – R03).
  - [ ] **`Bảng 103 & Bảng 104` (`GioHang`):**
    - Ở Bảng 103: thêm thuộc tính `phiVanChuyen: double` (R11).
    - Ở Bảng 104: **xóa** phương thức `kiemTraDangNhap(maPhien: String): boolean` (chuyển sang `PhienDangNhap` – R12).
  - [ ] **`Bảng 114` (`PhienDangNhap`):** Thêm phương thức **`kiemTraDangNhap(maPhien: String): boolean`** (R12).
  - [ ] **`Bảng 115 & Bảng 116` (`PhieuNhap`):**
    - Ở Bảng 115: thêm `lyDoTuChoi: String`, `ngayDuyet: Date` (R03).
    - Ở Bảng 116: giữ `capNhatTrangThai(maPhieuNhap: int, trangThai: String): boolean` (cho UC12 gửi duyệt) và thêm nạp chồng `capNhatTrangThai(maPhieuNhap: int, trangThai: String, lyDoTuChoi: String, maNguoiDuyet: int): boolean` (cho UC13 duyệt/từ chối – R03).
  - [ ] **`Bảng 117 & Bảng 118` (`PhieuXuat`):**
    - Ở Bảng 117: thêm `thoiDiemXacNhan: Date` (R03).
    - Ở Bảng 118: sửa `capNhatTrangThai(maPhieuXuat: int, trangThai: String, maNguoiXacNhan: int, thoiDiemXacNhan: Date): boolean` (cho UC16 – R03).
  - [ ] **`Bảng 122` (`TaiKhoan`):** Sửa `capNhatTrangThai(maTaiKhoan: int, trangThai: String, lyDoKhoa: String): boolean` (cho UC49, UC50 – R03).
  - [ ] **`Bảng 128` (`ThongSoKyThuat`):** Đổi kiểu trả về `locTheoThongSo(ram: int, rom: int, dungLuongPin: int)` từ `List<ThongSoKyThuat>` → **`List<Integer>`** (danh sách `maDienThoai` thỏa thông số – Q4, R05).
  - [ ] **`Bảng 129 & Bảng 130` (`TonKho`):**
    - Ở Bảng 129: **xóa** thuộc tính `maKho` (Q1 – R01); vẫn giữ `tonKhaDung` trên Class (sẽ loại ở bước sang RDM).
    - Ở Bảng 130: **xóa** phương thức `layTonTheoKho(maKho: int)` (R01); đổi kiểu trả về `kiemTraTonThieuGiuCho()` từ `boolean` → **`List<DonHang>`** (Q4 – R05); sửa kiểu trả về `timTheoDienThoai(maDienThoai: int)` từ `List<TonKho>` → **`TonKho`** (vì bỏ `maKho` nên mỗi điện thoại chỉ có 1 dòng tồn kho!). *(Đồng thời sửa bội số `DienThoai 1 – 0..1 TonKho` ở Bảng 135 dòng 22 & Bảng 168 dòng 3 cho chặt chẽ tuyệt đối!)*
  - [ ] **`Bảng 132` (`Voucher`):** **Xóa** `kiemTraLuotSuDung(maVoucher, maKhachHang)` và `ghiNhanLuotSuDung(maVoucher, maKhachHang)` (R07); thêm **`tangLuotDaDung(maVoucher: String): boolean`**; sửa tham số `hoanLuotSuDung(maDonHang: int)` → **`hoanLuotSuDung(maVoucher: String): boolean`**; đổi tham số `thoiGian: String` trong `luu` và `capNhat` thành `ngayBatDau: Date, ngayKetThuc: Date` cho khớp thuộc tính ở Bảng 131!
  - [ ] **`Bảng 133 & Bảng 134` (`YeuCauHuyDon`):**
    - Ở Bảng 133: thêm `lyDoTuChoi: String`, `ngayXuLy: Date` (R03).
    - Ở Bảng 134: sửa `capNhatTrangThai(maYeuCau: int, trangThai: String, lyDoTuChoi: String, maNhanVienXuLy: int): boolean` (UC36, UC37 – R03).
- [ ] **`Bảng 135` – Tổng hợp quan hệ giữa các lớp (tr. 146–148):**
  - Xóa 7 dòng `Phụ thuộc` (dòng 7, 8, 29, 30, 40, 41, 42) và xóa giải thích *"Phụ thuộc – ..."* dưới chân bảng.
  - Sửa dòng 12: `GioHang – ThanhToan` bội số `1 – 0..*`.
  - Sửa dòng 22: `DienThoai – TonKho` bội số `1 – 0..1` (do chỉ có 1 kho).
  - Thêm 8 dòng `Kết hợp` mới: `DanhMuc (0..1) – CanhBaoTonKho (0..*)`, `DienThoai (0..1) – CanhBaoTonKho (0..*)`, `DiaChiGiaoHang (0..1) – GioHang (0..*)`, và 5 dòng `NhanVien (0..1)` duyệt/xác nhận/xử lý `PhieuNhap`, `PhieuXuat`, `BienBanKiemKe`, `DonHang`, `YeuCauHuyDon` (`0..*`) → Tổng cộng Bảng 135 có **43 quan hệ thực chất** (35 cũ giữ lại + 8 mới, 0 phụ thuộc), mọi dòng đều ánh xạ sang FK hoặc bảng trung gian ở RDM!

**B. Phần Mô hình Dữ liệu Quan hệ RDM (`Mục 3.5`, tr. 149–170):**
- [ ] **`Mục 3.5.1` – Chọn lựa thuộc tính lưu trữ và ánh xạ sang RDM (tr. 149 – Barem bắt buộc của giảng viên!)** → Hiện đang viết *"toàn bộ thuộc tính của 29 lớp lưu trữ được giữ lại thành cột"* (coi như bỏ qua bước phân tích chọn lọc!) → Viết lại đoạn dẫn và chèn **Bảng Phân tích chọn lọc thuộc tính khi chuyển sang RDM**:
  - Loại bỏ **4 thuộc tính dẫn xuất** để đạt dạng chuẩn **3NF**: `TonKho.tonKhaDung` (`= tonThucTe - giuCho`), `ChiTietKiemKe.chenhLech` (`= soLuongThucTe - soLuongHeThong`), `GioHang.tongTamTinh` và `GioHang.tongTien` (biến động liên tục theo giỏ hàng, tính trực tiếp khi truy vấn).
  - Giữ lại có chủ đích (kèm lý do nghiệp vụ bảo toàn lịch sử / hiệu năng): `DonHang.tongTien`, `DonHang.phiVanChuyen`, `ChiTietDonHang.donGia` (chốt cứng giá trị tài chính tại thời điểm đặt); `DonHang.tenNguoiNhan, sdtNguoiNhan, diaChiNhan` (bản chụp địa chỉ giao hàng); `DienThoai.giaVonBinhQuan, giaBan` (chốt từ phiếu nhập đã duyệt và % lợi nhuận hiện hành); `Voucher.luotDaDung` (kiểm tra nhanh tổng lượt phát hành, đồng bộ tăng/giảm trong giao dịch đặt/hủy đơn).
  - Lớp `BaoCao`: không ánh xạ thành bảng; quan hệ `N–N` giữa `KhuyenMai` và `DienThoai` tách thành bảng trung gian `KhuyenMai_DienThoai` → Tổng số bảng vật lý = `29 + 1 = 30 bảng` đạt chuẩn **3NF**.
- [ ] **`Hình 78 – Hình 82` – 5 Sơ đồ RDM (tr. 150–154)** → Đang thiếu hiển thị các cột `[FK]` bên trong hộp bảng, thiếu 8 đường nối FK mới, đặt trên trang A3 và ghi sai chú thích *"xem Mục 3 của chương RDM"*, *"Viền nét đứt"* → Vẽ lại nền trắng rõ nét đặt trên trang **A4 ngang** (Hình 78) và **A4 dọc/ngang** (Hình 79–82):
  - Trong mỗi hộp bảng chỉ ghi `Tên cột : Kiểu SQL(độ dài)` + `[PK]` / `[FK]` (không nhét văn mô tả vào hình – `Lỗi.docx` dòng 71).
  - Xóa `maKho`, `tonKhaDung`, `chenhLech`, `tongTamTinh`, `GioHang.tongTien` trên hình.
  - Thêm đầy đủ các cột mới & vẽ đường nối Crow's Foot cho 8 FK mới (`CanhBaoTonKho` nối `DanhMuc`, `DienThoai` nên không còn bị đứng cô lập trên Hình 78, 80!).
  - Sửa câu chú thích thành *"xem Mục 3.5.4"*, xóa câu *"Viền nét đứt"*.
- [ ] **`Bảng 137` (tr. 155)** → Dòng 28 (`TonKho`): sửa mô tả từ *"Lưu tồn thực tế, giữ chỗ và tồn khả dụng"* → *"Lưu số lượng tồn thực tế và số lượng đang giữ chỗ của từng điện thoại"*.
- [ ] **`Bảng 138 – Bảng 167` – Cấu trúc chi tiết 30 bảng (tr. 156–166)** → Đang dùng kiểu `int`, `string`, `double`, `date`, `boolean` không có độ dài (vi phạm `Lỗi.docx` dòng 69) và cột thứ 4 chỉ ghi `Khóa` rời rạc (vi phạm `Lỗi.docx` dòng 77) → Sửa đồng bộ cả 30 bảng:
  1. Đổi tiêu đề 5 cột của cả 30 bảng thành: **`STT | Tên cột | Kiểu dữ liệu | Ràng buộc toàn vẹn (RBTV) | Mô tả`**.
  2. Đổi toàn bộ kiểu dữ liệu sang chuẩn MySQL có độ dài cụ thể theo **Mục 1.3.3** (`INT`, `VARCHAR(50/100/255)`, `DECIMAL(15,2)`, `DECIMAL(5,2)`, `DATE`, `DATETIME`, `TINYINT(1)`).
  3. Ghi đầy đủ RBTV vào cột thứ 4: `PK, AUTO_INCREMENT`, `FK, NOT NULL`, `FK, NULL`, `NOT NULL, UNIQUE`, `NOT NULL, CHECK (soLuong > 0)`, `NOT NULL, DEFAULT 'Hoạt động'`, `NOT NULL, DEFAULT 'Chờ duyệt'`... (nhớ dùng chuỗi tiếng Việt có dấu theo Xung đột #4!).
  4. Áp dụng chính xác việc xóa cột (`maKho`, `tonKhaDung`, `chenhLech`, `tongTamTinh`, `GioHang.tongTien`) và thêm cột mới ở 8 bảng: `BienBanKiemKe` (`Bảng 138`), `CanhBaoTonKho` (`Bảng 139`), `ChiTietKiemKe` (`Bảng 143`), `DonHang` (`Bảng 150`), `GioHang` (`Bảng 151`), `PhieuNhap` (`Bảng 158`), `PhieuXuat` (`Bảng 159`), `TonKho` (`Bảng 165`), `YeuCauHuyDon` (`Bảng 167`), đồng thời cập nhật dòng `Khóa ngoại:` ngay phía trên mỗi bảng!
- [ ] **`Bảng 168` – Danh sách quan hệ khóa ngoại (tr. 168–170)** → Hiện có 36 dòng → Sửa bội số dòng 3 (`TonKho.maDienThoai`) thành `1 – 0..1` (`UNIQUE`), dòng 25 (`ThanhToan.maGioHang`) thành `1 – 0..*`; bổ sung **8 dòng khóa ngoại mới** (`CanhBaoTonKho.maDanhMuc`, `CanhBaoTonKho.maDienThoai`, `GioHang.maDiaChi`, `DonHang.maNhanVienDuyet`, `PhieuNhap.maNguoiDuyet`, `PhieuXuat.maNguoiXacNhan`, `BienBanKiemKe.maNguoiDuyet`, `YeuCauHuyDon.maNhanVienXuLy` — cả 8 đều ghi chú `Cho phép NULL`) → Tổng cộng **44 khóa ngoại**, khớp 1–1 với 43 quan hệ ở Bảng 135 (trong đó 1 quan hệ `N–N` sinh ra 2 FK ở `KhuyenMai_DienThoai`: `42 + 2 = 44 FK` — toán học khớp tuyệt đối!).

#### 4. Điểm bắt buộc khớp với Bước 0 (Sync Guardrails)
- Công thức kiểm tra vàng của Gói 4:
  - `29 lớp Entity lưu trữ + 1 bảng trung gian KhuyenMai_DienThoai = 30 bảng RDM` (lớp `BaoCao` không ánh xạ).
  - `42 quan hệ 1–1 / 1–N trong Bảng 135 + 1 quan hệ N–N (2 FK) = 44 dòng khóa ngoại trong Bảng 168`.
  - `Ctrl+F` trong toàn bộ Mục 3.4 & 3.5: không còn từ `string`, `maKho`, `layTonTheoKho`, `kiemTraLuotSuDung`, `Phụ thuộc`.

---

### GÓI VIỆC 5 – THÀNH VIÊN 5: THIẾT KẾ GIAO DIỆN 28 MÀN HÌNH (`MỤC 3.6`), CHƯƠNG 4 DEMO & KIỂM THỬ, CHƯƠNG 5, TLTK & PHỤ LỤC B

#### 1. Phạm vi phụ trách chính xác
- **Phần Thiết kế Giao diện (`Mục 3.6`, tr. 171–200):**
  - Văn bản `Mục 3.6.1`, Danh sách 28 màn hình `Mục 3.6.2` (`Bảng 169`), Mô tả chi tiết 28 màn hình `Mục 3.6.3` (**`Hình 83 – Hình 110`** kèm các hình biểu mẫu con và **`Bảng 170 – Bảng 225`**).
- **Phần Cuối quyển (`tr. 201–205` & `tr. 218`):**
  - **Chương 4 – Cài đặt và Thử nghiệm (`Mục 4.1 – 4.5`):** `Bảng 226` (Môi trường), `Mục 4.2` (Cây thư mục BCE), `Mục 4.3.1` (Demo Phần A – `UC28 Đặt hàng`), `Mục 4.3.2` (`Bảng 227` + Truy vết code 1–1 Demo Phần B – `Quản lý Danh mục UC1–3` & `Quản lý Điện thoại UC4–6, UC10`), `Mục 4.4 – 4.5` (`Bảng 228` mở rộng 18 test case thực tế).
  - **Chương 5 – Kết luận và Hướng phát triển (`Mục 5.1 – 5.4`, tr. 203–204)**.
  - **Tài liệu tham khảo (`tr. 205`)** và **Phụ lục B – Bảng phân công (`Bảng 229`, tr. 218)**.

#### 2. Hình dung kết quả đầu ra của bước này
28 màn hình giao diện sạch 100% lỗi tên control kiểu `btnLu4`, chụp ảnh thực tế nền sáng rõ nét (kèm popup/form Thêm, Sửa, Quên mật khẩu, không lộ mật khẩu test), đủ control cho các trường dữ liệu mới và đủ cả 3 nhóm biến cố (`Load`, `Click`, `OnChange`) gọi đúng hàm Controller; Chương 4 và Chương 5 điền đầy đủ 100% số liệu thật, ảnh chạy thật của chương trình web hiện có trong workspace `D:\PTTK`, không còn bất kỳ dấu `[Nhóm điền]` hay vết tô vàng nào.

#### 3. Checklist sửa chữa chi tiết đến từng dòng / hình / bảng

**A. Phần Thiết kế Giao diện (`Mục 3.6`, tr. 171–200):**
- [ ] **`Mục 3.6.1` – Nguyên tắc thiết kế (tr. 171)** → Đang để sót câu nháp tự tố cáo mình: *"Lưu ý: các hình giao diện trong mục này là bản phác thảo bố cục (wireframe)... khi chương trình hoàn thiện, nhóm thay bằng ảnh chụp màn hình thực tế..."* → **Xóa bỏ hoàn toàn đoạn Lưu ý này!** Đồng thời bổ sung tiền tố `grd`, `rdo` vào danh sách tiền tố control ở đoạn trên.
- [ ] **Đổi tên toàn bộ 78 control bị lỗi mất dấu kèm số thứ tự (`Bảng 170 – Bảng 224`)** → Hiện 78/203 control bị lỗi xuất tự động như `btnLu4`, `btnSa5`, `btnngnhp6`, `btnKhiphckinhdoanh12`... → Tra cứu **Bảng Đổi tên 78 Control ở cuối file kế hoạch này** và sửa sạch 100% trên cả 28 màn hình; đồng thời đổi tên các `DataGridView` viết tắt tối nghĩa (`grdQldm` → `grdDanhMuc`, `grdQldt` → `grdDienThoai`, `grdSp` → `grdSanPham`, `grdPn` → `grdPhieuNhap`, `grdPx` → `grdPhieuXuat`, `grdKk` → `grdKiemKe`, `grdGh` → `grdGioHang`, `grdDh` → `grdDonHangTam`, `grdQldh` → `grdDonHang`, `grdYch` → `grdYeuCauHuy`).
- [ ] **`Bảng 170 – 171` (`Màn hình Quản lý danh mục`, tr. 172–173 – Demo Phần B!)** → Đang thiếu nút mở form Thêm và dòng STT 4 của Bảng 171 ghi sai *"Click nút 'Thêm danh mục' → `capNhatDanhMuc(...)`"* → Sửa chuẩn:
  - Ở Bảng 170: thêm `btnThemDanhMuc` (`Button` – Nút "Thêm danh mục" mở biểu mẫu nhập mới).
  - Ở Bảng 171: thêm dòng `Click nút "Thêm danh mục"` → mở biểu mẫu trống (UC1); sửa dòng STT 4 thành **`Click nút "Cập nhật"`** → gọi `capNhatDanhMuc(maDanhMuc, ten, moTa)` (UC2).
- [ ] **`Bảng 172 – 173` (`Màn hình Quản lý điện thoại`, tr. 173–174 – Demo Phần B!)** → Đang thiếu ô chọn Hãng (`hang`), gộp 7 thông số kỹ thuật vào 1 `txtThongSo`, thiếu event lọc ngừng kinh doanh → Sửa chuẩn:
  - Ở Bảng 172: thêm `btnThemDienThoai` (Nút "Thêm điện thoại"), thêm **`cboHang`** (`ComboBox` – Chọn hãng sản xuất); tách `txtThongSo` thành **7 control đúng thuộc tính lớp `ThongSoKyThuat`**: `txtManHinh`, `txtChip`, `numRAM`, `numROM`, `txtCamera`, `numDungLuongPin`, `cboHeDieuHanh`; cột `grdDienThoai` ghi rõ: *Mã, Hình ảnh, Tên điện thoại, Hãng, Danh mục, Giá bán, Trạng thái*.
  - Ở Bảng 173: sửa chữ ký hàm ở STT 2 & STT 4 có đủ tham số `hang`: `luuDienThoai(ten, maDanhMuc, hang, moTa, thongSo, hinhAnh)` và `capNhatDienThoai(maDienThoai, ten, maDanhMuc, hang, moTa, thongSo, hinhAnh)`; thêm biến cố **`Thay đổi trạng thái chkNgungKinhDoanh (CheckedChanged)`** → gọi `layDanhSachNgungKinhDoanh()` (khi tích chọn, UC10) hoặc `layDanhSachDienThoai()` (khi bỏ tích).
- [ ] **`Bảng 180 – 181` (`Màn hình Phiếu nhập kho`, tr. 178)** → Đang thiếu toàn bộ vùng nhập dòng hàng chi tiết của phiếu nhập (`UC12`) và cột người duyệt (`R03`) → Ở Bảng 180 bổ sung: `cboDienThoai` (chọn điện thoại nhập), `numSoLuong` (số lượng nhập), `numDonGiaNhap` (đơn giá nhập), `btnThemDongHang` (thêm dòng hàng vào bảng tạm), `grdChiTietNhap` (bảng dòng hàng phiếu nhập), đổi `txtLyDo` → `txtLyDoTuChoi`; cột của `grdPhieuNhap` thêm *Người duyệt, Ngày duyệt, Lý do từ chối*.
- [ ] **`Bảng 182 – 183` (`Màn hình Phiếu xuất kho`, tr. 179–180)** → Theo R03: cột của `grdPhieuXuat` bổ sung *Người xác nhận, Thời điểm xác nhận*; thêm `grdChiTietXuat` hiển thị các mặt hàng của phiếu xuất.
- [ ] **`Bảng 184 – 185` (`Màn hình Kiểm kê tồn kho`, tr. 180–181)** → Theo R01 (bỏ `maKho`) & R03: ở Bảng 184 sửa `txtLyDo` → `txtLyDoTuChoi`, cột danh sách biên bản hiện thêm *Người duyệt, Thời điểm duyệt, Lý do từ chối*; ở **Bảng 185 dòng STT 2** sửa `taoDotKiemKe(maKho, thoiDiem)` → **`taoDotKiemKe(thoiDiem)`**!
- [ ] **`Bảng 188 – 189` (`Màn hình Cấu hình ngưỡng tồn kho`, tr. 182)** → Theo R08: thêm `cboDanhMuc` và `cboDienThoai` (kích hoạt khi chọn phạm vi theo danh mục hoặc theo sản phẩm).
- [ ] **`Hình 93` & `Bảng 190 – 191` (`Màn hình Giỏ hàng`, tr. 183)** → Đang bị dư dấu phẩy `,Hình 93...` dính liền đoạn trên, và nhãn UC trên ảnh lệch với Bảng 169 → Xóa dấu `,` thừa, tách caption `Hình 93` thành đoạn riêng chuẩn style Caption; thống nhất `Use Case: UC23, UC24` khớp Bảng 169.
- [ ] **`Bảng 192 – 193` (`Màn hình Đặt hàng`, tr. 184)** → Theo R06: bổ sung `lblThongTinNhanHang` (hiển thị bản chụp Họ tên, SĐT, Địa chỉ người nhận sẽ chốt vào đơn hàng) và `lblPhiVanChuyen`, `lblTienGiamGia`, `lblTongThanhToan`.
- [ ] **`Bảng 198 – 201` (`Quản lý đơn hàng` & `Yêu cầu hủy đơn`, tr. 187–188) và `Bảng 212` (`Quản lý tài khoản`, tr. 193)** → Bổ sung cột hiển thị *Người duyệt/xử lý, Thời điểm duyệt/xử lý, Lý do hủy/từ chối/khóa* khớp R03; đảm bảo `Quản lý tài khoản` có `txtLyDoKhoa` cho `UC49`.
- [ ] **Bổ sung biến cố `Mở màn hình (Load)` còn thiếu cho 3 màn hình** → Thêm dòng STT 1 `Mở màn hình (Load)` cho: **Màn hình Đăng ký (`Bảng 205`)**, **Màn hình Đăng nhập (`Bảng 207`)**, và **Màn hình Tra cứu khách hàng (`Bảng 211`)** để đạt đủ **28/28 màn hình đều có biến cố Load**.
- [ ] **Bổ sung biến cố `OnChange` (`TextChanged` / `SelectedIndexChanged` / `ValueChanged` / `CheckedChanged`)** → Bổ sung tối thiểu 1 biến cố thay đổi giá trị cho các màn hình tương tác chính:
  - `Màn hình Sản phẩm (Bảng 175)`: `cboHang / cboRAM / cboROM / cboPin (SelectedIndexChanged)` → cập nhật bộ lọc.
  - `Màn hình Giỏ hàng (Bảng 191)`: `numSoLuong (ValueChanged)` → `capNhatSoLuong(maChiTietGioHang, soLuong)`.
  - `Màn hình Đặt hàng (Bảng 193)`: `cboDiaChi (SelectedIndexChanged)` → `chonDiaChi(maDiaChi)` & `tinhPhiVanChuyen(maDiaChi, tongTienHang)`.
  - `Màn hình Quản lý đơn hàng (Bảng 199)`: `cboTrangThai (SelectedIndexChanged)` → `layDanhSachDonHang(trangThai)`.
  - `4 Màn hình Báo cáo (Bảng 215, 217, 219)`: `dtpTuNgay / dtpDenNgay (ValueChanged)` → kiểm tra khoảng ngày hợp lệ.
- [ ] **Ghi chú `UC41 Đăng xuất` ở `Mục 3.6.3.20` (`Màn hình Tài khoản của tôi`, tr. 191)** → Thêm câu ghi chú rõ: *"Nút `btnDangXuat` được bố trí cố định trên thanh điều hướng chung của hệ thống nên cả 4 vai trò (Khách hàng, Nhân viên Bán hàng / CSKH, Nhân viên Kho, Ban Quản lý) đều thực hiện được UC41 từ bất kỳ màn hình làm việc nào."*
- [ ] **Chụp lại bộ ảnh `Hình 83 – Hình 110` + ảnh biểu mẫu con** → Hiện ảnh đang nền tối (dark mode) tốn mực khó nhìn khi in giấy trắng, có gắn chữ `(UC1-3)` giả trên góc giao diện, thiếu popup Thêm/Sửa, và **`Hình 101` (Đăng nhập) đang lộ dòng tài khoản test kèm mật khẩu `123456`** → Mở chương trình trên localhost (`http://localhost/...`), dùng **giao diện nền sáng (light theme)**, xóa nhãn `(UC...)` và xóa dòng gợi ý mật khẩu test trên màn hình đăng nhập; chụp lại rõ nét 28 màn hình chính kèm ảnh mở biểu mẫu Thêm/Sửa của các màn hình trọng tâm (`Quản lý danh mục`, `Quản lý điện thoại`, `Đăng nhập – tab Quên mật khẩu`, `Quản lý khuyến mãi`, `Quản lý mã giảm giá`).

**B. Phần Chương 4, Chương 5, Tài liệu tham khảo & Phụ lục B (`tr. 201–205` & `tr. 218`):**
- [ ] **Đầu Chương 4 (tr. 201)** → Xóa câu nhắc nội bộ *"Các nội dung được tô vàng... nhóm cần điền"* và bỏ toàn bộ màu tô vàng (`No Color`).
- [ ] **`Bảng 226` – Môi trường và công nghệ cài đặt (tr. 201)** → Điền đúng thực tế bộ code trong `D:\PTTK`:
  - Ngôn ngữ & Backend: `PHP 8.2 (RESTful API theo kiến trúc Boundary – Control – Entity, PDO)`
  - Frontend (Boundary): `HTML5, CSS3, JavaScript (ES6+, Fetch API)`
  - Hệ quản trị CSDL: `MySQL 8.0 (InnoDB, UTF8MB4, hỗ trợ Transaction ACID)`
  - Máy chủ chạy thử nghiệm: `XAMPP / Apache 2.4 (hoặc PHP Built-in Server)`
  - Công cụ mô hình hóa: `PlantUML, Draw.io`
  - Hệ điều hành & Trình duyệt: `Windows 11, Google Chrome / Microsoft Edge (phiên bản mới nhất)`
- [ ] **`Mục 4.2` – Hiện thực thiết kế thành mã nguồn (tr. 201)** → Chèn sơ đồ cây thư mục thực tế của project `D:\PTTK` chứng minh phân tầng BCE rõ ràng:
  - Tầng **Boundary**: `index.html`, `assets/main.js`, `admin/index.html`, `admin/js/admin.js` (28 màn hình).
  - Tầng **Control**: `api/controllers/*Ctrl.php` (đủ **18 lớp Controller** trùng tên 100% với Bảng 73).
  - Tầng **Entity**: `api/entities/*.php` (đủ **30 lớp Entity** trùng tên 100% với Bảng 74 & Class Diagram).
  - Tầng **CSDL**: `database/01_schema.sql` & `database/02_seed.sql` (30 bảng RDM).
- [ ] **`Mục 4.3.1` – Chức năng nghiệp vụ chính (`1,0 điểm` – tr. 201)** → Xóa `[Nhóm điền...]`, trình bày kịch bản demo **`UC28 – Đặt hàng`** (kết hợp liền mạch `UC22 Thêm vào giỏ` → `UC25 Chọn địa chỉ` → `UC26 Tính phí vận chuyển` → `UC30 Áp dụng mã giảm giá` → `UC29 Thanh toán` → `UC28 Lưu đơn hàng, giữ chỗ tồn kho & chụp địa chỉ người nhận`), đính kèm **5–6 ảnh chụp màn hình chạy thực tế** (trước & sau khi đặt đơn thấy rõ `giuCho` tăng, `tonKhaDung` giảm và đơn mới ở trạng thái `"Chờ duyệt"`).
- [ ] **`Mục 4.3.2` – Chức năng quản lý được chỉ định (`2,0 điểm = 0,5đ chạy + 1,5đ đúng thiết kế` – tr. 201)** → Giữ `Bảng 227` và trình bày chi tiết chuỗi truy vết 1–1 từ **Use Case → Đặc tả → Sequence Diagram → Class Diagram → Code PHP thực tế** cho cụm **`Quản lý Sản phẩm`** (`Quản lý Danh mục UC1–UC3` qua `QuanLyDanhMucCtrl.php` + `DanhMuc.php` và `Quản lý Điện thoại UC4–UC6, UC10` qua `QuanLyDienThoaiCtrl.php` + `DienThoai.php` + `ThongSoKyThuat.php`, kèm tìm kiếm/lọc `UC7–UC8`), trích dẫn ngắn gọn tên hàm trong code khớp từng chữ với Sequence Diagram và đính kèm ảnh chạy thực tế (thêm mới, sửa nạp từ API, chặn xóa danh mục còn SP, xóa mềm điện thoại đã có giao dịch sang `"Ngừng kinh doanh"` và khôi phục kinh doanh `UC10`).
- [ ] **`Bảng 228` & `Mục 4.5` – Thử nghiệm và đánh giá (tr. 202)** → Mở rộng `Bảng 228` từ 4 dòng mẫu lên **16 – 20 ca kiểm thử (Test Cases)** bao phủ cả dòng chính và dòng phụ của `UC1–UC10` (Demo quản lý), `UC28–UC30` (Demo nghiệp vụ), `UC13` (Nhập kho tính giá vốn), `UC16` (Xuất kho trừ tồn), `UC31/UC36` (Hủy đơn nhả giữ chỗ & hoàn lượt voucher), `UC40` (Đăng nhập phân quyền RBAC); điền đầy đủ cột **`Kết quả thực tế`** và đánh dấu **`Đạt`** cho toàn bộ các ca kiểm thử; viết `Mục 4.5` tổng kết tỷ lệ đạt `100%` trên bộ dữ liệu kiểm thử.
- [ ] **`Chương 5` – Kết luận và Hướng phát triển (tr. 203–204):**
  - `Mục 5.1`: Kiểm tra khớp con số **64 Use Case, 64 Sequence Diagram, 18 Controller, 30 lớp (29 Entity lưu trữ + `BaoCao`), 30 bảng RDM đạt chuẩn 3NF, 28 màn hình giao diện**, và bổ sung câu tổng kết kết quả cài đặt & kiểm thử ở Chương 4.
  - `Mục 5.3 (Hạn chế)`: Xóa `[Nhóm bổ sung...]`; nêu rõ 4 hạn chế thực tế: (1) Quản lý tồn kho theo số lượng mẫu máy trên 1 kho duy nhất, chưa quản lý theo từng mã IMEI/Serial và đa chi nhánh kho; (2) Thanh toán trực tuyến (VNPAY/MoMo) và tính phí vận chuyển mới ở mức cấu hình/mô phỏng, chưa kết nối cổng thật; (3) Hai phân hệ Bảo hành – Đổi trả và Giám sát hạ tầng mới dừng ở mức phân tích định hướng; (4) Chưa tích hợp gửi thông báo thời gian thực (WebSocket/Email/SMS thực tế).
  - `Mục 5.4 (Hướng phát triển)`: Viết 4 gạch đầu dòng **đối ứng 1–1** với đúng 4 hạn chế ở Mục 5.3 (bổ sung quản lý IMEI & đa kho; tích hợp API VNPAY/MoMo/GHN; thiết kế & cài đặt phân hệ Bảo hành điện tử theo IMEI; tích hợp thông báo đẩy & gửi OTP qua Email/SMS Gateway).
- [ ] **`Tài liệu tham khảo` (tr. 205)** → Xóa dòng `[2] [Nhóm bổ sung...]`! Viết danh mục **6 tài liệu chuẩn IEEE**, sắp xếp đánh số `[1]` đến `[6]` (đồng thời lưu các file PDF tài liệu tham khảo được vào thư mục `Tham Khao/` khi nén `.zip`):
  - `[1]` Hoàng Mạnh Hà, *Bài giảng và Quy định đồ án học phần Phân tích thiết kế hướng đối tượng*, Khoa Công nghệ thông tin, Trường Đại học Sài Gòn, 2026.
  - `[2]` A. Dennis, B. H. Wixom, and D. Tegarden, *Systems Analysis and Design: An Object-Oriented Approach with UML*, 6th ed. Hoboken, NJ, USA: Wiley, 2020.
  - `[3]` C. Larman, *Applying UML and Patterns: An Introduction to Object-Oriented Analysis and Design and Iterative Development*, 3rd ed. Upper Saddle River, NJ, USA: Prentice Hall, 2004.
  - `[4]` Object Management Group (OMG), *OMG Unified Modeling Language (OMG UML) Specification, Version 2.5.1*, 2017. [Online]. Available: https://www.omg.org/spec/UML/2.5.1/ (truy cập ngày 25/11/2026).
  - `[5]` PlantUML Team, *PlantUML Language Reference Guide*, 2026. [Online]. Available: https://plantuml.com/guide (truy cập ngày 25/11/2026).
  - `[6]` Oracle Corporation, *MySQL 8.0 Reference Manual*, 2026. [Online]. Available: https://dev.mysql.com/doc/refman/8.0/en/ (truy cập ngày 25/11/2026).
- [ ] **`Phụ lục B – Bảng 229` (tr. 218)** → Điền rõ nội dung công việc thực hiện của cả 5 thành viên tương ứng 5 Gói công việc (Phân tích thiết kế + Cài đặt + Kiểm thử + Hoàn thiện báo cáo) và điền cột `Tỷ lệ (%)` là `20%` cho mỗi thành viên (tổng cộng = `100%`, sau khi cả nhóm thống nhất).

#### 4. Điểm bắt buộc khớp với Bước 0 (Sync Guardrails)
- Mọi tên hàm trong cột **`Hàm được gọi`** của 28 bảng biến cố (`Bảng 171 – Bảng 225`) phải khớp từng chữ với phần **"Hàm của các Controller"** trong Phụ lục A (của TV3).
- `Ctrl+F` trên toàn bộ phần Gói 5: `[` (placeholder ngoặc vuông như `[điền]`, `[Nhóm`, `[ ]`) = **0 kết quả**; `wireframe` = **0 kết quả**; `123456` trên ảnh chụp = **0 kết quả**.

---

## PHẦN 3: LỘ TRÌNH GHÉP BÀI, KIỂM TRA CHÉO & ĐÓNG GÓI NỘP (27/11 – 04/12/2026)

### 3.1. Lịch trình phối hợp song song 5 người (Không ai chờ ai)

| Mốc thời gian | Hoạt động của cả 5 thành viên | Điểm chốt đồng bộ |
| :--- | :--- | :--- |
| **Ngày 1 (27–28/11)** | - Sao lưu bản gốc thành `BaoCao_CuoiKi_Nhom08_OOAD_BACKUP.docx`.<br>- Cả 5 thành viên đọc kỹ **PHẦN 1 (Bước 0)** của file này và bắt tay sửa song song trên 5 nhánh độc lập (`Gói 1` → `Gói 5`). | Không ai tự ý đổi tên lớp, tên hàm hay cột ngoài Hợp đồng Bước 0 nếu chưa báo nhóm. |
| **Ngày 2–3 (29–30/11)** | - **TV1:** Hoàn tất BFD, Hình 2–8 và 64 Bảng đặc tả.<br>- **TV2 & TV3:** Hoàn tất sửa 64 file `.puml`, xuất 64 ảnh PNG sắc nét và lập xong Phụ lục A mới.<br>- **TV4:** Hoàn tất 5 hình Class, 5 hình RDM và Bảng 74–168.<br>- **TV5:** Hoàn tất đổi tên 78 control, chụp lại 28 màn hình theme sáng + viết xong Chương 4, 5, TLTK, Phụ lục B. | Cuối ngày 30/11: **TV3 (Phụ lục A)** và **TV4 (Bảng 76–134 Class)** đối chiếu nhanh danh sách hàm Entity trong 15 phút để đảm bảo khớp 100%. |
| **Ngày 4 (01/12)** | - **Ghép Master File:** 1 bạn (gợi ý TV4 hoặc TV5) ghép 5 phần vào bản `BaoCao_CuoiKi_Nhom08_OOAD_FINAL.docx`.<br>- Chuẩn hóa khổ giấy **100% A4** (đổi toàn bộ 18 trang đang khổ A3 về A4; chỉ đặt Section Break **A4 Ngang – Landscape** cho các sơ đồ tổng quan lớn như `Hình 73`, `Hình 78`). | Bấm `Ctrl+A` → `F9` → *Update entire table* cho Mục lục, Danh sách bảng, Danh sách hình (sửa lỗi Hình 73 bị nhảy số trang). |
| **Ngày 5 (02–03/12)** | - Chạy **Bảng Kiểm tra chéo 12 tiêu chí** ở Mục 3.2 bên dưới.<br>- Xóa Document Metadata (`File → Info → Inspect Document`, xóa dòng *"generated by python-docx"*), xuất file `.pdf`, duyệt từng trang từ đầu đến cuối.<br>- Đi in thử 1 trang sơ đồ lớn nhất + in chính thức **01 quyển đóng bìa chuẩn Khóa luận tốt nghiệp**. | Kiểm tra chữ nhỏ nhất trên mọi Sequence/Class/RDM sau khi in ra giấy A4 đọc rõ bằng mắt thường (`≥ 8pt`). |
| **Ngày 6 (04/12 – Trước 23:59)** | - Đóng gói file `.zip` đúng cấu trúc quy định của assignment trên `hoctructuyen`, giải nén thử ở máy khác kiểm tra không lỗi file, upload nộp trước **23:59 ngày 04/12/2026** (sớm 1 ngày trước hạn cứng 05/12) và chụp màn hình xác nhận nộp thành công.<br>- Cả 5 thành viên chuẩn bị kịch bản vấn đáp Demo (`UC28` & `UC1–UC10`) và **ký tên đầy đủ vào danh sách dự thi**! | **Cảnh báo từ quy chế thầy:** Sinh viên không ký tên vào danh sách dự thi coi như vắng thi, điểm báo cáo không có giá trị! |

---

### 3.2. Bảng Kiểm tra chéo 12 tiêu chí bắt buộc trước khi xuất PDF & In quyển

Trước khi mang đi in, cả nhóm cùng mở file Word/PDF cuối cùng và tick đủ 12 dòng kiểm định định lượng sau:

- [ ] **1. Con số 64 đồng nhất tuyệt đối:** Số UC trong `Bảng 4` = Số UC phủ trong bảng `FR` (`Mục 2.3.2`) = Tổng số oval chi tiết trên `Hình 3–8` = Số bảng đặc tả (`Bảng 8–71`) = Số dòng `Bảng 73` = Số Sequence Diagram (`Hình 9–72`) = Số UC phủ trên 28 màn hình (`Bảng 169`) = **`64`**.
- [ ] **2. Khớp hàm 100% giữa SD – Phụ lục A – Class:** Mọi lời gọi hàm vào Entity trên `Hình 9–72` đều có mặt trong `Phụ lục A` và trùng 100% tên hàm, số tham số, kiểu trả về với `Bảng 76–134` (`Mục 3.4.3`) và ngăn thứ 3 trên `Hình 74–77`.
- [ ] **3. Sạch lỗi cấm trên Use Case & Sequence:** `Hình 2` chỉ vẽ mức nhóm chức năng; `Hình 6` không có `UC42 <<extend>> UC40`; cả 64 SD không có dấu `X` trên Actor/Boundary, không có nhánh `Hủy` giao diện hay `Lỗi kết nối CSDL`, không mất viền khung `alt`.
- [ ] **4. Sạch lỗi cấm trên Class Diagram:** `Hình 73–77` không có lớp `...Ctrl`, **0 đường nét đứt `..>` (Dependency)**, **0 mũi tên chiều `-->` / `▸` / `◂`**, đủ hình thoi `◆` (Composition), `◇` (Aggregation) và bội số 2 đầu.
- [ ] **5. Con số 30 – 43 – 44 giữa Class & RDM:** `29 lớp Entity lưu trữ + 1 bảng trung gian KhuyenMai_DienThoai = 30 bảng RDM`; `Mục 3.5.1` có bảng loại bỏ 4 thuộc tính dẫn xuất đạt 3NF; `42 quan hệ 1–N/1–1 + 1 quan hệ N–N (43 dòng Bảng 135) = 44 khóa ngoại trong Bảng 168`; không còn bảng `CanhBaoTonKho` đứng cô lập trên RDM.
- [ ] **6. Chuẩn bảng mô tả RDM:** Cả 30 bảng (`Bảng 138–167`) có cột **`Ràng buộc toàn vẹn (RBTV)`**, mọi cột `VARCHAR` đều ghi rõ độ dài `VARCHAR(n)`, tiền tệ dùng `DECIMAL(15,2)`.
- [ ] **7. Chuẩn 28 màn hình UI:** Đủ 28/28 màn hình có ảnh nền sáng rõ nét + bảng Thành phần + bảng Biến cố (có đủ `Load`, `Click`, `OnChange`); sạch 100% tên control lỗi kiểu `btnLu4`; `Hình 101` không lộ mật khẩu test.
- [ ] **8. Tìm kiếm từ khóa rác bằng `Ctrl+F` phải ra `0 kết quả`:**
  - `maKho` = **0** | `layTonTheoKho` = **0** | `kiemTraLuotSuDung` = **0** | `ghiNhanLuotSuDung` = **0**
  - `Lỗi kết nối cơ sở dữ liệu` = **0** | `Nhánh lược bỏ` = **0** | `Quy tắc 2.1` = **0** | `Quy tắc 3` = **0**
  - `[Nhóm` = **0** | `[điền]` = **0** | `[ ]` = **0** | `wireframe` = **0** | `Lần 01` = **0**
- [ ] **9. Khổ giấy & Bố cục trang:** `0 trang A3` (100% trang trong PDF là khổ A4 dọc hoặc A4 ngang); bật `Keep with next` cho mọi Heading và Caption trên bảng để không bị tiêu đề nằm cuối trang trước, bảng nhảy sang trang sau; mọi caption Hình nằm dưới hình, caption Bảng nằm trên bảng.
- [ ] **10. Đồng bộ Code Demo:** Chạy lại trên trình duyệt trước ngày thi cả 2 phần Demo: **Phần A (`UC28 Đặt hàng`)** và **Phần B (`Quản lý Danh mục UC1–3` & `Quản lý Điện thoại UC4–6, UC10`)** đảm bảo chạy mượt 100% và khớp từng tên lớp, tên hàm đã in trong quyển báo cáo!
- [ ] **11. Cấu trúc gói `.zip` nộp `hoctructuyen`:** Đúng tên file nén theo quy định trên assignment của thầy, bên trong gồm:
  - `BaoCao/` → File báo cáo hoàn chỉnh (`.docx` và `.pdf`).
  - `SourceCode/` → Mã nguồn sạch của hệ thống (`admin/`, `api/`, `assets/`, `database/01_schema.sql`, `database/02_seed.sql`, `index.html`, `config.js`, `README.md` hướng dẫn cài đặt chạy demo — **nhớ xóa `.git/`, thư mục nháp `scratch/` hoặc file tạm** trước khi nén!).
  - `Diagrams/` → Toàn bộ file nguồn sơ đồ gốc (`.puml`, `.drawio`) và ảnh xuất `.png`.
  - `ThamKhao/` → Các tài liệu PDF đã trích dẫn trong mục Tài liệu tham khảo.
- [ ] **12. Ký tên dự thi:** Cả 5 thành viên Nhóm 08 kiểm tra thẻ sinh viên và ký tên đầy đủ vào danh sách dự thi khi nộp quyển / báo cáo demo.

---

## PHỤ LỤC TRA CỨU NHANH (DÀNH CHO THÀNH VIÊN 5): BẢNG ĐỔI TÊN 78 CONTROL GIAO DIỆN BỊ LỖI

Thành viên 5 dùng `Ctrl+F` tìm từng tên ở cột **`Tên đang sai`** trong `Mục 3.6.3` (`Bảng 170 – Bảng 224`) và thay thế chính xác bằng cột **`Tên chuẩn sau khi sửa`**:

| STT | Màn hình | Tên đang sai | Nhãn nút | Tên chuẩn sau khi sửa |
| :-: | :--- | :--- | :--- | :--- |
| 1 | Quản lý danh mục (`Bảng 170`) | `btnLu4` | Lưu | `btnLuu` |
| 2 | Quản lý danh mục (`Bảng 170`) | `btnSa5` | Sửa | `btnSua` |
| 3 | Quản lý danh mục (`Bảng 170`) | `btnCpnht6` | Cập nhật | `btnCapNhat` |
| 4 | Quản lý danh mục (`Bảng 170`) | `btnXa7` | Xóa | `btnXoa` |
| 5 | Quản lý điện thoại (`Bảng 172`) | `btnLu8` | Lưu | `btnLuu` |
| 6 | Quản lý điện thoại (`Bảng 172`) | `btnSa9` | Sửa | `btnSua` |
| 7 | Quản lý điện thoại (`Bảng 172`) | `btnCpnht10` | Cập nhật | `btnCapNhat` |
| 8 | Quản lý điện thoại (`Bảng 172`) | `btnXa11` | Xóa | `btnXoa` |
| 9 | Quản lý điện thoại (`Bảng 172`) | `btnKhiphckinhdoanh12` | Khôi phục kinh doanh | `btnKhoiPhucKinhDoanh` |
| 10 | Sản phẩm (`Bảng 174`) | `btnTmkim9` | Tìm kiếm | `btnTimKiem` |
| 11 | Sản phẩm (`Bảng 174`) | `btnLc10` | Lọc | `btnLoc` |
| 12 | Sản phẩm (`Bảng 174`) | `btnXemchitit11` | Xem chi tiết | `btnXemChiTiet` |
| 13 | Chi tiết sản phẩm (`Bảng 176`) | `btnThmvogihng5` | Thêm vào giỏ hàng | `btnThemVaoGioHang` |
| 14 | Chính sách giá (`Bảng 178`) | `btnLu6` | Lưu | `btnLuu` |
| 15 | Phiếu nhập kho (`Bảng 180`) | `btnLuphiu8` | Lưu phiếu | `btnLuuPhieu` |
| 16 | Phiếu nhập kho (`Bảng 180`) | `btnGiduyt9` | Gửi duyệt | `btnGuiDuyet` |
| 17 | Phiếu nhập kho (`Bảng 180`) | `btnDuyt10` | Duyệt | `btnDuyet` |
| 18 | Phiếu nhập kho (`Bảng 180`) | `btnTchi11` | Từ chối | `btnTuChoi` |
| 19 | Phiếu nhập kho (`Bảng 180`) | `btnLc12` | Lọc | `btnLoc` |
| 20 | Phiếu nhập kho (`Bảng 180`) | `btnXemchitit13` | Xem chi tiết | `btnXemChiTiet` |
| 21 | Phiếu xuất kho (`Bảng 182`) | `btnTophiu7` | Tạo phiếu | `btnTaoPhieu` |
| 22 | Phiếu xuất kho (`Bảng 182`) | `btnLuphiu8` | Lưu phiếu | `btnLuuPhieu` |
| 23 | Phiếu xuất kho (`Bảng 182`) | `btnXcnhnxutkho9` | Xác nhận xuất kho | `btnXacNhanXuatKho` |
| 24 | Phiếu xuất kho (`Bảng 182`) | `btnLc10` | Lọc | `btnLoc` |
| 25 | Phiếu xuất kho (`Bảng 182`) | `btnXemchitit11` | Xem chi tiết | `btnXemChiTiet` |
| 26 | Kiểm kê tồn kho (`Bảng 184`) | `btnTotkimk6` | Tạo đợt kiểm kê | `btnTaoDotKiemKe` |
| 27 | Kiểm kê tồn kho (`Bảng 184`) | `btnLunhp7` | Lưu nháp | `btnLuuNhap` |
| 28 | Kiểm kê tồn kho (`Bảng 184`) | `btnHontt8` | Hoàn tất | `btnHoanTat` |
| 29 | Kiểm kê tồn kho (`Bảng 184`) | `btnDuyt9` | Duyệt | `btnDuyet` |
| 30 | Kiểm kê tồn kho (`Bảng 184`) | `btnTchi10` | Từ chối | `btnTuChoi` |
| 31 | Cảnh báo tồn kho (`Bảng 186`) | `btnLc3` | Lọc | `btnLoc` |
| 32 | Cảnh báo tồn kho (`Bảng 186`) | `btnXemchitit4` | Xem chi tiết | `btnXemChiTiet` |
| 33 | Cấu hình ngưỡng tồn kho (`Bảng 188`) | `btnLu6` | Lưu | `btnLuu` |
| 34 | Giỏ hàng (`Bảng 190`) | `btnCpnhtslng3` | Cập nhật số lượng | `btnCapNhatSoLuong` |
| 35 | Giỏ hàng (`Bảng 190`) | `btnXa4` | Xóa | `btnXoa` |
| 36 | Giỏ hàng (`Bảng 190`) | `btnthng5` | Đặt hàng | `btnDatHang` |
| 37 | Đặt hàng (`Bảng 192`) | `btnLuachmi8` | Lưu địa chỉ mới | `btnLuuDiaChiMoi` |
| 38 | Đặt hàng (`Bảng 192`) | `btnChnach9` | Chọn địa chỉ | `btnChonDiaChi` |
| 39 | Đặt hàng (`Bảng 192`) | `btnpdng10` | Áp dụng | `btnApDung` |
| 40 | Đặt hàng (`Bảng 192`) | `btnBpdng11` | Bỏ áp dụng | `btnBoApDung` |
| 41 | Đặt hàng (`Bảng 192`) | `btnthng12` | Đặt hàng | `btnDatHang` |
| 42 | Đặt hàng (`Bảng 192`) | `btnThanhton13` | Thanh toán | `btnThanhToan` |
| 43 | Cấu hình phí vận chuyển (`Bảng 194`) | `btnLu6` | Lưu | `btnLuu` |
| 44 | Đơn hàng của tôi (`Bảng 196`) | `btnHyn3` | Hủy đơn | `btnHuyDon` |
| 45 | Đơn hàng của tôi (`Bảng 196`) | `btnGiyucuhy4` | Gửi yêu cầu hủy | `btnGuiYeuCauHuy` |
| 46 | Quản lý đơn hàng (`Bảng 198`) | `btnDuytn3` | Duyệt đơn | `btnDuyetDon` |
| 47 | Quản lý đơn hàng (`Bảng 198`) | `btnCpnhttin4` | Cập nhật tiến độ | `btnCapNhatTienDo` |
| 48 | Yêu cầu hủy đơn (`Bảng 200`) | `btnXemxl3` | Xem xử lý | `btnXemXuLy` |
| 49 | Yêu cầu hủy đơn (`Bảng 200`) | `btnXcnhnhy4` | Xác nhận hủy | `btnXacNhanHuy` |
| 50 | Yêu cầu hủy đơn (`Bảng 200`) | `btnTchi5` | Từ chối | `btnTuChoi` |
| 51 | Hoàn tiền (`Bảng 202`) | `btnYucuhontin3` | Yêu cầu hoàn tiền | `btnYeuCauHoanTien` |
| 52 | Đăng ký (`Bảng 204`) | `btnngk5` | Đăng ký | `btnDangKy` |
| 53 | Đăng nhập (`Bảng 206`) | `btnngnhp6` | Đăng nhập | `btnDangNhap` |
| 54 | Đăng nhập (`Bảng 206`) | `btnGimxcthc7` | Gửi mã xác thực | `btnGuiMaXacThuc` |
| 55 | Đăng nhập (`Bảng 206`) | `btntlimtkhu8` | Đặt lại mật khẩu | `btnDatLaiMatKhau` |
| 56 | Tài khoản của tôi (`Bảng 208`) | `btnCpnhths8` | Cập nhật hồ sơ | `btnCapNhatHoSo` |
| 57 | Tài khoản của tôi (`Bảng 208`) | `btnCpnhtach9` | Cập nhật địa chỉ | `btnCapNhatDiaChi` |
| 58 | Tài khoản của tôi (`Bảng 208`) | `btnXemchititn10` | Xem chi tiết đơn | `btnXemChiTietDon` |
| 59 | Tài khoản của tôi (`Bảng 208`) | `btnngxut11` | Đăng xuất | `btnDangXuat` |
| 60 | Tra cứu khách hàng (`Bảng 210`) | `btnTm3` | Tìm | `btnTim` |
| 61 | Tra cứu khách hàng (`Bảng 210`) | `btnXemlchsmua4` | Xem lịch sử mua | `btnXemLichSuMua` |
| 62 | Quản lý tài khoản (`Bảng 212`) | `btnTmkim8` | Tìm kiếm | `btnTimKiem` |
| 63 | Quản lý tài khoản (`Bảng 212`) | `btnKha9` | Khóa | `btnKhoa` |
| 64 | Quản lý tài khoản (`Bảng 212`) | `btnMkha10` | Mở khóa | `btnMoKhoa` |
| 65 | Quản lý tài khoản (`Bảng 212`) | `btnTonhnvin11` | Tạo nhân viên | `btnTaoNhanVien` |
| 66 | Quản lý tài khoản (`Bảng 212`) | `btnCpnhtnhnvin12` | Cập nhật nhân viên | `btnCapNhatNhanVien` |
| 67 | Thống kê doanh thu (`Bảng 214`) | `btnXemboco4` | Xem báo cáo | `btnXemBaoCao` |
| 68 | Thống kê đơn hàng (`Bảng 216`) | `btnXemboco4` | Xem báo cáo | `btnXemBaoCao` |
| 69 | Báo cáo sản phẩm bán chạy (`Bảng 218`) | `btnLc5` | Lọc | `btnLoc` |
| 70 | Quản lý khuyến mãi (`Bảng 222`) | `btnLu7` | Lưu | `btnLuu` |
| 71 | Quản lý khuyến mãi (`Bảng 222`) | `btnSa8` | Sửa | `btnSua` |
| 72 | Quản lý khuyến mãi (`Bảng 222`) | `btnCpnht9` | Cập nhật | `btnCapNhat` |
| 73 | Quản lý khuyến mãi (`Bảng 222`) | `btnXa10` | Xóa | `btnXoa` |
| 74 | Quản lý mã giảm giá (`Bảng 224`) | `btnTo10` | Tạo | `btnTao` |
| 75 | Quản lý mã giảm giá (`Bảng 224`) | `btnLcXem11` | Lọc/Xem | `btnLocXem` |
| 76 | Quản lý mã giảm giá (`Bảng 224`) | `btnCpnht12` | Cập nhật | `btnCapNhat` |
| 77 | Quản lý mã giảm giá (`Bảng 224`) | `btnKha13` | Khóa | `btnKhoa` |
| 78 | Quản lý mã giảm giá (`Bảng 224`) | `btnXa14` | Xóa | `btnXoa` |
