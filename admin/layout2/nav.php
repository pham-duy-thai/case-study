<?php $currentUser = $_SESSION['full_name'] ?? 'Admin'; ?>
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">Quản lý Ký túc xá</a>

    <button class="btn btn-link btn-sm me-3" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <form class="d-none d-md-inline-block ms-auto me-3">
        <input class="form-control" placeholder="Tìm kiếm...">
    </form>

    <ul class="navbar-nav ms-auto me-3">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
               data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user me-2"></i> <?= htmlspecialchars($currentUser) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="/case-study/auth/logout.php">Đăng xuất</a></li>
            </ul>
        </li>
    </ul>
</nav>
