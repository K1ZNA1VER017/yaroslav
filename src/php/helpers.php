<?php
declare(strict_types=1);

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

function is_valid_iso_date(string $date): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    $parts = explode('-', $date);
    return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

function nights_between(string $in, string $out): int {
    if (!is_valid_iso_date($in) || !is_valid_iso_date($out)) {
        return 0;
    }
    $a = strtotime($in . ' 00:00:00 UTC');
    $b = strtotime($out . ' 00:00:00 UTC');
    if (!$a || !$b || $b <= $a) {
        return 0;
    }
    return (int)round(($b - $a) / 86400);
}

function plural_ru(int $n, string $one, string $few, string $many): string {
    $a = abs($n) % 100;
    $b = $a % 10;
    if ($a > 10 && $a < 20) return $many;
    if ($b > 1 && $b < 5) return $few;
    if ($b === 1) return $one;
    return $many;
}

function booking_total(int $pricePerNight, int $nights, int $extrasTotal = 0): int {
    return ($pricePerNight * max(1, $nights)) + max(0, $extrasTotal);
}

function normalize_phone_digits(string $phone): string {
    return preg_replace('/\D+/', '', $phone) ?? '';
}

function parse_booking_extras(string $rawIds): array {
    if (trim($rawIds) === '') {
        return ['items' => [], 'total' => 0];
    }
    $ids = array_values(array_unique(array_filter(array_map('trim', explode(',', $rawIds)))));
    $items = [];
    $total = 0;
    foreach ($ids as $id) {
        if (isset(BOOKING_EXTRAS[$id])) {
            $items[] = BOOKING_EXTRAS[$id];
            $total += (int)BOOKING_EXTRAS[$id]['price'];
        }
    }
    return ['items' => $items, 'total' => $total];
}
