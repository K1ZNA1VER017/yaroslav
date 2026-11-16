<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/rooms.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/bookings.php';
require_once __DIR__ . '/reviews.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function state_for_client(): array {
    $user = current_user();
    $bookings = $user ? bookings_for_user($user['login']) : [];
    $reviews  = $user ? reviews_for_user($user['login']) : [];

    return [
        'user'          => $user,
        'admin'         => is_admin(),
        'bookings'      => $bookings,
        'reviews'       => $reviews,
        'publicReviews' => reviews_public(18),
    ];
}

function app_bootstrap_payload(): array {
    $state = [
        'user'          => null,
        'admin'         => false,
        'bookings'      => [],
        'reviews'       => [],
        'publicReviews' => [],
    ];
    $dbReady = true;
    $dbError = null;

    try {
        $state = state_for_client();
    } catch (Throwable $e) {
        $dbReady = false;
        $dbError = $e->getMessage();
    }

    return [
        'mode'    => $dbReady ? 'php' : 'fallback',
        'dbReady' => $dbReady,
        'dbError' => $dbError,
        'rooms'   => rooms(),
        'extras'  => array_values(BOOKING_EXTRAS),
        'state'   => $state,
    ];
}
