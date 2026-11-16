<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/rooms.php';

function format_review_row(array $r): array {
    return [
        'id'       => (int)$r['id'],
        'user'     => (string)$r['user_login'],
        'userName' => (string)$r['user_name'],
        'roomId'   => (string)$r['room_id'],
        'roomName' => (string)$r['room_name'],
        'rating'   => (int)$r['rating'],
        'text'     => (string)$r['text'],
        'date'     => date('d.m.Y', strtotime((string)$r['created_at'])),
    ];
}

function reviews_for_user(string $userLogin): array {
    $stmt = db()->prepare(
        "SELECT id, user_login, user_name, room_id, room_name, rating, text, created_at
         FROM reviews WHERE user_login = ? ORDER BY id DESC"
    );
    $stmt->bind_param('s', $userLogin);
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = format_review_row($row);
    }
    $stmt->close();
    return $list;
}

function reviews_public(int $limit = 24): array {
    $limit = max(1, min(100, $limit));
    $stmt = db()->prepare(
        "SELECT id, user_login, user_name, room_id, room_name, rating, text, created_at
         FROM reviews ORDER BY id DESC LIMIT ?"
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = format_review_row($row);
    }
    $stmt->close();
    return $list;
}

function review_create(array $user, string $roomId, int $rating, string $text): void {
    $room = find_room(trim($roomId));
    $text = trim($text);

    if (!$room) {
        throw new InvalidArgumentException('Выберите номер');
    }
    if ($rating < 1 || $rating > 5) {
        throw new InvalidArgumentException('Поставьте оценку от 1 до 5');
    }
    if (text_len($text) < 10) {
        throw new InvalidArgumentException('Отзыв — минимум 10 символов');
    }

    $stmt = db()->prepare(
        "INSERT INTO reviews (user_login, user_name, room_id, room_name, rating, text)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ssssis', $user['login'], $user['name'], $room['id'], $room['name'], $rating, $text);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Ошибка сохранения отзыва: ' . $err);
    }
    $stmt->close();
}
