<?php

declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

try {
    $user = require_user();
    $events = new EventService(App::db());
    $reports = new ReportService(App::db(), $events);

    json_response(['ok' => true, 'report' => $reports->summary((int) $user['id'])]);
} catch (Throwable $exception) {
    json_response(['ok' => false, 'error' => $exception->getMessage()], 500);
}
