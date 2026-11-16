<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $conn = DB_SOCKET !== ''
        ? @new mysqli(null, DB_USER, DB_PASS, null, 0, DB_SOCKET)
        : @new mysqli(DB_HOST, DB_USER, DB_PASS, null, DB_PORT);

    if ($conn->connect_error) {
        throw new RuntimeException('Не удалось подключиться к MySQL: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', DB_NAME) ?: 'hotel_db';
    $conn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($dbName);

    ensure_schema($conn);
    return $conn;
}

function ensure_column(mysqli $c, string $table, string $column, string $definition): void {
    $stmt = $c->prepare(
        "SELECT COUNT(*) AS cnt
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)($row['cnt'] ?? 0) === 0) {
        $c->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function ensure_schema(mysqli $c): void {
    $c->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(64)  NOT NULL UNIQUE,
            name  VARCHAR(128) NOT NULL,
            phone VARCHAR(32)  NULL,
            pass  VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $c->query("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_login   VARCHAR(64)  NOT NULL,
            room_id      VARCHAR(16)  NOT NULL,
            room_name    VARCHAR(128) NOT NULL,
            guest_name   VARCHAR(128) NOT NULL,
            phone        VARCHAR(32)  NOT NULL,
            email        VARCHAR(128) NULL,
            checkin      VARCHAR(16)  NOT NULL,
            checkout     VARCHAR(16)  NOT NULL,
            guests       INT          NOT NULL DEFAULT 1,
            nights       INT          NOT NULL DEFAULT 1,
            extras       TEXT         NULL,
            extras_total INT          NOT NULL DEFAULT 0,
            total        INT          NOT NULL DEFAULT 0,
            comment      TEXT         NULL,
            status       VARCHAR(16)  NOT NULL DEFAULT 'new',
            created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user   (user_login),
            INDEX idx_status (status),
            INDEX idx_room   (room_id),
            INDEX idx_dates  (room_id, checkin, checkout)
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

    // Безопасное обновление ранее созданных таблиц
    ensure_column($c, 'users', 'phone', 'VARCHAR(32) NULL AFTER name');
    ensure_column($c, 'bookings', 'extras', 'TEXT NULL AFTER nights');
    ensure_column($c, 'bookings', 'extras_total', 'INT NOT NULL DEFAULT 0 AFTER extras');
}
