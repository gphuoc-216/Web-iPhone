// ============================================================================
// admin/js/admin.js
// Giao diện Quản trị Nội bộ theo kiến trúc BCE & phân quyền 3 vai trò nội bộ
// Đồng bộ thiết kế giao diện Mục 3.6 báo cáo v4 (Light Theme, chuẩn hóa ID điều khiển)
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
    maDanhMucDangChon: 0,
    maDienThoaiDangChon: 0,
    maTaiKhoanDangChon: 0,
    maVoucherDangChon: '',
  };

  // Menu phân quyền chặt chẽ theo 3 Actor nội bộ
  const MENU_THEO_VAI_TRO = {
    'Ban Quản lý': [
      { ma: 'bao-cao', nhan: 'Báo cáo & Thống kê' },
      { ma: 'danh-muc', nhan: 'Quản lý danh mục' },
      { ma: 'dien-thoai', nhan: 'Quản lý điện thoại' },
      { ma: 'chinh-sach-gia', nhan: 'Chính sách giá & Lợi nhuận' },
      { ma: 'duyet-phieu-nhap', nhan: 'Duyệt phiếu nhập kho' },
      { ma: 'duyet-kiem-ke', nhan: 'Duyệt kiểm kê & Ngưỡng kho' },
      { ma: 'phi-van-chuyen', nhan: 'Quy tắc phí vận chuyển' },
      { ma: 'khuyen-mai', nhan: 'Quản lý khuyến mãi' },
      { ma: 'voucher', nhan: 'Quản lý mã giảm giá' },
      { ma: 'tai-khoan', nhan: 'Quản lý tài khoản' },
    ],
    'Nhân viên Kho': [
      { ma: 'canh-bao-kho', nhan: 'Cảnh báo tồn kho' },
      { ma: 'phieu-nhap', nhan: 'Phiếu nhập kho' },
      { ma: 'phieu-xuat', nhan: 'Phiếu xuất kho' },
      { ma: 'kiem-ke', nhan: 'Kiểm kê tồn kho' },
    ],
    'Nhân viên Bán hàng / CSKH': [
      { ma: 'don-hang', nhan: 'Quản lý đơn hàng' },
      { ma: 'huy-don', nhan: 'Yêu cầu hủy & Hoàn tiền' },
      { ma: 'khach-hang', nhan: 'Tra cứu khách hàng' },
    ],
  };

  function dinhDangTien(so) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(Number(so) || 0)) + ' đ';
  }

  function maHoaHtml(chuoi) {
    return String(chuoi ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
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

  // ==================== XÁC THỰC NỘI BỘ ====================
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

  // ==================== HỘP THÔNG BÁO ====================
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
        <div>${maHoaHtml(tb.noiDung)}</div>
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
    document.getElementById('mo-ta-man-hinh').textContent = `Vai trò hiện tại: ${trangThai.taiKhoan.vaiTro}`;

    const banDoHam = {
      'bao-cao': () => renderBaoCao(),
      'danh-muc': renderDanhMuc,
      'dien-thoai': () => renderDienThoai(false),
      'chinh-sach-gia': renderChinhSachGia,
      'duyet-phieu-nhap': () => renderPhieuNhap(true),
      'phieu-nhap': () => renderPhieuNhap(false),
      'phieu-xuat': () => renderPhieuXuat(),
      'duyet-kiem-ke': () => renderKiemKe(true),
      'kiem-ke': () => renderKiemKe(false),
      'canh-bao-kho': renderCanhBaoKho,
      'phi-van-chuyen': renderPhiVanChuyen,
      'khuyen-mai': renderKhuyenMai,
      'voucher': () => renderVoucher(),
      'tai-khoan': () => renderTaiKhoan(),
      'don-hang': renderDonHang,
      'huy-don': renderHuyDon,
      'khach-hang': () => renderKhachHang(),
    };

    const khung = document.getElementById('khung-man-hinh');
    khung.innerHTML = '<div class="rong">Đang tải dữ liệu...</div>';
    if (banDoHam[maTab]) {
      banDoHam[maTab]();
    }
  }

  // ==================== 1. BÁO CÁO & THỐNG KÊ ====================
  async function renderBaoCao(boLoc = {}) {
    const qsDt = new URLSearchParams();
    const qsDh = new URLSearchParams();
    const qsSp = new URLSearchParams({ gioiHan: '10' });

    if (boLoc.tuNgay) { qsDt.set('tuNgay', boLoc.tuNgay); qsDh.set('tuNgay', boLoc.tuNgay); qsSp.set('tuNgay', boLoc.tuNgay); }
    if (boLoc.denNgay) { qsDt.set('denNgay', boLoc.denNgay); qsDh.set('denNgay', boLoc.denNgay); qsSp.set('denNgay', boLoc.denNgay); }
    if (boLoc.maDanhMuc) { qsSp.set('maDanhMuc', boLoc.maDanhMuc); }

    const [dt, dh, sp, tk, dmKq] = await Promise.all([
      goiApi('bao-cao' + (qsDt.toString() ? '?' + qsDt.toString() : '')),
      goiApi('bao-cao/don-hang' + (qsDh.toString() ? '?' + qsDh.toString() : '')),
      goiApi('bao-cao/san-pham-ban-chay?' + qsSp.toString()),
      goiApi('bao-cao/ton-kho'),
      goiApi('danh-muc'),
    ]);

    if (!dt.thanhCong || !dh.thanhCong || !sp.thanhCong) {
      hienThongBaoToast(dt.thongBao || dh.thongBao || sp.thongBao, false);
    }

    const dsDm = dmKq.duLieu || [];
    const duLieuDt = dt.duLieu || {};
    const chiTietDh = (dh.duLieu && dh.duLieu.chiTiet) || [];
    const chiTietSp = (sp.duLieu && sp.duLieu.chiTiet) || [];
    const chiTietTk = (tk.duLieu && tk.duLieu.chiTiet) || [];
    const tuHienThi = boLoc.tuNgay || duLieuDt.tuNgay || '';
    const denHienThi = boLoc.denNgay || duLieuDt.denNgay || '';

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang" style="padding:14px 18px; margin-bottom:16px;">
        <form id="form-loc-bc" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
          <div class="nhom-nhap" style="margin:0;">
            <label for="dtpTuNgay">Từ ngày</label>
            <input type="date" id="dtpTuNgay" value="${tuHienThi}" />
          </div>
          <div class="nhom-nhap" style="margin:0;">
            <label for="dtpDenNgay">Đến ngày</label>
            <input type="date" id="dtpDenNgay" value="${denHienThi}" />
          </div>
          <div class="nhom-nhap" style="margin:0;">
            <label for="cboDanhMucBaoCao">Danh mục sản phẩm bán chạy</label>
            <select id="cboDanhMucBaoCao">
              <option value="">Tất cả danh mục</option>
              ${dsDm.map(dm => `<option value="${dm.maDanhMuc}" ${String(boLoc.maDanhMuc || '') === String(dm.maDanhMuc) ? 'selected' : ''}>${maHoaHtml(dm.ten)}</option>`).join('')}
            </select>
          </div>
          <button type="submit" id="btnThongKe" class="nut nut-chinh">Thống kê</button>
          <button type="button" id="bc-mac-dinh" class="nut nut-phu">Tháng hiện tại</button>
        </form>
      </div>

      <div class="luoi-the">
        <div class="the-chi-so">
          <div class="nhan">Doanh thu đơn giao thành công</div>
          <div class="gia-tri" id="lblTongDoanhThu">${dinhDangTien(duLieuDt.tongDoanhThu)}</div>
          <div class="chu-phu">${tuHienThi && denHienThi ? `${tuHienThi} → ${denHienThi}` : 'Tháng hiện tại'} (${duLieuDt.soDonThanhCong || 0} đơn)</div>
        </div>
        <div class="the-chi-so">
          <div class="nhan">Tổng lợi nhuận gộp ước tính</div>
          <div class="gia-tri" id="lblTongLoiNhuan" style="color:#16a34a;">${dinhDangTien(duLieuDt.tongLoiNhuan)}</div>
        </div>
        <div class="the-chi-so">
          <div class="nhan">Tổng số đơn hàng</div>
          <div class="gia-tri">${(dh.duLieu && dh.duLieu.tongSoDon) || 0}</div>
          <div class="chu-phu">Thành công: ${(dh.duLieu && dh.duLieu.soDonThanhCong) || 0} · Đang xử lý: ${(dh.duLieu && dh.duLieu.soDonDangXuLy) || 0} · Đã hủy: ${(dh.duLieu && dh.duLieu.soDonDaHuy) || 0}</div>
        </div>
        <div class="the-chi-so">
          <div class="nhan">Mặt hàng cảnh báo tồn kho</div>
          <div class="gia-tri" style="color:#dc2626;">${chiTietTk.length}</div>
        </div>
      </div>

      <div class="luoi-2-cot">
        <div class="khung-bang">
          <div class="thanh-cong-cu"><strong>Thống kê đơn hàng theo trạng thái</strong></div>
          <table class="bang-du-lieu" id="grdDonHangTheoTrangThai">
            <thead><tr><th>Trạng thái</th><th>Số lượng</th><th>Tổng giá trị</th></tr></thead>
            <tbody>${chiTietDh.map(r => `<tr><td><span class="nhan-trang-thai ${lopTrangThai(r.trangThai)}">${r.trangThai}</span></td><td>${r.soLuong}</td><td>${dinhDangTien(r.tongGiaTri)}</td></tr>`).join('') || '<tr><td colspan="3" class="rong">Chưa có dữ liệu</td></tr>'}</tbody>
          </table>
        </div>

        <div class="khung-bang">
          <div class="thanh-cong-cu"><strong>Sản phẩm bán chạy (Đơn đã giao thành công)</strong></div>
          <table class="bang-du-lieu" id="grdSanPhamBanChay">
            <thead><tr><th>Sản phẩm</th><th>Đã bán</th><th>Doanh thu</th></tr></thead>
            <tbody>${chiTietSp.map(r => `<tr><td>${maHoaHtml(r.ten)}</td><td><strong>${r.tongSoLuongBan}</strong></td><td>${dinhDangTien(r.tongDoanhThu)}</td></tr>`).join('') || '<tr><td colspan="3" class="rong">Chưa có đơn hàng giao thành công trong khoảng lọc</td></tr>'}</tbody>
          </table>
        </div>
      </div>

      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Báo cáo hàng sắp hết / vượt ngưỡng tồn kho</strong></div>
        <table class="bang-du-lieu" id="grdHangSapHet">
          <thead><tr><th>Mã</th><th>Tên điện thoại</th><th>Tồn thực tế</th><th>Giữ chỗ</th><th>Tồn khả dụng</th><th>Mức cảnh báo</th></tr></thead>
          <tbody>${chiTietTk.map(r => `<tr><td>#${r.maDienThoai}</td><td>${maHoaHtml(r.tenDienThoai)}</td><td>${r.tonThucTe}</td><td>${r.giuCho}</td><td><strong>${r.tonKhaDung}</strong></td><td><span class="nhan-trang-thai ${lopTrangThai(r.mucDoCanhBao)}">${r.mucDoCanhBao}</span></td></tr>`).join('') || '<tr><td colspan="6" class="rong">Tồn kho ổn định</td></tr>'}</tbody>
        </table>
      </div>
    `;

    document.getElementById('form-loc-bc').onsubmit = (e) => {
      e.preventDefault();
      renderBaoCao({
        tuNgay: document.getElementById('dtpTuNgay').value,
        denNgay: document.getElementById('dtpDenNgay').value,
        maDanhMuc: document.getElementById('cboDanhMucBaoCao').value,
      });
    };
    document.getElementById('bc-mac-dinh').onclick = () => renderBaoCao({});
  }

  // ==================== 2. QUẢN LÝ DANH MỤC (Mục 3.6.1 - Bảng 173, Bảng 174) ====================
  async function renderDanhMuc() {
    const kq = await goiApi('danh-muc');
    const ds = kq.duLieu || [];
    trangThai.danhSachDanhMucCache = ds;
    if (!ds.some(d => Number(d.maDanhMuc) === trangThai.maDanhMucDangChon)) {
      trangThai.maDanhMucDangChon = ds[0] ? Number(ds[0].maDanhMuc) : 0;
    }

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Danh sách danh mục sản phẩm (${ds.length})</strong>
          <div class="nhom-hanh-dong">
            <button type="button" class="nut nut-chinh" id="btnThemDanhMuc">+ Thêm danh mục</button>
            <button type="button" class="nut nut-phu" id="btnSua" ${ds.length === 0 ? 'disabled' : ''}>Sửa dòng chọn</button>
            <button type="button" class="nut nut-nguy-hiem" id="btnXoa" ${ds.length === 0 ? 'disabled' : ''}>Xóa dòng chọn</button>
          </div>
        </div>
        <table class="bang-du-lieu" id="grdDanhMuc">
          <thead><tr><th>Mã</th><th>Tên danh mục</th><th>Mô tả</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(dm => `
              <tr data-dong-dm="${dm.maDanhMuc}" class="${Number(dm.maDanhMuc) === trangThai.maDanhMucDangChon ? 'dong-dang-chon' : ''}">
                <td>#${dm.maDanhMuc}</td>
                <td><strong>${maHoaHtml(dm.ten)}</strong></td>
                <td>${maHoaHtml(dm.moTa || '')}</td>
                <td class="nhom-hanh-dong">
                  <button type="button" class="nut nut-phu nut-nho" data-sua-dm="${dm.maDanhMuc}">Sửa</button>
                  <button type="button" class="nut nut-nguy-hiem nut-nho" data-xoa-dm="${dm.maDanhMuc}">Xóa</button>
                </td>
              </tr>
            `).join('') || '<tr><td colspan="4" class="rong">Chưa có danh mục nào</td></tr>'}
          </tbody>
        </table>
      </div>
    `;

    async function xuLySuaDm(maDm) {
      if (!maDm) return;
      const kqCt = await goiApi('danh-muc?maDanhMuc=' + maDm);
      if (!kqCt.thanhCong || !kqCt.duLieu) {
        hienThongBaoToast(kqCt.thongBao || 'Không thể tải thông tin danh mục', false);
        return;
      }
      moFormDanhMuc(kqCt.duLieu);
    }

    async function xuLyXoaDm(maDm) {
      if (!maDm) return;
      if (!confirm('Xác nhận xóa danh mục này?')) return;
      const r = await goiApi('danh-muc?maDanhMuc=' + maDm, { method: 'DELETE' });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) renderDanhMuc();
    }

    document.querySelectorAll('[data-dong-dm]').forEach(tr => {
      tr.onclick = (e) => {
        if (e.target.closest('button')) return;
        trangThai.maDanhMucDangChon = Number(tr.dataset.dongDm);
        document.querySelectorAll('[data-dong-dm]').forEach(r => r.classList.toggle('dong-dang-chon', Number(r.dataset.dongDm) === trangThai.maDanhMucDangChon));
      };
    });

    document.getElementById('btnThemDanhMuc').onclick = () => moFormDanhMuc();
    document.getElementById('btnSua').onclick = () => xuLySuaDm(trangThai.maDanhMucDangChon);
    document.getElementById('btnXoa').onclick = () => xuLyXoaDm(trangThai.maDanhMucDangChon);

    document.querySelectorAll('[data-sua-dm]').forEach(b => {
      b.onclick = () => xuLySuaDm(Number(b.dataset.suaDm));
    });
    document.querySelectorAll('[data-xoa-dm]').forEach(b => {
      b.onclick = () => xuLyXoaDm(Number(b.dataset.xoaDm));
    });
  }

  function moFormDanhMuc(dm = null) {
    moModal(dm ? 'Cập nhật danh mục' : 'Thêm danh mục mới', `
      <form id="form-dm" novalidate>
        <div class="nhom-nhap">
          <label for="txtTenDanhMuc">Tên danh mục *</label>
          <input id="txtTenDanhMuc" value="${dm ? maHoaHtml(dm.ten) : ''}" placeholder="Nhập tên danh mục..." />
        </div>
        <div class="nhom-nhap">
          <label for="txtMoTa">Mô tả</label>
          <textarea id="txtMoTa" rows="3" placeholder="Mô tả danh mục...">${dm ? maHoaHtml(dm.moTa || '') : ''}</textarea>
        </div>
        <button type="submit" id="${dm ? 'btnCapNhat' : 'btnLuu'}" class="nut nut-chinh nut-rong">${dm ? 'Cập nhật' : 'Lưu danh mục'}</button>
      </form>
    `);
    document.getElementById('form-dm').onsubmit = async (e) => {
      e.preventDefault();
      const duLieu = {
        ten: document.getElementById('txtTenDanhMuc').value,
        moTa: document.getElementById('txtMoTa').value,
      };
      if (dm) duLieu.maDanhMuc = dm.maDanhMuc;
      const r = await goiApi('danh-muc', { method: dm ? 'PUT' : 'POST', body: JSON.stringify(duLieu) });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) { dongModal(); renderDanhMuc(); }
    };
  }

  // ==================== 3. QUẢN LÝ ĐIỆN THOẠI (Mục 3.6.2 - Bảng 175, Bảng 176) ====================
  async function renderDienThoai(chiNgungKinhDoanh = false) {
    const duongDanDt = chiNgungKinhDoanh ? 'dien-thoai/ngung-kinh-doanh' : 'dien-thoai';
    const [kqDt, kqDm] = await Promise.all([goiApi(duongDanDt), goiApi('danh-muc')]);
    const ds = kqDt.duLieu || [];
    trangThai.danhSachSanPhamCache = ds;
    trangThai.danhSachDanhMucCache = kqDm.duLieu || [];
    if (!ds.some(sp => Number(sp.maDienThoai) === trangThai.maDienThoaiDangChon)) {
      trangThai.maDienThoaiDangChon = ds[0] ? Number(ds[0].maDienThoai) : 0;
    }

    const thongBaoRong = chiNgungKinhDoanh
      ? 'Chưa có sản phẩm ngừng kinh doanh'
      : 'Chưa có sản phẩm đang kinh doanh';

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu" style="flex-wrap:wrap; gap:10px;">
          <strong>${chiNgungKinhDoanh ? `Danh sách điện thoại ngừng kinh doanh (${ds.length})` : `Danh sách điện thoại đang kinh doanh (${ds.length})`}</strong>
          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <label style="display:flex; align-items:center; gap:6px; font-size:13.5px; cursor:pointer; user-select:none;">
              <input type="checkbox" id="chkNgungKinhDoanh" ${chiNgungKinhDoanh ? 'checked' : ''} style="width:auto;" />
              <span>Hiển thị sản phẩm ngừng kinh doanh</span>
            </label>
            <button type="button" class="nut nut-chinh" id="btnThemDienThoai">+ Thêm điện thoại</button>
            <button type="button" class="nut nut-phu" id="btnSua" ${ds.length === 0 ? 'disabled' : ''}>Sửa</button>
            ${chiNgungKinhDoanh
              ? `<button type="button" class="nut nut-thanh-cong" id="btnKhoiPhucKinhDoanh" ${ds.length === 0 ? 'disabled' : ''}>Khôi phục kinh doanh</button>`
              : `<button type="button" class="nut nut-nguy-hiem" id="btnXoa" ${ds.length === 0 ? 'disabled' : ''}>Xóa</button>`}
          </div>
        </div>
        <table class="bang-du-lieu" id="grdDienThoai">
          <thead><tr><th>Ảnh</th><th>Tên điện thoại</th><th>Hãng</th><th>Giá vốn BQ</th><th>Giá bán</th><th>Tồn (TT/GC/KD)</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.length > 0 ? ds.map(sp => `
              <tr data-dong-dt="${sp.maDienThoai}" class="${Number(sp.maDienThoai) === trangThai.maDienThoaiDangChon ? 'dong-dang-chon' : ''}">
                <td><img class="anh-nho" src="${sp.hinhAnh}" alt="${maHoaHtml(sp.ten)}" /></td>
                <td><strong>${maHoaHtml(sp.ten)}</strong><div class="chu-phu">#${sp.maDienThoai} · ${maHoaHtml(sp.tenDanhMuc)}</div></td>
                <td>${maHoaHtml(sp.hang)}</td>
                <td>${dinhDangTien(sp.giaVonBinhQuan)}</td>
                <td><strong>${dinhDangTien(sp.giaBan)}</strong></td>
                <td>${sp.tonKho.tonThucTe} / ${sp.tonKho.giuCho} / <strong>${sp.tonKho.tonKhaDung}</strong></td>
                <td><span class="nhan-trang-thai ${lopTrangThai(sp.trangThai)}">${sp.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  <button type="button" class="nut nut-phu nut-nho" data-sua-dt="${sp.maDienThoai}">Sửa</button>
                  ${sp.trangThai === 'Ngừng kinh doanh'
                    ? `<button type="button" class="nut nut-thanh-cong nut-nho" data-kp-dt="${sp.maDienThoai}">Khôi phục kinh doanh</button>`
                    : `<button type="button" class="nut nut-nguy-hiem nut-nho" data-xoa-dt="${sp.maDienThoai}">Xóa</button>`}
                </td>
              </tr>
            `).join('') : `<tr><td colspan="8" class="rong">${thongBaoRong}</td></tr>`}
          </tbody>
        </table>
      </div>
    `;

    async function xuLySuaDt(maDt) {
      if (!maDt) return;
      const kqCt = await goiApi('dien-thoai?maDienThoai=' + maDt);
      if (!kqCt.thanhCong || !kqCt.duLieu) {
        hienThongBaoToast(kqCt.thongBao || 'Không thể tải thông tin điện thoại', false);
        return;
      }
      moFormDienThoai(kqCt.duLieu, chiNgungKinhDoanh);
    }

    async function xuLyXoaDt(maDt) {
      if (!maDt) return;
      if (!confirm('Xác nhận xóa điện thoại này?')) return;
      const r = await goiApi('dien-thoai?maDienThoai=' + maDt, { method: 'DELETE' });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) renderDienThoai(chiNgungKinhDoanh);
    }

    async function xuLyKhoiPhucDt(maDt) {
      if (!maDt) return;
      const buocKiemTra = await goiApi('dien-thoai/khoi-phuc', {
        method: 'POST',
        body: JSON.stringify({ maDienThoai: maDt }),
      });
      if (!buocKiemTra.thanhCong) {
        hienThongBaoToast(buocKiemTra.thongBao, false);
        return;
      }
      if (!confirm('Xác nhận khôi phục kinh doanh sản phẩm này?')) return;
      const r = await goiApi('dien-thoai/xac-nhan-khoi-phuc', {
        method: 'POST',
        body: JSON.stringify({ maDienThoai: maDt }),
      });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) renderDienThoai(chiNgungKinhDoanh);
    }

    document.querySelectorAll('[data-dong-dt]').forEach(tr => {
      tr.onclick = (e) => {
        if (e.target.closest('button')) return;
        trangThai.maDienThoaiDangChon = Number(tr.dataset.dongDt);
        document.querySelectorAll('[data-dong-dt]').forEach(r => r.classList.toggle('dong-dang-chon', Number(r.dataset.dongDt) === trangThai.maDienThoaiDangChon));
      };
    });

    document.getElementById('chkNgungKinhDoanh').onchange = (e) => {
      renderDienThoai(e.target.checked);
    };
    document.getElementById('btnThemDienThoai').onclick = () => moFormDienThoai(null, chiNgungKinhDoanh);
    document.getElementById('btnSua').onclick = () => xuLySuaDt(trangThai.maDienThoaiDangChon);
    if (document.getElementById('btnXoa')) {
      document.getElementById('btnXoa').onclick = () => xuLyXoaDt(trangThai.maDienThoaiDangChon);
    }
    if (document.getElementById('btnKhoiPhucKinhDoanh')) {
      document.getElementById('btnKhoiPhucKinhDoanh').onclick = () => xuLyKhoiPhucDt(trangThai.maDienThoaiDangChon);
    }

    document.querySelectorAll('[data-sua-dt]').forEach(b => {
      b.onclick = () => xuLySuaDt(Number(b.dataset.suaDt));
    });
    document.querySelectorAll('[data-xoa-dt]').forEach(b => {
      b.onclick = () => xuLyXoaDt(Number(b.dataset.xoaDt));
    });
    document.querySelectorAll('[data-kp-dt]').forEach(b => {
      b.onclick = () => xuLyKhoiPhucDt(Number(b.dataset.kpDt));
    });
  }

  function moFormDienThoai(sp = null, chiNgungKinhDoanh = false) {
    const ts = (sp && sp.thongSoKyThuat) ? sp.thongSoKyThuat : {};
    const optDm = trangThai.danhSachDanhMucCache
      .map(dm => `<option value="${dm.maDanhMuc}" ${sp && dm.maDanhMuc == sp.maDanhMuc ? 'selected' : ''}>${maHoaHtml(dm.ten)}</option>`)
      .join('');

    const cacHangCoSan = ['Apple', 'Samsung', 'Xiaomi', 'OPPO', 'Vivo', 'Realme', 'Honor', 'Google'];
    if (sp && sp.hang && !cacHangCoSan.includes(sp.hang)) {
      cacHangCoSan.unshift(sp.hang);
    }
    const optHang = cacHangCoSan
      .map(h => `<option value="${maHoaHtml(h)}" ${sp && sp.hang === h ? 'selected' : ''}>${maHoaHtml(h)}</option>`)
      .join('');

    const cacHdhCoSan = ['iOS 18', 'iOS 17', 'Android 15', 'Android 14', 'Xiaomi HyperOS', 'ColorOS 15', 'One UI 7'];
    const hdhHienTai = ts.heDieuHanh || 'iOS 18';
    if (!cacHdhCoSan.includes(hdhHienTai)) {
      cacHdhCoSan.unshift(hdhHienTai);
    }
    const optHdh = cacHdhCoSan
      .map(hdh => `<option value="${maHoaHtml(hdh)}" ${hdhHienTai === hdh ? 'selected' : ''}>${maHoaHtml(hdh)}</option>`)
      .join('');

    moModal(sp ? `Cập nhật điện thoại #${sp.maDienThoai}` : 'Thêm điện thoại mới', `
      <form id="form-dt" novalidate>
        <div class="nhom-nhap">
          <label for="txtTenSanPham">Tên sản phẩm *</label>
          <input id="txtTenSanPham" value="${sp ? maHoaHtml(sp.ten) : ''}" placeholder="VD: iPhone 17 Pro Max 256GB" />
        </div>
        <div class="luoi-2-cot">
          <div class="nhom-nhap">
            <label for="cboDanhMuc">Danh mục *</label>
            <select id="cboDanhMuc">${optDm}</select>
          </div>
          <div class="nhom-nhap">
            <label for="cboHang">Hãng sản xuất *</label>
            <select id="cboHang">${optHang}</select>
          </div>
        </div>
        <div class="nhom-nhap">
          <label for="uplHinhAnh">${sp ? 'Tải ảnh sản phẩm mới (tùy chọn)' : 'Hình ảnh sản phẩm'}</label>
          <input id="uplHinhAnh" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" />
        </div>
        <div class="nhom-nhap">
          <label for="txtMoTa">Mô tả sản phẩm</label>
          <textarea id="txtMoTa" rows="2" placeholder="Mô tả nổi bật của điện thoại...">${sp ? maHoaHtml(sp.moTa || '') : ''}</textarea>
        </div>
        <h4 style="margin:10px 0 6px;">Thông số kỹ thuật</h4>
        <div class="luoi-2-cot">
          <div class="nhom-nhap">
            <label for="txtManHinh">Màn hình</label>
            <input id="txtManHinh" value="${maHoaHtml(ts.manHinh || 'OLED 6.7 inch 120Hz')}" />
          </div>
          <div class="nhom-nhap">
            <label for="txtChip">Chip xử lý</label>
            <input id="txtChip" value="${maHoaHtml(ts.chip || 'Apple A18 Pro')}" />
          </div>
          <div class="nhom-nhap">
            <label for="numRAM">RAM (GB) *</label>
            <input id="numRAM" type="number" min="1" value="${ts.ram || 8}" />
          </div>
          <div class="nhom-nhap">
            <label for="numROM">Bộ nhớ ROM (GB) *</label>
            <input id="numROM" type="number" min="8" value="${ts.rom || 256}" />
          </div>
          <div class="nhom-nhap">
            <label for="txtCamera">Camera</label>
            <input id="txtCamera" value="${maHoaHtml(ts.camera || '48MP + 12MP')}" />
          </div>
          <div class="nhom-nhap">
            <label for="numDungLuongPin">Dung lượng pin (mAh) *</label>
            <input id="numDungLuongPin" type="number" min="500" value="${ts.dungLuongPin || 4600}" />
          </div>
        </div>
        <div class="nhom-nhap">
          <label for="cboHeDieuHanh">Hệ điều hành</label>
          <select id="cboHeDieuHanh">${optHdh}</select>
        </div>
        <button type="submit" id="${sp ? 'btnCapNhat' : 'btnLuu'}" class="nut nut-chinh nut-rong">${sp ? 'Cập nhật điện thoại' : 'Lưu điện thoại mới'}</button>
      </form>
    `);

    document.getElementById('form-dt').onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData();
      if (sp) fd.append('maDienThoai', sp.maDienThoai);
      fd.append('ten', document.getElementById('txtTenSanPham').value.trim());
      fd.append('maDanhMuc', document.getElementById('cboDanhMuc').value);
      fd.append('hang', document.getElementById('cboHang').value.trim());
      fd.append('moTa', document.getElementById('txtMoTa').value.trim());
      fd.append('manHinh', document.getElementById('txtManHinh').value.trim());
      fd.append('chip', document.getElementById('txtChip').value.trim());
      fd.append('ram', document.getElementById('numRAM').value);
      fd.append('rom', document.getElementById('numROM').value);
      fd.append('camera', document.getElementById('txtCamera').value.trim());
      fd.append('dungLuongPin', document.getElementById('numDungLuongPin').value);
      fd.append('heDieuHanh', document.getElementById('cboHeDieuHanh').value.trim());

      const file = document.getElementById('uplHinhAnh').files[0];
      if (file) fd.append('hinhAnh', file);

      const url = sp ? 'dien-thoai/cap-nhat' : 'dien-thoai';
      const r = await goiApi(url, { method: 'POST', body: fd });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) { dongModal(); renderDienThoai(chiNgungKinhDoanh); }
    };
  }

  // ==================== 4. CHÍNH SÁCH GIÁ & LỢI NHUẬN ====================
  async function renderChinhSachGia() {
    const kq = await goiApi('chinh-sach-gia');
    const dsCh = (kq.duLieu && kq.duLieu.danhSachCauHinh) || [];
    const lichSu = (kq.duLieu && kq.duLieu.lichSuThayDoi) || [];

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang" style="padding:18px;">
        <h3 style="margin-top:0;">Thiết lập tỷ lệ lợi nhuận kỳ vọng</h3>
        <form id="form-gia" class="luoi-2-cot">
          <div class="nhom-nhap"><label for="cboPhamVi">Phạm vi áp dụng</label><input id="cboPhamVi" value="Toàn bộ sản phẩm" required /></div>
          <div class="nhom-nhap"><label for="numTyLe">Tỷ lệ lợi nhuận (%)</label><input id="numTyLe" type="number" step="0.1" min="0" max="500" value="${dsCh[0] ? dsCh[0].tyLe : 25}" required /></div>
          <div class="nhom-nhap"><label for="dtpNgayApDung">Ngày áp dụng</label><input id="dtpNgayApDung" type="date" value="${new Date().toISOString().slice(0, 10)}" /></div>
          <div class="nhom-nhap"><label for="txtGhiChu">Ghi chú</label><input id="txtGhiChu" placeholder="Lý do điều chỉnh chính sách giá..." /></div>
          <div style="grid-column:span 2;"><button type="submit" id="btnLuuCauHinh" class="nut nut-chinh">Cập nhật tỷ lệ & Tính lại giá bán</button></div>
        </form>
      </div>
      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Lịch sử thay đổi cấu hình lợi nhuận</strong></div>
        <table class="bang-du-lieu" id="grdLichSuLoiNhuan">
          <thead><tr><th>Thời điểm</th><th>Phạm vi</th><th>Tỷ lệ (%)</th><th>Ngày áp dụng</th></tr></thead>
          <tbody>${lichSu.map(ls => `<tr><td>${ls.thoiDiemGhi}</td><td>${maHoaHtml(ls.phamVi)}</td><td><strong>${ls.tyLe}%</strong></td><td>${ls.ngayApDung}</td></tr>`).join('') || '<tr><td colspan="4" class="rong">Chưa có lịch sử cấu hình</td></tr>'}</tbody>
        </table>
      </div>
    `;

    document.getElementById('form-gia').onsubmit = async (e) => {
      e.preventDefault();
      const r = await goiApi('chinh-sach-gia', {
        method: 'POST',
        body: JSON.stringify({
          phamVi: document.getElementById('cboPhamVi').value,
          tyLe: Number(document.getElementById('numTyLe').value),
          ngayApDung: document.getElementById('dtpNgayApDung').value,
          ghiChu: document.getElementById('txtGhiChu').value,
        }),
      });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) renderChinhSachGia();
    };
  }

  // ==================== 5. PHIẾU NHẬP KHO ====================
  async function renderPhieuNhap(chiDuyet, boLoc = {}) {
    const qs = new URLSearchParams();
    if (boLoc.trangThai) qs.set('trangThai', boLoc.trangThai);
    if (boLoc.tuNgay) qs.set('tuNgay', boLoc.tuNgay);
    if (boLoc.denNgay) qs.set('denNgay', boLoc.denNgay);

    const [kqPn, kqDt] = await Promise.all([
      goiApi('phieu-nhap' + (qs.toString() ? '?' + qs.toString() : '')),
      goiApi('dien-thoai'),
    ]);
    const ds = kqPn.duLieu || [];
    trangThai.danhSachSanPhamCache = kqDt.duLieu || [];

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu" style="flex-wrap:wrap; gap:10px;">
          <strong>${chiDuyet ? 'Danh sách phiếu nhập kho chờ phê duyệt' : 'Quản lý & Tra cứu phiếu nhập kho'}</strong>
          <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <select id="loc-pn-tt" style="padding:7px; border:1px solid #cbd5e1; border-radius:8px;">
              <option value="">Tất cả trạng thái</option>
              ${['Nháp', 'Chờ duyệt', 'Đã duyệt'].map(tt => `<option value="${tt}" ${boLoc.trangThai === tt ? 'selected' : ''}>${tt}</option>`).join('')}
            </select>
            <input type="date" id="loc-pn-tu" value="${boLoc.tuNgay || ''}" style="padding:6px; border:1px solid #cbd5e1; border-radius:8px;" />
            <input type="date" id="loc-pn-den" value="${boLoc.denNgay || ''}" style="padding:6px; border:1px solid #cbd5e1; border-radius:8px;" />
            <button type="button" class="nut nut-phu" id="btn-loc-pn">Lọc phiếu nhập</button>
            ${!chiDuyet ? '<button type="button" class="nut nut-chinh" id="btn-tao-pn">+ Lập phiếu nhập</button>' : ''}
          </div>
        </div>
        <table class="bang-du-lieu" id="grdPhieuNhap">
          <thead><tr><th>Mã</th><th>Nguồn nhập</th><th>Ngày nhập</th><th>Nhân viên</th><th>Chi tiết mặt hàng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(p => `
              <tr>
                <td>#PN${p.maPhieuNhap}</td>
                <td>${maHoaHtml(p.nguonNhap)}${p.lyDoTuChoi ? `<div class="chu-phu" style="color:#dc2626;">Lý do từ chối: ${maHoaHtml(p.lyDoTuChoi)}</div>` : ''}</td>
                <td>${p.ngayNhap}</td>
                <td>${maHoaHtml(p.tenNhanVien)}</td>
                <td>${(p.chiTiet || []).map(c => `${maHoaHtml(c.tenDienThoai)} (x${c.soLuong} @ ${dinhDangTien(c.donGiaNhap)})`).join('<br>')}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(p.trangThai)}">${p.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  ${!chiDuyet && p.trangThai === 'Nháp' ? `<button type="button" class="nut nut-chinh nut-nho" data-gui-pn="${p.maPhieuNhap}">Gửi duyệt</button>` : ''}
                  ${chiDuyet && p.trangThai === 'Chờ duyệt' ? `
                    <button type="button" class="nut nut-thanh-cong nut-nho" data-duyet-pn="${p.maPhieuNhap}">Duyệt phiếu</button>
                    <button type="button" class="nut nut-nguy-hiem nut-nho" data-tuchoi-pn="${p.maPhieuNhap}">Từ chối</button>
                  ` : ''}
                </td>
              </tr>
            `).join('') || '<tr><td colspan="7" class="rong">Không có phiếu nhập phù hợp</td></tr>'}
          </tbody>
        </table>
      </div>
    `;

    document.getElementById('btn-loc-pn').onclick = () => {
      renderPhieuNhap(chiDuyet, {
        trangThai: document.getElementById('loc-pn-tt').value,
        tuNgay: document.getElementById('loc-pn-tu').value,
        denNgay: document.getElementById('loc-pn-den').value,
      });
    };

    if (!chiDuyet && document.getElementById('btn-tao-pn')) {
      document.getElementById('btn-tao-pn').onclick = () => {
        const optSp = trangThai.danhSachSanPhamCache.map(sp => `<option value="${sp.maDienThoai}">${maHoaHtml(sp.ten)}</option>`).join('');
        const taoDongPnHtml = () => `
          <div class="dong-sp-pn" style="display:grid; grid-template-columns:2fr 1fr 1.4fr auto; gap:8px; margin-bottom:8px; align-items:center;">
            <select class="pn-sp">${optSp}</select>
            <input class="pn-sl" type="number" min="1" value="10" required placeholder="SL" />
            <input class="pn-gia" type="number" min="1000" value="20000000" required placeholder="Đơn giá nhập" />
            <button type="button" class="nut nut-nguy-hiem nut-nho btn-xoa-dong-pn" title="Xóa dòng">&times;</button>
          </div>
        `;
        moModal('Lập phiếu nhập kho mới', `
          <form id="form-pn">
            <div class="luoi-2-cot">
              <div class="nhom-nhap"><label for="txtNguonNhap">Nguồn nhập / Nhà cung cấp *</label><input id="txtNguonNhap" required placeholder="VD: Apple Việt Nam" /></div>
              <div class="nhom-nhap"><label for="dtpNgayNhap">Ngày nhập *</label><input id="dtpNgayNhap" type="date" value="${new Date().toISOString().slice(0, 10)}" required /></div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin:8px 0;">
              <strong>Danh sách mặt hàng nhập kho</strong>
              <button type="button" class="nut nut-phu nut-nho" id="btn-them-dong-pn">+ Thêm dòng sản phẩm</button>
            </div>
            <div id="ds-dong-pn">${taoDongPnHtml()}</div>
            <button type="submit" class="nut nut-chinh nut-rong" style="margin-top:10px;">Lưu phiếu nháp</button>
          </form>
        `);

        const vungDong = document.getElementById('ds-dong-pn');
        function ganSuKienXoaDongPn() {
          vungDong.querySelectorAll('.btn-xoa-dong-pn').forEach(btn => {
            btn.onclick = () => {
              if (vungDong.querySelectorAll('.dong-sp-pn').length > 1) {
                btn.closest('.dong-sp-pn').remove();
              } else {
                hienThongBaoToast('Phiếu nhập phải có ít nhất 1 dòng sản phẩm', false);
              }
            };
          });
        }
        ganSuKienXoaDongPn();
        document.getElementById('btn-them-dong-pn').onclick = () => {
          vungDong.insertAdjacentHTML('beforeend', taoDongPnHtml());
          ganSuKienXoaDongPn();
        };

        document.getElementById('form-pn').onsubmit = async (e) => {
          e.preventDefault();
          const chiTiet = Array.from(vungDong.querySelectorAll('.dong-sp-pn')).map(row => ({
            maDienThoai: Number(row.querySelector('.pn-sp').value),
            soLuong: Number(row.querySelector('.pn-sl').value),
            donGiaNhap: Number(row.querySelector('.pn-gia').value),
          }));
          const r = await goiApi('phieu-nhap', {
            method: 'POST',
            body: JSON.stringify({
              nguonNhap: document.getElementById('txtNguonNhap').value.trim(),
              ngayNhap: document.getElementById('dtpNgayNhap').value,
              chiTiet,
            }),
          });
          hienThongBaoToast(r.thongBao, r.thanhCong);
          if (r.thanhCong) { dongModal(); renderPhieuNhap(false, boLoc); }
        };
      };
    }

    document.querySelectorAll('[data-gui-pn]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('phieu-nhap/gui-duyet', { method: 'POST', body: JSON.stringify({ maPhieuNhap: Number(b.dataset.guiPn) }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuNhap(false, boLoc);
      };
    });
    document.querySelectorAll('[data-duyet-pn]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('phieu-nhap/duyet', { method: 'POST', body: JSON.stringify({ maPhieuNhap: Number(b.dataset.duyetPn) }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuNhap(true, boLoc);
      };
    });
    document.querySelectorAll('[data-tuchoi-pn]').forEach(b => {
      b.onclick = async () => {
        const lyDo = prompt('Nhập lý do từ chối phiếu nhập:');
        if (lyDo === null) return;
        const r = await goiApi('phieu-nhap/tu-choi', { method: 'POST', body: JSON.stringify({ maPhieuNhap: Number(b.dataset.tuchoiPn), lyDo }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuNhap(true, boLoc);
      };
    });
  }

  // ==================== 6. PHIẾU XUẤT KHO ====================
  async function renderPhieuXuat(boLoc = {}) {
    const qs = new URLSearchParams();
    if (boLoc.trangThai) qs.set('trangThai', boLoc.trangThai);
    if (boLoc.tuNgay) qs.set('tuNgay', boLoc.tuNgay);
    if (boLoc.denNgay) qs.set('denNgay', boLoc.denNgay);

    const [kqPx, kqDh] = await Promise.all([
      goiApi('phieu-xuat' + (qs.toString() ? '?' + qs.toString() : '')),
      goiApi('don-hang'),
    ]);
    const dsPx = kqPx.duLieu || [];
    const donSanSang = (kqDh.duLieu || []).filter(d => ['Đã duyệt', 'Đang đóng gói'].includes(d.trangThai) && !d.maPhieuXuat);

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Đơn hàng đã duyệt chờ lập phiếu xuất (${donSanSang.length})</strong>
        </div>
        <table class="bang-du-lieu" id="grdDonChoXuat">
          <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Trạng thái</th><th>Tổng tiền</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${donSanSang.map(d => `
              <tr>
                <td>#DH${d.maDonHang}</td><td>${maHoaHtml(d.tenKhachHang)}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(d.trangThai)}">${d.trangThai}</span></td>
                <td>${dinhDangTien(d.tongTien)}</td>
                <td><button type="button" class="nut nut-chinh nut-nho" data-lap-px="${d.maDonHang}">Lập phiếu xuất</button></td>
              </tr>
            `).join('') || '<tr><td colspan="5" class="rong">Không có đơn hàng nào đang chờ lập phiếu xuất</td></tr>'}
          </tbody>
        </table>
      </div>

      <div class="khung-bang">
        <div class="thanh-cong-cu" style="flex-wrap:wrap; gap:10px;">
          <strong>Danh sách phiếu xuất kho (${dsPx.length})</strong>
          <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <select id="loc-px-tt" style="padding:7px; border:1px solid #cbd5e1; border-radius:8px;">
              <option value="">Tất cả trạng thái</option>
              ${['Nháp', 'Đã xuất', 'Đã hủy'].map(tt => `<option value="${tt}" ${boLoc.trangThai === tt ? 'selected' : ''}>${tt}</option>`).join('')}
            </select>
            <input type="date" id="loc-px-tu" value="${boLoc.tuNgay || ''}" style="padding:6px; border:1px solid #cbd5e1; border-radius:8px;" />
            <input type="date" id="loc-px-den" value="${boLoc.denNgay || ''}" style="padding:6px; border:1px solid #cbd5e1; border-radius:8px;" />
            <button type="button" class="nut nut-phu" id="btn-loc-px">Lọc phiếu xuất</button>
          </div>
        </div>
        <table class="bang-du-lieu" id="grdPhieuXuat">
          <thead><tr><th>Mã PX</th><th>Mã ĐH</th><th>Ngày lập</th><th>Chi tiết</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${dsPx.map(px => `
              <tr>
                <td>#PX${px.maPhieuXuat}</td><td>#DH${px.maDonHang}</td><td>${px.ngayLap}</td>
                <td>${(px.chiTiet || []).map(c => `${maHoaHtml(c.tenDienThoai)} (x${c.soLuong})`).join('<br>')}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(px.trangThai)}">${px.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  ${px.trangThai === 'Nháp' ? `
                    <button type="button" class="nut nut-thanh-cong nut-nho" data-xn-px="${px.maPhieuXuat}">Xác nhận xuất kho</button>
                    <button type="button" class="nut nut-nguy-hiem nut-nho" data-huy-px="${px.maPhieuXuat}">Hủy nháp</button>
                  ` : ''}
                </td>
              </tr>
            `).join('') || '<tr><td colspan="6" class="rong">Không có phiếu xuất phù hợp</td></tr>'}
          </tbody>
        </table>
      </div>
    `;

    document.getElementById('btn-loc-px').onclick = () => {
      renderPhieuXuat({
        trangThai: document.getElementById('loc-px-tt').value,
        tuNgay: document.getElementById('loc-px-tu').value,
        denNgay: document.getElementById('loc-px-den').value,
      });
    };

    document.querySelectorAll('[data-lap-px]').forEach(b => {
      b.onclick = async () => {
        const ghiChu = prompt('Ghi chú phiếu xuất kho (tùy chọn):', 'Lập phiếu xuất kho cho đơn #' + b.dataset.lapPx);
        if (ghiChu === null) return;
        const r = await goiApi('phieu-xuat', { method: 'POST', body: JSON.stringify({ maDonHang: Number(b.dataset.lapPx), ghiChu }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuXuat(boLoc);
      };
    });
    document.querySelectorAll('[data-xn-px]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('phieu-xuat/xac-nhan', { method: 'POST', body: JSON.stringify({ maPhieuXuat: Number(b.dataset.xnPx) }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuXuat(boLoc);
      };
    });
    document.querySelectorAll('[data-huy-px]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('phieu-xuat/huy', { method: 'POST', body: JSON.stringify({ maPhieuXuat: Number(b.dataset.huyPx) }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderPhieuXuat(boLoc);
      };
    });
  }

  // ==================== 7. KIỂM KÊ & NGƯỠNG TỒN KHO ====================
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
        <div class="thanh-cong-cu"><strong>Danh sách mặt hàng cảnh báo tồn kho</strong></div>
        <table class="bang-du-lieu" id="grdCanhBaoTonKho">
          <thead><tr><th>Mã SP</th><th>Tên điện thoại</th><th>Tồn thực tế</th><th>Giữ chỗ</th><th>Tồn khả dụng</th><th>Loại cảnh báo</th></tr></thead>
          <tbody>${ds.map(r => `<tr><td>#${r.maDienThoai}</td><td>${maHoaHtml(r.tenDienThoai)}</td><td>${r.tonThucTe}</td><td>${r.giuCho}</td><td><strong>${r.tonKhaDung}</strong></td><td><span class="nhan-trang-thai ${lopTrangThai(r.mucDoCanhBao)}">${r.mucDoCanhBao}</span></td></tr>`).join('') || '<tr><td colspan="6" class="rong">Không có cảnh báo tồn kho</td></tr>'}</tbody>
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
          <h3 style="margin-top:0;">Cấu hình ngưỡng cảnh báo tồn kho</h3>
          <form id="form-nguong" class="luoi-2-cot">
            <div class="nhom-nhap"><label for="numNguongToiThieu">Ngưỡng tối thiểu</label><input id="numNguongToiThieu" type="number" min="0" value="${ch.nguongToiThieu}" required /></div>
            <div class="nhom-nhap"><label for="numNguongSom">Ngưỡng cảnh báo sớm</label><input id="numNguongSom" type="number" min="1" value="${ch.nguongSom}" required /></div>
            <div class="nhom-nhap"><label for="numNguongToiDa">Ngưỡng tối đa</label><input id="numNguongToiDa" type="number" min="2" value="${ch.nguongToiDa}" required /></div>
            <div style="display:flex; align-items:flex-end; margin-bottom:12px;"><button type="submit" id="btnLuuNguong" class="nut nut-chinh">Lưu cấu hình ngưỡng</button></div>
          </form>
        </div>
      ` : ''}

      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Danh sách biên bản kiểm kê tồn kho (${dsBb.length})</strong>
          ${!laBanQuanLy ? '<button type="button" class="nut nut-chinh" id="btn-tao-kk">+ Lập biên bản kiểm kê</button>' : ''}
        </div>
        <table class="bang-du-lieu" id="grdBienBanKiemKe">
          <thead><tr><th>Mã BB</th><th>Thời điểm</th><th>Nhân viên</th><th>Ghi chú</th><th>Chi tiết đối chiếu</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${dsBb.map(bb => `
              <tr>
                <td>#KK${bb.maBienBan}</td><td>${bb.thoiDiem}</td><td>${maHoaHtml(bb.tenNhanVien)}</td>
                <td>${maHoaHtml(bb.ghiChu || '')}${bb.lyDoTuChoi ? `<div class="chu-phu" style="color:#dc2626;">Yêu cầu kiểm lại: ${maHoaHtml(bb.lyDoTuChoi)}</div>` : ''}</td>
                <td>${(bb.chiTiet || []).map(c => `${maHoaHtml(c.tenDienThoai)}: HT ${c.soLuongHeThong} → TT ${c.soLuongThucTe} (Lệch ${c.chenhLech})`).join('<br>')}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(bb.trangThai)}">${bb.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  ${laBanQuanLy && bb.trangThai === 'Chờ duyệt' ? `
                    <button type="button" class="nut nut-thanh-cong nut-nho" data-duyet-kk="${bb.maBienBan}">Duyệt biên bản</button>
                    <button type="button" class="nut nut-canh-bao nut-nho" data-ktra-kk="${bb.maBienBan}">Yêu cầu kiểm lại</button>
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
            nguongToiThieu: Number(document.getElementById('numNguongToiThieu').value),
            nguongSom: Number(document.getElementById('numNguongSom').value),
            nguongToiDa: Number(document.getElementById('numNguongToiDa').value),
          }),
        });
        hienThongBaoToast(r.thongBao, r.thanhCong);
      };
    }

    if (!laBanQuanLy && document.getElementById('btn-tao-kk')) {
      document.getElementById('btn-tao-kk').onclick = () => {
        const optSp = trangThai.danhSachSanPhamCache
          .map(sp => `<option value="${sp.maDienThoai}" data-ton="${sp.tonKho.tonThucTe}">${maHoaHtml(sp.ten)} (Tồn HT: ${sp.tonKho.tonThucTe})</option>`)
          .join('');
        const taoDongKkHtml = () => `
          <div class="dong-sp-kk" style="display:grid; grid-template-columns:2.4fr 1fr auto; gap:8px; margin-bottom:8px; align-items:center;">
            <select class="kk-sp">${optSp}</select>
            <input class="kk-sl" type="number" min="0" required placeholder="SL thực tế" />
            <button type="button" class="nut nut-nguy-hiem nut-nho btn-xoa-dong-kk" title="Xóa dòng">&times;</button>
          </div>
        `;
        moModal('Lập biên bản kiểm kê tồn kho', `
          <form id="form-kk">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
              <strong>Danh sách sản phẩm kiểm đếm</strong>
              <button type="button" class="nut nut-phu nut-nho" id="btn-them-dong-kk">+ Thêm dòng sản phẩm</button>
            </div>
            <div id="ds-dong-kk">${taoDongKkHtml()}</div>
            <div class="nhom-nhap" style="margin-top:10px;"><label for="kk-ghichu">Ghi chú giải trình (bắt buộc nếu phát sinh chênh lệch)</label><textarea id="kk-ghichu" rows="3"></textarea></div>
            <button type="submit" class="nut nut-chinh nut-rong">Hoàn tất biên bản kiểm kê</button>
          </form>
        `);

        const vungDong = document.getElementById('ds-dong-kk');
        function ganSuKienDongKk() {
          vungDong.querySelectorAll('.btn-xoa-dong-kk').forEach(btn => {
            btn.onclick = () => {
              if (vungDong.querySelectorAll('.dong-sp-kk').length > 1) {
                btn.closest('.dong-sp-kk').remove();
              } else {
                hienThongBaoToast('Biên bản kiểm kê cần ít nhất 1 sản phẩm', false);
              }
            };
          });
        }
        ganSuKienDongKk();
        document.getElementById('btn-them-dong-kk').onclick = () => {
          vungDong.insertAdjacentHTML('beforeend', taoDongKkHtml());
          ganSuKienDongKk();
        };

        document.getElementById('form-kk').onsubmit = async (e) => {
          e.preventDefault();
          const chiTiet = Array.from(vungDong.querySelectorAll('.dong-sp-kk')).map(row => ({
            maDienThoai: Number(row.querySelector('.kk-sp').value),
            soLuongThucTe: Number(row.querySelector('.kk-sl').value),
          }));
          const r = await goiApi('kiem-ke', {
            method: 'POST',
            body: JSON.stringify({
              ghiChu: document.getElementById('kk-ghichu').value.trim(),
              chiTiet,
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
        if (lyDo === null) return;
        const r = await goiApi('kiem-ke/tu-choi', { method: 'POST', body: JSON.stringify({ maBienBan: Number(b.dataset.ktraKk), lyDo }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderKiemKe(true);
      };
    });
  }

  // ==================== 8. QUY TẮC PHÍ VẬN CHUYỂN ====================
  async function renderPhiVanChuyen() {
    const kq = await goiApi('phi-van-chuyen');
    const ds = kq.duLieu || [];
    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Cấu hình quy tắc phí vận chuyển</strong>
          <button type="button" class="nut nut-chinh" id="btn-them-phi">+ Thêm quy tắc</button>
        </div>
        <table class="bang-du-lieu" id="grdQuyTacPhi">
          <thead><tr><th>Mã</th><th>Khu vực</th><th>Mức phí</th><th>Phí mặc định</th><th>Ngưỡng miễn phí</th><th>Thao tác</th></tr></thead>
          <tbody>${ds.map(q => `<tr><td>#${q.maQuyTac}</td><td><strong>${maHoaHtml(q.khuVuc)}</strong></td><td>${dinhDangTien(q.mucPhi)}</td><td>${dinhDangTien(q.mucPhiMacDinh)}</td><td>${dinhDangTien(q.nguongMienPhi)}</td><td><button type="button" class="nut nut-phu nut-nho" data-sua-phi="${q.maQuyTac}">Sửa</button></td></tr>`).join('')}</tbody>
        </table>
      </div>
    `;
    function moFormPhi(qt = null) {
      moModal(qt ? 'Cập nhật quy tắc phí vận chuyển' : 'Thêm quy tắc phí vận chuyển', `
        <form id="form-phi">
          <div class="nhom-nhap"><label for="txtKhuVuc">Khu vực</label><input id="txtKhuVuc" required value="${qt ? maHoaHtml(qt.khuVuc) : ''}" /></div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label for="numMucPhi">Mức phí khu vực (VNĐ)</label><input id="numMucPhi" type="number" min="0" value="${qt ? qt.mucPhi : 30000}" required /></div>
            <div class="nhom-nhap"><label for="numMucPhiMacDinh">Mức phí mặc định (VNĐ)</label><input id="numMucPhiMacDinh" type="number" min="0" value="${qt ? qt.mucPhiMacDinh : 35000}" required /></div>
          </div>
          <div class="nhom-nhap"><label for="numNguongMienPhi">Ngưỡng miễn phí vận chuyển (VNĐ)</label><input id="numNguongMienPhi" type="number" min="0" value="${qt ? qt.nguongMienPhi : 10000000}" required /></div>
          <button type="submit" id="btnLuuQuyTac" class="nut nut-chinh nut-rong">Lưu quy tắc</button>
        </form>
      `);
      document.getElementById('form-phi').onsubmit = async (e) => {
        e.preventDefault();
        const duLieu = {
          khuVuc: document.getElementById('txtKhuVuc').value.trim(),
          mucPhi: Number(document.getElementById('numMucPhi').value),
          mucPhiMacDinh: Number(document.getElementById('numMucPhiMacDinh').value),
          nguongMienPhi: Number(document.getElementById('numNguongMienPhi').value),
        };
        if (qt) duLieu.maQuyTac = qt.maQuyTac;
        const r = await goiApi('phi-van-chuyen', { method: 'POST', body: JSON.stringify(duLieu) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) { dongModal(); renderPhiVanChuyen(); }
      };
    }
    document.getElementById('btn-them-phi').onclick = () => moFormPhi();
    document.querySelectorAll('[data-sua-phi]').forEach(b => {
      b.onclick = () => moFormPhi(ds.find(x => x.maQuyTac == b.dataset.suaPhi));
    });
  }

  // ==================== 9. QUẢN LÝ KHUYẾN MÃI & MÃ GIẢM GIÁ ====================
  async function renderKhuyenMai() {
    const [kqKm, kqDt] = await Promise.all([goiApi('khuyen-mai'), goiApi('dien-thoai')]);
    const ds = kqKm.duLieu || [];
    const dsSanPham = kqDt.duLieu || [];
    trangThai.danhSachSanPhamCache = dsSanPham;

    const tenSpMap = {};
    dsSanPham.forEach(sp => { tenSpMap[Number(sp.maDienThoai)] = sp.ten; });

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Chương trình khuyến mãi theo sản phẩm (${ds.length})</strong>
          <button type="button" class="nut nut-chinh" id="btn-them-km">+ Tạo chương trình khuyến mãi</button>
        </div>
        <table class="bang-du-lieu" id="grdKhuyenMai">
          <thead><tr><th>Mã</th><th>Tên chương trình</th><th>Ưu đãi</th><th>Sản phẩm áp dụng</th><th>Thời gian</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(km => {
              const maSps = km.danhSachMaDienThoai || [];
              const chuoiSp = maSps.length > 0
                ? maSps.map(id => maHoaHtml(tenSpMap[Number(id)] || ('#' + id))).join(', ')
                : '<span style="color:#dc2626;">Chưa chọn sản phẩm</span>';
              const dk = km.dieuKienApDung || {};
              return `
              <tr>
                <td>#${km.maKhuyenMai}</td>
                <td>
                  <strong>${maHoaHtml(km.ten)}</strong>
                  <div class="chu-phu">${maHoaHtml(km.noiDung || '')}</div>
                  <div class="chu-phu">ĐK: Tối thiểu ${dinhDangTien(dk.giaTriToiThieu || 0)} · Nhóm: ${maHoaHtml(dk.nhomHang || 'Tất cả')}</div>
                </td>
                <td><strong>-${km.mucUuDai}%</strong></td>
                <td style="max-width:240px; font-size:12.5px;">${chuoiSp}</td>
                <td>${km.ngayBatDau} → ${km.ngayKetThuc}<div class="chu-phu">${maHoaHtml(km.khungGio || '00:00-23:59')}</div></td>
                <td><span class="nhan-trang-thai ${lopTrangThai(km.trangThai)}">${km.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  <button type="button" class="nut nut-phu nut-nho" data-sua-km="${km.maKhuyenMai}">Sửa</button>
                  <button type="button" class="nut nut-canh-bao nut-nho" data-doi-km="${km.maKhuyenMai}" data-tt="${km.trangThai === 'Đang áp dụng' ? 'Ngừng áp dụng' : 'Đang áp dụng'}">${km.trangThai === 'Đang áp dụng' ? 'Ngừng' : 'Bật'}</button>
                  <button type="button" class="nut nut-nguy-hiem nut-nho" data-xoa-km="${km.maKhuyenMai}">Xóa</button>
                </td>
              </tr>`;
            }).join('') || '<tr><td colspan="7" class="rong">Chưa có chương trình khuyến mãi</td></tr>'}
          </tbody>
        </table>
      </div>
    `;

    function moFormKm(km = null) {
      const maDaChon = new Set((km && km.danhSachMaDienThoai ? km.danhSachMaDienThoai : []).map(Number));
      const dk = (km && km.dieuKienApDung) ? km.dieuKienApDung : { giaTriToiThieu: 0, nhomHang: 'Tất cả', doiTuong: 'Tất cả khách hàng' };

      moModal(km ? `Cập nhật chương trình khuyến mãi #${km.maKhuyenMai}` : 'Tạo chương trình khuyến mãi mới', `
        <form id="form-km">
          <div class="nhom-nhap"><label>Tên chương trình *</label><input id="km-ten" required value="${km ? maHoaHtml(km.ten) : ''}" /></div>
          <div class="nhom-nhap"><label>Nội dung mô tả</label><input id="km-nd" value="${km ? maHoaHtml(km.noiDung || '') : ''}" /></div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Mức ưu đãi (%) *</label><input id="km-ud" type="number" min="1" max="100" value="${km ? km.mucUuDai : 10}" required /></div>
            <div class="nhom-nhap"><label>Khung giờ áp dụng</label><input id="km-gio" value="${km ? maHoaHtml(km.khungGio || '00:00-23:59') : '00:00-23:59'}" /></div>
          </div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Ngày bắt đầu *</label><input id="km-bd" type="date" value="${km ? km.ngayBatDau : new Date().toISOString().slice(0, 10)}" required /></div>
            <div class="nhom-nhap"><label>Ngày kết thúc *</label><input id="km-kt" type="date" value="${km ? km.ngayKetThuc : new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10)}" required /></div>
          </div>

          <h4 style="margin:10px 0 6px;">Điều kiện áp dụng</h4>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Giá trị tối thiểu (VNĐ)</label><input id="km-dk-tt" type="number" min="0" value="${dk.giaTriToiThieu || 0}" /></div>
            <div class="nhom-nhap"><label>Nhóm hàng</label><input id="km-dk-nhom" value="${maHoaHtml(dk.nhomHang || 'Tất cả')}" placeholder="Tất cả / Apple / Samsung..." /></div>
          </div>
          <div class="nhom-nhap">
            <label>Đối tượng khách hàng</label>
            <select id="km-dk-dt">
              ${['Tất cả khách hàng', 'Khách hàng đã đăng nhập', 'Khách hàng mới'].map(dt => `<option value="${dt}" ${dk.doiTuong === dt ? 'selected' : ''}>${dt}</option>`).join('')}
            </select>
          </div>

          <div class="nhom-nhap">
            <label>Chọn sản phẩm điện thoại áp dụng khuyến mãi (${dsSanPham.length} SP) *</label>
            <div style="max-height:165px; overflow-y:auto; border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px; background:#f8fafc;">
              ${dsSanPham.map(sp => `
                <label style="display:flex; align-items:center; gap:8px; padding:4px 0; font-size:13px; cursor:pointer;">
                  <input type="checkbox" class="km-chon-sp" value="${sp.maDienThoai}" ${maDaChon.has(Number(sp.maDienThoai)) ? 'checked' : ''} style="width:auto;" />
                  <span><strong>${maHoaHtml(sp.ten)}</strong> (${maHoaHtml(sp.hang)} — ${dinhDangTien(sp.giaBan)})</span>
                </label>
              `).join('')}
            </div>
          </div>

          <button type="submit" class="nut nut-chinh nut-rong">Lưu chương trình khuyến mãi</button>
        </form>
      `);

      document.getElementById('form-km').onsubmit = async (e) => {
        e.preventDefault();
        const danhSachMaDienThoai = Array.from(document.querySelectorAll('.km-chon-sp:checked')).map(cb => Number(cb.value));
        if (danhSachMaDienThoai.length === 0) {
          hienThongBaoToast('Vui lòng chọn ít nhất 1 sản phẩm áp dụng khuyến mãi', false);
          return;
        }
        const duLieu = {
          ten: document.getElementById('km-ten').value.trim(),
          noiDung: document.getElementById('km-nd').value.trim(),
          mucUuDai: Number(document.getElementById('km-ud').value),
          khungGio: document.getElementById('km-gio').value.trim() || '00:00-23:59',
          ngayBatDau: document.getElementById('km-bd').value,
          ngayKetThuc: document.getElementById('km-kt').value,
          danhSachMaDienThoai,
          dieuKienApDung: {
            giaTriToiThieu: Number(document.getElementById('km-dk-tt').value || 0),
            nhomHang: document.getElementById('km-dk-nhom').value.trim() || 'Tất cả',
            doiTuong: document.getElementById('km-dk-dt').value,
          },
        };
        if (km) duLieu.maKhuyenMai = km.maKhuyenMai;
        const r = await goiApi('khuyen-mai', { method: km ? 'PUT' : 'POST', body: JSON.stringify(duLieu) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) { dongModal(); renderKhuyenMai(); }
      };
    }

    document.getElementById('btn-them-km').onclick = () => moFormKm();
    document.querySelectorAll('[data-sua-km]').forEach(b => {
      b.onclick = () => moFormKm(ds.find(x => x.maKhuyenMai == b.dataset.suaKm));
    });
    document.querySelectorAll('[data-doi-km]').forEach(b => {
      b.onclick = async () => {
        const r = await goiApi('khuyen-mai', { method: 'PATCH', body: JSON.stringify({ maKhuyenMai: Number(b.dataset.doiKm), trangThai: b.dataset.tt }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderKhuyenMai();
      };
    });
    document.querySelectorAll('[data-xoa-km]').forEach(b => {
      b.onclick = async () => {
        if (!confirm('Xác nhận xóa chương trình khuyến mãi này?')) return;
        const r = await goiApi('khuyen-mai?maKhuyenMai=' + b.dataset.xoaKm, { method: 'DELETE' });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderKhuyenMai();
      };
    });
  }

  // Mục 3.6.28 - Bảng 227, Bảng 228: Quản lý mã giảm giá
  async function renderVoucher(tuKhoa = '', trangThaiLoc = '') {
    const kq = await goiApi('voucher' + (trangThaiLoc ? '?trangThai=' + encodeURIComponent(trangThaiLoc) : ''));
    const tatCa = kq.duLieu || [];
    const tk = tuKhoa.trim().toLowerCase();
    const ds = tk ? tatCa.filter(v => v.maVoucher.toLowerCase().includes(tk) || v.trangThai.toLowerCase().includes(tk)) : tatCa;
    if (!ds.some(v => v.maVoucher === trangThai.maVoucherDangChon)) {
      trangThai.maVoucherDangChon = ds[0] ? ds[0].maVoucher : '';
    }

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu" style="flex-wrap:wrap; gap:8px;">
          <strong>Danh sách mã giảm giá (${ds.length})</strong>
          <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <select id="cboTrangThai" style="padding:7px; border:1px solid #cbd5e1; border-radius:8px;">
              <option value="">Tất cả trạng thái</option>
              ${['Đang hiệu lực', 'Đã khóa', 'Hết hạn'].map(tt => `<option value="${tt}" ${trangThaiLoc === tt ? 'selected' : ''}>${tt}</option>`).join('')}
            </select>
            <input id="tim-vc" value="${maHoaHtml(tuKhoa)}" placeholder="Tra cứu mã giảm giá..." style="padding:7px 10px; border:1px solid #cbd5e1; border-radius:8px;" />
            <button type="button" class="nut nut-phu" id="btnLocXem">Lọc / Xem</button>
            <button type="button" class="nut nut-chinh" id="btnTao">+ Tạo mã giảm giá</button>
            <button type="button" class="nut nut-canh-bao" id="btnKhoa" ${ds.length === 0 ? 'disabled' : ''}>Khóa / Mở</button>
            <button type="button" class="nut nut-nguy-hiem" id="btnXoa" ${ds.length === 0 ? 'disabled' : ''}>Xóa</button>
          </div>
        </div>
        <table class="bang-du-lieu" id="grdQlvc">
          <thead><tr><th>Mã giảm giá</th><th>Loại & Giá trị</th><th>Lượt dùng / Tổng</th><th>Điều kiện áp dụng</th><th>Hiệu lực</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(v => {
              const dk = v.dieuKienApDung || {};
              return `
              <tr data-dong-vc="${maHoaHtml(v.maVoucher)}" class="${v.maVoucher === trangThai.maVoucherDangChon ? 'dong-dang-chon' : ''}">
                <td><strong>${maHoaHtml(v.maVoucher)}</strong></td>
                <td>${v.loaiGiam}: <strong>${v.loaiGiam === 'Phần trăm' ? v.giaTriGiam + '%' : dinhDangTien(v.giaTriGiam)}</strong></td>
                <td>${v.luotDaDung} / ${v.soLuong} <span class="chu-phu">(Tối đa ${v.gioiHanSuDung}/khách)</span></td>
                <td style="font-size:12.5px;">Đơn tối thiểu: <strong>${dinhDangTien(dk.giaTriToiThieu || 0)}</strong><div class="chu-phu">${maHoaHtml(dk.nhomHang || 'Tất cả')} · ${maHoaHtml(dk.doiTuong || 'Tất cả khách hàng')}</div></td>
                <td>${v.ngayBatDau} → ${v.ngayKetThuc}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(v.trangThai)}">${v.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  <button type="button" class="nut nut-phu nut-nho" data-sua-vc="${maHoaHtml(v.maVoucher)}">Sửa</button>
                  <button type="button" class="nut nut-canh-bao nut-nho" data-khoa-vc="${maHoaHtml(v.maVoucher)}" data-tt="${v.trangThai === 'Đã khóa' ? 'Đang hiệu lực' : 'Đã khóa'}">${v.trangThai === 'Đã khóa' ? 'Mở khóa' : 'Khóa'}</button>
                  <button type="button" class="nut nut-nguy-hiem nut-nho" data-xoa-vc="${maHoaHtml(v.maVoucher)}">Xóa</button>
                </td>
              </tr>`;
            }).join('') || '<tr><td colspan="7" class="rong">Không tìm thấy mã giảm giá phù hợp</td></tr>'}
          </tbody>
        </table>
      </div>
    `;

    document.querySelectorAll('[data-dong-vc]').forEach(tr => {
      tr.onclick = (e) => {
        if (e.target.closest('button')) return;
        trangThai.maVoucherDangChon = tr.dataset.dongVc;
        document.querySelectorAll('[data-dong-vc]').forEach(r => r.classList.toggle('dong-dang-chon', r.dataset.dongVc === trangThai.maVoucherDangChon));
      };
    });

    document.getElementById('cboTrangThai').onchange = (e) => renderVoucher(document.getElementById('tim-vc').value, e.target.value);
    document.getElementById('btnLocXem').onclick = () => renderVoucher(document.getElementById('tim-vc').value, document.getElementById('cboTrangThai').value);

    function moFormVc(v = null) {
      const dk = (v && v.dieuKienApDung) ? v.dieuKienApDung : { giaTriToiThieu: 0, nhomHang: 'Tất cả', doiTuong: 'Khách hàng đã đăng nhập' };
      moModal(v ? `Cập nhật mã giảm giá ${v.maVoucher}` : 'Tạo mã giảm giá mới', `
        <form id="form-vc" novalidate>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label for="txtMaVoucher">Mã giảm giá *</label><input id="txtMaVoucher" value="${v ? maHoaHtml(v.maVoucher) : ''}" ${v ? 'readonly' : ''} placeholder="VD: TET2027" /></div>
            <div class="nhom-nhap"><label for="cboLoaiGiam">Loại giảm *</label><select id="cboLoaiGiam"><option value="Số tiền" ${v && v.loaiGiam === 'Số tiền' ? 'selected' : ''}>Số tiền (VNĐ)</option><option value="Phần trăm" ${v && v.loaiGiam === 'Phần trăm' ? 'selected' : ''}>Phần trăm (%)</option></select></div>
          </div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label for="numGiaTriGiam">Giá trị giảm *</label><input id="numGiaTriGiam" type="number" min="1" value="${v ? v.giaTriGiam : 500000}" /></div>
            <div class="nhom-nhap"><label for="numSoLuong">Tổng số lượng phát hành *</label><input id="numSoLuong" type="number" min="1" value="${v ? v.soLuong : 100}" /></div>
          </div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label for="numGioiHan">Giới hạn sử dụng / mỗi khách *</label><input id="numGioiHan" type="number" min="1" value="${v ? v.gioiHanSuDung : 1}" /></div>
            <div class="nhom-nhap"><label for="txtDieuKien">Giá trị đơn tối thiểu (VNĐ)</label><input id="txtDieuKien" type="number" min="0" value="${dk.giaTriToiThieu || 0}" /></div>
          </div>
          <div id="dtpThoiGian" class="luoi-2-cot">
            <div class="nhom-nhap"><label for="vc-bd">Ngày bắt đầu *</label><input id="vc-bd" type="date" value="${v ? v.ngayBatDau : new Date().toISOString().slice(0, 10)}" /></div>
            <div class="nhom-nhap"><label for="vc-kt">Ngày kết thúc *</label><input id="vc-kt" type="date" value="${v ? v.ngayKetThuc : new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10)}" /></div>
          </div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label for="vc-dk-nhom">Nhóm hàng áp dụng</label><input id="vc-dk-nhom" value="${maHoaHtml(dk.nhomHang || 'Tất cả')}" placeholder="Tất cả / Apple / Samsung..." /></div>
            <div class="nhom-nhap">
              <label for="vc-dk-dt">Đối tượng áp dụng</label>
              <select id="vc-dk-dt">
                ${['Tất cả khách hàng', 'Khách hàng đã đăng nhập', 'Khách hàng mới'].map(dt => `<option value="${dt}" ${dk.doiTuong === dt ? 'selected' : ''}>${dt}</option>`).join('')}
              </select>
            </div>
          </div>
          <button type="submit" id="${v ? 'btnCapNhat' : 'btnTao'}" class="nut nut-chinh nut-rong">${v ? 'Cập nhật mã giảm giá' : 'Tạo mã giảm giá'}</button>
        </form>
      `);
      document.getElementById('form-vc').onsubmit = async (e) => {
        e.preventDefault();
        const r = await goiApi('voucher', {
          method: v ? 'PUT' : 'POST',
          body: JSON.stringify({
            maVoucher: document.getElementById('txtMaVoucher').value.trim(),
            loaiGiam: document.getElementById('cboLoaiGiam').value,
            giaTriGiam: Number(document.getElementById('numGiaTriGiam').value),
            soLuong: Number(document.getElementById('numSoLuong').value),
            gioiHanSuDung: Number(document.getElementById('numGioiHan').value),
            ngayBatDau: document.getElementById('vc-bd').value,
            ngayKetThuc: document.getElementById('vc-kt').value,
            dieuKienApDung: {
              giaTriToiThieu: Number(document.getElementById('txtDieuKien').value || 0),
              nhomHang: document.getElementById('vc-dk-nhom').value.trim() || 'Tất cả',
              doiTuong: document.getElementById('vc-dk-dt').value,
            },
          }),
        });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) { dongModal(); renderVoucher(tuKhoa, trangThaiLoc); }
      };
    }

    async function xuLySuaVc(maVc) {
      if (!maVc) return;
      const kqCt = await goiApi('voucher?maVoucher=' + encodeURIComponent(maVc));
      if (!kqCt.thanhCong || !kqCt.duLieu) {
        hienThongBaoToast(kqCt.thongBao || 'Không thể tải thông tin mã giảm giá', false);
        return;
      }
      moFormVc(kqCt.duLieu);
    }

    async function xuLyKhoaVc(maVc, ttMoi = null) {
      if (!maVc) return;
      const vcObj = ds.find(x => x.maVoucher === maVc);
      const trangThaiDich = ttMoi || (vcObj && vcObj.trangThai === 'Đã khóa' ? 'Đang hiệu lực' : 'Đã khóa');
      const r = await goiApi('voucher', { method: 'PATCH', body: JSON.stringify({ maVoucher: maVc, trangThai: trangThaiDich }) });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) renderVoucher(tuKhoa, trangThaiLoc);
    }

    async function xuLyXoaVc(maVc) {
      if (!maVc) return;
      if (!confirm('Xác nhận xóa mã giảm giá này?')) return;
      const r = await goiApi('voucher?maVoucher=' + encodeURIComponent(maVc), { method: 'DELETE' });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) renderVoucher(tuKhoa, trangThaiLoc);
    }

    document.getElementById('btnTao').onclick = () => moFormVc();
    document.getElementById('btnKhoa').onclick = () => xuLyKhoaVc(trangThai.maVoucherDangChon);
    document.getElementById('btnXoa').onclick = () => xuLyXoaVc(trangThai.maVoucherDangChon);

    document.querySelectorAll('[data-sua-vc]').forEach(b => {
      b.onclick = () => xuLySuaVc(b.dataset.suaVc);
    });
    document.querySelectorAll('[data-khoa-vc]').forEach(b => {
      b.onclick = () => xuLyKhoaVc(b.dataset.khoaVc, b.dataset.tt);
    });
    document.querySelectorAll('[data-xoa-vc]').forEach(b => {
      b.onclick = () => xuLyXoaVc(b.dataset.xoaVc);
    });
  }

  // ==================== 10. QUẢN LÝ TÀI KHOẢN (Mục 3.6.22 - Bảng 215, Bảng 216) ====================
  async function renderTaiKhoan(tuKhoa = '') {
    const kq = await goiApi('tai-khoan' + (tuKhoa.trim() ? '?tuKhoa=' + encodeURIComponent(tuKhoa.trim()) : ''));
    const ds = kq.duLieu || [];
    if (!ds.some(t => Number(t.maTaiKhoan) === trangThai.maTaiKhoanDangChon)) {
      trangThai.maTaiKhoanDangChon = ds[0] ? Number(ds[0].maTaiKhoan) : 0;
    }

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu" style="flex-wrap:wrap; gap:8px;">
          <strong>Danh sách tài khoản (${ds.length})</strong>
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <input id="txtTuKhoa" value="${maHoaHtml(tuKhoa)}" placeholder="Tìm theo tên, email, SĐT..." style="padding:7px 10px; border:1px solid #cbd5e1; border-radius:8px;" />
            <button type="button" class="nut nut-phu" id="btnTimKiem">Tìm kiếm</button>
            <button type="button" class="nut nut-chinh" id="btnTaoNhanVien">+ Tạo tài khoản nhân viên</button>
            <button type="button" class="nut nut-nguy-hiem" id="btnKhoa" ${ds.length === 0 ? 'disabled' : ''}>Khóa</button>
            <button type="button" class="nut nut-thanh-cong" id="btnMoKhoa" ${ds.length === 0 ? 'disabled' : ''}>Mở khóa</button>
          </div>
        </div>
        <table class="bang-du-lieu" id="grdQltk">
          <thead><tr><th>Mã</th><th>Định danh</th><th>Họ tên & SĐT</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(tk => `
              <tr data-dong-tk="${tk.maTaiKhoan}" class="${Number(tk.maTaiKhoan) === trangThai.maTaiKhoanDangChon ? 'dong-dang-chon' : ''}">
                <td>#${tk.maTaiKhoan}</td>
                <td>${maHoaHtml(tk.dinhDanh)}</td>
                <td>
                  <strong>${maHoaHtml(tk.hoTen)}</strong>
                  ${tk.soDienThoai ? `<div class="chu-phu">${maHoaHtml(tk.soDienThoai)}</div>` : ''}
                  ${tk.lyDoKhoa ? `<div class="chu-phu" style="color:#dc2626;">Lý do khóa: ${maHoaHtml(tk.lyDoKhoa)}</div>` : ''}
                </td>
                <td><span class="nhan-trang-thai tt-lam">${tk.vaiTro}</span></td>
                <td><span class="nhan-trang-thai ${lopTrangThai(tk.trangThai)}">${tk.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  ${['Nhân viên Kho', 'Nhân viên Bán hàng / CSKH'].includes(tk.vaiTro) ? `<button type="button" class="nut nut-phu nut-nho" data-sua-nv="${tk.maTaiKhoan}">Sửa nhân viên</button>` : ''}
                  <button type="button" class="nut ${tk.trangThai === 'Hoạt động' ? 'nut-nguy-hiem' : 'nut-thanh-cong'} nut-nho" data-khoa-tk="${tk.maTaiKhoan}" data-hd="${tk.trangThai === 'Hoạt động' ? '1' : '0'}">${tk.trangThai === 'Hoạt động' ? 'Khóa' : 'Mở khóa'}</button>
                </td>
              </tr>
            `).join('') || '<tr><td colspan="6" class="rong">Không tìm thấy tài khoản phù hợp</td></tr>'}
          </tbody>
        </table>
      </div>
    `;

    document.querySelectorAll('[data-dong-tk]').forEach(tr => {
      tr.onclick = (e) => {
        if (e.target.closest('button')) return;
        trangThai.maTaiKhoanDangChon = Number(tr.dataset.dongTk);
        document.querySelectorAll('[data-dong-tk]').forEach(r => r.classList.toggle('dong-dang-chon', Number(r.dataset.dongTk) === trangThai.maTaiKhoanDangChon));
      };
    });

    document.getElementById('btnTimKiem').onclick = () => renderTaiKhoan(document.getElementById('txtTuKhoa').value);
    document.getElementById('txtTuKhoa').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        renderTaiKhoan(document.getElementById('txtTuKhoa').value);
      }
    });

    function moModalKhoaTk(maTk) {
      if (!maTk) return;
      moModal(`Khóa tài khoản #${maTk}`, `
        <form id="form-khoa-tk" novalidate>
          <div class="nhom-nhap">
            <label for="txtLyDoKhoa">Lý do khóa tài khoản *</label>
            <textarea id="txtLyDoKhoa" rows="3" placeholder="Nhập lý do khóa tài khoản...">Vi phạm quy định hoạt động của hệ thống</textarea>
          </div>
          <button type="submit" id="btnXacNhanKhoa" class="nut nut-nguy-hiem nut-rong">Xác nhận khóa tài khoản</button>
        </form>
      `);
      document.getElementById('form-khoa-tk').onsubmit = async (e) => {
        e.preventDefault();
        const lyDoKhoa = document.getElementById('txtLyDoKhoa').value.trim();
        const r = await goiApi('tai-khoan/khoa', {
          method: 'PATCH',
          body: JSON.stringify({ maTaiKhoan: maTk, khoa: true, lyDoKhoa }),
        });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) { dongModal(); renderTaiKhoan(tuKhoa); }
      };
    }

    async function xuLyMoKhoaTk(maTk) {
      if (!maTk) return;
      const r = await goiApi('tai-khoan/mo-khoa', {
        method: 'PATCH',
        body: JSON.stringify({ maTaiKhoan: maTk }),
      });
      hienThongBaoToast(r.thongBao, r.thanhCong);
      if (r.thanhCong) renderTaiKhoan(tuKhoa);
    }

    document.getElementById('btnKhoa').onclick = () => moModalKhoaTk(trangThai.maTaiKhoanDangChon);
    document.getElementById('btnMoKhoa').onclick = () => xuLyMoKhoaTk(trangThai.maTaiKhoanDangChon);

    document.querySelectorAll('[data-sua-nv]').forEach(b => {
      b.onclick = async () => {
        const kqNv = await goiApi('tai-khoan/nhan-vien?maTaiKhoan=' + b.dataset.suaNv);
        if (!kqNv.thanhCong || !kqNv.duLieu) {
          hienThongBaoToast(kqNv.thongBao || 'Không thể tải thông tin nhân viên', false);
          return;
        }
        const tkObj = kqNv.duLieu;
        moModal(`Cập nhật thông tin nhân viên #${tkObj.maTaiKhoan}`, `
          <form id="form-vt" novalidate>
            <div class="nhom-nhap"><label for="txtHoTen">Họ và tên nhân viên *</label><input id="txtHoTen" value="${maHoaHtml(tkObj.hoTen || '')}" /></div>
            <div class="nhom-nhap"><label for="txtSoDienThoai">Số điện thoại</label><input id="txtSoDienThoai" value="${maHoaHtml(tkObj.soDienThoai || '')}" /></div>
            <div class="nhom-nhap"><label for="cboVaiTro">Vai trò nhân viên *</label>
              <select id="cboVaiTro">
                <option value="Nhân viên Kho" ${tkObj.vaiTro === 'Nhân viên Kho' ? 'selected' : ''}>Nhân viên Kho</option>
                <option value="Nhân viên Bán hàng / CSKH" ${tkObj.vaiTro === 'Nhân viên Bán hàng / CSKH' ? 'selected' : ''}>Nhân viên Bán hàng / CSKH</option>
              </select>
            </div>
            <p class="chu-phu">Khi đổi vai trò, toàn bộ phiên đăng nhập hiện tại của nhân viên sẽ tự động được thu hồi để áp dụng quyền mới.</p>
            <button type="submit" id="btnCapNhatNhanVien" class="nut nut-chinh nut-rong">Cập nhật nhân viên</button>
          </form>
        `);
        document.getElementById('form-vt').onsubmit = async (e) => {
          e.preventDefault();
          const payload = {
            maTaiKhoan: tkObj.maTaiKhoan,
            hoTen: document.getElementById('txtHoTen').value.trim(),
            soDienThoai: document.getElementById('txtSoDienThoai').value.trim(),
            vaiTro: document.getElementById('cboVaiTro').value,
            xacNhanDoiVaiTro: false,
          };
          let r = await goiApi('tai-khoan/nhan-vien', { method: 'PUT', body: JSON.stringify(payload) });
          if (!r.thanhCong && r.duLieu && r.duLieu.canhBaoMatQuyen) {
            if (!confirm(r.thongBao)) return;
            payload.xacNhanDoiVaiTro = true;
            r = await goiApi('tai-khoan/nhan-vien', { method: 'PUT', body: JSON.stringify(payload) });
          }
          hienThongBaoToast(r.thongBao, r.thanhCong);
          if (r.thanhCong) { dongModal(); renderTaiKhoan(tuKhoa); }
        };
      };
    });

    document.getElementById('btnTaoNhanVien').onclick = () => {
      moModal('Tạo tài khoản nhân viên mới', `
        <form id="form-nv" novalidate>
          <div class="nhom-nhap"><label for="txtEmail">Định danh đăng nhập (Email) *</label><input id="txtEmail" placeholder="VD: nhanvienmoi@phonestore.vn" /></div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label for="txtHoTen">Họ tên nhân viên *</label><input id="txtHoTen" placeholder="Nhập họ tên..." /></div>
            <div class="nhom-nhap"><label for="txtSoDienThoai">Số điện thoại</label><input id="txtSoDienThoai" placeholder="09xxxxxxxx" /></div>
          </div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label for="cboVaiTro">Vai trò nội bộ *</label><select id="cboVaiTro"><option value="Nhân viên Kho">Nhân viên Kho</option><option value="Nhân viên Bán hàng / CSKH">Nhân viên Bán hàng / CSKH</option></select></div>
            <div class="nhom-nhap"><label for="pwdMatKhauTam">Mật khẩu tạm thời *</label><input id="pwdMatKhauTam" type="password" minlength="6" placeholder="Tối thiểu 6 ký tự" /></div>
          </div>
          <button type="submit" id="btnLuuNhanVien" class="nut nut-chinh nut-rong">Tạo tài khoản nhân viên</button>
        </form>
      `);
      document.getElementById('form-nv').onsubmit = async (e) => {
        e.preventDefault();
        const r = await goiApi('tai-khoan', {
          method: 'POST',
          body: JSON.stringify({
            email: document.getElementById('txtEmail').value.trim(),
            hoTen: document.getElementById('txtHoTen').value.trim(),
            soDienThoai: document.getElementById('txtSoDienThoai').value.trim(),
            vaiTro: document.getElementById('cboVaiTro').value,
            matKhauTam: document.getElementById('pwdMatKhauTam').value,
          }),
        });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) { dongModal(); renderTaiKhoan(tuKhoa); }
      };
    };

    document.querySelectorAll('[data-khoa-tk]').forEach(b => {
      b.onclick = () => {
        const maTk = Number(b.dataset.khoaTk);
        if (b.dataset.hd === '1') {
          moModalKhoaTk(maTk);
        } else {
          xuLyMoKhoaTk(maTk);
        }
      };
    });
  }

  // ==================== 11. QUẢN LÝ ĐƠN HÀNG ====================
  async function renderDonHang() {
    const kq = await goiApi('don-hang');
    const ds = kq.duLieu || [];

    function nutChuyenTiep(dh) {
      if (dh.trangThai === 'Chờ duyệt') return `<button type="button" class="nut nut-thanh-cong nut-nho" data-dh="${dh.maDonHang}" data-tt="Đã duyệt">Duyệt đơn hàng</button>`;
      if (dh.trangThai === 'Đã duyệt') return `<button type="button" class="nut nut-chinh nut-nho" data-dh="${dh.maDonHang}" data-tt="Đang đóng gói">Chuyển Đang đóng gói</button>`;
      if (dh.trangThai === 'Đang đóng gói') return `<span class="chu-phu">Chờ Kho xác nhận xuất hàng</span>`;
      if (dh.trangThai === 'Đã giao shipper') return `<button type="button" class="nut nut-chinh nut-nho" data-dh="${dh.maDonHang}" data-tt="Đang giao">Bắt đầu giao hàng</button>`;
      if (dh.trangThai === 'Đang giao') return `<button type="button" class="nut nut-thanh-cong nut-nho" data-dh="${dh.maDonHang}" data-tt="Đã giao thành công">Giao thành công</button>`;
      return '';
    }

    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Quản lý đơn hàng & Cập nhật tiến độ giao hàng</strong></div>
        <table class="bang-du-lieu" id="grdDonHang">
          <thead><tr><th>Mã ĐH</th><th>Người nhận</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Thanh toán</th><th>Phiếu xuất</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(dh => `
              <tr>
                <td><strong>#DH${dh.maDonHang}</strong></td>
                <td>
                  <strong>${maHoaHtml(dh.tenNguoiNhan || dh.nguoiNhan || dh.tenKhachHang)}</strong>
                  <div class="chu-phu">${maHoaHtml(dh.sdtNguoiNhan || dh.soDienThoai || '')} · ${maHoaHtml(dh.diaChiNhan || dh.diaChiGiaoHang || '')}</div>
                </td>
                <td>${dh.ngayDat}</td>
                <td><strong>${dinhDangTien(dh.tongTien)}</strong></td>
                <td><span class="nhan-trang-thai ${lopTrangThai(dh.trangThaiThanhToan)}">${dh.trangThaiThanhToan}</span></td>
                <td>${dh.maPhieuXuat ? `#PX${dh.maPhieuXuat} (${dh.trangThaiPhieuXuat})` : 'Chưa có'}</td>
                <td><span class="nhan-trang-thai ${lopTrangThai(dh.trangThai)}">${dh.trangThai}</span></td>
                <td>${nutChuyenTiep(dh)}</td>
              </tr>
            `).join('') || '<tr><td colspan="8" class="rong">Chưa có đơn hàng</td></tr>'}
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

  // ==================== 12. YÊU CẦU HỦY ĐƠN & HOÀN TIỀN ====================
  async function renderHuyDon() {
    const kq = await goiApi('huy-don');
    const ds = kq.duLieu || [];
    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu"><strong>Tiếp nhận yêu cầu hủy đơn & Xử lý hoàn tiền</strong></div>
        <table class="bang-du-lieu" id="grdYeuCauHuy">
          <thead><tr><th>Mã YC</th><th>Đơn hàng</th><th>Khách hàng</th><th>Lý do hủy</th><th>TT Đơn / TT Thanh toán</th><th>Trạng thái YC</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(yc => `
              <tr>
                <td>#YC${yc.maYeuCau}</td>
                <td>#DH${yc.maDonHang}<div class="chu-phu">${dinhDangTien(yc.soTienThanhToan || yc.tongTien)}</div></td>
                <td>${maHoaHtml(yc.tenKhachHang)}</td>
                <td>${maHoaHtml(yc.lyDo)}${yc.lyDoTuChoi ? `<div class="chu-phu" style="color:#dc2626;">Từ chối: ${maHoaHtml(yc.lyDoTuChoi)}</div>` : ''}</td>
                <td>${yc.trangThaiDonHang} / <strong>${yc.trangThaiThanhToan}</strong></td>
                <td><span class="nhan-trang-thai ${lopTrangThai(yc.trangThai)}">${yc.trangThai}</span></td>
                <td class="nhom-hanh-dong">
                  ${yc.trangThai === 'Chờ xử lý' ? `
                    <button type="button" class="nut nut-thanh-cong nut-nho" data-xl-yc="${yc.maYeuCau}" data-cn="1">Xác nhận hủy</button>
                    <button type="button" class="nut nut-nguy-hiem nut-nho" data-xl-yc="${yc.maYeuCau}" data-cn="0">Từ chối</button>
                  ` : ''}
                  ${['Cần hoàn tiền', 'Hoàn tiền lỗi'].includes(yc.trangThaiThanhToan) ? `<button type="button" class="nut nut-chinh nut-nho" data-hoan-tien="${yc.maDonHang}">Hoàn tiền</button>` : ''}
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
        if (!chapNhan && lyDoTuChoi === null) return;
        const url = chapNhan ? 'huy-don/xac-nhan' : 'huy-don/tu-choi';
        const r = await goiApi(url, { method: 'POST', body: JSON.stringify({ maYeuCau: Number(b.dataset.xlYc), lyDo: lyDoTuChoi }) });
        hienThongBaoToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) renderHuyDon();
      };
    });

    document.querySelectorAll('[data-hoan-tien]').forEach(b => {
      b.onclick = () => {
        const yc = ds.find(x => x.maDonHang == b.dataset.hoanTien);
        const toiDa = yc ? (yc.soTienThanhToan || yc.tongTien) : 0;
        moModal(`Xử lý hoàn tiền — Đơn hàng #DH${b.dataset.hoanTien}`, `
          <form id="form-hoan-tien">
            <div class="nhom-nhap">
              <label for="numSoTienHoan">Số tiền khách đã thanh toán: <strong>${dinhDangTien(toiDa)}</strong></label>
              <input id="numSoTienHoan" type="number" min="1" value="${Math.round(toiDa)}" required />
            </div>
            <div class="nhom-nhap">
              <label for="txtLyDoHoanTien">Ghi chú / Lý do hoàn tiền</label>
              <input id="txtLyDoHoanTien" value="Hoàn tiền đơn hàng đã hủy #DH${b.dataset.hoanTien}" required />
            </div>
            <div class="nhom-nhap">
              <label for="ht-kq">Kết quả giao dịch hoàn tiền</label>
              <select id="ht-kq">
                <option value="1">Hoàn tiền thành công</option>
                <option value="0">Mô phỏng lỗi cổng thanh toán</option>
              </select>
            </div>
            <button type="submit" id="btnHoanTien" class="nut nut-chinh nut-rong">Thực hiện hoàn tiền</button>
          </form>
        `);
        document.getElementById('form-hoan-tien').onsubmit = async (e) => {
          e.preventDefault();
          const r = await goiApi('huy-don/hoan-tien', {
            method: 'POST',
            body: JSON.stringify({
              maDonHang: Number(b.dataset.hoanTien),
              soTienHoan: Number(document.getElementById('numSoTienHoan').value),
              lyDo: document.getElementById('txtLyDoHoanTien').value.trim(),
              hoanThanhCong: document.getElementById('ht-kq').value === '1',
            }),
          });
          hienThongBaoToast(r.thongBao, r.thanhCong);
          if (r.thanhCong || r.duLieu) {
            dongModal();
            renderHuyDon();
          }
        };
      };
    });
  }

  // ==================== 13. TRA CỨU KHÁCH HÀNG ====================
  async function renderKhachHang(tuKhoa = '') {
    const kq = await goiApi('khach-hang' + (tuKhoa ? '?tuKhoa=' + encodeURIComponent(tuKhoa) : ''));
    const ds = kq.duLieu || [];
    document.getElementById('khung-man-hinh').innerHTML = `
      <div class="khung-bang">
        <div class="thanh-cong-cu">
          <strong>Tra cứu thông tin & Lịch sử mua hàng của khách hàng</strong>
          <div style="display:flex; gap:8px;">
            <input id="tim-kh" placeholder="Tìm theo tên, SĐT, email..." value="${maHoaHtml(tuKhoa)}" style="padding:7px 10px; border:1px solid #cbd5e1; border-radius:8px;" />
            <button type="button" class="nut nut-chinh" id="btn-tim-kh">Tìm kiếm</button>
          </div>
        </div>
        <table class="bang-du-lieu" id="grdKhachHang">
          <thead><tr><th>Mã KH</th><th>Họ tên</th><th>SĐT</th><th>Email</th><th>Số đơn</th><th>Tổng chi tiêu</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map(kh => `
              <tr>
                <td>#KH${kh.maKhachHang}</td><td><strong>${maHoaHtml(kh.hoTen)}</strong></td><td>${maHoaHtml(kh.soDienThoai || '')}</td><td>${maHoaHtml(kh.email || '')}</td>
                <td>${kh.tongSoDon}</td><td>${dinhDangTien(kh.tongChiTieu)}</td>
                <td><button type="button" class="nut nut-phu nut-nho" data-ls-kh="${kh.maKhachHang}" data-ten="${maHoaHtml(kh.hoTen)}">Xem lịch sử mua</button></td>
              </tr>
            `).join('') || '<tr><td colspan="7" class="rong">Không tìm thấy khách hàng phù hợp</td></tr>'}
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
        const oDinhDanh = document.getElementById('txtDinhDanh');
        const oMatKhau = document.getElementById('pwdMatKhau');
        oDinhDanh.value = btn.dataset.tk;
        oMatKhau.focus();
      };
    });

    document.getElementById('form-dang-nhap').onsubmit = async (e) => {
      e.preventDefault();
      const loiEl = document.getElementById('loi-dang-nhap');
      loiEl.classList.add('an');
      const kq = await goiApi('xac-thuc/dang-nhap', {
        method: 'POST',
        body: JSON.stringify({
          dinhDanh: document.getElementById('txtDinhDanh').value.trim(),
          matKhau: document.getElementById('pwdMatKhau').value,
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

    document.getElementById('btnGuiMaXacThuc').onclick = async () => {
      const kq = await goiApi('xac-thuc/quen-mat-khau', {
        method: 'POST',
        body: JSON.stringify({ dinhDanh: document.getElementById('qmk-dinh-danh').value.trim() }),
      });
      if (kq.thanhCong) {
        document.getElementById('qmk-buoc-2').classList.remove('an');
        if (kq.duLieu && kq.duLieu.maXacThucMoPhong) {
          document.getElementById('qmk-thong-bao-ma').textContent = `${kq.thongBao} — Mã xác thực mô phỏng: ${kq.duLieu.maXacThucMoPhong} (Hết hạn: ${kq.duLieu.hanMaXacThuc})`;
        } else {
          document.getElementById('qmk-thong-bao-ma').textContent = kq.thongBao;
        }
      } else {
        alert(kq.thongBao);
      }
    };

    document.getElementById('btnDatLaiMatKhau').onclick = async () => {
      const matKhauMoi = document.getElementById('pwdMatKhauMoi').value;
      const xacNhanEl = document.getElementById('pwdXacNhan');
      const xacNhanMatKhau = xacNhanEl ? xacNhanEl.value : matKhauMoi;
      if (matKhauMoi !== xacNhanMatKhau) {
        alert('Mật khẩu xác nhận không khớp');
        return;
      }
      const kq = await goiApi('xac-thuc/dat-lai-mat-khau', {
        method: 'POST',
        body: JSON.stringify({
          dinhDanh: document.getElementById('qmk-dinh-danh').value.trim(),
          maXacThuc: document.getElementById('txtMaXacThuc').value.trim(),
          matKhauMoi,
          xacNhanMatKhau,
        }),
      });
      alert(kq.thongBao);
      if (kq.thanhCong) document.getElementById('modal-quen-mk').classList.add('an');
    };

    khoiTaoPhien();
  });
})();
