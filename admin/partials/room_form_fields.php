<?php
$formPrefix = $formPrefix ?? uniqid('room_', false);
$formValues = $formValues ?? [];
$formSelectedAmenities = array_map('intval', $formSelectedAmenities ?? []);
$amenityOptionsForForm = $amenityOptionsForForm ?? [];

$value = static function (string $key) use ($formValues) {
    return htmlspecialchars($formValues[$key] ?? '');
};
?>

<div class="row g-3">
    <div class="col-md-6">
        <label for="<?= $formPrefix ?>_area" class="form-label">Khu vực <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="<?= $formPrefix ?>_area" name="area"
               value="<?= $value('area') ?>" required>
    </div>
    <div class="col-md-6">
        <label for="<?= $formPrefix ?>_room" class="form-label">Số phòng <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="<?= $formPrefix ?>_room" name="room_number"
               value="<?= $value('room_number') ?>" required>
    </div>
    <div class="col-md-4">
        <label for="<?= $formPrefix ?>_floor" class="form-label">Tầng</label>
        <input type="number" class="form-control" id="<?= $formPrefix ?>_floor" name="floor" min="0"
               value="<?= $value('floor') ?>">
    </div>
    <div class="col-md-4">
        <label for="<?= $formPrefix ?>_size" class="form-label">Diện tích (m²)</label>
        <input type="number" step="0.1" class="form-control" id="<?= $formPrefix ?>_size" name="size"
               value="<?= $value('size') ?>">
    </div>
    <div class="col-md-4">
        <label for="<?= $formPrefix ?>_price" class="form-label">Giá tiền (đ)</label>
        <input type="text" class="form-control" id="<?= $formPrefix ?>_price" name="price"
               value="<?= $value('price') ?>">
    </div>
    <div class="col-md-6">
        <label for="<?= $formPrefix ?>_max" class="form-label">Số lượng tối đa <span class="text-danger">*</span></label>
        <input type="number" class="form-control" id="<?= $formPrefix ?>_max" name="max_capacity" min="1"
               value="<?= $value('max_capacity') ?>" required>
    </div>
    <div class="col-md-6">
        <label for="<?= $formPrefix ?>_current" class="form-label">Số lượng hiện tại</label>
        <input type="number" class="form-control" id="<?= $formPrefix ?>_current" name="current_occupancy" min="0"
               value="<?= $value('current_occupancy') ?>">
    </div>
    <div class="col-12">
        <label for="<?= $formPrefix ?>_address" class="form-label">Địa chỉ</label>
        <input type="text" class="form-control" id="<?= $formPrefix ?>_address" name="address"
               value="<?= $value('address') ?>">
    </div>

    <?php if ($amenityOptionsForForm): ?>
        <div class="col-12">
            <label class="form-label">Tiện ích kèm theo</label>
            <div class="row g-2">
                <?php foreach ($amenityOptionsForForm as $amenity): ?>
                    <div class="col-sm-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="amenities[]"
                                   value="<?= (int) $amenity['id'] ?>"
                                   id="<?= $formPrefix ?>_amenity_<?= (int) $amenity['id'] ?>"
                                <?= in_array((int) $amenity['id'], $formSelectedAmenities, true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="<?= $formPrefix ?>_amenity_<?= (int) $amenity['id'] ?>">
                                <?= htmlspecialchars($amenity['name']) ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
