<?php
require __DIR__ . "/auth_check.php";
require __DIR__ . "/../include/db.php";
require __DIR__ . "/../include/rooms.php";

$title = "Trang quản trị";
$fullName = htmlspecialchars($_SESSION['full_name'] ?? 'Admin');
$roleName = htmlspecialchars($_SESSION['role_name'] ?? 'admin');

$errors = [];
$successMessage = '';
$rooms = [];

ensureRoomsSchema($conn);
$amenitiesOptions = getAmenities($conn);
$formState = [
    'add' => ['values' => [], 'amenities' => []],
    'edit' => ['values' => [], 'amenities' => []],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $roomData = buildRoomPayload($_POST);
        $selectedAmenities = array_map('intval', $_POST['amenities'] ?? []);
        $formKey = $action === 'create' ? 'add' : 'edit';
        $formState[$formKey]['values'] = $_POST;
        $formState[$formKey]['amenities'] = $selectedAmenities;
        $formErrors = validateRoomData($roomData);

        if (!$formErrors) {
            try {
                if ($action === 'create') {
                    $stmt = $conn->prepare(
                        "INSERT INTO rooms (area, room_number, floor, size, price, max_capacity, current_occupancy, address)
                         VALUES (:area, :room_number, :floor, :size, :price, :max_capacity, :current_occupancy, :address)"
                    );
                    $stmt->execute($roomData);
                    $roomId = (int) $conn->lastInsertId();
                    syncRoomAmenities($conn, $roomId, $selectedAmenities);
                    $successMessage = "Đã thêm phòng mới thành công.";
                    $formState['add'] = ['values' => [], 'amenities' => []];
                } else {
                    $roomId = (int) ($_POST['room_id'] ?? 0);
                    if ($roomId <= 0) {
                        $errors[] = "Thiếu mã phòng cần cập nhật.";
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE rooms
                             SET area = :area,
                                 room_number = :room_number,
                                 floor = :floor,
                                 size = :size,
                                 price = :price,
                                 max_capacity = :max_capacity,
                                 current_occupancy = :current_occupancy,
                                 address = :address
                             WHERE id = :id"
                        );
                        $stmt->execute($roomData + ['id' => $roomId]);
                        syncRoomAmenities($conn, $roomId, $selectedAmenities);
                        $successMessage = "Cập nhật thông tin phòng thành công.";
                        $formState['edit'] = ['values' => [], 'amenities' => []];
                    }
                }
            } catch (PDOException $exception) {
                $errors[] = "Không thể lưu dữ liệu: " . $exception->getMessage();
            }
        } else {
            $errors = array_merge($errors, $formErrors);
        }
    } elseif ($action === 'delete') {
        $roomId = (int) ($_POST['room_id'] ?? 0);
        if ($roomId <= 0) {
            $errors[] = "Không xác định được phòng cần xóa.";
        } else {
            try {
                $stmt = $conn->prepare("DELETE FROM rooms WHERE id = :id");
                $stmt->execute(['id' => $roomId]);
                $successMessage = "Đã xóa phòng thành công.";
            } catch (PDOException $exception) {
                $errors[] = "Không thể xóa phòng: " . $exception->getMessage();
            }
        }
    }
}

$roomsStmt = $conn->query("SELECT * FROM rooms ORDER BY area ASC, room_number ASC");
$rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);
$roomAmenitiesMap = getRoomAmenitiesMap($conn, array_column($rooms, 'id'));
foreach ($rooms as &$room) {
    $roomId = (int) $room['id'];
    $room['amenities'] = $roomAmenitiesMap[$roomId] ?? [];
}
unset($room);

$totalRooms = count($rooms);
$totalSlots = array_sum(array_column($rooms, 'max_capacity')) ?: 0;
$currentSlots = array_sum(array_column($rooms, 'current_occupancy')) ?: 0;
$availableSlots = max($totalSlots - $currentSlots, 0);
$roomsFull = array_reduce($rooms, static function ($carry, $room) {
    return $carry + ($room['current_occupancy'] >= $room['max_capacity'] ? 1 : 0);
}, 0);
$roomsAvailable = $totalRooms - $roomsFull;

