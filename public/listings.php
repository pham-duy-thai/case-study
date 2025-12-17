<?php
require __DIR__ . '/../include/db.php';
require __DIR__ . '/../include/rooms.php';

ensureRoomsSchema($conn);

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

$amenitiesOptions = getAmenities($conn);
$areas = $conn->query("SELECT DISTINCT area FROM rooms ORDER BY area ASC")->fetchAll(PDO::FETCH_COLUMN) ?: [];

function retainValue($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES);
}

function isAmenityChecked(int $id, array $selected): bool
{
    return in_array($id, $selected, true);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách phòng trọ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <header class="mb-4 text-center">
        <h1 class="fw-bold text-primary">Danh sách phòng trọ</h1>
        <p class="text-muted">Tìm kiếm theo khu vực, khoảng giá, diện tích và tiện ích kèm theo.</p>
    </header>

    <section class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <strong>Tìm kiếm nâng cao</strong>
        </div>
        <div class="card-body">
            <form class="row g-3 align-items-end" method="get">
                <div class="col-md-4">
                    <label class="form-label text-muted small">Từ khóa</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Khu vực, địa chỉ, số phòng"
                           value="<?= retainValue($filters['keyword']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small">Khu vực</label>
                    <select class="form-select" name="area">
                        <option value="">-- Tất cả --</option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= retainValue($area) ?>"
                                <?= $filters['area'] === $area ? 'selected' : '' ?>>
                                <?= htmlspecialchars($area) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small">Giá tối thiểu (đ)</label>
                    <input type="number" class="form-control" name="price_min" min="0"
                           value="<?= retainValue($filters['price_min']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small">Giá tối đa (đ)</label>
                    <input type="number" class="form-control" name="price_max" min="0"
                           value="<?= retainValue($filters['price_max']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small">Diện tích tối thiểu (m²)</label>
                    <input type="number" step="0.1" class="form-control" name="size_min" min="0"
                           value="<?= retainValue($filters['size_min']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small">Diện tích tối đa (m²)</label>
                    <input type="number" step="0.1" class="form-control" name="size_max" min="0"
                           value="<?= retainValue($filters['size_max']) ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label text-muted small">Tiện ích kèm theo</label>
                    <div class="row g-2">
                        <?php foreach ($amenitiesOptions as $amenity): ?>
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="amenities[]"
                                           value="<?= (int) $amenity['id'] ?>"
                                           id="amenity_filter_<?= (int) $amenity['id'] ?>"
                                        <?= isAmenityChecked((int) $amenity['id'], $filters['amenities']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="amenity_filter_<?= (int) $amenity['id'] ?>">
                                        <?= htmlspecialchars($amenity['name']) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-12 d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary px-4">Tìm kiếm</button>
                    <a class="btn btn-link" href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>">Xóa lọc</a>
                </div>
            </form>
        </div>
    </section>

    <section class="mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Tin đăng phù hợp</h5>
                <p class="text-muted small mb-0">
                    Có <?= $pagination['total'] ?> phòng trọ • Trang <?= $pagination['page'] ?> / <?= $pagination['pages'] ?>
                </p>
            </div>
            <span class="badge bg-success-subtle text-success border">
                Ưu tiên hiển thị tin đăng mới nhất
            </span>
        </div>
    </section>

    <section class="row g-4 mb-5">
        <?php if (!$rooms): ?>
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    Không tìm thấy phòng phù hợp. Vui lòng thử lại với điều kiện khác.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($rooms as $room): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($room['area']) ?></span>
                                <small class="text-muted">Phòng số <?= htmlspecialchars($room['room_number']) ?></small>
                            </div>
                            <h5 class="card-title text-danger mb-2"><?= formatCurrency($room['price']) ?> đ/tháng</h5>
                            <p class="card-text small mb-3 text-muted">
                                Tầng <?= (int) $room['floor'] ?> •
                                <?= number_format((float) $room['size'], 1, ',', '.') ?> m² •
                                Tối đa <?= (int) $room['max_capacity'] ?> người
                            </p>
                            <p class="card-text mb-3"><?= htmlspecialchars($room['address']) ?></p>
                            <?php if (!empty($room['amenities'])): ?>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php foreach ($room['amenities'] as $amenity): ?>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($amenity) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                            <small class="text-muted">Hiện tại: <?= (int) $room['current_occupancy'] ?> người</small>
                            <button class="btn btn-sm btn-outline-primary">Liên hệ tư vấn</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if ($pagination['pages'] > 1): ?>
        <nav aria-label="Pagination" class="mb-5">
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

    <section class="card shadow-sm">
        <div class="card-header bg-white">
            <strong>Thông tin liên hệ</strong>
        </div>
        <div class="card-body">
            <p>Nếu bạn cần hỗ trợ thêm, vui lòng liên hệ:</p>
            <ul class="list-unstyled mb-0">
                <li><strong>Hotline:</strong> 0123 456 789</li>
                <li><strong>Email:</strong> hotro@nhatro62pm3.com</li>
                <li><strong>Địa chỉ:</strong> 101 Đại La, Hai Bà Trưng, Hà Nội</li>
            </ul>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
