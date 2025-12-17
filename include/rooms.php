<?php

const DEFAULT_AMENITIES = [
    'Wifi miễn phí',
    'Máy giặt chung',
    'Nhà để xe',
    'Camera an ninh',
    'Giờ giấc tự do',
    'Gần trường ĐH Vinh',
    'Bếp chung',
    'Ban công thoáng mát',
];

function ensureRoomsSchema(PDO $conn): void
{
    $conn->exec(
        "CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            area VARCHAR(255) NOT NULL,
            room_number VARCHAR(50) NOT NULL,
            floor INT DEFAULT 0,
            size DECIMAL(10,2) DEFAULT 0,
            price BIGINT DEFAULT 0,
            max_capacity INT DEFAULT 0,
            current_occupancy INT DEFAULT 0,
            address VARCHAR(255) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_room (area, room_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->exec(
        "CREATE TABLE IF NOT EXISTS amenities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->exec(
        "CREATE TABLE IF NOT EXISTS room_amenities (
            room_id INT NOT NULL,
            amenity_id INT NOT NULL,
            PRIMARY KEY (room_id, amenity_id),
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
            FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $count = (int) $conn->query("SELECT COUNT(*) FROM amenities")->fetchColumn();
    if ($count === 0) {
        $stmt = $conn->prepare("INSERT INTO amenities (name) VALUES (:name)");
        foreach (DEFAULT_AMENITIES as $name) {
            $stmt->execute(['name' => $name]);
        }
    }
}

function getAmenities(PDO $conn): array
{
    $stmt = $conn->query("SELECT id, name FROM amenities ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function buildRoomPayload(array $source): array
{
    $normalizePrice = static function ($value) {
        $digits = preg_replace('/[^\d]/', '', (string) $value);
        return $digits === '' ? 0 : (int) $digits;
    };

    $normalizeSize = static function ($value) {
        $value = str_replace(',', '.', (string) $value);
        return is_numeric($value) ? round((float) $value, 2) : 0;
    };

    return [
        'area' => trim($source['area'] ?? ''),
        'room_number' => trim($source['room_number'] ?? ''),
        'floor' => (int) ($source['floor'] ?? 0),
        'size' => $normalizeSize($source['size'] ?? 0),
        'price' => $normalizePrice($source['price'] ?? 0),
        'max_capacity' => max(0, (int) ($source['max_capacity'] ?? 0)),
        'current_occupancy' => max(0, (int) ($source['current_occupancy'] ?? 0)),
        'address' => trim($source['address'] ?? ''),
    ];
}

function validateRoomData(array $data): array
{
    $messages = [];
    if ($data['area'] === '') {
        $messages[] = "Khu vực không được để trống.";
    }
    if ($data['room_number'] === '') {
        $messages[] = "Số phòng không được để trống.";
    }
    if ($data['max_capacity'] <= 0) {
        $messages[] = "Số lượng tối đa phải lớn hơn 0.";
    }
    if ($data['current_occupancy'] > $data['max_capacity']) {
        $messages[] = "Số lượng hiện tại không được lớn hơn số lượng tối đa.";
    }

    return $messages;
}

function formatCurrency($value): string
{
    return number_format((float) $value, 0, ',', '.');
}

function syncRoomAmenities(PDO $conn, int $roomId, array $amenityIds): void
{
    $conn->prepare("DELETE FROM room_amenities WHERE room_id = :room_id")
        ->execute(['room_id' => $roomId]);

    $uniqueIds = array_values(array_unique(array_filter($amenityIds, static function ($id) {
        return (int) $id > 0;
    })));

    if (!$uniqueIds) {
        return;
    }

    $stmt = $conn->prepare(
        "INSERT INTO room_amenities (room_id, amenity_id)
         VALUES (:room_id, :amenity_id)"
    );

    foreach ($uniqueIds as $amenityId) {
        $stmt->execute([
            'room_id' => $roomId,
            'amenity_id' => (int) $amenityId,
        ]);
    }
}

function getRoomAmenitiesMap(PDO $conn, array $roomIds): array
{
    $roomIds = array_values(array_filter($roomIds, static function ($id) {
        return (int) $id > 0;
    }));
    if (!$roomIds) {
        return [];
    }

    $placeholder = implode(',', array_fill(0, count($roomIds), '?'));
    $stmt = $conn->prepare(
        "SELECT ra.room_id, a.id, a.name
         FROM room_amenities ra
         JOIN amenities a ON a.id = ra.amenity_id
         WHERE ra.room_id IN ($placeholder)
         ORDER BY a.name ASC"
    );
    $stmt->execute($roomIds);

    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $roomId = (int) $row['room_id'];
        $map[$roomId][] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
        ];
    }
    return $map;
}

function getRoomById(PDO $conn, int $roomId): ?array
{
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = :id");
    $stmt->execute(['id' => $roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) {
        return null;
    }
    $amenities = getRoomAmenitiesMap($conn, [$roomId]);
    $room['amenities'] = $amenities[$roomId] ?? [];
    return $room;
}

function searchRooms(PDO $conn, array $filters, int $perPage = 6, int $page = 1): array
{
    $perPage = max(1, $perPage);
    $page = max(1, $page);

    $where = [];
    $params = [];

    $keyword = trim($filters['keyword'] ?? '');
    if ($keyword !== '') {
        $where[] = "(r.area LIKE :keyword OR r.address LIKE :keyword OR r.room_number LIKE :keyword)";
        $params[':keyword'] = '%' . $keyword . '%';
    }

    $areaFilter = trim($filters['area'] ?? '');
    if ($areaFilter !== '') {
        $where[] = "r.area LIKE :area_filter";
        $params[':area_filter'] = '%' . $areaFilter . '%';
    }

    $priceMin = (int) ($filters['price_min'] ?? 0);
    if ($priceMin > 0) {
        $where[] = "r.price >= :price_min";
        $params[':price_min'] = $priceMin;
    }

    $priceMax = (int) ($filters['price_max'] ?? 0);
    if ($priceMax > 0) {
        $where[] = "r.price <= :price_max";
        $params[':price_max'] = $priceMax;
    }

    $sizeMin = (float) ($filters['size_min'] ?? 0);
    if ($sizeMin > 0) {
        $where[] = "r.size >= :size_min";
        $params[':size_min'] = $sizeMin;
    }

    $sizeMax = (float) ($filters['size_max'] ?? 0);
    if ($sizeMax > 0) {
        $where[] = "r.size <= :size_max";
        $params[':size_max'] = $sizeMax;
    }

    $amenityIds = array_values(array_unique(array_filter(
        $filters['amenities'] ?? [],
        static function ($id) {
            return (int) $id > 0;
        }
    )));
    if ($amenityIds) {
        $placeholderNames = [];
        foreach ($amenityIds as $index => $amenityId) {
            $paramName = ":amenity_$index";
            $placeholderNames[] = $paramName;
            $params[$paramName] = (int) $amenityId;
        }

        $where[] = "r.id IN (
            SELECT room_id
            FROM room_amenities
            WHERE amenity_id IN (" . implode(',', $placeholderNames) . ")
            GROUP BY room_id
            HAVING COUNT(DISTINCT amenity_id) = " . count($amenityIds) . "
        )";
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) FROM rooms r $whereClause";
    $countStmt = $conn->prepare($countSql);
    foreach ($params as $name => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $countStmt->bindValue($name, $value, $type);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;

    $dataSql = "
        SELECT r.*, GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR ', ') AS amenity_names
        FROM rooms r
        LEFT JOIN room_amenities ra ON ra.room_id = r.id
        LEFT JOIN amenities a ON a.id = ra.amenity_id
        $whereClause
        GROUP BY r.id
        ORDER BY r.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $dataStmt = $conn->prepare($dataSql);
    foreach ($params as $name => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $dataStmt->bindValue($name, $value, $type);
    }
    $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$row) {
        $row['amenities'] = $row['amenity_names']
            ? array_filter(array_map('trim', explode(',', $row['amenity_names'])))
            : [];
    }

    return [
        'data' => $rows,
        'pagination' => [
            'total' => $total,
            'per_page' => $perPage,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ],
    ];
}
