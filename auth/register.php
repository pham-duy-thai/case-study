<?php
require "../include/db.php";
$error = "";

if (isset($_POST['register'])) {
  $full_name = trim($_POST['full_name']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $repassword = $_POST['repassword'];

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email không hợp lệ";
  } elseif ($password !== $repassword) {
    $error = "Mật khẩu không khớp";
  } elseif (strlen($password) < 6) {
    $error = "Mật khẩu tối thiểu 6 ký tự";
  } else {
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
      $error = "Email đã tồn tại";
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);

      $sql = "
                INSERT INTO users (role_id, full_name, email, password)
                VALUES (2, ?, ?, ?)
            ";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$full_name, $email, $hash]);

      header("Location: login.php");
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Đăng ký</title>
</head>

<body>

  <h2>ĐĂNG KÝ</h2>

  <form method="POST">
    <input type="text" name="full_name" required placeholder="Họ tên"><br><br>
    <input type="email" name="email" required placeholder="Email"><br><br>
    <input type="password" name="password" required placeholder="Mật khẩu"><br><br>
    <input type="password" name="repassword" required placeholder="Nhập lại mật khẩu"><br><br>
    <button name="register">Đăng ký</button>
  </form>

  <p style="color:red"><?php echo $error; ?></p>

</body>

</html>