<?php
declare(strict_types=1);

/**
 * Параметры подключения. Значения по умолчанию рассчитаны на OpenServer
 * (хост «MySQL-8.4»), но их можно переопределить переменными окружения —
 * это удобно для локальной отладки и деплоя.
 */
define('DB_HOST', getenv('DB_HOST') ?: 'MySQL-8.4');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_USER', getenv('DB_USER') !== false && getenv('DB_USER') !== '' ? getenv('DB_USER') : 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');   // в XAMPP пусто, в OpenServer часто 'root'
define('DB_NAME', getenv('DB_NAME') ?: 'hotel_db');
define('DB_SOCKET', getenv('DB_SOCKET') ?: '');

function db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) return $conn;

    // 1. Подключаемся к серверу без указания БД и создаём её при необходимости
    $conn = DB_SOCKET !== ''
        ? @new mysqli(null, DB_USER, DB_PASS, null, 0, DB_SOCKET)
        : @new mysqli(DB_HOST, DB_USER, DB_PASS, null, DB_PORT);
    if ($conn->connect_error) {
        throw new RuntimeException('Не удалось подключиться к MySQL: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    $conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
                  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db(DB_NAME);

    // 2. Создаём таблицы, если их нет
    ensure_schema($conn);
    return $conn;
}

function ensure_schema(mysqli $c): void {
    $c->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(64)  NOT NULL UNIQUE,
            name  VARCHAR(128) NOT NULL,
            pass  VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    /* Брони номеров: даты заезда/выезда, число гостей, ночей и итоговая сумма */
    $c->query("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_login VARCHAR(64)  NOT NULL,
            room_id    VARCHAR(16)  NOT NULL,
            room_name  VARCHAR(128) NOT NULL,
            guest_name VARCHAR(128) NOT NULL,
            phone      VARCHAR(32)  NOT NULL,
            email      VARCHAR(128) NULL,
            checkin    VARCHAR(16)  NOT NULL,
            checkout   VARCHAR(16)  NOT NULL,
            guests     INT          NOT NULL DEFAULT 1,
            nights     INT          NOT NULL DEFAULT 1,
            total      INT          NOT NULL DEFAULT 0,
            comment    TEXT         NULL,
            status     VARCHAR(16)  NOT NULL DEFAULT 'new',
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user   (user_login),
            INDEX idx_status (status),
            INDEX idx_room   (room_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $c->query("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_login VARCHAR(64)  NOT NULL,
            user_name  VARCHAR(128) NOT NULL,
            room_id    VARCHAR(16)  NOT NULL,
            room_name  VARCHAR(128) NOT NULL,
            rating     TINYINT      NOT NULL,
            text       TEXT         NOT NULL,
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_login),
            INDEX idx_room (room_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
