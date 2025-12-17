<?php
require "../include/auth_check.php";
require "../include/db.php";

$id = $_SESSION['user_id'];
$full_name = trim($_POST['full_name']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);

if ($full_name == "" || $phone == "") {
    die("Không được để trống");
}

$stmt = $conn->prepare(
  "UPDATE users SET full_name=?, phone=?, address=? WHERE id=?"
);
$stmt->execute([$full_name, $phone, $address, $id]);

header("Location: profile.php");
