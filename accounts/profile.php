<?php
require "../include/auth_check.php";
require "../include/db.php";

$id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch();
?>

<h3>Thông tin tài khoản</h3>

<form method="post" action="update_profile.php">
    <input name="full_name" value="<?= $user['full_name'] ?>" placeholder="Họ tên"><br>
    <input name="phone" value="<?= $user['phone'] ?>" placeholder="SĐT"><br>
    <input name="address" value="<?= $user['address'] ?>" placeholder="Địa chỉ"><br>
    <button>Cập nhật</button>
</form>

<hr>

<img src="/case-study/uploads/avatar/<?= $user['avatar'] ?? 'default.png' ?>" width="120"><br>
<form method="post" action="upload_avatar.php" enctype="multipart/form-data">
    <input type="file" name="avatar">
    <button>Đổi ảnh đại diện</button>
</form>

<hr>
<a href="change_password.php">Đổi mật khẩu</a> |
<a href="my_posts.php">Quản lý tin đăng</a>
