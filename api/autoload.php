<?php
// Tự động nạp các lớp Entity và Controller theo mô hình BCE
require_once __DIR__ . '/db.php';

spl_autoload_register(function (string $tenLop): void {
    $duongDanEntity = __DIR__ . '/entities/' . $tenLop . '.php';
    if (is_file($duongDanEntity)) {
        require_once $duongDanEntity;
        return;
    }
    $duongDanCtrl = __DIR__ . '/controllers/' . $tenLop . '.php';
    if (is_file($duongDanCtrl)) {
        require_once $duongDanCtrl;
    }
});
