<?php
session_start();

/* Chưa đăng nhập */
if (!isset($_SESSION['user_id'])) {
    header("Location: /CASE-STUDY/auth/login.php");
    exit;
}

/* Không phải admin */
if ($_SESSION['role_name'] !== 'admin') {
    die("Bạn không có quyền truy cập trang này");
}
