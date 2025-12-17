<?php
require "../include/auth_check.php";
require "../include/db.php";

$id = $_SESSION['user_id'];
$post_id = (int)($_GET['post_id'] ?? 0);

if ($_POST) {
    $content = trim($_POST['content']);
    if ($content == "") die("Chưa nhập nội dung");

    $stmt = $conn->prepare(
      "INSERT INTO reports(user_id, post_id, content)
       VALUES(?,?,?)"
    );
    $stmt->execute([$id, $post_id, $content]);

    echo "Đã gửi báo cáo";
}
?>

<h3>Báo cáo đã thuê</h3>
<form method="post">
    <textarea name="content" placeholder="Nội dung báo cáo"></textarea><br>
    <button>Gửi báo cáo</button>
</form>
