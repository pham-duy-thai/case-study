<?php
require "include/auth_check.php";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
</head>
<body>

<h2>TRANG QUẢN TRỊ</h2>

<p>Xin chào: <b><?php echo $_SESSION['full_name']; ?></b></p>
<p>Quyền: <b><?php echo $_SESSION['role_name']; ?></b></p>

<ul>
    <li><a href="../index.php">Trang người dùng</a></li>
    <li><a href="../auth/logout.php">Đăng xuất</a></li>
</ul>

</body>
</html>
