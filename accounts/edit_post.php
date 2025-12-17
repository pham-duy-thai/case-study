<?php
require "../include/auth_check.php";
require "../include/db.php";

$id = $_SESSION['user_id'];
$post_id = (int)$_GET['id'];

$stmt = $conn->prepare(
  "SELECT * FROM posts WHERE id=? AND user_id=?"
);
$stmt->execute([$post_id, $id]);
$post = $stmt->fetch();

if ($_POST) {
    $title = $_POST['title'];
    $price = $_POST['price'];

    $conn->prepare(
      "UPDATE posts SET title=?, price=? WHERE id=? AND user_id=?"
    )->execute([$title, $price, $post_id, $id]);

    header("Location: my_posts.php");
}
?>

<form method="post">
    <input name="title" value="<?= $post['title'] ?>"><br>
    <input name="price" value="<?= $post['price'] ?>"><br>
    <button>Cập nhật</button>
</form>
