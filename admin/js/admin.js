// ============================================================================
// admin/js/admin.js
// Giao diện Quản trị Nội bộ BCE + RBAC 3 Actor + Hộp Thông báo thời gian thực
// ============================================================================

(function () {
  const KHOA_PHIEN = 'ps_ma_phien_noi_bo';
  const VAI_TRO_NOI_BO = ['Ban Quản lý', 'Nhân viên Kho', 'Nhân viên Bán hàng / CSKH'];

  const trangThai = {
    maPhien: localStorage.getItem(KHOA_PHIEN) || '',
    taiKhoan: null,
    tabHienTai: '',
    boDemThongBao: null,
    danhSachSanPhamCache: [],
    danhSachDanhMucCache: [],
  };

  // Menu phân quyền chặt chẽ theo 3 Actor nội bộ
  const MENU_THEO_VAI_TRO = {
    'Ban Quản lý': [
      { ma: 'bao-cao', nhan: 'Báo cáo & Thống kê (UC53-56)' },
      { ma: 'danh-muc', nhan: 'Quản lý Danh mục (UC1-3)' },
      { ma: 'dien-thoai', nhan: 'Quản lý Điện thoại (UC4-6,10)' },
      { ma: 'chinh-sach-gia', nhan: 'Chính sách Giá & LN (UC11)' },
      { ma: 'duyet-phieu-nhap', nhan: 'Duyệt Phiếu nhập (UC13)' },
      { ma: 'duyet-kiem-ke', nhan: 'Duyệt Kiểm kê & Ngưỡng (UC19,21)' },
      { ma: 'phi-van-chuyen', nhan: 'Phí vận chuyển (UC27)' },
      { ma: 'khuyen-mai', nhan: 'Khuyến mãi (UC57-59)' },
      { ma: 'voucher', nhan: 'Mã giảm giá / Voucher (UC60-64)' },
      { ma: 'tai-khoan', nhan: 'Quản lý Tài khoản (UC48-52)' },
    ],
    'Nhân viên Kho': [
      { ma: 'canh-bao-kho', nhan: 'Cảnh báo Tồn kho (UC20)' },
      { ma: 'phieu-nhap', nhan: 'Phiếu nhập kho (UC12,14)' },
      { ma: 'phieu-xuat', nhan: 'Phiếu xuất kho (UC15-17)' },
      { ma: 'kiem-ke', nhan: 'Kiểm kê kho (UC18)' },
    ],
    'Nhân viên Bán hàng / CSKH': [
      { ma: 'don-hang', nhan: 'Quản lý Đơn hàng (UC33-34)' },
      { ma: 'huy-don', nhan: 'Yêu cầu Hủy & Hoàn tiền (UC35-38)' },
      { ma: 'khach-hang', nhan: 'Tra cứu Khách hàng (UC47)' },
    ],
  };

  function dinhDangTien(so) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(Number(so) || 0)) + ' đ';
  }

  function lopTrangThai(tt) {
    if (['Đã duyệt', 'Hoạt động', 'Đang kinh doanh', 'Đã xuất', 'Hoàn tất', 'Đã giao thành công', 'Đang áp dụng', 'Đang hiệu lực', 'Thành công', 'Đã hoàn tiền'].includes(tt)) return 'tt-xanh';
    if (['Chờ duyệt', 'Chờ xử lý', 'Đang đóng gói', 'Đã giao shipper', 'Đang giao', 'Cần hoàn tiền', 'Sắp hết'].includes(tt)) return 'tt-vang';
    if (['Bị khóa', 'Ngừng kinh doanh', 'Đã hủy', 'Đã từ chối', 'Dưới tối thiểu', 'Vượt tối đa', 'Cần kiểm lại', 'Hoàn tiền lỗi', 'Đã khóa'].includes(tt)) return 'tt-do';
    return 'tt-xam';
  }

  function hienThongBaoToast(noiDung, thanhCong = true) {
    const el = document.getElementById('thanh-phan-hoi');
    el.textContent = noiDung;
    el.className = 'thanh-toast ' + (thanhCong ? 'toast-ok' : 'toast-loi');
    clearTimeout(el._timer);
    el._timer = setTimeout(() => el.classList.add('an'), 4500);
  }

  async function goiApi(duongDan, tuyChon = {}) {
    const headers = Object.assign({}, tuyChon.headers || {});
    if (trangThai.maPhien) {
      headers['X-Ma-Phien'] = trangThai.maPhien;
    }
    if (!(tuyChon.body instanceof FormData) && tuyChon.body && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }
    const res = await fetch('/api/' + duongDan, Object.assign({}, tuyChon, { headers }));
    const json = await res.json().catch(() => ({ thanhCong: false, thongBao: 'Phản hồi máy chủ không hợp lệ' }));
    if (res.status === 401 && duongDan !== 'xac-thuc/dang-nhap') {
      dangXuatCucBo();
    }
    return json;
  }

  function moModal(tieuDe, noiDungHtml) {
    document.getElementById('tieu-de-modal-chung').textContent = tieuDe;
    document.getElementById('than-modal-chung').innerHTML = noiDungHtml;
    document.getElementById('modal-chung').classList.remove('an');
  }

  function dongModal() {
    document.getElementById('modal-chung').classList.add('an');
  }

  // ==================== XÁC THỰC (UC40 - UC42) ====================
  function dangXuatCucBo() {
    trangThai.maPhien = '';
    trangThai.taiKhoan = null;
    localStorage.removeItem(KHOA_PHIEN);
    clearInterval(trangThai.boDemThongBao);
    document.getElementById('ung-dung-quan-tri').classList.add('an');
    document.getElementById('man-hinh-dang-nhap').classList.remove('an');
  }

  async function khoiTaoPhien() {
    if (!trangThai.maPhien) return;
    const kq = await goiApi('xac-thuc/kiem-tra-phien');
    if (kq.thanhCong && kq.duLieu && VAI_TRO_NOI_BO.includes(kq.duLieu.vaiTro)) {
      vaoCongQuanTri(kq.duLieu);
    } else {
      dangXuatCucBo();
    }
  }

  function vaoCongQuanTri(thongTinTaiKhoan) {
    trangThai.taiKhoan = thongTinTaiKhoan;
    document.getElementById('man-hinh-dang-nhap').classList.add('an');
    document.getElementById('ung-dung-quan-tri').classList.remove('an');
    document.getElementById('ten-nguoi-dung').textContent = thongTinTaiKhoan.hoTen || thongTinTaiKhoan.dinhDanh;
    document.getElementById('huy-hieu-vai-tro').textContent = thongTinTaiKhoan.vaiTro;
    document.getElementById('nhan-vai-tro-ben').textContent = thongTinTaiKhoan.vaiTro;

    const dsMenu = MENU_THEO_VAI_TRO[thongTinTaiKhoan.vaiTro] || [];
    const nav = document.getElementById('menu-dieu-huong');
    nav.innerHTML = dsMenu.map(m => `<button type="button" class="muc-menu" data-tab="${m.ma}">${m.nhan}</button>`).join('');
    nav.querySelectorAll('.muc-menu').forEach(btn => {
      btn.addEventListener('click', () => chuyenTab(btn.dataset.tab));
    });

    if (dsMenu.length > 0) {
      chuyenTab(dsMenu[0].ma);
    }

    taiThongBao();
    clearInterval(trangThai.boDemThongBao);
    trangThai.boDemThongBao = setInterval(taiThongBao, 15000);
  }

  // ==================== HỘP THÔNG BÁO (ThongBao) ====================
  async function taiThongBao() {
    const kq = await goiApi('thong-bao');
    if (!kq.thanhCong || !Array.isArray(kq.duLieu)) return;
    const chuaDoc = kq.duLieu.filter(tb => !tb.daDoc).length;
    const demEl = document.getElementById('so-tb-chua-doc');
    if (chuaDoc > 0) {
      demEl.textContent = chuaDoc;
      demEl.classList.remove('an');
    } else {
      demEl.classList.add('an');
    }

    const khung = document.getElementById('noi-dung-tb');
    if (kq.duLieu.length === 0) {
      khung.innerHTML = '<div class="rong">Không có thông báo nào</div>';
      return;
    }
    khung.innerHTML = kq.duLieu.map(tb => `
      <div class="muc-tb ${tb.daDoc ? '' : 'chua-doc'}">
        <div>${tb.noiDung}</div>
        <div class="thoi-gian-tb">${tb.thoiDiemTao || ''}</div>
      </div>
    `).join('');
  }

  // ==================== ĐIỀU HƯỚNG TAB ====================
  function chuyenTab(maTab) {
    trangThai.tabHienTai = maTab;
    document.querySelectorAll('.muc-menu').forEach(b => b.classList.toggle('dang-chon', b.dataset.tab === maTab));
    const menuHienTai = (MENU_THEO_VAI_TRO[trangThai.taiKhoan.vaiTro] || []).find(m => m.ma === maTab);
    document.getElementById('tieu-de-man-hinh').textContent = menuHienTai ? menuHienTai.nhan : 'Quản trị';
    document.getElementById('mo-ta-man-hinh').textContent = `Vai trò: ${trangThai.taiKhoan.vaiTro}`;

    const banDoHam = {
      'bao-cao': renderBaoCao,
      'danh-muc': renderDanhMuc,
      'dien-thoai': renderDienThoai,
      'chinh-sach-gia': renderChinhSachGia,
      'duyet-phieu-nhap': () => renderPhieuNhap(true),
      'phieu-nhap': () => renderPhieuNhap(false),
      'phieu-xuat': renderPhieuXuat,
      'duyet-kiem-ke': () => renderKiemKe(true),
      'kiem-ke': () => renderKiemKe(false),
      'canh-bao-kho': renderCanhBaoKho,
      'phi-van-chuyen': renderPhiVanChuyen,
      'khuyen-mai': renderKhuyenMai,
      'voucher': renderVoucher,
      'tai-khoan': renderTaiKhoan,
      'don-hang': renderDonHang,
      'huy-don': renderHuyDon,
      'khach-hang': renderKhachHang,
    };

    const khung = document.getElementById('khung-man-hinh');
    khung.innerHTML = '<div class="rong">Đang tải dữ liệu...</div>';
    if (banDoHam[maTab]) {
      banDoHam[maTab]();
    }
  }

  // ==================== 1. BÁO CÁO THỐNG KÊ (UC53-56) ====================
  async function renderBaoCao() {
    const [dt, dh, sp, tk] = await Promise.all([
      goiApi('bao-cao?tuNgay=2026-01-01&denNgay=2026-12-31'),
      goiApi('bao-cao/don-hang'),
      goiApi('bao-cao/san-pham-ban-chay?gioiHan=10'),
      goiApi('bao-cao/ton-kho'),
    ]);

    const duLieuDt = dt.duLieu || {};
    const chiTietDh = (dh.duLieu && dh.duLieu.chiTiet) || [];
    const chiTietSp = (sp.duLieu && sp.duLieu.chiTiet) || [];
    const chiTietTk = (tk.duLieu && tk.duLieu.chiTiet) || [];

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="luoi-the">
        <div class="the-chi-so"><div class="nhan">Tổng doanh thu (UC53)</div><div class="gia-tri">${dinhDangTien(duLieuDt.tongDoanhThu)}</div></div>
        <div class="the-chi-so"><div class="nhan">Tổng lợi nhuận ước tính</div><div class="gia-tri" style="color:#16a34a;">${dinhDangTien(duLieuDt.tongLoiNhuan)}</div></div>
        <div class="the-chi-so"><div class="nhan">Tổng số đơn hàng (UC54)</div><div class="gia-tri">${(dh.duLieu && dh.duLieu.tongSoDonHang) || 0}</div></div>
        <div class="the-chi-so"><div class="nhan">Mặt hàng cần cảnh báo kho (UC56)</div><div class="gia-tri" style="color:#dc2626;">${chiTietTk.length}</div></div>
      </div>

      <div class="luoi-2-cot">
        <div class="khung-bang">
          <div class="thanh-cong-cu"><strong>Đơn hàng theo trạng thái (UC54)</strong></div>
          <table class="bang-du-lieu">
            <thead><tr><th>Trạng thái</th><th>Số lượng</th><th>Tổng giá trị</th></tr></thead>
            <tbody>${chiTietDh.map(r => `<tr><td><span class="nhan-trang-thai ${lopTrangThai(r.trangThai)}">${r.trangThai}</span></td><td>${r.soLuong}</td><td>${dinhDangTien(r.tongGiaTri)}</td></tr>`).join('') || '<tr><td colspan="3" class="rong">Chưa có dữ liệu</td></tr>'}</tbody>
          </table>
        </div>

        <div class="khung-bang">
          <div class="thanh-cong-cu"><strong>Sản phẩm bán chạy (UC55)</strong></div>
          <table class="bang-du-lieu">
            <thead><tr><th>Sản phẩm</th><th>Đã bán</th><th>Doanh thu</th></tr></thead>
            <tbody>${chiTietSp.map(r => `<tr><td>${r.ten}</td><td><strong>${r.tongSoLuongBan}</strong></td><td>${dinhDangTien(r.tongDoanhThu)}</td></tr>`).join('') || '<tr><td colspan="3" class="rong">Chưa có dữ liệu</td></tr>'}</tbody>
          </table>
        </div>
      </div>

      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Báo cáo hàng sắp hết / vượt ngưỡng (UC56)</strong></div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã</th><th>Tên điện thoại</th><th>Tồn thực tế</th><th>Giữ chỗ</th><th>Tồn khả dụng</th><th>Mức cảnh báo</th></tr></thead>
          <tbody>${chiTietTk.map(r => `<tr><td>#${r.maDienThoai}</td><td>${r.tenDienThoai}</td><td>${r.tonThucTe}</td><td>${r.giuCho}</td><td><strong>${r.tonKhaDung}</strong></td><td><span class="nhan-trang-thai ${lopTrangThai(r.mucDoCanhBao)}">${r.mucDoCanhBao}</span></td></tr>`).join('') || '<tr><td colspan="6" class="rong">Tồn kho ổn định</td></tr>'}</tbody>
        </table>
      </div>
    `;
  }

  // ==================== 2. DANH MỤC (UC1-3) ====================
  async function renderDanhMuc() {
    const kq = await goiApi('danh-muc');
    const ds = kq.duLieu || [];
    trangThai.danhSachDanhMucCache = ds;

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Danh sách Danh mục (${ds.length})</strong>
          <button type="button" class="nut nut-chinh" id="btn-them-dm">+ Thêm danh mục (UC1)</button>
        </div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã</th><th>Tên danh mục</th><th>Mô tả</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(dm => `
              <tr>
                <td>#${dm.maDanhMuc}</td>
                <td><strong>${dm.ten}</strong></td>
                <td>${dm.moTa || ''}</td>
                <td class="nhom-hanh-dong">
                  <button class="nut nut-phu nut-nho" data-sua-dm="${dm.maDanhMuc}">Sửa</button>
                  <button class="nut nut-nguy-hiem nut-nho" data-xoa-dm="${dm.maDanhMuc}">Xóa</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;

    document.getElementById('btn-them-dm').onclick = () => moFormDanhMuc();
    document.querySelectorAll('[data-sua-dm]').forEach(b => {
      b.onclick = () => moFormDanhMuc(ds.find(x => x.maDanhMuc == b.dataset.suaDm));
    });
    document.querySelectorAll('[data-xoa-dm]').forEach(b => {
      b.onclick = async () => {
        if (!confirm('Xác nhận xóa danh mục này?')) return;
        const r = await goiApi('danh-muc?maDanhMuc=' + b.dataset.xoaDm, { method: 'DELETE' });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderDanhMuc();
      };
    });
  }

  function moFormDanhMuc(dm = null) {
    moModal(dm ? 'Cập nhật danh mục (UC2)' : 'Thêm danh mục mới (UC1)', `
      <form id="form-dm">
        <div class="nhom-nhap"><label>Tên danh mục</label><input id="dm-ten" required value="${dm ? dm.ten : ''}" /></div>
        <div class="nhom-nhap"><label>Mô tả</label><textarea id="dm-mota" rows="3">${dm ? (dm.moTa || '') : ''}</textarea></div>
        <button type="submit" class="nut nut-chinh nut-rong">Lưu danh mục</button>
      </form>
    `);
    document.getElementById('form-dm').onsubmit = async (e) => {
      e.preventDefault();
      const duLieu = { ten: document.getElementById('dm-ten').value, moTa: document.getElementById('dm-mota').value };
      if (dm) duLieu.maDanhMuc = dm.maDanhMuc;
      const r = await goiApi('danh-muc', { method: dm ? 'PUT' : 'POST', body: JSON.stringify(duLieu) });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) { dongModal(); renderDanhMuc(); }
    };
  }

  // ==================== 3. ĐIỆN THOẠI (UC4-6, UC10) ====================
  async function renderDienThoai() {
    const [kqDt, kqDm] = await Promise.all([goiApi('dien-thoai'), goiApi('danh-muc')]);
    const ds = kqDt.duLieu || [];
    trangThai.danhSachSanPhamCache = ds;
    trangThai.danhSachDanhMucCache = kqDm.duLieu || [];

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Danh sách Điện thoại (${ds.length})</strong>
          <button type="button" class="nut nut-chinh" id="btn-them-dt">+ Thêm điện thoại (UC4)</button>
        </div>
        <table class="bang-du-lieu">
          <thead><tr><th>Ảnh</th><th>Tên điện thoại</th><th>Hãng</th><th>Giá vốn BQ</th><th>Giá bán</th><th>Tồn (TT/GC/KD)</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(sp => `
              <tr>
                <td><img class="anh-nho" src="${sp.hinhAnh}" alt="${sp.ten}" /></td>
                <td><strong>${sp.ten}</strong><div class="chu-phu">#${sp.maDienThoai} · ${sp.tenDanhMuc}</div></td>
                <td>${sp.hang}</td>
                <td>${dinhDangTien(sp.giaVonBinhQuan)}</td>
                <td><strong>${dinhDangTien(sp.giaBan)}</strong></td>
                <td>${sp.tonKho.tonThucTe} / ${sp.tonKho.giuCho} / <strong>${sp.tonKho.tonKhaDung}</strong></td>
                <td><span class="nhan-trang-thai ${lopTrangThai(sp.trangThai)}">${sp.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  ${sp.trangThai === 'Ngừng kinh doanh'
                    ? `<button class="nut nut-thanh-cong nut-nho" data-kp-dt="${sp.maDienThoai}">Khôi phục (UC10)</button>`
                    : `<button class="nut nut-nguy-hiem nut-nho" data-xoa-dt="${sp.maDienThoai}">Xóa (UC6)</button>`}
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;

    document.getElementById('btn-them-dt').onclick = moFormThemDienThoai;
    document.querySelectorAll('[data-xoa-dt]').forEach(b => {
      b.onclick = async () => {
        if (!confirm('Xác nhận xóa điện thoại này?')) return;
        const r = await goiApi('dien-thoai?maDienThoai=' + b.dataset.xoaDt, { method: 'DELETE' });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderDienThoai();
      };
    });
    document.querySelectorAll('[data-kp-dt]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('dien-thoai/khoi-phuc', { method: 'POST', body: JSON.stringify({ maDienThoai: Number(b.dataset.kpDt) }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderDienThoai();
      };
    });
  }

  function moFormThemDienThoai() {
    const optDm = trangThai.danhSachDanhMucCache.map(dm => `<option value="${dm.maDanhMuc}">${dm.ten}</option>`).join('');
    moModal('Thêm điện thoại mới (UC4)', `
      <form id="form-dt">
        <div class="nhom-nhap"><label>Tên điện thoại</label><input id="dt-ten" required /></div>
        <div class="luoi-2-cot">
          <div class="nhom-nhap"><label>Danh mục</label><select id="dt-dm">${optDm}</select></div>
          <div class="nhom-nhap"><label>Hãng sản xuất</label><input id="dt-hang" required placeholder="Apple, Samsung..." /></div>
        </div>
        <div class="luoi-2-cot">
          <div class="nhom-nhap"><label>Giá bán dự kiến (VNĐ)</label><input id="dt-gia" type="number" min="0" value="0" /></div>
          <div class="nhom-nhap"><label>Ảnh sản phẩm (lưu vào uploads/)</label><input id="dt-anh" type="file" accept="image/*" /></div>
        </div>
        <div class="luoi-2-cot">
          <div class="nhom-nhap"><label>RAM (GB)</label><input id="dt-ram" type="number" value="8" /></div>
          <div class="nhom-nhap"><label>ROM (GB)</label><input id="dt-rom" type="number" value="256" /></div>
        </div>
        <button type="submit" class="nut nut-chinh nut-rong">Lưu điện thoại</button>
      </form>
    `);
    document.getElementById('form-dt').onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData();
      fd.append('ten', document.getElementById('dt-ten').value);
      fd.append('maDanhMuc', document.getElementById('dt-dm').value);
      fd.append('hang', document.getElementById('dt-hang').value);
      fd.append('giaBan', document.getElementById('dt-gia').value);
      fd.append('ram', document.getElementById('dt-ram').value);
      fd.append('rom', document.getElementById('dt-rom').value);
      const file = document.getElementById('dt-anh').files[0];
      if (file) fd.append('hinhAnh', file);
      const r = await goiApi('dien-thoai', { method: 'POST', body: fd });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) { dongModal(); renderDienThoai(); }
    };
  }

  // ==================== 4. CHÍNH SÁCH GIÁ (UC11) ====================
  async function renderChinhSachGia() {
    const kq = await goiApi('chinh-sach-gia');
    const dsCh = (kq.duLieu && kq.duLieu.danhSachCauHinh) || [];
    const lichSu = (kq.duLieu && kq.duLieu.lichSuThayDoi) || [];

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang" style="padding:18px;">
        <h3 style="margin-top:0;">Cấu hình tỷ lệ lợi nhuận (UC11)</h3>
        <form id="form-gia" class="luoi-2-cot">
          <div class="nhom-nhap"><label>Phạm vi áp dụng</label><input id="gia-phamvi" value="Toàn bộ sản phẩm" required /></div>
          <div class="nhom-nhap"><label>Tỷ lệ lợi nhuận (%)</label><input id="gia-tyle" type="number" step="0.1" min="0" max="500" value="${dsCh[0] ? dsCh[0].tyLe : 25}" required /></div>
          <div class="nhom-nhap"><label>Ngày áp dụng</label><input id="gia-ngay" type="date" value="${new Date().toISOString().slice(0, 10)}" /></div>
          <div class="nhom-nhap"><label>Ghi chú</label><input id="gia-ghichu" placeholder="Lý do điều chỉnh giá..." /></div>
          <div style="grid-column:span 2;"><button type="submit" class="nut nut-chinh">Cập nhật tỷ lệ & Tính lại giá bán</button></div>
        </form>
      </div>
      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Lịch sử thay đổi cấu hình lợi nhuận</strong></div>
        <table class="bang-du-lieu">
          <thead><tr><th>Thời điểm</th><th>Phạm vi</th><th>Tỷ lệ (%)</th><th>Ngày áp dụng</th></tr></thead>
          <tbody>${lichSu.map(ls => `<tr><td>${ls.thoiDiemGhi}</td><td>${ls.phamVi}</td><td><strong>${ls.tyLe}%</strong></td><td>${ls.ngayApDung}</td></tr>`).join('')}</tbody>
        </table>
      </div>
    `;

    document.getElementById('form-gia').onsubmit = async (e) => {
      e.preventDefault();
      const r = await goiApi('chinh-sach-gia', {
        method: 'POST',
        body: JSON.stringify({
          phamVi: document.getElementById('gia-phamvi').value,
          tyLe: Number(document.getElementById('gia-tyle').value),
          ngayApDung: document.getElementById('gia-ngay').value,
          ghiChu: document.getElementById('gia-ghichu').value,
        }),
      });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) renderChinhSachGia();
    };
  }

  // ==================== 5. PHIẾU NHẬP (UC12 - UC14) ====================
  async function renderPhieuNhap(chiDuyet) {
    const [kqPn, kqDt] = await Promise.all([goiApi('phieu-nhap'), goiApi('dien-thoai')]);
    const ds = kqPn.duLieu || [];
    trangThai.danhSachSanPhamCache = kqDt.duLieu || [];

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>${chiDuyet ? 'Duyệt Phiếu nhập kho (Ban Quản lý - UC13)' : 'Quản lý Phiếu nhập kho (Nhân viên Kho - UC12, UC14)'}</strong>
          ${!chiDuyet ? '<button type="button" class="nut nut-chinh" id="btn-tao-pn">+ Lập phiếu nhập (UC12)</button>' : ''}
        </div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã</th><th>Nguồn nhập</th><th>Ngày nhập</th><th>Nhân viên</th><th>Chi tiết</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(p => `
              <tr>
                <td>#PN${p.maPhieuNhap}</td>
                <td>${p.nguonNhap}</td>
                <td>${p.ngayNhap}</td>
                <td>${p.tenNhanVien}</td>
                <td>${(p.chiTiet || []).map(c => `${c.tenDienThoai} (x${c.soLuong} @ ${dinhDangTien(c.donGiaNhap)})`).join('<br>')}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(p.trangThai)}">${p.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  ${!chiDuyet && p.trangThai === 'Nháp' ? `<button class="nut nut-chinh nut-nho" data-gui-pn="${p.maPhieuNhap}">Gửi duyệt</button>` : ''}
                  ${chiDuyet && p.trangThai === 'Chờ duyệt' ? `
                    <button class="nut nut-thanh-cong nut-nho" data-duyet-pn="${p.maPhieuNhap}">Duyệt</button>
                    <button class="nut nut-nguy-hiem nut-nho" data-tuchoi-pn="${p.maPhieuNhap}">Từ chối</button>
                  ` : ''}
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;

    if (!chiDuyet && document.getElementById('btn-tao-pn')) {
      document.getElementById('btn-tao-pn').onclick = () => {
        const optSp = trangThai.danhSachSanPhamCache.map(sp => `<option value="${sp.maDienThoai}">${sp.ten}</option>`).join('');
        moModal('Lập phiếu nhập kho (UC12)', `
          <form id="form-pn">
            <div class="nhom-nhap"><label>Nguồn nhập / Nhà cung cấp</label><input id="pn-nguon" required placeholder="VD: Apple Việt Nam" /></div>
            <div class="nhom-nhap"><label>Sản phẩm</label><select id="pn-sp">${optSp}</select></div>
            <div class="luoi-2-cot">
              <div class="nhom-nhap"><label>Số lượng nhập</label><input id="pn-sl" type="number" min="1" value="10" required /></div>
              <div class="nhom-nhap"><label>Đơn giá nhập (VNĐ)</label><input id="pn-gia" type="number" min="1000" value="20000000" required /></div>
            </div>
            <button type="submit" class="nut nut-chinh nut-rong">Lưu phiếu Nháp</button>
          </form>
        `);
        document.getElementById('form-pn').onsubmit = async (e) => {
          e.preventDefault();
          const r = await goiApi('phieu-nhap', {
            method: 'POST',
            body: JSON.stringify({
              nguonNhap: document.getElementById('pn-nguon').value,
              chiTiet: [{
                maDienThoai: Number(document.getElementById('pn-sp').value),
                soLuong: Number(document.getElementById('pn-sl').value),
                donGiaNhap: Number(document.getElementById('pn-gia').value),
              }],
            }),
          });
          hienThongBaoToast(r.thongBao, r.thanhCong);
          if (r.thanhCong) { dongModal(); renderPhieuNhap(false); }
        };
      };
    }

    document.querySelectorAll('[data-gui-pn]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('phieu-nhap/gui-duyet', { method: 'POST', body: JSON.stringify({ maPhieuNhap: Number(b.dataset.guiPn) }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuNhap(false);
      };
    });
    document.querySelectorAll('[data-duyet-pn]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('phieu-nhap/duyet', { method: 'POST', body: JSON.stringify({ maPhieuNhap: Number(b.dataset.duyetPn) }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuNhap(true);
      };
    });
    document.querySelectorAll('[data-tuchoi-pn]').forEach(b => {
      b.onclick = async () => {
        const lyDo = prompt('Nhập lý do từ chối phiếu nhập:');
        if (!lyDo) return;
        const r = await goiApi('phieu-nhap/tu-choi', { method: 'POST', body: JSON.stringify({ maPhieuNhap: Number(b.dataset.tuchoiPn), lyDo }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuNhap(true);
      };
    });
  }

  // ==================== 6. PHIẾU XUẤT KHO (UC15 - UC17) ====================
  async function renderPhieuXuat() {
    const [kqPx, kqDh] = await Promise.all([goiApi('phieu-xuat'), goiApi('don-hang')]);
    const dsPx = kqPx.duLieu || [];
    const donSanSang = (kqDh.duLieu || []).filter(d => ['Đã duyệt', 'Đang đóng gói'].includes(d.trangThai) && !d.maPhieuXuat);

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Đơn hàng chờ lập phiếu xuất (${donSanSang.length})</strong>
        </div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Trạng thái</th><th>Tổng tiền</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${donSanSang.map(d => `
              <tr>
                <td>#DH${d.maDonHang}</td><td>${d.tenKhachHang}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(d.trangThai)}">${d.trangThai}</span></td>
                <td>${dinhDangTien(d.tongTien)}</td>
                <td><button class="nut nut-chinh nut-nho" data-lap-px="${d.maDonHang}">Lập phiếu xuất (UC15)</button></td>
              </tr>
            `).join('') || '<tr><td colspan="5" class="rong">Không có đơn hàng nào đang chờ lập phiếu xuất</td></tr>'}
          </tbody>
        </table>
      </div>

      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Danh sách Phiếu xuất kho (${dsPx.length})</strong></div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã PX</th><th>Mã ĐH</th><th>Ngày lập</th><th>Chi tiết</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${dsPx.map(px => `
              <tr>
                <td>#PX${px.maPhieuXuat}</td><td>#DH${px.maDonHang}</td><td>${px.ngayLap}</td>
                <td>${(px.chiTiet || []).map(c => `${c.tenDienThoai} (x${c.soLuong})`).join('<br>')}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(px.trangThai)}">${px.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  ${px.trangThai === 'Nháp' ? `
                    <button class="nut nut-thanh-cong nut-nho" data-xn-px="${px.maPhieuXuat}">Xác nhận xuất (UC16)</button>
                    <button class="nut nut-nguy-hiem nut-nho" data-huy-px="${px.maPhieuXuat}">Hủy nháp</button>
                  ` : ''}
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;

    document.querySelectorAll('[data-lap-px]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('phieu-xuat', { method: 'POST', body: JSON.stringify({ maDonHang: Number(b.dataset.lapPx), ghiChu: 'Lập phiếu xuất kho' }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuXuat();
      };
    });
    document.querySelectorAll('[data-xn-px]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('phieu-xuat/xac-nhan', { method: 'POST', body: JSON.stringify({ maPhieuXuat: Number(b.dataset.xnPx) }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuXuat();
      };
    });
    document.querySelectorAll('[data-huy-px]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('phieu-xuat/huy', { method: 'POST', body: JSON.stringify({ maPhieuXuat: Number(b.dataset.huyPx) }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuXuat();
      };
    });
  }

  // ==================== 7. KIỂM KÊ & NGƯỠNG TỒN KHO (UC18 - UC21) ====================
  async function renderCanhBaoKho() {
    const kq = await goiApi('kiem-ke/canh-bao');
    const ds = (kq.duLieu && kq.duLieu.danhSachCanhBao) || [];
    const ch = (kq.duLieu && kq.duLieu.cauHinhNguong) || { nguongToiThieu: 5, nguongSom: 15, nguongToiDa: 350 };

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="luoi-the">
        <div class="the-chi-so"><div class="nhan">Ngưỡng tối thiểu</div><div class="gia-tri">${ch.nguongToiThieu}</div></div>
        <div class="the-chi-so"><div class="nhan">Ngưỡng cảnh báo sớm</div><div class="gia-tri">${ch.nguongSom}</div></div>
        <div class="the-chi-so"><div class="nhan">Ngưỡng tối đa</div><div class="gia-tri">${ch.nguongToiDa}</div></div>
      </div>
      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Danh sách Cảnh báo Tồn kho (UC20)</strong></div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã SP</th><th>Tên điện thoại</th><th>Tồn thực tế</th><th>Giữ chỗ</th><th>Tồn khả dụng</th><th>Loại cảnh báo</th></tr></thead>
          <tbody>${ds.map(r => `<tr><td>#${r.maDienThoai}</td><td>${r.tenDienThoai}</td><td>${r.tonThucTe}</td><td>${r.giuCho}</td><td><strong>${r.tonKhaDung}</strong></td><td><span class="nhan-trang-thai ${lopTrangThai(r.mucDoCanhBao)}">${r.mucDoCanhBao}</span></td></tr>`).join('') || '<tr><td colspan="6" class="rong">Không có cảnh báo tồn kho</td></tr>'}</tbody>
        </table>
      </div>
    `;
  }

  async function renderKiemKe(laBanQuanLy) {
    const [kqBb, kqCb, kqDt] = await Promise.all([goiApi('kiem-ke'), goiApi('kiem-ke/canh-bao'), goiApi('dien-thoai')]);
    const dsBb = kqBb.duLieu || [];
    const ch = (kqCb.duLieu && kqCb.duLieu.cauHinhNguong) || { nguongToiThieu: 5, nguongSom: 15, nguongToiDa: 350 };
    trangThai.danhSachSanPhamCache = kqDt.duLieu || [];

    document.getElementById('khung-man-hinh').innerHTML = `
      ${laBanQuanLy ? `
        <div class="khung-bang" style="padding:18px;">
          <h3 style="margin-top:0;">Cấu hình ngưỡng cảnh báo tồn kho (UC21)</h3>
          <form id="form-nguong" class="luoi-2-cot">
            <div class="nhom-nhap"><label>Ngưỡng tối thiểu</label><input id="ng-min" type="number" min="0" value="${ch.nguongToiThieu}" required /></div>
            <div class="nhom-nhap"><label>Ngưỡng sớm</label><input id="ng-som" type="number" min="1" value="${ch.nguongSom}" required /></div>
            <div class="nhom-nhap"><label>Ngưỡng tối đa</label><input id="ng-max" type="number" min="2" value="${ch.nguongToiDa}" required /></div>
            <div style="display:flex; align-items:flex-end; margin-bottom:12px;"><button type="submit" class="nut nut-chinh">Lưu cấu hình ngưỡng</button></div>
          </form>
        </div>
      ` : ''}

      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Danh sách Biên bản Kiểm kê (${dsBb.length})</strong>
          ${!laBanQuanLy ? '<button type="button" class="nut nut-chinh" id="btn-tao-kk">+ Lập biên bản kiểm kê (UC18)</button>' : ''}
        </div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã BB</th><th>Thời điểm</th><th>Nhân viên</th><th>Ghi chú</th><th>Chi tiết chênh lệch</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${dsBb.map(bb => `
              <tr>
                <td>#KK${bb.maBienBan}</td><td>${bb.thoiDiem}</td><td>${bb.tenNhanVien}</td><td>${bb.ghiChu || ''}</td>
                <td>${(bb.chiTiet || []).map(c => `${c.tenDienThoai}: HT ${c.soLuongHeThong} → TT ${c.soLuongThucTe} (Lệch ${c.chenhLech})`).join('<br>')}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(bb.trangThai)}">${bb.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  ${laBanQuanLy && bb.trangThai === 'Chờ duyệt' ? `
                    <button class="nut nut-thanh-cong nut-nho" data-duyet-kk="${bb.maBienBan}">Duyệt (UC19)</button>
                    <button class="nut nut-canh-bao nut-nho" data-ktra-kk="${bb.maBienBan}">Yêu cầu kiểm lại</button>
                  ` : ''}
                </td>
              </tr>
            `).join('') || '<tr><td colspan="7" class="rong">Chưa có biên bản kiểm kê</td></tr>'}
          </tbody>
        </table>
      </div>
    `;

    if (laBanQuanLy && document.getElementById('form-nguong')) {
      document.getElementById('form-nguong').onsubmit = async (e) => {
        e.preventDefault();
        const r = await goiApi('kiem-ke/nguong-canh-bao', {
          method: 'POST',
          body: JSON.stringify({
            nguongToiThieu: Number(document.getElementById('ng-min').value),
            nguongSom: Number(document.getElementById('ng-som').value),
            nguongToiDa: Number(document.getElementById('ng-max').value),
          }),
        });
        hienThongBaoToast(r.thongBao, r.thanhCong);
      };
    }

    if (!laBanQuanLy && document.getElementById('btn-tao-kk')) {
      document.getElementById('btn-tao-kk').onclick = () => {
        const optSp = trangThai.danhSachSanPhamCache.map(sp => `<option value="${sp.maDienThoai}">${sp.ten} (Tồn HT: ${sp.tonKho.tonThucTe})</option>`).join('');
        moModal('Lập biên bản kiểm kê (UC18)', `
          <form id="form-kk">
            <div class="nhom-nhap"><label>Chọn sản phẩm kiểm kê</label><select id="kk-sp">${optSp}</select></div>
            <div class="nhom-nhap"><label>Số lượng kiểm đếm thực tế</label><input id="kk-sl" type="number" min="0" required /></div>
            <div class="nhom-nhap"><label>Ghi chú giải trình (bắt buộc nếu có chênh lệch)</label><textarea id="kk-ghichu" rows="3"></textarea></div>
            <button type="submit" class="nut nut-chinh nut-rong">Lưu biên bản kiểm kê</button>
          </form>
        `);
        document.getElementById('form-kk').onsubmit = async (e) => {
          e.preventDefault();
          const r = await goiApi('kiem-ke', {
            method: 'POST',
            body: JSON.stringify({
              ghiChu: document.getElementById('kk-ghichu').value,
              chiTiet: [{ maDienThoai: Number(document.getElementById('kk-sp').value), soLuongThucTe: Number(document.getElementById('kk-sl').value) }],
            }),
          });
          hienThongBaoToast(r.thongBao, r.thanhCong);
          if (r.thanhCong) { dongModal(); renderKiemKe(false); }
        };
      };
    }

    document.querySelectorAll('[data-duyet-kk]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('kiem-ke/duyet', { method: 'POST', body: JSON.stringify({ maBienBan: Number(b.dataset.duyetKk), pheDuyet: true }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderKiemKe(true);
      };
    });
    document.querySelectorAll('[data-ktra-kk]').forEach(b => {
      b.onclick = async () => {
        const lyDo = prompt('Nhập lý do yêu cầu kiểm kê lại:');
        const r = await goiApi('kiem-ke/duyet', { method: 'POST', body: JSON.stringify({ maBienBan: Number(b.dataset.ktraKk), pheDuyet: false, lyDo }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderKiemKe(true);
      };
    });
  }

  // ==================== 8. PHÍ VẬN CHUYỂN (UC27) ====================
  async function renderPhiVanChuyen() {
    const kq = await goiApi('phi-van-chuyen');
    const ds = kq.duLieu || [];
    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Quy tắc Phí vận chuyển (UC27)</strong>
          <button type="button" class="nut nut-chinh" id="btn-them-phi">+ Thêm quy tắc</button>
        </div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã</th><th>Khu vực</th><th>Mức phí</th><th>Phí mặc định</th><th>Ngưỡng miễn phí</th></tr></thead>
          <tbody>${ds.map(q => `<tr><td>#${q.maQuyTac}</td><td><strong>${q.khuVuc}</strong></td><td>${dinhDangTien(q.mucPhi)}</td><td>${dinhDangTien(q.mucPhiMacDinh)}</td><td>${dinhDangTien(q.nguongMienPhi)}</td></tr>`).join('')}</tbody>
        </table>
      </div>
    `;
    document.getElementById('btn-them-phi').onclick = () => {
      moModal('Thêm quy tắc phí vận chuyển (UC27)', `
        <form id="form-phi">
          <div class="nhom-nhap"><label>Khu vực</label><input id="phi-kv" required /></div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Mức phí (VNĐ)</label><input id="phi-muc" type="number" min="0" value="30000" required /></div>
            <div class="nhom-nhap"><label>Ngưỡng miễn phí (VNĐ)</label><input id="phi-mp" type="number" min="0" value="10000000" required /></div>
          </div>
          <button type="submit" class="nut nut-chinh nut-rong">Lưu quy tắc</button>
        </form>
      `);
      document.getElementById('form-phi').onsubmit = async (e) => {
        e.preventDefault();
        const r = await goiApi('phi-van-chuyen', {
          method: 'POST',
          body: JSON.stringify({ khuVuc: document.getElementById('phi-kv').value, mucPhi: Number(document.getElementById('phi-muc').value), nguongMienPhi: Number(document.getElementById('phi-mp').value) }),
        });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) { dongModal(); renderPhiVanChuyen(); }
      };
    };
  }

  // ==================== 9. KHUYẾN MÃI & VOUCHER (UC57-64) ====================
  async function renderKhuyenMai() {
    const kq = await goiApi('khuyen-mai');
    const ds = kq.duLieu || [];
    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Chương trình Khuyến mãi (UC57 - UC59)</strong><button class="nut nut-chinh" id="btn-them-km">+ Tạo khuyến mãi</button></div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã</th><th>Tên chương trình</th><th>Ưu đãi</th><th>Thời gian</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(km => `
              <tr>
                <td>#${km.maKhuyenMai}</td><td><strong>${km.ten}</strong><div class="chu-phu">${km.noiDung || ''}</div></td>
                <td><strong>-${km.mucUuDai}%</strong></td><td>${km.ngayBatDau} → ${km.ngayKetThuc}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(km.trangThai)}">${km.trangThai}</span></td>
                <td><button class="nut nut-phu nut-nho" data-doi-km="${km.maKhuyenMai}" data-tt="${km.trangThai === 'Đang áp dụng' ? 'Ngừng áp dụng' : 'Đang áp dụng'}">${km.trangThai === 'Đang áp dụng' ? 'Ngừng áp dụng' : 'Kích hoạt'}</button></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;
    document.getElementById('btn-them-km').onclick = () => {
      moModal('Tạo chương trình khuyến mãi (UC57)', `
        <form id="form-km">
          <div class="nhom-nhap"><label>Tên chương trình</label><input id="km-ten" required /></div>
          <div class="nhom-nhap"><label>Mức ưu đãi (%)</label><input id="km-ud" type="number" min="1" max="100" value="10" required /></div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Ngày bắt đầu</label><input id="km-bd" type="date" value="${new Date().toISOString().slice(0, 10)}" required /></div>
            <div class="nhom-nhap"><label>Ngày kết thúc</label><input id="km-kt" type="date" value="${new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10)}" required /></div>
          </div>
          <button type="submit" class="nut nut-chinh nut-rong">Tạo khuyến mãi</button>
        </form>
      `);
      document.getElementById('form-km').onsubmit = async (e) => {
        e.preventDefault();
        const r = await goiApi('khuyen-mai', {
          method: 'POST',
          body: JSON.stringify({ ten: document.getElementById('km-ten').value, mucUuDai: Number(document.getElementById('km-ud').value), ngayBatDau: document.getElementById('km-bd').value, ngayKetThuc: document.getElementById('km-kt').value }),
        });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) { dongModal(); renderKhuyenMai(); }
      };
    };
    document.querySelectorAll('[data-doi-km]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('khuyen-mai', { method: 'PATCH', body: JSON.stringify({ maKhuyenMai: Number(b.dataset.doiKm), trangThai: b.dataset.tt }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderKhuyenMai();
      };
    });
  }

  async function renderVoucher() {
    const kq = await goiApi('voucher');
    const ds = kq.duLieu || [];
    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Quản lý Mã giảm giá / Voucher (UC60 - UC64)</strong><button class="nut nut-chinh" id="btn-them-vc">+ Tạo Voucher</button></div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã Voucher</th><th>Loại & Giá trị</th><th>Lượt dùng / Tổng</th><th>Hiệu lực</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(v => `
              <tr>
                <td><strong>${v.maVoucher}</strong></td>
                <td>${v.loaiGiam}: <strong>${v.loaiGiam === 'Phần trăm' ? v.giaTriGiam + '%' : dinhDangTien(v.giaTriGiam)}</strong></td>
                <td>${v.luotDaDung} / ${v.soLuong}</td>
                <td>${v.ngayBatDau} → ${v.ngayKetThuc}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(v.trangThai)}">${v.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  <button class="nut nut-canh-bao nut-nho" data-khoa-vc="${v.maVoucher}" data-tt="${v.trangThai === 'Đã khóa' ? 'Đang hiệu lực' : 'Đã khóa'}">${v.trangThai === 'Đã khóa' ? 'Mở khóa' : 'Khóa (UC63)'}</button>
                  <button class="nut nut-nguy-hiem nut-nho" data-xoa-vc="${v.maVoucher}">Xóa (UC64)</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;
    document.getElementById('btn-them-vc').onclick = () => {
      moModal('Tạo mã Voucher mới (UC60)', `
        <form id="form-vc">
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Mã Voucher</label><input id="vc-ma" required placeholder="VD: TET2027" /></div>
            <div class="nhom-nhap"><label>Loại giảm</label><select id="vc-loai"><option value="Số tiền">Số tiền</option><option value="Phần trăm">Phần trăm</option></select></div>
          </div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Giá trị giảm</label><input id="vc-gt" type="number" min="1" value="500000" required /></div>
            <div class="nhom-nhap"><label>Số lượng phát hành</label><input id="vc-sl" type="number" min="1" value="100" required /></div>
          </div>
          <button type="submit" class="nut nut-chinh nut-rong">Tạo Voucher</button>
        </form>
      `);
      document.getElementById('form-vc').onsubmit = async (e) => {
        e.preventDefault();
        const r = await goiApi('voucher', {
          method: 'POST',
          body: JSON.stringify({ maVoucher: document.getElementById('vc-ma').value, loaiGiam: document.getElementById('vc-loai').value, giaTriGiam: Number(document.getElementById('vc-gt').value), soLuong: Number(document.getElementById('vc-sl').value) }),
        });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) { dongModal(); renderVoucher(); }
      };
    };
    document.querySelectorAll('[data-khoa-vc]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('voucher', { method: 'PATCH', body: JSON.stringify({ maVoucher: b.dataset.khoaVc, trangThai: b.dataset.tt }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderVoucher();
      };
    });
    document.querySelectorAll('[data-xoa-vc]').forEach(b => {
      b.onclick = async () => {
        if (!confirm('Xóa voucher này (chỉ thành công khi chưa có lượt dùng)?')) return;
        const r = await goiApi('voucher?maVoucher=' + encodeURIComponent(b.dataset.xoaVc), { method: 'DELETE' });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderVoucher();
      };
    });
  }

  // ==================== 10. QUẢN LÝ TÀI KHOẢN (UC48-52) ====================
  async function renderTaiKhoan() {
    const kq = await goiApi('tai-khoan');
    const ds = kq.duLieu || [];
    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Danh sách Tài khoản (${ds.length})</strong><button class="nut nut-chinh" id="btn-tao-nv">+ Tạo tài khoản Nhân viên (UC51)</button></div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã</th><th>Định danh</th><th>Họ tên</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(tk => `
              <tr>
                <td>#${tk.maTaiKhoan}</td><td>${tk.dinhDanh}</td><td>${tk.hoTen}</td>
                <td><span class="nhan-trang-thai tt-lam">${tk.vaiTro}</span></td>
                <td><span class="nhan-trang-thai ${lopTrangThai(tk.trangThai)}">${tk.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  <button class="nut ${tk.trangThai === 'Hoạt động' ? 'nut-nguy-hiem' : 'nut-thanh-cong'} nut-nho" data-khoa-tk="${tk.maTaiKhoan}" data-hd="${tk.trangThai === 'Hoạt động' ? '1' : '0'}">${tk.trangThai === 'Hoạt động' ? 'Khóa (UC49)' : 'Mở khóa (UC50)'}</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;
    document.getElementById('btn-tao-nv').onclick = () => {
      moModal('Tạo tài khoản Nhân viên (UC51)', `
        <form id="form-nv">
          <div class="nhom-nhap"><label>Định danh (Email/SĐT)</label><input id="nv-dd" required /></div>
          <div class="nhom-nhap"><label>Họ tên nhân viên</label><input id="nv-ten" required /></div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Vai trò</label><select id="nv-vt"><option value="Nhân viên Kho">Nhân viên Kho</option><option value="Nhân viên Bán hàng / CSKH">Nhân viên Bán hàng / CSKH</option><option value="Ban Quản lý">Ban Quản lý</option></select></div>
            <div class="nhom-nhap"><label>Mật khẩu khởi tạo</label><input id="nv-mk" type="password" value="123456" required /></div>
          </div>
          <button type="submit" class="nut nut-chinh nut-rong">Tạo tài khoản</button>
        </form>
      `);
      document.getElementById('form-nv').onsubmit = async (e) => {
        e.preventDefault();
        const r = await goiApi('tai-khoan', {
          method: 'POST',
          body: JSON.stringify({ dinhDanh: document.getElementById('nv-dd').value, hoTen: document.getElementById('nv-ten').value, vaiTro: document.getElementById('nv-vt').value, matKhau: document.getElementById('nv-mk').value }),
        });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) { dongModal(); renderTaiKhoan(); }
      };
    };
    document.querySelectorAll('[data-khoa-tk]').forEach(b => {
      b.onclick = async () => {
        const khoa = b.dataset.hd === '1';
        const lyDoKhoa = khoa ? prompt('Nhập lý do khóa tài khoản:') : null;
        if (khoa && lyDoKhoa === null) return;
        const r = await goiApi('tai-khoan/khoa', { method: 'PATCH', body: JSON.stringify({ maTaiKhoan: Number(b.dataset.khoaTk), khoa, lyDoKhoa }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderTaiKhoan();
      };
    });
  }

  // ==================== 11. ĐƠN HÀNG (UC33-34 - CSKH) ====================
  async function renderDonHang() {
    const kq = await goiApi('don-hang');
    const ds = kq.duLieu || [];

    function nutChuyenTiep(dh) {
      if (dh.trangThai === 'Chờ duyệt') return `<button class="nut nut-thanh-cong nut-nho" data-dh="${dh.maDonHang}" data-tt="Đã duyệt">Duyệt đơn (UC33)</button>`;
      if (dh.trangThai === 'Đã duyệt') return `<button class="nut nut-chinh nut-nho" data-dh="${dh.maDonHang}" data-tt="Đang đóng gói">Chuyển Đang đóng gói</button>`;
      if (dh.trangThai === 'Đang đóng gói') return `<span class="chu-phu">Chờ Kho xuất hàng (UC16)</span>`;
      if (dh.trangThai === 'Đã giao shipper') return `<button class="nut nut-chinh nut-nho" data-dh="${dh.maDonHang}" data-tt="Đang giao">Bắt đầu giao (UC34)</button>`;
      if (dh.trangThai === 'Đang giao') return `<button class="nut nut-thanh-cong nut-nho" data-dh="${dh.maDonHang}" data-tt="Đã giao thành công">Giao thành công</button>`;
      return '';
    }

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Quản lý Đơn hàng & Tiến độ giao hàng (UC33 - UC34)</strong></div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã ĐH</th><th>Khách hàng</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Thanh toán</th><th>Phiếu xuất</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(dh => `
              <tr>
                <td><strong>#DH${dh.maDonHang}</strong></td>
                <td>${dh.tenKhachHang}<div class="chu-phu">${dh.soDienThoai || ''}</div></td>
                <td>${dh.ngayDat}</td>
                <td><strong>${dinhDangTien(dh.tongTien)}</strong></td>
                <td><span class="nhan-trang-thai ${lopTrangThai(dh.trangThaiThanhToan)}">${dh.trangThaiThanhToan}</span></td>
                <td>${dh.maPhieuXuat ? `#PX${dh.maPhieuXuat} (${dh.trangThaiPhieuXuat})` : 'Chưa có'}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(dh.trangThai)}">${dh.trangThai}</span></td>
                <td>${nutChuyenTiep(dh)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;

    document.querySelectorAll('[data-dh]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('don-hang', { method: 'PATCH', body: JSON.stringify({ maDonHang: Number(b.dataset.dh), trangThai: b.dataset.tt }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderDonHang();
      };
    });
  }

  // ==================== 12. HỦY ĐƠN & HOÀN TIỀN (UC35-38 - CSKH) ====================
  async function renderHuyDon() {
    const kq = await goiApi('huy-don');
    const ds = kq.duLieu || [];
    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Yêu cầu Hủy đơn & Hoàn tiền mô phỏng (UC35 - UC38)</strong></div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã YC</th><th>Đơn hàng</th><th>Khách hàng</th><th>Lý do hủy</th><th>TT Đơn / TT Thanh toán</th><th>Trạng thái YC</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(yc => `
              <tr>
                <td>#YC${yc.maYeuCau}</td><td>#DH${yc.maDonHang}</td><td>${yc.tenKhachHang}</td><td>${yc.lyDo}</td>
                <td>${yc.trangThaiDonHang} / <strong>${yc.trangThaiThanhToan}</strong></td>
                <td><span class="nhan-trang-thai ${lopTrangThai(yc.trangThai)}">${yc.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  ${yc.trangThai === 'Chờ xử lý' ? `
                    <button class="nut nut-thanh-cong nut-nho" data-xl-yc="${yc.maYeuCau}" data-cn="1">Xác nhận hủy (UC36)</button>
                    <button class="nut nut-nguy-hiem nut-nho" data-xl-yc="${yc.maYeuCau}" data-cn="0">Từ chối (UC37)</button>
                  ` : ''}
                  ${['Cần hoàn tiền', 'Hoàn tiền lỗi'].includes(yc.trangThaiThanhToan) ? `<button class="nut nut-chinh nut-nho" data-hoan-tien="${yc.maDonHang}">Hoàn tiền (UC38)</button>` : ''}
                </td>
              </tr>
            `).join('') || '<tr><td colspan="7" class="rong">Chưa có yêu cầu hủy đơn nào</td></tr>'}
          </tbody>
        </table>
      </div>
    `;

    document.querySelectorAll('[data-xl-yc]').forEach(b => {
      b.onclick = async () => {
        const chapNhan = b.dataset.cn === '1';
        const lyDoTuChoi = chapNhan ? null : prompt('Nhập lý do từ chối yêu cầu hủy:');
        if (!chapNhan && !lyDoTuChoi) return;
        const r = await goiApi('huy-don/xu-ly', { method: 'POST', body: JSON.stringify({ maYeuCau: Number(b.dataset.xlYc), chapNhan, lyDoTuChoi }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderHuyDon();
      };
    });
    document.querySelectorAll('[data-hoan-tien]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('huy-don/hoan-tien', { method: 'POST', body: JSON.stringify({ maDonHang: Number(b.dataset.hoanTien), hoanThanhCong: true }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderHuyDon();
      };
    });
  }

  // ==================== 13. TRA CỨU KHÁCH HÀNG (UC47 - CHỈ XEM) ====================
  async function renderKhachHang(tuKhoa = '') {
    const kq = await goiApi('khach-hang' + (tuKhoa ? '?tuKhoa=' + encodeURIComponent(tuKhoa) : ''));
    const ds = kq.duLieu || [];
    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Tra cứu Khách hàng & Lịch sử mua (UC47 — Chỉ xem)</strong>
          <div style="display:flex; gap:8px;">
            <input id="tim-kh" placeholder="Tìm theo tên, SĐT, email..." value="${tuKhoa}" style="padding:7px 10px; border:1px solid #cbd5e1; border-radius:8px;" />
            <button type="button" class="nut nut-chinh" id="btn-tim-kh">Tìm</button>
          </div>
        </div>
        <table class="bang-du-lieu">
          <thead><tr><th>Mã KH</th><th>Họ tên</th><th>SĐT</th><th>Email</th><th>Số đơn</th><th>Tổng chi tiêu</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(kh => `
              <tr>
                <td>#KH${kh.maKhachHang}</td><td><strong>${kh.hoTen}</strong></td><td>${kh.soDienThoai || ''}</td><td>${kh.email || ''}</td>
                <td>${kh.tongSoDon}</td><td>${dinhDangTien(kh.tongChiTieu)}</td>
                <td><button class="nut nut-phu nut-nho" data-ls-kh="${kh.maKhachHang}" data-ten="${kh.hoTen}">Xem lịch sử mua</button></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;
    document.getElementById('btn-tim-kh').onclick = () => renderKhachHang(document.getElementById('tim-kh').value);
    document.querySelectorAll('[data-ls-kh]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('khach-hang/lich-su?maKhachHang=' + b.dataset.lsKh);
        const don = r.duLieu || [];
        moModal('Lịch sử mua hàng: ' + b.dataset.ten, `
          <table class="bang-du-lieu">
            <thead><tr><th>Mã ĐH</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead>
            <tbody>${don.map(d => `<tr><td>#DH${d.maDonHang}</td><td>${d.ngayDat}</td><td>${dinhDangTien(d.tongTien)}</td><td><span class="nhan-trang-thai ${lopTrangThai(d.trangThai)}">${d.trangThai}</span></td></tr>`).join('') || '<tr><td colspan="4" class="rong">Chưa có đơn hàng</td></tr>'}</tbody>
          </table>
        `);
      };
    });
  }

  // ==================== SỰ KIỆN KHỞI ĐỘNG ====================
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nut-nhanh').forEach(btn => {
      btn.onclick = () => {
        document.getElementById('nhap-dinh-danh').value = btn.dataset.tk;
        document.getElementById('nhap-mat-khau').value = '123456';
      };
    });

    document.getElementById('form-dang-nhap').onsubmit = async (e) => {
      e.preventDefault();
      const loiEl = document.getElementById('loi-dang-nhap');
      loiEl.classList.add('an');
      const kq = await goiApi('xac-thuc/dang-nhap', {
        method: 'POST',
        body: JSON.stringify({
          dinhDanh: document.getElementById('nhap-dinh-danh').value.trim(),
          matKhau: document.getElementById('nhap-mat-khau').value,
        }),
      });
      if (!kq.thanhCong) {
        loiEl.textContent = kq.thongBao;
        loiEl.classList.remove('an');
        return;
      }
      if (!VAI_TRO_NOI_BO.includes(kq.duLieu.taiKhoan.vaiTro)) {
        loiEl.textContent = 'Tài khoản Khách hàng không có quyền truy cập Cổng Quản trị nội bộ';
        loiEl.classList.remove('an');
        return;
      }
      trangThai.maPhien = kq.duLieu.maPhien;
      localStorage.setItem(KHOA_PHIEN, trangThai.maPhien);
      vaoCongQuanTri(kq.duLieu.taiKhoan);
    };

    document.getElementById('nut-dang-xuat').onclick = async () => {
      await goiApi('xac-thuc/dang-xuat', { method: 'POST' });
      dangXuatCucBo();
    };

    document.getElementById('nut-chuong-tb').onclick = () => {
      document.getElementById('hop-thong-bao').classList.toggle('an');
      taiThongBao();
    };
    document.getElementById('nut-doc-tat-ca').onclick = async () => {
      await goiApi('thong-bao/da-doc', { method: 'POST', body: JSON.stringify({}) });
      taiThongBao();
    };

    document.getElementById('dong-modal-chung').onclick = dongModal;
    document.getElementById('nut-mo-quen-mk').onclick = () => document.getElementById('modal-quen-mk').classList.remove('an');
    document.getElementById('dong-modal-quen-mk').onclick = () => document.getElementById('modal-quen-mk').classList.add('an');

    document.getElementById('qmk-gui-ma').onclick = async () => {
      const kq = await goiApi('xac-thuc/quen-mat-khau', {
        method: 'POST',
        body: JSON.stringify({ dinhDanh: document.getElementById('qmk-dinh-danh').value.trim() }),
      });
      if (kq.thanhCong) {
        document.getElementById('qmk-buoc-2').classList.remove('an');
        document.getElementById('qmk-thong-bao-ma').textContent = `Mã xác thực mô phỏng: ${kq.duLieu.maXacThucMoPhong} (Hết hạn: ${kq.duLieu.hanMaXacThuc})`;
      } else {
        alert(kq.thongBao);
      }
    };
    document.getElementById('qmk-dat-lai').onclick = async () => {
      const kq = await goiApi('xac-thuc/dat-lai-mat-khau', {
        method: 'POST',
        body: JSON.stringify({
          dinhDanh: document.getElementById('qmk-dinh-danh').value.trim(),
          maXacThuc: document.getElementById('qmk-ma-xac-thuc').value.trim(),
          matKhauMoi: document.getElementById('qmk-mat-khau-moi').value,
        }),
      });
      alert(kq.thongBao);
      if (kq.thanhCong) document.getElementById('modal-quen-mk').classList.add('an');
    };

    khoiTaoPhien();
  });
})();
