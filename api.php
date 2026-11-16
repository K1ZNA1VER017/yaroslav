<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');

try {
    switch ($action) {

        /* ================= ПОЛЬЗОВАТЕЛЬ ================= */

        case 'register': {
            $login = trim((string)($_POST['login'] ?? ''));
            $name  = trim((string)($_POST['name']  ?? ''));
            $pass  = (string)($_POST['pass'] ?? '');

            if (text_len($login) < 3) json_out(['ok'=>false,'error'=>'Логин — минимум 3 символа']);
            if (text_len($pass)  < 4) json_out(['ok'=>false,'error'=>'Пароль — минимум 4 символа']);
            if ($name === '') $name = $login;

            $db = db();
            $stmt = $db->prepare("SELECT id FROM users WHERE login = ? LIMIT 1");
            $stmt->bind_param('s', $login);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                json_out(['ok'=>false,'error'=>'Такой логин уже занят']);
            }
            $stmt->close();

            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (login, name, pass) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $login, $name, $hash);
            if (!$stmt->execute()) {
                json_out(['ok'=>false,'error'=>'Ошибка регистрации: '.$stmt->error]);
            }
            $stmt->close();

            $_SESSION['user'] = $login;
            json_out(['ok'=>true, 'state' => state_for_client()]);
        }

        case 'login': {
            $login = trim((string)($_POST['login'] ?? ''));
            $pass  = (string)($_POST['pass'] ?? '');

            $stmt = db()->prepare("SELECT login, pass FROM users WHERE login = ? LIMIT 1");
            $stmt->bind_param('s', $login);
            $stmt->execute();
            $u = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$u) json_out(['ok'=>false,'error'=>'Пользователь не найден']);
            if (!password_verify($pass, $u['pass'])) {
                json_out(['ok'=>false,'error'=>'Неверный пароль']);
            }

            $_SESSION['user'] = $u['login'];
            json_out(['ok'=>true, 'state' => state_for_client()]);
        }

        case 'logout': {
            unset($_SESSION['user']);
            json_out(['ok'=>true, 'state' => state_for_client()]);
        }

        case 'state': {
            json_out(['ok'=>true, 'state' => state_for_client()]);
        }

        /* ================= БРОНИРОВАНИЕ НОМЕРА ================= */

        case 'booking.create': {
            $u = current_user();
            if (!$u) json_out(['ok'=>false,'error'=>'Требуется вход'], 401);

            $roomId   = trim((string)($_POST['roomId'] ?? ''));
            $name     = trim((string)($_POST['name'] ?? ''));
            $phone    = trim((string)($_POST['phone'] ?? ''));
            $email    = trim((string)($_POST['email'] ?? ''));
            $checkin  = trim((string)($_POST['checkin'] ?? ''));
            $checkout = trim((string)($_POST['checkout'] ?? ''));
            $guests   = (int)($_POST['guests'] ?? 0);
            $comment  = trim((string)($_POST['comment'] ?? ''));

            $room = find_room($roomId);
            if (!$room)        json_out(['ok'=>false,'error'=>'Выберите номер']);
            if ($name === '')  json_out(['ok'=>false,'error'=>'Укажите имя гостя']);
            if ($phone === '') json_out(['ok'=>false,'error'=>'Укажите телефон']);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_out(['ok'=>false,'error'=>'Проверьте e-mail']);
            }
            if ($checkin === '' || $checkout === '') json_out(['ok'=>false,'error'=>'Укажите даты заезда и выезда']);

            $nights = nights_between($checkin, $checkout);
            if ($nights < 1) json_out(['ok'=>false,'error'=>'Дата выезда должна быть позже даты заезда']);
            if ($guests < 1) json_out(['ok'=>false,'error'=>'Укажите количество гостей']);
            if ($guests > (int)$room['capacity']) {
                $cap = (int)$room['capacity'];
                json_out(['ok'=>false,'error'=>'В номере «'.$room['name'].'» максимум '.$cap.' '.plural_ru($cap, 'гость', 'гостя', 'гостей')]);
            }

            $total = booking_total((int)$room['price'], $nights);

            $stmt = db()->prepare(
                "INSERT INTO bookings
                 (user_login, room_id, room_name, guest_name, phone, email,
                  checkin, checkout, guests, nights, total, comment, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')"
            );
            $stmt->bind_param('ssssssssiiis',
                $u['login'], $room['id'], $room['name'],
                $name, $phone, $email,
                $checkin, $checkout, $guests, $nights, $total, $comment
            );
            if (!$stmt->execute()) {
                json_out(['ok'=>false,'error'=>'Ошибка сохранения: '.$stmt->error]);
            }
            $stmt->close();

            json_out(['ok'=>true, 'state' => state_for_client()]);
        }

        case 'booking.cancel': {
            $u = current_user();
            if (!$u) json_out(['ok'=>false,'error'=>'Требуется вход'], 401);

            $id = (int)($_POST['id'] ?? 0);
            $stmt = db()->prepare(
                "UPDATE bookings SET status='cancel'
                 WHERE id = ? AND user_login = ? AND status = 'new'"
            );
            $stmt->bind_param('is', $id, $u['login']);
            $stmt->execute();
            $ok = $stmt->affected_rows > 0;
            $stmt->close();

            if (!$ok) json_out(['ok'=>false,'error'=>'Заявка не найдена или уже обработана']);
            json_out(['ok'=>true, 'state' => state_for_client()]);
        }

        /* ================= ОТЗЫВЫ ================= */

        case 'review.create': {
            $u = current_user();
            if (!$u) json_out(['ok'=>false,'error'=>'Требуется вход'], 401);

            $roomId = trim((string)($_POST['roomId'] ?? ''));
            $rating = (int)($_POST['rating'] ?? 0);
            $text   = trim((string)($_POST['text'] ?? ''));

            $room = find_room($roomId);
            if (!$room)                     json_out(['ok'=>false,'error'=>'Выберите номер']);
            if ($rating < 1 || $rating > 5) json_out(['ok'=>false,'error'=>'Поставьте оценку']);
            if (text_len($text) < 10)       json_out(['ok'=>false,'error'=>'Отзыв — минимум 10 символов']);

            $stmt = db()->prepare(
                "INSERT INTO reviews (user_login, user_name, room_id, room_name, rating, text)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ssssis',
                $u['login'], $u['name'], $room['id'], $room['name'], $rating, $text
            );
            if (!$stmt->execute()) {
                json_out(['ok'=>false,'error'=>'Ошибка сохранения: '.$stmt->error]);
            }
            $stmt->close();

            json_out(['ok'=>true, 'state' => state_for_client()]);
        }

        /* ================= АДМИН ================= */

        case 'admin.login': {
            $login = trim((string)($_POST['login'] ?? ''));
            $pass  = (string)($_POST['pass'] ?? '');

            if ($login !== ADMIN_LOGIN || $pass !== ADMIN_PASS) {
                json_out(['ok'=>false,'error'=>'Неверный логин или пароль']);
            }
            $_SESSION['admin'] = true;
            json_out(['ok'=>true]);
        }

        case 'admin.logout': {
            unset($_SESSION['admin']);
            json_out(['ok'=>true]);
        }

        case 'admin.data': {
            if (!is_admin()) json_out(['ok'=>false,'error'=>'Нет доступа'], 403);

            $stats = ['total'=>0,'new'=>0,'confirm'=>0,'paid'=>0,'done'=>0,'cancel'=>0];
            $res = db()->query("SELECT status FROM bookings");
            while ($r = $res->fetch_assoc()) {
                $stats['total']++;
                $st = $r['status'] ?? 'new';
                if (isset($stats[$st])) $stats[$st]++;
            }

            $list = [];
            $res = db()->query(
                "SELECT id, user_login, room_id, room_name, guest_name, phone, email,
                        checkin, checkout, guests, nights, total, comment, status
                 FROM bookings ORDER BY id DESC"
            );
            while ($r = $res->fetch_assoc()) {
                $list[] = [
                    'id'       => (int)$r['id'],
                    'user'     => $r['user_login'],
                    'roomId'   => $r['room_id'],
                    'roomName' => $r['room_name'],
                    'name'     => $r['guest_name'],
                    'phone'    => $r['phone'],
                    'email'    => $r['email'] ?? '',
                    'checkin'  => $r['checkin'],
                    'checkout' => $r['checkout'],
                    'guests'   => (int)$r['guests'],
                    'nights'   => (int)$r['nights'],
                    'total'    => (int)$r['total'],
                    'comment'  => $r['comment'] ?? '',
                    'status'   => $r['status'],
                ];
            }

            json_out(['ok'=>true, 'bookings'=>$list, 'stats'=>$stats]);
        }

        case 'admin.setStatus': {
            if (!is_admin()) json_out(['ok'=>false,'error'=>'Нет доступа'], 403);

            $id     = (int)($_POST['id'] ?? 0);
            $status = (string)($_POST['status'] ?? '');
            if (!in_array($status, STATUSES, true)) {
                json_out(['ok'=>false,'error'=>'Недопустимый статус']);
            }

            $stmt = db()->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $id);
            $stmt->execute();
            $ok = $stmt->affected_rows >= 0;
            $stmt->close();

            if (!$ok) json_out(['ok'=>false,'error'=>'Не удалось обновить']);
            json_out(['ok'=>true]);
        }

        default:
            json_out(['ok'=>false,'error'=>'Неизвестное действие'], 400);
    }
} catch (Throwable $e) {
    json_out(['ok'=>false,'error'=>'Ошибка сервера: '.$e->getMessage()], 500);
}
