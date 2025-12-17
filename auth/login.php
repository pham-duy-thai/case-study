<?php
session_start();
require "../include/db.php";
require "../include/config.php";

$error = "";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $captcha  = $_POST['g-recaptcha-response'];

    if ($email == "" || $password == "") {
        $error = "Không được để trống dữ liệu";
    } elseif (!$captcha) {
        $error = "Vui lòng xác nhận Captcha";
    } else {

        /* Check captcha */
        $verify = file_get_contents(
            "https://www.google.com/recaptcha/api/siteverify?secret="
            . RECAPTCHA_SECRET_KEY . "&response=" . $captcha
        );
        $response = json_decode($verify);

        if (!$response->success) {
            $error = "Captcha không hợp lệ";
        } else {

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
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_name'] = $user['role_name'];

                /* Điều hướng theo role */
                if ($user['role_name'] === 'admin') {
                    header("Location: ../admin/index.php");
                } else {
                    header("Location: ../index.php");
                }
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<h2>log in</h2>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Mật khẩu" required><br><br>

    <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div><br>

    <button name="login">Đăng nhập</button>
</form>

<p style="color:red"><?= $error ?></p>
<a href="register.php">Đăng ký</a>

</body>
</html>
