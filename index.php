<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

/**
 * Единая точка входа PHP + MySQL.
 * Использует index.html как единый шаблон интерфейса, внедряя серверное
 * начальное состояние (каталог, сессию, брони и отзывы) без дублирования верстки.
 */
$bootstrap = app_bootstrap_payload();
$templatePath = __DIR__ . '/index.html';

if (!is_file($templatePath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Шаблон index.html не найден.';
    exit;
}

$html = (string)file_get_contents($templatePath);
$payloadJson = json_encode(
    $bootstrap,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
);
$scriptTag = "<script>window.__APP_BOOTSTRAP__ = {$payloadJson};</script>";

header('Content-Type: text/html; charset=utf-8');
echo str_replace('<!-- APP_BOOTSTRAP -->', $scriptTag, $html);
