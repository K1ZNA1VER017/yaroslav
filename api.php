<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');

try {
    switch ($action) {
        /* ================= ПОЛЬЗОВАТЕЛЬ ================= */

        case 'register': {
            auth_register(
                (string)($_POST['login'] ?? ''),
                (string)($_POST['name'] ?? ''),
                (string)($_POST['pass'] ?? ''),
                (string)($_POST['phone'] ?? '')
            );
            json_out(['ok' => true, 'state' => state_for_client()]);
        }

        case 'login': {
            auth_login(
                (string)($_POST['login'] ?? ''),
                (string)($_POST['pass'] ?? '')
            );
            json_out(['ok' => true, 'state' => state_for_client()]);
        }

        case 'logout': {
            auth_logout();
            json_out(['ok' => true, 'state' => state_for_client()]);
        }

        case 'state': {
            json_out(['ok' => true, 'state' => state_for_client()]);
        }

        /* ================= НОМЕРА И ДОСТУПНОСТЬ ================= */

        case 'rooms.availability': {
            $checkin  = trim((string)($_POST['checkin'] ?? $_GET['checkin'] ?? ''));
            $checkout = trim((string)($_POST['checkout'] ?? $_GET['checkout'] ?? ''));
            $unavailable = rooms_unavailable_ids($checkin, $checkout);
            json_out(['ok' => true, 'unavailable' => $unavailable]);
        }

        /* ================= БРОНИРОВАНИЕ НОМЕРА ================= */

        case 'booking.create': {
            $booking = booking_create($_POST);
            json_out(['ok' => true, 'booking' => $booking, 'state' => state_for_client()]);
        }

        case 'booking.cancel': {
            $u = current_user();
            if (!$u) {
                json_out(['ok' => false, 'error' => 'Требуется вход'], 401);
            }
            $id = (int)($_POST['id'] ?? 0);
            booking_cancel($id, $u['login']);
            json_out(['ok' => true, 'state' => state_for_client()]);
        }

        /* ================= ОТЗЫВЫ ================= */

        case 'review.create': {
            $u = current_user();
            if (!$u) {
                json_out(['ok' => false, 'error' => 'Требуется вход'], 401);
            }
            review_create(
                $u,
                (string)($_POST['roomId'] ?? ''),
                (int)($_POST['rating'] ?? 0),
                (string)($_POST['text'] ?? '')
            );
            json_out(['ok' => true, 'state' => state_for_client()]);
        }

        case 'reviews.list': {
            json_out(['ok' => true, 'reviews' => reviews_public(30)]);
        }

        /* ================= АДМИН-ПАНЕЛЬ ================= */

        case 'admin.login': {
            admin_login(
                (string)($_POST['login'] ?? ''),
                (string)($_POST['pass'] ?? '')
            );
            json_out(['ok' => true]);
        }

        case 'admin.logout': {
            admin_logout();
            json_out(['ok' => true]);
        }

        case 'admin.data': {
            if (!is_admin()) {
                json_out(['ok' => false, 'error' => 'Нет доступа'], 403);
            }
            $dashboard = admin_bookings_dashboard();
            json_out([
                'ok'       => true,
                'bookings' => $dashboard['bookings'],
                'stats'    => $dashboard['stats'],
                'reviews'  => reviews_public(50),
            ]);
        }

        case 'admin.setStatus': {
            if (!is_admin()) {
                json_out(['ok' => false, 'error' => 'Нет доступа'], 403);
            }
            admin_update_status(
                (int)($_POST['id'] ?? 0),
                (string)($_POST['status'] ?? '')
            );
            json_out(['ok' => true]);
        }

        default:
            json_out(['ok' => false, 'error' => 'Неизвестное действие'], 400);
    }
} catch (InvalidArgumentException $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
}
