// ============================================================================
// assets/main.js
// Giao diện Khách hàng Phone Store theo kiến trúc BCE
// Đồng bộ thiết kế giao diện Mục 3.6 báo cáo v4 (Sản phẩm, Chi tiết, Giỏ hàng, Đặt hàng, Xác thực)
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

  function maHoaHtml(chuoi) {
    return String(chuoi ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
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
    if (res.status === 401 && trangThai.maPhien && !duongDan.startsWith('xac-thuc/') && !duongDan.startsWith('dat-hang/kiem-tra')) {
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

  // ==================== XÁC THỰC NGƯỜI DÙNG (Mục 3.6 - Bảng 209, Bảng 210) ====================
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
        <button type="button" id="nut-mo-tai-khoan" class="nut-header">👤 ${maHoaHtml(trangThai.taiKhoan.hoTen || trangThai.taiKhoan.dinhDanh)}</button>
        ${laNoiBo ? `<a href="/admin" class="nut nut-chinh" style="text-decoration:none;">Cổng Quản trị</a>` : ''}
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
      if (trangThai.dangNhapDoForm && !confirm('Bạn đang có dữ liệu chưa lưu trong biểu mẫu. Bạn có chắc chắn muốn đăng xuất?')) {
        return;
      }
      await goiApi('xac-thuc/dang-xuat', { method: 'POST' });
      trangThai.maPhien = '';
      trangThai.taiKhoan = null;
      localStorage.removeItem(KHOA_PHIEN_KH);
      renderVungTaiKhoan();
      await taiGioHang();
      hienToast('Đã đăng xuất. Giỏ hàng tài khoản của bạn vẫn được bảo toàn trong hệ thống.');
    };
  }

  function moModalXacThuc(tab = 'dang-nhap') {
    moModal('Tài khoản Phone Store', `
      <div class="tab-bar">
        <button type="button" class="nut ${tab === 'dang-nhap' ? 'nut-chinh' : 'nut-phu'}" id="tab-dn">Đăng nhập</button>
        <button type="button" class="nut ${tab === 'dang-ky' ? 'nut-chinh' : 'nut-phu'}" id="tab-dk">Đăng ký tài khoản</button>
        <button type="button" class="nut ${tab === 'quen-mk' ? 'nut-chinh' : 'nut-phu'}" id="tab-qmk">Quên mật khẩu</button>
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
          <div class="nhom-nhap">
            <label for="txtDinhDanh">Email hoặc Số điện thoại</label>
            <input id="txtDinhDanh" required placeholder="VD: nguyenvana@gmail.com" />
          </div>
          <div class="nhom-nhap">
            <label for="pwdMatKhau">Mật khẩu</label>
            <input id="pwdMatKhau" type="password" required placeholder="Nhập mật khẩu..." />
          </div>
          <button type="submit" id="btnDangNhap" class="nut nut-chinh nut-rong">Đăng nhập</button>
          <div style="margin-top:14px; padding-top:12px; border-top:1px solid #e2e8f0;">
            <div class="chu-phu" style="margin-bottom:6px;">Chọn nhanh định danh mẫu:</div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
              <button type="button" class="nut-goi-y-tk" data-chon-tk="nguyenvana@gmail.com">Khách hàng: nguyenvana@gmail.com</button>
              <button type="button" class="nut-goi-y-tk" data-chon-tk="tranthib@gmail.com">Khách hàng: tranthib@gmail.com</button>
              <button type="button" class="nut-goi-y-tk" data-chon-tk="admin@phonestore.vn">Quản trị: admin@phonestore.vn</button>
            </div>
          </div>
        </form>
      `;
      vung.querySelectorAll('[data-chon-tk]').forEach(btn => {
        btn.onclick = () => {
          document.getElementById('txtDinhDanh').value = btn.dataset.chonTk;
          document.getElementById('pwdMatKhau').focus();
        };
      });
      document.getElementById('f-dn').onsubmit = async (e) => {
        e.preventDefault();
        const kq = await goiApi('xac-thuc/dang-nhap', {
          method: 'POST',
          body: JSON.stringify({
            dinhDanh: document.getElementById('txtDinhDanh').value.trim(),
            matKhau: document.getElementById('pwdMatKhau').value,
            maPhienTam: trangThai.maPhienTam,
          }),
        });
        if (!kq.thanhCong) {
          alert(kq.thongBao);
          return;
        }
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
        hienToast('Đăng nhập thành công! Giỏ hàng tạm (nếu có) đã được đồng bộ vào tài khoản.');
        if (trangThai.quayLaiDatHangSauDangNhap) {
          trangThai.quayLaiDatHangSauDangNhap = false;
          moModalDatHang();
        }
      };
    } else if (tab === 'dang-ky') {
      vung.innerHTML = `
        <form id="f-dk">
          <div class="nhom-nhap"><label for="txtHoTen">Họ và tên</label><input id="txtHoTen" required placeholder="Nguyễn Văn A" /></div>
          <div class="nhom-nhap"><label for="txtDinhDanh">Email hoặc Số điện thoại</label><input id="txtDinhDanh" required placeholder="Email hoặc SĐT đăng nhập" /></div>
          <div class="nhom-nhap"><label for="pwdMatKhau">Mật khẩu (tối thiểu 6 ký tự)</label><input id="pwdMatKhau" type="password" minlength="6" required /></div>
          <div class="nhom-nhap"><label for="pwdXacNhan">Xác nhận mật khẩu</label><input id="pwdXacNhan" type="password" minlength="6" required /></div>
          <button type="submit" id="btnDangKy" class="nut nut-chinh nut-rong">Đăng ký tài khoản</button>
        </form>
      `;
      document.getElementById('f-dk').onsubmit = async (e) => {
        e.preventDefault();
        const matKhau = document.getElementById('pwdMatKhau').value;
        const xacNhanMatKhau = document.getElementById('pwdXacNhan').value;
        if (matKhau !== xacNhanMatKhau) {
          alert('Mật khẩu xác nhận không khớp');
          return;
        }
        const kq = await goiApi('xac-thuc/dang-ky', {
          method: 'POST',
          body: JSON.stringify({
            hoTen: document.getElementById('txtHoTen').value.trim(),
            dinhDanh: document.getElementById('txtDinhDanh').value.trim(),
            matKhau,
            xacNhanMatKhau,
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
        hienToast('Đăng ký tài khoản thành công!');
        if (trangThai.quayLaiDatHangSauDangNhap) {
          trangThai.quayLaiDatHangSauDangNhap = false;
          moModalDatHang();
        }
      };
    } else {
      vung.innerHTML = `
        <div class="nhom-nhap"><label for="txtDinhDanh">Email hoặc SĐT đã đăng ký</label><input id="txtDinhDanh" placeholder="Nhập định danh tài khoản" /></div>
        <button type="button" id="btnGuiMaXacThuc" class="nut nut-phu">1. Gửi mã xác thực</button>
        <div id="q-kq" class="chu-phu" style="margin:10px 0;"></div>
        <div class="nhom-nhap"><label for="txtMaXacThuc">Mã xác thực (6 số)</label><input id="txtMaXacThuc" /></div>
        <div class="nhom-nhap"><label for="pwdMatKhauMoi">Mật khẩu mới</label><input id="pwdMatKhauMoi" type="password" minlength="6" /></div>
        <div class="nhom-nhap"><label for="pwdXacNhan">Xác nhận mật khẩu mới</label><input id="pwdXacNhan" type="password" minlength="6" /></div>
        <button type="button" id="btnDatLaiMatKhau" class="nut nut-chinh nut-rong">2. Đặt lại mật khẩu</button>
      `;
      document.getElementById('btnGuiMaXacThuc').onclick = async () => {
        const kq = await goiApi('xac-thuc/quen-mat-khau', { method: 'POST', body: JSON.stringify({ dinhDanh: document.getElementById('txtDinhDanh').value.trim() }) });
        document.getElementById('q-kq').textContent = kq.thongBao + (kq.duLieu && kq.duLieu.maXacThucMoPhong ? ` — Mã xác thực mô phỏng: ${kq.duLieu.maXacThucMoPhong}` : '');
      };
      document.getElementById('btnDatLaiMatKhau').onclick = async () => {
        const matKhauMoi = document.getElementById('pwdMatKhauMoi').value;
        const xacNhanMatKhau = document.getElementById('pwdXacNhan').value;
        if (matKhauMoi !== xacNhanMatKhau) {
          alert('Mật khẩu xác nhận không khớp');
          return;
        }
        const kq = await goiApi('xac-thuc/dat-lai-mat-khau', {
          method: 'POST',
          body: JSON.stringify({
            dinhDanh: document.getElementById('txtDinhDanh').value.trim(),
            maXacThuc: document.getElementById('txtMaXacThuc').value.trim(),
            matKhauMoi,
            xacNhanMatKhau,
          }),
        });
        alert(kq.thongBao);
        if (kq.thanhCong) moModalXacThuc('dang-nhap');
      };
    }
  }

  // ==================== TÌM KIẾM, LỌC & CHI TIẾT SẢN PHẨM (Mục 3.6.3 - 3.6.4) ====================
  async function taiDanhMuc() {
    const kq = await goiApi('danh-muc');
    const ds = kq.duLieu || [];
    const thanh = document.getElementById('thanh-danh-muc');
    thanh.innerHTML = `<button type="button" class="vien-danh-muc dang-chon" data-dm="">Tất cả</button>` +
      ds.map(dm => `<button type="button" class="vien-danh-muc" data-dm="${dm.maDanhMuc}">${maHoaHtml(dm.ten)}</button>`).join('');
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
    const tuKhoa = document.getElementById('txtTuKhoa').value.trim();
    if (tuKhoa) params.set('tuKhoa', tuKhoa);
    if (trangThai.danhMucHienTai) params.set('maDanhMuc', trangThai.danhMucHienTai);
    const hang = document.getElementById('cboHang').value;
    if (hang) params.set('hang', hang);
    const giaTu = document.getElementById('numGiaTu').value;
    if (giaTu) params.set('giaTu', giaTu);
    const giaDen = document.getElementById('numGiaDen').value;
    if (giaDen) params.set('giaDen', giaDen);
    const ram = document.getElementById('cboRAM').value;
    if (ram) params.set('ram', ram);
    const rom = document.getElementById('cboROM').value;
    if (rom) params.set('rom', rom);
    const dungLuongPin = document.getElementById('cboPin').value;
    if (dungLuongPin) params.set('dungLuongPin', dungLuongPin);
    const sapXep = document.getElementById('loc-sap-xep').value;
    if (sapXep && sapXep !== 'moiNhat') params.set('sapXep', sapXep);

    const kq = await goiApi('tim-kiem' + (params.toString() ? '?' + params.toString() : ''));
    if (!kq.thanhCong) {
      hienToast(kq.thongBao, false);
      return;
    }

    const ds = kq.duLieu || [];
    document.getElementById('tong-so-san-pham').textContent = `Tìm thấy ${ds.length} sản phẩm`;
    const luoi = document.getElementById('grdSanPham');

    if (ds.length === 0) {
      luoi.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:#64748b;">Không tìm thấy sản phẩm phù hợp</div>';
      return;
    }

    luoi.innerHTML = ds.map((sp, idx) => {
      const conHang = sp.trangThaiTonKho === 'Còn hàng';
      const coKm = sp.mucUuDaiKhuyenMai > 0;
      const ts = sp.thongSoKyThuat || {};
      return `
        <article class="the-san-pham">
          <div class="khung-anh-sp" data-xem-sp="${sp.maDienThoai}">
            ${coKm ? `<span class="nhan-giam-gia">-${sp.mucUuDaiKhuyenMai}%</span>` : ''}
            <span class="nhan-ton-kho ${conHang ? 'con-hang' : 'het-hang'}">${sp.trangThaiTonKho}</span>
            <img src="${sp.hinhAnh}" alt="${maHoaHtml(sp.ten)}" loading="lazy" />
          </div>
          <h3 class="ten-sp" data-xem-sp="${sp.maDienThoai}">${maHoaHtml(sp.ten)}</h3>
          <div class="thong-so-nhanh">${maHoaHtml(sp.hang)} · ${ts.ram || 0}GB/${ts.rom || 0}GB · ${ts.dungLuongPin || 0}mAh</div>
          <div class="gia-sp">
            <span class="gia-cuoi">${dinhDangTien(sp.giaBanCuoi)}</span>
            ${coKm ? `<span class="gia-goc">${dinhDangTien(sp.giaGoc)}</span>` : ''}
          </div>
          <div class="nhom-nut-sp">
            <button type="button" ${idx === 0 ? 'id="btnXemChiTiet"' : ''} name="btnXemChiTiet" class="nut nut-phu" data-xem-sp="${sp.maDienThoai}">Chi tiết</button>
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

  // Mục 3.6.4 - Bảng 179, Bảng 180: Màn hình Chi tiết sản phẩm
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
          <img src="${sp.hinhAnh}" alt="${maHoaHtml(sp.ten)}" style="max-height:260px; max-width:100%; object-fit:contain;" />
          <div style="margin-top:10px;">
            <span id="lblTinhTrang" class="nhan-ton-kho ${conHang ? 'con-hang' : 'het-hang'}" style="position:static;">${sp.trangThaiTonKho} (Khả dụng: ${sp.tonKho.tonKhaDung})</span>
          </div>
        </div>
        <div>
          <div style="margin-bottom:10px;">
            <span id="lblGiaBan" class="gia-cuoi" style="font-size:22px;">${dinhDangTien(sp.giaBanCuoi)}</span>
            ${coKm ? `<span class="gia-goc">${dinhDangTien(sp.giaGoc)}</span> <span style="color:#dc2626; font-weight:700;">(-${sp.mucUuDaiKhuyenMai}%)</span>` : ''}
          </div>
          ${(sp.khuyenMaiApDung || []).map(km => `<div style="background:#fef2f2; color:#991b1b; padding:6px 10px; border-radius:8px; font-size:12.5px; margin-bottom:8px;">🎁 <strong>${maHoaHtml(km.ten)}</strong>: ${maHoaHtml(km.noiDung || '')}</div>`).join('')}
          <p class="chu-phu">${maHoaHtml(sp.moTa || '')}</p>
          <div style="display:flex; gap:10px; align-items:center; margin-top:14px;">
            <input id="numSoLuong" type="number" min="1" max="${Math.max(1, sp.tonKho.tonKhaDung)}" value="1" style="width:84px; padding:8px; border:1px solid #cbd5e1; border-radius:8px;" />
            <button type="button" id="btnThemVaoGioHang" class="nut nut-chinh" ${conHang ? '' : 'disabled'}>Thêm vào giỏ hàng</button>
          </div>
        </div>
      </div>
      <h4 style="margin:18px 0 8px;">Thông số kỹ thuật</h4>
      <table id="lblThongSo" class="bang-thong-so">
        <tr><th>Màn hình</th><td>${maHoaHtml(ts.manHinh || '-')}</td><th>Chip xử lý</th><td>${maHoaHtml(ts.chip || '-')}</td></tr>
        <tr><th>RAM</th><td>${ts.ram || 0} GB</td><th>Bộ nhớ (ROM)</th><td>${ts.rom || 0} GB</td></tr>
        <tr><th>Camera</th><td>${maHoaHtml(ts.camera || '-')}</td><th>Dung lượng pin</th><td>${ts.dungLuongPin || 0} mAh</td></tr>
        <tr><th>Hệ điều hành</th><td colspan="3">${maHoaHtml(ts.heDieuHanh || '-')}</td></tr>
      </table>
    `);

    const btnThem = document.getElementById('btnThemVaoGioHang');
    if (btnThem) {
      btnThem.onclick = async () => {
        const sl = Number(document.getElementById('numSoLuong').value) || 1;
        await themVaoGioHang(sp.maDienThoai, sl);
        dongModal();
      };
    }
  }

  // ==================== GIỎ HÀNG (Mục 3.6.11 - Bảng 193, Bảng 194) ====================
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

    moModal('Giỏ hàng của bạn', `
      ${ds.length === 0 ? '<div style="text-align:center; padding:30px; color:#64748b;">Giỏ hàng của bạn đang trống</div>' : `
        <table id="grdGioHang" class="bang-thong-so">
          <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th><th>Thao tác</th></tr></thead>
          <tbody>
            ${ds.map((ct, idx) => `
              <tr>
                <td><strong>${maHoaHtml(ct.tenDienThoai)}</strong><div class="chu-phu">Tồn khả dụng: ${ct.tonKhaDung}</div></td>
                <td>${dinhDangTien(ct.donGia)}</td>
                <td>
                  <div style="display:flex; align-items:center; gap:6px;">
                    <input type="number" ${idx === 0 ? 'id="numSoLuong"' : ''} class="inp-sl-gio" data-ma-ct="${ct.maChiTietGioHang}" min="1" max="${Math.max(1, ct.tonKhaDung)}" value="${ct.soLuong}" style="width:68px; padding:6px; border:1px solid #cbd5e1; border-radius:6px;" />
                    <button type="button" ${idx === 0 ? 'id="btnCapNhatSoLuong"' : ''} class="nut nut-phu nut-nho" data-cn-ct="${ct.maChiTietGioHang}">Cập nhật</button>
                  </div>
                </td>
                <td><strong>${dinhDangTien(ct.donGia * ct.soLuong)}</strong></td>
                <td><button type="button" ${idx === 0 ? 'id="btnXoa"' : ''} class="nut nut-do nut-nho" data-xoa-ct="${ct.maChiTietGioHang}">Xóa</button></td>
              </tr>
            `).join('')}
          </tbody>
        </table>

        <div style="display:flex; gap:8px; margin:14px 0; align-items:center;">
          <input id="nhap-voucher-gio" value="${maHoaHtml(gh.maVoucher || '')}" placeholder="Nhập mã giảm giá (VD: GIAM500K, SALE5PT, VIP200K)" style="flex:1; padding:9px; border:1px solid #cbd5e1; border-radius:8px;" />
          <button type="button" id="nut-ap-voucher-gio" class="nut nut-chinh">Áp dụng</button>
          ${gh.maVoucher ? `<button type="button" id="nut-bo-voucher-gio" class="nut nut-phu">Bỏ áp dụng</button>` : ''}
        </div>

        <div style="background:#f8fafc; padding:14px; border-radius:10px; margin-bottom:14px; border:1px solid #e2e8f0;">
          <div style="display:flex; justify-content:space-between; margin-bottom:4px;"><span>Tạm tính:</span><strong>${dinhDangTien(gh.tongTamTinh)}</strong></div>
          <div style="display:flex; justify-content:space-between; margin-bottom:4px; color:#16a34a;"><span>Giảm giá mã ưu đãi${gh.maVoucher ? ` (${maHoaHtml(gh.maVoucher)})` : ''}:</span><strong>-${dinhDangTien(gh.tienGiamGia)}</strong></div>
          <div style="display:flex; justify-content:space-between; font-size:17px; border-top:1px solid #e2e8f0; padding-top:8px;"><span>Tổng tiền hàng:</span><strong style="color:#dc2626;">${dinhDangTien(gh.tongTien)}</strong></div>
        </div>

        <button type="button" id="btnDatHang" class="nut nut-xanh nut-rong" style="padding:11px; font-size:15px;">Tiến hành đặt hàng</button>
      `}
    `);

    document.querySelectorAll('[data-cn-ct]').forEach(b => {
      b.onclick = async () => {
        const maCt = Number(b.dataset.cnCt);
        const inp = document.querySelector(`.inp-sl-gio[data-ma-ct="${maCt}"]`);
        const slMoi = Number(inp ? inp.value : 1);
        const kq = await goiApi('gio-hang', {
          method: 'PUT',
          body: JSON.stringify({ maChiTietGioHang: maCt, soLuong: slMoi }),
        });
        if (!kq.thanhCong) alert(kq.thongBao);
        moModalGioHang();
      };
    });

    document.querySelectorAll('[data-xoa-ct]').forEach(b => {
      b.onclick = async () => {
        await goiApi('gio-hang?maChiTietGioHang=' + b.dataset.xoaCt, { method: 'DELETE' });
        moModalGioHang();
      };
    });

    if (document.getElementById('nut-ap-voucher-gio')) {
      document.getElementById('nut-ap-voucher-gio').onclick = async () => {
        const r = await goiApi('ap-dung-voucher', {
          method: 'POST',
          body: JSON.stringify({ maGioHang: gh.maGioHang, maVoucher: document.getElementById('nhap-voucher-gio').value.trim() }),
        });
        hienToast(r.thongBao, r.thanhCong);
        if (!r.thanhCong) alert(r.thongBao);
        moModalGioHang();
      };
    }

    if (document.getElementById('nut-bo-voucher-gio')) {
      document.getElementById('nut-bo-voucher-gio').onclick = async () => {
        await goiApi('ap-dung-voucher?maGioHang=' + gh.maGioHang, { method: 'DELETE' });
        moModalGioHang();
      };
    }

    if (document.getElementById('btnDatHang')) {
      document.getElementById('btnDatHang').onclick = async () => {
        // Kiểm tra phiên đăng nhập qua DatHangCtrl::datHang -> PhienDangNhap::kiemTraDangNhap
        const kqKiemTra = await goiApi('dat-hang/kiem-tra', {
          method: 'POST',
          body: JSON.stringify({ maGioHang: gh.maGioHang }),
        });
        if (!kqKiemTra.thanhCong || !trangThai.taiKhoan || trangThai.taiKhoan.vaiTro !== 'Khách hàng') {
          trangThai.quayLaiDatHangSauDangNhap = true;
          moModalXacThuc('dang-nhap');
          hienToast('Vui lòng đăng nhập hoặc đăng ký để đặt hàng (giỏ hàng tạm vẫn được giữ nguyên)', true);
          return;
        }
        moModalDatHang();
      };
    }
  }

  // ==================== ĐẶT HÀNG & THANH TOÁN (Mục 3.6.12 - Bảng 195, Bảng 196) ====================
  async function moModalDatHang() {
    await taiGioHang();
    const [kqDc, kqPhi] = await Promise.all([goiApi('ho-so/dia-chi'), goiApi('phi-van-chuyen')]);
    let dsDiaChi = kqDc.duLieu || [];
    const dsQuyTac = kqPhi.duLieu || [];
    let gh = trangThai.gioHang;

    if (!gh || !gh.chiTiet || gh.chiTiet.length === 0) {
      hienToast('Giỏ hàng đang trống', false);
      return;
    }

    let phiVanChuyenHienTai = 0;

    moModal('Xác nhận Đặt hàng & Thanh toán', `
      <form id="form-dat-hang">
        <h4 style="margin:0 0 8px;">1. Địa chỉ giao hàng</h4>
        <div class="nhom-nhap" style="display:flex; gap:8px; align-items:center;">
          <select id="cboDiaChi" style="flex:1;">
            ${dsDiaChi.map(dc => `<option value="${dc.maDiaChi}" data-qt="${dc.maQuyTac}">${maHoaHtml(dc.nguoiNhan)} (${maHoaHtml(dc.soDienThoai)}) — ${maHoaHtml(dc.diaChi)} [${maHoaHtml(dc.khuVuc)}]${dc.macDinh ? ' ★ Mặc định' : ''}</option>`).join('')}
            <option value="0" ${dsDiaChi.length === 0 ? 'selected' : ''}>+ Nhập địa chỉ giao hàng mới...</option>
          </select>
          <button type="button" id="btnChonDiaChi" class="nut nut-phu">Chọn địa chỉ</button>
        </div>

        <div id="lblThongTinNhanHang" class="hop-tom-tat-nhan-hang"></div>

        <div id="khung-dc-moi" class="${dsDiaChi.length > 0 ? 'an' : ''}" style="background:#f8fafc; padding:12px; border-radius:10px; margin-bottom:14px; border:1px solid #e2e8f0;">
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label for="txtNguoiNhan">Người nhận *</label><input id="txtNguoiNhan" value="${maHoaHtml(trangThai.taiKhoan.hoTen || '')}" /></div>
            <div class="nhom-nhap"><label for="txtSoDienThoai">Số điện thoại *</label><input id="txtSoDienThoai" value="${maHoaHtml(trangThai.taiKhoan.soDienThoai || '')}" /></div>
          </div>
          <div class="nhom-nhap"><label for="txtDiaChi">Địa chỉ chi tiết *</label><input id="txtDiaChi" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành..." /></div>
          <div class="nhom-nhap">
            <label for="cboQuyTacPhi">Khu vực tính phí vận chuyển</label>
            <select id="cboQuyTacPhi">
              ${dsQuyTac.map(q => `<option value="${q.maQuyTac}">${maHoaHtml(q.khuVuc)} (Phí chuẩn: ${dinhDangTien(q.mucPhi)}, miễn phí từ ${dinhDangTien(q.nguongMienPhi)})</option>`).join('')}
            </select>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <label style="display:flex; align-items:center; gap:8px; font-size:13px;">
              <input type="checkbox" id="dc-luu-so" checked style="width:auto;" /> Đặt làm địa chỉ mặc định
            </label>
            <button type="button" id="btnLuuDiaChiMoi" class="nut nut-chinh nut-nho">Lưu địa chỉ mới</button>
          </div>
        </div>

        <h4 style="margin:12px 0 6px;">2. Danh sách sản phẩm đặt mua</h4>
        <table id="grdDonHangTam" class="bang-thong-so">
          <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th></tr></thead>
          <tbody>
            ${gh.chiTiet.map(ct => `
              <tr>
                <td><strong>${maHoaHtml(ct.tenDienThoai)}</strong></td>
                <td>${dinhDangTien(ct.donGia)}</td>
                <td>x${ct.soLuong}</td>
                <td><strong>${dinhDangTien(ct.donGia * ct.soLuong)}</strong></td>
              </tr>
            `).join('')}
          </tbody>
        </table>

        <h4 style="margin:14px 0 6px;">3. Mã giảm giá & Phương thức thanh toán</h4>
        <div style="display:flex; gap:8px; margin-bottom:12px; align-items:center;">
          <input id="txtMaGiamGia" value="${maHoaHtml(gh.maVoucher || '')}" placeholder="Nhập mã giảm giá (VD: GIAM500K, SALE5PT, VIP200K)" style="flex:1; padding:9px; border:1px solid #cbd5e1; border-radius:8px;" />
          <button type="button" id="btnApDung" class="nut nut-chinh">Áp dụng</button>
          <button type="button" id="btnBoApDung" class="nut nut-phu" ${gh.maVoucher ? '' : 'disabled'}>Bỏ áp dụng</button>
        </div>

        <div class="nhom-nhap">
          <label for="cboPhuongThuc">Phương thức thanh toán</label>
          <select id="cboPhuongThuc">
            <option value="Thanh toán khi nhận hàng">Thanh toán khi nhận hàng (COD)</option>
            <option value="Thanh toán trực tuyến mô phỏng">Thanh toán trực tuyến mô phỏng</option>
          </select>
        </div>

        <div style="background:#eff6ff; padding:14px; border-radius:10px; margin:14px 0; border:1px solid #bfdbfe;">
          <div style="display:flex; justify-content:space-between;"><span>Tổng tạm tính:</span><strong id="lblTamTinh">${dinhDangTien(gh.tongTamTinh)}</strong></div>
          <div style="display:flex; justify-content:space-between; margin-top:4px; color:#16a34a;"><span>Tiền giảm giá mã ưu đãi:</span><strong id="lblTienGiamGia">-${dinhDangTien(gh.tienGiamGia)}</strong></div>
          <div style="display:flex; justify-content:space-between; margin-top:4px;"><span>Phí vận chuyển:</span><strong id="lblPhiVanChuyen">Đang tính...</strong></div>
          <div style="display:flex; justify-content:space-between; font-size:17px; border-top:1px solid #bfdbfe; padding-top:8px; margin-top:8px;">
            <span>Tổng thanh toán:</span><strong id="lblTongThanhToan" style="color:#dc2626;">${dinhDangTien(gh.tongTien)}</strong>
          </div>
        </div>

        <button type="submit" id="btnXacNhanDatHang" name="btnDatHang" class="nut nut-xanh nut-rong" style="padding:11px; font-size:15px;">Xác nhận đặt hàng</button>
      </form>
    `);

    const chonDc = document.getElementById('cboDiaChi');
    const khungDcMoi = document.getElementById('khung-dc-moi');
    const chonQt = document.getElementById('cboQuyTacPhi');
    const lblNhanHang = document.getElementById('lblThongTinNhanHang');

    function capNhatNhanThongTinNhanHang() {
      const maDc = Number(chonDc.value || 0);
      if (maDc > 0) {
        const dc = dsDiaChi.find(x => Number(x.maDiaChi) === maDc);
        if (dc) {
          lblNhanHang.innerHTML = `📍 <strong>Người nhận:</strong> ${maHoaHtml(dc.nguoiNhan)} · <strong>SĐT:</strong> ${maHoaHtml(dc.soDienThoai)}<br/><strong>Địa chỉ giao hàng:</strong> ${maHoaHtml(dc.diaChi)} (${maHoaHtml(dc.khuVuc)})`;
          return;
        }
      }
      const ten = document.getElementById('txtNguoiNhan').value.trim() || '(Chưa nhập người nhận)';
      const sdt = document.getElementById('txtSoDienThoai').value.trim() || '(Chưa nhập SĐT)';
      const diaChi = document.getElementById('txtDiaChi').value.trim() || '(Chưa nhập địa chỉ)';
      lblNhanHang.innerHTML = `📍 <strong>Người nhận mới:</strong> ${maHoaHtml(ten)} · <strong>SĐT:</strong> ${maHoaHtml(sdt)}<br/><strong>Địa chỉ giao hàng:</strong> ${maHoaHtml(diaChi)}`;
    }

    function capNhatTongHopTien() {
      document.getElementById('lblTamTinh').textContent = dinhDangTien(gh.tongTamTinh);
      document.getElementById('lblTienGiamGia').textContent = '-' + dinhDangTien(gh.tienGiamGia || 0);
      document.getElementById('lblPhiVanChuyen').textContent = phiVanChuyenHienTai === 0 ? 'Miễn phí (0 đ)' : dinhDangTien(phiVanChuyenHienTai);
      const tongCuoi = Math.max(0, Number(gh.tongTien || 0) + Number(phiVanChuyenHienTai || 0));
      document.getElementById('lblTongThanhToan').textContent = dinhDangTien(tongCuoi);
      document.getElementById('btnBoApDung').disabled = !gh.maVoucher;
    }

    async function capNhatPhiShip(ghiNhanDiaChiLenServer = false) {
      const maDc = Number(chonDc.value || 0);
      if (ghiNhanDiaChiLenServer && maDc > 0) {
        await goiApi('dat-hang/chon-dia-chi', {
          method: 'POST',
          body: JSON.stringify({ maGioHang: gh.maGioHang, maDiaChi: maDc }),
        });
      }
      const q = maDc > 0
        ? `dat-hang/phi-van-chuyen?maDiaChi=${maDc}&tongTamTinh=${gh.tongTamTinh}&maGioHang=${gh.maGioHang}`
        : `dat-hang/phi-van-chuyen?maQuyTac=${chonQt.value}&tongTamTinh=${gh.tongTamTinh}&maGioHang=${gh.maGioHang}`;
      const kq = await goiApi(q);
      phiVanChuyenHienTai = (kq.duLieu && kq.duLieu.phiVanChuyen !== undefined) ? Number(kq.duLieu.phiVanChuyen) : 0;
      capNhatNhanThongTinNhanHang();
      capNhatTongHopTien();
    }

    chonDc.onchange = () => {
      khungDcMoi.classList.toggle('an', Number(chonDc.value) > 0);
      capNhatPhiShip(true);
    };
    document.getElementById('btnChonDiaChi').onclick = () => {
      khungDcMoi.classList.toggle('an', Number(chonDc.value) > 0);
      capNhatPhiShip(true);
    };
    ['txtNguoiNhan', 'txtSoDienThoai', 'txtDiaChi'].forEach(id => {
      document.getElementById(id).addEventListener('input', capNhatNhanThongTinNhanHang);
    });
    if (chonQt) chonQt.onchange = () => capNhatPhiShip(false);

    // UC25: Nút Lưu địa chỉ mới ngay trên màn hình Đặt hàng
    document.getElementById('btnLuuDiaChiMoi').onclick = async () => {
      const r = await goiApi('dat-hang/luu-dia-chi', {
        method: 'POST',
        body: JSON.stringify({
          maGioHang: gh.maGioHang,
          nguoiNhan: document.getElementById('txtNguoiNhan').value.trim(),
          soDienThoai: document.getElementById('txtSoDienThoai').value.trim(),
          diaChi: document.getElementById('txtDiaChi').value.trim(),
          maQuyTac: Number(chonQt.value),
          luuVaoSoDiaChi: true,
          macDinh: document.getElementById('dc-luu-so').checked,
        }),
      });
      if (!r.thanhCong) {
        alert(r.thongBao);
        return;
      }
      hienToast(r.thongBao, true);
      const kqDcMoi = await goiApi('ho-so/dia-chi');
      dsDiaChi = kqDcMoi.duLieu || [];
      const maMoi = r.duLieu ? Number(r.duLieu.maDiaChi) : (dsDiaChi[0] ? Number(dsDiaChi[0].maDiaChi) : 0);
      chonDc.innerHTML = dsDiaChi.map(dc => `<option value="${dc.maDiaChi}" data-qt="${dc.maQuyTac}" ${Number(dc.maDiaChi) === maMoi ? 'selected' : ''}>${maHoaHtml(dc.nguoiNhan)} (${maHoaHtml(dc.soDienThoai)}) — ${maHoaHtml(dc.diaChi)} [${maHoaHtml(dc.khuVuc)}]${dc.macDinh ? ' ★ Mặc định' : ''}</option>`).join('') +
        `<option value="0">+ Nhập địa chỉ giao hàng mới...</option>`;
      khungDcMoi.classList.add('an');
      await capNhatPhiShip(true);
    };

    // UC30: Áp dụng & Bỏ áp dụng mã giảm giá ngay trên màn hình Đặt hàng
    document.getElementById('btnApDung').onclick = async () => {
      const maVc = document.getElementById('txtMaGiamGia').value.trim();
      const r = await goiApi('ap-dung-voucher', {
        method: 'POST',
        body: JSON.stringify({ maGioHang: gh.maGioHang, maVoucher: maVc }),
      });
      if (!r.thanhCong) {
        alert(r.thongBao);
        return;
      }
      hienToast(r.thongBao, true);
      await taiGioHang();
      gh = trangThai.gioHang;
      document.getElementById('txtMaGiamGia').value = gh.maVoucher || '';
      capNhatTongHopTien();
    };

    document.getElementById('btnBoApDung').onclick = async () => {
      const r = await goiApi('ap-dung-voucher?maGioHang=' + gh.maGioHang, { method: 'DELETE' });
      hienToast(r.thongBao || 'Đã gỡ mã giảm giá', r.thanhCong);
      await taiGioHang();
      gh = trangThai.gioHang;
      document.getElementById('txtMaGiamGia').value = '';
      capNhatTongHopTien();
    };

    capNhatPhiShip(Number(chonDc.value) > 0);

    document.getElementById('form-dat-hang').onsubmit = async (e) => {
      e.preventDefault();
      const maDiaChi = Number(chonDc.value || 0);
      const payload = {
        maGioHang: gh.maGioHang,
        maDiaChi,
        phuongThuc: document.getElementById('cboPhuongThuc').value,
      };
      if (maDiaChi === 0) {
        payload.nguoiNhan = document.getElementById('txtNguoiNhan').value.trim();
        payload.soDienThoai = document.getElementById('txtSoDienThoai').value.trim();
        payload.diaChi = document.getElementById('txtDiaChi').value.trim();
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
        moCongThanhToanMoPhong(kq.duLieu);
      } else {
        await taiGioHang();
        taiDanhSachSanPham();
        moManHinhXacNhanDonHang(kq.duLieu);
      }
    };
  }

  function moCongThanhToanMoPhong(duLieuTt) {
    moModal('Cổng Thanh toán Trực tuyến Mô phỏng', `
      <div style="text-align:center; padding:10px 0;">
        <p style="font-size:15px;">Giỏ hàng đã được <strong>khóa</strong> và tồn kho đã <strong>giữ chỗ tạm</strong> cho giao dịch <strong>#TT${duLieuTt.maThanhToan}</strong>.</p>
        <div style="font-size:24px; font-weight:800; color:#dc2626; margin:14px 0;">${dinhDangTien(duLieuTt.tongTien)}</div>
        <p class="chu-phu">Chọn kết quả phản hồi mô phỏng từ cổng thanh toán:</p>
        <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:16px;">
          <button type="button" id="btnThanhToan" class="nut nut-xanh" data-kq-tt="thanhCong">✔ Thanh toán thành công</button>
          <button type="button" class="nut nut-do" data-kq-tt="thatBai">✖ Thanh toán thất bại</button>
          <button type="button" class="nut nut-vang" data-kq-tt="hetHan">⏱ Hết hạn giữ chỗ tạm</button>
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
        <p>Mã đơn hàng: <strong>#DH${duLieuDon.maDonHang}</strong> · Trạng thái: <strong>${maHoaHtml(duLieuDon.trangThai)}</strong></p>
        <p>Thanh toán: <strong>${maHoaHtml(duLieuDon.trangThaiThanhToan)}</strong> · Tổng thanh toán: <strong style="color:#dc2626;">${dinhDangTien(duLieuDon.tongTien)}</strong></p>
        <div class="hop-tom-tat-nhan-hang" style="text-align:left; max-width:540px; margin:12px auto;">
          <strong>Thông tin người nhận đã lưu trên đơn hàng:</strong><br/>
          Người nhận: <strong>${maHoaHtml(duLieuDon.tenNguoiNhan || '')}</strong> · SĐT: <strong>${maHoaHtml(duLieuDon.sdtNguoiNhan || '')}</strong><br/>
          Địa chỉ nhận: ${maHoaHtml(duLieuDon.diaChiNhan || '')}
        </div>
        <div style="margin-top:16px; display:flex; gap:10px; justify-content:center;">
          <button type="button" class="nut nut-chinh" id="xn-xem-don">Xem đơn hàng của tôi</button>
          <button type="button" class="nut nut-phu" id="xn-tiep-tuc">Tiếp tục mua sắm</button>
        </div>
      </div>
    `);
    document.getElementById('xn-xem-don').onclick = () => moModalHoSo('don-hang');
    document.getElementById('xn-tiep-tuc').onclick = dongModal;
  }

  // ==================== HỒ SƠ, ĐỊA CHỈ & LỊCH SỬ ĐƠN HÀNG ====================
  async function moModalHoSo(tab = 'don-hang') {
    moModal('Tài khoản Khách hàng', `
      <div class="tab-bar">
        <button type="button" class="nut ${tab === 'don-hang' ? 'nut-chinh' : 'nut-phu'}" data-hs-tab="don-hang">Đơn hàng của tôi</button>
        <button type="button" class="nut ${tab === 'ho-so' ? 'nut-chinh' : 'nut-phu'}" data-hs-tab="ho-so">Hồ sơ cá nhân</button>
        <button type="button" class="nut ${tab === 'dia-chi' ? 'nut-chinh' : 'nut-phu'}" data-hs-tab="dia-chi">Sổ địa chỉ giao hàng</button>
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
            <div class="chu-phu">Giao tới: ${maHoaHtml(dh.tenNguoiNhan || dh.nguoiNhan)} (${maHoaHtml(dh.sdtNguoiNhan || dh.soDienThoai)}) — ${maHoaHtml(dh.diaChiNhan || dh.diaChiGiaoHang)}</div>
            <div class="chu-phu">Thanh toán: ${maHoaHtml(dh.phuongThucThanhToan)} (<strong>${maHoaHtml(dh.trangThaiThanhToan)}</strong>)${dh.trangThaiYeuCauHuy ? ` · Yêu cầu hủy: <strong>${maHoaHtml(dh.trangThaiYeuCauHuy)}</strong>` : ''}</div>
            ${dh.trangThai === 'Đã hủy' ? `<div style="color:#dc2626; font-weight:700; margin:6px 0;">Trạng thái: Đã hủy${dh.lyDoHuy ? ` (${maHoaHtml(dh.lyDoHuy)})` : ''}</div>` : `
              <div class="tien-do-don">
                ${CAC_MOC_GIAO_HANG.map((m, i) => `<span class="buoc-don ${i < idxMoc ? 'da-qua' : (i === idxMoc ? 'hien-tai' : '')}">${m}</span>`).join('')}
              </div>
            `}
            <div style="font-size:12.5px; margin:8px 0;">${(dh.chiTiet || []).map(c => `• ${maHoaHtml(c.tenDienThoai)} x${c.soLuong} (${dinhDangTien(c.donGia)})`).join('<br>')}</div>
            <div style="display:flex; gap:8px; margin-top:8px;">
              ${choTuHuy ? `<button type="button" class="nut nut-do nut-nho" data-tu-huy="${dh.maDonHang}">Hủy đơn hàng</button>` : ''}
              ${choYeuCauHuy ? `<button type="button" class="nut nut-vang nut-nho" data-yc-huy="${dh.maDonHang}">Gửi yêu cầu hủy đơn</button>` : ''}
            </div>
          </div>
        `;
      }).join('');

      vung.querySelectorAll('[data-tu-huy]').forEach(b => {
        b.onclick = async () => {
          const lyDo = prompt('Nhập lý do hủy đơn hàng:', 'Thay đổi nhu cầu mua sắm');
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
          <div class="nhom-nhap"><label for="hs-ten">Họ và tên</label><input id="hs-ten" value="${maHoaHtml(hs.hoTen || '')}" required /></div>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label for="hs-sdt">Số điện thoại</label><input id="hs-sdt" value="${maHoaHtml(hs.soDienThoai || '')}" /></div>
            <div class="nhom-nhap"><label for="hs-email">Email</label><input id="hs-email" value="${maHoaHtml(hs.email || '')}" /></div>
          </div>
          <button type="submit" id="btnCapNhatHoSo" class="nut nut-chinh">Lưu thay đổi hồ sơ</button>
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
        <div id="grdDiaChi">
          ${dsDc.map(dc => `
            <div style="border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
              <div>
                <strong>${maHoaHtml(dc.nguoiNhan)}</strong> (${maHoaHtml(dc.soDienThoai)}) ${dc.macDinh ? '<span style="color:#16a34a; font-weight:700;">★ Mặc định</span>' : ''}
                <div class="chu-phu">${maHoaHtml(dc.diaChi)} · ${maHoaHtml(dc.khuVuc)}</div>
              </div>
              <div style="display:flex; gap:6px;">
                ${!dc.macDinh ? `<button type="button" class="nut nut-phu nut-nho" data-md-dc="${dc.maDiaChi}">Đặt mặc định</button>` : ''}
                <button type="button" class="nut nut-do nut-nho" data-xoa-dc="${dc.maDiaChi}">Xóa</button>
              </div>
            </div>
          `).join('')}
        </div>
        <form id="f-dc" style="margin-top:14px; border-top:1px solid #e2e8f0; padding-top:12px;">
          <h4>Thêm địa chỉ giao hàng mới</h4>
          <div class="luoi-2-cot">
            <div class="nhom-nhap"><label for="ndc-ten">Người nhận</label><input id="ndc-ten" required /></div>
            <div class="nhom-nhap"><label for="ndc-sdt">Số điện thoại</label><input id="ndc-sdt" required /></div>
          </div>
          <div class="nhom-nhap"><label for="ndc-dc">Địa chỉ</label><input id="ndc-dc" required /></div>
          <div class="nhom-nhap"><label for="ndc-qt">Khu vực</label><select id="ndc-qt">${dsQt.map(q => `<option value="${q.maQuyTac}">${maHoaHtml(q.khuVuc)}</option>`).join('')}</select></div>
          <button type="submit" id="btnThemDiaChi" class="nut nut-chinh">Thêm địa chỉ</button>
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
      vung.innerHTML = ds.map(tb => `<div style="border-bottom:1px solid #f1f5f9; padding:10px 0;"><div>${maHoaHtml(tb.noiDung)}</div><div class="chu-phu">${tb.thoiDiemTao}</div></div>`).join('') || '<div class="chu-phu">Chưa có thông báo</div>';
    }
  }

  // ==================== SỰ KIỆN KHỞI ĐỘNG ====================
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('form-tim-kiem').onsubmit = (e) => { e.preventDefault(); taiDanhSachSanPham(); };
    document.getElementById('btnLoc').onclick = taiDanhSachSanPham;
    ['cboHang', 'cboRAM', 'cboROM', 'cboPin', 'loc-sap-xep'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.onchange = taiDanhSachSanPham;
    });
    document.getElementById('nut-xoa-loc').onclick = () => {
      document.getElementById('txtTuKhoa').value = '';
      ['cboHang', 'numGiaTu', 'numGiaDen', 'cboRAM', 'cboROM', 'cboPin'].forEach(id => { document.getElementById(id).value = ''; });
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
