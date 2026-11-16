<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

function current_user(): ?array {
    if (empty($_SESSION['user'])) {
        return null;
    }
    $stmt = db()->prepare("SELECT login, name, phone FROM users WHERE login = ? LIMIT 1");
    $stmt->bind_param('s', $_SESSION['user']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }
    return [
        'login' => (string)$row['login'],
        'name'  => (string)$row['name'],
        'phone' => (string)($row['phone'] ?? ''),
    ];
}

function is_admin(): bool {
    return !empty($_SESSION['admin']);
}

function auth_register(string $login, string $name, string $pass, string $phone = ''): array {
    $login = trim($login);
    $name  = trim($name);
    $phone = trim($phone);

    if (text_len($login) < 3) {
        throw new InvalidArgumentException('Логин — минимум 3 символа');
    }
    if (text_len($pass) < 4) {
        throw new InvalidArgumentException('Пароль — минимум 4 символа');
    }
    if ($name === '') {
        $name = $login;
    }

    $c = db();
    $stmt = $c->prepare("SELECT id FROM users WHERE login = ? LIMIT 1");
    $stmt->bind_param('s', $login);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        throw new InvalidArgumentException('Такой логин уже занят');
    }
    $stmt->close();

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $c->prepare("INSERT INTO users (login, name, phone, pass) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $login, $name, $phone, $hash);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Ошибка регистрации: ' . $err);
    }
    $stmt->close();

    $_SESSION['user'] = $login;
    return ['login' => $login, 'name' => $name, 'phone' => $phone];
}

function auth_login(string $login, string $pass): array {
    $login = trim($login);
    $stmt = db()->prepare("SELECT login, name, phone, pass FROM users WHERE login = ? LIMIT 1");
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$u) {
        throw new InvalidArgumentException('Пользователь не найден');
    }
    if (!password_verify($pass, (string)$u['pass'])) {
        throw new InvalidArgumentException('Неверный пароль');
    }

    $_SESSION['user'] = (string)$u['login'];
    return [
        'login' => (string)$u['login'],
        'name'  => (string)$u['name'],
        'phone' => (string)($u['phone'] ?? ''),
    ];
}

/**
 * Бесшовное создание или вход в аккаунт гостя прямо из формы бронирования.
 */
function auth_ensure_guest_account(string $name, string $phone, string $pass): array {
    $existing = current_user();
    if ($existing) {
        return $existing;
    }

    $digits = normalize_phone_digits($phone);
    $login = $digits !== '' ? ('g' . substr($digits, -10)) : '';
    if (text_len($login) < 3) {
        throw new InvalidArgumentException('Укажите корректный номер телефона для оформления брони');
    }
    if (text_len($pass) < 4) {
        throw new InvalidArgumentException('Придумайте пароль для личного кабинета (минимум 4 символа)');
    }

    $stmt = db()->prepare("SELECT login, name, phone, pass FROM users WHERE login = ? LIMIT 1");
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($u) {
        if (!password_verify($pass, (string)$u['pass'])) {
            throw new InvalidArgumentException('Аккаунт с этим телефоном уже существует. Войдите или проверьте пароль.');
        }
        $_SESSION['user'] = (string)$u['login'];
        return [
            'login' => (string)$u['login'],
            'name'  => (string)$u['name'],
            'phone' => (string)($u['phone'] ?? $phone),
        ];
    }

    return auth_register($login, $name, $pass, $phone);
}

function auth_logout(): void {
    unset($_SESSION['user']);
}

function admin_login(string $login, string $pass): void {
    if (trim($login) !== ADMIN_LOGIN || $pass !== ADMIN_PASS) {
        throw new InvalidArgumentException('Неверный логин или пароль администратора');
    }
    $_SESSION['admin'] = true;
}

function admin_logout(): void {
    unset($_SESSION['admin']);
}
