<?php
require "../include/auth_check.php";
require "../include/db.php";

if ($_POST) {
    $id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $price = $_POST['price'];

    $stmt = $conn->prepare(
      "INSERT INTO posts(user_id,title,price,status)
       VALUES(?,?,?, 'pending')"
    );
    $stmt->execute([$id, $title, $price]);

    header("Location: ../accounts/my_posts.php");
}
?>

<form method="post">
    <input name="title" placeholder="Tiêu đề"><br>
    <input name="price" placeholder="Giá"><br>
    <button>Đăng tin</button>
</form>
