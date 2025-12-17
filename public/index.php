<?php
require __DIR__ . '/../include/db.php';
require __DIR__ . '/../include/rooms.php';
require __DIR__ . '/../include/admin_helpers.php';

ensureRoomsSchema($conn);
ensurePostsSchema($conn);

$filters = [
    'keyword' => trim($_GET['keyword'] ?? ''),
    'area' => trim($_GET['area'] ?? ''),
    'price_min' => (int) ($_GET['price_min'] ?? 0),
    'price_max' => (int) ($_GET['price_max'] ?? 0),
    'size_min' => (float) ($_GET['size_min'] ?? 0),
    'size_max' => (float) ($_GET['size_max'] ?? 0),
    'amenities' => array_values(array_unique(array_map('intval', $_GET['amenities'] ?? []))),
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

$searchResult = searchRooms($conn, $filters, $perPage, $page);
$rooms = $searchResult['data'];
$pagination = $searchResult['pagination'];

$amenityOptions = getAmenities($conn);
$areas = $conn->query("SELECT DISTINCT area FROM rooms ORDER BY area ASC")->fetchAll(PDO::FETCH_COLUMN) ?: [];

function retain($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES);
}

function isAmenityChecked(int $id, array $selected): bool
{
    return in_array($id, $selected, true);
}

function getSampleImages(): array
{
    static $images = null;
    if ($images === null) {
        $images = [
            '/case-study/public/assets/img/images1.jpg',
            '/case-study/public/assets/img/images2.jpg',
            '/case-study/public/assets/img/images3.jpg',
            '/case-study/public/assets/img/images4.jpg',
            '/case-study/public/assets/img/images5.jpg',
            '/case-study/public/assets/img/images6.jpg',
            '/case-study/public/assets/img/images7.jpg',
        ];
    }
    return $images;
}

function getImageByIndex(int $index): string
{
    $images = getSampleImages();
    if (!$images) {
        return 'https://placehold.co/600x400';
    }
    return $images[$index % count($images)];
}

