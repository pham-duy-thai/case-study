<?php
require "../include/db.php";
require "../include/config.php";

$error = "";

/* Hàm check captcha bằng cURL */
function verifyCaptcha($captcha) {
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $captcha
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    curl_close($ch);

    if (!$result) return false;

    $response = json_decode($result, true);
    return $response['success'] ?? false;
}

if (isset($_POST['register'])) {
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $repassword = $_POST['repassword'];
    $captcha    = $_POST['g-recaptcha-response'] ?? "";

    /* 1. Validate dữ liệu */
    if ($full_name === "" || $email === "" || $password === "") {
        $error = "Không được để trống dữ liệu";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu tối thiểu 6 ký tự";
    } elseif ($password !== $repassword) {
        $error = "Mật khẩu nhập lại không khớp";
    } elseif (!$captcha) {
        $error = "Vui lòng xác nhận Captcha";
    } elseif (!verifyCaptcha($captcha)) {
        $error = "Captcha không hợp lệ";
    } else {

        /* 2. Check email tồn tại */
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $error = "Email đã tồn tại";
        } else {

            /* 3. Insert user */
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (role_id, full_name, email, password)
                    VALUES (2, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$full_name, $email, $hash]);

            /* redirect ĐÚNG */
            header("Location: /case-study/auth/login.php");
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<h2>ĐĂNG KÝ</h2>

<form method="POST">
    <input type="text" name="full_name" placeholder="Họ tên" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Mật khẩu" required><br><br>
    <input type="password" name="repassword" placeholder="Nhập lại mật khẩu" required><br><br>

    <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div><br>

    <button name="register">Đăng ký</button>
</form>

<p style="color:red"><?= $error ?></p>
<a href="/case-study/auth/login.php">Đăng nhập</a>

</body>
</html>
