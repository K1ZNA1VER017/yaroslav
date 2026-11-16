<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const ADMIN_LOGIN = 'Admin26';
const ADMIN_PASS  = 'Demo26';

/* Статусы брони */
const STATUSES = ['new', 'confirm', 'paid', 'done', 'cancel'];

/* ---------- утилиты ---------- */

function text_len(string $s): int {
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function current_user(): ?array {
    if (empty($_SESSION['user'])) return null;
    $stmt = db()->prepare("SELECT login, name FROM users WHERE login = ? LIMIT 1");
    $stmt->bind_param('s', $_SESSION['user']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function is_admin(): bool {
    return !empty($_SESSION['admin']);
}

/* ---------- каталог номеров ---------- */

/**
 * Каталог хранится в data/rooms.json — его же читает статичное превью.
 * Если файла нет или он битый, используется встроенный резервный список.
 */
function rooms(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $file = __DIR__ . '/data/rooms.json';
    if (is_file($file)) {
        $raw = file_get_contents($file);
        $list = json_decode((string)$raw, true);
        if (is_array($list) && $list) {
            $cache = $list;
            return $cache;
        }
    }

    $cache = [
        ['id'=>'r1','number'=>'101','name'=>'Люкс «Классик»','type'=>'Люкс','capacity'=>2,
         'beds'=>'1 двуспальная кровать','size'=>42,'price'=>8900,'image'=>'assets/img/r1.jpg',
         'rating'=>4.9,'desc'=>'Просторный номер с зоной отдыха и большой ванной комнатой.',
         'features'=>['Завтрак включён','Вид на двор','Кондиционер']],
        ['id'=>'r2','number'=>'205','name'=>'Стандарт Twin','type'=>'Стандарт','capacity'=>2,
         'beds'=>'2 односпальные кровати','size'=>24,'price'=>4900,'image'=>'assets/img/r2.jpg',
         'rating'=>4.6,'desc'=>'Светлый номер с двумя отдельными кроватями.',
         'features'=>['Завтрак включён','Рабочий стол','Wi-Fi']],
    ];
    return $cache;
}

function find_room(string $id): ?array {
    foreach (rooms() as $r) if ($r['id'] === $id) return $r;
    return null;
}

/** Сколько ночей между датами (0 — если даты некорректны) */
function nights_between(string $in, string $out): int {
    $a = strtotime($in);
    $b = strtotime($out);
    if (!$a || !$b || $b <= $a) return 0;
    return (int)round(($b - $a) / 86400);
}

/** Склонение русских существительных: 1 гость / 2 гостя / 5 гостей */
function plural_ru(int $n, string $one, string $few, string $many): string {
    $a = abs($n) % 100;
    $b = $a % 10;
    if ($a > 10 && $a < 20) return $many;
    if ($b > 1 && $b < 5) return $few;
    if ($b === 1) return $one;
    return $many;
}

/** Итоговая стоимость проживания */
function booking_total(int $pricePerNight, int $nights): int {
    return $pricePerNight * max(1, $nights);
}

/* ---------- состояние для клиента ---------- */

function state_for_client(): array {
    $user = current_user();
    $bookings = [];
    $reviews  = [];

    if ($user) {
        $stmt = db()->prepare(
            "SELECT id, room_id, room_name, guest_name, phone, email,
                    checkin, checkout, guests, nights, total, comment, status
             FROM bookings WHERE user_login = ? ORDER BY id ASC"
        );
        $stmt->bind_param('s', $user['login']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $bookings[] = [
                'id'        => (int)$r['id'],
                'user'      => $user['login'],
                'roomId'    => $r['room_id'],
                'roomName'  => $r['room_name'],
                'name'      => $r['guest_name'],
                'phone'     => $r['phone'],
                'email'     => $r['email'] ?? '',
                'checkin'   => $r['checkin'],
                'checkout'  => $r['checkout'],
                'guests'    => (int)$r['guests'],
                'nights'    => (int)$r['nights'],
                'total'     => (int)$r['total'],
                'comment'   => $r['comment'] ?? '',
                'status'    => $r['status'],
            ];
        }
        $stmt->close();

        $stmt = db()->prepare(
            "SELECT id, room_id, room_name, rating, text, created_at
             FROM reviews WHERE user_login = ? ORDER BY id ASC"
        );
        $stmt->bind_param('s', $user['login']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $reviews[] = [
                'id'       => (int)$r['id'],
                'user'     => $user['login'],
                'roomId'   => $r['room_id'],
                'roomName' => $r['room_name'],
                'rating'   => (int)$r['rating'],
                'text'     => $r['text'],
                'date'     => date('d.m.Y', strtotime($r['created_at'])),
            ];
        }
        $stmt->close();
    }

    return [
        'user'     => $user ? ['login' => $user['login'], 'name' => $user['name']] : null,
        'admin'    => is_admin(),
        'bookings' => $bookings,
        'reviews'  => $reviews,
    ];
}
