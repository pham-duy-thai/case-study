<?php
require __DIR__ . "/auth_check.php";
require __DIR__ . "/../include/db.php";
require __DIR__ . "/../include/admin_helpers.php";

$title = "Quản lý tài khoản";
$fullName = htmlspecialchars($_SESSION['full_name'] ?? 'Admin');

ensureUserSchema($conn);

$errors = [];
$successMessage = '';
$roles = getAllRoles($conn);
$roleMap = [];
foreach ($roles as $role) {
    $roleMap[$role['id']] = $role['name'];
}

$formState = [
    'add' => ['values' => []],
    'edit' => ['values' => []],
];

function validateUserData(array $data, bool $isCreate): array
{
    $messages = [];
    if (trim($data['full_name'] ?? '') === '') {
        $messages[] = "Họ tên không được để trống.";
    }
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $messages[] = "Email không hợp lệ.";
    }
    if ($isCreate && (empty($data['password']) || strlen($data['password']) < 6)) {
        $messages[] = "Mật khẩu phải có ít nhất 6 ký tự.";
    }
    if (empty($data['role_id'])) {
        $messages[] = "Vui lòng chọn quyền.";
    }
    return $messages;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $payload = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role_id' => (int) ($_POST['role_id'] ?? 0),
            'status' => (int) ($_POST['status'] ?? 1),
        ];
        $formState['add']['values'] = $payload;

        $validation = validateUserData($payload, true);
        if (!$validation) {
            if (!isset($roleMap[$payload['role_id']])) {
                $validation[] = "Quyền không hợp lệ.";
            }
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $payload['email']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $validation[] = "Email đã tồn tại.";
            }
        }

        if ($validation) {
            $errors = array_merge($errors, $validation);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO users (role_id, full_name, email, password, status)
                 VALUES (:role_id, :full_name, :email, :password, :status)"
            );
            $stmt->execute([
                'role_id' => $payload['role_id'],
                'full_name' => $payload['full_name'],
                'email' => $payload['email'],
                'password' => password_hash($payload['password'], PASSWORD_DEFAULT),
                'status' => $payload['status'] ? 1 : 0,
            ]);
            $successMessage = "Đã tạo tài khoản mới.";
            $formState['add']['values'] = [];
        }
    } elseif ($action === 'update_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $payload = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role_id' => (int) ($_POST['role_id'] ?? 0),
            'status' => (int) ($_POST['status'] ?? 1),
        ];
        $formState['edit']['values'] = $payload + ['id' => $userId];

        $validation = validateUserData($payload + ['password' => 'placeholder'], false);
        if (!$validation) {
            if (!isset($roleMap[$payload['role_id']])) {
                $validation[] = "Quyền không hợp lệ.";
            }
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id <> :id");
            $stmt->execute([
                'email' => $payload['email'],
                'id' => $userId,
            ]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $validation[] = "Email đã được sử dụng.";
            }
        }

        if ($validation) {
            $errors = array_merge($errors, $validation);
        } else {
            $stmt = $conn->prepare(
                "UPDATE users
                 SET full_name = :full_name,
                     email = :email,
                     role_id = :role_id,
                     status = :status
                 WHERE id = :id"
            );
            $stmt->execute([
                'full_name' => $payload['full_name'],
                'email' => $payload['email'],
                'role_id' => $payload['role_id'],
                'status' => $payload['status'] ? 1 : 0,
                'id' => $userId,
            ]);
            $successMessage = "Đã cập nhật thông tin tài khoản.";
            $formState['edit']['values'] = [];
        }
    } elseif ($action === 'reset_password') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $errors[] = "Không xác định được tài khoản cần reset.";
        } else {
            $newPassword = generateRandomPassword();
            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute([
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'id' => $userId,
            ]);
            $successMessage = "Đã reset mật khẩu. Mật khẩu mới: <strong>{$newPassword}</strong>";
        }
    }
}

$users = getAllUsersWithRoles($conn);
$totalUsers = count($users);
$activeUsers = array_reduce($users, static function ($carry, $user) {
    return $carry + ($user['status'] ? 1 : 0);
}, 0);
$lockedUsers = $totalUsers - $activeUsers;

ob_start();
?>

<div class="container-fluid px-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-4 mb-4">
        <div>
            <h1 class="mb-1">Quản lý tài khoản</h1>
            <p class="text-muted mb-0">Xin chào <?= $fullName ?> • Theo dõi và quản trị tài khoản hệ thống.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus me-2"></i>Thêm tài khoản
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
        <div class="col-md-4">
            <div class="card border-start border-primary border-3 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Tổng tài khoản</div>
                    <div class="fs-3 fw-semibold"><?= $totalUsers ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-success border-3 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Đang hoạt động</div>
                    <div class="fs-3 fw-semibold text-success"><?= $activeUsers ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-warning border-3 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Đã khóa</div>
                    <div class="fs-3 fw-semibold text-warning"><?= $lockedUsers ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong>Danh sách tài khoản</strong>
            <div class="text-muted small">Hiển thị <?= $totalUsers ?> tài khoản</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Quyền</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$users): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Chưa có tài khoản nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($user['role_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['status']): ?>
                                            <span class="badge bg-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Đã khóa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button"
                                                    class="btn btn-outline-primary js-edit-user"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-name="<?= htmlspecialchars($user['full_name'], ENT_QUOTES) ?>"
                                                    data-email="<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>"
                                                    data-role="<?= $user['role_id'] ?>"
                                                    data-status="<?= $user['status'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-outline-warning"
                                                        onclick="return confirm('Reset mật khẩu cho tài khoản này?');">
                                                    <i class="fas fa-key"></i>
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

<!-- Modal: Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="create_user">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm tài khoản</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $formPrefix = 'add_user';
                    $formValues = $formState['add']['values'];
                    $includePassword = true;
                    include __DIR__ . '/partials/user_form_fields.php';
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu tài khoản</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật tài khoản</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $formPrefix = 'edit_user';
                    $formValues = $formState['edit']['values'];
                    $includePassword = false;
                    include __DIR__ . '/partials/user_form_fields.php';
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
    const editModal = document.getElementById('editUserModal');
    if (!editModal) {
        return;
    }

    editModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        document.getElementById('editUserId').value = trigger.getAttribute('data-id');
        editModal.querySelector('[name="full_name"]').value = trigger.getAttribute('data-name') || '';
        editModal.querySelector('[name="email"]').value = trigger.getAttribute('data-email') || '';
        editModal.querySelector('[name="role_id"]').value = trigger.getAttribute('data-role') || '';
        editModal.querySelector('[name="status"]').value = trigger.getAttribute('data-status') || '1';
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout2/theme.php';
