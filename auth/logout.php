<?php
session_start();

/* Xóa toàn bộ session */
session_unset();
session_destroy();

/* Quay về trang đăng nhập */
header("Location: /c    ase-studt/auth/login.php");
exit;
