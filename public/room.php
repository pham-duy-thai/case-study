<?php
require __DIR__ . '/../include/db.php';
require __DIR__ . '/../include/rooms.php';
require __DIR__ . '/../include/admin_helpers.php';

ensureRoomsSchema($conn);
ensurePostsSchema($conn);

$roomId = (int) ($_GET['id'] ?? 0);
$room = $roomId > 0 ? getRoomById($conn, $roomId) : null;

function getImageByRoom(int $roomId): string
{
    $images = [
        '/case-study/public/assets/img/images1.jpg',
        '/case-study/public/assets/img/images2.jpg',
        '/case-study/public/assets/img/images3.jpg',
        '/case-study/public/assets/img/images4.jpg',
        '/case-study/public/assets/img/images5.jpg',
        '/case-study/public/assets/img/images6.jpg',
        '/case-study/public/assets/img/images7.jpg',
    ];
    if (!$images) {
        return 'https://placehold.co/800x450';
    }
    return $images[$roomId % count($images)];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết phòng trọ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body {background-color: #f5f7fb;}
        .room-cover {
            width: 100%;
            height: 320px;
            object-fit: cover;
            border-radius: 1.25rem;
        }
        .info-card {
            border-radius: 1rem;
            border: 1px solid #e3e8f0;
        }
        .info-card .tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            border: 1px solid #e0e7ff;
            margin-right: 0.35rem;
            margin-bottom: 0.35rem;
        }
        .breadcrumb {
            background: none;
            padding: 0;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chi tiết phòng</li>
        </ol>
    </nav>

    <?php if (!$room): ?>
        <div class="alert alert-warning">Không tìm thấy phòng trọ. <a href="index.php">Quay lại danh sách</a>.</div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <img class="room-cover mb-3" src="<?= getImageByRoom($room['id']) ?>" alt="Phòng trọ">
                <h2 class="fw-bold mb-2">Phòng <?= htmlspecialchars($room['room_number']) ?> - <?= htmlspecialchars($room['area']) ?></h2>
                <p class="text-muted mb-3"><?= htmlspecialchars($room['address']) ?></p>
                <p class="lead text-danger fw-semibold mb-4"><?= formatCurrency($room['price']) ?> đ/tháng</p>
                <div class="card info-card mb-4">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3">Mô tả phòng</h5>
                        <p>Phòng rộng <?= number_format((float) $room['size'], 1, ',', '.') ?> m², tầng <?= (int) $room['floor'] ?>, phù hợp tối đa <?= (int) $room['max_capacity'] ?> người. Hiện tại đang có <?= (int) $room['current_occupancy'] ?> người thuê.</p>
                        <div class="d-flex flex-wrap">
                            <span class="tag">Tầng <?= (int) $room['floor'] ?></span>
                            <span class="tag">Diện tích <?= number_format((float) $room['size'], 1, ',', '.') ?> m²</span>
                            <span class="tag">Tối đa <?= (int) $room['max_capacity'] ?> người</span>
                        </div>
                    </div>
                </div>
                <div class="card info-card mb-4">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3">Tiện ích kèm theo</h5>
                        <?php if (empty($room['amenities'])): ?>
                            <p class="text-muted mb-0">Chưa cập nhật tiện ích.</p>
                        <?php else: ?>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($room['amenities'] as $amenity): ?>
                                    <span class="tag"><?= htmlspecialchars($amenity['name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card info-card mb-4">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3">Thông tin người đăng</h5>
                        <p class="mb-1"><strong>Liên hệ:</strong> 0123 456 789</p>
                        <p class="mb-1"><strong>Email:</strong> lienhe@nhatro62pm3.com</p>
                        <p class="mb-1"><strong>Địa điểm:</strong> <?= htmlspecialchars($room['area']) ?></p>
                        <p class="mb-3"><strong>Trạng thái:</strong> <?= $room['current_occupancy'] >= $room['max_capacity'] ? 'Đã đầy' : 'Còn chỗ' ?></p>
                        <div class="d-grid gap-2">
                            <a href="tel:0123456789" class="btn btn-primary">Gọi ngay</a>
                            <a href="mailto:lienhe@nhatro62pm3.com" class="btn btn-outline-secondary">Gửi email</a>
                            <a href="/case-study/auth/register.php" class="btn btn-warning text-dark">Đăng ký thuê phòng</a>
                        </div>
                    </div>
                </div>
                <div class="card info-card">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3">Báo cáo</h5>
                        <p>Nếu bạn phát hiện tin đăng sai thông tin, vui lòng báo cáo để chúng tôi xử lý kịp thời.</p>
                        <button class="btn btn-warning w-100">Gửi báo cáo</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
