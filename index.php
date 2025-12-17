<?php
require "include/db.php";
session_start();

/* PHÂN TRANG */
$limit = 6;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* DANH SÁCH PHÒNG */
$posts = $conn->query("
SELECT p.*, l.address
FROM posts p
JOIN locations l ON p.location_id = l.id
WHERE p.status='active'
ORDER BY p.created_at DESC
LIMIT $limit OFFSET $offset
");

$total = $conn->query("
SELECT COUNT(*) FROM posts WHERE status='active'
")->fetchColumn();
$total_page = ceil($total / $limit);

/* PHÒNG TRỌ MỚI */
$new_posts = $conn->query("
SELECT p.id,p.title,p.price,p.area,p.views,
       l.address,
       (SELECT image_url FROM post_images WHERE post_id=p.id LIMIT 1) AS image
FROM posts p
JOIN locations l ON p.location_id=l.id
WHERE p.status='active'
ORDER BY p.created_at DESC
LIMIT 4
");

/* SIDEBAR */
$latest = $conn->query("
SELECT id,title FROM posts
WHERE status='active'
ORDER BY created_at DESC
LIMIT 5
");

$hot_posts = $conn->query("
SELECT id,title,views FROM posts
WHERE status='active'
ORDER BY views DESC
LIMIT 5
");

$near_uni = $conn->query("
SELECT p.id,p.title FROM posts p
JOIN locations l ON p.location_id=l.id
WHERE l.address LIKE '%ĐH Vinh%'
LIMIT 5
");

$title = "Trang chủ";
include "include/layout.php";
?>
<div class="card mb-4">
  <div class="card-body">
    <form method="get" class="row g-2">

      <div class="col-md-2">
        <input type="number" name="min_price" class="form-control" placeholder="Giá từ">
      </div>

      <div class="col-md-2">
        <input type="number" name="max_price" class="form-control" placeholder="Giá đến">
      </div>

      <div class="col-md-2">
        <input type="number" name="area" class="form-control" placeholder="Diện tích ≥">
      </div>

      <div class="col-md-3">
        <input type="text" name="address" class="form-control" placeholder="Địa điểm">
      </div>

      <div class="col-md-3">
        <?php
        $amenities = $conn->query("SELECT * FROM amenities");
        while ($a = $amenities->fetch()):
        ?>
          <label class="me-2">
            <input type="checkbox" name="utilities[]" value="<?= $a['id'] ?>">
            <?= $a['name'] ?>
          </label>
        <?php endwhile; ?>
      </div>

      <div class="col-12">
        <button class="btn btn-primary w-100">Tìm kiếm</button>
      </div>

    </form>
  </div>
</div>


<?php
$images = [
  'assets/img/anh1.jpg',
  'assets/img/anh2.jpg',
  'assets/img/anh3.jpg'

];
$i = 0;
?>

<h4 class="mb-3">PHÒNG TRỌ MỚI ĐĂNG NHẤT</h4>
<div class="row">
<?php while($p = $new_posts->fetch()): ?>
  <div class="col-md-6 mb-3">
    <div class="d-flex border rounded p-2 h-100">

      <img src="<?= $images[$i % count($images)] ?>"
           width="120" height="90"
           style="object-fit:cover"
           class="me-3 rounded">

      <div>
        <h6 class="text-warning"><?= $p['title'] ?></h6>
        <div class="small">📐 <?= $p['area'] ?> m² • 👁 <?= $p['views'] ?></div>
        <div class="small">📍 <?= $p['address'] ?></div>
        <div class="text-danger fw-bold"><?= number_format($p['price']) ?> đ</div>
      </div>

    </div>
  </div>
<?php $i++; endwhile; ?>
</div>


</div>
<h4 class="mb-3">Danh sách phòng trọ</h4>
<div class="row">
<?php while($p=$posts->fetch()): ?>
  <div class="col-md-4 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h5><?= $p['title'] ?></h5>
        <p class="text-danger fw-bold"><?= number_format($p['price']) ?> VNĐ</p>
        <p>📐 <?= $p['area'] ?> m²</p>
        <p>📍 <?= $p['address'] ?></p>
      </div>
    </div>
  </div>
<?php endwhile; ?>
</div>
<div class="row">
  <div class="col-md-4">
    <h5>Tin mới nhất</h5>
    <?php while($n=$latest->fetch()): ?>
      <p>• <?= $n['title'] ?></p>
    <?php endwhile; ?>
  </div>

  <div class="col-md-4">
    <h5>Xem nhiều nhất</h5>
    <?php while($h=$hot_posts->fetch()): ?>
      <p>• <?= $h['title'] ?> (<?= $h['views'] ?>)</p>
    <?php endwhile; ?>
  </div>

  <div class="col-md-4">
    <h5>Gần ĐH Vinh</h5>
    <?php while($u=$near_uni->fetch()): ?>
      <p>• <?= $u['title'] ?></p>
    <?php endwhile; ?>
  </div>
</div>
