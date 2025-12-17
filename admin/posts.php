
ini_set('display_errors', 0);
error_reporting(E_ALL);

<?php
require __DIR__ . "/auth_check.php";
require __DIR__ . "/../include/db.php";
require __DIR__ . '/../include/admin_helpers.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

$title = "Quản lý tin đăng";
$fullName = htmlspecialchars($_SESSION['full_name'] ?? 'Admin');

ensurePostsSchema($conn);

$errors = [];
$successMessage = '';
$statusOptions = [
    'draft' => 'Nháp',
    'published' => 'Đăng công khai',
    'archived' => 'Đã lưu trữ',
];

$authorOptions = getActiveUsers($conn);
$authorMap = [];
foreach ($authorOptions as $author) {
    $authorMap[$author['id']] = $author['full_name'] . ' (' . $author['email'] . ')';
}

$formState = [
    'add' => ['values' => []],
    'edit' => ['values' => []],
];

function normalizePriceValue($value): int
{
    $digits = preg_replace('/[^\d]/', '', (string) $value);
    return $digits === '' ? 0 : (int) $digits;
}

function normalizeSizeValue($value): float
{
    $value = str_replace(',', '.', (string) $value);
    return is_numeric($value) ? round((float) $value, 2) : 0;
}

function validatePostPayload(array $data, array $statusOptions, array $authorMap): array
{
    $messages = [];
    if ($data['title'] === '') {
        $messages[] = "Tiêu đề không được để trống.";
    }
    if (!$data['user_id'] || !isset($authorMap[$data['user_id']])) {
        $messages[] = "Vui lòng chọn người đăng.";
    }
    if (!isset($statusOptions[$data['status']])) {
        $messages[] = "Trạng thái không hợp lệ.";
    }
    return $messages;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_post' || $action === 'update_post') {
        $payload = [
            'title' => trim($_POST['title'] ?? ''),
            'user_id' => (int) ($_POST['user_id'] ?? 0),
            'area' => trim($_POST['area'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'price' => normalizePriceValue($_POST['price'] ?? 0),
            'size' => normalizeSizeValue($_POST['size'] ?? 0),
            'status' => $_POST['status'] ?? 'draft',
            'is_hidden' => isset($_POST['is_hidden']) ? 1 : 0,
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
        ];

        $formKey = $action === 'create_post' ? 'add' : 'edit';
        $formState[$formKey]['values'] = $payload;

        $validation = validatePostPayload($payload, $statusOptions, $authorMap);

        if ($validation) {
            $errors = array_merge($errors, $validation);
        } else {
            if ($action === 'create_post') {
                $stmt = $conn->prepare(
                    "INSERT INTO posts (user_id, title, area, address, price, size, status, is_hidden, excerpt, content)
                     VALUES (:user_id, :title, :area, :address, :price, :size, :status, :is_hidden, :excerpt, :content)"
                );
                $stmt->execute($payload);
                $successMessage = "Đã tạo tin đăng mới.";
                $formState['add']['values'] = [];
            } else {
                $postId = (int) ($_POST['post_id'] ?? 0);
                $stmt = $conn->prepare(
                    "UPDATE posts
                     SET user_id = :user_id,
                         title = :title,
                         area = :area,
                         address = :address,
                         price = :price,
                         size = :size,
                         status = :status,
                         is_hidden = :is_hidden,
                         excerpt = :excerpt,
                         content = :content
                     WHERE id = :id"
                );
                $stmt->execute($payload + ['id' => $postId]);
                $successMessage = "Đã cập nhật tin đăng.";
                $formState['edit']['values'] = [];
            }
        }
    } elseif ($action === 'toggle_visibility') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE posts SET is_hidden = 1 - is_hidden WHERE id = :id");
        $stmt->execute(['id' => $postId]);
        $successMessage = "Đã cập nhật trạng thái hiển thị tin.";
    } elseif ($action === 'update_status') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $newStatus = $_POST['status'] ?? 'draft';
        if (!isset($statusOptions[$newStatus])) {
            $errors[] = "Trạng thái không hợp lệ.";
        } else {
            $stmt = $conn->prepare("UPDATE posts SET status = :status WHERE id = :id");
            $stmt->execute([
                'status' => $newStatus,
                'id' => $postId,
            ]);
            $successMessage = "Đã cập nhật trạng thái tin đăng.";
        }
    }
}

$posts = getAllPostsWithAuthor($conn);
$totalPosts = count($posts);
$publishedPosts = array_reduce($posts, static function ($carry, $post) {
    return $carry + ($post['status'] === 'published' ? 1 : 0);
}, 0);
$hiddenPosts = array_reduce($posts, static function ($carry, $post) {
    return $carry + (!empty($post['is_hidden']) ? 1 : 0);
}, 0);

