<?php
require "../include/auth_check.php";
require "../include/db.php";

$id = $_SESSION['user_id'];
$posts = $conn->prepare("SELECT * FROM posts WHERE user_id=?");
$posts->execute([$id]);
?>

<h3>Tin đăng của tôi</h3>
<a href="add_post.php">+ Thêm tin</a>

<?php foreach ($posts as $p): ?>
<hr>
<b><?= $p['title'] ?></b> | <?= number_format($p['price']) ?> |
<?= $p['status'] ?><br>
<a href="edit_post.php?id=<?= $p['id'] ?>">Sửa</a> |
<a href="delete_post.php?id=<?= $p['id'] ?>">Xóa</a> |
<a href="report.php?post_id=<?= $p['id'] ?>">Báo cáo đã thuê</a>
<?php endforeach; ?>
