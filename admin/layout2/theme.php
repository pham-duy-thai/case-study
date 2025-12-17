<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?= $title ?? 'Dashboard - Quản lý KTX' ?></title>

    <link rel="stylesheet" href="/case-study/public/css/styles.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css">

    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>
</head>

<body class="sb-nav-fixed">

<?php include 'nav.php'; ?>

<div id="layoutSidenav">
    <?php include 'menu.php'; ?>

    <div id="layoutSidenav_content">
        <main class="p-4">
            <?= $content ?>
        </main>

        <?php include 'footer.php'; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/case-study/public/js/scripts.js"></script>
</body>
</html>
