<?php
require "../include/auth_check.php";
require "../include/db.php";

$id = $_SESSION['user_id'];
$error = $success = "";

if ($_POST) {
    $old = $_POST['old'];
    $new = $_POST['new'];
    $confirm = $_POST['confirm'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!password_verify($old, $user['password'])) {
        $error = "Sai mật khẩu cũ";
    } elseif ($new !== $confirm || strlen($new) < 6) {
        $error = "Mật khẩu mới không hợp lệ";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $conn->prepare(
          "UPDATE users SET password=? WHERE id=?"
        )->execute([$hash, $id]);
        $success = "Đổi mật khẩu thành công";
    }
}
?>

<h3>Đổi mật khẩu</h3>

<form method="post">
    <input type="password" name="old" placeholder="Mật khẩu cũ"><br>
    <input type="password" name="new" placeholder="Mật khẩu mới"><br>
    <input type="password" name="confirm" placeholder="Xác nhận"><br>
    <button>Đổi mật khẩu</button>
</form>

<p style="color:red"><?= $error ?></p>
<p style="color:green"><?= $success ?></p>
