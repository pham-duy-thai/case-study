<?php
$formPrefix = $formPrefix ?? uniqid('user_', false);
$formValues = $formValues ?? [];
$includePassword = $includePassword ?? false;

$value = static function (string $key) use ($formValues) {
    return htmlspecialchars($formValues[$key] ?? '');
};

$statusValue = isset($formValues['status']) ? (int) $formValues['status'] : 1;
?>

<div class="mb-3">
    <label for="<?= $formPrefix ?>_name" class="form-label">Họ tên <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="<?= $formPrefix ?>_name" name="full_name"
           value="<?= $value('full_name') ?>" required>
</div>

<div class="mb-3">
    <label for="<?= $formPrefix ?>_email" class="form-label">Email <span class="text-danger">*</span></label>
    <input type="email" class="form-control" id="<?= $formPrefix ?>_email" name="email"
           value="<?= $value('email') ?>" required>
</div>

<?php if ($includePassword): ?>
    <div class="mb-3">
        <label for="<?= $formPrefix ?>_password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="<?= $formPrefix ?>_password" name="password"
               minlength="6" required>
        <div class="form-text">Tối thiểu 6 ký tự.</div>
    </div>
<?php endif; ?>

<div class="mb-3">
    <label for="<?= $formPrefix ?>_role" class="form-label">Quyền</label>
    <select class="form-select" id="<?= $formPrefix ?>_role" name="role_id" required>
        <option value="">-- Chọn quyền --</option>
        <?php foreach ($roleMap as $id => $roleName): ?>
            <option value="<?= $id ?>" <?= (int) ($formValues['role_id'] ?? 0) === (int) $id ? 'selected' : '' ?>>
                <?= htmlspecialchars($roleName) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-3">
    <label for="<?= $formPrefix ?>_status" class="form-label">Trạng thái</label>
    <select class="form-select" id="<?= $formPrefix ?>_status" name="status">
        <option value="1" <?= $statusValue === 1 ? 'selected' : '' ?>>Hoạt động</option>
        <option value="0" <?= $statusValue === 0 ? 'selected' : '' ?>>Khóa</option>
    </select>
</div>
