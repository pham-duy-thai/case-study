<?php
require "../include/auth_check.php";
require "../include/db.php";

$id = $_SESSION['user_id'];
$post_id = (int)$_GET['id'];

$conn->prepare(
  "UPDATE posts SET status='hidden' WHERE id=? AND user_id=?"
)->execute([$post_id, $id]);

header("Location: my_posts.php");
