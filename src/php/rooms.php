<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Каталог номеров читается из data/rooms.json (единый источник для PHP и статики).
 */
function rooms(): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $file = dirname(__DIR__, 2) . '/data/rooms.json';
    if (is_file($file)) {
        $raw = file_get_contents($file);
        $list = json_decode((string)$raw, true);
        if (is_array($list) && count($list) > 0) {
            $cache = $list;
            return $cache;
        }
    }

    $cache = [
        [
            'id' => 'r1', 'number' => '101', 'name' => 'Люкс «Классик»', 'type' => 'Люкс',
            'capacity' => 2, 'beds' => '1 двуспальная кровать', 'size' => 42, 'price' => 8900,
            'image' => 'assets/img/r1.jpg', 'rating' => 4.9,
            'desc' => 'Просторный номер с зоной отдыха и большой ванной комнатой.',
            'features' => ['Завтрак включён', 'Вид на двор', 'Кондиционер']
        ],
    ];
    return $cache;
}

function find_room(string $id): ?array {
    foreach (rooms() as $room) {
        if (($room['id'] ?? '') === $id) {
            return $room;
        }
    }
    return null;
}

/**
 * Проверяет, пересекаются ли даты с активными (не отменёнными) бронями номера.
 */
function room_has_conflict(string $roomId, string $checkin, string $checkout): bool {
    if (!is_valid_iso_date($checkin) || !is_valid_iso_date($checkout)) {
        return false;
    }
    $stmt = db()->prepare(
        "SELECT id FROM bookings
         WHERE room_id = ? AND status != 'cancel'
           AND checkin < ? AND checkout > ?
         LIMIT 1"
    );
    $stmt->bind_param('sss', $roomId, $checkout, $checkin);
    $stmt->execute();
    $hit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $hit !== null;
}

/**
 * Возвращает список ID номеров, занятых в указанном диапазоне дат.
 */
function rooms_unavailable_ids(string $checkin, string $checkout): array {
    if (nights_between($checkin, $checkout) < 1) {
        return [];
    }
    $stmt = db()->prepare(
        "SELECT DISTINCT room_id FROM bookings
         WHERE status != 'cancel'
           AND checkin < ? AND checkout > ?"
    );
    $stmt->bind_param('ss', $checkout, $checkin);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($row = $res->fetch_assoc()) {
        $ids[] = (string)$row['room_id'];
    }
    $stmt->close();
    return $ids;
}
