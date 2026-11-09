<?php
declare(strict_types=1);

const DB_HOST = 'MySQL-8.4';
const DB_USER = 'root';
const DB_PASS = '';              // в XAMPP пусто, в OpenServer часто 'root'
const DB_NAME = 'banket_db';

function db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) return $conn;

    // 1. Подключаемся к серверу без указания БД и создаём её при необходимости
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
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

    $c->query("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_login VARCHAR(64)  NOT NULL,
            hall_id    VARCHAR(16)  NOT NULL,
            hall_name  VARCHAR(128) NOT NULL,
            name       VARCHAR(128) NOT NULL,
            phone      VARCHAR(32)  NOT NULL,
            `date`     VARCHAR(32)  NOT NULL,
            guests     INT          NOT NULL DEFAULT 1,
            comment    TEXT         NULL,
            status     VARCHAR(16)  NOT NULL DEFAULT 'new',
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user   (user_login),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $c->query("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_login VARCHAR(64)  NOT NULL,
            user_name  VARCHAR(128) NOT NULL,
            hall_id    VARCHAR(16)  NOT NULL,
            hall_name  VARCHAR(128) NOT NULL,
            rating     TINYINT      NOT NULL,
            text       TEXT         NOT NULL,
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_login),
            INDEX idx_hall (hall_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}