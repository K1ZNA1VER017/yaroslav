<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/rooms.php';
require_once __DIR__ . '/auth.php';

function format_booking_row(array $r): array {
    $extrasRaw = (string)($r['extras'] ?? '');
    $extras = [];
    if ($extrasRaw !== '') {
        $decoded = json_decode($extrasRaw, true);
        if (is_array($decoded)) {
            $extras = $decoded;
        }
    }

    return [
        'id'          => (int)$r['id'],
        'code'        => 'GN-' . str_pad((string)(1000 + (int)$r['id']), 4, '0', STR_PAD_LEFT),
        'user'        => (string)$r['user_login'],
        'roomId'      => (string)$r['room_id'],
        'roomName'    => (string)$r['room_name'],
        'name'        => (string)$r['guest_name'],
        'phone'       => (string)$r['phone'],
        'email'       => (string)($r['email'] ?? ''),
        'checkin'     => (string)$r['checkin'],
        'checkout'    => (string)$r['checkout'],
        'guests'      => (int)$r['guests'],
        'nights'      => (int)$r['nights'],
        'extras'      => $extras,
        'extrasTotal' => (int)($r['extras_total'] ?? 0),
        'total'       => (int)$r['total'],
        'comment'     => (string)($r['comment'] ?? ''),
        'status'      => (string)$r['status'],
        'createdAt'   => isset($r['created_at']) ? date('d.m.Y H:i', strtotime((string)$r['created_at'])) : '',
    ];
}

function bookings_for_user(string $userLogin): array {
    $stmt = db()->prepare(
        "SELECT id, user_login, room_id, room_name, guest_name, phone, email,
                checkin, checkout, guests, nights, extras, extras_total, total, comment, status, created_at
         FROM bookings WHERE user_login = ? ORDER BY id DESC"
    );
    $stmt->bind_param('s', $userLogin);
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = format_booking_row($row);
    }
    $stmt->close();
    return $list;
}

