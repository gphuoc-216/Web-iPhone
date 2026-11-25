// custom.js — Áp dụng tiêu đề trang từ config.js cho môi trường Localhost
(function () {
  const cauHinh = window.PROJECT_CONFIG || {};
  if (cauHinh.siteTitle) {
    document.title = cauHinh.siteTitle;
  }
})();
