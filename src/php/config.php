<?php
declare(strict_types=1);

/**
 * Конфигурация приложения и подключения к MySQL.
 * Значения по умолчанию рассчитаны на OpenServer (хост «MySQL-8.4»),
 * но переопределяются переменными окружения для XAMPP, Docker и продакшена.
 */
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'MySQL-8.4');
    define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
    define('DB_USER', getenv('DB_USER') !== false && getenv('DB_USER') !== '' ? getenv('DB_USER') : 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    define('DB_NAME', getenv('DB_NAME') ?: 'hotel_db');
    define('DB_SOCKET', getenv('DB_SOCKET') ?: '');
}

if (!defined('ADMIN_LOGIN')) {
    define('ADMIN_LOGIN', getenv('ADMIN_LOGIN') ?: 'Admin26');
    define('ADMIN_PASS', getenv('ADMIN_PASS') ?: 'Demo26');
}

const BOOKING_STATUSES = ['new', 'confirm', 'paid', 'done', 'cancel'];

/**
 * Каталог дополнительных услуг (цены проверяются и считаются на сервере).
 */
const BOOKING_EXTRAS = [
    'transfer' => ['id' => 'transfer', 'title' => 'Трансфер из аэропорта',     'price' => 2500],
    'late_out' => ['id' => 'late_out', 'title' => 'Поздний выезд до 16:00',    'price' => 1800],
    'romance'  => ['id' => 'romance',  'title' => 'Романтический комплимент',  'price' => 3200],
    'crib'     => ['id' => 'crib',     'title' => 'Детская кроватка',          'price' => 0],
];
