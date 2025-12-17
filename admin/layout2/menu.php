<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<div id="layoutSidenav_nav">
<nav class="sb-sidenav accordion sb-sidenav-dark">
    <div class="sb-sidenav-menu">
        <div class="nav">

            <div class="sb-sidenav-menu-heading">Quản lý</div>

            <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                <i class="fas fa-tachometer-alt me-2"></i> Tổng quan
            </a>

            <a class="nav-link <?= $currentPage === 'posts.php' ? 'active' : '' ?>" href="posts.php">
                <i class="fas fa-door-open me-2"></i> Tin đăng
            </a>

            <a class="nav-link <?= $currentPage === 'user.php' ? 'active' : '' ?>" href="user.php">
                <i class="fas fa-users me-2"></i> Tài khoản
            </a>

        </div>
    </div>

    <div class="sb-sidenav-footer">
        <div class="small">Đăng nhập:</div>
        Admin KTX
    </div>
</nav>
</div>
