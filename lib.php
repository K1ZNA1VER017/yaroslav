<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const ADMIN_LOGIN = 'Admin26';
const ADMIN_PASS  = 'Demo26';
const STATUSES    = ['new', 'confirm', 'assigned', 'done', 'cancel'];

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

/* ---------- каталог залов ---------- */

function halls(): array {
    return [
        ['id'=>'h1','name'=>'Зал «Империал»','icon'=>'🏛️','image'=>'b1.png','capacity'=>150,'price'=>45000,'type'=>'Классический','desc'=>'Роскошный зал с колоннами и люстрами.'],
        ['id'=>'h2','name'=>'Лофт «Фабрика»','icon'=>'🏭','image'=>'b2.png','capacity'=>80,'price'=>28000,'type'=>'Лофт','desc'=>'Индустриальный стиль, панорамные окна.'],
        ['id'=>'h3','name'=>'Банкет-холл «Восток»','icon'=>'🕌','image'=>'b3.png','capacity'=>200,'price'=>60000,'type'=>'Восточный','desc'=>'Атмосфера Востока, кальянная зона.'],
        ['id'=>'h4','name'=>'Терраса «Закат»','icon'=>'🌅','image'=>'b4.png','capacity'=>60,'price'=>32000,'type'=>'Открытая','desc'=>'Вид на город, идеально для летних событий.'],
        ['id'=>'h5','name'=>'Зал «Мираж»','icon'=>'✨','image'=>'b5.png','capacity'=>120,'price'=>39000,'type'=>'Современный','desc'=>'Зеркальные потолки и световое шоу.'],
        ['id'=>'h6','name'=>'Ресторан «Сакура»','icon'=>'🌸','image'=>'b6.png','capacity'=>90,'price'=>35000,'type'=>'Ресторан','desc'=>'Японская кухня и сад внутри зала.'],
    ];
}

function find_hall(string $id): ?array {
    foreach (halls() as $h) if ($h['id'] === $id) return $h;
    return null;
}

/* ---------- состояние для клиента ---------- */

function state_for_client(): array {
    $user = current_user();
    $bookings = [];
    $reviews  = [];

    if ($user) {
        $stmt = db()->prepare(
            "SELECT id, hall_id, hall_name, name, phone, `date`, guests, comment, status
             FROM bookings WHERE user_login = ? ORDER BY id ASC"
        );
        $stmt->bind_param('s', $user['login']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $bookings[] = [
                'id'       => (int)$r['id'],
                'user'     => $user['login'],
                'hallId'   => $r['hall_id'],
                'hallName' => $r['hall_name'],
                'name'     => $r['name'],
                'phone'    => $r['phone'],
                'date'     => $r['date'],
                'guests'   => (int)$r['guests'],
                'comment'  => $r['comment'] ?? '',
                'status'   => $r['status'],
            ];
        }
        $stmt->close();

        $stmt = db()->prepare(
            "SELECT id, hall_id, hall_name, rating, text, created_at
             FROM reviews WHERE user_login = ? ORDER BY id ASC"
        );
        $stmt->bind_param('s', $user['login']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $reviews[] = [
                'id'       => (int)$r['id'],
                'user'     => $user['login'],
                'hallId'   => $r['hall_id'],
                'hallName' => $r['hall_name'],
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