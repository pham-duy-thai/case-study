<?php
require "auth_check.php";
$title = "Admin Panel";
include "../include/layout.php";
?>

<div class="row">
  <div class="col-md-3">
    <div class="list-group">
      <a href="index.php" class="list-group-item active">Dashboard</a>
      <a href="users.php" class="list-group-item">Quản lý tài khoản</a>
      <a href="posts.php" class="list-group-item">Quản lý tin đăng</a>
    </div>
  </div>

  <div class="col-md-9">
    <div class="card">
      <div class="card-body">
        <h4>Xin chào Admin</h4>
        <p>Hệ thống quản lý phòng trọ</p>
      </div>
    </div>
  </div>
</div>

<?php include "../include/footer.php"; ?>
