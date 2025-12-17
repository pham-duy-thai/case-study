<?php
$formPrefix = $formPrefix ?? uniqid('post_', false);
$formValues = $formValues ?? [];

$value = static function (string $key) use ($formValues) {
    return htmlspecialchars($formValues[$key] ?? '');
};

$selectedAuthor = isset($formValues['user_id']) ? (int) $formValues['user_id'] : 0;
$selectedStatus = $formValues['status'] ?? 'draft';
$isHidden = !empty($formValues['is_hidden']);
?>

<div class="row g-3">
    <div class="col-md-8">
        <label for="<?= $formPrefix ?>_title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="<?= $formPrefix ?>_title" name="title"
               value="<?= $value('title') ?>" required>
    </div>
    <div class="col-md-4">
        <label for="<?= $formPrefix ?>_author" class="form-label">Người đăng <span class="text-danger">*</span></label>
        <select class="form-select" id="<?= $formPrefix ?>_author" name="user_id" required>
            <option value="">-- Chọn --</option>
            <?php foreach ($authorMap as $id => $authorLabel): ?>
                <option value="<?= $id ?>" <?= $selectedAuthor === (int) $id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($authorLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label for="<?= $formPrefix ?>_area" class="form-label">Khu vực</label>
        <input type="text" class="form-control" id="<?= $formPrefix ?>_area" name="area"
               value="<?= $value('area') ?>">
    </div>
    <div class="col-md-6">
        <label for="<?= $formPrefix ?>_address" class="form-label">Địa chỉ</label>
        <input type="text" class="form-control" id="<?= $formPrefix ?>_address" name="address"
               value="<?= $value('address') ?>">
    </div>
    <div class="col-md-4">
        <label for="<?= $formPrefix ?>_price" class="form-label">Giá tiền (đ)</label>
        <input type="number" class="form-control" id="<?= $formPrefix ?>_price" name="price" min="0"
               value="<?= $value('price') ?>">
    </div>
    <div class="col-md-4">
        <label for="<?= $formPrefix ?>_size" class="form-label">Diện tích (m²)</label>
        <input type="number" step="0.1" class="form-control" id="<?= $formPrefix ?>_size" name="size" min="0"
               value="<?= $value('size') ?>">
    </div>
    <div class="col-md-4">
        <label for="<?= $formPrefix ?>_status" class="form-label">Trạng thái</label>
        <select class="form-select" id="<?= $formPrefix ?>_status" name="status">
            <?php foreach ($statusOptions as $state => $label): ?>
                <option value="<?= $state ?>" <?= $selectedStatus === $state ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="<?= $formPrefix ?>_hidden"
                   name="is_hidden" <?= $isHidden ? 'checked' : '' ?>>
            <label class="form-check-label" for="<?= $formPrefix ?>_hidden">
                Ẩn tin khỏi danh sách công khai
            </label>
        </div>
    </div>
    <div class="col-12">
        <label for="<?= $formPrefix ?>_excerpt" class="form-label">Mô tả ngắn</label>
        <textarea class="form-control" id="<?= $formPrefix ?>_excerpt" rows="2" name="excerpt"><?= $value('excerpt') ?></textarea>
    </div>
    <div class="col-12">
        <label for="<?= $formPrefix ?>_content" class="form-label">Nội dung chi tiết</label>
        <textarea class="form-control" id="<?= $formPrefix ?>_content" rows="4" name="content"><?= $value('content') ?></textarea>
    </div>
</div>
