<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Case Study' ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/case-study/index.php">PHÒNG TRỌ</a>
    <div>
      <?php if (isset($_SESSION['user_id'])): ?>
        <span class="text-white me-2"><?= $_SESSION['full_name'] ?></span>
        <a href="/case-study/auth/logout.php" class="btn btn-outline-light btn-sm">Đăng xuất</a>
      <?php else: ?>
        <a href="/case-study/auth/login.php" class="btn btn-outline-light btn-sm">Đăng nhập</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container mt-4">