ob_start();
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4 mb-4">
        <div>
            <h1 class="mb-1">Quản lý tin đăng</h1>
            <p class="text-muted mb-0">Thêm, chỉnh sửa, ẩn tin và cập nhật trạng thái hiển thị.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPostModal">
            <i class="fas fa-plus me-2"></i>Thêm tin đăng
        </button>
    </div>

    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $successMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card border-start border-primary border-3 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Tổng số tin</div>
                    <div class="fs-3 fw-semibold"><?= $totalPosts ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-start border-success border-3 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Đang đăng</div>
                    <div class="fs-3 fw-semibold text-success"><?= $publishedPosts ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-start border-warning border-3 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Đang ẩn</div>
                    <div class="fs-3 fw-semibold text-warning"><?= $hiddenPosts ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong>Danh sách tin đăng</strong>
            <span class="text-muted small"><?= $totalPosts ?> tin</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Tiêu đề</th>
                            <th>Người đăng</th>
                            <th>Khu vực</th>
                            <th>Giá (đ)</th>
                            <th>Trạng thái</th>
                            <th>Hiển thị</th>
                            <th>Lượt xem</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$posts): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Chưa có tin đăng nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($post['title']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($post['address']) ?></div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($post['author_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($post['author_email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($post['area']) ?></td>
                                    <td><?= number_format((float) $post['price'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border mb-2 d-inline-block">
                                            <?= htmlspecialchars($statusOptions[$post['status']] ?? $post['status']) ?>
                                        </span>
                                        <form method="post" class="d-flex gap-2 align-items-center">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <select class="form-select form-select-sm" name="status">
                                                <?php foreach ($statusOptions as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $post['status'] === $value ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($label) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Lưu</button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if (!empty($post['is_hidden'])): ?>
                                            <span class="badge bg-secondary">Đang ẩn</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Hiển thị</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int) $post['views'] ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <!-- Nút sửa -->
        <button type="button"
                class="btn btn-outline-primary btn-icon"
                data-bs-toggle="modal"
                data-bs-target="#editPostModal"
                data-id="<?= $post['id'] ?>"
                data-title="<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>"
                data-user="<?= $post['user_id'] ?>"
                data-area="<?= htmlspecialchars($post['area'], ENT_QUOTES) ?>"
                data-address="<?= htmlspecialchars($post['address'], ENT_QUOTES) ?>"
                data-price="<?= (int) ($post['price'] ?? 0) ?>"
                data-size="<?= number_format((float) ($post['size'] ?? 0), 2, '.', '') ?>"
                data-status="<?= htmlspecialchars($post['status'] ?? '', ENT_QUOTES) ?>"
                data-hidden="<?= !empty($post['is_hidden']) ? 1 : 0 ?>"
                data-excerpt="<?= htmlspecialchars($post['excerpt'] ?? '', ENT_QUOTES) ?>"
                data-content="<?= htmlspecialchars($post['content'] ?? '', ENT_QUOTES) ?>">
            <i class="fas fa-edit"></i>
        </button>
                                            <!-- Nút ẩn / hiện -->
        <form method="post">
            <input type="hidden" name="action" value="toggle_visibility">
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
            <button type="submit" class="btn btn-outline-warning btn-icon">
                <i class="fas fa-eye-slash"></i>
            </button>
        </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Post -->
<div class="modal fade" id="addPostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="create_post">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm tin đăng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $formPrefix = 'add_post';
                    $formValues = $formState['add']['values'];
                    include __DIR__ . '/partials/post_form_fields.php';
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu tin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Post -->
<div class="modal fade" id="editPostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="update_post">
                <input type="hidden" name="post_id" id="editPostId">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật tin đăng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $formPrefix = 'edit_post';
                    $formValues = $formState['edit']['values'];
                    include __DIR__ . '/partials/post_form_fields.php';
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editPostModal');
    if (!editModal) {
        return;
    }

    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) {
            return;
        }

        document.getElementById('editPostId').value = button.getAttribute('data-id');
        editModal.querySelector('[name="title"]').value = button.getAttribute('data-title') || '';
        editModal.querySelector('[name="user_id"]').value = button.getAttribute('data-user') || '';
        editModal.querySelector('[name="area"]').value = button.getAttribute('data-area') || '';
        editModal.querySelector('[name="address"]').value = button.getAttribute('data-address') || '';
        editModal.querySelector('[name="price"]').value = button.getAttribute('data-price') || '';
        editModal.querySelector('[name="size"]').value = button.getAttribute('data-size') || '';
        editModal.querySelector('[name="status"]').value = button.getAttribute('data-status') || 'draft';
        editModal.querySelector('[name="is_hidden"]').checked = button.getAttribute('data-hidden') === '1';
        editModal.querySelector('[name="excerpt"]').value = button.getAttribute('data-excerpt') || '';
        editModal.querySelector('[name="content"]').value = button.getAttribute('data-content') || '';
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout2/theme.php';