function booking_create(array $input): array {
    $roomId   = trim((string)($input['roomId'] ?? ''));
    $name     = trim((string)($input['name'] ?? $input['fio'] ?? ''));
    $phone    = trim((string)($input['phone'] ?? ''));
    $email    = trim((string)($input['email'] ?? ''));
    $checkin  = trim((string)($input['checkin'] ?? ''));
    $checkout = trim((string)($input['checkout'] ?? ''));
    $guests   = (int)($input['guests'] ?? 0);
    $comment  = trim((string)($input['comment'] ?? ''));
    $pass     = (string)($input['pass'] ?? '');
    $extrasIn = (string)($input['extras'] ?? '');

    $room = find_room($roomId);
    if (!$room) {
        throw new InvalidArgumentException('Выберите номер для бронирования');
    }
    if ($name === '') {
        throw new InvalidArgumentException('Укажите имя гостя');
    }
    if ($phone === '') {
        throw new InvalidArgumentException('Укажите контактный телефон');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Проверьте корректность e-mail');
    }
    if ($checkin === '' || $checkout === '') {
        throw new InvalidArgumentException('Укажите даты заезда и выезда');
    }

    $nights = nights_between($checkin, $checkout);
    if ($nights < 1) {
        throw new InvalidArgumentException('Дата выезда должна быть позже даты заезда');
    }
    if ($nights > 60) {
        throw new InvalidArgumentException('Максимальная длительность онлайн-бронирования — 60 ночей');
    }
    if ($guests < 1) {
        throw new InvalidArgumentException('Укажите количество гостей');
    }
    $cap = (int)$room['capacity'];
    if ($guests > $cap) {
        throw new InvalidArgumentException(
            'В номере «' . $room['name'] . '» максимум ' . $cap . ' ' . plural_ru($cap, 'гость', 'гостя', 'гостей')
        );
    }

    if (room_has_conflict($room['id'], $checkin, $checkout)) {
        throw new InvalidArgumentException(
            'Номер «' . $room['name'] . '» уже забронирован на выбранные даты. Попробуйте другие даты или категорию.'
        );
    }

    // Авторизованный пользователь или бесшовное создание аккаунта гостя
    $user = current_user();
    if (!$user) {
        if ($pass === '') {
            throw new InvalidArgumentException('Войдите в аккаунт или задайте пароль для создания личного кабинета');
        }
        $user = auth_ensure_guest_account($name, $phone, $pass);
    }

    $parsedExtras = parse_booking_extras($extrasIn);
    $extrasJson   = json_encode($parsedExtras['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $extrasTotal  = (int)$parsedExtras['total'];
    $total        = booking_total((int)$room['price'], $nights, $extrasTotal);

    $stmt = db()->prepare(
        "INSERT INTO bookings
         (user_login, room_id, room_name, guest_name, phone, email,
          checkin, checkout, guests, nights, extras, extras_total, total, comment, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')"
    );
    $stmt->bind_param(
        'ssssssssiisiis',
        $user['login'],
        $room['id'],
        $room['name'],
        $name,
        $phone,
        $email,
        $checkin,
        $checkout,
        $guests,
        $nights,
        $extrasJson,
        $extrasTotal,
        $total,
        $comment
    );
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Ошибка сохранения заявки: ' . $err);
    }
    $newId = (int)$stmt->insert_id;
    $stmt->close();

    return [
        'id'          => $newId,
        'code'        => 'GN-' . str_pad((string)(1000 + $newId), 4, '0', STR_PAD_LEFT),
        'user'        => $user['login'],
        'roomId'      => $room['id'],
        'roomName'    => $room['name'],
        'name'        => $name,
        'phone'       => $phone,
        'email'       => $email,
        'checkin'     => $checkin,
        'checkout'    => $checkout,
        'guests'      => $guests,
        'nights'      => $nights,
        'extras'      => $parsedExtras['items'],
        'extrasTotal' => $extrasTotal,
        'total'       => $total,
        'comment'     => $comment,
        'status'      => 'new',
        'createdAt'   => date('d.m.Y H:i'),
    ];
}

function booking_cancel(int $id, string $userLogin): void {
    $stmt = db()->prepare(
        "UPDATE bookings SET status = 'cancel'
         WHERE id = ? AND user_login = ? AND status = 'new'"
    );
    $stmt->bind_param('is', $id, $userLogin);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected < 1) {
        throw new InvalidArgumentException('Заявка не найдена или уже обработана менеджером');
    }
}

function admin_bookings_dashboard(): array {
    $stats = [
        'total'    => 0,
        'new'      => 0,
        'confirm'  => 0,
        'paid'     => 0,
        'done'     => 0,
        'cancel'   => 0,
        'revenue'  => 0,
        'occupied' => 0,
    ];

    $list = [];
    $occupiedRooms = [];
    $today = date('Y-m-d');

    $res = db()->query(
        "SELECT id, user_login, room_id, room_name, guest_name, phone, email,
                checkin, checkout, guests, nights, extras, extras_total, total, comment, status, created_at
         FROM bookings ORDER BY id DESC"
    );

    while ($row = $res->fetch_assoc()) {
        $formatted = format_booking_row($row);
        $list[] = $formatted;

        $stats['total']++;
        $st = $formatted['status'];
        if (isset($stats[$st])) {
            $stats[$st]++;
        }
        if (in_array($st, ['confirm', 'paid', 'done'], true)) {
            $stats['revenue'] += $formatted['total'];
        }
        if ($st !== 'cancel' && $formatted['checkin'] <= $today && $formatted['checkout'] > $today) {
            $occupiedRooms[$formatted['roomId']] = true;
        }
    }

    $stats['occupied'] = count($occupiedRooms);
    return ['bookings' => $list, 'stats' => $stats];
}

function admin_update_status(int $id, string $status): void {
    if (!in_array($status, BOOKING_STATUSES, true)) {
        throw new InvalidArgumentException('Недопустимый статус заявки');
    }
    $stmt = db()->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();

    if (!$ok) {
        throw new RuntimeException('Не удалось обновить статус');
    }
}
