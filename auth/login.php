<?php
session_start();
require "../include/db.php";

$error = "";

if (isset($_POST['login'])) {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  $sql = "
        SELECT users.*, roles.name AS role_name
        FROM users
        JOIN roles ON users.role_id = roles.id
        WHERE email = ? AND status = 1
    ";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user || !password_verify($password, $user['password'])) {
    $error = "Sai email hoặc mật khẩu";
  } else {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['full_name'] = $user['full_name'];

    header("Location: ../index.php");
    exit;
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Đăng nhập</title>
</head>

<body>

  <h2>ĐĂNG NHẬP</h2>

  <form method="POST">
    <input type="email" name="email" required placeholder="Email"><br><br>
    <input type="password" name="password" required placeholder="Mật khẩu"><br><br>
    <button name="login">Đăng nhập</button>
  </form>

  <p style="color:red"><?php echo $error; ?></p>

  <a href="register.php">Đăng ký</a>

</body>

</html>