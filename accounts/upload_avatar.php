<?php
require "../include/auth_check.php";
require "../include/db.php";

$id = $_SESSION['user_id'];
$file = $_FILES['avatar'];

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg','jpeg','png'])) {
    die("File không hợp lệ");
}

$name = time() . "_" . $id . "." . $ext;
move_uploaded_file($file['tmp_name'], "../uploads/avatar/" . $name);

$stmt = $conn->prepare("UPDATE users SET avatar=? WHERE id=?");
$stmt->execute([$name, $id]);

header("Location: profile.php");
