<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;

try {
    (new App())->run();
} catch (Throwable $e) {
    http_response_code(500);

    $debug = ($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'true') === 'true';

    if ($debug) {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>500 Internal Error</title></head><body style="font-family:sans-serif;padding:2rem;">';
        echo '<h1 style="color:#d9534f;">500 Internal Server Error</h1>';
        echo '<p><strong>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</strong></p>';
        echo '<pre style="background:#f8f9fa;padding:1rem;border-radius:4px;overflow:auto;">' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '</body></html>';
    } else {
        echo '<h1>500 Internal Server Error</h1>';
        echo '<p>Произошла непредвиденная ошибка. Пожалуйста, попробуйте позже.</p>';
    }
}
