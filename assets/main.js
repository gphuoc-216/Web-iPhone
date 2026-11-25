// ============================================================================
// assets/main.js
// Giao diện Khách hàng Phone Store (BCE — UC7-9, UC22-32, UC39-46)
// ============================================================================

(function () {
  const KHOA_PHIEN_KH = 'ps_ma_phien_khach';
  const KHOA_PHIEN_TAM = 'ps_ma_phien_tam';
  const KHOA_PHIEN_NOI_BO = 'ps_ma_phien_noi_bo';
  const CAC_MOC_GIAO_HANG = ['Chờ duyệt', 'Đã duyệt', 'Đang đóng gói', 'Đã giao shipper', 'Đang giao', 'Đã giao thành công'];

  function taoMaPhienTam() {
    let ma = localStorage.getItem(KHOA_PHIEN_TAM);
    if (!ma) {
      ma = 'TAM_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
      localStorage.setItem(KHOA_PHIEN_TAM, ma);
    }
    return ma;
  }

  const trangThai = {
    maPhien: localStorage.getItem(KHOA_PHIEN_KH) || '',
    maPhienTam: taoMaPhienTam(),
    taiKhoan: null,
    gioHang: null,
    danhMucHienTai: '',
    quayLaiDatHangSauDangNhap: false,
    dangNhapDoForm: false,
  };

  function dinhDangTien(so) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(Number(so) || 0)) + ' đ';
  }

  function hienToast(noiDung, thanhCong = true) {
    const el = document.getElementById('thanh-thong-bao-web');
    el.textContent = noiDung;
    el.className = 'toast-web ' + (thanhCong ? 'toast-ok' : 'toast-loi');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.add('an'), 4500);
  }

  async function goiApi(duongDan, tuyChon = {}) {
    const headers = Object.assign({}, tuyChon.headers || {});
    headers['X-Ma-Phien'] = trangThai.maPhien || trangThai.maPhienTam;
    if (tuyChon.body && !(tuyChon.body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }
    const res = await fetch('/api/' + duongDan, Object.assign({}, tuyChon, { headers }));
    const json = await res.json().catch(() => ({ thanhCong: false, thongBao: 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau' }));
    if (res.status === 401 && trangThai.maPhien && !duongDan.startsWith('xac-thuc/')) {
      trangThai.maPhien = '';
      trangThai.taiKhoan = null;
      localStorage.removeItem(KHOA_PHIEN_KH);
      renderVungTaiKhoan();
    }
    return json;
  }

  function moModal(tieuDe, html) {
    trangThai.dangNhapDoForm = false;
    document.getElementById('tieu-de-modal-web').textContent = tieuDe;
    document.getElementById('than-modal-web').innerHTML = html;
    document.getElementById('modal-web').classList.remove('an');
    document.getElementById('than-modal-web').querySelectorAll('input, textarea, select').forEach(inp => {
      inp.addEventListener('input', () => { trangThai.dangNhapDoForm = true; });
    });
  }

  function dongModal() {
    trangThai.dangNhapDoForm = false;
    document.getElementById('modal-web').classList.add('an');
  }

  // ==================== XÁC THỰC CHUNG (UC39 - UC42) ====================
  async function khoiTaoPhien() {
    if (trangThai.maPhien) {
      const kq = await goiApi('xac-thuc/kiem-tra-phien');
      if (kq.thanhCong && kq.duLieu) {
        trangThai.taiKhoan = kq.duLieu;
      } else {
        trangThai.maPhien = '';
        localStorage.removeItem(KHOA_PHIEN_KH);
      }
    }
    renderVungTaiKhoan();
    await taiGioHang();
  }

  function renderVungTaiKhoan() {
    const vung = document.getElementById('vung-tai-khoan');
    if (!trangThai.taiKhoan) {
      vung.innerHTML = `<button type="button" id="nut-mo-dang-nhap" class="nut nut-chinh">Đăng nhập / Đăng ký</button>`;
      document.getElementById('nut-mo-dang-nhap').onclick = () => moModalXacThuc('dang-nhap');
      return;
    }

    const laNoiBo = trangThai.taiKhoan.vaiTro !== 'Khách hàng';
    vung.innerHTML = `
      <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <button type="button" id="nut-mo-tai-khoan" class="nut-header">👤 ${trangThai.taiKhoan.hoTen || trangThai.taiKhoan.dinhDanh}</button>
        ${laNoiBo ? `<a href="/admin" class="nut nut-chinh" style="text-decoration:none;">Vào Admin</a>` : ''}
        <button type="button" id="nut-dang-xuat-web" class="nut nut-phu">Đăng xuất</button>
      </div>
    `;
    document.getElementById('nut-mo-tai-khoan').onclick = () => {
      if (laNoiBo) {
        window.location.href = '/admin';
      } else {
        moModalHoSo('don-hang');
      }
    };
    document.getElementById('nut-dang-xuat-web').onclick = async () => {
      // UC41: Cảnh báo nếu đang nhập dở form
      if (trangThai.dangNhapDoForm && !confirm('Bạn đang có dữ liệu chưa lưu trong biểu mẫu. Bạn có chắc muốn đăng xuất?')) {
        return;
      }
      await goiApi('xac-thuc/dang-xuat', { method: 'POST' });
      trangThai.maPhien = '';
      trangThai.taiKhoan = null;
      localStorage.removeItem(KHOA_PHIEN_KH);
      renderVungTaiKhoan();
      await taiGioHang();
      hienToast('Đã đăng xuất (giỏ hàng tài khoản của bạn vẫn được giữ nguyên trong hệ thống)');
    };
  }

  function moModalXacThuc(tab = 'dang-nhap') {
    moModal('Tài khoản Phone Store', `
      <div class="tab-bar">
        <button type="button" class="nut ${tab === 'dang-nhap' ? 'nut-chinh' : 'nut-phu'}" id="tab-dn">Đăng nhập (UC40)</button>
        <button type="button" class="nut ${tab === 'dang-ky' ? 'nut-chinh' : 'nut-phu'}" id="tab-dk">Đăng ký (UC39)</button>
        <button type="button" class="nut ${tab === 'quen-mk' ? 'nut-chinh' : 'nut-phu'}" id="tab-qmk">Quên mật khẩu (UC42)</button>
      </div>
      <div id="noi-dung-xac-thuc"></div>
    `);

    document.getElementById('tab-dn').onclick = () => moModalXacThuc('dang-nhap');
    document.getElementById('tab-dk').onclick = () => moModalXacThuc('dang-ky');
    document.getElementById('tab-qmk').onclick = () => moModalXacThuc('quen-mk');

    const vung = document.getElementById('noi-dung-xac-thuc');
    if (tab === 'dang-nhap') {
      vung.innerHTML = `
        <form id="f-dn">
          <div class="nhom-nhap"><label>Email hoặc Số điện thoại</label><input id="dn-dd" required placeholder="VD: nguyenvana@gmail.com" /></div>
          <div class="nhom-nhap"><label>Mật khẩu</label><input id="dn-mk" type="password" required /></div>
          <button type="submit" class="nut nut-chinh nut-rong">Đăng nhập</button>
          <p class="chu-phu" style="margin-top:10px;">Tài khoản test: <code>nguyenvana@gmail.com</code> (Khách hàng) hoặc <code>admin@phonestore.vn</code> (Ban Quản lý) — Mật khẩu: <code>123456</code></p>
        </form>
      `;
      document.getElementById('f-dn').onsubmit = async (e) => {
        e.preventDefault();
        const kq = await goiApi('xac-thuc/dang-nhap', {
          method: 'POST',
          body: JSON.stringify({
            dinhDanh: document.getElementById('dn-dd').value.trim(),
            matKhau: document.getElementById('dn-mk').value,
            maPhienTam: trangThai.maPhienTam,
          }),
        });
        if (!kq.thanhCong) {
          alert(kq.thongBao);
          return;
        }
        // Điều hướng đúng giao diện theo vaiTro (UC40)
        if (kq.duLieu.taiKhoan.vaiTro !== 'Khách hàng') {
          localStorage.setItem(KHOA_PHIEN_NOI_BO, kq.duLieu.maPhien);
          window.location.href = '/admin';
          return;
        }
        trangThai.maPhien = kq.duLieu.maPhien;
        trangThai.taiKhoan = kq.duLieu.taiKhoan;
        localStorage.setItem(KHOA_PHIEN_KH, trangThai.maPhien);
        renderVungTaiKhoan();
        await taiGioHang();
        dongModal();
        hienToast('Đăng nhập thành công! Giỏ hàng tạm (nếu có) đã được tự động gộp.');
        if (trangThai.quayLaiDatHangSauDangNhap) {
          trangThai.quayLaiDatHangSauDangNhap = false;
          moModalDatHang();
        }
      };
    } else if (tab === 'dang-ky') {
      vung.innerHTML = `
        <form id="f-dk">
          <div class="nhom-nhap"><label>Họ và tên</label><input id="dk-ten" required placeholder="Nguyễn Văn A" /></div>
          <div class="nhom-nhap"><label>Email hoặc Số điện thoại (định danh)</label><input id="dk-dd" required placeholder="Email hoặc SĐT" /></div>
          <div class="nhom-nhap"><label>Mật khẩu (tối thiểu 6 ký tự)</label><input id="dk-mk" type="password" minlength="6" required /></div>
          <button type="submit" class="nut nut-chinh nut-rong">Đăng ký tài khoản</button>
        </form>
      `;
      document.getElementById('f-dk').onsubmit = async (e) => {
        e.preventDefault();
        const kq = await goiApi('xac-thuc/dang-ky', {
          method: 'POST',
          body: JSON.stringify({
            hoTen: document.getElementById('dk-ten').value.trim(),
            dinhDanh: document.getElementById('dk-dd').value.trim(),
            matKhau: document.getElementById('dk-mk').value,
            maPhienTam: trangThai.maPhienTam,
          }),
        });
        if (!kq.thanhCong) {
          alert(kq.thongBao);
          return;
        }
        trangThai.maPhien = kq.duLieu.maPhien;
        trangThai.taiKhoan = kq.duLieu.taiKhoan;
        localStorage.setItem(KHOA_PHIEN_KH, trangThai.maPhien);
        renderVungTaiKhoan();
        await taiGioHang();
        dongModal();
        hienToast('Đăng ký thành công! Giỏ hàng tạm đã được gộp vào tài khoản của bạn.');
        if (trangThai.quayLaiDatHangSauDangNhap) {
          trangThai.quayLaiDatHangSauDangNhap = false;
          moModalDatHang();
        }
      };
    } else {
      vung.innerHTML = `
        <div class="nhom-nhap"><label>Email hoặc SĐT đã đăng ký</label><input id="q-dd" placeholder="Nhập định danh tài khoản" /></div>
        <button type="button" id="q-gui" class="nut nut-phu">1. Gửi mã xác thực</button>
        <div id="q-kq" class="chu-phu" style="margin:10px 0;"></div>
        <div class="nhom-nhap"><label>Mã xác thực (6 số)</label><input id="q-ma" /></div>
        <div class="nhom-nhap"><label>Mật khẩu mới</label><input id="q-mk" type="password" minlength="6" /></div>
        <button type="button" id="q-datlai" class="nut nut-chinh nut-rong">2. Đặt lại mật khẩu</button>
      `;
      document.getElementById('q-gui').onclick = async () => {
        const kq = await goiApi('xac-thuc/quen-mat-khau', { method: 'POST', body: JSON.stringify({ dinhDanh: document.getElementById('q-dd').value.trim() }) });
        document.getElementById('q-kq').textContent = kq.thongBao + (kq.duLieu && kq.duLieu.maXacThucMoPhong ? ` [Mã test: ${kq.duLieu.maXacThucMoPhong}]` : '');
      };
      document.getElementById('q-datlai').onclick = async () => {
        const kq = await goiApi('xac-thuc/dat-lai-mat-khau', {
          method: 'POST',
          body: JSON.stringify({
            dinhDanh: document.getElementById('q-dd').value.trim(),
            maXacThuc: document.getElementById('q-ma').value.trim(),
            matKhauMoi: document.getElementById('q-mk').value,
          }),
        });
        alert(kq.thongBao);
        if (kq.thanhCong) moModalXacThuc('dang-nhap');
      };
    }
  }

  // ==================== DANH MỤC & TÌM KIẾM / LỌC (UC7 - UC9) ====================
  async function taiDanhMuc() {
    const kq = await goiApi('danh-muc');
    const ds = kq.duLieu || [];
    const thanh = document.getElementById('thanh-danh-muc');
    thanh.innerHTML = `<button type="button" class="vien-danh-muc dang-chon" data-dm="">Tất cả</button>` +
      ds.map(dm => `<button type="button" class="vien-danh-muc" data-dm="${dm.maDanhMuc}">${dm.ten}</button>`).join('');
    thanh.querySelectorAll('.vien-danh-muc').forEach(btn => {
      btn.onclick = () => {
        thanh.querySelectorAll('.vien-danh-muc').forEach(b => b.classList.remove('dang-chon'));
        btn.classList.add('dang-chon');
        trangThai.danhMucHienTai = btn.dataset.dm;
        taiDanhSachSanPham();
      };
    });
  }

  async function taiDanhSachSanPham() {
    const params = new URLSearchParams();
    const tuKhoa = document.getElementById('nhap-tu-khoa').value.trim();
    if (tuKhoa) params.set('tuKhoa', tuKhoa);
    if (trangThai.danhMucHienTai) params.set('maDanhMuc', trangThai.danhMucHienTai);
    const hang = document.getElementById('loc-hang').value;
    if (hang) params.set('hang', hang);
    const giaTu = document.getElementById('loc-gia-tu').value;
    if (giaTu) params.set('giaTu', giaTu);
    const giaDen = document.getElementById('loc-gia-den').value;
    if (giaDen) params.set('giaDen', giaDen);
    const ram = document.getElementById('loc-ram').value;
    if (ram) params.set('ram', ram);
    const rom = document.getElementById('loc-rom').value;
    if (rom) params.set('rom', rom);
    const pinTu = document.getElementById('loc-pin').value;
    if (pinTu) params.set('pinTu', pinTu);
    params.set('sapXep', document.getElementById('loc-sap-xep').value);

    const kq = await goiApi('tim-kiem?' + params.toString());
    if (!kq.thanhCong) {
      hienToast(kq.thongBao, false);
      return;
    }

    const ds = kq.duLieu || [];
    document.getElementById('tong-so-san-pham').textContent = `Tìm thấy ${ds.length} sản phẩm`;
    const luoi = document.getElementById('luoi-san-pham');

    if (ds.length === 0) {
      luoi.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:#64748b;">Không tìm thấy điện thoại phù hợp với bộ lọc</div>';
      return;
    }

    luoi.innerHTML = ds.map(sp => {
      const conHang = sp.trangThaiTonKho === 'Còn hàng';
      const coKm = sp.mucUuDaiKhuyenMai > 0;
      const ts = sp.thongSoKyThuat || {};
      return `
        <article class="the-san-pham">
          <div class="khung-anh-sp" data-xem-sp="${sp.maDienThoai}">
            ${coKm ? `<span class="nhan-giam-gia">-${sp.mucUuDaiKhuyenMai}%</span>` : ''}
            <span class="nhan-ton-kho ${conHang ? 'con-hang' : 'het-hang'}">${sp.trangThaiTonKho}</span>
            <img src="${sp.hinhAnh}" alt="${sp.ten}" loading="lazy" />
          </div>
          <h3 class="ten-sp" data-xem-sp="${sp.maDienThoai}">${sp.ten}</h3>
          <div class="thong-so-nhanh">${sp.hang} · ${ts.ram || 0}GB/${ts.rom || 0}GB · ${ts.dungLuongPin || 0}mAh</div>
          <div class="gia-sp">
            <span class="gia-cuoi">${dinhDangTien(sp.giaBanCuoi)}</span>
            ${coKm ? `<span class="gia-goc">${dinhDangTien(sp.giaGoc)}</span>` : ''}
          </div>
          <div class="nhom-nut-sp">
            <button type="button" class="nut nut-phu" data-xem-sp="${sp.maDienThoai}">Chi tiết</button>
            <button type="button" class="nut nut-chinh" data-them-gio="${sp.maDienThoai}" ${conHang ? '' : 'disabled'}>+ Giỏ hàng</button>
          </div>
        </article>
      `;
    }).join('');

    luoi.querySelectorAll('[data-xem-sp]').forEach(el => {
      el.onclick = () => moChiTietSanPham(Number(el.dataset.xemSp));
    });
    luoi.querySelectorAll('[data-them-gio]').forEach(btn => {
      btn.onclick = () => themVaoGioHang(Number(btn.dataset.themGio), 1);
    });
  }

  // UC9: Xem chi tiết sản phẩm
  async function moChiTietSanPham(maDienThoai) {
    const kq = await goiApi('tim-kiem/chi-tiet?maDienThoai=' + maDienThoai);
    if (!kq.thanhCong) {
      hienToast(kq.thongBao, false);
      return;
    }
    const sp = kq.duLieu;
    const ts = sp.thongSoKyThuat || {};
    const conHang = sp.trangThaiTonKho === 'Còn hàng';
    const coKm = sp.mucUuDaiKhuyenMai > 0;

    moModal(sp.ten, `
      <div class="luoi-2-cot">
        <div style="text-align:center;">
          <img src="${sp.hinhAnh}" alt="${sp.ten}" style="max-height:260px; max-width:100%; object-fit:contain;" />
          <div style="margin-top:10px;">
            <span class="nhan-ton-kho ${conHang ? 'con-hang' : 'het-hang'}" style="position:static;">${sp.trangThaiTonKho} (Khả dụng: ${sp.tonKho.tonKhaDung})</span>
          </div>
        </div>
        <div>
          <div style="margin-bottom:10px;">
            <span class="gia-cuoi" style="font-size:22px;">${dinhDangTien(sp.giaBanCuoi)}</span>
            ${coKm ? `<span class="gia-goc">${dinhDangTien(sp.giaGoc)}</span> <span style="color:#dc2626; font-weight:700;">(-${sp.mucUuDaiKhuyenMai}%)</span>` : ''}
          </div>
          ${(sp.khuyenMaiApDung || []).map(km => `<div style="background:#fef2f2; color:#991b1b; padding:6px 10px; border-radius:8px; font-size:12.5px; margin-bottom:8px;">🎁 <strong>${km.ten}</strong>: ${km.noiDung || ''}</div>`).join('')}
          <p class="chu-phu">${sp.moTa || ''}</p>
          <div style="display:flex; gap:10px; align-items:center; margin-top:14px;">
            <input id="ct-sl" type="number" min="1" max="${Math.max(1, sp.tonKho.tonKhaDung)}" value="1" style="width:80px; padding:8px; border:1px solid #cbd5e1; border-radius:8px;" />
            <button type="button" id="ct-them-gio" class="nut nut-chinh" ${conHang ? '' : 'disabled'}>Thêm vào giỏ hàng</button>
          </div>
        </div>
      </div>
      <h4 style="margin:18px 0 8px;">Thông số kỹ thuật</h4>
      <table class="bang-thong-so">
        <tr><th>Màn hình</th><td>${ts.manHinh || '-'}</td><th>Chip xử lý</th><td>${ts.chip || '-'}</td></tr>
        <tr><th>RAM</th><td>${ts.ram || 0} GB</td><th>Bộ nhớ (ROM)</th><td>${ts.rom || 0} GB</td></tr>
        <tr><th>Camera</th><td>${ts.camera || '-'}</td><th>Dung lượng pin</th><td>${ts.dungLuongPin || 0} mAh</td></tr>
        <tr><th>Hệ điều hành</th><td colspan="3">${ts.heDieuHanh || '-'}</td></tr>
      </table>
    `);

    const btnThem = document.getElementById('ct-them-gio');
    if (btnThem) {
      btnThem.onclick = async () => {
        const sl = Number(document.getElementById('ct-sl').value) || 1;
        await themVaoGioHang(sp.maDienThoai, sl);
        dongModal();
      };
    }
  }

  // ==================== GIỎ HÀNG & VOUCHER (UC22 - UC24, UC30) ====================
  async function taiGioHang() {
    const kq = await goiApi('gio-hang');
    if (kq.thanhCong && kq.duLieu) {
      trangThai.gioHang = kq.duLieu;
      const tongSl = (kq.duLieu.chiTiet || []).reduce((t, i) => t + Number(i.soLuong), 0);
      document.getElementById('dem-gio-hang').textContent = tongSl;
    }
  }

  async function themVaoGioHang(maDienThoai, soLuong) {
    const kq = await goiApi('gio-hang', {
      method: 'POST',
      body: JSON.stringify({ maDienThoai, soLuong }),
    });
    if (kq.thanhCong) {
      trangThai.gioHang = kq.duLieu;
      const tongSl = (kq.duLieu.chiTiet || []).reduce((t, i) => t + Number(i.soLuong), 0);
      document.getElementById('dem-gio-hang').textContent = tongSl;
      hienToast('Đã thêm sản phẩm vào giỏ hàng');
    } else {
      hienToast(kq.thongBao, false);
    }
  }

  async function moModalGioHang() {
    await taiGioHang();
    const gh = trangThai.gioHang || { chiTiet: [], tongTamTinh: 0, tienGiamGia: 0, tongTien: 0 };
    const ds = gh.chiTiet || [];

    moModal('Giỏ hàng của bạn (UC22 - UC24, UC30)', `
      ${ds.length === 0 ? '<div style="text-align:center; padding:30px; color:#64748b;">Giỏ hàng của bạn đang trống</div>' : `
        <table class="bang-thong-so">
          <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th><th></th></tr></thead>
          <tbody>
            ${ds.map(ct => `
              <tr>
                <td><strong>${ct.tenDienThoai}</strong><div class="chu-phu">Khả dụng: ${ct.tonKhaDung}</div></td>
                <td>${dinhDangTien(ct.donGia)}</td>
                <td>
                  <button class="nut nut-phu nut-nho" data-sl-giam="${ct.maDienThoai}" data-sl="${ct.soLuong - 1}">-</button>
                  <strong style="margin:0 8px;">${ct.soLuong}</strong>
                  <button class="nut nut-phu nut-nho" data-sl-tang="${ct.maDienThoai}" data-sl="${ct.soLuong + 1}">+</button>
                </td>
                <td><strong>${dinhDangTien(ct.donGia * ct.soLuong)}</strong></td>
                <td><button class="nut nut-do nut-nho" data-xoa-gh="${ct.maDienThoai}">Xóa</button></td>
              </tr>
            `).join('')}
          </tbody>
        </table>

        <div style="display:flex; gap:8px; margin:14px 0; align-items:center;">
          <input id="nhap-voucher" value="${gh.maVoucher || ''}" placeholder="Nhập mã giảm giá (VD: GIAM500K, SALE5PT)" style="flex:1; padding:9px; border:1px solid #cbd5e1; border-radius:8px;" />
          <button type="button" id="nut-ap-voucher" class="nut nut-chinh">Áp dụng (UC30)</button>
          ${gh.maVoucher ? `<button type="button" id="nut-bo-voucher" class="nut nut-phu">Bỏ mã</button>` : ''}
        </div>

        <div style="background:#f8fafc; padding:14px; border-radius:10px; margin-bottom:14px;">
          <div style="display:flex; justify-content:space-between; margin-bottom:4px;"><span>Tạm tính (đã gồm khuyến mãi SP):</span><strong>${dinhDangTien(gh.tongTamTinh)}</strong></div>
          <div style="display:flex; justify-content:space-between; margin-bottom:4px; color:#16a34a;"><span>Giảm giá Voucher${gh.maVoucher ? ` (${gh.maVoucher})` : ''}:</span><strong>-${dinhDangTien(gh.tienGiamGia)}</strong></div>
          <div style="display:flex; justify-content:space-between; font-size:17px; border-top:1px solid #e2e8f0; padding-top:8px;"><span>Tổng tiền hàng:</span><strong style="color:#dc2626;">${dinhDangTien(gh.tongTien)}</strong></div>
        </div>

        <button type="button" id="nut-dat-hang-ngay" class="nut nut-xanh nut-rong" style="padding:11px; font-size:15px;">Tiến hành đặt hàng (UC28)</button>
      `}
    `);

    document.querySelectorAll('[data-sl-giam], [data-sl-tang]').forEach(b => {
      b.onclick = async () => {
        const maDt = Number(b.dataset.slGiam || b.dataset.slTang);
        const kq = await goiApi('gio-hang', { method: 'PUT', body: JSON.stringify({ maDienThoai: maDt, soLuong: Number(b.dataset.sl) }) });
        if (!kq.thanhCong) alert(kq.thongBao);
        moModalGioHang();
      };
    });
    document.querySelectorAll('[data-xoa-gh]').forEach(b => {
      b.onclick = async () => {
        await goiApi('gio-hang?maDienThoai=' + b.dataset.xoaGh, { method: 'DELETE' });
        moModalGioHang();
      };
    });
    if (document.getElementById('nut-ap-voucher')) {
      document.getElementById('nut-ap-voucher').onclick = async () => {
        const r = await goiApi('ap-dung-voucher', {
          method: 'POST',
          body: JSON.stringify({ maGioHang: gh.maGioHang, maVoucher: document.getElementById('nhap-voucher').value.trim() }),
        });
        hienToast(r.thongBao, r.thanhCong);
        if (!r.thanhCong) alert(r.thongBao);
        moModalGioHang();
      };
    }
    if (document.getElementById('nut-bo-voucher')) {
      document.getElementById('nut-bo-voucher').onclick = async () => {
        await goiApi('ap-dung-voucher?maGioHang=' + gh.maGioHang, { method: 'DELETE' });
        moModalGioHang();
      };
    }
    if (document.getElementById('nut-dat-hang-ngay')) {
      document.getElementById('nut-dat-hang-ngay').onclick = () => {
        // UC28 Bước 1: Kiểm tra đăng nhập, giữ nguyên giỏ tạm và quay lại sau khi đăng nhập
        if (!trangThai.taiKhoan || trangThai.taiKhoan.vaiTro !== 'Khách hàng') {
          trangThai.quayLaiDatHangSauDangNhap = true;
          moModalXacThuc('dang-nhap');
          hienToast('Vui lòng đăng nhập hoặc đăng ký để đặt hàng (giỏ hàng tạm sẽ được giữ nguyên)', true);
          return;
        }
        moModalDatHang();
      };
    }
  }

  // ==================== ĐẶT HÀNG & THANH TOÁN (UC25 - UC29) ====================
  async function moModalDatHang() {
    await taiGioHang();
    const [kqDc, kqPhi] = await Promise.all([goiApi('ho-so/dia-chi'), goiApi('phi-van-chuyen')]);
    const dsDiaChi = kqDc.duLieu || [];
    const dsQuyTac = kqPhi.duLieu || [];
    const gh = trangThai.gioHang;

    if (!gh || !gh.chiTiet || gh.chiTiet.length === 0) {
      hienToast('Giỏ hàng đang trống', false);
      return;
    }

    moModal('Xác nhận Đặt hàng & Thanh toán (UC25 - UC29)', `
      <form id="form-dat-hang">
        <h4>1. Địa chỉ giao hàng (UC25)</h4>
        ${dsDiaChi.length > 0 ? `
          <div class="nhom-nhap">
            <label>Chọn địa chỉ đã lưu</label>
            <select id="dh-chon-dc">
              ${dsDiaChi.map(dc => `<option value="${dc.maDiaChi}" data-qt="${dc.maQuyTac}">${dc.nguoiNhan} (${dc.soDienThoai}) — ${dc.diaChi} [${dc.khuVuc}]${dc.macDinh ? ' ★ Mặc định' : ''}</option>`).join('')}
              <option value="0">+ Nhập địa chỉ giao hàng mới...</option>
            </select>
          </div>
        ` : '<input type="hidden" id="dh-chon-dc" value="0" />'}

        <div id="khung-dc-moi" class="${dsDiaChi.length > 0 ? 'an' : ''}" style="background:#f8fafc; padding:12px; border-radius:10px; margin-bottom:14px;">
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Người nhận</label><input id="dc-nguoinhan" value="${trangThai.taiKhoan.hoTen || ''}" /></div>
            <div class="nhom-nhap"><label>Số điện thoại</label><input id="dc-sdt" value="${trangThai.taiKhoan.soDienThoai || ''}" /></div>
          </div>
          <div class="nhom-nhap"><label>Địa chỉ chi tiết</label><input id="dc-diachi" placeholder="Số nhà, đường, phường/xã, quận/huyện..." /></div>
          <div class="nhom-nhap">
            <label>Khu vực vận chuyển</label>
            <select id="dc-quytac">
              ${dsQuyTac.map(q => `<option value="${q.maQuyTac}">${q.khuVuc} (Phí: ${dinhDangTien(q.mucPhi)}, miễn phí từ ${dinhDangTien(q.nguongMienPhi)})</option>`).join('')}
            </select>
          </div>
          <label style="display:flex; align-items:center; gap:8px; font-size:13px;">
            <input type="checkbox" id="dc-luu-so" checked style="width:auto;" /> Lưu vào sổ địa chỉ mặc định
          </label>
        </div>

        <h4>2. Phương thức thanh toán (UC29)</h4>
        <div class="nhom-nhap">
          <select id="dh-phuong-thuc">
            <option value="Thanh toán khi nhận hàng">Thanh toán khi nhận hàng (COD)</option>
            <option value="Thanh toán trực tuyến mô phỏng">Thanh toán trực tuyến mô phỏng (Online)</option>
          </select>
        </div>

        <div style="background:#eff6ff; padding:14px; border-radius:10px; margin:14px 0;">
          <div style="display:flex; justify-content:space-between;"><span>Tiền hàng (sau KM & Voucher):</span><strong>${dinhDangTien(gh.tongTien)}</strong></div>
          <div style="display:flex; justify-content:space-between; margin-top:4px;"><span>Phí vận chuyển (UC26):</span><strong id="hien-thi-phi-ship">Đang tính...</strong></div>
          <div style="display:flex; justify-content:space-between; font-size:17px; border-top:1px solid #bfdbfe; padding-top:8px; margin-top:8px;">
            <span>Tổng thanh toán:</span><strong id="hien-thi-tong-cuoi" style="color:#dc2626;">${dinhDangTien(gh.tongTien)}</strong>
          </div>
        </div>

        <button type="submit" class="nut nut-xanh nut-rong" style="padding:11px; font-size:15px;">Xác nhận đặt hàng</button>
      </form>
    `);

    const chonDc = document.getElementById('dh-chon-dc');
    const khungDcMoi = document.getElementById('khung-dc-moi');
    const chonQt = document.getElementById('dc-quytac');

    async function capNhatPhiShip() {
      const maDc = Number(chonDc.value || 0);
      const q = maDc > 0
        ? `dat-hang/phi-van-chuyen?maDiaChi=${maDc}&tongTamTinh=${gh.tongTamTinh}`
        : `dat-hang/phi-van-chuyen?maQuyTac=${chonQt.value}&tongTamTinh=${gh.tongTamTinh}`;
      const kq = await goiApi(q);
      const phi = (kq.duLieu && kq.duLieu.phiVanChuyen) || 0;
      document.getElementById('hien-thi-phi-ship').textContent = phi === 0 ? 'Miễn phí (0 đ)' : dinhDangTien(phi);
      document.getElementById('hien-thi-tong-cuoi').textContent = dinhDangTien(Number(gh.tongTien) + Number(phi));
    }

    chonDc.onchange = () => {
      if (khungDcMoi) khungDcMoi.classList.toggle('an', Number(chonDc.value) > 0);
      capNhatPhiShip();
    };
    if (chonQt) chonQt.onchange = capNhatPhiShip;
    capNhatPhiShip();

    document.getElementById('form-dat-hang').onsubmit = async (e) => {
      e.preventDefault();
      const maDiaChi = Number(chonDc.value || 0);
      const payload = {
        maGioHang: gh.maGioHang,
        maDiaChi,
        phuongThuc: document.getElementById('dh-phuong-thuc').value,
      };
      if (maDiaChi === 0) {
        payload.nguoiNhan = document.getElementById('dc-nguoinhan').value.trim();
        payload.soDienThoai = document.getElementById('dc-sdt').value.trim();
        payload.diaChi = document.getElementById('dc-diachi').value.trim();
        payload.maQuyTac = Number(chonQt.value);
        payload.luuVaoSoDiaChi = document.getElementById('dc-luu-so').checked;
        payload.macDinh = document.getElementById('dc-luu-so').checked;
      }

      const kq = await goiApi('dat-hang', { method: 'POST', body: JSON.stringify(payload) });
      if (!kq.thanhCong) {
        alert(kq.thongBao);
        return;
      }

      if (kq.duLieu.choThanhToan) {
        // UC29: Màn hình Cổng thanh toán trực tuyến mô phỏng (Thành công / Thất bại / Hết hạn giữ chỗ)
        moCongThanhToanMoPhong(kq.duLieu);
      } else {
        await taiGioHang();
        taiDanhSachSanPham();
        moManHinhXacNhanDonHang(kq.duLieu);
      }
    };
  }

  function moCongThanhToanMoPhong(duLieuTt) {
    moModal('Cổng Thanh toán Trực tuyến Mô phỏng (UC29)', `
      <div style="text-align:center; padding:10px 0;">
        <p style="font-size:15px;">Giỏ hàng đã được <strong>khóa</strong> và tồn kho đã <strong>giữ chỗ tạm</strong> cho giao dịch <strong>#TT${duLieuTt.maThanhToan}</strong>.</p>
        <div style="font-size:24px; font-weight:800; color:#dc2626; margin:14px 0;">${dinhDangTien(duLieuTt.tongTien)}</div>
        <p class="chu-phu">Chọn kết quả mô phỏng từ cổng thanh toán:</p>
        <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:16px;">
          <button type="button" class="nut nut-xanh" data-kq-tt="thanhCong">✔ Thanh toán thành công</button>
          <button type="button" class="nut nut-do" data-kq-tt="thatBai">✖ Thanh toán thất bại</button>
          <button type="button" class="nut nut-vang" data-kq-tt="hetHan">⏱ Hết hạn giữ chỗ</button>
        </div>
      </div>
    `);

    document.querySelectorAll('[data-kq-tt]').forEach(btn => {
      btn.onclick = async () => {
        const kq = await goiApi('dat-hang/thanh-toan', {
          method: 'POST',
          body: JSON.stringify({
            maThanhToan: duLieuTt.maThanhToan,
            maDiaChi: duLieuTt.maDiaChi,
            ketQua: btn.dataset.kqTt,
          }),
        });
        await taiGioHang();
        taiDanhSachSanPham();
        if (kq.thanhCong) {
          moManHinhXacNhanDonHang(kq.duLieu);
        } else {
          dongModal();
          hienToast(kq.thongBao, false);
        }
      };
    });
  }

  function moManHinhXacNhanDonHang(duLieuDon) {
    moModal('Đặt hàng thành công!', `
      <div style="text-align:center; padding:16px 0;">
        <div style="font-size:44px;">🎉</div>
        <h3 style="margin:8px 0;">Cảm ơn bạn đã đặt hàng!</h3>
        <p>Mã đơn hàng: <strong>#DH${duLieuDon.maDonHang}</strong> · Trạng thái: <strong>${duLieuDon.trangThai}</strong></p>
        <p>Thanh toán: <strong>${duLieuDon.trangThaiThanhToan}</strong> · Tổng tiền: <strong style="color:#dc2626;">${dinhDangTien(duLieuDon.tongTien)}</strong></p>
        <div style="margin-top:16px; display:flex; gap:10px; justify-content:center;">
          <button type="button" class="nut nut-chinh" id="xn-xem-don">Xem đơn hàng của tôi</button>
          <button type="button" class="nut nut-phu" id="xn-tiep-tuc">Tiếp tục mua sắm</button>
        </div>
      </div>
    `);
    document.getElementById('xn-xem-don').onclick = () => moModalHoSo('don-hang');
    document.getElementById('xn-tiep-tuc').onclick = dongModal;
  }

  // ==================== HỒ SƠ, ĐỊA CHỈ & ĐƠN HÀNG CỦA TÔI (UC43-46, UC31-32) ====================
  async function moModalHoSo(tab = 'don-hang') {
    moModal('Tài khoản Khách hàng', `
      <div class="tab-bar">
        <button type="button" class="nut ${tab === 'don-hang' ? 'nut-chinh' : 'nut-phu'}" data-hs-tab="don-hang">Đơn hàng của tôi (UC46, UC31-32)</button>
        <button type="button" class="nut ${tab === 'ho-so' ? 'nut-chinh' : 'nut-phu'}" data-hs-tab="ho-so">Hồ sơ cá nhân (UC43-44)</button>
        <button type="button" class="nut ${tab === 'dia-chi' ? 'nut-chinh' : 'nut-phu'}" data-hs-tab="dia-chi">Sổ địa chỉ (UC45)</button>
        <button type="button" class="nut ${tab === 'thong-bao' ? 'nut-chinh' : 'nut-phu'}" data-hs-tab="thong-bao">Thông báo</button>
      </div>
      <div id="vung-ho-so">Đang tải...</div>
    `);
    document.querySelectorAll('[data-hs-tab]').forEach(b => {
      b.onclick = () => moModalHoSo(b.dataset.hsTab);
    });

    const vung = document.getElementById('vung-ho-so');

    if (tab === 'don-hang') {
      const kq = await goiApi('ho-so/don-hang');
      const ds = kq.duLieu || [];
      if (ds.length === 0) {
        vung.innerHTML = '<div style="text-align:center; padding:24px; color:#64748b;">Bạn chưa có đơn hàng nào</div>';
        return;
      }
      vung.innerHTML = ds.map(dh => {
        const idxMoc = CAC_MOC_GIAO_HANG.indexOf(dh.trangThai);
        const choTuHuy = dh.trangThai === 'Chờ duyệt';
        const choYeuCauHuy = ['Đã duyệt', 'Đang đóng gói', 'Đã giao shipper', 'Đang giao'].includes(dh.trangThai) && dh.trangThaiYeuCauHuy !== 'Chờ xử lý';
        return `
          <div style="border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px;">
              <strong>#DH${dh.maDonHang} · ${dh.ngayDat}</strong>
              <strong style="color:#dc2626;">${dinhDangTien(dh.tongTien)}</strong>
            </div>
            <div class="chu-phu">Giao tới: ${dh.nguoiNhan} (${dh.soDienThoai}) — ${dh.diaChiGiaoHang}</div>
            <div class="chu-phu">Thanh toán: ${dh.phuongThucThanhToan} (<strong>${dh.trangThaiThanhToan}</strong>)${dh.trangThaiYeuCauHuy ? ` · Yêu cầu hủy: <strong>${dh.trangThaiYeuCauHuy}</strong>` : ''}</div>
            ${dh.trangThai === 'Đã hủy' ? '<div style="color:#dc2626; font-weight:700; margin:6px 0;">Trạng thái: Đã hủy</div>' : `
              <div class="tien-do-don">
                ${CAC_MOC_GIAO_HANG.map((m, i) => `<span class="buoc-don ${i < idxMoc ? 'da-qua' : (i === idxMoc ? 'hien-tai' : '')}">${m}</span>`).join('')}
              </div>
            `}
            <div style="font-size:12.5px; margin:8px 0;">${(dh.chiTiet || []).map(c => `• ${c.tenDienThoai} x${c.soLuong} (${dinhDangTien(c.donGia)})`).join('<br>')}</div>
            <div style="display:flex; gap:8px; margin-top:8px;">
              ${choTuHuy ? `<button class="nut nut-do nut-nho" data-tu-huy="${dh.maDonHang}">Hủy đơn (UC31)</button>` : ''}
              ${choYeuCauHuy ? `<button class="nut nut-vang nut-nho" data-yc-huy="${dh.maDonHang}">Yêu cầu hủy đơn (UC32)</button>` : ''}
            </div>
          </div>
        `;
      }).join('');

      vung.querySelectorAll('[data-tu-huy]').forEach(b => {
        b.onclick = async () => {
          const lyDo = prompt('Nhập lý do hủy đơn (tùy chọn):', 'Thay đổi nhu cầu mua sắm');
          if (lyDo === null) return;
          const r = await goiApi('huy-don/tu-huy', { method: 'POST', body: JSON.stringify({ maDonHang: Number(b.dataset.tuHuy), lyDo }) });
          hienToast(r.thongBao, r.thanhCong);
          moModalHoSo('don-hang');
          taiDanhSachSanPham();
        };
      });
      vung.querySelectorAll('[data-yc-huy]').forEach(b => {
        b.onclick = async () => {
          const lyDo = prompt('Nhập lý do yêu cầu hủy đơn hàng:');
          if (!lyDo) return;
          const r = await goiApi('huy-don/gui-yeu-cau', { method: 'POST', body: JSON.stringify({ maDonHang: Number(b.dataset.ycHuy), lyDo }) });
          hienToast(r.thongBao, r.thanhCong);
          moModalHoSo('don-hang');
        };
      });
    } else if (tab === 'ho-so') {
      const kq = await goiApi('ho-so');
      const hs = kq.duLieu || {};
      vung.innerHTML = `
        <form id="f-hs">
          <div class="nhom-nhap"><label>Họ và tên</label><input id="hs-ten" value="${hs.hoTen || ''}" required /></div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Số điện thoại</label><input id="hs-sdt" value="${hs.soDienThoai || ''}" /></div>
            <div class="nhom-nhap"><label>Email</label><input id="hs-email" value="${hs.email || ''}" /></div>
          </div>
          <button type="submit" class="nut nut-chinh">Lưu thay đổi hồ sơ</button>
        </form>
      `;
      document.getElementById('f-hs').onsubmit = async (e) => {
        e.preventDefault();
        const r = await goiApi('ho-so', { method: 'PUT', body: JSON.stringify({ hoTen: document.getElementById('hs-ten').value, soDienThoai: document.getElementById('hs-sdt').value, email: document.getElementById('hs-email').value }) });
        hienToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) {
          trangThai.taiKhoan.hoTen = r.duLieu.hoTen;
          renderVungTaiKhoan();
        }
      };
    } else if (tab === 'dia-chi') {
      const [kqDc, kqPhi] = await Promise.all([goiApi('ho-so/dia-chi'), goiApi('phi-van-chuyen')]);
      const dsDc = kqDc.duLieu || [];
      const dsQt = kqPhi.duLieu || [];
      vung.innerHTML = `
        ${dsDc.map(dc => `
          <div style="border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
            <div>
              <strong>${dc.nguoiNhan}</strong> (${dc.soDienThoai}) ${dc.macDinh ? '<span style="color:#16a34a; font-weight:700;">★ Mặc định</span>' : ''}
              <div class="chu-phu">${dc.diaChi} · ${dc.khuVuc}</div>
            </div>
            <div style="display:flex; gap:6px;">
              ${!dc.macDinh ? `<button class="nut nut-phu nut-nho" data-md-dc="${dc.maDiaChi}">Đặt mặc định</button>` : ''}
              <button class="nut nut-do nut-nho" data-xoa-dc="${dc.maDiaChi}">Xóa</button>
            </div>
          </div>
        `).join('')}
        <form id="f-dc" style="margin-top:14px; border-top:1px solid #e2e8f0; padding-top:12px;">
          <h4>Thêm địa chỉ mới</h4>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label>Người nhận</label><input id="ndc-ten" required /></div>
            <div class="nhom-nhap"><label>Số điện thoại</label><input id="ndc-sdt" required /></div>
          </div>
          <div class="nhom-nhap"><label>Địa chỉ</label><input id="ndc-dc" required /></div>
          <div class="nhom-nhap"><label>Khu vực</label><select id="ndc-qt">${dsQt.map(q => `<option value="${q.maQuyTac}">${q.khuVuc}</option>`).join('')}</select></div>
          <button type="submit" class="nut nut-chinh">Thêm địa chỉ</button>
        </form>
      `;
      vung.querySelectorAll('[data-md-dc]').forEach(b => {
        b.onclick = async () => {
          const r = await goiApi('ho-so/dia-chi', { method: 'PUT', body: JSON.stringify({ maDiaChi: Number(b.dataset.mdDc), macDinh: true }) });
          hienToast(r.thongBao, r.thanhCong);
          moModalHoSo('dia-chi');
        };
      });
      vung.querySelectorAll('[data-xoa-dc]').forEach(b => {
        b.onclick = async () => {
          const r = await goiApi('ho-so/dia-chi?maDiaChi=' + b.dataset.xoaDc, { method: 'DELETE' });
          hienToast(r.thongBao, r.thanhCong);
          moModalHoSo('dia-chi');
        };
      });
      document.getElementById('f-dc').onsubmit = async (e) => {
        e.preventDefault();
        const r = await goiApi('ho-so/dia-chi', { method: 'POST', body: JSON.stringify({ nguoiNhan: document.getElementById('ndc-ten').value, soDienThoai: document.getElementById('ndc-sdt').value, diaChi: document.getElementById('ndc-dc').value, maQuyTac: Number(document.getElementById('ndc-qt').value), macDinh: true }) });
        hienToast(r.thongBao, r.thanhCong);
        if (r.thanhCong) moModalHoSo('dia-chi');
      };
    } else {
      const kq = await goiApi('ho-so/thong-bao');
      const ds = kq.duLieu || [];
      vung.innerHTML = ds.map(tb => `<div style="border-bottom:1px solid #f1f5f9; padding:10px 0;"><div>${tb.noiDung}</div><div class="chu-phu">${tb.thoiDiemTao}</div></div>`).join('') || '<div class="chu-phu">Chưa có thông báo</div>';
    }
  }

  // ==================== KHỞI ĐỘNG ====================
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('form-tim-kiem').onsubmit = (e) => { e.preventDefault(); taiDanhSachSanPham(); };
    document.getElementById('nut-ap-dung-loc').onclick = taiDanhSachSanPham;
    document.getElementById('nut-xoa-loc').onclick = () => {
      document.getElementById('nhap-tu-khoa').value = '';
      ['loc-hang', 'loc-gia-tu', 'loc-gia-den', 'loc-ram', 'loc-rom', 'loc-pin'].forEach(id => { document.getElementById(id).value = ''; });
      document.getElementById('loc-sap-xep').value = 'moiNhat';
      trangThai.danhMucHienTai = '';
      taiDanhMuc();
      taiDanhSachSanPham();
    };
    document.getElementById('nut-mo-gio-hang').onclick = moModalGioHang;
    document.getElementById('dong-modal-web').onclick = dongModal;
    document.getElementById('modal-web').onclick = (e) => { if (e.target.id === 'modal-web') dongModal(); };

    khoiTaoPhien();
    taiDanhMuc();
    taiDanhSachSanPham();
  });
})();