function fetchPosts(PDO $conn, string $orderBy, string $extraWhere = '', array $params = [], int $limit = 4): array
{
    $sql = "
        SELECT posts.*, users.full_name AS author_name
        FROM posts
        JOIN users ON users.id = posts.user_id
        WHERE posts.status = 'published' AND posts.is_hidden = 0
        $extraWhere
        ORDER BY $orderBy
        LIMIT :limit
    ";
    $stmt = $conn->prepare($sql);
    foreach ($params as $name => $value) {
        $stmt->bindValue($name, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$newestPosts = fetchPosts($conn, 'posts.created_at DESC');
$popularPosts = fetchPosts($conn, 'posts.views DESC');
$vinhPosts = fetchPosts(
    $conn,
    'posts.created_at DESC',
    "AND (posts.area LIKE :vinh OR posts.address LIKE :vinh)",
    [':vinh' => '%Vinh%']
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhà trọ 62PM3 - Trang chủ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body {background-color: #f5f7fb;}
        .hero {
            background: url('/case-study/public/assets/img/images1.jpg') center/cover no-repeat;
            border-radius: 20px;
            color: #fff;
            position: relative;
            overflow: hidden;
            min-height: 280px;
        }
        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(8, 28, 73, 0.6);
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        .highlight-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
        }
        .room-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
        }
        .room-card .badge {
            font-size: 0.75rem;
        }
        .amenity-tag {
            border: 1px solid #e0e7ff;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            font-size: 0.75rem;
            margin-right: 0.35rem;
        }
        .info-block h6 {
            font-weight: 600;
            color: #0f172a;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <section class="hero mb-5 shadow position-relative">
        <div class="hero-content p-5">
            <p class="text-uppercase small mb-2">Nhà trọ 62PM3</p>
            <h1 class="display-5 fw-bold mb-3">Tìm phòng trọ phù hợp ngay hôm nay</h1>
            <p class="lead mb-0">Lọc theo khu vực, giá, diện tích và tiện ích kèm theo. Xem tin mới nhất, tin được xem nhiều và các phòng gần Đại học Vinh.</p>
        </div>
        <div class="position-absolute top-0 end-0 p-4 hero-content">
            <a href="/case-study/auth/login.php" class="btn btn-outline-light me-2">Đăng nhập</a>
            <a href="/case-study/auth/register.php" class="btn btn-warning text-dark">Đăng ký ngay</a>
        </div>
    </section>

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-semibold">Phòng trọ mới đăng nhất</h3>
            <span class="badge bg-primary-subtle text-primary border">Cập nhật hàng ngày</span>
        </div>
        <div class="row g-4">
            <?php foreach ($newestPosts ?: [] as $index => $post): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm h-100 highlight-card">
                        <img src="<?= getImageByIndex($index) ?>" alt="Hình phòng">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-1"><?= htmlspecialchars($post['title']) ?></h6>
                            <p class="text-muted small mb-1"><?= htmlspecialchars($post['area']) ?></p>
                            <p class="fw-semibold text-danger mb-1"><?= formatCurrency($post['price']) ?> đ</p>
                            <small class="text-muted">Đăng bởi <?= htmlspecialchars($post['author_name']) ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$newestPosts): ?>
                <div class="col-12">
                    <div class="alert alert-info mb-0">Chưa có tin đăng nào để hiển thị.</div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card shadow-sm mb-5">
        <div class="card-header bg-white">
            <strong>Tìm kiếm phòng trọ</strong>
        </div>
        <div class="card-body">
            <form class="row g-3 align-items-end" method="get">
                <div class="col-lg-4">
                    <label class="form-label text-muted small">Từ khóa</label>
                    <input class="form-control" name="keyword" placeholder="Ví dụ: Thanh Xuân, Đại La..."
                           value="<?= retain($filters['keyword']) ?>">
                </div>
                <div class="col-lg-3">
                    <label class="form-label text-muted small">Khu vực</label>
                    <select class="form-select" name="area">
                        <option value="">Tất cả</option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= retain($area) ?>" <?= $filters['area'] === $area ? 'selected' : '' ?>>
                                <?= htmlspecialchars($area) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label text-muted small">Giá tối thiểu</label>
                    <input type="number" class="form-control" name="price_min" min="0"
                           value="<?= retain((string) $filters['price_min']) ?>">
                </div>
                <div class="col-lg-2">
                    <label class="form-label text-muted small">Giá tối đa</label>
                    <input type="number" class="form-control" name="price_max" min="0"
                           value="<?= retain((string) $filters['price_max']) ?>">
                </div>
                <div class="col-lg-2">
                    <label class="form-label text-muted small">Diện tích tối thiểu</label>
                    <input type="number" step="0.1" class="form-control" name="size_min" min="0"
                           value="<?= retain((string) $filters['size_min']) ?>">
                </div>
                <div class="col-lg-2">
                    <label class="form-label text-muted small">Diện tích tối đa</label>
                    <input type="number" step="0.1" class="form-control" name="size_max" min="0"
                           value="<?= retain((string) $filters['size_max']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label text-muted small">Tiện ích kèm theo</label>
                    <div class="row g-2">
                        <?php foreach ($amenityOptions as $amenity): ?>
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="amenities[]"
                                           value="<?= (int) $amenity['id'] ?>"
                                        <?= isAmenityChecked((int) $amenity['id'], $filters['amenities']) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars($amenity['name']) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-between">
                    <button class="btn btn-primary px-4" type="submit">Tìm kiếm</button>
                    <a class="btn btn-link" href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>">Xóa lọc</a>
                </div>
            </form>
        </div>
    </section>

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-semibold">Danh sách phòng trọ</h3>
            <span class="text-muted small">Trang <?= $pagination['page'] ?> / <?= $pagination['pages'] ?></span>
        </div>
        <div class="row g-4">
            <?php if (!$rooms): ?>
                <div class="col-12">
                    <div class="alert alert-info">Không tìm thấy phòng trọ phù hợp.</div>
                </div>
            <?php else: ?>
                <?php foreach ($rooms as $index => $room): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card room-card shadow-sm h-100">
                            <img src="<?= getImageByIndex($index + 3) ?>" alt="Phòng trọ">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary-subtle text-primary border">
                                        <?= htmlspecialchars($room['area']) ?>
                                    </span>
                                    <span class="text-muted small">Số phòng <?= htmlspecialchars($room['room_number']) ?></span>
                                </div>
                                <h5 class="fw-semibold mb-2"><?= formatCurrency($room['price']) ?> đ/tháng</h5>
                                <p class="text-muted small mb-1">Tầng <?= (int) $room['floor'] ?> • diện tích <?= number_format((float) $room['size'], 1, ',', '.') ?> m²</p>
                                <p class="text-muted small mb-3">Địa chỉ: <?= htmlspecialchars($room['address']) ?></p>
                                <div class="mb-3">
                                    <span class="amenity-tag">Tối đa <?= (int) $room['max_capacity'] ?> người</span>
                                    <span class="amenity-tag">Hiện tại <?= (int) $room['current_occupancy'] ?></span>
                                </div>
                                <a href="room.php?id=<?= $room['id'] ?>" class="btn btn-outline-primary btn-sm">Xem chi tiết</a>
                                <a href="/case-study/auth/login.php" class="btn btn-link btn-sm text-decoration-none">Liên hệ đặt phòng</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($pagination['pages'] > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                        <?php
                        $query = $_GET;
                        $query['page'] = $i;
                        $url = '?' . http_build_query($query);
                        ?>
                        <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $url ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </section>

    <section class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Tin đăng xem nhiều</div>
                <div class="card-body">
                    <?php foreach ($popularPosts ?: [] as $index => $post): ?>
                        <div class="d-flex gap-3 mb-3">
                            <img src="<?= getImageByIndex($index + 5) ?>" alt="Tin nổi bật" width="80" height="80" style="object-fit:cover;border-radius:10px;">
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($post['title']) ?></h6>
                                <p class="text-muted small mb-1">Lượt xem: <?= (int) $post['views'] ?></p>
                                <small class="text-muted"><?= htmlspecialchars($post['address']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$popularPosts): ?>
                        <p class="text-muted small mb-0">Chưa có dữ liệu.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Tin gần Đại học Vinh</div>
                <div class="card-body">
                    <?php foreach ($vinhPosts ?: [] as $index => $post): ?>
                        <div class="d-flex gap-3 mb-3">
                            <img src="<?= getImageByIndex($index + 7) ?>" alt="Tin gần ĐH Vinh" width="80" height="80" style="object-fit:cover;border-radius:10px;">
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($post['title']) ?></h6>
                                <p class="text-muted small mb-1"><?= htmlspecialchars($post['area']) ?></p>
                                <small class="text-danger fw-semibold"><?= formatCurrency($post['price']) ?> đ</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$vinhPosts): ?>
                        <p class="text-muted small mb-0">Không tìm thấy tin phù hợp.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Thông tin liên hệ</div>
                <div class="card-body">
                    <div class="info-block mb-3">
                        <h6>Hotline</h6>
                        <p class="mb-0">0123 456 789</p>
                    </div>
                    <div class="info-block mb-3">
                        <h6>Email hỗ trợ</h6>
                        <p class="mb-0">hotro@nhatro62pm3.com</p>
                    </div>
                    <div class="info-block">
                        <h6>Địa chỉ</h6>
                        <p class="mb-0">101 Đại La, Hai Bà Trưng, Hà Nội</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