ob_start();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Trang quản trị</h1>
    <p class="lead mb-4">Xin chào <?= $fullName ?> • Quyền: <strong><?= $roleName ?></strong></p>

    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($successMessage) ?>
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

    <div class="row mb-4 g-3">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="small">Tổng số phòng</div>
                    <div class="fs-3"><?= $totalRooms ?></div>
                </div>
                <div class="card-footer text-white">
                    Đã cập nhật <?= date('d/m/Y') ?>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="small">Phòng còn trống</div>
                    <div class="fs-3"><?= $roomsAvailable ?></div>
                </div>
                <div class="card-footer text-white">
                    Còn <?= $availableSlots ?> slot
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="small">Phòng đã đầy</div>
                    <div class="fs-3"><?= $roomsFull ?></div>
                </div>
                <div class="card-footer text-white">
                    Đang có <?= $currentSlots ?> khách
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="small">Thao tác nhanh</div>
                    <div class="fs-6">Quản lý phòng</div>
                </div>
                <button class="card-footer text-white btn btn-link text-white text-decoration-none p-0"
                        data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    Thêm phòng mới
                    <span class="float-end"><i class="fas fa-angle-right"></i></span>
                </button>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <i class="fas fa-table me-1"></i>
                Quản lý phòng trọ
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="fas fa-plus me-2"></i>Thêm phòng
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Khu vực</th>
                            <th>Số phòng</th>
                            <th>Tầng</th>
                            <th>Diện tích (m²)</th>
                            <th>Giá tiền (đ)</th>
                            <th>Tối đa</th>
                            <th>Hiện tại</th>
                            <th>Địa chỉ</th>
                            <th>Tiện ích kèm theo</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rooms): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">Chưa có phòng nào, hãy thêm phòng mới.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><?= htmlspecialchars($room['area']) ?></td>
                                    <td><?= htmlspecialchars($room['room_number']) ?></td>
                                    <td><?= (int) $room['floor'] ?></td>
                                    <td><?= number_format((float) $room['size'], 2, ',', '.') ?></td>
                                    <td><?= formatCurrency($room['price']) ?></td>
                                    <td><?= (int) $room['max_capacity'] ?></td>
                                    <td><?= (int) $room['current_occupancy'] ?></td>
                                    <td><?= htmlspecialchars($room['address']) ?></td>
                                    <td>
                                        <?php if (!empty($room['amenities'])): ?>
                                            <?php foreach ($room['amenities'] as $amenity): ?>
                                                <span class="badge bg-light text-dark border me-1 mb-1 d-inline-flex align-items-center">
                                                    <?= htmlspecialchars($amenity['name']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Chưa cập nhật</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button"
                                                    class="btn btn-outline-primary js-edit-room"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editRoomModal"
                                                    data-id="<?= $room['id'] ?>"
                                                    data-area="<?= htmlspecialchars($room['area'], ENT_QUOTES) ?>"
                                                    data-room_number="<?= htmlspecialchars($room['room_number'], ENT_QUOTES) ?>"
                                                    data-floor="<?= (int) $room['floor'] ?>"
                                                    data-size="<?= number_format((float) $room['size'], 2, '.', '') ?>"
                                                    data-price="<?= (int) $room['price'] ?>"
                                                    data-max="<?= (int) $room['max_capacity'] ?>"
                                                    data-current="<?= (int) $room['current_occupancy'] ?>"
                                                    data-address="<?= htmlspecialchars($room['address'], ENT_QUOTES) ?>"
                                                    data-amenities="<?= htmlspecialchars(implode(',', array_map(static function ($amenity) {
                                                        return (int) $amenity['id'];
                                                    }, $room['amenities']))) ?>">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <form method="post" class="d-inline"
                                                  onsubmit="return confirm('Bạn chắc chắn muốn xóa phòng này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
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

<!-- Modal: Add Room -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm phòng mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $formPrefix = 'add_room';
                    $formValues = $formState['add']['values'];
                    $formSelectedAmenities = $formState['add']['amenities'];
                    $amenityOptionsForForm = $amenitiesOptions;
                    include __DIR__ . '/partials/room_form_fields.php';
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu phòng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Room -->
<div class="modal fade" id="editRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" id="editRoomForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="room_id" id="editRoomId">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật thông tin phòng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $formPrefix = 'edit_room';
                    $formValues = $formState['edit']['values'];
                    $formSelectedAmenities = $formState['edit']['amenities'];
                    $amenityOptionsForForm = $amenitiesOptions;
                    include __DIR__ . '/partials/room_form_fields.php';
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
    const editModal = document.getElementById('editRoomModal');
    if (!editModal) {
        return;
    }

    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) {
            return;
        }

        const fields = {
            area: button.getAttribute('data-area'),
            room_number: button.getAttribute('data-room_number'),
            floor: button.getAttribute('data-floor'),
            size: button.getAttribute('data-size'),
            price: button.getAttribute('data-price'),
            max_capacity: button.getAttribute('data-max'),
            current_occupancy: button.getAttribute('data-current'),
            address: button.getAttribute('data-address'),
        };

        document.getElementById('editRoomId').value = button.getAttribute('data-id');

        Object.entries(fields).forEach(([key, value]) => {
            const input = editModal.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = value ?? '';
            }
        });

        const amenityCheckboxes = editModal.querySelectorAll('input[name="amenities[]"]');
        amenityCheckboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });

        const amenitiesRaw = button.getAttribute('data-amenities') || '';
        if (amenitiesRaw) {
            amenitiesRaw
                .split(',')
                .map((id) => id.trim())
                .filter((id) => id !== '')
                .forEach((id) => {
                    const checkbox = editModal.querySelector(`input[name="amenities[]"][value="${id}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout2/theme.php';
